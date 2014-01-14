<?php

class TranslationsLinter
{
	public function checkCoherence()
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
						foreach (FilesLister::listFiles($module_root, '/\.tpl$/', null, true) as $overriden_path)
						{
							$original_path = _PS_MODULE_DIR_.Tools::substr($overriden_path, Tools::strlen($theme_modules_root));
							$differences = $this->getDifferences('tpl', $overriden_path, $original_path);
							if (count($differences) > 0)
								$deltas[] = array(
									'overriden_file' => Tools::substr($overriden_path, Tools::strlen(_PS_ROOT_DIR_)),
									'original_file' => file_exists($original_path) ? Tools::substr($original_path, Tools::strlen(_PS_ROOT_DIR_)) : false,
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

	public function checkLUse()
	{
		$issues = array();
		$root_dirs = array(_PS_MODULE_DIR_, _PS_ALL_THEMES_DIR_.Tools::getValue('theme').'/modules/');
		foreach ($root_dirs as $root_dir)
			foreach (FilesLister::listFiles(_PS_ROOT_DIR_, '/\.tpl$/', null, true) as $path)
				if (preg_match('#/modules/#', $path) && !preg_match('#/controllers/modules/#', $path))
				{
					$problems = $this->lintLTpl($path);
					if (count($problems) > 0)
						$issues[Tools::substr($path, Tools::strlen(_PS_ROOT_DIR_)+1)] = $problems;
				}

		return array('issues' => $issues);
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

		$data = Tools::file_get_contents($path);

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
			if (Tools::strlen($word) > 3)
				$bow[] = Tools::strtolower($word);
			
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

	public function lintLTpl($path)
	{
		$problems = array();

		$parser = new SmartyFunctionCallParser(Tools::file_get_contents($path), 'l');
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