<?php

require_once dirname(__FILE__).'/SimpleParser.php';

class SmartyFunctionCallParser extends SimpleParser
{
	public function __construct($string, $function_name)
	{
		$this->function_name = $function_name;
		$this->pattern = '/{'.$this->function_name.'\s+/';
		parent::__construct($string);
	}

	public function getMatch()
	{
		$this->arguments = array();
		$this->setState('key');
		$this->token = '';
		$this->key = '';

		$m = array();
		if (preg_match($this->pattern, $this->string, $m, PREG_OFFSET_CAPTURE, $this->at))
		{
			$this->at = $m[0][1] + mb_strlen($m[0][0]);

			while ($c=$this->peek())
			{
				$advance = true;

				if ($this->getState() === 'key')
				{
					if ($c === '}')
					{
						if ($this->key === '')
							return $this->arguments;
						else
							return false;
					}
					else if ($c === '=')
					{
						$this->key = $this->getToken();
						$this->popState();
						$this->pushState('before-value');
					}
					else if ($c !== '=')
					{
						$this->token .= $c;
					}
				}
				else if ($this->getState() === 'before-value')
				{
					if (!preg_match('/\s/', $c))
					{
						$this->token .= $c;

						$this->popState();

						if ($c === '[')
						{
							$this->pushState('value');
							$this->pushState('array');
						}
						else if ($c === '"' or $c === "'")
						{
							$this->quote = $c;
							$this->pushState('value');
							$this->pushState('string');
						}
						else
						{
							$this->pushState('value');
						}
					}
				}
				else if ($this->getState() === 'value')
				{
					if ($c === '}')
					{
						$this->arguments[$this->key] = $this->getToken();
						$this->key = '';
						$this->advance();
						return $this->arguments;
					}
					else if (preg_match('/\s/', $c))
					{
						$this->arguments[$this->key] = $this->getToken();
						$this->key = '';
						$this->popState();
						$this->pushState('key');
					}
					$this->token .= $c;
				}
				else if ($this->getState() === 'string')
				{
					$this->token .= $c;
					if ($c === '\\')
					{
						$this->pushState('escape');
					}
					else if ($c === $this->quote)
					{
						$this->popState();
					}
				}
				else if ($this->getState() === 'array')
				{
					$this->token .= $c;
					if ($c === ']')
					{
						$this->popState();
					}
					else if ($c === '"' || $c === "'")
					{
						$this->quote = $c;
						$this->pushState('string');
					}
				}
				else if ($this->getState() === 'escape')
				{
					$this->popState();
				}

				if ($advance)
					$this->advance();
			}
		}
		else
			return false;
	}

	public function getToken()
	{
		$token = trim($this->token);
		$this->token = '';
		return $token;
	}

	public function parse()
	{
		$matches = array();
		while ($match = $this->getMatch())
			$matches[] = $match;

		return $matches;
	}
}