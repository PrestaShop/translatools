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

	public function ajaxExportSourcesAction($payload)
	{
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

					$crowdin_path = $this->getCrowdinPath(_PS_VERSION_, $ps_path);

					$files_to_export[] = array(
						'real_relative_path' => substr(realpath($path), strlen(_PS_ROOT_DIR_)+1),
						'crowdin_path' => $crowdin_path
					);

					$dirs_to_create[dirname($crowdin_path)] = true;
				}
			}

			$dirs_to_create = array_keys($dirs_to_create);

			$tasks = array();

			
			foreach ($dirs_to_create as $dir)
			{
				$tasks[] = array('action' => 'createDirectory', 'path' => $dir);
			}

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
			if (($res=$this->crowdin->createDirectory($action['path'])) === true)
				$message = 'Created directory: '.$action['path'];
			else
			{
				$ok = false;
				$message = $res;
			}
		}
		else if ($action['action'] === 'exportFile')
		{
			$data = array();

			$data['realpath'] = _PS_ROOT_DIR_.'/'.$action['data']['real_relative_path'];
			$data['path'] = $action['data']['crowdin_path'];
			$data['title'] = basename($data['path'], '.php');
			//$data['export-pattern'] = $this->getCrowdinExportPattern($action['data']['real_relative_path']);

			if (($res=$this->crowdin->addFile($data)) === true)
				$message = 'Exported file: '.$action['data']['crowdin_path'];
			else
			{
				$ok = false;
				$message = $res;
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