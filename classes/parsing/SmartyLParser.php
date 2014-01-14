<?php

class SmartyLParser
{
	public function __construct()
	{
		$this->pattern = '/\{l\s+s\s*=\s*/';
	}

	public function peek($n=1)
	{
		return mb_substr($this->string, $this->at, $n);
	}

	public function getc($n=1)
	{
		$str = $this->peek($n);
		$this->at += $n;
		return $str;
	}

	public function parse($path)
	{
		return $this->parseString(file_get_contents($path));
	}

	public function parseString($string)
	{
		$this->strings = array();
		$this->string = $string;

		$this->at = 0;

		$m = array();
		while (preg_match($this->pattern, $this->string, $m, PREG_OFFSET_CAPTURE, $this->at))
		{
			$this->str = '';
			$this->state = 'default';
			$this->at = $m[0][1] + mb_strlen($m[0][0]);
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