<?php

abstract class SimpleParser
{
	protected $string;
	protected $state = array();

	public function __construct($string)
	{
		$this->string = $string;
		$this->at = 0;
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

	public function pushState($state)
	{
		//echo "Push: $state<BR/>";
		$this->state[] = $state;
	}

	public function popState()
	{
		//echo "Pop: ".$this->getState()."<BR/>";
		return array_pop($this->state);
	}

	public function getState()
	{
		return end($this->state);
	}

	public function getStates()
	{
		return $this->state;
	}

	public function setState($state)
	{
		$this->state = array($state);
	}

	public function advance($n=1)
	{
		$this->at += $n;
	}
}