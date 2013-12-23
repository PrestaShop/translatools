<?php

class SmartyLParser
{
	public function __construct()
	{
		$this->pattern = '/\{l\s+s\s*=\s*/';
	}

	public function peek($n=1)
	{
		return substr($this->string, $this->at, $n);
	}

	public function getc($n=1)
	{
		$str = $this->peek($n);
		$this->at += $n;
		return $str;
	}

	public function parse($path)
	{
		$this->strings = array();
		$this->string = file_get_contents($path);

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
		
		/*if (strpos($path, '/var/www/prestashop.1.6.fmdj.fr/pdf/delivery-slip.tpl') !== false)
			ddd($this->strings);*/
		return $this->strings;
	}
}