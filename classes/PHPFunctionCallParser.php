<?php

class PHPFunctionCallParser
{
	public function setString($string)
	{
		$this->string = $string;
		$this->at = 0;
	}

	/**
	* $p here is treated as a regexp, but do not write delimiters.
	*/
	public function setPattern($p)
	{
		$this->pattern = '/('.$p.')\s*\(/';
	}

	public function peek($n=1)
	{
		return substr($this->string, $this->at, $n);
	}

	public function getc($n=1)
	{
		$this->at += $n;
		return substr($this->string, $this->at, $n);
	}

	public function getState()
	{
		return end($this->state);
	}

	public function pushState($state)
	{
		$this->state[] = $state;
	}

	public function popState()
	{
		array_pop($this->state);
	}

	public function initMatch()
	{
		$this->match = array('function' => '', 'arguments' => array());
	}

	public function takeArgument()
	{
		if ('' !== ($arg=trim($this->argument)))
			$this->match['arguments'][] = $arg;
		$this->argument = '';
	}

	public function getMatch()
	{
		$m = array();
		$start = preg_match(
			$this->pattern,
			$this->string,
			$m, 
			PREG_OFFSET_CAPTURE, 
			$this->at
		);

		if ($start)
		{
			$this->initMatch();

			$this->match['function'] = preg_replace('/\s+/', '', $m[1][0]);
			$this->at = $m[0][1] + mb_strlen($m[0][0]);

			$this->pushState('default');
			// Current argument
			$this->argument = '';
			while (false !== ($c=$this->peek()))
			{
				if ($this->getState() === 'default')
				{
					if ($c === ')')
					{
						$this->takeArgument();
						break;
					}
					elseif ($c === ',')
					{
						$this->takeArgument();
					}
					elseif ($c === '\'' || $c === '"')
					{
						$this->argument .= $c;
						$this->pushState('string');
						$this->quote = $c;
					}
					elseif ($c === '(')
					{
						$this->argument .= $c;
						$this->pushState('paren');
					}
					else
					{
						$this->argument .= $c;
					}
				}
				elseif ($this->getState() === 'paren')
				{
					$this->argument .= $c;
					if ($c === ')')
					{
						$this->popState();
					}
					elseif ($c === '(')
					{
						$this->pushState('paren');
					}
					elseif ($c === '\'' || $c === '"')
					{
						$this->pushState('string');
					}
				}
				elseif ($this->getState() === 'string')
				{
					$this->argument .= $c;

					if ($c === '\\')
					{
						$this->pushState('escape');
					}
					elseif ($c === $this->quote)
					{
						$this->popState();
					}
				}
				elseif ($this->getState() === 'escape')
				{
					$this->argument .= $c;
					$this->popState();
				}
				$this->at += 1;
			}

			if ($c === false)
				return false;
			else
				return $this->match;
		}
		else
		{
			return false;
		}
	}
}