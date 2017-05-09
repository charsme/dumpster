<?php

/**
 * Cache Class
 *
 * @dependencies (Libraries): MemCache, FileCache
 */
class Cache
{
    protected $configurations;
    public $memcache_active;
    public $memcache;
    public $filecache_active;
    public $filecache;
    public $mongocache;
    public $rediscache;

    public function __construct()
    {
        if (is_file(CONFIG_PATH . "cache.php")) {
            include(CONFIG_PATH . "cache.php");

            $this->configurations = & $cache;
        }

        $this->filecache_active = false;
        $this->memcache_active = false;
        $this->mongocache_active = false;
        $this->rediscache_active = false;

        $this->init_memcache();
        $this->init_filecache();
        $this->init_mongocache();
        $this->init_rediscache();
    }

    public function start_cache($driver = '*')
    {
        if ($driver == 'memcache' || $driver == '*') {
            $this->configurations['memcache_active'] = true;

            if (is_object($this->memcache) === false) {
                $this->init_memcache();
            }

            $this->memcache_active = true;
        }

        if ($driver == 'filecache' || $driver == '*') {
            $this->configurations['filecache_active'] = true;

            if (is_object($this->filecache) === false) {
                $this->init_filecache();
            }

            $this->filecache_active = true;
        }

        if ($driver == 'mongocache' || $driver == '*') {
            $this->configurations['mongocache_active'] = true;

            if (is_object($this->mongocache) === false) {
                $this->init_mongocache();
            }

            $this->mongocache_active = true;
        }

        if ($driver == 'rediscache' || $driver == '*') {
            $this->configurations['rediscache_active'] = true;

            if (is_object($this->rediscache) === false) {
                $this->init_rediscache();
            }

            $this->rediscache_active = true;
        }
    }

    public function stop_cache($driver = '*')
    {
        if ($driver == 'memcache' || $driver == '*') {
            $this->configurations['memcache_active'] = false;
            $this->memcache_active = false;
        }

        if ($driver == 'filecache' || $driver == '*') {
            $this->configurations['filecache_active'] = false;
            $this->filecache_active = false;
        }

        if ($driver == 'mongocache' || $driver == '*') {
            $this->configurations['mongocache_active'] = false;
            $this->mongocache_active = false;
        }

        if ($driver == 'rediscache' || $driver == '*') {
            $this->configurations['rediscache_active'] = false;
            $this->rediscache_active = false;
        }
    }

    public function get_config($name = '')
    {
        return isset($this->configurations[$name]) ? $this->configurations[$name] : $this->configurations;
    }

    private function init_memcache()
    {
        if ($this->configurations['memcache_active']) {
            if (class_exists('MemCached') === false) {
                require_once(SYSTEM_PATH.'libraries/MemCache.php');
            }

            $this->memcache = new MemCached($this->configurations['memcache_hosts']);
            $this->memcache_active = true;
        }
    }

    private function init_filecache()
    {
        if ($this->configurations['filecache_active']) {
            if (class_exists('FileCache') === false) {
                require_once(SYSTEM_PATH.'libraries/FileCache.php');
            }
            $this->filecache = new FileCache($this->configurations['filecache_path']);
            $this->filecache_active = true;
        }
    }

    private function init_mongocache()
    {
        if ($this->configurations['mongocache_active']) {
            if (PHP_VERSION < 7) {
                if (class_exists('MongoCache') === false) {
                    require_once(SYSTEM_PATH.'libraries/MongoCache.php');
                }

                $this->mongocache = new MongoCache($this->configurations['mongocache_collection']);
            } else {
                if (class_exists('MongoCache7') === false) {
                    require_once(SYSTEM_PATH.'libraries/MongoCache7.php');
                }

                $this->mongocache = new MongoCache7($this->configurations['mongocache_collection']);
            }
            
            $this->mongocache_active = true;
        }
    }

    private function init_rediscache()
    {
        if (isset($this->configurations['rediscache_active']) && $this->configurations['rediscache_active']) {
            if (class_exists('RedisCache') === false) {
                require_once(SYSTEM_PATH.'libraries/RedisCache.php');
            }
            $config = isset($this->configurations['rediscache_config']) ? $this->configurations['rediscache_config'] : null;
            $this->rediscache = new RedisCache($config);
            $this->rediscache_active = true;
        }
    }
}
