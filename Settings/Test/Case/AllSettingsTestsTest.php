<?php
App::uses('CroogoTestCase', 'Croogo.TestSuite');

class AllSettingsTestsTest extends PHPUnit\Framework\TestSuite {

/**
 * suite
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Settings tests');
		$suite->addTestDirectoryRecursive(CakePlugin::path('Settings') . 'Test' . DS . 'Case' . DS);
		return $suite;
	}

}
