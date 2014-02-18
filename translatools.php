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

	// Locale => array('crowdin_code' => xy, 'prestashop_code' => zt)
	// This list is not meant to be written by hand :)
	public static $languageMapping = array('an-AR' => array('crowdin_code' => 'an', 'prestashop_code' => 'an'), 'af-ZA' => array('crowdin_code' => 'af', 'prestashop_code' => 'af'), 'es-AR' => array('crowdin_code' => 'es-AR', 'prestashop_code' => 'ag'), 'ar-SA' => array('crowdin_code' => 'ar', 'prestashop_code' => 'ar'), 'az-AZ' => array('crowdin_code' => 'az', 'prestashop_code' => 'az'), 'bg-BG' => array('crowdin_code' => 'bg', 'prestashop_code' => 'bg'), 'bn-BD' => array('crowdin_code' => 'bn', 'prestashop_code' => 'bn'), 'pt-BR' => array('crowdin_code' => 'pt-BR', 'prestashop_code' => 'br'), 'bs-BA' => array('crowdin_code' => 'bs', 'prestashop_code' => 'bs'), 'br-FR' => array('crowdin_code' => 'br-FR', 'prestashop_code' => 'bz'), 'ca-ES' => array('crowdin_code' => 'ca', 'prestashop_code' => 'ca'), 'es-CO' => array('crowdin_code' => 'es-CO', 'prestashop_code' => 'cb'), 'cs-CZ' => array('crowdin_code' => 'cs', 'prestashop_code' => 'cs'), 'da-DK' => array('crowdin_code' => 'da', 'prestashop_code' => 'da'), 'de-DE' => array('crowdin_code' => 'de', 'prestashop_code' => 'de'), 'el-GR' => array('crowdin_code' => 'el', 'prestashop_code' => 'el'), 'en-US' => array('crowdin_code' => 'en', 'prestashop_code' => 'en'), 'es-ES' => array('crowdin_code' => 'es-ES', 'prestashop_code' => 'es'), 'et-EE' => array('crowdin_code' => 'et', 'prestashop_code' => 'et'), 'eu-ES' => array('crowdin_code' => 'eu', 'prestashop_code' => 'eu'), 'fa-IR' => array('crowdin_code' => 'fa', 'prestashop_code' => 'fa'), 'fi-FI' => array('crowdin_code' => 'fi', 'prestashop_code' => 'fi'), 'fo-FO' => array('crowdin_code' => 'fo', 'prestashop_code' => 'fo'), 'fr-FR' => array('crowdin_code' => 'fr', 'prestashop_code' => 'fr'), 'ga-IE' => array('crowdin_code' => 'ga-IE', 'prestashop_code' => 'ga'), 'en-GB' => array('crowdin_code' => 'en-GB', 'prestashop_code' => 'gb'), 'gl-ES' => array('crowdin_code' => 'gl', 'prestashop_code' => 'gl'), 'he-IL' => array('crowdin_code' => 'he', 'prestashop_code' => 'he'), 'hi-IN' => array('crowdin_code' => 'hi', 'prestashop_code' => 'hi'), 'hr-HR' => array('crowdin_code' => 'hr', 'prestashop_code' => 'hr'), 'hu-HU' => array('crowdin_code' => 'hu', 'prestashop_code' => 'hu'), 'hy-AM' => array('crowdin_code' => 'hy-AM', 'prestashop_code' => 'hy'), 'id-ID' => array('crowdin_code' => 'id', 'prestashop_code' => 'id'), 'it-IT' => array('crowdin_code' => 'it', 'prestashop_code' => 'it'), 'ja-JP' => array('crowdin_code' => 'ja', 'prestashop_code' => 'ja'), 'ka-GE' => array('crowdin_code' => 'ka', 'prestashop_code' => 'ka'), 'ko-KR' => array('crowdin_code' => 'ko', 'prestashop_code' => 'ko'), 'lo-LA' => array('crowdin_code' => 'lo', 'prestashop_code' => 'lo'), 'lt-LT' => array('crowdin_code' => 'lt', 'prestashop_code' => 'lt'), 'lv-LV' => array('crowdin_code' => 'lv', 'prestashop_code' => 'lv'), 'mk-MK' => array('crowdin_code' => 'mk', 'prestashop_code' => 'mk'), 'ml-IN' => array('crowdin_code' => 'ml-IN', 'prestashop_code' => 'ml'), 'ms-MY' => array('crowdin_code' => 'ms', 'prestashop_code' => 'ms'), 'es-MX' => array('crowdin_code' => 'es-MX', 'prestashop_code' => 'mx'), 'nl-NL' => array('crowdin_code' => 'nl', 'prestashop_code' => 'nl'), 'no-NO' => array('crowdin_code' => 'no', 'prestashop_code' => 'no'), 'pl-PL' => array('crowdin_code' => 'pl', 'prestashop_code' => 'pl'), 'pt-PT' => array('crowdin_code' => 'pt-PT', 'prestashop_code' => 'pt'), 'ro-RO' => array('crowdin_code' => 'ro', 'prestashop_code' => 'ro'), 'ru-RU' => array('crowdin_code' => 'ru', 'prestashop_code' => 'ru'), 'si-LK' => array('crowdin_code' => 'si-LK', 'prestashop_code' => 'sh'), 'sl-SI' => array('crowdin_code' => 'sl', 'prestashop_code' => 'si'), 'sk-SK' => array('crowdin_code' => 'sk', 'prestashop_code' => 'sk'), 'sq-AL' => array('crowdin_code' => 'sq', 'prestashop_code' => 'sq'), 'sr-CS' => array('crowdin_code' => 'sr-CS', 'prestashop_code' => 'sr'), 'sv-SE' => array('crowdin_code' => 'sv-SE', 'prestashop_code' => 'sv'), 'sw-KE' => array('crowdin_code' => 'sw', 'prestashop_code' => 'sw'), 'ta-IN' => array('crowdin_code' => 'ta', 'prestashop_code' => 'ta'), 'te-IN' => array('crowdin_code' => 'te', 'prestashop_code' => 'te'), 'th-TH' => array('crowdin_code' => 'th', 'prestashop_code' => 'th'), 'tr-TR' => array('crowdin_code' => 'tr', 'prestashop_code' => 'tr'), 'zh-TW' => array('crowdin_code' => 'zh-TW', 'prestashop_code' => 'tw'), 'ug-CN' => array('crowdin_code' => 'ug', 'prestashop_code' => 'ug'), 'uk-UA' => array('crowdin_code' => 'uk', 'prestashop_code' => 'uk'), 'ur-PK' => array('crowdin_code' => 'ur-PK', 'prestashop_code' => 'ur'), 'vi-VN' => array('crowdin_code' => 'vi', 'prestashop_code' => 'vn'), 'zh-CN' => array('crowdin_code' => 'zh-CN', 'prestashop_code' => 'zh'));
	public static $reverseLanguageMapping = array();

	public function __construct()
	{
		$this->name = 'translatools';
		$this->version = '0.8';
		$this->author = 'fmdj';
		$this->tab = 'administration';
		
		//TODO: Add warning curl ($this->warning = 'blah blah';)

		$this->bootstrap = true;
		parent::__construct();	

		$this->displayName = $this->l('TranslaTools');
		$this->description = $this->l('Crowdin integration and more!');

		foreach (static::$languageMapping as $locale => $codes)
		{
			static::$reverseLanguageMapping[$codes['prestashop_code']] = array('locale' => $locale, 'crowdin_code' => $codes['crowdin_code']);
		}
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
		$this->updateVirtualLanguage();

		return $ok;
	}

	public function updateVirtualLanguage()
	{
		if (defined('_PS_MODE_DEV_') && _PS_MODE_DEV_ && count($this->nonWritableDirectories()) === 0)
		{
			$this->exportAsInCodeLanguage();
			$ttc = new AdminTranslatoolsController(true);
			$ttc->ajaxDownloadTranslationsAction(array('only_virtual' => true, 'language' => 'an'));
		}
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
		$directories[] = FilesLister::join(_PS_MODULE_DIR_, 'translatools/tmp');

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

		$shop_not_up_to_date = '';
		$translatability = $this->computeTranslatability();
		if ((int)$translatability[null]['percent_translated'] < 100)
		{
			$this->updateVirtualLanguage();
			$translatability = $this->computeTranslatability();
			if ((int)$translatability[null]['percent_translated'] < 100)
			{
				$shop_not_up_to_date = $this->l('We tried to update the list of translatable strings from Crowdin, but your shop is still not 100% translatable. The strings on Crowdin are maybe not up to date or your PrestaShop installation is either not up to date or contains custom changes in the core strings.');
			}
		}

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
			'coverage' => $translatability,
			'shop_not_up_to_date' => $shop_not_up_to_date
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
			'generatedEmails' => 1,
			'installer' => 1,
			'fields' => 1
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

	public function exportNativePack()
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
			'generatedEmails' => 1,
			'fields' => 1
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
			elseif (preg_match('#/langs/[a-z]{2}/install.php$#', $file))
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

	public function getPrestaShopLanguageCode($foreignCode)
	{
		if (isset(self::$languageMapping[$foreignCode])) 
			return self::$languageMapping[$foreignCode]['prestashop_code'];
		else
			return $foreignCode;
	}

	public function getCrowdinLanguageCode($prestashopCode)
	{
		if (isset(self::$reverseLanguageMapping[$prestashopCode])) 
			return self::$reverseLanguageMapping[$prestashopCode]['locale'];
		else
			return $prestashopCode;
	}

	public function getCrowdinShortCode($prestashopCode)
	{
		if (isset(self::$reverseLanguageMapping[$prestashopCode])) 
			return self::$reverseLanguageMapping[$prestashopCode]['crowdin_code'];
		else
			return $prestashopCode;
	}

	public function guessLanguageCodeFromPath($path)
	{
		$exps = array(
			'#(?:^|/)translations/([^/]+)/(?:admin|errors|pdf|tabs|fields)\.php$#',
			'#(?:^|/)modules/(?:[^/]+)/translations/(.*?)\.php$#',
			'#(?:^|/)themes/(?:[^/]+)/lang/(.*?)\.php$#',
			'#(?:^|/)mails/([^/]+)/lang.php$#',
			'#(?:^|/)templates_translations/([^/]+)/lang_content\.php$#',
			'#/langs/([^/]+)/install\.php$#'
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
		static $installed_languages;

		if (!is_array($installed_languages))
		{
			$installed_languages = array();
			foreach (Language::getLanguages() as $l)
				$installed_languages[$l['iso_code']] = true;
		}

		// Guess language code
		$m = array();
		$lc = $this->guessLanguageCodeFromPath($path);

		if ($lc === false)
			return "Could not infer language code from file named '$path'.";

		// Remove empty lines, just in case
		$contents = preg_replace('/^\\s*\\$?\\w+\\s*\\[\\s*\'((?:\\\\\'|[^\'])+)\'\\s*\\]\\s*=\\s*\'\'\\s*;$/m', '', $contents);
		

		$languageCode = $this->getPrestaShopLanguageCode($lc);


		if (count($languages) > 0 && !in_array($languageCode, $languages) && $languageCode !== 'an')
			return true;

		if ($languageCode === null)
			return "Could not map language code '$lc' to a PrestaShop code.";

		$path = str_replace(
			array("/$lc/", "/$lc.php"),
			array("/$languageCode/", "/$languageCode.php"),
			$path
		);

		// Skip installer translations for languages that do not have their data folders
		if (basename($path) === 'install.php' && !is_dir(FilesLister::join(_PS_ROOT_DIR_, "install-dev/langs/$languageCode/data")))
			return true;

		$full_path = _PS_ROOT_DIR_.'/'.$path;
		$dir = dirname($full_path);

		if (!is_dir($dir))
			if (!@mkdir($dir, 0777, true))
				return "Could not create directory for file '$path'.";

		if(!@file_put_contents($full_path, $contents))
		{
			return $this->l('Could not import file: ').$path;
		}

		$this->postProcessTranslationFile($languageCode, $full_path);

		return true;
	}

	public function postProcessTranslationFile($language_code, $full_path)
	{
		$id_lang = Db::getInstance()->getValue('SELECT `id_lang` FROM `'._DB_PREFIX_.'lang` WHERE `iso_code` = \''.pSQL(strtolower($language_code)).'\'');
		
		if (!$id_lang)
			return;

		if (basename($full_path) === 'tabs.php')
		{
			$te = new TranslationsExtractor();
			foreach ($te->parseDictionary($full_path) as $class => $name)
			{
				// Unescape the quotes
				$name = preg_replace('/\\\*\'/', '\'', $name);
				
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
