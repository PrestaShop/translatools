<?php

require_once dirname(__FILE__).'/../../../tools/smarty/Smarty.class.php';

class SmartyLParser
{
	public function __construct()
	{
		$this->smarty = new Smarty();

		$this->smarty->registerPlugin('compiler', 'l', array($this, 'smartyLFound'));

		/**
		* Define Smarty functions & modifiers so that it parses!
		*/
		$dummy = array($this, 'dummy');

		$functions = array(
			'getAdminToken', 
			'hook', 
			't', 'm', 'p', 'd', 'l',
			'toolsConvertPrice', 'dateFormat',
			'convertPrice', 'convertPriceWithCurrency',
			'displayWtPrice', 'displayWtPriceWithCurrency',
			'displayPrice', 'displayAddressDetail',
			'getWidthSize', 'getHeightSize',
			'summarypaginationlink'
		);

		$modifiers = array(
			'htmlentitiesUTF8', 'convertAndFormatPrice',
			'secureReferrer', 'truncate'
		);

		$blocks = array(
			'assign_debug_info'
		);

		foreach ($functions as $name)
			$this->smarty->registerPlugin('function', $name, $dummy);
		foreach ($modifiers as $name)
			$this->smarty->registerPlugin('modifier', $name, $dummy);
		foreach ($blocks as $name)
			$this->smarty->registerPlugin('block', $name, $dummy);
	}

	public function dummy()
	{

	}

	public function smartyLFound($params)
	{
		$this->strings[] = $params['s'];
	}

	public function parse($path)
	{
		$this->strings = array();
		$tpl = $this->smarty->createTemplate($path);
		try{
			// this will fire the smartyLFound callback
			$tpl->compileTemplateSource();
		}
		// Smarty will throw a BUNCH of exceptions because
		// we're not doing the full setup, catch them silently
		catch(Exception $e)
		{
			if ($e instanceof SmartyCompilerException)
			{
				die(get_class($e).": ".$e->getMessage());
			}
		}
		return $this->strings;
	}
}