<?php

require dirname(__FILE__).'/PHPFunctionCallParser.php';

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

	public function listFiles($dir, $whitelist=null, $blacklist=null, $recurse=false)
	{
		$files = array();

		foreach (scandir($dir) as $file)
		{
			$path = $this->join($dir, $file);
			if ($blacklist !== null && preg_match($blacklist, $path))
				continue;
			if ($whitelist !== null && !preg_match($whitelist, $path))
				continue;

			if (is_dir($path) and $recurse)
				$files = array_merge($files, $this->listFiles($path, $whitelist, $blacklist, $recurse));
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

	public function dequote($str)
	{
		if (mb_strlen($str) < 2)
			return false;
		if ($str[0] === $str[mb_strlen($str)-1] && ($str[0] === '\'' || $str[0] === '"'))
			return substr($str, 1, mb_strlen($str)-2);
		else
			return $false;
	}

	public function record($string, $key, $storage_file)
	{

	}

	public function extractBackOfficeStrings()
	{
		// Regular PHP files
		$files = array_merge(
			$this->listFiles($this->getAdminControllersDir(), '/\.php$/'),
			$this->listFiles($this->getAdminOverridenControllersDir(), '/\.php$/'),
			$this->listFiles($this->getHelpersDir(), '/\.php$/'),
			array(
				$this->getAdminControllerPath(),
				$this->getPaymentModulePath()
			)
		);

		foreach ($files as $file)
		{
			$parser = new PHPFunctionCallParser();
			$parser->setPattern('\$this\s*->\s*l');
			$parser->setString(file_get_contents($file));
			while ($m=$parser->getMatch())
			{
				if ($str=$this->dequote($m['arguments'][0]))
					$this->record(
						$str,
						$key
					);
			}
		}

		ddd($files);
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