<?php

require_once dirname(__FILE__).'/../../classes/CrowdinPHP.php';
require_once dirname(__FILE__).'/../../classes/TranslationsExtractor.php';
require_once dirname(__FILE__).'/../../classes/FilesLister.php';

class AdminTranslatoolsController extends ModuleAdminController
{
	public function __construct($standalone=false)
	{
		$this->bootstrap = true;
		$this->crowdin = new CrowdinPHP(
			Configuration::get('CROWDIN_PROJECT_IDENTIFIER'),
			Configuration::get('CROWDIN_PROJECT_API_KEY')
		);
		
		parent::__construct();

		if (!$standalone)
		{		
			if (!$this->module->active)
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
		}
	}

	public function init()
	{
		$action = Tools::getValue('action');
		$this->action = $action ? $action : 'default';
		$this->template = basename($this->action).'.tpl';

		parent::init();
	}

	public function postProcess()
	{
		if ($this->action && !$this->ajax && !method_exists($this, 'process'.Tools::ucfirst($this->action)))
		{
			$method = $this->action.'Action';

			foreach (array($this, $this->module) as $object)
			{
				if (is_callable(array($object, $method)))
				{
					$data = $object->$method();
					if (is_array($data))
						$this->context->smarty->assign($data);
					break;
				}
			}
			
		}
		else if ($this->action && $this->ajax && !method_exists($this, 'ajaxProcess'.Tools::ucfirst($this->action)))
		{
			$method = 'ajax'.Tools::ucfirst($this->action).'Action';
			foreach (array($this, $this->module) as $object)
			{
				if (is_callable(array($object, $method)))
				{
					$data = $object->$method(Tools::jsonDecode(Tools::file_get_contents('php://input'), true));
					die(Tools::jsonEncode($data));
				}
			}
		}
		else
		{
			parent::postProcess();
		}
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
			array('/%locale%.php', '/%locale%/'),
			$path
		);

		// Prepend version
		$export_pattern = '/'.$this->module->getPackVersion().'/'.$export_pattern;

		return $export_pattern;
	}

	public function ajaxExportSourcesAction($payload)
	{
		$info = $this->crowdin->info();

		$this->module->exportAsInCodeLanguage();
		$path_to_sources = dirname(__FILE__).'/../../packs/en';

		$files_to_export = array();
		$dirs_to_create = array();

		if (is_dir($path_to_sources))
		{
			foreach (FilesLister::listFiles($path_to_sources, null, null, true) as $path)
			{
				if (preg_match('/(?:[a-z]{2}|admin|pdf|errors|tabs)\.php$/', $path))
				{
					$ps_path = Tools::substr($path, Tools::strlen($path_to_sources)+1);

					$dest = $this->getCrowdinPath($this->module->getPackVersion(), $ps_path);

					$files_to_export[] = array(
						'real_relative_path' => FilesLister::cleanPath(Tools::substr(realpath($path), Tools::strlen(_PS_ROOT_DIR_)+1)),
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
				'message' => $ok ? 'Done :)' : $message,
			);
	}

	public function getTranslationsFromCrowdin($prestashop_language_code)
	{
		$files = array();

		$lc = $this->module->getCrowdinLanguageCode($prestashop_language_code);
		$data = $this->crowdin->downloadTranslations($lc);
		$file = tempnam(null, 'translatools');
		file_put_contents($file, $data);
		$za = new ZipArchive();
		$za->open($file);

		$te = new TranslationsExtractor();

		for ($i=0; $i<$za->numFiles; $i++)
		{
			$stat = $za->statIndex($i);
			$name = $stat['name'];
			$m = array();
			$exp = '#^'.preg_quote($this->module->getPackVersion()).'/(.*?\.php)$#';
			if (preg_match($exp, $name, $m))
			{
				$target_path = $this->module->getPrestaShopPathFromCrowdinPath($m[1]);

				if ($target_path !== false)
				{
					$contents = $za->getFromIndex($i);
					$files[$target_path] = $te::parseDictionaryFromString($contents);
				}
			}
		}

		return $files;
	}

	public function ajaxTestAction()
	{
		ddd($this->getTranslationsFromCrowdin('fr'));
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
				$exp = '#^'.preg_quote($this->module->getPackVersion()).'/(.*?\.php)$#';
				if (preg_match($exp, $name, $m))
				{
					$target_path = $m[1];
					$contents = $za->getFromIndex($i);

					$only = array();
					if (is_array($payload) && isset($payload['only_virtual']) && $payload['only_virtual'])
						$only[] = 'an';
					$ok = $this->module->importTranslationFile($target_path, $contents, $only);
					
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

		$languages = array();
		if ($payload['language'] === '*')
		{
			foreach (Language::getLanguages() as $lang)
			{
				$languages[] = $lang['iso_code'];
			}
		}
		else
		{
			$languages[] = $payload['language'];
		}

		// Build the necessary languages
		foreach ($languages as $code)
		{
			// Don't export English or Aragonese to Crowdin!
			if ($code === 'en' || $code === 'an')
				continue;

			$packs_root = realpath(dirname(__FILE__).'/../../packs/');			
			$te->save();
			$te->setLanguage($code);
			$te->fill();
			// Remove identical translations
			$te->diffFromArrayOfDictionaries($code, $this->getTranslationsFromCrowdin($code));
			$wrote = $te->write($packs_root);
			$te->load();
			foreach ($wrote as $file)
			{
				$relpath = Tools::substr($file, Tools::strlen(_PS_ROOT_DIR_)+1);
				$tasks[] = array(
					'action' => 'exportTranslationFile',
					'language' => $this->module->getCrowdinLanguageCode($code),
					'relsrc' => $relpath,
					'dest' => $this->getCrowdinPath(
						$this->module->getPackVersion(),
						Tools::substr($file, Tools::strlen($packs_root.'/'.$code)+1)
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
				$this->context->cookie->JIPT_PREVIOUS_ID_LANG = $this->context->employee->id_lang;


				$this->context->employee->id_lang = $jipt_id_lang;
				$this->context->employee->save();

				return array('success' => true, 'language' => $jipt_id_lang);
			}

			return array('success' => false, 'language' => $this->context->employee->id_lang);
		}
		else
		{
			$language_to_set = $this->context->cookie->JIPT_PREVIOUS_ID_LANG;

			if ($language_to_set == $jipt_id_lang || !$language_to_set)
				$language_to_set = Configuration::get('PS_LANG_DEFAULT');

			$this->context->employee->id_lang = $language_to_set;
			$this->context->employee->save();
			unset($this->context->cookie->JIPT_PREVIOUS_ID_LANG);
			return array('success' => true, 'language' => $language_to_set);
		}
	}

	public function processCopyTabs()
	{
		$installer_dir = null;
		foreach (array('install-dev', 'install') as $dir)
		{
			$path = FilesLister::join(_PS_ROOT_DIR_, $dir);
			if (is_dir($path))
			{
				$installer_dir = $path;
				break;
			}
		}

		if ($installer_dir === null)
		{
			die("Could not find installer dir!");
		}

		$langs_dir = FilesLister::join($installer_dir, 'langs');

		foreach (scandir($langs_dir) as $entry)
		{
			$lang = $entry;
			$tabs_xml_path = FilesLister::join($langs_dir, $entry.'/data/tab.xml');
			if (file_exists($tabs_xml_path))
			{
				$xml_str = file_get_contents($tabs_xml_path);

				$xml_str = preg_replace('/^\s*<\?\s*xml\s+version\s*=\s*"(.*?)"\s*\?>/', '<?xml version="\1" encoding="UTF-8"?>', $xml_str);

				if ($xml = simplexml_load_string($xml_str))
				{
					$sql = 'SELECT e.name as message, t.name as translation 
					FROM '._DB_PREFIX_.'tab_lang e INNER JOIN '._DB_PREFIX_.'lang el ON el.id_lang=e.id_lang AND el.iso_code=\'en\'
					INNER JOIN '._DB_PREFIX_.'tab_lang t ON t.id_tab = e.id_tab INNER JOIN '._DB_PREFIX_.'lang tl on tl.id_lang=t.id_lang AND tl.iso_code=\''.pSQL($lang).'\'';

					$tl = Db::getInstance()->ExecuteS($sql);
					$translations = array();

					foreach($tl as $row)
					{
						$translations[$row['message']] = $row['translation'];
					}

					foreach ($xml->tab as $tab)
					{
						$tab_name = (string)$tab['name'];
						
						if (isset($translations[$tab_name]))
						{
							if ($lang === 'mk')
							{
								//die($translations[$tab_name]);
							}
							$tab['name'] = $translations[$tab_name];
						}	
					}

					file_put_contents($tabs_xml_path, $xml->asXML());
				}
			}
		}
	}

	public function processBuild()
	{

		$tmp = Tools::jsonDecode(Tools::file_get_contents('http://www.prestashop.com/download/lang_packs/get_each_language_pack.php?version='.$this->module->getPackVersion()), true);
		$published = array();

		if (is_array($tmp))
			foreach ($tmp as $lang)
				$published[$lang['iso_code']] = true;				

		require_once dirname(__FILE__).'/../../../../tools/tar/Archive_Tar.php';

		$dir = realpath(dirname(__FILE__).'/../../packs');

		if (!$dir)
			die("Packs directory not found, aborting.");

		FilesLister::rmDir($dir);
		if (!@mkdir($dir, 0777, true))
			die('Could not create directory: '.$dir);

		$te = $this->module->exportNativePack();
		
		$created = array();

		//$dl = $this->ajaxDownloadTranslationsAction(array());
		foreach (scandir(_PS_ROOT_DIR_.'/translations') as $lc)
		{
			if (!preg_match('/^\./', $lc) && is_dir(_PS_ROOT_DIR_.'/translations/'.$lc) && (isset($published[$lc]) || count($published) === 0))
			{
				$te->save();

				$te->setLanguage($lc);
				$te->fill();

				$wrote = $te->write($dir);

				$cwd = getcwd();
				chdir(FilesLister::join($dir, $lc));

				$archname = $lc.'.gzip';
				$archpath = '../'.$archname;

				if (file_exists($archpath))
					unlink($archpath);

				$arch = new Archive_Tar($archpath, 'gz');

				$add = array();

				foreach (FilesLister::listFiles('.', null, null, true) as $path)
					$add[] = preg_replace('#^\./#', '', $path);

				$arch->add($add);

				$created[] = $archname;

				chdir($cwd);

				$te->load();
			}					
		}

		// Put it back
		$this->module->exportAsInCodeLanguage();

		chdir($dir);

		$allpath = 'all_packs.tar.gz';
		if (file_exists($allpath))
			unlink($allpath);

		$all = new Archive_Tar($allpath, 'gz');
		$all->add($created);

		ob_end_clean();
		header('Content-Description: File Transfer');
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename='.$allpath);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($allpath);
        exit;
	}
}	