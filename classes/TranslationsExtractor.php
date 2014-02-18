<?php

@ini_set('display_errors', 'on');

require dirname(__FILE__).'/parsing/PHPFunctionCallParser.php';
require dirname(__FILE__).'/parsing/SmartyLParser.php';
require dirname(__FILE__).'/FilesLister.php';

class TranslationsExtractor
{
	public function __construct()
	{
		$this->modules_parse_what = 'both';
		$this->modules_store_where = 'core';
		$this->language = "-";
		$this->files = array();
		$this->raw_files = array();
	}

	public function save()
	{
		$this->old_files = $this->files;
		$this->old_raw_files = $this->raw_files;
	}

	public function load()
	{
		$this->files = $this->old_files;
		$this->raw_files = $this->old_raw_files;
	}

	public function setModuleFilter($filter = null)
	{
		$this->module_filter = $filter;
	}

	public function buildFromTranslationFiles($dir)
	{
		$this->files = array();

		$dir = FilesLister::cleanPath(realpath($dir));

		if (!is_dir($dir))
			return "Sources directory does not exist, please ensure that you have exported the English strings.";

		foreach (FilesLister::listFiles($dir, null, null, true) as $path)
		{
			if (preg_match('/\.php$/', $path))
			{
				$relpath = Tools::substr($path, Tools::strlen($dir)+1);

				$n = 0;

				$file = str_replace(array('/en/', '/en.php'), array('/[lc]/', '/[lc].php'), $relpath, $n);

				if ($n !== 1)
					return "File '$relpath' doesn't seem to be an English source.";

				$dictionary = array();
				if (basename($path) === 'install.php')
				{
					if (file_exists($path))
					{
						$tmp = include $path;
						$dictionary = $tmp['translations'];
					}
				}
				else
				{
					$dictionary = $this->parseDictionary($path);
				}

				$data = array();

				foreach ($dictionary as $key => $value)
				{
					$data[$key] = array('message' => $value);
				}

				$this->files[$file] = $data;
			}
		}

		return true;
	}

	public function getFiles()
	{
		return $this->files;
	}

	public function setSections($sections)
	{
		$this->sections = array();
		foreach ($sections as $key => $value)
		{
			if ($value == 1)
				$this->sections[] = $key;
		}
	}

	public function setRootDir($dir)
	{
		$this->root_dir = FilesLister::cleanPath($dir);
	}

	public function setTheme($theme)
	{
		$this->theme = $theme;
	}

	public function setLanguage($language)
	{
		$this->language = $language;
	}

	// $parseWhat can be: both, core, overriden
	// $storeWhere can be: core, theme
	public function setModuleParsingBehaviour($parseWhat, $storeWhere)
	{
		$this->modules_parse_what = $parseWhat;
		$this->modules_store_where = $storeWhere;
	}

	public function extract($to_folder = null)
	{		
		foreach ($this->sections as $section)
		{
			$method = 'extract'.Tools::ucfirst($section).'Strings';
			if (is_callable(array($this, $method)))
				$this->$method();
			else
				die("Unknown method: $method");
		}
		$this->fill();

		if ($to_folder)
			$this->write($to_folder);
	}

	public function writeInstallerTranslations($base, $path, $dictionary)
	{
		$existing_file = FilesLister::join($this->root_dir, $path);

		if (file_exists($existing_file))
		{
			$data = include $existing_file;
		}
		else
		{
			$data = array(
				'informations' => array(),
				'translations' => array()
			);
		}

		$data['translations'] = $dictionary;

		$output_file = FilesLister::join($base, $path);
		$output_dir = dirname($output_file);
		if (!is_dir($output_dir))
			if (!@mkdir($output_dir, 0777, true))
				return false;
		$str_data = "<?php\n\nreturn ".var_export($data, true).";\n";
		return @file_put_contents($output_file, $str_data) ? $output_file : false;
	}

	public function write($to_folder)
	{		
		$wrote = array();

		if (!is_dir($to_folder))
		{
			if (!@mkdir($to_folder, 0777, true))
				return $wrote;
		}

		if (is_dir($to_folder))
		{
			$lc = $this->language !== '-' ? $this->language : 'en';

			$this->rmDir($this->join($to_folder, $lc));

			foreach ($this->files as $name => $contents)
			{
				$array_name = null;
				if (preg_match('#lang_content\.php$#', $name))
					$array_name = '_LANGMAIL';
				else if (basename($name) === 'admin.php')
					$array_name = '_LANGADM';
				else if (basename($name) === 'errors.php')
					$array_name = '_ERRORS';
				else if (basename($name) === 'pdf.php')
					$array_name = '_LANGPDF';
				else if (preg_match('#/lang/\[lc\]\.php$#', $name))
					$array_name = '_LANG';
				else if (preg_match('#(?:/|^)modules/#', $name))
					$array_name = '_MODULE';
				else if (preg_match('#/tabs\.php$#', $name))
					$array_name = '_TABS';
				else if (preg_match('#^mails/#', $name))
					$array_name = '_LANGMAIL';
				else if (basename($name) === 'fields.php')
					$array_name = '_FIELDS';

				if ($array_name !== null)
				{
					$dictionary = array();
					foreach ($contents as $key => $data)
						if ($data['translation'])
							$dictionary[$key] = $data['translation'];

					$path = $this->join($this->join($to_folder, $lc), str_replace('[lc]', $lc, $name));

					if (!is_dir(dirname($path)))
						mkdir(dirname($path), 0777, true);
					file_put_contents($path, $this->dictionaryToArray($array_name, $dictionary, $array_name !== '_TABS'));
					$wrote[] = $path;
				}
				else if (basename($name) === 'install.php')
				{
					$dictionary = array();
					foreach ($contents as $key => $data)
						if ($data['translation'])
							$dictionary[$key] = $data['translation'];
					$path = str_replace('[lc]', $lc, $name);
					$written = $this->writeInstallerTranslations(FilesLister::join($to_folder, $lc), $path, $dictionary);
					if ($written !== false)
						$wrote[] = $written;
				}
				else
				{
					die("Could not guess array name for file '$name'.");
				}

			}

			foreach ($this->raw_files as $name => $contents)
			{
				$path = $this->join($to_folder, $lc.'/'.$name);

				if (!is_dir(dirname($path)))
					mkdir(dirname($path), 0777, true);
				$put = file_put_contents($path, $contents);
			}
		}

		return $wrote;
	}

	public function parseDictionary($path)
	{
		if(!file_exists($path))
			return array();

		$data = Tools::file_get_contents($path);

		return self::parseDictionaryFromString($data);
	}

	public static function parseDictionaryFromString($data)
	{
		$matches = array();
		$n = preg_match_all('/^\\s*\\$?\\w+\\s*\\[\\s*\'((?:\\\\\'|[^\'])+)\'\\s*]\\s*=\\s*\'((?:\\\\\'|[^\'])+)\'\\s*;$/m', $data, $matches);

		$dictionary = array();

		for ($i = 0; $i<$n; $i++)
			$dictionary[$matches[1][$i]] = $matches[2][$i];

		return $dictionary;
	}

	public function dictionaryToArray($name, $data, $global = true)
	{
		$str = "<?php\n\n";
		if ($global)
			$str .= 'global $'.$name.";\n";
		$str .= '$'.$name." = array();\n\n";

		foreach ($data as $key => $value)
			if (trim($value) != '')
				$str .= '$'.$name.'['.$this->quote($key).'] = '.$this->quote($value).";\n";

		$str .= "\n\nreturn ".'$'.$name.";\n";

		return $str;
	}

	public function fill()
	{
		foreach ($this->files as $name => &$data)
		{
			$dictionary = array();
			$lang = $this->language === '-' ? 'en' : $this->language;

			// The installer is different from everything else :)
			if (basename($name) === 'install.php')
			{
				$relpath = str_replace('[lc]', $lang, $name);
				$relpath = preg_replace('#^[^/]+#', $this->findInstallerName(), $relpath);
				$source = $this->join($this->root_dir, $relpath);
				if (file_exists($source))
				{
					$tmp = include $source;
					$dictionary = $tmp['translations'];
				}
			}
			else
			{
				$base_source = FilesLister::join($this->root_dir, str_replace('[lc]', $lang, $name));
				$translations_sources = array(
					$base_source
				);
				foreach ($translations_sources as $src)
					$dictionary = array_merge($this->parseDictionary($src), $dictionary);
			}			

			foreach ($data as $key => &$message)
			{
				if ($this->language === '-')
				{
					if (isset($dictionary[$message['message']]))
					{
						$message['translation'] = $dictionary[$key];
					}
					elseif (preg_match('/admin\.php$/', $name))
					{
						$message['translation'] = preg_replace('/:\s*$/', '', $message['message']);
					}
					else
					{
						$message['translation'] = $message['message'];
					}
				}
				else
					$message['translation'] = isset($dictionary[$key]) ? $dictionary[$key] : null;
			}
		}
		
		if($this->language !== '-')
		{
			$raw_files = array();
			foreach ($this->raw_files as $name => $unused)
			{
				$new = preg_replace('#(^|/)mails/en/#', '\1mails/'.$this->language.'/', $name);
				$path = FilesLister::join($this->root_dir, $new);
				if (file_exists($path) && basename($path) !== 'lang.php')
					$raw_files[$new] = file_get_contents($path);
			}
			$this->raw_files = $raw_files;
		}
	}

	public function computeStats()
	{
		$stats = array(null => array('total' => 0, 'translated' => 0));
		foreach ($this->files as $name => $messages)
		{
			// DIRTY: do not count installer in stats, cuz not useful to many ppl
			if (basename($name) === 'install.php')
				continue;

			$stats[null]['total'] += count($messages);
			$stats[$name] = array('total' => count($messages), 'translated' => 0);

			foreach ($messages as $key => $message)
			{
				if ($message['translation'] != '')
				{
					$stats[$name]['translated'] += 1; 
					$stats[null]['translated'] += 1; 
				}
			}
		}

		foreach ($stats as $file => $details)
		{
			$stats[$file]['percent_translated'] = $details['total'] > 0 ? 100*$details['translated'] / $details['total'] : 0; 
		}

		return $stats;
	}

	public function diffFromArrayOfDictionaries($lc, $files)
	{
		foreach ($this->files as $name => &$data)
		{
			$lcname = str_replace('[lc]', $lc, $name);
			if (isset($files[$lcname]))
			{
				foreach ($data as $key => $message)
				{
					if (
						(
							isset($files[$lcname]) 
							&& isset($files[$lcname][$key]) 
							&& $files[$lcname][$key] === $message['translation']
						)
						|| !$message['translation']
					)
					{
						unset($data[$key]);
					}
				}
			}
			if (count($data) === 0)
			{
				unset($this->files[$name]);
			}
		}
	}

	public function join($a, $b)
	{
		return FilesLister::join($a, $b);
	}

	public function getAdminControllersDir()
	{
		if (defined('_PS_ADMIN_CONTROLLER_DIR_'))
			return _PS_ADMIN_CONTROLLER_DIR_;
		else return $this->join($this->root_dir, 'controllers/admin');
	}

	public function getOverrideDir()
	{
		if (defined('_PS_OVERRIDE_DIR_'))
			return _PS_OVERRIDE_DIR_;
		else
			return $this->join($this->root_dir, 'override');
	}

	public function getClassesDir()
	{
		if (defined('_PS_CLASS_DIR_'))
			return _PS_CLASS_DIR_;
		else
			return $this->join($this->root_dir, 'classes');
	}

	public function getAdminOverridenControllersDir()
	{
		if (defined('_PS_OVERRIDE_DIR_'))
			return $this->join(_PS_OVERRIDE_DIR_, 'controllers/admin');
		else return $this->join($this->root_dir, 'override/controllers/admin');
	}

	public function getHelpersDir()
	{
		if (defined('_PS_CLASS_DIR_'))
			return $this->join(_PS_CLASS_DIR_, 'helper');
		else
			return $this->join($this->root_dir, 'classes/helper');
	}

	public function getAdminControllerPath()
	{
		if (defined('_PS_CLASS_DIR_'))
			return $this->join(_PS_CLASS_DIR_, 'controller/AdminController.php');
		else
			return $this->join($this->root_dir, 'classes/controller/AdminController.php');

	}

	public function getPaymentModulePath()
	{
		if (defined('_PS_CLASS_DIR_'))
			return $this->join(_PS_CLASS_DIR_, 'PaymentModule.php');
		else
			return $this->join($this->root_dir, 'classes/PaymentModule.php');
	}

	public function getAdminDir()
	{
		if (defined('_PS_ADMIN_DIR_'))
			return _PS_ADMIN_DIR_;
		else
			return $this->join($this->root_dir, 'admin-dev');
	}

	public function getThemesDir()
	{
		if (defined('_PS_ALL_THEMES_DIR_'))
			return _PS_ALL_THEMES_DIR_;
		else
			return $this->join($this->root_dir, 'themes');
	}

	public function getModulesDir()
	{
		if (defined('_PS_MODULE_DIR_'))
			return _PS_MODULE_DIR_;
		else
			return $this->join($this->root_dir, 'modules');
	}

	public function getPdfsDir()
	{
		if (defined('_PS_PDF_DIR_'))
			return _PS_PDF_DIR_;
		else
			return $this->join($this->root_dir, 'pdf');
	}

	public static function dequote($str, $unescape = false)
	{
		if (mb_strlen($str) < 2)
			return false;
		$fc = mb_substr($str, 0, 1);
		$lc = mb_substr($str, -1);

		if ($fc === $lc && ($fc === '\'' || $lc === '"'))
		{
			$string = mb_substr($str, 1, mb_strlen($str)-2);
			if ($unescape)
				return preg_replace('/\\\*\'/', '\'', $string);
			else
			{
				// As per: https://github.com/djfm/PrestaShop/commit/9ae63c6ecffd4f2a1679e2b6f9f8f1e969b64342
				if ($fc === '"')
				{
					// Escape single quotes because the core will do it when looking for the translation of this string
					$string = str_replace('\'', '\\\'', $string);
					// Unescape double quotes
					$string = preg_replace('/\\\\+"/', '"', $string);
				}
				return $string;
			}
		}
		else
			return false;
	}

	public function quote($str)
	{
		return '\''.str_replace("\n", '', preg_replace('/\\\*\'/', '\\\'', $str)).'\'';
	}

	public function record($string, $key, $storage_file, $type)
	{
		if (!isset($this->files[$storage_file]))
			$this->files[$storage_file] = array();

		$this->files[$storage_file][$key] = array(
			'message' => $string,
			'type' => $type
		);
	}

	public function getAdminUnSpecificPHPPrefixKey($file)
	{
		$prefix_key = basename($file);
		if (strpos($file, 'Controller.php') !== false)
			$prefix_key = basename(Tools::substr($file, 0, -14));
		else if (strpos($file, 'Helper') !== false)
			$prefix_key = 'Helper';

		if ($prefix_key == 'Admin')
			$prefix_key = 'AdminController';

		if ($prefix_key == 'PaymentModule.php')
			$prefix_key = 'PaymentModule';
		return $prefix_key;
	}

	public function getAdminTPLPrefixKey($file)
	{
		// get controller name instead of file name
		$prefix_key = Tools::toCamelCase(str_replace($this->getAdminDir().'/themes', '', $file), true);
		$pos = strrpos($prefix_key, '/');
		$tmp = Tools::substr($prefix_key, 0, $pos);

		if (preg_match('#controllers#', $tmp))
		{
			$parent_class = explode('/', $tmp);
			$override = array_search('override', $parent_class);
			if ($override !== false)
				// case override/controllers/admin/templates/controller_name
				$prefix_key = 'Admin'.Tools::ucfirst($parent_class[$override + 4]);
			else
			{
				// case admin_name/themes/theme_name/template/controllers/controller_name
				$key = array_search('controllers', $parent_class);
				$prefix_key = 'Admin'.Tools::ucfirst($parent_class[$key + 1]);
			}
		}
		else
			$prefix_key = 'Admin'.Tools::ucfirst(Tools::substr($tmp, strrpos($tmp, '/') + 1, $pos));

		// Adding list, form, option in Helper Translations
		$list_prefix_key = array('AdminHelpers', 'AdminList', 'AdminView', 'AdminOptions', 'AdminForm', 'AdminHelpAccess', 'AdminCalendar', 'AdminTree', 'AdminUploader', 'AdminDataviz', 'AdminKpi', 'AdminModule_list');
		if (in_array($prefix_key, $list_prefix_key))
			$prefix_key = 'Helper';

		// Adding the folder backup/download/ in AdminBackup Translations
		if ($prefix_key == 'AdminDownload')
			$prefix_key = 'AdminBackup';

		// use the prefix "AdminController" (like old php files 'header', 'footer.inc', 'index', 'login', 'password', 'functions'
		if ($prefix_key == 'Admin' || $prefix_key == 'AdminTemplate')
			$prefix_key = 'AdminController';

		return $prefix_key;
	}

	public function extractBackOfficeStrings()
	{
		/**************************************************************/
		/*                  Regular PHP files                         */
		/**************************************************************/
		$files = array_merge(
			FilesLister::listFiles($this->getAdminControllersDir(), '/\.php$/'),
			FilesLister::listFiles($this->getAdminOverridenControllersDir(), '/\.php$/'),
			FilesLister::listFiles($this->getHelpersDir(), '/\.php$/'),
			array(
				$this->getAdminControllerPath(),
				$this->getPaymentModulePath()
			)
		);

		$storage_file = 'translations/[lc]/admin.php';
		$type = 'backOffice';

		foreach ($files as $file)
		{
			$prefix_key = $this->getAdminUnSpecificPHPPrefixKey($file);

			$parser = new PHPFunctionCallParser();
			$parser->setPattern('\$this\s*->\s*l');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if ($str = self::dequote($m['arguments'][0]))
				{
					$key = $prefix_key.md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}

		/**************************************************************/
		/*                  Specific PHP files                        */
		/**************************************************************/

		$admin_dir = $this->getAdminDir();
		$files = array(
			$this->join($admin_dir, 'header.inc.php'),
			$this->join($admin_dir, 'footer.inc.php'),
			$this->join($admin_dir, 'index.php'),
			$this->join($admin_dir, 'functions.php'),
		);

		foreach ($files as $file)
		{
			$prefix_key = 'index';

			$parser = new PHPFunctionCallParser();
			$parser->setPattern('Translate\s*::\s*getAdminTranslation');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if ($str = self::dequote($m['arguments'][0]))
				{
					$key = $prefix_key.md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}

		/**************************************************************/
		/*                  Templates                                 */
		/**************************************************************/
		$files = array_merge(
			FilesLister::listFiles($this->join($this->getAdminDir(), 'themes'), '/\.tpl$/', null, true),
			FilesLister::listFiles($this->join($this->getAdminOverridenControllersDir(), 'admin/templates'), '/\.tpl$/', null, true)
		);
		foreach ($files as $file)
		{	
			$parser = new SmartyLParser();
			$strings = $parser->parse($file);

			$prefix_key = $this->getAdminTPLPrefixKey($file);

			foreach($strings as $string)
			{
				if ($str = self::dequote($string))
				{
					$key = $prefix_key.md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}
	}

	public function extractFrontOfficeStrings()
	{
		$files = array_merge(
			FilesLister::listFiles($this->join($this->getThemesDir(), $this->theme), '/\.tpl$/', '#/modules/#', true),
			FilesLister::listFiles($this->getThemesDir(), '/\.tpl$/')
			// + override?
		);
		$storage_file = 'themes/'.$this->theme.'/lang/[lc].php';
		$type = 'frontOffice';
		$parser = new SmartyLParser();
		foreach ($files as $file)
		{
			if (basename($file) === 'debug.tpl')
				continue;
			$prefix_key = Tools::substr(basename($file), 0, -4);
			foreach($parser->parse($file) as $string)

				if ($str = self::dequote($string))
				{
					$key = $prefix_key.'_'.md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
		}
	}

	public function extractMailSubjectsStrings()
	{
		$files = FilesLister::listFiles($this->root_dir, '/\.php$/', '#/tools/|/cache/|\.tpl\.php$|/[a-z]{2}\.php$#', true);

		$storage_file = 'mails/[lc]/lang.php';
		$type = 'mailSubjects';

		foreach ($files as $file)
		{
			$parser = new PHPFunctionCallParser();
			$parser->setPattern('Mail\s*::\s*l');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if (count($m['arguments']) > 0 && $str = self::dequote($m['arguments'][0]))
				{
					$key = $str;
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}
	}

	public function extractMailContentStrings()
	{
		$files = FilesLister::listFiles(
			$this->join($this->getModulesDir(),'emailgenerator/templates'),

			'#emailgenerator/templates/[^/]+/.*?\.php$#', null, true
		);

		$storage_file = 'modules/emailgenerator/templates_translations/[lc]/lang_content.php';
		$type = 'mailContent';

		foreach ($files as $file)
		{
			$parser = new PHPFunctionCallParser();
			$parser->setPattern('\bt');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if (count($m['arguments']) > 0 && $str = self::dequote($m['arguments'][0]))
				{
					$key = $str;
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}
	}

	public function extractGeneratedEmailsStrings()
	{
		$lc = $this->language;
		if ($lc === '-')
			$lc = 'en';

		$module_emails = FilesLister::listFiles(
			$this->getModulesDir(),
			'#'.preg_quote(preg_replace('#/$#', '', $this->getModulesDir())).'/[^/]+/mails/'.$lc.'/(.*?)\.(?:txt|html)$#',
			null,
			true
		);
		$core_emails = FilesLister::listFiles(
			FilesLister::join($this->root_dir, 'mails/'.$lc),
			'#\.(?:txt|html)$#'
		);
		
		$files = array_merge($module_emails, $core_emails);

		foreach ($files as $path)
		{
			$this->raw_files[Tools::substr($path, Tools::strlen($this->root_dir)+1)] = Tools::file_get_contents($path);
		}
	}

	public function extractFieldsStrings()
	{
		$src = dirname(__FILE__).'/../data/fields';
		if (file_exists($src) && ($data = @file_get_contents($src)))
		{
			$dictionary = array();
			foreach (preg_split('#\n+#', $data) as $line)
			{
				$left_right = explode(':', $line);
				if (count($left_right) === 2)
				{
					$classes = array_map('trim', explode(',', $left_right[0]));
					$fields = array_map('trim', explode(',', $left_right[1]));

					foreach ($classes as $class)
						foreach ($fields as $field)
							$this->record(
								$field,
								$class.'_'.md5($field),
								'translations/[lc]/fields.php',
								'fields'
							);
				}
			}
		}
		else
		{
			die("Could not find fields file!");
		}
	}

	public function extractErrorsStrings()
	{
		$files = FilesLister::listFiles($this->root_dir, '/\.php$/', '#/tools/|/cache/|\.tpl\.php$|/[a-z]{2}\.php$#', true);

		$storage_file = 'translations/[lc]/errors.php';
		$type = 'errors';
		$tstart = time();

		foreach ($files as $n => $file)
		{
			$parser = new PHPFunctionCallParser();
			$parser->setPattern('Tools\s*::\s*displayError');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if (count($m['arguments']) > 0 && $str = self::dequote($m['arguments'][0]))
				{
					$key = md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}
	}

	public function findInstallerName()
	{
		$candidates = array('install-dev', 'install');
		foreach ($candidates as $candidate)
		{
			$dir = FilesLister::join($this->root_dir, $candidate);
			if (is_dir($dir))
			{
				return $candidate;
			}
		}
		return false;
	}

	public function findInstaller()
	{
		$name = $this->findInstallerName();
		if ($name === false)
			return false;

		return FilesLister::join($this->root_dir, $name);
	}

	public function extractInstallerStrings()
	{
		$dir = $this->findInstaller();

		if ($dir === false)
			return;

		$files = FilesLister::recListFiles($dir, '/\.php|\.phtml/');

		$storage_file = FilesLister::join(Tools::substr($dir, Tools::strlen($this->root_dir)+1),'langs/[lc]/install.php');
		// Always name the folder install-dev
		$storage_file = preg_replace('#^[^/]+#', 'install-dev', $storage_file);

		foreach ($files as $file)
		{
			$parser = new PHPFunctionCallParser();
			$parser->setPattern('->\s*l');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				// Fully dequote because translations are read from the PHP array in memory,
				// not parsed, so quote are not unnecessarily escaped in the translations.
				if ($str = self::dequote($m['arguments'][0], true))
				{
					$key = $str;
					$this->record(
						$str,
						$key,
						$storage_file,
						'installer'
					);
				}
			}
		}
	}

	public function getModuleKey($kind, $module, $file, $str)
	{
		$mod = Tools::strtolower($module);

		$tmp = array();
		preg_match('/^(.*?)(?:\.tpl|\.php)$/', basename($file), $tmp);
		$name = $tmp[1];
		$f = Tools::strtolower($name);

		$md5 = md5($str);

		if ($this->modules_store_where === 'core')
			return '<{'.$mod.'}prestashop>'.$f.'_'.$md5;
		else if ($this->modules_store_where === 'theme' && $kind === 'core')
			return '<{'.$mod.'}prestashop>'.$f.'_'.$md5;
		else if ($this->modules_store_where === 'theme' && $kind === 'overriden')
			return '<{'.$mod.'}'.$this->$theme.'>'.$f.'_'.$md5;
	}

	public function getModuleStorageFile($kind, $module, $file)
	{
		if ($this->modules_store_where === 'core')
			return 'modules/'.$module.'/translations/[lc].php';
		else if ($this->modules_store_where === 'theme' && $kind === 'core')
			return 'modules/'.$module.'/translations/[lc].php';
		else if ($this->modules_store_where === 'theme' && $kind === 'overriden')
			return 'themes/'.$this->theme.'/modules/'.$module.'/translations/[lc].php';

	}

	public function extractModulesStrings()
	{
		$root_dirs = array(
			'core' => $this->getModulesDir(),

			'overriden' => $this->join($this->getThemesDir(), $this->theme.'/modules')
		);

		$type = 'modules';

		foreach ($root_dirs as $kind => $dir)
		{
			foreach (scandir($dir) as $module)
			{
				if (isset($this->module_filter) && is_array($this->module_filter) && !in_array($module, $this->module_filter))
					continue;

				if (!preg_match('/^\./', $module))
					if (is_dir($module_root=$this->join($dir, $module)))
					{
						/**************************************************************/
						/*                        PHP files                           */
						/**************************************************************/

						$files = FilesLister::listFiles($module_root, '/\.php$/', null, true);

						foreach ($files as $file)
						{
							$storage_file = $this->getModuleStorageFile($kind, $module, $file);
							$parser = new PHPFunctionCallParser();
							$parser->setPattern('->\s*l');
							$parser->setString(Tools::file_get_contents($file));
							while ($m=$parser->getMatch())
							{
								if ($str = self::dequote($m['arguments'][0]))
								{
									$key = $this->getModuleKey($kind, $module, $file, $str);
									$this->record(
										$str,
										$key,
										$storage_file,
										$type
									);
								}
							}
						}

						/**************************************************************/
						/*                        Templates                           */
						/**************************************************************/

						$files = FilesLister::listFiles($module_root, '/\.tpl$/', null, true);
						$parser = new SmartyLParser();
						foreach ($files as $file)
						{
							if (basename($file) === 'debug.tpl')
								continue;

							$storage_file = $this->getModuleStorageFile($kind, $module, $file);

							foreach($parser->parse($file) as $string)
								if ($str = self::dequote($string))
								{
									$key = $this->getModuleKey($kind, $module, $file, $str);
									$this->record(
										$str,
										$key,
										$storage_file,
										$type
									);
								}
						}
					}
			}

		}

	}

	public function extractPdfsStrings()
	{
		/**************************************************************/
		/*                        PHP files                           */
		/**************************************************************/

		$files = array_merge(
			FilesLister::listFiles($this->join($this->getClassesDir(), 'pdf'), '/\.php$/'),
			FilesLister::listFiles($this->join($this->getOverrideDir(), 'classes/pdf'), '/\.php$/')
		);

		$storage_file = 'translations/[lc]/pdf.php';
		$type = 'pdfs';
		foreach ($files as $file)
		{
			$parser = new PHPFunctionCallParser();
			$parser->setPattern('HTMLTemplate\w*\s*::\s*l');
			$parser->setString(Tools::file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if ($str = self::dequote($m['arguments'][0]))
				{
					$key = 'PDF'.md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
			}
		}

		/**************************************************************/
		/*                        Templates                           */
		/**************************************************************/

		$files = array_merge(
			FilesLister::listFiles($this->getPdfsDir(), '/\.tpl$/'),
			FilesLister::listFiles($this->join($this->getThemesDir(), $this->theme.'/pdf'), '/\.tpl$/')
		);

		$parser = new SmartyLParser();
		foreach ($files as $file)
		{
			foreach($parser->parse($file) as $string)
				if ($str = self::dequote($string))
				{
					$key = 'PDF'.md5($str);
					$this->record(
						$str,
						$key,
						$storage_file,
						$type
					);
				}
		}

	}

	public function extractTabsStrings()
	{
		$blacklist = array('AdminTranslatools', 'AdminEmailGenerator');
		if (class_exists('Tab')) // We  would not have this if running from CLI
		{
			$id_lang = Language::getIdByIso($this->language !== '-' ? $this->language : 'en');
			foreach(Tab::getTabs($id_lang) as $tab)
			{
				if (in_array($tab['class_name'], $blacklist))
					continue;
				if ($tab['name'] != '')
					$this->record($tab['name'], $tab['class_name'], 'translations/[lc]/tabs.php', 'tabs');
			}
		}

	}

	public function sendAsGZIP($packs_dir)
	{
		require_once dirname(__FILE__).'/../../../tools/tar/Archive_Tar.php';

		$lc = $this->language !== '-' ? $this->language : 'en';
		$archname = $lc.'.tar.gz';

		$dir = $this->join($packs_dir, $lc);

		if (!is_dir($dir))
			die ("TranslationsExtractor: Directory does not exist: '$dir'.");

		chdir($dir);

		$archpath = '../'.$archname;

		// Just in case...
		if (file_exists($archpath))
			unlink($archpath);

		$arch = new Archive_Tar($archpath, 'gz');

		$add = array();

		foreach (FilesLister::listFiles('.', null, null, true) as $path)
			$add[] = preg_replace('#^\./#', '', $path);

		$arch->add($add);

		ob_end_clean();
		header('Content-Description: File Transfer');
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename='.$archname);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($archpath);
        exit;
	}

	public function rmDir($out)
	{
		return FilesLister::rmDir($out);
	}
}