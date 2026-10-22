<?php

class AllUsersTestsTest extends PHPUnit\Framework\TestSuite {

	public static function suite() {
		$suite = new CakeTestSuite('All Users plugin tests');
		$path = CakePlugin::path('Users') . DS . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectory($path . 'Controller');
		$suite->addTestDirectory($path . 'Model');
		return $suite;
	}

}
