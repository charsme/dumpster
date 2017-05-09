<?php

class Output
{
    private static $instance;
    private $config;
    private $webcache;

    public function __construct()
    {
        /*
        if (is_file(CONFIG_PATH . "view.php"))
        {
            include(CONFIG_PATH . "view.php");

            $this->config = & $view;
        }
        */
        $CFG = & load_object('Config');
        $this->config = $CFG->get();

        $this->webcache = & load_object('WebCache');

        self::$instance = & $this;
    }

    public static function &App()
    {
        if (!self::$instance) {
            self::$instance = new Output();
        }
        return self::$instance;
    }

    public function output_cache()
    {
        $this->webcache->serve_page();
    }

    public function show_exec_time($time_start, $time_end)
    {
        $execution_time = $time_end - $time_start; //seconds
        echo $this->view("_system_/debug_exec_time", array('start' => $time_start, 'end' => $time_end, 'exec_time' => $execution_time), true);
    }

    public function closeDB()
    {
        if (class_exists('Database')) {
            Database::close();
        }
    }

    public function show_dblogs($logs = array())
    {
        if (!is_array($logs)) {
            return '';
        }

        $_log_html = '';
        foreach ($logs as $name => $log) {
            if (!$name || !$log) {
                continue;
            }

            $_log_html .=  $this->view("_system_/dblog_header", array('name' => $name), true);

            if (is_array($log)) {
                foreach ($log as $item) {
                    $callers = $item[3];
                    $callers_item = '&nbsp;';
                    foreach ($callers as $c) {
                        $classname = isset($c['class']) ? $c['class'] : '';
                        $functionname = isset($c['function']) ? $c['function'] : '';
                        if ($classname && $classname != 'sql_db' && $classname != 'Model') {
                            $callers_item .= ' || '. $classname .' -> '. $functionname;
                        }
                    }

                    $_log_html .=  $this->view("_system_/dblog_item", array('query' => $item[0], 'start' => $item[1], 'end' => $item[2], 'total' => $item[2] - $item[1], 'caller' => $callers_item), true);
                }
            }
        }
        echo $_log_html;
    }

    public function redirect($url = "")
    {
        //$url = rtrim($url, '/');
        $url = empty($url) ? '/' : $url;
        @header('Location: ' . $url);
        $this->closeDB();
        exit();
    }

    public function moved($url = "")
    {
        $url = empty($url) ? '/' : $url;

        @header("301 Moved Permanently HTTP/1.1", true, 301);
        @header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        @header('Location: ' . $url);
        $this->closeDB();
        exit();
    }

    public function show_404($text = "")
    {
        @header("HTTP/1.0 404 Not Found");
        echo $this->view("_system_/404", array('message' => $text), true);
        $this->closeDB();
        exit();
    }

    public function show_error($text = "")
    {
        @header('HTTP/1.1 500 Internal Server Error');
        echo $this->view("_system_/error", array('message' => $text), true);
        $this->closeDB();
        exit();
    }

    public function show_maintenance($text = "")
    {
        echo $this->view("_system_/maintenance", array('message' => $text), true);
        $this->closeDB();
        exit();
    }

    public function view($viewname = "", $data = array(), $return = false, $is_cache = false)
    {
        // twig template engine
        $path = VIEW_PATH.$this->config['theme'] . "/" . $viewname;
        $filename = $this->config['theme'] . "/" . $viewname;
        if (stripos($viewname, '.twig') !== false && is_file($path)) {
            $paths = array(VIEW_PATH . $this->config['theme']);
            $loader = new Twig_Loader_Filesystem($paths);
            if (ENVIRONMENT == 'development') {
                $twig = new Twig_Environment($loader, array('debug'    => true));
                $twig->addExtension(new Twig_Extension_Debug());
            } else {
                $twig = new Twig_Environment($loader);
            }
            
            $buffer = $twig->render($viewname, $data);
        } elseif (is_file($path.".phtml")) {
            ob_start();
            extract($data);
            require($path . ".phtml");
            $buffer = ob_get_contents();
            ob_end_clean();
        } elseif (is_file($path . ".html")) {
            $filename = $path . ".html";
            $data_buffer = array_merge($this->config, $data);
            $buffer = file_get_contents($filename);
            $this->_view_process($data_buffer, $buffer);
        } else {
            $this->show_404("This View : <strong>" . $filename . "</strong> doesn't exists in server");
        }


        /* Blade winwalker template engine
        $filename = $this->config['theme'] . "/" . $viewname;
        $paths = array(VIEW_PATH . $this->config['theme']);
        $view = $viewname;
        $renderer = new BladeRenderer($paths, array('cache_type' => $this->config['blade']['cache'], 'cache_path' => $this->config['blade']['location']));
        try
        {
                $buffer = $renderer->render($view, $data);
        } catch (Exception $e)
        {
            if (is_file(VIEW_PATH . $filename . ".html"))
            {
                $filename = VIEW_PATH . $filename . ".html";
            }
            else
            {
                $this->show_404("This View : <strong>" . $filename . "</strong> doesn't exists in server");
            }

            $data_buffer = array_merge($this->config, $data);
            $buffer = file_get_contents($filename);
            $this->_view_process($data_buffer, $buffer);
        }*/

        if ($return) {
            return $buffer;
        } else {
            $this->do_output($buffer, false, $is_cache);
        }
    }

    public function do_output($content = "", $is_plain = false, $is_cache = true)
    {
        $OutputHooks = & load_hooks('OutputHooks');

        $OutputHooks->pre_output($content);

        if ($is_cache) {
            $this->save($content);
        }

        echo $content;
    }

    private function save(&$content)
    {
        $this->webcache->save_page($content);
    }

    private function _view_process($vars = array(), & $view)
    {
        foreach ($vars as $k => $v) {
            if (is_array($v)) {
                $this->_view_process($v, $view);
            }
            if (is_string($v) || is_numeric($v)) {
                $view = str_replace('{' . strtoupper($k) . '}', $v, $view);
            }
        }
    }
}
