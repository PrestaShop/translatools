<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

require_once dirname(__FILE__).'/classes/TranslationsExtractor.php';
require_once dirname(__FILE__).'/classes/TranslationsLinter.php';
require_once dirname(__FILE__).'/classes/FilesLister.php';
require_once dirname(__FILE__).'/classes/parsing/SmartyFunctionCallParser.php';
require_once dirname(__FILE__).'/controllers/admin/AdminTranslatoolsController.php';


class TranslaTools extends Module
{
	private $_html = '';
	private $_postErrors = array();

	public $details;
	public $owner;
	public $address;
	public $extra_mail_vars;
	public function __construct()
	{
		$this->name = 'translatools';
		$this->version = '0.6';
		$this->author = 'fmdj';
		$this->tab = 'administration';
		
		//TODO: Add warning curl ($this->warning = 'blah blah';)

		$this->bootstrap = true;
		parent::__construct();	

		$this->displayName = 'TranslaTools';
		$this->description = 'Crowdin integration and more!';
	}

	public function install()
	{
		$ok = parent::install() 
		&& $this->registerHook('displayHeader') 
		&& $this->registerHook('actionAdminControllerSetMedia')
		&& $this->registerHook('displayBackOfficeFooter')
		&& $this->registerHook('displayBackOfficeHeader')
		&& $this->installTab();

		Configuration::updateValue('CROWDIN_PROJECT_IDENTIFIER', 'prestashop-official');
		Configuration::updateValue('JIPT_FO', '1');
		Configuration::updateValue('JIPT_BO', '1');

		$this->createVirtualLanguage();

		if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ && count($this->nonWritableDirectories()) === 0)
		{
			$this->exportAsInCodeLanguage();
			$ttc = new AdminTranslatoolsController(true);
			$ttc->ajaxDownloadTranslationsAction(array('only_virtual' => true));
		}

		return $ok;
	}

	public function uninstall()
	{
		return parent::uninstall() && $this->uninstallTab();
	}

	public function installTab()
	{
		$tab = new Tab();
		$tab->active = 1;
		$tab->class_name = "AdminTranslatools";
		$tab->name = array();
		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = "Translatools";
		$tab->id_parent = -1;
		$tab->module = $this->name;
		return $tab->add();
	}

	public function uninstallTab()
	{
		$id_tab = (int)Tab::getIdFromClassName('AdminTranslatools');
		if ($id_tab)
		{
			$tab = new Tab($id_tab);
			return $tab->delete();
		}
		else
			return false;
	}

	public function hookDisplayHeader($params)
	{
		if (Configuration::get('CROWDIN_PROJECT_IDENTIFIER') && Configuration::get('JIPT_FO') == '1' && $this->context->language->iso_code === 'an')
		{
			$this->context->controller->addJS('https://cdn.crowdin.net/jipt/jipt.js');
			$this->smarty->assign('CROWDIN_PROJECT_IDENTIFIER', Configuration::get('CROWDIN_PROJECT_IDENTIFIER'));
			return $this->display(__FILE__, 'views/templates/hook/header.tpl');
		}
		else return "";
	}

	public function hookDisplayBackOfficeHeader($params)
	{
		if (Configuration::get('CROWDIN_PROJECT_IDENTIFIER') && Configuration::get('JIPT_FO') == '1' && $this->context->language->iso_code === 'an')
		{
			$this->smarty->assign('CROWDIN_PROJECT_IDENTIFIER', Configuration::get('CROWDIN_PROJECT_IDENTIFIER'));
			return $this->display(__FILE__, 'views/templates/hook/jipt.tpl');
		}
		else return "";
	}

	public function hookActionAdminControllerSetMedia($params)
	{
	}

	public function hookDisplayBackOfficeFooter($params)
	{	
		if (!Configuration::get('JIPT_BO'))
			return;
		
		$live_translation_enabled = ($this->context->cookie->JIPT_PREVIOUS_ID_LANG ? 1 : 0) || $this->context->language->iso_code === 'an';
		
		$this->context->smarty->assign('live_translation_enabled', $live_translation_enabled);
		$this->context->smarty->assign('translatools_controller', $this->context->link->getAdminLink('AdminTranslatools'));
		return $this->display(__FILE__, '/views/templates/hook/backOfficeFooter.tpl');
	}

	public function nonWritableDirectories()
	{
		$nonWritable = array();
		$directories = array();
		foreach (scandir(_PS_MODULE_DIR_) as $entry)
		{
			if ($entry[0] !== '.' && is_dir(_PS_MODULE_DIR_.$entry))
			{
				$directories[] = FilesLister::join(_PS_MODULE_DIR_, "$entry/translations");
			}
		}
		foreach (scandir(_PS_ALL_THEMES_DIR_) as $entry)
		{
			if ($entry[0] !== '.' && is_dir(_PS_ALL_THEMES_DIR_.$entry))
			{
				$directories[] = FilesLister::join(_PS_ALL_THEMES_DIR_, "$entry/lang");
			}
		}
		$directories[] = FilesLister::join(_PS_ROOT_DIR_, 'translations');
		$directories[] = FilesLister::join(_PS_ROOT_DIR_, 'mails');
		$directories[] = FilesLister::join(_PS_MODULE_DIR_, 'translatools/packs');

		if (is_dir( FilesLister::join(_PS_MODULE_DIR_, 'emailgenerator')))
			$directories[] = FilesLister::join(_PS_MODULE_DIR_, 'emailgenerator/templates_translations');

		foreach ($directories as $dir)
		{
			$writable = false;
			if (file_exists($dir) && is_writable($dir))
				$writable = true;
			else if (!file_exists($dir))
			{
				$parent = $dir;
				while (($parent = preg_replace('#/[^/]+/?$#', '', $parent)) !== '')
				{
					if (file_exists($parent))
					{
						if (is_writable($parent))
						{
							$writable = true;
							break;
						}
						else
							break;
					}
				}
					
			}

			if (!$writable)
				$nonWritable[] = Tools::substr($dir, 1+Tools::strlen(FilesLister::cleanPath(_PS_ROOT_DIR_)));
		}

		return $nonWritable;
	}

	public function getContent()
	{
		Tools::redirectAdmin($this->context->link->getAdminLink('AdminTranslatools'));
	}

	public function getNativeModules()
	{
		return array_map('trim', 
			explode("\n", 
				Tools::file_get_contents(dirname(__FILE__).'/data/native_modules')
			)
		);
	}

	public function getPackVersion()
	{
		$m = array();
		if (preg_match('/^(\d+\.\d+)/', _PS_VERSION_, $m))
		{
			return $m[1];
		}
		return _PS_VERSION_;
	}

	public function defaultAction()
	{
		$modules_not_found = array();

		foreach ($this->getNativeModules() as $module)
		{
			if (!is_dir(_PS_MODULE_DIR_.$module))
				$modules_not_found[] = $module;
		}

		if (count($modules_not_found) > 0)
		{
			$install_link = $this->context->link->getAdminLink('AdminModules').'&install='.implode('|', $modules_not_found);
			$modules_not_found_warning = 
			"The following native modules were not found in your installation: "
			.implode(', ', $modules_not_found).'.'
			."&nbsp;<a target='_blank' href='$install_link'>Try to install them</a> automatically.";
		}
		else
			$modules_not_found_warning = false;

		$themes = array();
		foreach (scandir(_PS_ALL_THEMES_DIR_) as $entry)
			if (!preg_match('/^\./', $entry) && is_dir(_PS_ALL_THEMES_DIR_.$entry))
				$themes[] = $entry;


		$languages = array();
		foreach (Language::getLanguages() as $l)
			$languages[$l['iso_code']] = $l['name'];


		if ($_SERVER['REQUEST_METHOD'] === 'POST' && Tools::getValue('update_api_settings'))
		{
			Configuration::updateValue('CROWDIN_PROJECT_IDENTIFIER', Tools::getValue('CROWDIN_PROJECT_IDENTIFIER'));
			Configuration::updateValue('CROWDIN_PROJECT_API_KEY', Tools::getValue('CROWDIN_PROJECT_API_KEY'));
			Tools::redirectAdmin($_SERVER['REQUEST_URI']);
		}

		return array(
			'themes' => $themes,
			'current_theme' => $this->context->theme->name,
			'languages' => $languages,
			'jipt_bo' => Configuration::get('JIPT_BO'),
			'jipt_fo' => Configuration::get('JIPT_FO'),
			'modules_not_found_warning' => $modules_not_found_warning,
			'jipt_language' => 'an',
			'CROWDIN_PROJECT_IDENTIFIER' => Configuration::get('CROWDIN_PROJECT_IDENTIFIER'),
			'CROWDIN_PROJECT_API_KEY' => Configuration::get('CROWDIN_PROJECT_API_KEY'),
			'non_writable_directories' => $this->nonWritableDirectories(),
			'coverage' => $this->computeTranslatability()
		);
	}

	public function computeTranslatability()
	{
		$te = new TranslationsExtractor();
		$te->setRootDir(_PS_ROOT_DIR_);
		$built = $te->buildFromTranslationFiles(dirname(__FILE__).'/packs/en');
		if (!$built)
			return false;
		$te->setLanguage('an');
		$te->fill();
		$stats = $te->computeStats();
		return $stats;
	}

	public function exportAsInCodeLanguage()
	{
		$extractor = new TranslationsExtractor();
		$extractor->setSections(array(
			'frontOffice' => 1,
			'backOffice' => 1,
			'modules' => 1,
			'errors' => 1,
			'pdfs' => 1,
			'tabs' => 1,
			'mailSubjects' => 1,
			'mailContent' => 1,
			'generatedEmails' => 1
		));
		$extractor->setRootDir(_PS_ROOT_DIR_);
		$extractor->setTheme($this->context->theme->name);
		$extractor->setLanguage('-');
		$extractor->setModuleParsingBehaviour('both', 'core');
		$extractor->setModuleFilter($this->getNativeModules());
		$dir = FilesLister::join(dirname(__FILE__), 'packs');
		$extractor->extract($dir);
		
		return $extractor;
	}

	public function exportTranslationsAction()
	{
		if (Tools::getValue('filter_modules') === 'native')
			$module_filter = $this->getNativeModules();
		else
			$module_filter = null;

		$extractor = new TranslationsExtractor();
		$extractor->setSections(Tools::getValue('section'));
		$extractor->setRootDir(_PS_ROOT_DIR_);
		$extractor->setTheme(Tools::getValue('theme'));
		$extractor->setLanguage(Tools::getValue('language'));
		$extractor->setModuleParsingBehaviour(Tools::getValue('overriden_modules'), Tools::getValue('modules_storage'));
		$extractor->setModuleFilter($module_filter);
		$dir = FilesLister::join(dirname(__FILE__), 'packs');
		$extractor->extract($dir);
		$extractor->sendAsGZIP($dir);
	}

	public function viewStatsAction()
	{
		$extractor = new TranslationsExtractor();
		$extractor->setSections(Tools::getValue('section'));
		$extractor->setRootDir(_PS_ROOT_DIR_);
		$extractor->setTheme(Tools::getValue('theme'));
		$extractor->setModuleParsingBehaviour(Tools::getValue('overriden_modules'), Tools::getValue('modules_storage'));
		$extractor->extract();

		$files = $extractor->getFiles();

		$stats = array();

		foreach ($files as $name => $data)
		{
			$stats[$name] = array(
				'total' => count($data)
			); 
		}

		return array(
			'stats' => $stats
		);
	}

	public function purgeTranslationsAction()
	{
		$tokill = array();
		$killed = array();

		foreach (FilesLister::listFiles(_PS_ROOT_DIR_, null, null, true) as $file)
		{
			if (preg_match('#/translations/[a-z]{2}/(?:admin|errors|pdf|fields|tabs)\.php$#', $file))
				$tokill[] = $file;
			elseif (preg_match('#/(?:translations|lang)/[a-z]{2}\.php$#', $file))
				$tokill[] = $file;
		}

		foreach ($tokill as $path)
		{
			unlink($path);
			$killed[] = Tools::substr($path, Tools::strlen(_PS_ROOT_DIR_)+1);
		}

		return array('killed' => $killed);
	}

	public function setConfigurationValueAction()
	{
		$key = Tools::getValue('key');
		// Don't let users abuse this to change anything, whitelist the options
		if (in_array($key, array('JIPT_BO', 'JIPT_FO', 'CROWDIN_PROJECT_IDENTIFIER', 'CROWDIN_PROJECT_API_KEY')))
			Configuration::updateValue($key, Tools::getValue('value'));
		die();
	}

	public function createVirtualLanguage()
	{
		if (!Language::getIdByIso('an'))
		{
			$language = new Language();
			$language->iso_code = 'an';
			$language->language_code = 'an';
			$language->name = 'Live Translation';
			$language->save();
			if ($language->id)
				copy(dirname(__FILE__).'/img/an.jpg', _PS_IMG_DIR_.'/l/'.$language->id.'.jpg');
		}
	}

	public function createVirtualLanguageAction()
	{
		$this->tpl = 'default';

		$this->createVirtualLanguage();

		Tools::redirectAdmin($this->context->link->getAdminLink('AdminTranslatools'));
	}

	// Crowdin => PrestaShop
	public static $languageMapping = array(
		'es-AR' => 'ag',
		'pt-BR' => 'br',
		'br-FR' => 'bz',
		'es-CO' => 'cb',
		'es-ES' => 'es',
		'ga-IE' => 'ga',
		'en-GB' => 'gb',
		'hy-AM' => 'hy',
		'ml-IN' => 'ml',
		'es-MX' => 'mx',
		'pt-PT' => 'pt',
		'si-LK' => 'sh',
		'sl' 	=> 'si',
		'sr-CS' => 'sr',
		'sv-SE' => 'sv',
		'zh-TW' => 'tw',
		'ur-PK' => 'ur',
		'vi' 	=> 'vn',
		'zh-CN' => 'zh'
	);

	public function getPrestaShopLanguageCode($foreignCode)
	{
		if (isset(self::$languageMapping[$foreignCode])) 
			return self::$languageMapping[$foreignCode];
		else
			return $foreignCode;
	}

	public function getCrowdinLanguageCode($prestashopCode)
	{
		static $reverseLanguageMapping;
		if (!is_array($reverseLanguageMapping))
		{
			$reverseLanguageMapping = array();
			foreach (static::$languageMapping as $crowdin => $prestashop)
			{
				$reverseLanguageMapping[$prestashop] = $crowdin;
			}
		}
		if (isset($reverseLanguageMapping[$prestashopCode])) 
			return $reverseLanguageMapping[$prestashopCode];
		else
			return $prestashopCode;
	}

	public function guessLanguageCodeFromPath($path)
	{
		$exps = array(
			'#(?:^|/)translations/([^/]+)/(?:admin|errors|pdf|tabs)\.php$#',
			'#(?:^|/)modules/(?:[^/]+)/translations/(.*?)\.php$#',
			'#(?:^|/)themes/(?:[^/]+)/lang/(.*?)\.php$#',
			'#(?:^|/)mails/([^/]+)/lang.php$#',
			'#(?:^|/)templates_translations/([^/]+)/lang_content\.php$#'
		);

		$m = array();
		foreach ($exps as $exp)
			if (preg_match($exp, $path, $m))
				return $m[1];
		
		return false;
	}

	public function getPrestaShopPathFromCrowdinPath($path)
	{
		$crowdin_code = $this->guessLanguageCodeFromPath($path);
		if ($crowdin_code === false)
			return false;

		$lc = $this->getPrestaShopLanguageCode($crowdin_code);

		return str_replace(array("/$crowdin_code/", "/$crowdin_code.php"), array("/$lc/", "/$lc.php"), $path);
	}

	public function importTranslationFile($path, $contents, $languages = array())
	{
		// Guess language code
		$m = array();
		$lc = $this->guessLanguageCodeFromPath($path);

		if ($lc === false)
			return "Could not infer language code from file named '$path'.";

		// Remove empty lines, just in case
		$contents = preg_replace('/^\\s*\\$?\\w+\\s*\\[\\s*\'((?:\\\\\'|[^\'])+)\'\\s*\\]\\s*=\\s*\'\'\\s*;$/m', '', $contents);
		

		$languageCode = $this->getPrestaShopLanguageCode($lc);

		if (count($languages) > 0 && !in_array($languageCode, $languages))
			return true;

		if ($languageCode === null)
			return "Could not map language code '$lc' to a PrestaShop code.";

		$path = str_replace(
			array("/$lc/", "/$lc.php"),
			array("/$languageCode/", "/$languageCode.php"),
			$path
		);

		$full_path = _PS_ROOT_DIR_.'/'.$path;
		$dir = dirname($full_path);

		if (!is_dir($dir))
			if (!@mkdir($dir, 0777, true))
				return "Could not create directory for file '$path'.";

		file_put_contents($full_path, $contents);

		$this->postProcessTranslationFile($languageCode, $full_path);

		return true;
	}

	public function postProcessTranslationFile($language_code, $full_path)
	{
		if (basename($full_path) === 'tabs.php' && $language_code === 'an')
		{
			$te = new TranslationsExtractor();
			foreach ($te->parseDictionary($full_path) as $class => $name)
			{
				// Unescape the quotes
				$name = preg_replace('/\\\*\'/', '\'', $name);

				$id_lang = Language::getIdByIso($language_code);

				if ($id_lang)
				{
					$sql = 'SELECT id_tab FROM '._DB_PREFIX_.'tab WHERE class_name=\''.pSQL($class).'\'';
					$id_tab = Db::getInstance()->getValue($sql);

					if ($id_tab)
					{
						// DELETE old tab name in case it exists
						Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'tab_lang WHERE id_tab='.(int)$id_tab.' AND id_lang='.(int)$id_lang);
						// INSERT new tab name
						Db::getInstance()->execute('INSERT INTO '._DB_PREFIX_.'tab_lang (id_tab, id_lang, name) VALUES ('.(int)$id_tab.','.(int)$id_lang.',\''.pSQL($name).'\')');
					}
				}
			}
		}
	}

	public function checkLUseAction()
	{
		$linter = new TranslationsLinter();
		return $linter->checkLUse();
	}

	public function checkCoherenceAction()
	{
		$linter = new TranslationsLinter();
		return $linter->checkCoherence();
	}	
}
