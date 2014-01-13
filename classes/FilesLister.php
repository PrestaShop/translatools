<?php

class FilesLister
{
	public static function listFiles($dir, $whitelist=null, $blacklist=null, $recurse=false)
	{
		$files = array();

		if (!is_dir($dir))
			return $files;

		foreach (scandir($dir) as $file)
		{
			if ($file === '.' || $file === '..')
				continue;

			$path = static::join($dir, $file);
			if ($blacklist !== null && preg_match($blacklist, $path))
				continue;
			if ($whitelist !== null && !preg_match($whitelist, $path) && !is_dir($path))
				continue;

			if (is_dir($path) and $recurse)
			{
				$files = array_merge($files, static::listFiles($path, $whitelist, $blacklist, $recurse));
			}
			else if (!is_dir($path))
				$files[] = $path;
		}

		return $files;
	}

	public static function join($root, $path)
	{
		return preg_replace('#/+$#', '', $root).'/'.preg_replace('#^/+#', '', $path);
	}
}