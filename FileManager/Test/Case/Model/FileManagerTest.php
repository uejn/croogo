<?php
App::uses('FileManager', 'FileManager.Model');
App::uses('CroogoTestCase', 'Croogo.TestSuite');
App::uses('Folder', 'Utility');

class FileManagerTest extends CroogoTestCase {

	public $FileManager;

	public $fixtures = array(
		'plugin.settings.setting'
	);

	private $__testAppPath;

	public function setUp(): void {
		$this->FileManager = new FileManager(false, null, null, null);
		$this->__testAppPath = CakePlugin::path('FileManager') . 'Test' . DS . 'test_app' . DS;
		$this->__setFilePathsForTests();
		parent::setUp();
	}

	public function tearDown(): void {
		unset($this->FileManager);
		parent::tearDown();
	}

/**
 * @group isEditable
 */
	public function testIsEditableShouldReturnTrueWhenPathIsWithinEditablePaths() {
		$isEditable = $this->FileManager->isEditable($this->__testAppPath . DS . 'renameMeTooPlease.txt');
		$this->assertTrue($isEditable);
	}

/**
 * @group isEditable
 */
	public function testIsEditableShouldReturnFalseWhenPathIsOutsideEditablePaths() {
		$isEditable = $this->FileManager->isEditable('/var/log/apache2');
		$this->assertFalse($isEditable);
	}


/**
 * Arma base/, base-hermana/ y afuera/ en un directorio temporal, con un archivo en cada una.
 *
 * @return string El directorio temporal, con separador al final
 */
	protected function _carpetasDePrueba() {
		$raiz = sys_get_temp_dir() . DS . 'croogo_fm_' . uniqid() . DS;
		foreach (array('base', 'base-hermana', 'afuera') as $carpeta) {
			mkdir($raiz . $carpeta, 0777, true);
			touch($raiz . $carpeta . DS . 'archivo.php');
		}
		$this->_carpetas = $raiz;
		return $raiz;
	}

	protected $_carpetas = null;

	protected function _borrarCarpetasDePrueba() {
		if ($this->_carpetas) {
			$Folder = new Folder($this->_carpetas);
			$Folder->delete();
			$this->_carpetas = null;
		}
	}

/**
 * CVE-2026-16219: el chequeo comparaba sólo el prefijo, y base-hermana/ pasaba por base/.
 *
 * @group isEditable
 */
	public function testIsEditableNoAceptaUnaCarpetaHermanaConElMismoPrefijo() {
		$raiz = $this->_carpetasDePrueba();
		Configure::write('FileManager.editablePaths', array($raiz . 'base'));
		Configure::write('FileManager.deletablePaths', array($raiz . 'base'));
		try {
			$this->assertTrue($this->FileManager->isEditable($raiz . 'base' . DS . 'archivo.php'));
			$this->assertTrue($this->FileManager->isEditable($raiz . 'base'));
			$this->assertFalse($this->FileManager->isEditable($raiz . 'base-hermana' . DS . 'archivo.php'));
			$this->assertFalse($this->FileManager->isEditable($raiz . 'base' . DS . '..' . DS . 'afuera' . DS . 'archivo.php'));
			$this->assertFalse($this->FileManager->isDeletable($raiz . 'base-hermana' . DS . 'archivo.php'));
			$this->assertFalse($this->FileManager->isDeletable($raiz . 'base' . DS . '..' . DS . 'afuera' . DS . 'archivo.php'));
		} finally {
			$this->_borrarCarpetasDePrueba();
		}
	}

/**
 * Si la carpeta permitida no existe, realpath() da false y la regex quedaba en /^/: todo pasaba.
 *
 * @group isEditable
 */
	public function testUnaCarpetaPermitidaQueNoExisteNoPermiteNada() {
		$raiz = $this->_carpetasDePrueba();
		Configure::write('FileManager.editablePaths', array($raiz . 'no-existe'));
		Configure::write('FileManager.deletablePaths', array($raiz . 'no-existe'));
		try {
			$this->assertFalse($this->FileManager->isEditable($raiz . 'afuera' . DS . 'archivo.php'));
			$this->assertFalse($this->FileManager->isDeletable($raiz . 'afuera' . DS . 'archivo.php'));
		} finally {
			$this->_borrarCarpetasDePrueba();
		}
	}

/**
 * Un archivo que no existe no está adentro de nada (realpath() da false).
 *
 * @group isEditable
 */
	public function testUnArchivoQueNoExisteNoEsEditable() {
		$raiz = $this->_carpetasDePrueba();
		Configure::write('FileManager.editablePaths', array($raiz . 'base'));
		try {
			$this->assertFalse($this->FileManager->isEditable($raiz . 'base' . DS . 'no-existe.php'));
		} finally {
			$this->_borrarCarpetasDePrueba();
		}
	}

/**
 * @group rename
 */
	public function testRenameShouldReturnedTrueOnSuccess() {
		$oldPath = $this->__testAppPath . DS . 'renameMe';
		$newPath = $this->__testAppPath . DS . 'renamed';

		$this->assertTrue($this->FileManager->rename($oldPath, $newPath));
		$this->FileManager->rename($newPath, $oldPath);
	}

/**
 * @group rename
 */
	public function testRenameShouldRenamedOldFileToNewFile() {
		$oldPath = $this->__testAppPath . DS . 'renameMeTooPlease.txt';
		$newPath = $this->__testAppPath . DS . 'renamed.txt';

		$this->FileManager->rename($oldPath, $newPath);
		$this->assertTrue(file_exists($newPath) && !file_exists($oldPath));

		$this->FileManager->rename($newPath, $oldPath);
	}

/**
 * @group rename
 */
	public function testRenameShouldRenamedOldFolderToNewFolder() {
		$oldPath = $this->__testAppPath . 'renameMe';
		$newPath = $this->__testAppPath . 'renamed';

		$this->FileManager->rename($oldPath, $newPath);
		$this->assertTrue(is_dir($newPath) && !is_dir($oldPath));

		$this->FileManager->rename($newPath, $oldPath);
	}

/**
 * Convenient methods for testsuite
 */
	private function __setFilePathsForTests() {
		Configure::write('FileManager.editablePaths', array($this->__testAppPath));
		Configure::write('FileManager.deletablePaths', array($this->__testAppPath));
	}

}
