<?php
// Arranque de PHPUnit 9 para los tests de Croogo (el Console/cake test espera PHPUnit 3.7).
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
$appDir = dirname(__DIR__);
define('TESTS', $appDir . DS . 'Vendor' . DS . 'croogo' . DS . 'croogo' . DS . 'Test' . DS);
ini_set('include_path',
	$appDir . DS . 'Vendor' . DS . 'cakephp' . DS . 'cakephp' . DS . 'lib' . PATH_SEPARATOR .
	ini_get('include_path')
);
require_once 'Cake' . DS . 'Console' . DS . 'ShellDispatcher.php';
new ShellDispatcher(array('phpunit', '-working', $appDir));
unset($appDir);

App::uses('CakeTestSuiteDispatcher', 'TestSuite');
App::load('CakeTestSuiteDispatcher');
App::uses('ClassRegistry', 'Utility');
App::uses('CakeTestCase', 'TestSuite');
App::uses('CakeTestSuite', 'TestSuite');
App::uses('ControllerTestCase', 'TestSuite');
App::uses('CakeTestModel', 'TestSuite/Fixture');
App::uses('CakeFixtureManager', 'TestSuite/Fixture');
App::uses('CakeFixtureInjector', 'TestSuite/Fixture');
App::uses('CroogoTestCase', 'Croogo.TestSuite');
App::uses('CroogoControllerTestCase', 'Croogo.TestSuite');

// Los fixtures plugin.translate.* necesitan el plugin cargado; su bootstrap no
// (engancha Translate a los modelos y pide la tabla i18n en todos los tests).
if (!CakePlugin::loaded('Translate')) {
	CakePlugin::load('Translate');
}

// InstallTest necesita el plugin; sus rutas no (mandan todo a /install).
if (!CakePlugin::loaded('Install')) {
	CakePlugin::load('Install');
}
