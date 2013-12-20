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

	public function extractFrontOfficeStrings()
	{

	}

	public function extractBackOfficeStrings()
	{
		$data = file_get_contents(_PS_ROOT_DIR_.'/controllers/admin/AdminProductsController.php');
		
		$parser = new PHPFunctionCallParser();
		$parser->setString($data);
		$parser->setPattern('\$this->l');

		while ($match=$parser->getMatch())
		{
			echo "<pre>";
			print_r($match);
			echo "</pre>";
		}

		die("<BR/>\nK.");
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