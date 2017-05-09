<?php

class Model
{
    private $table          = '';
    private $dbname         = '';
    private $magic_quotes   = true;
    public $db_connect_id;
    public $master;
    public $slave;
    public $query_result;
    public $row            = array();
    public $rowset         = array();
    public $num_queries    = 0;
    public $arr_param      = array(
        'select'   => '*',
        'where'    => array(),
        'join'     => array(),
        'groupby'  => '',
        'offset'   => '',
        'limit'    => false,
        //'orderby' => '',
        'orderby'  => array(),
        'having'   => array(),
        'where_in' => array(),
        'like'     => array(),
    );
    private $replace_symbol = ".-',";
    public $auto_close     = true;

    /**
     * constructor
     * $cnf = array(
     *    'master'    => array(''db_host'=>'', 'db_user'=>'', 'db_pass'=> '', 'db_name'=>''),
     *    'slave'        => array(
     *                        array(''db_host'=>'', 'db_user'=>'', 'db_pass'=> '', 'db_name'=>''),
     *                        array(''db_host'=>'', 'db_user'=>'', 'db_pass'=> '', 'db_name'=>''),
     *                )
     * )
     */
    public function __construct($cnf = array())
    {
        $this->db        = &load_db();
        $this->db_config = Database::$config;

        //		$this->master['dbhost'] = $cnf['master']['db_host'];
        //		$this->master['dbuser'] = $cnf['master']['db_user'];
        //		$this->master['dbpass'] = $cnf['master']['db_pass'];
        //		$this->master['dbname'] = $cnf['master']['db_name'];
        //
        //		foreach($cnf['slave'] as $i=>$dbslave)
        //		{
        //			$this->slave['dbhost'][$i] = $dbslave['db_host'];
        //			$this->slave['dbuser'][$i] = $dbslave['db_user'];
        //			$this->slave['dbpass'][$i] = $dbslave['db_pass'];
        //			$this->slave['dbname'][$i] = $dbslave['db_name'];
        //		}
        //		if ($this->master['dbname'])
        //            $this->set_db($this->master['dbname']);
    }

    //    function set_db($database){
    //        $this->dbname = $database;
    //		return $this;
    //    }

    /*
      connect to mysql and db
      - $type (string) = 'SELECT' for random dbhost and default is master_dbhost
      - $persistency (boolean)

      function connect_db($type = '', $db_server = array(), $persistency = true,$new = false)
      {
      #global $master_dbhost, $master_dbuser, $master_dbpass, $slave_dbhost, $slave_dbuser, $slave_dbpass;

      $this->persistency = $persistency;
      if (count($db_server) > 0){
      $this->user = $db_server['user'];
      $this->password = $db_server['password'];
      $this->server = $db_server['host'];
      }else{
      if ($type == 'SELECT') {
      $dbcount = count($this->slave['dbhost']);
      $dbactive = rand(0, $dbcount - 1);
      $this->user = $this->slave['dbuser'][$dbactive];
      $this->password = $this->slave['dbpass'][$dbactive];
      $this->server = $this->slave['dbhost'][$dbactive];
      } else {
      $this->user = $this->master['dbuser'];
      $this->password = $this->master['dbpass'];
      $this->server = $this->master['dbhost'];
      }
      }
      //echo $this->server.','.$this->user.','. $this->password;
      if ($new)
      $this->db_connect_id = @mysql_connect($this->server, $this->user, $this->password, true);
      else
      $this->db_connect_id = ($this->persistency) ? @mysql_pconnect($this->server, $this->user, $this->password) : @mysql_connect($this->server, $this->user, $this->password);
      //echo $this->db_connect_id;
      if ($this->db_connect_id) {
      $dbselect = @mysql_select_db($this->dbname);
      if (!$dbselect) {
      @mysql_close($this->db_connect_id);
      $this->db_connect_id = $dbselect;
      }
      return $this->db_connect_id;
      } else
      return false;
      }
     */


    /*
      run mysql query
      - $query (string) = SQL query

      ex : $this->db->query("select * from table where title like '%this%' order by date desc");
     */
    public function _query($query = false)
    {
    }

    /*
      set where in condition. if there multiple, it will implode by 'AND'
      - $field (string) = field in table
      - $value (array) || (string) = searching value
      - $type (string) = type of filtering. There's two type '' & 'int'. Default is string filtering. for integer you can use 'int'

      ex :
      1. $this->db->where_in('id',"'comedy','horror','action'");
      2. $this->db->where_in('category',array(1,3,12),'int');
     */

    public function where_in($field, $value = '', $type = '')
    {
        if (is_array($value) && count($value) > 0) {
            if ($type == 'int') {
                foreach ($value as $key => $item) {
                    $value[$key] = $this->parseInt($item);
                }
                $value = implode(',', $value);
            } else {
                foreach ($value as $key => $item) {
                    $value[$key] = $this->escape_string($item);
                }
                $value = "'" . implode("','", $value) . "'";
            }
        }
        if ($field && $value) {
            $this->arr_param['where_in'][] = " $field IN ($value) ";
        }

        return $this;
    }

    /*
      set select parameter in sql query
      - $select (string) = field name that you want to select

      ex : $this->db->select('id,name,date');
     */

    public function select($select = '*', $add_prefix = true)
    {
        $select = trim($select);
        if ($select != '*') {
            if ($add_prefix) {
                $prefix = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
                if ($prefix) {
                    $select = preg_replace('/^' . $prefix . '/', '', $select);
                }
                $select = $prefix . $select;
            }
        }

        $this->arr_param['select'] = $select;

        return $this;
    }

    private function has_operator($str)
    {
        return (trim(!preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str))) ? false : true;
    }

    /*
      set where parameter
      - $field (string) || (array) = name of field or array that contain field (key) and value (value)
      - $val (string) = value

      ex :
      1. $this->db->where('id',2);
      2. $this->db->where('id >',10);
      3. $this->db->where(array('title' => 'my title', 'date_created >=' => '2011-02-01'));
     */

    public function where($field, $val = false)
    {
        if ($val === false) {
            if (is_array($field)) {
                foreach ($field as $key => $value) {
                    preg_match('/replace\((.+)\)/', strtolower($key), $match);
                    if (count($match) == 2) {
                        for ($i = 0; $i < strlen($this->replace_symbol); $i++) {
                            $replace = 'REPLACE(' . (($i == 0) ? $match[1] : $replace) . ',"' . substr($this->replace_symbol, $i, 1) . '","")';
                        }
                        $this->arr_param['where'][] = $replace . " = '" . $this->escape_string($value) . "'";
                    } elseif (is_numeric($key) && !empty($value)) {
                        $this->arr_param['where'][] = $value;
                    } else {
                        $this->arr_param['where'][] = ($this->has_operator($key)) ? ($key . ((!is_string($value)) ? $value : "'" . $this->escape_string($value) . "'")) : ($key . " = " . ((!is_string($value)) ? $value : "'" . $this->escape_string($value) . "'"));
                    }
                }
            } elseif ($this->has_operator($field)) {
                $this->arr_param['where'][] = $field;
            }
        } else {
            preg_match('/replace\((.+)\)/', strtolower($field), $match);
            if (count($match) == 2) {
                for ($i = 0; $i < strlen($this->replace_symbol); $i++) {
                    $replace = 'REPLACE(' . (($i == 0) ? $match[1] : $replace) . ',"' . substr($this->replace_symbol, $i, 1) . '","")';
                }
                $this->arr_param['where'][] = $replace . " = '" . $this->escape_string($val) . "'";
            } else {
                $this->arr_param['where'][] = ($this->has_operator($field)) ? $field . " " . ((!is_string($val)) ? $val : "'" . $this->escape_string($val) . "'") : $field . " = " . ((!is_string($val)) ? $val : "'" . $this->escape_string($val) . "'");
            }
        }

        return $this;
    }

    /*
      set like parameter
      - $field (string) || (array) = name of field or array that contain field (key) and value (value)
      - $val (string) = value

      ex :
      1. $this->db->like('id','%1%');
      2. $this->db->like(array('title' => '%my*title', 'date_created' => '2011-02-01'));
     */

    public function like($field, $val = false)
    {
        if ($val === false) {
            if (is_array($field)) {
                foreach ($field as $key => $value) {
                    $this->arr_param['like'][] = $key . " LIKE '" . $this->escape_string($value) . "'";
                }
            }
        } else {
            $this->arr_param['where'][] = $field . " LIKE '" . $this->escape_string($val) . "'";
        }

        return $this;
    }

    /*
      join table
      - $table (string) = name of table
      - $condition (string) || (array)= condition
      - $type (string) = join type ['LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER']

      ex :
      1. $this->db->join('category','category_id=item_category','LEFT');
      2. $this->db->join('category',array('category_id' => 'item_category'));
     */

    public function join($table, $condition, $type = '')
    {
        $prefix = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];

        if ($type != '') {
            $type = strtoupper(trim($type));
            if (!in_array($type, array(
                'LEFT',
                'RIGHT',
                'OUTER',
                'INNER',
                'LEFT OUTER',
                'RIGHT OUTER'
            ))
            ) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }
        $cond = array();
        if (is_array($condition)) {
            foreach ($condition as $key => $value) {
                $cond[] = ($this->has_operator($key)) ? ($key . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'")) : ($key . " = " . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'"));
            }
            $cond = implode(' AND ', $cond);
        } else {
            $cond = $condition;
        }

        $table = $prefix . str_replace(array(
                $prefix . '_',
                $prefix
            ), '', $table); //SEMENTARA DEVEL MEN

        $this->arr_param['join'][] = $type . 'JOIN ' . $table . ' ON ' . $cond;

        return $this;
    }

    /*
      set table
      - $table (string) = name of table

      ex : $this->db->table('news');
     */

    public function table($table)
    {
        $prefix      = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
        $table       = str_replace(array(
            $prefix . '_',
            $prefix
        ), '', $table); //SEMENTARA DEVEL MEN
        $this->table = $prefix . $table;

        return $this;
    }

    /*
      set order by
      - $by (string) || array = field name to be ordered or array contain key and ass/desc
      - $sort (string) = ASC or DESC

      ex :
      1. $this->db->orderby('id','desc');
      2. $this->db->orderby(array('id'=>'desc', 'name'=>'asc'));
     */

    public function orderby($by, $sort = false)
    {
        if ($sort === false) {
            if (is_array($by)) {
                foreach ($by as $key => $value) {
                    $value                        = (empty($value) ? 'ASC' : $value);
                    $this->arr_param['orderby'][] = $key . ' ' . $value;
                }
            } else {
                $this->arr_param['orderby'][] = $by . ' ASC';
            }
        } else {
            $this->arr_param['orderby'][] = "$by $sort";
        }

        return $this;
        /*
          if (!$sort)
          $sort = 'ASC';
          $this->arr_param['orderby'] = "$by $sort";
          return $this;
         */
    }

    /*
      set limit and offset
      - $limit (int) = starting index
      - $offset (int) = length of record. Default is null (all record)

      ex :
      1. $this->db->limit(10);
      2. $this->db->limit(0,10);
     */

    public function limit($limit, $offset = '')
    {
        $this->arr_param['limit'] = $this->parseInt($limit);
        $offset                   = $this->parseInt($offset);
        if ($offset) {
            $this->arr_param['offset'] = $offset;
        }

        return $this;
    }

    /*
      set groupby parameter
      - $groupby (string) = field name

      ex : $this->db->groupby('category_id');
     */

    public function groupby($groupby)
    {
        $this->arr_param['groupby'] = $groupby;

        return $this;
    }

    private function parseInt($int)
    {
        return abs(preg_replace("/[^\d]/", "", $int));
    }

    /*
      set having parameter
      - $field (string) || (array) = name of field or array that contain field (key) and value (value)
      - $val (string) = value

      ex :
      1. $this->db->having('id',2);
      2. $this->db->having("id > 10 AND date = '2011-11-11' ");
      3. $this->db->having(array('title' => 'my title', 'date_created >=' => '2011-02-01'));
     */

    public function having($field, $val = false)
    {
        if ($val === false) {
            if (is_array($field)) {
                foreach ($field as $key => $value) {
                    $this->arr_param['having'][] = ($this->has_operator($key)) ? ($key . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'")) : ($key . " = " . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'"));
                }
            } elseif ($this->has_operator($field)) {
                $this->arr_param['having'][] = $field;
            }
        } else {
            $this->arr_param['having'][] = ($this->has_operator($field)) ? $field . " " . ((is_numeric($val)) ? $val : "'" . $this->escape_string($val) . "'") : $field . " = " . ((is_numeric($val)) ? $val : "'" . $this->escape_string($val) . "'");
        }

        return $this;
    }

    /*
      send query and get record
      - $table (string) = table name

      return (array) $data;
      ex : $this->db->get('news');
     */

    public function get($table = '')
    {
        if ($table != '') {
            $prefix      = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
            $table       = str_replace(array(
                $prefix . '_',
                $prefix
            ), '', $table);
            $this->table = $prefix . $table;
        }
        $query = $this->create_query();
        //if (!$this->db_connect_id)
        //    $this->connect_db('SELECT');
        //$this->query_result = mysql_query($query,$this->db_connect_id);

        $this->query($query);
        $data = $this->fetchrow();

        return $data;
    }

    public function create_query()
    {
        $table = $this->table;
        $where = implode(' AND ', $this->arr_param['where']);
        $query = "SELECT " . $this->arr_param['select'] . " FROM " . $table . " " . implode(' ', $this->arr_param['join']);
        $merge = array();
        if (count($this->arr_param['where']) > 0 || count($this->arr_param['where_in']) > 0 || count($this->arr_param['like']) > 0) {
            $merge = array_merge($this->arr_param['where'], $this->arr_param['where_in'], $this->arr_param['like']);
            $query .= " WHERE " . implode(" AND ", $merge);
        }

        if ($this->arr_param['groupby'] != '') {
            $query .= " GROUP BY " . $this->arr_param['groupby'];
        }
        if (count($this->arr_param['having']) > 0) {
            $query .= " HAVING " . implode(" AND ", $this->arr_param['having']);
        }
        if (count($this->arr_param['orderby']) > 0 && !empty($this->arr_param['orderby'])) {
            $query .= " ORDER BY " . implode(", ", $this->arr_param['orderby']);
        }
        //if ($this->arr_param['orderby'] != '')
        //    $query .= " ORDER BY " . $this->arr_param['orderby'];
        if ($this->arr_param['limit'] !== false) {
            $query .= " LIMIT " . $this->arr_param['limit'];
            if ($this->arr_param['offset'] != '') {
                $query .= "," . $this->arr_param['offset'];
            }
        }
        $this->reset();

        return $query;
    }

    private function reset()
    {
        //echo '<pre>START :<br />';print_r($this->arr_param);echo '<hr />';
        $this->arr_param = array(
            'select'   => '*',
            'where'    => array(),
            'join'     => array(),
            'groupby'  => '',
            'offset'   => '',
            'limit'    => false,
            'orderby'  => '',
            'having'   => array(),
            'where_in' => array(),
            'like'     => array(),
        );
        //print_r($this->arr_param);echo '<br />END</pre>';
    }

    /*
      fetch query into array rows
      return (array) $row;
     */

    public function fetchrow()
    {
        if ($this->query_result) {
            $rows = array();
            while ($row = mysql_fetch_assoc($this->query_result)) {
                $rows[] = $row;
            }

            return $rows;
        } else {
            return array();
        }
    }

    /*
      insert row into a table
      - $table (string) = table name
      - $data (array) = array row data that contain field (key) and value (value)

      return (int) last_id;
      ex : $this->db->insert('news',array('title' => 'my title', 'category' =>'otomotif'));
      multiple insert
      ex :
      $field = array('title','content');
      $data = array(
      array(
      'rayakan imlek bersama keluarga',
      'mari kita rayakan semua'
      ),
      array(
      'indonesia tekena musibah banjir',
      'banjir yang melanda indonesia sangat dahsyat'
      )
      );
      $this->db->insert('tabelku',$data,$field);
      hasil query :
      insert into `tabelku` (`title`,`content`) values ('rayakan imlek bersama keluarga','mari kita rayakan semua'),('indonesia tekena musibah banjir','banjir yang melanda indonesia sangat dahsyat')
     */

    public function escape_string($str)
    {
        return ($this->magic_quotes) ? mysql_real_escape_string(stripslashes($str)) : mysql_real_escape_string($str);
    }

    public function insert($table = '', $data = array(), $fields = false, $echo = false)
    {
        if (!$table) {
            $table = $this->table;
        } else {
            $prefix = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
            $table  = $prefix . str_replace(array(
                    $prefix . '_',
                    $prefix
                ), '', $table);
        }

        $field = array();
        $value = array();
        if (is_array($data)) {
            if ($fields === false) {
                foreach ($data as $key => $val) {
                    $field[] = "`$key`";
                    $value[] = "'" . $this->escape_string($val) . "'";
                }
                $row_data = array("(" . implode(',', $value) . ")");
            } else {
                foreach ($fields as $value) {
                    $field[] = "`$value`";
                }
                $row_data = array();
                foreach ($data as $row) {
                    $field_data = array();
                    foreach ($row as $val) {
                        $field_data[] = "'" . $this->escape_string($val) . "'";
                    }
                    $row_data[] = "(" . implode(',', $field_data) . ")";
                }
            }
            $query = "insert into " . $table . " (" . implode(',', $field) . ") values " . implode(',', $row_data);
            if ($echo) {
                echo $query;
            }#exit;
            $query = $this->clearDiv($query);
            //			if (!$this->db_connect_id)
            //                $this->connect_db();
            //$exe = mysql_query($query,$this->db_connect_id);
            //			if ($exe)
            //                $exe = mysql_insert_id($this->db_connect_id);
            $this->query($query);

            //$exe = $this->db->sql_nextid();
            $exe = $this->master->sql_nextid(); // Adi, because from master

            $this->reset();
            //			if ($this->auto_close)
            //                $this->close_db();
            return $exe;
        } else {
            return false;
        }
    }

    /*
      delete record in table
      - $table (string) = table name
      - $condition (array) || (string) = condition

      return (int) affected_row;
      ex :
      1. $this->db->delete('news','id = 2');
      2. $this->db->delete('news',array('title' => 'my title', 'category' =>'otomotif'));
     */

    public function delete($table, $condition = array())
    {
        if (!$table) {
            $table = $this->table;
        } else {
            $prefix = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
            $table  = $prefix . str_replace(array(
                    $prefix . '_',
                    $prefix
                ), '', $table);
        }
        $cond = array();
        if (is_array($condition)) {
            foreach ($condition as $key => $value) {
                $cond[] = ($this->has_operator($key)) ? ($key . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'")) : ($key . " = " . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'"));
            }
        } elseif ($this->has_operator($condition)) {
            $cond[] = $condition;
        }
        $merge = array();
        $where = '';
        if (count($this->arr_param['where']) > 0 || count($this->arr_param['where_in']) > 0 || count($this->arr_param['like']) > 0 || count($cond) > 0) {
            $merge = array_merge($this->arr_param['where'], $this->arr_param['where_in'], $this->arr_param['like'], $cond);
            $where .= " WHERE " . implode(" AND ", $merge);
        }

        // strict, could not delete entire table
        if ($where) {
            $query = "delete from " . $table;
            $query .= $where;
            //echo $query;die();
            //if (!$this->db_connect_id)
            //    $this->connect_db();
            //mysql_query($query,$this->db_connect_id);
            $this->query($query);
            //$row = mysql_affected_rows($this->db->db_connect_id);
            $row = mysql_affected_rows($this->master->db_connect_id); // Adi, because from master
            $this->reset();

            return $row;
        }

        return false;
    }

    /*
      update record in table
      - $table (string) = table name
      - $data (array) =  array data that will be set in database, it's contain field (key) and value (value)
      - $condition (array) || (string) = condition

      return (int) affected_rows;
      ex :
      1. $this->db->update('news',array('title' => 'my title', 'category' =>'otomotif'),'id = 2');
      2. $this->db->update('news',array('title' => 'my title', 'category' =>'otomotif'),array('id' => 2, 'category' => 'otomotif'));
     */

    public function update($table, $data, $condition)
    {
        if (!$table) {
            $table = $this->table;
        } else {
            $prefix = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
            $table  = $prefix . str_replace(array(
                    $prefix . '_',
                    $prefix
                ), '', $table);
        }

        $cond = array();
        if (is_array($condition)) {
            foreach ($condition as $key => $value) {
                $cond[] = ($this->has_operator($key)) ? ($key . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'")) : ($key . " = " . ((is_numeric($value)) ? $value : "'" . $this->escape_string($value) . "'"));
            }
        } elseif ($this->has_operator($condition)) {
            $cond[] = $condition;
        }
        $merge = array();
        $where = '';
        if (count($this->arr_param['where']) > 0 || count($this->arr_param['where_in']) > 0 || count($this->arr_param['like']) > 0 || count($cond) > 0) {
            $merge = array_merge($this->arr_param['where'], $this->arr_param['where_in'], $this->arr_param['like'], $cond);
            $where .= " WHERE " . implode(" AND ", $merge);
        }

        $set = array();
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                $set[] = " $key = '" . $this->escape_string($val) . "'";
            }
            $set = implode(',', $set);
        } else {
            $set = $data;
        }

        $query = "UPDATE " . $table . " SET $set $where";
        $query = $this->clearDiv($query);
        //echo $query."<br/>"; #die();
        //if (!$this->db_connect_id)
        //    $this->connect_db();
        $this->query($query);
        //$exe = mysql_affected_rows($this->db->db_connect_id);
        $exe = mysql_affected_rows($this->master->db_connect_id);
        //$this->query_result = mysql_query($query,$this->db_connect_id);
        //$exe = mysql_affected_rows($this->db_connect_id);
        $this->reset();
        //if ($this->auto_close)
        //    $this->close_db();
        return $exe;
    }

    public function numrow()
    {
        if ($this->query_result) {
            //if (!$this->db_connect_id)
            //    $this->connect_db('SELECT');
            $result = @mysql_num_rows($this->query_result);
            //if ($this->auto_close)
            //    $this->close_db();
            $this->reset();

            return $result;
        } else {
            return false;
        }
    }

    public function affectedrow()
    {
        if ($this->query_result) {
            //if (!$this->db_connect_id)
            //    $this->connect_db('SELECT');
            $result = @mysql_affected_rows($this->query_result);
            //if ($this->auto_close)
            //    $this->close_db();
            $this->reset();

            return $result;
        } else {
            return false;
        }
    }

    public function numfield()
    {
        if ($this->query_result) {
            if (!$this->db_connect_id) {
                $this->connect_db('SELECT');
            }
            $result = @mysql_num_fields($this->query_result);
            if ($this->auto_close) {
                $this->close_db();
            }

            return $result;
        } else {
            return false;
        }
    }

    public function clearDiv($str = '')
    {
        preg_match_all('/[<](\/)?div[^>]*[>]/', $str, $cek);
        foreach ($cek[0] as $c) {
            $str = str_replace($c, ' ', $str);
        }

        return $str;
    }

    public function execute($statement = '', $is_object = true)
    {
        $this->result = $this->db->sql_query($statement, $is_object);
        $this->reset();

        return $this->result;
    }

    public function query($statement = '', $is_object = true)
    {
        if ($statement == '') {
            $statement = $this->create_query();
        }

        $prefix    = $this->db_config['prefix'] == '' ? '' : $this->db_config['prefix'];
        $statement = str_replace('#__', $prefix, $statement);

        // Adi
        if (preg_match('/^select /i', trim($statement))) {
            $this->query_result = $this->db->sql_query($statement, $is_object);
        } else {
            $this->master       = &load_db('master');
            $this->query_result = $this->master->sql_query($statement, $is_object);
        }

        $this->reset();

        return $this;
    }
}
