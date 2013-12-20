<?php

@ini_set('display_errors', 'on');

/**
* Test PHPFunctionCallParser
*/

require_once dirname(__FILE__).'/../classes/PHPFunctionCallParser.php';

$fixtures = array(
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bob")',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bob"')
			)
		)
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b")',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"')
			)
		)
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", 42, 43)',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"', '42', '43')
			)
		)
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", (1 ? 2 : 3), 43)',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"', '(1 ? 2 : 3)', '43')
			)
		)
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", (1 ? 2 : "3,)"), 43)',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"', '(1 ? 2 : "3,)")', '43')
			)
		)
	)
	,
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", (1 ? 2 : "3,)"), function(){})',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"', '(1 ? 2 : "3,)")', 'function(){}')
			)
		)
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", (1 ? 2 : "3,)"), (function($bob){})(44))',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"', '(1 ? 2 : "3,)")', '(function($bob){})(44)')
			)
		)
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", (1 ? 2 : "3,)"), (function($bob){})("4),4"))',
		'expected' => array(
			array(
				'function' => '$this->l',
				'arguments' => array('"bo\"b"', '(1 ? 2 : "3,)")', '(function($bob){})("4),4")')
			)
		)
	)
);

foreach ($fixtures as $fixture)
{
	$parser = new PHPFunctionCallParser();
	$parser->setPattern($fixture['pattern']);
	$parser->setString($fixture['string']);

	$results = [];
	while ($r=$parser->getMatch())
	{
		$results[] = $r;
	}

	if($results !== $fixture['expected'])
	{
		echo "Not good!<BR/>";

		echo "<p>Expected:<BR/><pre>";
		print_r($fixture['expected']);
		echo "</pre></p><p>But got:<BR/><pre>";
		print_r($results);
		echo "</pre>";
		die();
	}
}

echo "<BR>\nTests done! (success)";