<?php
App::uses('CroogoTestCase', 'Croogo.TestSuite');

class AllNodesTestsTest extends PHPUnit\Framework\TestSuite {

/**
 * suite
 *
 * @return CakeTestSuite
 */
	public static function suite() {
		$suite = new CakeTestSuite('All Nodes tests');
		$suite->addTestDirectoryRecursive(CakePlugin::path('Nodes') . 'Test' . DS . 'Case' . DS);
		return $suite;
	}

}
