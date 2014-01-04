<?php

require_once dirname(__FILE__).'/../../classes/CrowdinPHP.php';
require_once dirname(__FILE__).'/../../classes/TranslationsExtractor.php';

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
		if (basename($ps_path) === 'lang_content.php')
			$path = $version.'/email_contents';
		// Module file => modules/name
		else if (preg_match('#^modules/([^/]+)/#', $ps_path, $m))
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
		else if (preg_match('#^mails/.*?/lang.php$#', $ps_path))
			$path = $version.'/email_subjects';
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
		$export_pattern = '/'._PS_VERSION_.'/'.$export_pattern;

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
				$tasks[] = array('action' => 'exportSourceFile', 'data' => $data);
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
				'message' => 'Could not find sources, please try exporting the "As in code" language!'
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
		else if ($action['action'] === 'exportSourceFile')
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
		else if ($action['action'] === 'exportTranslationFile')
		{
			$res = $this->crowdin->uploadTranslations(
				$action['language'],
				_PS_ROOT_DIR_.'/'.$action['relsrc'],
				$action['dest']
			);
			if ($res['success'])
				$message = 'Exported translations ('.$action['language'].') for: '.$action['dest'];				
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
				'message' => 'Done :)',
			);
	}

	public function ajaxDownloadTranslationsAction($payload)
	{
		$data = $this->crowdin->downloadTranslations();
		if ($data)
		{
			$file = tempnam(null, 'translatools');
			file_put_contents($file, $data);

			$za = new ZipArchive();
			$za->open($file);

			for ($i=0; $i<$za->numFiles; $i++)
			{
				$stat = $za->statIndex($i);
				$name = $stat['name'];
				$m = array();
				if (preg_match('#^'.preg_quote(_PS_VERSION_).'/(.*?\.php)$#', $name, $m))
				{
					$target_path = $m[1];
					$contents = $za->getFromIndex($i);

					$ok = $this->module->importTranslationFile($target_path, $contents);
					
					if ($ok !== true)
						return array('success' => false, 'message' => $ok);
				}

			}

			return array('success' => true, 'message' => 'Done :)');
		}
		else
			return array('success' => false, 'message' => 'Could not download archive from Crowdin');
	}

	public function ajaxExportTranslationsAction($payload)
	{
		$te = new TranslationsExtractor();
		$te->setRootDir(_PS_ROOT_DIR_);

		$built = $te->buildFromTranslationFiles(dirname(__FILE__).'/../../packs/en');

		if ($built !== true)
		{
			return array('success' => false, 'message' => $built);
		}

		$tasks = array();

		// Build the necessary languages
		foreach (Language::getLanguages() as $lang)
		{
			// Don't export English or Aragonese to Crowdin!
			if ($lang['iso_code'] === 'en' || $lang['iso_code'] === 'an')
				continue;

			$packs_root = realpath(dirname(__FILE__).'/../../packs/');

			$te->setLanguage($lang['iso_code']);
			$te->fill();
			$wrote = $te->write($packs_root);

			foreach ($wrote as $file)
			{
				$relpath = substr($file, strlen(_PS_ROOT_DIR_)+1);
				$tasks[] = array(
					'action' => 'exportTranslationFile',
					'language' => $this->module->getCrowdinLanguageCode($lang['iso_code']),
					'relsrc' => $relpath,
					'dest' => $this->getCrowdinPath(
						_PS_VERSION_,
						substr($file, strlen($packs_root.'/'.$lang['iso_code'])+1)
					)
				);
			}
		}

		return array(
			'success' => true,
			'message' => 'Built packs, exporting now.',
			'next-action' => 'dequeue',
			'next-payload' => $tasks
		);
	}

	public function ajaxSwitchVirtualLanguageAction($payload)
	{
		$jipt_id_lang = Language::getIdByIso('an');

		if ($payload['value'])
		{
			if ($jipt_id_lang)
			{
				Configuration::updateValue('JIPT_PREVIOUS_ID_LANG', $this->context->employee->id_lang);

				$this->context->employee->id_lang = $jipt_id_lang;
				$this->context->employee->save();

				return array('success' => true, 'language' => $jipt_id_lang);
			}

			return array('success' => false, 'language' => $this->context->employee->id_lang);
		}
		else
		{
			$language_to_set = Configuration::get('JIPT_PREVIOUS_ID_LANG');

			if ($language_to_set == $jipt_id_lang || !$language_to_set)
				$language_to_set = Configuration::get('PS_LANG_DEFAULT');

			$this->context->employee->id_lang = $language_to_set;
			$this->context->employee->save();
			Configuration::deleteByName('JIPT_PREVIOUS_ID_LANG');
			return array('success' => true, 'language' => $language_to_set);
		}
	}
}	