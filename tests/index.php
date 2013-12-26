<?php

@ini_set('display_errors', 'on');

/**
* Test PHPFunctionCallParser
*/

require_once dirname(__FILE__).'/../classes/PHPFunctionCallParser.php';
require_once dirname(__FILE__).'/../classes/SmartyFunctionCallParser.php';

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
	),
	array(
		'pattern' => '\$this->l',
		'string' => '$this->l("bo\"b", (1 ? 2 : "3,)"), (function($bob){})("4),4")',
		'expected' => array()
	),
	array(
		'pattern' => 'Tools::displayError',
		'string' => 'Tools::displayError($this->l(\'You do not have permission to edit this.\'));',
		'expected' => array(
			array(
				'function' => 'Tools::displayError',
				'arguments' => array('$this->l(\'You do not have permission to edit this.\')')
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

$fixtures = array(

	array(
		'function_name' => 'l',
		'string' => '{l s="hello"}',
		'expected' => array(
			array(
				's' => '"hello"'
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s="hello" mod=\'hi\'}',
		'expected' => array(
			array(
				's' => '"hello"',
				'mod' => "'hi'"
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=hello mod=\'hi\'}',
		'expected' => array(
			array(
				's' => 'hello',
				'mod' => "'hi'"
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=hello      mod=[hi]  }',
		'expected' => array(
			array(
				's' => 'hello',
				'mod' => "[hi]"
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=hello      mod=["hi"]  }',
		'expected' => array(
			array(
				's' => 'hello',
				'mod' => '["hi"]'
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=hello      mod=["h]i"]  }',
		'expected' => array(
			array(
				's' => 'hello',
				'mod' => '["h]i"]'
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=hello   mod=["h]i}"]  }',
		'expected' => array(
			array(
				's' => 'hello',
				'mod' => '["h]i}"]'
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=\'Showing %1$d - %2$d of 1 item\' sprintf=[$productShowingStart, $productShowing]}',
		'expected' => array(
			array(
				's' => '\'Showing %1$d - %2$d of 1 item\'',
				'sprintf' => '[$productShowingStart, $productShowing]'
			)
		)
	),

	array(
		'function_name' => 'l',
		'string' => '{l s=hello   mod="h=plop"  }',
		'expected' => array(
			array(
				's' => 'hello',
				'mod' => '"h=plop"'
			)
		)
	)
);

foreach ($fixtures as $fixture)
{
	$parser = new SmartyFunctionCallParser($fixture['string'], $fixture['function_name']);

	$result = $parser->parse();

	if($result !== $fixture['expected'])
	{
		echo "Not good!<BR/>";

		echo "<p>Expected:<BR/><pre>";
		print_r($fixture['expected']);
		echo "</pre></p><p>But got:<BR/><pre>";
		print_r($result);
		echo "</pre>";
		echo "<p>Parser State:</p>";
		echo "<pre>";
		print_r($parser->getStates());
		echo "</pre>";
		die();
	}
}



echo "<BR>\nTests done! (success)";