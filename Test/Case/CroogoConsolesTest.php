<?php
App::uses('CroogoTestCase', 'Croogo.TestSuite');

class CroogoConsolesTests extends PHPUnit\Framework\TestSuite {

	public static function suite() {
		$suite = new CakeTestSuite('Croogo commands tests');
		$path = APP . 'Vendor' . DS . 'croogo' . DS . 'croogo' . DS . 'Croogo' . DS . 'Test' . DS . 'Case' . DS . 'Console' . DS . 'Command' . DS;
		$suite->addTestDirectory($path);
		return $suite;
	}

}
