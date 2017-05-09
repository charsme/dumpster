<?php
/**
 * Config Class
 */
class Config
{
    private static $instance;
    private $configuration = array();

    public function __construct()
    {
        if (!self::$instance) {
            $this->initialize();
            self::$instance = & $this;
        }
    }

    public static function &App()
    {
        if (!self::$instance) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    private function initialize()
    {
        include_once(CONFIG_PATH . "config.php");
        include_once(CONFIG_PATH . "ftp.php");
        include_once(CONFIG_PATH . "url_dir.php");
        if (is_file(CONFIG_PATH . "cache.php")) {
            include(CONFIG_PATH . "cache.php");
            $config['cache'] = $cache;
        }

        $this->configuration = &$config;
        if ($this->get('base_url') == '') {
            if (isset($_SERVER['HTTP_HOST'])) {
                $base_url = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
                $base_url .= '://' . $_SERVER['HTTP_HOST'];
                $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
            } else {
                $base_url = 'http://localhost/';
            }

            $this->set('base_url', $base_url);
        }
    }

    public function load($name = "")
    {
        if (is_file(CONFIG_PATH . $name . ".php")) {
            include_once(CONFIG_PATH . $name . ".php");

            $this->configuration = array_merge($this->configuration, $config);
        }
    }

    public function get($name = "all", $subname = null, $subname2 = null, $subname3 = null, $subname4 = null)
    {
        if ($name == "all" || $name == "") {
            return $this->configuration;
        } else {
            if ($subname == null) {
                return isset($this->configuration[$name]) ? $this->configuration[$name] : false;
            } else {
                if ($subname2 == null) {
                    return isset($this->configuration[$name][$subname]) ? $this->configuration[$name][$subname] : false;
                } else {
                    if ($subname3 == null) {
                        return isset($this->configuration[$name][$subname][$subname2]) ? $this->configuration[$name][$subname][$subname2] : false;
                    } else {
                        if ($subname4 == null) {
                            return isset($this->configuration[$name][$subname][$subname2][$subname3]) ? $this->configuration[$name][$subname][$subname2][$subname3] : false;
                        } else {
                            return isset($this->configuration[$name][$subname][$subname2][$subname3][$subname4]) ? $this->configuration[$name][$subname][$subname2][$subname3][$subname4] : false;
                        }
                    }
                }
            }
        }
    }

    public function set($name = "", $value = "")
    {
        $this->configuration[$name] = $value;
    }
}
