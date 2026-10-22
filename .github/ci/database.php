<?php
class DATABASE_CONFIG {
	public $default = array(
		'datasource' => 'Database/Mysql', 'persistent' => false, 'host' => '127.0.0.1',
		'login' => 'root', 'password' => 'root', 'database' => 'croogo_test', 'encoding' => 'utf8', 'prefix' => '',
	);
	public $test = array(
		'datasource' => 'Database/Mysql', 'persistent' => false, 'host' => '127.0.0.1',
		'login' => 'root', 'password' => 'root', 'database' => 'croogo_test', 'encoding' => 'utf8', 'prefix' => '',
	);
	public function __construct() {
		foreach (array('host' => 'CROOGO_DB_HOST', 'database' => 'CROOGO_DB') as $clave => $variable) {
			if (getenv($variable)) {
				$this->default[$clave] = $this->test[$clave] = getenv($variable);
			}
		}
	}
}
