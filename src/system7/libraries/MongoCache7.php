<?php
/**
 * MongoCache Class
 *
 */
class MongoCache7
{
    private $cimongo;
    private $cache_table;
    
    public function __construct($path = 'cache')
    {
        $this->cache_table = $path;
        $this->cimongo = app_load_mongo7($path);
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

        $data = ["value" => $value, "expire" => $expire, "serialized" => $serialized];
    
        $where = ["key" => $key];

        if ($this->cache_table != $collection) {
            $cimongo = app_load_mongo7($collection);
            $res = $cimongo->updateOne($where, [$set => $data], ['upsert' => true]);
            unset($cimongo);
        } else {
            $res = $this->cimongo->updateOne($where, ['$set' => $data], ['upsert' => true]);
        }
        
        return $data;
    }
    
    /***
     * @param $key : string key
     * $custom_key : string key of custome key
     */
    public function get($key = "", $settings = false)
    {
        $collection = (isset($settings['collection'])) ? $settings['collection'] : $this->cache_table ;

        $find = ["key" => $key];
        
        if ($this->cache_table != $collection) {
            $cimongo = app_load_mongo7($collection);
            $data = $cimongo->find($find);
            unset($cimongo);
        } else {
            $data = $this->cimongo->find($find);
        }
        
        if (!$data || !is_array($data) || count($data) == 0) {
            return false;
        } else {
            $ress = null;
            foreach ($data as $document) {
                $ress = json_decode(json_encode($document), true); //(array) $document;
                break;
            }
            $data = $ress;
        }

        if (time() > $data['expire']) {
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
            
            if ($asterix !== true) {
                $key = "^" . $key;
            }
            
            if ($asterix2 !== false) {
                $key .= "$";
            }
            
            $data = '';
            if ($this->cache_table != $collection) {
                $cimongo = app_load_mongo7($collection);
                
                $cursor = $cimongo->find(['key' => ['$regex' => $key]]);
                foreach ($cursor as $document) {
                    $data .= $cimongo->deleteMany(['key' => $document['key']]);
                }
                
                unset($cimongo);
            } else {
                $cursor = $this->cimongo->find(['key' => ['$regex' => $key]]);
                foreach ($cursor as $document) {
                    $data .= $this->cimongo->deleteMany(['key' => $document['key']]);
                }
            }

            return $data;
        } else {
            $find = ["key" => $key];
            
            if ($this->cache_table != $collection) {
                $cimongo = app_load_mongo7($collection);
                $data = $cimongo->deleteMany($find);
                unset($cimongo);
            } else {
                $data = $this->cimongo->deleteMany($find);
            }
            
            return $data;
        }

        return false;
    }
}
