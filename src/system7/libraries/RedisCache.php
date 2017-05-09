<?php

/**
 * RedisCache Class
 *
 */
class RedisCache
{
    private $predis;
    private $prefix = '';

    public function __construct($config = 'tcp=//127.0.0.1:6379')
    {
        if ($config !== null) {
            if (isset($config['prefix'])) {
                $this->prefix = $config['prefix'];
                unset($config['prefix']);
            }
            try {
                $this->predis = new \Predis\Client($config);
            } catch (\Predis\Connection\ConnectionException $e) {
                echo "Couldn't connected to Redis";
                echo $e->getMessage();
            }
        }
    }


    public function set($key, $value, $expire = 0)
    {
        if (is_object($this->predis)) {
            $this->predis->set($key, $value);

            if ($expire) {
                $expire = (time() + $expire);
                $this->predis->expireat($key, $expire);
            }
        }

        return $this;
    }

    public function delete($key)
    {
        if (is_object($this->predis)) {
            $this->predis->set($key, false);
            $expire = (time() + 1);
            $this->predis->expireat($key, $expire);
        } else {
            return null;
        }
    }

    public function get($key = "")
    {
        if (is_object($this->predis)) {
            return $this->predis->get($key);
        } else {
            return null;
        }
    }
}
