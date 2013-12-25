<?php

require_once dirname(__FILE__).'/../../classes/CrowdinPHP.php';

class AdminTranslatoolsController extends ModuleAdminController
{
	public function __construct()
	{
		$this->bootstrap = true;
		$this->crowdin = new CrowdinPHP(
			Configuration::get('CROWDIN_PROJECT_IDENTIFIER'),
			Configuration::get('CROWDIN_PROJECT_API_KEY')
		);
		
		parent::__construct();
		if (!$this->module->active)
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
	}

	public function run()
	{
		$action = Tools::getValue('action');
		$method = 'ajax'.ucfirst($action).'Action';
		if (is_callable(array($this, $method)))
		{
			$payload = json_decode(file_get_contents('php://input'), true);
			die(json_encode($this->$method($payload)));
		}
		else
			die(json_encode(array('success' => false)));
	}

	public function getCrowdinPath($version, $ps_path)
	{
		$m = array();
		// Module file => modules/name
		if (preg_match('#^modules/([^/]+)/#', $ps_path, $m))
			$path = $version.'/modules/'.$m[1];
		// Theme module => modules/name::theme
		else if (preg_match('#^themes/([^/]+)/modules/([^/]+)/#', $ps_path, $m))
			$path = $version.'/modules/'.$m[2].'::'.$m[1];
		// Theme language file => theme::name
		else if (preg_match('#^themes/([^/]+)/lang#', $ps_path, $m))
			$path = $version.'/theme::'.$m[1];
		// Anything else in theme => name::theme
		else if (preg_match('#^themes/([^/]+)/#', $ps_path, $m))
			$path = $version.'/'.basename($ps_path,'.php').'::'.$m[1];
		// Admin, pdf, etc. => name
		else
			$path = $version.'/'.basename($ps_path,'.php');

		$path .= '.php';

		return $path;
	}

	public function getCrowdinExportPattern($path)
	{
		// Get interesting part of path,
		// i.e. as if we were at the root of the target
		// PrestaShop installation
		$path_parts = explode('/translatools/packs/en/', $path);
		$path = end($path_parts);
		
		$export_pattern = str_replace(
			array('/en.php', '/en/'), 
			array('/%two_letters_code%.php', '/%two_letters_code%/'),
			$path
		);

		// Prepend version
		$export_pattern = _PS_VERSION_.'/'.$export_pattern;

		return $export_pattern;
	}

	public function ajaxExportSourcesAction($payload)
	{
		$info = $this->crowdin->info();

		$path_to_sources = dirname(__FILE__).'/../../packs/en';

		$files_to_export = array();
		$dirs_to_create = array();

		if (is_dir($path_to_sources))
		{
			foreach (new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($path_to_sources)
			) as $path => $data)
			{
				if (preg_match('/(?:[a-z]{2}|admin|pdf|errors|tabs)\.php$/', $path))
				{
					$ps_path = substr($path, strlen($path_to_sources)+1);

					$dest = $this->getCrowdinPath(_PS_VERSION_, $ps_path);

					$files_to_export[] = array(
						'real_relative_path' => substr(realpath($path), strlen(_PS_ROOT_DIR_)+1),
						'dest' => $dest,
						'add_or_update' => isset($info['files'][$dest]) ? 'update' : 'add'
					);

					$dirs_to_create[dirname($dest)] = true;
				}
			}

			// Determine the new directories
			$dirs_to_create = array_diff(
				array_keys($dirs_to_create),
				$info['directories']
			);

			// Sort them in ascending order so that
			// we don't risk creating a dir twice
			// (they are created with parents)
			sort($dirs_to_create);

			// List what we need to do
			$tasks = array();
			
			// Need to create the directories
			// before putting files in them
			foreach ($dirs_to_create as $dir)
			{
				$tasks[] = array('action' => 'createDirectory', 'path' => $dir);
			}

			// Then we do the file!
			foreach ($files_to_export as $data)
			{
				$tasks[] = array('action' => 'exportFile', 'data' => $data);
			}

			return array(
				'success' => true,
				'message' => 'Found sources...',
				'next-payload' => $tasks,
				'next-action' => 'dequeue'
			);
		}
		else
		{
			return array(
				'success' => false,
				'message' => 'Could not find sources...'
			);
		}
	}

	public function ajaxDequeueAction($payload)
	{
		$action = array_shift($payload);

		$ok = true;
		$message = $action['action'];

		if ($action['action'] === 'createDirectory')
		{
			$res = $this->crowdin->createDirectory($action['path']);

			if ($res['success'])
				$message = 'Created directory: '.$action['path'];
			else
			{
				$ok = false;
				$message = $res['error']['message'];
			}
		}
		else if ($action['action'] === 'exportFile')
		{
			$data = array();

			$data['src'] = _PS_ROOT_DIR_.'/'.$action['data']['real_relative_path'];
			$data['dest'] = $action['data']['dest'];
			$data['title'] = basename($data['dest'], '.php');
			$data['export_pattern'] = $this->getCrowdinExportPattern($action['data']['real_relative_path']);

			$res = $this->crowdin->addOrUpdateFile($action['data']['add_or_update'], $data);

			if ($res['success'])
				$message = 'Exported file: '.$action['data']['dest'];
			else
			{
				$ok = false;
				$message = $res['error']['message'];
			}
		}

		if (count($payload) > 0)
			return array(
				'success' => $ok,
				'message' => $message,
				'next-action' => 'dequeue',
				'next-payload' => $payload
			);
		else
			return array(
				'success' => $ok,
				'message' => $message,
			);
	}
}	