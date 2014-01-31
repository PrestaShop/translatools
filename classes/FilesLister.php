<?php

class FilesLister
{
	public static function listFiles($dir, $whitelist = null, $blacklist = null, $recurse = false)
	{
		$dir = self::cleanPath($dir);
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
				$files = array_merge($files, static::listFiles($path, $whitelist, $blacklist, $recurse));
			elseif (!is_dir($path))
				$files[] = $path;
		}

		return $files;
	}

	public static function recListFiles($dir, $whitelist = null, $blacklist = null)
	{
		return self::listFiles($dir, $whitelist, $blacklist, true);
	}

	public static function join($root, $path)
	{
		return self::cleanPath(preg_replace('#/+$#', '', $root)).'/'.self::cleanPath(preg_replace('#^/+#', '', $path));
	}

	public static function cleanPath($path)
	{
		return str_replace('\\', '/', $path);
	}

	public static function rmDir($out)
	{
		if (!is_dir($out))
			return;

		foreach (scandir($out) as $entry)
		{
			if ($entry === '.' or $entry === '..')
				continue;

			$path = self::join($out, $entry);

			if (is_dir($path))
				self::rmDir($path);
			else
				unlink($path);
		}

		rmdir($out);
	}
}