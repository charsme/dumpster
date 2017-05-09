<?php
/**
 * Memcache Class
 *
 */
class MemCached
{
    private $memcached;
    private $hosts;
    
    public function __construct($_hosts_ = '')
    {
        if ($_hosts_) {
            $this->set_host($_hosts_);
        }
        if (class_exists('Memcache')) {
            $this->memcached = new Memcache();
        }
        if (is_array($this->hosts)) {
            foreach ($this->hosts as $server) {
                if (is_resource($this->memcached)) {
                    $this->memcached->addServer($server, 11211);
                }
            }
        }
    }
        
    public function set_host($_hosts_)
    {
        if (!is_array($_hosts_)) {
            $_hosts_ = array($_hosts_);
        }
        $this->hosts = $_hosts_;
    }
    
    private function connect()
    {
        /*
        if($this->memcached)
        {
            return '';
        }
        $this->memcached = new Memcache();
        foreach($this->hosts as $server)
        {
            $this->memcached->addServer($server, 11211);
        }
        */
        
    if (is_resource($this->memcached) === false) {
        $this->memcached = new Memcache();
        foreach ($this->hosts as $server) {
            $this->memcached->addServer($server, 11211);
        }
    }
    }
    
    private function close()
    {
        if ($this->memcached != null) {
            $this->memcached->close();
        }
    }
    
    
    public function set($key = "", $content = "", $timeout = 3600)
    {
        $this->connect();
        
        $result = false;
        if (is_resource($this->memcached)) {
            if ($this->memcached->get($key)) {
                $result = @$this->memcached->replace($key, $content, false, $timeout);
            } else {
                $result = @$this->memcached->set($key, $content, false, $timeout);
            }
        
            $this->close();
        }
        return $result;
    }
    
    public function get($key = "")
    {
        $this->connect();
        $data = false;
        if (is_resource($this->memcached)) {
            $data = $this->memcached->get($key);
            $this->close();
        }
        return $data;
    }
    
    public function __destruct()
    {
        $this->close();
    }
}
