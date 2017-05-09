<?php

class CronScheduler
{
    private $_method = '';
    private $_pid;
    private $_controller_path;
    private $_overlapping_check = true;
    private $_time;
    private $_domain;
    private $_config;
    private $_pid_file;
    private $_overlap_file;
    private $_execute_file;

    public function initialize()
    {
        $this->_config          = Config::App()->get();
        $this->_time            = time();
        $this->_domain          = 'newshub.id';//(isset($_SERVER["HTTP_HOST"]) && $_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : ( (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : ((!defined('DOMAIN_IDENTITY')) ? 'newshub.id' : DOMAIN_IDENTITY));
        $this->_pid             = strtoupper($this->_domain);
        $this->_controller_path = CONTROLLER_PATH;
        $this->_pid_file        = $this->_config['klimg_dir'].'cron/running_pid/'.$this->_pid.'.txt';
        $this->_overlap_file    = $this->_config['klimg_dir'].'cron/overlap/'.date('Ymd').'.txt';
        $this->_execute_file    = $this->_config['klimg_dir'].'cron/execute/'.date('Ymd').'.txt';
        if ($this->_overlapping_check) {
            $check_mongo = $this->_checkPID();
            if ($check_mongo && (time() - $check_mongo['time']) <= 3600) {
                $this->_overlap();
                exit();
            }
            if ($check_mongo) {
                $this->_deletePID();
            }
            $this->_insertPID();
        }
        return $this;
    }

    public function destroy()
    {
        if ($this->_overlapping_check) {
            $this->_deletePID();
        }
        return $this;
    }

    public function set_controller_path($path)
    {
        $this->_controller_path = $path;
        return $this;
    }

    /**
     * execute schedule at specified time,
     * ex :
     * // execute method clear_cache every minute at 30 and 10:20
     * $scheduler->atTime("clear_cache", array('*:30', '10:20'));
     *
     * @param  string $method method name in controller Cron
     * @param  mixed  time as array
     * @return object
     */
    public function atTime($method, $time)
    {
        if (!is_array($time)) {
            $time = array($time);
        }
        foreach ($time as $k => $v) {
            list($hour, $minute) = explode(':', $v);
            $exec = false;
            if (($hour == '*' && date('i', $this->_time) == $minute) || (date('H:i', $this->_time) == $v)) {
                $this->_method = $method;
                $this->_execute();
                break;
            }
        }
        return $this;
    }

    /**
     * execute schedule every N minute,
     * ex :
     * // execute method clear_cache every 30 minutes
     * $scheduler->everyMinute("clear_cache", 30);
     *
     * @param  string $method method name in controller Cron
     * @param  int    $minute interval execute in minute
     * @return object
     */
    public function everyMinute($method, $minute)
    {
        $total_minute = intval(date('G', $this->_time)) * 60 + intval(date('i', $this->_time));
        if ($total_minute % $minute == 0) {
            $this->_method = $method;
            $this->_execute();
        }
        return $this;
    }

    /**
     * execute schedule every N hour,
     * ex :
     * // execute method clear_cache every 3 hours
     * $scheduler->everyHour("clear_cache", 3);
     *
     * @param  string $method method name in controller Cron
     * @param  int    $hour   interval execute in hour
     * @return object
     */
    public function everyHour($method, $hour)
    {
        if (intval(date('G', $this->_time)) % $hour == 0 && date('i', $this->_time) == '00') {
            $this->_method = $method;
            $this->_execute();
        }
        return $this;
    }

    /**
     * execute schedule every specified day of week and it's time,
     * ex :
     * // execute method clear_cache every sunday and thursday at 5 pm
     * $scheduler->everyDayandTime("clear_cache", array(1, 4), '05:00');
     *
     * @param  string $method method name in controller Cron
     * @param  mixed  array of day in week in number 1 = Monday until 7 = Sunday
     * @param  string time in 24 hour format
     * @return object
     */
    public function everyDayandTime($method, $day, $time)
    {
        $day = (is_array($day)) ? $day : explode(',', $day);
        foreach ($day as $key => $value) {
            $day[$key] = trim($value);
        }
        if (in_array(date('N', $this->_time), $day) && date('H:i', $this->_time) == $time) {
            $this->_method = $method;
            $this->_execute();
        }
        return $this;
    }

    private $_obj = [];

    /**
     * execute cron queue
     * @return void
     */
    private function _execute()
    {
        $check_mongo = $this->_checkPID();
        if ($check_mongo) {
            $this->_updatePID();
        }

        if (stripos($this->_method, '/') > 0) {
            $tmp = explode('/', $this->_method);
            $this->_method = end($tmp);
            unset($tmp[count($tmp) - 1]);
            $controller = end($tmp);
            $controllerPath = implode('/', $tmp);
        } else {
            $controller = $controllerPath = 'Cron';
        }

        $file = (is_file($this->_controller_path . $controllerPath . ".php")) ? $this->_controller_path . $controllerPath . ".php" : $this->_controller_path . strtolower($controllerPath) . ".php" ;
        include_once($file);

        if (!isset($this->_obj[$controller])) {
            $this->_obj[$controller] = new $controller();
        }
        if (method_exists($this->_obj[$controller], $this->_method)) {
            $starttime = microtime(true);

            ob_start();
            call_user_func(array( $this->_obj[$controller], $this->_method ));
            $res = ob_get_contents();
            ob_end_clean();

            $endtime = microtime(true);
            $duration = $endtime - $starttime;
            $this->_executed(array(
                'starttime' => date('Y-m-d H:i:s', $starttime),
                'endtime'   => date('Y-m-d H:i:s', $endtime),
                'duration'  => $duration,
                'pid'       => $this->_pid,
                'time'      => $this->_time,
                'method'    => $controller.'/'.$this->_method,
                'domain'    => $this->_domain,
                'result'    => $res
            ));
        }
        return $this;
    }

    private function _executed($data)
    {
        if (!empty($this->_config['cron_log']) && $this->_config['cron_log'] == 'file') {
            unset($data['result']);
            $content = implode(' | ', $data);
            $this->_write($this->_execute_file, $content."\n");
        } else {
            insertDataMongo(
                'cron_execution_log',
                $data
            );
        }
    }

    private function _overlap()
    {
        if (!empty($this->_config['cron_log']) && $this->_config['cron_log'] == 'file') {
            $this->_write($this->_overlap_file, time().' | '.$this->_pid."\n");
        } else {
            insertDataMongo('cron_overlapping', array('datetime' => date('Y-m-d H:i:s'), 'time' => time(), 'PID' => $this->_pid));
        }
    }

    private function _updatePID()
    {
        if (!empty($this->_config['cron_log']) && $this->_config['cron_log'] == 'file') {
            $this->_write($this->_pid_file, $this->_method.' '.date('Y-m-d H:i:s')."\n");
        } else {
            updateDataMongo('cron_running_pid', ['execute' => $this->_method, 'pid' => $this->_pid], array('pid' => $this->_pid));
        }
    }

    private function _deletePID()
    {
        if (!empty($this->_config['cron_log']) && $this->_config['cron_log'] == 'file') {
            @unlink($this->_pid_file);
        } else {
            deleteDataMongo(array('pid' => $this->_pid), 'cron_running_pid');
        }
    }

    private function _insertPID()
    {
        if (!empty($this->_config['cron_log']) && $this->_config['cron_log'] == 'file') {
            $this->_write($this->_pid_file, time()."\n");
        } else {
            insertDataMongo('cron_running_pid', array('pid' => $this->_pid, 'time' => time()));
        }
    }

    private function _checkPID()
    {
        if (!empty($this->_config['cron_log']) && $this->_config['cron_log'] == 'file') {
            if (is_file($this->_pid_file)) {
                $fp      = fopen($this->_pid_file, 'r');
                $content = fread($fp, filesize($this->_pid_file));
                fclose($fp);
                $tmp     = explode("\n", $content);
                return ['time' => $tmp[0]];
            }
            return false;
        } else {
            return getOneDataMongo(array('pid' => $this->_pid), 'cron_running_pid');
        }
    }

    private function _write($path, $content)
    {
        $filename = basename($path);
        $dir      = substr($path, 0, (strlen($path) - strlen($filename)));
        if (!is_dir($dir)) {
            $this->_createDir($dir);
        }

        $fp = fopen($path, 'a+');
        fwrite($fp, $content);
        fclose($fp);
        chmod($path, 0777);
    }

    private function _createDir($path)
    {
        $arr = explode("/", $path);
        $dir = "";
        foreach ($arr as $r) {
            if ($r) {
                $dir = $dir . "/" . $r;
                if (!file_exists($dir)) {
                    mkdir($dir, 0777);
                    chmod($dir, 0777);
                }
            }
        }
    }
}
