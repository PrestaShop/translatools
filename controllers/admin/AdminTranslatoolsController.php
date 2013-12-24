<?php

class AdminTranslatoolsController extends ModuleAdminController
{
	public function __construct()
	{
		$this->bootstrap = true;
		
		parent::__construct();
		if (!$this->module->active)
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
	}
}	