<?php

class CrowdinPHP
{
	private $identifier;
	private $key;

	public function __construct($project_identifier, $api_key)
	{
		$this->identifier = $project_identifier;
		$this->key = $api_key;
	}
	
	/*
	Starting with PHP 5.5, the '@' syntax to upload files is deprecated
	so use the new way (curl_file_create) if possible,
	else use the old one.
	*/
	public function file($path)
	{
		if (function_exists('curl_file_create'))
			return curl_file_create(realpath($path));
		else
			return '@'.realpath($path);
	}

	/* 
	Transform a multi-dimensional array into a 2d one,
	so that it can be used properly by curl (curl is dumb).
	E.g.: [a => [b => c]] is transformed into: [a[b] => c]
	If you pass [a => [b => c]] to CURLOPT_POSTFIELDS
	it will assume [b => c] is a string!
	Passing [a[b] => c] works, though, hence this function.
	*/
	private function flatten($array)
	{
		$out = array();
		foreach ($array as $key => $value)
		{
			if (is_array($value))
				foreach ($this->flatten($value) as $k => $v)
					$out["$key"."[$k]"] = $v;
			else
				$out[$key] = $value;
		}

		return $out;
	}

	/*
	Make a request to the Crowdin API (http://crowdin.net/page/api), return result as JSON.
	Takes care of authentication.
	*/
	public function makeRequest($method, $data)
	{
		// We like JSON. With this, Crowdin will return JSON. JSON is good.
		if (isset($data['json']) && $data['json'] === false)
			unset($data['json']);
		else
			$data['json'] = true;

		$url = "http://api.crowdin.net/api/project/{$this->identifier}/$method?key={$this->key}";

		$payload = $this->flatten($data);

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		if (isset($data['json']))
			$result = json_decode($result, true);
		return $result;
	}

	private function extractFilesInfo($cwd, $nodes, &$files)
	{
		$base = $cwd === '' ? '' : ($cwd.'/');
		foreach ($nodes as $node)
		{
			if ($node['node_type'] === 'file')
			{
				$files[$base.$node['name']] = array(
					'created' => $node['created'],
					'last_updated' => $node['last_updated'],
					'last_accessed' => $node['last_accessed']
				);
			}
			else if ($node['node_type'] === 'directory')
				$this->extractFilesInfo($base.$node['name'], $node['files'], $files);
		}
	}

	/*
	This method will use the public download link if $language === all
	(this doens't require privileges if the project has allowed public downloads)
	If $language is different from 'all' it will use the private API
	*/
	public function downloadTranslations($language='all')
	{
		if ($language === 'all')
		{
			$url = "http://crowdin.net/download/project/{$this->identifier}.zip";
		}
		else
		{
			$url = "http://api.crowdin.net/api/project/{$this->identifier}/download/$language.zip?key={$this->key}";
		}
		return @file_get_contents($url);
	}

	public function info()
	{
		// Get project info from Crowding
		$response = $this->makeRequest('info', array());
		// Make it into a nicer form
		$info = array(
			'languages' => array(),
			'files' => array()
		);

		foreach ($response['languages'] as $language)
			$info['languages'][$language['code']] = $language['name'];

		$this->extractFilesInfo('', $response['files'], $info['files']);

		$info['directories'] = array_unique(
			array_map('dirname', array_keys($info['files']))
		);

		sort($info['directories']);

		return $info;
	}

	/*
	Add or update a file to crowdin
	Data is an array with the following MANDATORY keys:
	- dest: target path
	- src: local path
	Optional keys are:
	- type
	- title
	- export_pattern
	*/
	public function addOrUpdateFile($add_or_update, $params)
	{
		if (file_exists($params['src']))
		{
			$data = array(
				'files' => array(
					$params['dest'] => $this->file($params['src'])

				)
			);

			// Setting type can only be done when adding the file
			if ($add_or_update === 'add' && isset($params['type']))
				$data['type'] = array($params['dest'] => $params['type']);

			if (isset($params['title']))
				$data['titles'] = array($params['dest'] => $params['title']);

			if (isset($params['export_pattern']))
				$data['export_patterns'] = array($params['dest'] => $params['export_pattern']);

			return $this->makeRequest($add_or_update.'-file', $data);

		}
		else
			return array(
				'success' => false,

				'error' => array(
					'code' => -1,

					'message' => 'Local file does not exist'
				)
			);
	}

	public function addFile($params)
	{
		return $this->addOrUpdateFile('add', $params);
	}

	public function updateFile($params)
	{
		return $this->addOrUpdateFile('update', $params);
	}

	public function createDirectory($path)
	{
		return $this->makeRequest('add-directory', array('name' => $path));
	}

	public function uploadTranslations($language, $src, $dest)
	{
		if (!file_exists($src))
			return "Could not find source file for '$dest' in '$language'";

		$data = array(
			'files' => array(
				$dest => $this->file($src)
			),
			'language' => $language,
			'import_duplicates' => 0,
			'import_eq_suggestions' => 0,
			'auto_approve_imported' => 0,
		);

		return $this->makeRequest('upload-translation', $data);
	}
}