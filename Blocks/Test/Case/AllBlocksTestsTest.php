<?php
App::uses('CroogoTestCase', 'Croogo.TestSuite');

class AllBlocksTestsTest extends PHPUnit\Framework\TestSuite {

/**
 * suite
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Blocks tests');
		$suite->addTestDirectoryRecursive(CakePlugin::path('Blocks') . 'Test' . DS . 'Case' . DS);
		return $suite;
	}

}
