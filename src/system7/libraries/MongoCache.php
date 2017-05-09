<?php
/**
 * MongoCache Class
 *
 */
class MongoCache
{
    private $cimongo;
    private $cache_table;
    
    public function __construct($path = 'cache')
    {
        if (class_exists('Cimongo') === false) {
            include(SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');
        }
    
        $this->cache_table = $path;
        $this->cimongo = new Cimongo();
    }
    
   
    /***
     * @param $key : string key
     * $value : string value
     * $collection : string name of table
     * $expire : time()
     */
    public function set($key, $value, $expire = 0, $serialized = false, $settings = false)
    {
        $collection = (isset($settings['collection'])) ? $settings['collection'] : $this->cache_table ;

        $expire = $expire ? (time() + $expire) : time();

        $data = array("key" => $key, "value" => $value, "expire" => $expire, "serialized" => $serialized);
    
        $where = array("key" => $key);

        $res = $this->cimongo->where($where)->update($collection, $data);
        
        return $data;
    }
    
    /***
     * @param $key : string key
     * $custom_key : string key of custome key
     */
    public function get($key = "", $settings = false)
    {
        $collection = (isset($settings['collection'])) ? $settings['collection'] : $this->cache_table ;

        $find = array("key" => $key );
        $data = $this->cimongo->where($find)->get($collection)->result_array();

        if (!$data || !is_array($data) || count($data) == 0) {
            return false;
        } else {
            $data = $data[0];
        }

        if (time() > $data['expire']) {
            $this->cimongo->where($find)->delete($collection);
            return false;
        }

        if (isset($data['serialized']) && $data['serialized']) {
            $data['value'] = unserialize($data['value']);
        }
        return $data['value'];
    }

    public function delete($key, $settings = false)
    {
        $collection = (isset($settings['collection'])) ? $settings['collection'] : $this->cache_table ;
        
        $asterix = substr($key, 0, 1);
        $asterix2 = substr($key, -1, 1);
        if ($asterix == '*' || $asterix2 == '*') {
            if ($asterix == '*') {
                $key = substr($key, 1);
                $asterix = true;
            } else {
                $asterix = false;
            }
            if ($asterix2 == '*') {
                $key = substr($key, 0, strlen($key) - 1);
                $asterix2 = true;
            } else {
                $asterix2 = false;
            }
            return $this->cimongo->like('key', trim($key), 'i', $asterix, $asterix2)->delete($collection);
        } else {
            $find = array("key" => $key );
            return $this->cimongo->where($find)->delete($collection);
        }

        return false;
    }
}
