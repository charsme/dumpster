<?php

/* *
 *
 * MONGO DB
 *
 */

if (!function_exists('insertDataMongo')) {
    function insertDataMongo($collection, $data, $toUTF8 = false)
    {
        $cimongo = app_load_mongo7($collection);
        if (is_array($data) && count($data) > 0) {
            if ($toUTF8) {
                $data = toUTF8($data);
            }

            $result = $cimongo->insertOne($data);
            $id = $result->getInsertedId();
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
        $cimongo = app_load_mongo7($type);

        $retM = $cimongo->find($condition);

        if ($retM) {
            $ress = null;
            foreach ($retM as $document) {
                $ress = json_decode(json_encode($document), true); //(array) $document;
                break;
            }
            return ($toLatin) ? toLatin1($ress) : $ress;
        } else {
            return false;
        }
    }
}

if (!function_exists('dropCollectionMongo')) {
    function dropCollectionMongo($coll)
    {
        $cimongo = app_load_mongo7($coll);
        $cimongo->drop();
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
        $cimongo = app_load_mongo7($type);

        $row['json_name'] = $file;
        $retM = $cimongo->find(["json_name" => $row["json_name"]]);

        if ($retM) {
            $ress = null;
            foreach ($retM as $document) {
                $ress = json_decode(json_encode($document), true); //(array) $document;
                break;
            }
            return $ress;
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
        $cimongo = app_load_mongo7($type);

        if ($condition) {
            $cursor = $cimongo->find($condition);
        } else {
            $cursor = $cimongo->find([]);
        }
        $retM = [];
        foreach ($cursor as $document) {
            $retM[] = json_decode(json_encode($document), true); //(array) $document;
        }

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
        $cimongo = app_load_mongo7($type);

        if (is_array($row) && count($row) > 0) {
            $row['json_name'] = $file;
            $retM = $cimongo->updateOne(["json_name" => $row["json_name"]], ['$set' => $row], ['upsert' => true]);
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
        $cimongo = app_load_mongo7($type);

        if (is_array($file)) {
            $condition = $file;
        } else {
            $condition = ['json_name' => $file];
        }
        
        $retM = $cimongo->deleteMany($condition);
    }
}

if (!function_exists('updateDataMongo')) {
    function updateDataMongo($collection, $data, $condition, $toUTF8 = false)
    {
        $cimongo = app_load_mongo7($collection);
        
        if ($toUTF8) {
            $data = toUTF8($data);
        }

        if (is_array($condition)) {
            $condition = $condition;
        } else {
            $condition = ['json_name' => $condition];
        }
        
        $retM = $cimongo->updateMany($condition, ['$set' => $data], ['upsert' => true]);
    }
}
