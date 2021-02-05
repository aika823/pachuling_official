<?php

// 에러 확인 코드
error_reporting(E_ALL);
ini_set("display_errors", 1);


define('_ROOT',dirname(__FILE__)."/");
define('_APP',_ROOT."application/");
define('_PUBLIC',_ROOT."public/");
define('_MODEL',_APP."model/");
define('_CONFIG',_APP."config/");
define('_CONTROLLER',_APP."controller/");
define('_VIEW',_APP."view/");
define('_JS',_PUBLIC."js/");

define('_URL',str_replace("index.php","","http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}"));
define('_IMG',_URL.'public/img/');
define('_CSS',_URL.'public/css/');

require_once(_CONFIG."lib.php");
//require_once(_CONFIG."db.php");
require_once(_ROOT."config.php");

//앱 실행(/application/application.php)
new Application();