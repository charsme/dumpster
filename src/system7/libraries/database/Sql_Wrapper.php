<?php

/**
 * Sql_Wrapper
 *
 * @package
 * @author
 * @copyright unisbadri
 * @version 2011
 * @access public
 */
class Sql_Wrapper extends sql_db
{

    /**
     * Sql_Wrapper::__construct()
     *
     * @param mixed $sql_host
     * @param mixed $sql_user
     * @param mixed $sql_password
     * @param mixed $sql_dbname
     * @return
     */
    public function __construct($sql_host, $sql_user, $sql_password, $sql_dbname, $debug)
    {
        parent::__construct($sql_host, $sql_user, $sql_password, $sql_dbname);
        
        $this->debug = $debug;
    }

    /**
     * Sql_Wrapper::query()
     *
     * @param mixed $query
     * @param bool $is_object
     * @return
     */
    public function query($query, $is_object = true)
    {
        $raw_data = $this->sql_query($query, true);
        
        $data = array();
        $data =  $this->process_data($raw_data, $is_object);
        $this->sql_freeresult($raw_data);
        return $data;
    }

    /**
     * Sql_Wrapper::array_to_object()
     *
     * @param mixed $array
     * @return
     */
    public function array_to_object($array)
    {
        if (isset($array[0]) && is_array($array[0])) {
            $data = array();
            foreach ($array as $array_2) {
                array_push($data, (object) $array_2);
            }
            return $data;
        } elseif (is_array($array)) {
            if (count($array)>0) {
                return (object) $array;
            } else {
                return false;
            }
        } elseif (!is_bool($array)) {
            return $array;
        }
    }

    /**
     * Sql_Wrapper::process_data()
     *
     * @param mixed $raw_data
     * @param bool $is_object
     * @return
     */
    public function process_data($raw_data, $is_object = false)
    {
        $data = array();
        if ($this->sql_numrows($raw_data) == 1) {
            //$data = $this->sql_fetchrow($raw_data);
        array_push($data, $this->sql_fetchrow($raw_data));
        } elseif ($this->sql_numrows($raw_data) > 1) {
            while ($row = $this->sql_fetchrow($raw_data)) {
                array_push($data, $row);
            }
        }

        if ($is_object) {
            return $this->array_to_object($data);
        } else {
            return $data;
        }
    }
}
