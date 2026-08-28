<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('CORE_FOLDER')) {
    define('CORE_FOLDER', 'coremio');
}

$moduleDir = dirname(__DIR__) . '/coremio/modules/Servers/DNAHosting/';

require_once __DIR__ . '/support/FakeTransport.php';
require_once $moduleDir . 'lib/Exception.php';
