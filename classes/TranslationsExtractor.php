<?php

@ini_set('display_errors', 'on');

require dirname(__FILE__).'/PHPFunctionCallParser.php';
require dirname(__FILE__).'/SmartyLParser.php';

class TranslationsExtractor
{
	public function setSections($sections)
	{
		$this->sections = $sections;
	}

	public function setRootDir($dir)
	{
		$this->root_dir = $dir;
	}

	public function extract()
	{
		foreach ($this->sections as $section)
		{
			$method = 'extract'.ucfirst($section).'Strings';
			if (is_callable(array($this, $method)))
				$this->$method();
			else
				die("Unknown method: $method");
		}
		die("Done.");
	}

	public function join($root, $path)
	{
		return preg_replace('#/+$#', '', $root).'/'.preg_replace('#^/+#', '', $path);
	}

	// $whitelist affects only files, while $blacklist affects directories too.
	// $blacklist is used to skip recursion, while $whitelist controls which files
	// are returned.
	public function listFiles($dir, $whitelist=null, $blacklist=null, $recurse=false)
	{
		$files = array();

		if (!is_dir($dir))
			return $files;

		foreach (scandir($dir) as $file)
		{
			if ($file === '.' || $file === '..')
				continue;

			$path = $this->join($dir, $file);
			if ($blacklist !== null && preg_match($blacklist, $path))
				continue;
			if ($whitelist !== null && !preg_match($whitelist, $path) && !is_dir($path))
				continue;

			if (is_dir($path) and $recurse)
			{
				$files = array_merge($files, $this->listFiles($path, $whitelist, $blacklist, $recurse));
			}
			else
				$files[] = $path;
		}

		return $files;
	}

	public function extractFrontOfficeStrings()
	{

	}

	public function getAdminControllersDir()
	{
		if (defined('_PS_ADMIN_CONTROLLER_DIR_'))
			return _PS_ADMIN_CONTROLLER_DIR_;
		else return $this->join($this->root_dir, 'controllers/admin');
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

	public function dequote($str)
	{
		if (mb_strlen($str) < 2)
			return false;
		if ($str[0] === $str[mb_strlen($str)-1] && ($str[0] === '\'' || $str[0] === '"'))
			return substr($str, 1, mb_strlen($str)-2);
		else
			return $false;
	}

	public function record($string, $key, $storage_file, $type)
	{
		$data = array(
			'string' => $string,
			'key' => $key,
			'storage_file' => $storage_file,
			'type' => $type
		);
		echo "<PRE>";
		print_r($data);
		echo "</PRE>";
	}

	public function getAdminUnSpecificPHPPrefixKey($file)
	{
		$prefix_key = basename($file);
		if (strpos($file, 'Controller.php') !== false)
			$prefix_key = basename(substr($file, 0, -14));
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
		$tmp = substr($prefix_key, 0, $pos);

		if (preg_match('#controllers#', $tmp))
		{
			$parent_class = explode('/', $tmp);
			$override = array_search('override', $parent_class);
			if ($override !== false)
				// case override/controllers/admin/templates/controller_name
				$prefix_key = 'Admin'.ucfirst($parent_class[$override + 4]);
			else
			{
				// case admin_name/themes/theme_name/template/controllers/controller_name
				$key = array_search('controllers', $parent_class);
				$prefix_key = 'Admin'.ucfirst($parent_class[$key + 1]);
			}
		}
		else
			$prefix_key = 'Admin'.ucfirst(substr($tmp, strrpos($tmp, '/') + 1, $pos));

		// Adding list, form, option in Helper Translations
		$list_prefix_key = array('AdminHelpers', 'AdminList', 'AdminView', 'AdminOptions', 'AdminForm', 'AdminHelpAccess');
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
			$this->listFiles($this->getAdminControllersDir(), '/\.php$/'),
			$this->listFiles($this->getAdminOverridenControllersDir(), '/\.php$/'),
			$this->listFiles($this->getHelpersDir(), '/\.php$/'),
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
			$parser->setString(file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if ($str=$this->dequote($m['arguments'][0]))
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
			$parser->setString(file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if ($str=$this->dequote($m['arguments'][0]))
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
			$this->listFiles($this->join($this->getAdminDir(), 'themes'), '/\.tpl$/', null, true),
			$this->listFiles($this->join($this->getAdminOverridenControllersDir(), 'admin/templates'), '/\.tpl$/', null, true)
		);
		$parser = new SmartyLParser();
		foreach ($files as $n => $file)
		{
			$prefix_key = $this->getAdminTPLPrefixKey($file);
			foreach($parser->parse($file) as $string);
				if ($str=$this->dequote($string))
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

	public function extractErrorsStrings()
	{
		
	}

	public function extractModulesStrings()
	{
		
	}

	public function extractPdfsStrings()
	{
		
	}

	public function extractTabsStrings()
	{
		
	}
}