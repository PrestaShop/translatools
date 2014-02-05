<?php
class SmartyLParser
{
	private $verbose = false;

	public function __construct()
	{
		$this->pattern = '/\{l\s+s\s*=\s*/u';
	}

	public function setVerbose($verbose=true)
	{
		$this->verbose = $verbose;
	}

	public function peek($n=1)
	{
		return mb_substr($this->string, $this->at, $n, 'UTF-8');
	}

	public function getc($n=1)
	{
		$str = $this->peek($n);
		$this->at += 1;
		return $str;
	}

	public function parse($path)
	{
		$str = file_get_contents($path);
		return $this->parseString($str);
	}

	public function parseString($string)
	{
		$this->strings = array();
		$this->string = $string;

		$this->at = 0;

		$m = array();
		while (preg_match($this->pattern, $this->string, $m, null, $this->at))
		{
			$this->str = '';
			$this->state = 'default';
			$this->at = mb_strpos($this->string, $m[0], $this->at, 'UTF-8') + mb_strlen($m[0], 'UTF-8');
			while (false !== ($c=$this->getc()))
			{
				$this->str .= $c;
				if ($this->state === 'default')
				{
					if ($c === '\'' || $c === '"')
					{
						$this->quote = $c;
						$this->state = 'string';
					}
					else
						break;
				}
				else if ($this->state === 'string')
				{
					if ($c === '\\')
						$this->state = 'escape';
					elseif ($c === $this->quote)
					{
						$this->strings[] = $this->str;
						break;
					}
				}
				else if ($this->state === 'escape')
				{
					$this->state = 'string';
				}
			}
		}

		return $this->strings;
	}
}