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
require_once dirname(__FILE__).'/classes/SmartyFunctionCallParser.php';


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
		

		$this->bootstrap = true;
		parent::__construct();	

		$this->displayName = 'TranslaTools';
		$this->description = 'Check translations norm, export strings, and maybe more.';
	}

	public function install()
	{
		return parent::install() 
		&& $this->registerHook('displayHeader') 
		&& $this->registerHook('actionAdminControllerSetMedia')
		&& $this->registerHook('displayBackOfficeFooter')
		&& $this->registerHook('displayBackOfficeHeader')
		&& $this->installTab();
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
			$tab->name[$lang['id_lang']] = "AdminTranslatools";
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
			return $this->display(__FILE__, 'views/header.tpl');
		}
		else return "";
	}

	public function hookDisplayBackOfficeHeader($params)
	{
		if (Configuration::get('CROWDIN_PROJECT_IDENTIFIER') && Configuration::get('JIPT_FO') == '1' && $this->context->language->iso_code === 'an')
		{
			$this->smarty->assign('CROWDIN_PROJECT_IDENTIFIER', Configuration::get('CROWDIN_PROJECT_IDENTIFIER'));
			return $this->display(__FILE__, 'views/jipt.tpl');
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
		global $smarty;
		$smarty->assign('live_translation_enabled', $live_translation_enabled);
		$smarty->assign('translatools_controller', $this->context->link->getAdminLink('AdminTranslatools'));
		return $this->display(__FILE__, '/views/backOfficeFooter.tpl');
	}

	public function getContent()
	{
		global $smarty;

		$action = Tools::getValue('action');
		if ($action == '')
			$action = 'default';

		$method = $action.'Action';
		if (is_callable(array($this, $method)))
		{
			$this->tpl = $action;
			$template_parameters = $this->$method();
			if (is_array($template_parameters))
			{
				$smarty->assign($template_parameters);
			}
			if (file_exists($tpl_path=dirname(__FILE__).'/views/'.$this->tpl.'.tpl'))
			{
				$this->assignDefaultSmartyParameters();
				return $smarty->fetch($tpl_path);
			}
			else
				return "Could not find template for: '$action'";
		}
		else
		{
			return "Unknown action: '$action'.";
		}

	}

	public function getNativeModules()
	{
		return array_map('trim', 
			explode("\n", 
				file_get_contents(dirname(__FILE__).'/data/native_modules')
			)
		);
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
			'languages' => $languages,
			'jipt_bo' => Configuration::get('JIPT_BO'),
			'jipt_fo' => Configuration::get('JIPT_FO'),
			'modules_not_found_warning' => $modules_not_found_warning,
			'jipt_language' => 'an',
			'CROWDIN_PROJECT_IDENTIFIER' => Configuration::get('CROWDIN_PROJECT_IDENTIFIER'),
			'CROWDIN_PROJECT_API_KEY' => Configuration::get('CROWDIN_PROJECT_API_KEY')
		);
	}

	public function checkCoherenceAction()
	{
		$deltas = array();

		$themes_root = _PS_ALL_THEMES_DIR_;

		foreach (scandir($themes_root) as $theme)
		{
			if ($theme[0] !== '.' and is_dir($themes_root.$theme))
			{
				$theme_modules_root = $themes_root.$theme.'/modules/';
				foreach (scandir($theme_modules_root) as $module)
				{
					$module_root = $theme_modules_root.$module;
					if ($module[0] !== '.' and is_dir($module_root))
					{
						$rdi = new RecursiveDirectoryIterator($module_root);
						$rdi->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
						foreach (new RecursiveIteratorIterator($rdi) as $overriden_path)
							if (preg_match('/\.tpl$/', $overriden_path))
							{
								$original_path = _PS_MODULE_DIR_.substr($overriden_path, strlen($theme_modules_root));
								$differences = $this->getDifferences('tpl', $overriden_path, $original_path);
								if (count($differences) > 0)
									$deltas[] = array(
										'overriden_file' => substr($overriden_path, strlen(_PS_ROOT_DIR_)),
										'original_file' => file_exists($original_path) ? substr($original_path, strlen(_PS_ROOT_DIR_)) : false,
										'differences' => $differences
									);
							}
					}
				}
			}
		}

		return array(
			'deltas' => $deltas
		);
	}

	public function getStrings($file_extension, $path)
	{
		if ($file_extension !== 'tpl')
			return array();

		if (!file_exists($path))
			return array();

		$regexps = array(
			'/\{l\s*s=\''._PS_TRANS_PATTERN_.'\'/U',
			'/\{l\s*s=\"'._PS_TRANS_PATTERN_.'\"/U'
		);

		$data = file_get_contents($path);

		$strings = array();

		foreach ($regexps as $exp)
		{
			$matches = array();
			$n = preg_match_all($exp, $data, $matches);
			for ($i=0; $i<$n; $i++)
				$strings[] = $matches[1][$i]; 
		}

		return $strings;
	}

	public function getDifferences($file_extension, $overriden_path, $original_path)
	{
		$overriden_strings = array_unique($this->getStrings($file_extension, $overriden_path));
		$original_strings = array_unique($this->getStrings($file_extension, $original_path));

		$delta = array_diff($overriden_strings, $original_strings);

		foreach ($delta as $k => $string)
		{
			list($score, $closest) = $this->findBestMatch($string, $original_strings);
			if ($score > 0.7)
				$delta[$k] = array('overriden' => $string, 'original' => $closest);
		}

		return $delta;
	}

	public function stringToBagOfWords($str)
	{
		$list = array();
		preg_match_all('/\w+/', $str, $list);
		$bow = array();
		foreach ($list[0] as $word)
			if (strlen($word) > 3)
				$bow[] = strtolower($word);
			
		return array_unique($bow);
	}

	public function findBestMatch($needle, $haystack)
	{
		$score = 0;
		$string = '';

		$needle_bow = $this->stringToBagOfWords($needle);

		$matches = array();

		foreach ($haystack as $candidate)
		{
			$bow = $this->stringToBagOfWords($candidate);
			$denominator = count($bow)+count($needle_bow);
			if ($denominator === 0)
				continue;
			$score = 2*count(array_intersect($bow, $needle_bow)) / $denominator;
			$matches["$score"] = $candidate;
		}

		krsort($matches);

		if (count($matches) > 0)
			return array(key($matches), current($matches));

		return array(0, '');
	}

	public function assignDefaultSmartyParameters()
	{
		global $smarty;
		$hidden = array(
			'token' => Tools::getValue('token'),
			'configure' => $this->name,
			'controller' => 'AdminModules'
		);

		$inputs = array();
		$params = array();
		foreach ($hidden as $name => $value)
		{
			$inputs[] = "<input type='hidden' name='$name' value='$value'>";
			$params[] = urlencode($name).'='.urlencode($value);
		}
		$translatools_stay_here = implode("\n", $inputs);
		$translatools_url = '?'.implode('&', $params);

		$smarty->assign('translatools_stay_here', $translatools_stay_here); 
		$smarty->assign('translatools_url', $translatools_url);
		$smarty->assign('translatools_controller', $this->context->link->getAdminLink('AdminTranslatools'));
	}

	public static function getNewTranslationsExtractor($language, $theme=null)
	{
		$extractor = new TranslationsExtractor();
		$extractor->setRootDir(_PS_ROOT_DIR_);
		if ($theme === null)
		{
			$theme = Context::getContext()->shop->theme_name;
		}
		$extractor->setLanguage($language);
		$extractor->setTheme($theme);

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
		$extractor->extract(dirname(__FILE__).'/packs/');
		$extractor->sendAsGZIP(dirname(__FILE__).'/packs/');
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
		require_once dirname(__FILE__).'/classes/SkipDotsFilterIterator.php';

		$diter = new RecursiveDirectoryIterator(_PS_ROOT_DIR_, RecursiveDirectoryIterator::SKIP_DOTS);
		$filter = new SkipDotsFilterIterator($diter);

		$tokill = array();
		$killed = array();

		foreach (new RecursiveIteratorIterator($filter) as $file)
		{
			if (preg_match('#/translations/[a-z]{2}/(?:admin|errors|pdf|fields|tabs)\.php$#', $file))
				$tokill[] = $file;
			elseif (preg_match('#/(?:translations|lang)/[a-z]{2}\.php$#', $file))
				$tokill[] = $file;
		}

		foreach ($tokill as $path)
		{
			unlink($path);
			$killed[] = substr($path, strlen(_PS_ROOT_DIR_)+1);
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

	public function createVirtualLanguageAction()
	{
		$this->tpl = 'default';

		if (!Language::getIdByIso('an'))
		{
			$language = new Language();
			$language->iso_code = 'an';
			$language->language_code = 'an';
			$language->name = 'Aragonese';
			$language->save();
			if ($language->id)
				copy(dirname(__FILE__).'/img/an.jpg', _PS_IMG_DIR_.'/l/'.$language->id.'.jpg');
		}

		Tools::redirectAdmin('?controller=AdminModules&configure='.$this->name.'&token='.Tools::getValue('token'));
	}

	public function getPrestaShopLanguageCode($foreignCode)
	{
		// TODO: implement;
		if($foreignCode === 'zh-CN')
		{
			return 'zh';
		}
		return $foreignCode;
	}

	public function getCrowdinLanguageCode($prestashopCode)
	{
		// TODO: implement;
		if($prestashopCode === 'zh')
		{
			return 'zh-CN';
		}
		return $prestashopCode;
	}

	public function importTranslationFile($path, $contents)
	{
		// Guess language code
		$m = array();
		$lc = null;
		if (preg_match('#(?:^|/)translations/([^/]+)/(?:admin|errors|pdf|tabs)\.php$#', $path, $m))
			$lc = $m[1];
		else if(preg_match('#(?:^|/)modules/(?:[^/]+)/translations/(.*?)\.php$#', $path, $m))
			$lc = $m[1];
		else if(preg_match('#^themes/(?:[^/]+)/lang/(.*?)\.php$#', $path, $m))
			$lc = $m[1];
		else if(preg_match('#mails/([^/]+)/lang.php$#', $path, $m))
			$lc = $m[1];
		else if(basename($path) === 'lang_content.php')
			return true;

		if ($lc === null)
			return "Could not infer language code from file named '$path'.";

		// Remove empty lines, just in case
		$contents = preg_replace('/^\\s*\\$?\\w+\\s*\\[\\s*\'((?:\\\\\'|[^\'])+)\'\\s*\\]\\s*=\\s*\'\'\\s*;$/m', '', $contents);
		

		$languageCode = $this->getPrestaShopLanguageCode($lc);

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
		$issues = array();
		$root_dirs = array(_PS_MODULE_DIR_, _PS_ALL_THEMES_DIR_.Tools::getValue('theme').'/modules/');
		foreach ($root_dirs as $root_dir)
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root_dir))
				as $path => $info)
				if (preg_match('/\.tpl$/', $path))
				{
					$problems = $this->lintLTpl($path);
					if (count($problems) > 0)
						$issues[substr($path, strlen(_PS_ROOT_DIR_)+1)] = $problems;
				}

		return array('issues' => $issues);
	}

	public function lintLTpl($path)
	{
		$problems = array();

		$parser = new SmartyFunctionCallParser(file_get_contents($path), 'l');
		$matches = $parser->parse();

		foreach ($matches as $arguments)
		{
			$problem = null;
			if (!isset($arguments['mod']))
				$problem = "Missing 'mod' argument. [s: {$arguments['s']}]";
			else if (isset($arguments['mod']))
			{
				$m  = array();
				preg_match('#(?:^|/)modules/([^/]+)/#', $path, $m);
				$module = $m[1];
				$mod = TranslationsExtractor::dequote($arguments['mod']);
				if (!$mod)
					$mod = $arguments['mod'];
				if ($mod !== $module)
					$problem = "Wrong 'mod' argument, '$mod' instead of '$module'.";
			}

			if (!$problem)
			{
				if($str=TranslationsExtractor::dequote($arguments['s']))
				{
					$quote = $arguments['s'][0];
					$otherquote = $quote === '"' ? "'" : '"';
					$wrongescape = '\\'.$otherquote;
					if (strpos($str, $wrongescape) !== false)
						$problem = 'Superfluous quote escaping in: '.$arguments['s'];
				}
			}
				

			if ($problem !== null)
			{
				if (!isset($problems[$problem]))
					$problems[$problem] = 0;

				$problems[$problem] += 1;
			}
		}

		return $problems;
	}

}
