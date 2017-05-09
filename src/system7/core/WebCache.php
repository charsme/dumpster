<?php

if (!class_exists('Cache')) {
    require_once(SYSTEM_PATH . "core/Cache.php");
}

/**
 * WebCache Class
 *
 */
class WebCache extends Cache
{
    private static $instance;
    private $cache_expiration = 0;
    public $RTR;

    public function __construct()
    {
        parent::__construct();
        $this->RTR      = &load_object('Router');
        self::$instance = &$this;
    }

    public static function &App()
    {
        if (!self::$instance) {
            self::$instance = new WebCache();
        }

        return self::$instance;
    }

    public function expired($time = 0)
    {
        if (is_string($time)) {
            if (isset($this->configurations[$time])) {
                $time = $this->configurations[$time];
            } else {
                $time = 0;
            }
        }

        $this->cache_expiration = (!is_numeric($time)) ? 0 : $time;

        return $this;
    }

    public function save_page($content = "", $expire = 0)
    {
        if (!$expire && $this->cache_expiration) {
            $expire = $this->cache_expiration;
        }
        if (!$expire) {
            $expire = $this->configurations["cachetime_default"];
        }

        if ($expire && $content) {
            $key = $this->configurations['prefix'] . 'full_page_' . md5(ltrim($this->RTR->full_uri()));
            if ($this->memcache_active) {
                $this->memcache->set($key, $content, $expire);
            }

            if ($this->filecache_active) {
                $this->filecache->set($key, $content, $expire);
            }

            if ($this->mongocache_active) {
                $this->mongocache->set($key, $content, $expire);
            }

            if ($this->rediscache_active) {
                $this->rediscache->set($key, $content, $expire);
            }
        }
    }

    public function serve_page()
    {
        $key = $this->configurations['prefix'] . 'full_page_' . md5(ltrim($this->RTR->full_uri()));
        $data = '';
        if ($this->rediscache_active && !$data) {
            $data = $this->rediscache->get($key);
        }
        if ($this->memcache_active && !$data) {
            $data = $this->memcache->get($key);
        }
        if ($this->filecache_active && !$data) {
            $data = $this->filecache->get($key);
        }
        if ($this->mongocache_active && !$data) {
            $data = $this->mongocache->get($key);
        }
        if (trim($data)) {
            echo $data;
            exit;
        }

        return false;
    }

    public function set_cache($name, $content = "", $expire = 0, $parameters = [])
    {
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : $this->configurations['prefix'];
        $name = $prefix . $name;
        if (!$expire) {
            $expire = $this->configurations["cachetime_default"];
        }
        if ($this->filecache_active || $this->mongocache_active) {
            $content_serialized = $content;
            $serialized = false;
            if (is_array($content) || is_object($content)) {
                $content_serialized = serialize($content);
                $serialized = true;
            }
        }
        if ($expire) {
            if ($this->memcache_active) {
                $this->memcache->set($name, $content, $expire);
            }
            if ($this->filecache_active) {
                $this->filecache->set($name, $content_serialized, $expire, '', $serialized);
            }
            if ($this->mongocache_active) {
                $this->mongocache->set($name, $content_serialized, $expire, $serialized, $parameters);
            }
            if ($this->rediscache_active) {
                $this->rediscache->set($name, $content, $expire, $parameters);
            }
        }
    }

    public function delete_cache($key, $parameters = [])
    {
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : $this->configurations['prefix'];
        $key = $prefix . $key;

        if ($this->filecache_active) {
            $this->filecache->delete($key);
        }

        if ($this->mongocache_active) {
            $this->mongocache->delete($key, $parameters);
        }

        if ($this->rediscache_active) {
            $this->rediscache->delete($key, $parameters);
        }
    }

    public function get_cache($name, $parameters = [])
    {
        $prefix = isset($parameters['prefix']) ? $parameters['prefix'] : $this->configurations['prefix'];
        $name = $prefix . $name;

        $data = '';
        if ($this->rediscache_active && !$data) {
            $data = $this->rediscache->get($name, $parameters);
        }
        if ($this->memcache_active && !$data) {
            $data = $this->memcache->get($name, $parameters);
        }
        if ($this->filecache_active && !$data) {
            $data = $this->filecache->get($name);
        }
        if ($this->mongocache_active && !$data) {
            $data = $this->mongocache->get($name, $parameters);
        }

        return $data;
    }
}
