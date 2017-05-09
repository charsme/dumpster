<?php
/**
 * Filecache Class
 *
 */
class FileCache
{
    private $path;
    
    public function __construct($_path_ = '')
    {
        if ($_path_) {
            $this->set_path($_path_);
        }
    }
    
    public function set_path($_path_ = '')
    {
        $this->path = $_path_;
    }
    
    public function delete($key, $custompath = false)
    {
        if ($custompath) {
            $cachefile = $custompath.$key;
        }
        
        if (! @file_exists($cachefile)) {
            return false;
        }
        
        @unlink($filepath);
        return true;
    }

    /***
     * @param $expire : integer (second)
     */
    public function set($key, $content, $expire, $custompath = '', $serialized = false)
    {
        $cachefile = $this->path.$key;
        if ($custompath) {
            $cachefile = $custompath.$key;
        }

        if (! $fp = @fopen($cachefile, 'wb')) {
            return;
        }

        $expire = time() + $expire;

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $expire.'TS--->'.($serialized ? 1 : 0).'SR--->'.$content);
            flock($fp, LOCK_UN);
        } else {
            return;
        }
        fclose($fp);
        
        return true;
    }
    
    public function get($key = "", $custompath = '')
    {
        $cachefile = $this->path.$key;
        
        if ($custompath) {
            $cachefile = $custompath.$key;
        }
        
        if (! @file_exists($cachefile)) {
            return false;
        }

        if (! $fp = @fopen($cachefile, 'rb')) {
            return false;
        }

        flock($fp, LOCK_SH);

        $data = '';
        if (filesize($cachefile) > 0) {
            $data = fread($fp, filesize($cachefile));
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        // Strip out the embedded expired timestamp
        if (! preg_match("/(\d+TS--->)/", $data, $match)) {
            return false;
        }

        // Has the file expired? If so we'll delete it.
        if (time() >= trim(str_replace('TS--->', '', $match['1']))) {
            @unlink($filepath);
            return false;
        }

        $data = str_replace($match['0'], '', $data);
        if (preg_match("/(\d+SR--->)/", $data, $match)) {
            $serialized = trim(str_replace('SR--->', '', $match[1]));
            if ($serialized) {
                $data = unserialize(str_replace($match['0'], '', $data));
            }
        }

        return $data;
    }
}
