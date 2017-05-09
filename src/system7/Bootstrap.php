<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$_time_exec_start_ = microtime(true);
/**
 * System Core Component
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

/*
 * ---------------------------------------------------------------------------
 * FRAMEWORK IS ON
 * ---------------------------------------------------------------------------
 */
$CFG = & load_object('Config');
$OUT = & load_object('Output');

if ($CFG->get('is_maintenance')) {
    $OUT->show_maintenance('Server Maintenance');
}

$CacheHook = & load_hooks('CacheHooks');

$CacheHook->pre_cache_serving($uri);

$OUT->output_cache();

$CacheHook->post_cache_serving($uri);
/*
 * ---------------------------------------------------------------------------
 * THE END OF THE PROCESS,  IF THE REQUESTED PAGE IS HANDLED BY OUTPUT CACHE
 * ---------------------------------------------------------------------------
 */
/*** ====================================================================== ***/
$RTR = & load_object('Router');
$RTR->set_routing("/" . (isset($_GET["q"])?$_GET["q"]:""));
// phpinfo();
// var_dump($RTR);die();
$classname = $RTR->classname;
$method_name = $RTR->methodname;
$params = $RTR->params;
$class_dir = $RTR->classdir;
$class_filename = $classname;

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
if (file_exists(BASE_PATH . "/controllers/" . $class_dir . $class_filename . ".php")) {
    include(SYSTEM_PATH."core/Controller.php");
    include(BASE_PATH."core/CController.php");
    include(BASE_PATH . "/controllers/" . $class_dir . $class_filename . ".php");
} else {
    $class_filename = strtolower($class_filename);
    if (file_exists(BASE_PATH . "/controllers/" . $class_dir . $class_filename . ".php")) {
        include(SYSTEM_PATH."core/Controller.php");
        include(BASE_PATH."core/CController.php");
        include(BASE_PATH . "/controllers/" . $class_dir . $class_filename . ".php");
    }
}

$ControllerHooks = & load_hooks('ControllerHooks');
if (class_exists($classname)) {
    $c = new $classname();

    if (method_exists($c, $method_name)) {
        foreach ($params as $key=>$value) {
            $params[$key] = $value;
        }

        $ControllerHooks->pre_instantiation($c, $method_name);

        call_user_func_array(array($c, $method_name), $params);

        $ControllerHooks->post_instantiation($c, $method_name);
    } else {
        $OUT->show_404("Method $method_name in Class $classname doesn't exists");
    }
} else {
    $OUT->show_404("Controller $classname doesn't exists");
}


/*
 * ---------------------------------------------------------------------------
 * CLOSE EVERY DATABASE CONNECTION
 * ---------------------------------------------------------------------------
 */
if (class_exists('Database')) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    } else {
        $OUT->show_dblogs(Database::get_logs());
    }

    Database::close();
}
// var_dump(mysql_query("select * FROM newshubid_users LIMIT 0,1"));
// echo "<br/><br/>";
$_time_exec_end_ = microtime(true);
if ($CFG->get('debug_exec_time')) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    } else {
        $OUT->show_exec_time($_time_exec_start_, $_time_exec_end_);
    }
}
