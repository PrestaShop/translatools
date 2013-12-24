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

	public function addFile($data)
	{
		if (file_exists($data['realpath']))
		{
			$body = array(
				'json' => true,
				"files[{$data['path']}]" => $this->file($data['realpath'])
			);

			$url = "http://api.crowdin.net/api/project/{$this->identifier}/add-file?key={$this->key}";

			//$url = "http://safe.shell.la/echo/";

			$ch = curl_init($url);                                                                      
			curl_setopt($ch, CURLOPT_POST, true);                                                                     
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);                                                                  
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = json_decode(curl_exec($ch), true);
			die(print_r($result));
		}
		else
			return 'Local file does not exist: '.$data['realpath'].'.';
	}
}