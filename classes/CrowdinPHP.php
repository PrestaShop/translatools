<?php

class CrowdinPHP
{
	public function __construct($project_identifier, $api_key)
	{
		$this->identifier = $project_identifier;
		$this->key = $api_key;
	}

	public function file($path)
	{
		if (function_exists('curl_file_create'))
			return curl_file_create(realpath($path));
		else
			return '@'.realpath($path);
	}

	// Data is an array with the following keys:
	// - path: target path
	// - realpath: local path
	public function addFile($data)
	{
		if (file_exists($data['realpath']))
		{
			$body = array(
				'json' => true,
				"files[{$data['path']}]" => $this->file($data['realpath'])
			);

			if (isset($data['type']))
				$body["type[{$data['path']}]"] = $data['type'];

			if (isset($data['title']))
				$body["titles[{$data['path']}]"] = $data['title'];

			if (isset($data['export-pattern']))
				$body["export_patterns[{$data['path']}]"] = $data['export-pattern'];

			$url = "http://api.crowdin.net/api/project/{$this->identifier}/add-file?key={$this->key}";

			//$url = "http://safe.shell.la/echo/";

			$ch = curl_init($url);                                                                      
			curl_setopt($ch, CURLOPT_POST, true);                                                                     
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);                                                                  
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = json_decode(curl_exec($ch), true);
			if ($result['success'])
				return true;
			else
				return 'Crowdin Error: '.$result['error']['message'];
		}
		else
			return 'Local file does not exist: '.$data['realpath'].'.';
	}

	public function createDirectory($path)
	{
		$body = array(
			'json' => true,
			'name' => $path
		);

		$url = "http://api.crowdin.net/api/project/{$this->identifier}/add-directory?key={$this->key}";

		$ch = curl_init($url);                                                                      
		curl_setopt($ch, CURLOPT_POST, true);                                                                     
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);                                                                  
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($ch), true);
		if ($result['success'])
			return true;
		else
			return 'Crowdin Error: '.$result['error']['message'];
	}
}