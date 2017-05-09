<?php

class Database
{
    private static $instance;
    private static $db_connection = array();
    private static $db_server = array();
    public static $config = array();

    public function __construct()
    {
        parent::__construct();
    }

    public static function get()
    {
        return self::$db_connection;
    }

    public static function get_logs()
    {
        $logs = array();
        if (is_array(self::$db_connection)) {
            foreach (self::$db_connection as $name => $connection) {
                if ($connection) {
                    $logs[$name] = $connection->get_query_logs();
                }
            }
        }
        return $logs;
    }

    public static function connect($db_name = "default")
    {
        if (!isset(self::$db_connection[$db_name])) {
            if (is_file(CONFIG_PATH . "database.php")) {
                include(CONFIG_PATH . "database.php");

                $db_config = & $database;
            }

            self::$db_server[$db_name] = $db_config[$db_name];
            $_db_debug_ = $db_config[$db_name]["debug"];
            self::$config = $db_config[$db_name];

            if (is_array(self::$db_server[$db_name]["host"])) {
                $key = array_rand(self::$db_server[$db_name]["host"]);
                self::$db_server[$db_name]["host"] = self::$db_server[$db_name]["host"][$key];
            }

            self::$db_connection[$db_name] = new sql_wrapper(self::$db_server[$db_name]["host"], self::$db_server[$db_name]["username"], self::$db_server[$db_name]["password"], self::$db_server[$db_name]["name"], $_db_debug_);
            return self::$db_connection[$db_name];
        } else {
            return self::$db_connection[$db_name];
        }
    }

    public static function close()
    {
        if (is_array(self::$db_connection)) {
            foreach (self::$db_connection as $name => $connection) {
                if ($connection) {
                    $connection->sql_freeresult();
                    $connection->sql_close();
                }
                unset(self::$db_connection[$name]);
            }
        }
    }

    public function __destruct()
    {
        self::close();
    }
}
