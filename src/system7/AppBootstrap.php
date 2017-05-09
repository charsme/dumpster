<?php
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * helpers
 */
include(SYSTEM_PATH."helpers/Global.php");

if (PHP_VERSION < 7) {
    include(SYSTEM_PATH."helpers/mongo.php");
} else {
    include(SYSTEM_PATH."helpers/mongo7.php");
}

include(SYSTEM_PATH.'vendor/autoload.php');
/**
 * Define Application Component Path
 */
if (defined('ENVIRONMENT')) {
    if (is_dir(BASE_PATH . 'config/'. ENVIRONMENT .'/')) {
        if (!defined('CONFIG_PATH')) {
            define('CONFIG_PATH', BASE_PATH . 'config/'. ENVIRONMENT .'/');
        }
    } else {
        if (!defined('CONFIG_PATH')) {
            define('CONFIG_PATH', BASE_PATH . 'config/');
        }
    }
} else {
    if (!defined('CONFIG_PATH')) {
        define('CONFIG_PATH', BASE_PATH . 'config/');
    }
}

if (!defined('CONTROLLER_PATH')) {
    define('CONTROLLER_PATH', BASE_PATH . 'controllers/');
}
if (!defined('LIBRARY_PATH')) {
    define('LIBRARY_PATH', BASE_PATH . 'libraries/');
}
if (!defined('HELPER_PATH')) {
    define('HELPER_PATH', BASE_PATH . 'helpers/');
}
if (!defined('MODEL_PATH')) {
    define('MODEL_PATH', BASE_PATH . 'models/');
}
if (!defined('VIEW_PATH')) {
    define('VIEW_PATH', BASE_PATH . 'views/');
}

/**
 * core object
 */
$CFG = & load_object('Config');
$OUT = & load_object('Output');
$RTR = & load_object('Router');


/**
 * framework
 */
// config and load eloquent
$capsule = new Capsule;

require(CONFIG_PATH.'database.php');
if (!is_array($database['default']['host'])) {
    $database['default']['host'] = array($database['default']['host']);
}
$host_slave = $database['default']['host'][array_rand($database['default']['host'])];


$capsule->addConnection(array(
    'read' => [
        'host' => $host_slave,
    ],
    'write' => [
        'host' => $database['master']['host']
    ],
    'driver'    => 'mysql',
    'database'  => $database['master']['name'],
    'username'  => $database['master']['username'],
    'password'  => $database['master']['password'],
    'charset'   => 'latin1',
    'collation' => 'latin1_swedish_ci',
    'prefix'    => $database['master']['prefix']
), 'default');
if (isset($database['default'])) {
    unset($database['default']);
}
if (isset($database['master'])) {
    unset($database['master']);
}
if (count($database) && is_array($database)) {
    foreach ($database as $key => $value) {
        $capsule->addConnection(array(
            'host' => $value['host'],
            'driver'    => 'mysql',
            'database'  => $value['name'],
            'username'  => $value['username'],
            'password'  => $value['password'],
            'charset'   => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'prefix'    => $value['prefix']
        ), $key);
    }
}
$capsule->setAsGlobal();
$capsule->bootEloquent();
if ($CFG->get('eloquent_query_log')) {
    $capsule::connection()->enableQueryLog();
    if (count($database) && is_array($database)) {
        foreach ($database as $key => $value) {
            $capsule::connection($key)->enableQueryLog();
        }
    }
}

//end eloquent load

include_once(SYSTEM_PATH."core/Eloquent.php");
include(SYSTEM_PATH."core/Controller.php");
include(BASE_PATH."core/CController.php");
