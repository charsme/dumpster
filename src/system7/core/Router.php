<?php

class Router
{
    private static $instance;

    private static $uri_full;
    private $routes = [];

    public $uri_string;
    public $uri_string_routed;
    public $classname;
    public $methodname;
    public $params;
    public $classdir;

    public function __construct()
    {
        self::$instance = &$this;
    }

    public static function &App()
    {
        if (!self::$instance) {
            self::$instance = new Router();
        }

        return self::$instance;
    }

    public function group($domain, $routes)
    {
        $routes = $routes();
        if ($domain && is_array($routes)) {
            $this->routes[$domain] = $routes;
        }
    }

    public function set_routing($real_uri = '')
    {
        $this->uri_string = $real_uri;

        $config = [];
        foreach (array('routes', 'redirects') as $name) {
            if (is_file(CONFIG_PATH . $name . ".php")) {
                $router = &load_object('Router');
                include(CONFIG_PATH . $name . ".php");

                if (!isset($config[$name]) || !is_array($config[$name])) {
                    $config[$name] = array();
                }
                if (isset($$name)) {
                    $config[$name] = array_merge($config[$name], $$name);

                    unset($$name);
                }
            }
        }
        $config['routes'] = array_merge($this->routes, $config['routes']);
        $RouterHook = &load_hooks('RouterHooks');

        $RouterHook->pre_routes($real_uri, $real_uri);

        $this->uri_string_routed = $this->uri_string;
        $pattern_found           = false;
        $current_domain          = $_SERVER['HTTP_HOST'];
        foreach ($config['routes'] as $pattern => $target) {
            if ($pattern_found) {
                break;
            }

            if (is_array($target) && $current_domain == $pattern) {
                foreach ($target as $domain_pattern => $domain_target) {
                    if ($pattern_found) {
                        break;
                    }

                    $pattern_found = $this->_processPattern($domain_pattern, $domain_target);
                }
            } else {
                $pattern_found = $this->_processPattern($pattern, $target);
            }
        }
        $RouterHook->post_routes($this->uri_string_routed, $real_uri);

        $uri_array_routed = explode("/", trim($this->normalize($this->uri_string_routed), "/"));

        $dir_route = isset($uri_array_routed[0]) ? $uri_array_routed[0] : '';
        if (is_dir(CONTROLLER_PATH . $dir_route)) {
            $controller_dir = $dir_route . '/';
            array_shift($uri_array_routed);
        } else {
            $controller_dir = '';
        }
        $this->classname  = ucwords((isset($uri_array_routed[0]) && $uri_array_routed[0]) ? $uri_array_routed[0] : "index");
        $this->methodname = (isset($uri_array_routed[1]) && $uri_array_routed[1]) ? $uri_array_routed[1] : "index";
        $this->params     = array_slice($uri_array_routed, 2);
        $this->classdir   = $controller_dir;
    }

    public function _processPattern($pattern, $target)
    {
        $pattern = '#'.$pattern.'#';
        if (preg_match($pattern, $this->uri_string)) {
            $this->uri_string_routed = preg_replace($pattern, $target, $this->uri_string);
            return true;
        }
        return false;
    }

    public function normalize($uri = "")
    {
        $uri = strip_tags(preg_replace("#[/]{2,}#", "/", $uri));

        $non_displayables[] = '/%0[0-8bcef]/'; // url encoded 00-08, 11, 12, 14, 15
        $non_displayables[] = '/%1[0-9a-f]/'; // url encoded 16-31
        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $uri = preg_replace($non_displayables, '', $uri, -1, $count);
        } while ($count);

        return $uri;
    }

    public function full_uri()
    {
        if (self::$uri_full) {
            return self::$uri_full;
        }

        self::$uri_full = Config::App()->get('base_url') . $this->_request_uri();

        return self::$uri_full;
    }

    private function _request_uri()
    {
        if (!isset($_SERVER['REQUEST_URI']) or !isset($_SERVER['SCRIPT_NAME'])) {
            return '';
        }

        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
            $uri = substr($uri, strlen($_SERVER['SCRIPT_NAME']));
        } elseif (strpos($uri, dirname($_SERVER['SCRIPT_NAME'])) === 0) {
            $uri = substr($uri, strlen(dirname($_SERVER['SCRIPT_NAME'])));
        }

        if (strncmp($uri, '?/', 2) === 0) {
            $uri = substr($uri, 2);
        }
        $parts = preg_split('#\?#i', $uri, 2);
        $uri   = $parts[0];
        if (isset($parts[1])) {
            $_SERVER['QUERY_STRING'] = $parts[1];
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        } else {
            // we still support this
            //$_SERVER['QUERY_STRING'] = '';
            //$_GET = array();
        }

        if ($uri == '/' || empty($uri)) {
            return '/';
        }

        $uri = parse_url($uri, PHP_URL_PATH);

        return $this->normalize(str_replace(array('//', '../'), '/', trim($uri, '/')));
    }

    public function get($key = '', $default = '', $clearxss = false)
    {
        if ($clearxss) {
            return isset($_GET[$key]) ? $this->normalize($_GET[$key]) : $default;
        } else {
            return isset($_GET[$key]) ? $_GET[$key] : $default;
        }
    }

    public function post($key = '', $default = '', $clearxss = false)
    {
        if ($clearxss) {
            return isset($_POST[$key]) ? $this->normalize($_POST[$key]) : $default;
        } else {
            return isset($_POST[$key]) ? $_POST[$key] : $default;
        }
    }

    public function request($key = '', $default = '', $clearxss = false)
    {
        if ($clearxss) {
            return isset($_REQUEST[$key]) ? $this->normalize($_REQUEST[$key]) : $default;
        } else {
            return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        }
    }
}
