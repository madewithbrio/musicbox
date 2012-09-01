<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '128M');
define('_BOOTSTRAP', true);

define('ENV', 'local'); //local, development, staging, production

define('_PROJECT', 'musicbox');

define('_PROJECT_LOCATION', dirname(__FILE__));
define('_PROJECT_LIB', _PROJECT_LOCATION."/lib");
define('_PROJECT_CONFIG' , _PROJECT_LOCATION."/config");
define('CDN', 'http://' . $_SERVER['HTTP_HOST'] . '/static');

define('_TMP_LOCATION', "/var/tmp/"._PROJECT);
define('_TMP', _TMP_LOCATION);
define('_TMP_LOG', _TMP_LOCATION . "/application.log");

if (ENV == 'local') {
  include(_PROJECT_LOCATION.'/tools/prepare_env.php');
}

ini_set('include_path', _PROJECT_LIB); // . PATH_SEPARATOR . _SMARTY_DIR);
require_once 'Sapo/Autoloader.php';
Sapo_Autoloader::init();

// load functions
require_once 'functions.php';
setlocale(LC_ALL, 'pt_PT', 'pt_PT.UTF-8', 'portuguese');
date_default_timezone_set('Europe/Lisbon');

// init context
Sapo_Context::init();
