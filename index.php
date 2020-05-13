<?php
require 'library/Apps.php';
define('APPLICATION_PATH', __DIR__);
define('LIBRARY_PATH', (string) (APPLICATION_PATH . '/library'));
define('APPS_PATH', (string) (APPLICATION_PATH . '/apps'));
define('EXTENSIONS_PATH', (string) (APPLICATION_PATH . '/extensions'));
define('CACHE_DIR',(String)$_SERVER["DOCUMENT_ROOT"] . '/var/cache');
define('IMPORT_DIR',(String)$_SERVER["DOCUMENT_ROOT"] . '/var/import');
define('EXPORT_DIR',(String)$_SERVER["DOCUMENT_ROOT"] . '/var/export');
ini_set('display_errors', 1);

global $oLayout;
Apps::start()->dispatch();
