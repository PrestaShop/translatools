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

	public function defaultAction()
	{
		$themes = array();
		foreach (scandir(_PS_ALL_THEMES_DIR_) as $entry)
			if (!preg_match('/^\./', $entry) && is_dir(_PS_ALL_THEMES_DIR_.$entry))
				$themes[] = $entry;


		$languages = array();
		foreach (Language::getLanguages() as $l)
			$languages[$l['iso_code']] = $l['name'];

		return array(
			'themes' => $themes,
			'languages' => $languages
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
		foreach ($hidden as $name => $value)
			$inputs[] = "<input type='hidden' name='$name' value='$value'>";
		
		$translacheck_stay_here = implode("\n", $inputs);
		$smarty->assign('translacheck_stay_here', $translacheck_stay_here); 
	}

	public function exportTranslationsAction()
	{
		require_once dirname(__FILE__).'/classes/TranslationsExtractor.php';

		$extractor = new TranslationsExtractor();
		$extractor->setSections(Tools::getValue('section'));
		$extractor->setRootDir(_PS_ROOT_DIR_);
		$extractor->setTheme(Tools::getValue('theme'));
		$extractor->setLanguage(Tools::getValue('language'));
		$extractor->setModuleParsingBehaviour(Tools::getValue('overriden_modules'), Tools::getValue('modules_storage'));
		$extractor->extract();
		$extractor->sendAsGZIP();
	}

	public function viewStatsAction()
	{
		require_once dirname(__FILE__).'/classes/TranslationsExtractor.php';

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
}
