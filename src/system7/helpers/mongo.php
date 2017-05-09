<?php

/* *
 *
 * MONGO DB
 *
 */

if (!function_exists('insertDataMongo')) {
    function insertDataMongo($collection, $data, $toUTF8 = false)
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');
        if (is_array($data) && count($data) > 0) {
            if ($toUTF8) {
                $data = toUTF8($data);
            }

            $cimongo = new Cimongo();
            $cimongo->insert($collection, $data);
            $id = get_object_vars($cimongo->insert_id())['$id'];
            return $id;
        }
        return false;
    }
}

if (!function_exists('getOneDataMongo')) {

    /**
     * get one data from collection
     * @param  array $condition
     * @param  string $type
     * @return array
     */
    function getOneDataMongo($condition, $type = "news", $toLatin = false)
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');

        $retM = $cimongo->where($condition)->get($type)->result_array(); //var_dump($retM);

        if ($retM) {
            return ($toLatin) ? toLatin1($retM[0]) : $retM[0];
        } else {
            return false;
        }
    }
}

if (!function_exists('dropCollectionMongo')) {
    function dropCollectionMongo($coll)
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');

        $cimongo->drop_collection(Config::App()->get('mongo_db'), $coll);
    }
}

if (!function_exists('readDataMongo')) {

    /**
     *
     * @param type $file --> berfungsi seperti primary key
     * @param type $type --> berfungsi seperti table
     *
     * @return boolean
     */
    function readDataMongo($file, $type = "news")
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');

        $row['json_name'] = $file;
        $retM = $cimongo->where(array( "json_name" => $row["json_name"] ))->get($type)->result_array(); //var_dump($retM);

        if ($retM) {
            return $retM[0];
        }
    }
}

if (!function_exists('readAllDataMongo')) {

    /**
     *
     * @param type $type --> berfungsi seperti table
     *
     * @return boolean
     */
    function readAllDataMongo($type = "news", $condition = false)
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');

        if ($condition) {
            $cimongo = $cimongo->where($condition);
        }

        $retM = $cimongo->get($type);

        $retM = $retM->result_array();

        return $retM;
    }
}

if (!function_exists('writeDataMongo')) {

    /**
     *
     * @param type $file --> berfungsi seperti primary key
     * @param type $type --> berfungsi seperti table
     * @param type $row
     *
     * @return boolean
     */
    function writeDataMongo($file, $row, $type = "news")
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');

        if (is_array($row) && count($row) > 0) {
            $cimongo = new Cimongo();
            $row['json_name'] = $file;
            $retM = $cimongo->where(array( "json_name" => $row["json_name"] ))->update($type, $row);
        }
    }
}

if (!function_exists('deleteDataMongo')) {

    /**
     *
     * @param type $file --> berfungsi seperti primary key
     * @param type $type --> berfungsi seperti table
     *
     * @return boolean
     */
    function deleteDataMongo($file, $type = "news")
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');

        if (is_array($file)) {
            $condition = $file;
        } else {
            $condition = array('json_name' => $file);
        }
        $retM = $cimongo->where($condition)->delete($type);
    }
}

if (!function_exists('updateDataMongo')) {
    function updateDataMongo($collection, $data, $condition, $toUTF8 = false)
    {
        $cimongo = app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');
        if ($toUTF8) {
            $data = toUTF8($data);
        }

        if (is_array($condition)) {
            $condition = $condition;
        } else {
            $condition = array('json_name' => $condition);
        }
        $retM = $cimongo->where($condition)->update($collection, $data);
    }
}
