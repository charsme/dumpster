<?php
use Illuminate\Database\Capsule\Manager as DB;

function is_closure($t)
{
    return is_object($t) && ($t instanceof Closure);
}

function &load_object($class = '')
{
    static $_classes = array();

    if (isset($_classes[$class])) {
        return $_classes[$class];
    }

    if (file_exists(SYSTEM_PATH.'core/'.$class.'.php')) {
        if (class_exists($class) === false) {
            require_once(SYSTEM_PATH.'core/'.$class.'.php');
        }

        $_classes[$class] = new $class();
        return $_classes[$class];
    }
}

function &load_db($name = "default")
{
    static $_db = array();

    if (isset($_db[$name])) {
        return $_db[$name];
    }

    if (file_exists(SYSTEM_PATH.'core/Database.php')) {
        if (class_exists('Database') === false) {
            require_once(SYSTEM_PATH.'core/Database.php');
        }
        if (class_exists('sql_db') === false) {
            require_once(SYSTEM_PATH.'libraries/database/sql_db.php');
        }
        if (class_exists('Sql_Wrapper') === false) {
            require_once(SYSTEM_PATH.'libraries/database/Sql_Wrapper.php');
        }

        $_db[$name] = Database::connect($name); // Adi
        return $_db[$name];
    }
}

function &load_hooks($class = '')
{
    static $_classes_hooks = array();

    if (isset($_classes_hooks[$class])) {
        return $_classes_hooks[$class];
    }

    if (file_exists(BASE_PATH.'hooks/'.$class.'.php')) {
        if (class_exists($class) === false) {
            require_once(BASE_PATH.'hooks/'.$class.'.php');
        }

        $_classes_hooks[$class] = new $class();
        return $_classes_hooks[$class];
    }
}

if (!function_exists('app_load_mongo')) {

    /**
     * load mongo object
     * @return [object] mongo object
     */
    function app_load_mongo()
    {
        return app_load_object('Cimongo', SYSTEM_PATH.'libraries/database/mongodb/Cimongo.php');
    }
}

if (!function_exists('app_load_mongo7')) {

    /**
     * load mongo object
     * @return [object] mongo object
     */
    function app_load_mongo7($collection = null, $use_manager = false)
    {
        static $_app_mongo_object = [];
        
        $class = 'Mongo7'. Config::App()->get('mongo_host') .'_'. $collection .'_'. $use_manager;
        if (isset($_app_mongo_object[$class])) {
            return $_app_mongo_object[$class];
        }

        if ($use_manager === false) {
            if ($collection && $collection != null) {
                $_app_mongo_object[$class] = (new \MongoDB\Client(mongo7_connection_string()))->{Config::App()->get('mongo_db')}->$collection;
            } else {
                $_app_mongo_object[$class] = (new \MongoDB\Client(mongo7_connection_string()))->{Config::App()->get('mongo_db')};
            }
        } else {
            try {
                $_app_mongo_object[$class] = new MongoDB\Driver\Manager(mongo7_connection_string());
            } catch (MongoException $e) {
                show_error("Connect to MongoDB failed: {$e->getMessage()}", 500);
            } catch (MongoCursorException $e) {
                show_error("Connect to MongoDB failed: {$e->getMessage()}", 500);
            }
        }
        
        return $_app_mongo_object[$class];
    }
}

if (!function_exists('mongo7_connection_string')) {
    /**
    * MongoDB for PHP 7 Connection String
    */
    function mongo7_connection_string()
    {
        if (Config::App()->get('mongo_user')) {
            return "mongodb://". Config::App()->get('mongo_user') .":". Config::App()->get('mongo_pass') ."@". Config::App()->get('mongo_host') .":". Config::App()->get('mongo_port').'/'.Config::App()->get('mongo_db');
        } else {
            return "mongodb://". Config::App()->get('mongo_host') .":". Config::App()->get('mongo_port').'/'.Config::App()->get('mongo_db');
        }
    }
}

if (!function_exists('app_load_object')) {

    /**
     *
     * @param type $class --> class, static
     * @param type $filename --> filename of library
     *
     * @return object
     */
    function app_load_object($class, $filename)
    {
        static $_app_object = array();

        if (isset($_app_object[$class])) {
            return $_app_object[$class];
        }

        if (is_file($filename)) {
            if (class_exists($class) === false) {
                include($filename);
            }

            $_app_object[$class] = new $class();

            return $_app_object[$class];
        }
    }
}

function cache($name, $value = false, $interval = 0, $encodeUTF8 = false)
{
    if ($value !== false) {
        if ($interval == 0) {
            $interval = WebCache::App()->get_config('cachetime_short');
        }
        if (is_closure($value)) {
            $ret = WebCache::App()->get_cache($name);
            if (!$ret) {
                $ret = $value();
                if ($encodeUTF8) {
                    $ret = toUTF8($ret);
                }
                WebCache::App()->set_cache($name, $ret, $interval);
            }
        } else {
            $ret = $value;
            if ($value === null) {
                WebCache::App()->delete_cache($name);
            } else {
                if ($encodeUTF8) {
                    $ret = toUTF8($ret);
                }
                WebCache::App()->set_cache($name, $ret, $interval);
            }
        }
        if ($encodeUTF8 && $ret) {
            $ret = toLatin1($ret);
        }

        return $ret;
    } else {
        $ret = WebCache::App()->get_cache($name);
        if ($ret) {
            return $ret;
        } else {
            return false;
        }
    }
}

function toUTF8($var)
{
    return _convert_charset($var);
}

function toLatin1($var)
{
    return _convert_charset($var, 'UTF-8', 'ISO-8859-1');
}

function _convert_charset($var, $reverse = false)
{
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            $var[$key] = _convert_charset($value, $reverse);
        }
    } elseif (is_object($var)) {
        /*$keys = array_keys(get_object_vars($var));

        foreach ($keys as $key) {
            if (is_writeable())
            $var->$key = _convert_charset($var->$key, $reverse);
        }*/
    } else {
        if (is_string($var)) {
            return ($reverse) ? mb_convert_encoding($var, 'ISO-8859-1', 'UTF-8') : mb_convert_encoding($var, 'UTF-8', 'ISO-8859-1');
        } else {
            return $var;
        }
    }
    return $var;
}

if (!function_exists('changeTimezone')) {
    function changeTimezone($str_date, $src_format = 'Y-m-d H:i:s', $ret_format = 'Y-m-d H:i:s', $src_timezone = false, $dest_timezone = false)
    {
        if ($src_timezone) {
            $src_timezone = Config::App()->get('site_timezone');
        }
        if ($dest_timezone) {
            $dest_timezone = Config::App()->get('mysql_timezone');
        }

        if (!$src_timezone || !$dest_timezone) {
            return $str_date;
        }

        $src_tz = new DateTimeZone($src_timezone);
        $dest_tz = new DateTimeZone($dest_timezone);

        $dt = DateTime::createFromFormat($src_format, $str_date, $src_tz);
        $dt->setTimeZone($dest_tz);

        return $dt->format($ret_format);
    }
}

if (!function_exists('queryLog')) {
    function queryLog()
    {
        $logs = DB::getQueryLog();
        require(CONFIG_PATH.'database.php');
        if (isset($database['default'])) {
            unset($database['default']);
        }
        if (isset($database['master'])) {
            unset($database['master']);
        }
        if (count($database) && is_array($database)) {
            foreach ($database as $key => $value) {
                $logs = array_merge($logs, DB::connection($key)->getQueryLog());
            }
        }
        return $logs;
    }
}

if (!function_exists('closeDB')) {
    function closeDB()
    {
        if (class_exists('Database')) {
            Database::close();
        }
    }
}

if (!function_exists('show_error')) {

    /**
     * show_error()
     *
     * @param string $message
     * @return
     */
    function show_error($message = "", $code = 500)
    {
        @header('HTTP/1.1 '.$code.' Internal Server Error');
        echo $message;
        closeDB();
        exit();
    }
}

if (!function_exists('abort')) {
    function abort($code = 404, $message = '')
    {
        switch ($code) {
            case 444:
                echo json_encode(['error' => 444, 'message' => 'session expire, please relogin.']);
                break;
            case 401:
                header("HTTP/1.1 401 Unauthorized");
                break;
            case 400:
                header("HTTP/1.1 400 Bad Request");
                Controller::App()->view('_system_/400', array('message' => $message), false, false);
                break;
            case 404:
                header("HTTP/1.0 404 Not Found");
                Controller::App()->view("_system_/404", array('message' => $message), false, false);
                break;
            case 503:
                header("HTTP/1.0 503 Service Unavailable");
                echo $message;
                break;
            default:
                Output::App()->show_error($message, $code);
                break;
        }
        closeDB();
        exit();
    }
}

if (!function_exists('redirect')) {
    function redirect($route = '', $param = '')
    {
        $url = (preg_match('#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i', $route)) ? $route : url($route, $param);
        closeDB();
        Output::App()->redirect($url);
    }
}

if (!function_exists('url')) {
    function url($route = '', $param = '', $config_url = 'rel_url')
    {
        $param = (is_array($param) && count($param)) ? '&'.http_build_query($param) : '';
        $base_url = Config::App()->get($config_url);
        if ($base_url === false) {
            $base_url = Config::App()->get('base_url');
        }
        $result = $base_url.$route;
        return (substr($result, -1, 1) !== '/' && $param) ? $result.'/'.$param :  $result.$param;
    }
}

if (!function_exists('newsByTag')) {
    function newsByTag($tag_url, $mongo_prefix, $page = 1, $limit = 10, $offset = null, $total_row = true)
    {
        $total = 0;
        $row = [];
        if ($offset === null) {
            $offset = (($page - 1) * $limit);
        }
        if (!Config::App()->get('cache')['mongocache_active']) {
            $domain = DB::select("SELECT domain_id FROM ".DB::getTablePrefix()."domains WHERE mongo_table_prefix=:prefix LIMIT 0,1", ['prefix' => $mongo_prefix]);
            if (isset($domain[0])) {
                $tag = DB::select("SELECT id, tag, tag_url FROM ".DB::getTablePrefix()."tags WHERE tag_url = :tag_url AND tags_domain_id = :domain_id LIMIT 0,1", ['tag_url' => $tag_url, 'domain_id' => $domain[0]->domain_id]);
                if (isset($tag[0])) {
                    $where = "news_date_publish <= NOW() AND news_domain_id = :domain_id AND news_level = :level AND (select count(*) from `".DB::getTablePrefix()."tags` inner join `".DB::getTablePrefix()."tag_news` on `".DB::getTablePrefix()."tags`.`id` = `".DB::getTablePrefix()."tag_news`.`tag_news_tag_id` where `".DB::getTablePrefix()."tag_news`.`tag_news_news_id` = `".DB::getTablePrefix()."news`.`news_id` and `id` = :tag_id) >= 1";
                    if ($total_row) {
                        $count = DB::select("
                            SELECT
                                COUNT(*) as total
                            FROM
                                ".DB::getTablePrefix()."news
                            WHERE
                                 ".$where, [':domain_id' => $domain[0]->domain_id, 'level' => 1, 'tag_id' => $tag[0]->id]);
                        $total = $count[0]->total;
                    }
                    if ($total || !$total_row) {
                        $row = DB::select("
                            SELECT
                                news_id, news_title, news_type, news_entry, news_synopsis, news_url, news_date_publish, news_category, news_image, news_image_thumbnail, news_image_potrait
                            FROM
                                ".DB::getTablePrefix()."news
                            WHERE
                                 ".$where."
                            ORDER BY news_date_publish DESC LIMIT :offset, :limit", [':domain_id' => $domain[0]->domain_id, 'level' => 1, 'tag_id' => $tag[0]->id, 'offset' => $offset, 'limit' => $limit]);
                        if (is_array($row)) {
                            foreach ($row as $k => $v) {
                                $tmp = [
                                    'tag_alphabet'         => substr($tag_url, 0, 1),
                                    'tag_id'               => $tag[0]->id,
                                    'tag_name'             => $tag[0]->tag,
                                    'tag_url'              => $tag[0]->tag_url,
                                    'news_id'              => $v->news_id,
                                    'news_title'           => $v->news_title,
                                    'news_type'            => $v->news_type,
                                    'news_entry'           => $v->news_entry,
                                    'news_synopsis'        => $v->news_synopsis,
                                    'news_url'             => $v->news_url,
                                    'news_date_publish'    => $v->news_date_publish,
                                    'news_category'        => $v->news_category,
                                    'news_image'           => $v->news_image,
                                    'news_image_thumbnail' => $v->news_image_thumbnail,
                                    'news_image_secondary' => $v->news_image_potrait,
                                    'domain'               => $domain[0]->domain_id,
                                ];
                                $row[$k] = $tmp;
                            }
                        }
                    }
                }
            }
        } else {
            if (PHP_VERSION < 7) {
                $mongo = app_load_mongo();
                $where = ['tag_url' => $tag_url, 'news_date_publish' => ['$lt' => time()]];
                $mongo = $mongo->where($where);
                if ($total_row) {
                    $total = clone $mongo;
                    $total = $total->count_all_results($mongo_prefix.'tag');
                }
                $row = $mongo->order_by(array('news_date_publish' => 'DESC'))->get($mongo_prefix.'tag', $limit, $offset)->result_array();
            } else {
                $collection = $mongo_prefix.'tag';
                $cimongo = app_load_mongo7($collection);
                $where = ['tag_url' => $tag_url, 'news_date_publish' => ['$lt' => time()]];
                if ($total_row) {
                    $total = $cimongo->count($where);
                }
                $cursor = $cimongo->find($where, ['limit' => $limit, 'skip' => $offset, 'sort' => ['news_date_publish' => -1]]);
                $row = [];
                foreach ($cursor as $document) {
                    $row[] = json_decode(json_encode($document), true); //(array) $document;
                }
            }
        }
        return $total_row ? ['total' => $total, 'data' => $row] : ['data' => $row];
    }
}

if (!function_exists('newsRate')) {
    function newsRate($rate_number, $mongo_prefix, $page = 1, $limit = 10, $order_by = 'news_date_publish')
    {
        $mongo = app_load_mongo();
        $where = ['rate_'.$rate_number.'_counter' => ['$gt' => 0], 'news_date_publish' => ['$lt' => time()]];
        $mongo = $mongo->where($where);
        $total = clone $mongo;
        $total  = $total->count_all_results($mongo_prefix.'news_rate');
        $row = $mongo->order_by(array($order_by => 'DESC'))->get($mongo_prefix.'news_rate', $limit, (($page - 1) * $limit))->result_array();
        return ['total' => $total, 'data' => toLatin1($row)];
    }
}

if (!function_exists('newsPopular')) {
    function newsPopular($type, $mongo_prefix, $date_start = false, $date_end = false, $limit = 10, $condition = false)
    {
        $row = [];
        if (!Config::App()->get('cache')['mongocache_active']) {
            $domain = DB::select("SELECT domain_id FROM ".DB::getTablePrefix()."domains WHERE mongo_table_prefix=:prefix LIMIT 0,1", ['prefix' => $mongo_prefix]);
            if (isset($domain[0])) {
                $additional_where = [];
                $additional_param = [];
                $param = ['domain_id' => $domain[0]->domain_id, 'date_start' => date('Y-m-d H:i:s', $date_start), 'date_end' => date('Y-m-d H:i:s', $date_end), 'limit' => $limit];
                if (is_array($condition)) {
                    foreach ($condition as $key => $value) {
                        $additional_where[] = "$key = :$key";
                        $additional_param[$key] = $value;
                    }
                }
                if (count($additional_where)) {
                    $param = array_merge($param, $additional_param);
                    $additional_where = "AND ".implode(' AND ', $additional_where);
                } else {
                    $additional_where = "";
                }
                switch ($type) {
                    case 'share':
                        $order_field = "fbinfo_share";
                        break;
                    case 'like':
                        $order_field = "fbinfo_like";
                        break;
                    case 'comment':
                        $order_field = "fbinfo_comment";
                        break;
                    default:
                        $order_field = "jsview_counter";
                        break;
                }
                $row = DB::select("
                    SELECT
                        news_id,
                        news_title,
                        news_type,
                        news_entry,
                        news_synopsis,
                        news_url,
                        news_date_publish,
                        news_category,
                        news_image,
                        news_image_thumbnail,
                        news_image_potrait as news_image_secondary,
                        jsview_counter as pageview,
                        fbinfo_share as share,
                        fbinfo_like as `like`,
                        fbinfo_comment as comment,
                        news_domain_id as domain
                    FROM
                        ".DB::getTablePrefix()."news
                    LEFT JOIN
                        ".DB::getTablePrefix()."jsview on news_id=jsview_news_id
                    LEFT JOIN
                        ".DB::getTablePrefix()."fbinfo on news_id=fbinfo_news_id
                    WHERE
                        news_date_publish <= NOW() AND
                        (news_date_publish BETWEEN :date_start AND :date_end) AND
                        news_level = 1 AND
                        news_domain_id = :domain_id
                        ".$additional_where."
                    ORDER BY ".$order_field." DESC, news_date_publish DESC LIMIT :limit
                ", $param);
                if (count($row)) {
                    $id_news = [];
                    $tmp = [];
                    foreach ($row as $k => $v) {
                        $tmp[$v->news_id] = (array) $v;
                        $tmp[$v->news_id]['tags'] = [];
                        $tmp[$v->news_id]['tag_id'] = "";
                        $tmp[$v->news_id]['tag_name'] = "";
                        $tmp[$v->news_id]['tag_url'] = "";
                        $id_news[] = $v->news_id;
                    }
                    $row_tag = DB::select("
                        SELECT
                            id as tag_id,
                            tag as tag_name,
                            tag_url,
                            tag_news_news_id as news_id
                        FROM
                            ".DB::getTablePrefix()."tag_news
                        JOIN
                            ".DB::getTablePrefix()."tags ON tag_news_tag_id = id
                        WHERE
                            tag_news_news_id IN (".implode(',', $id_news).")
                        ORDER BY news_id ASC
                    ");
                    if (count($row_tag)) {
                        foreach ($row_tag as $k => $v) {
                            if (!count($tmp[$v->news_id]['tags'])) {
                                $tmp[$v->news_id]['tag_id'] = $v->tag_id;
                                $tmp[$v->news_id]['tag_name'] = $v->tag_name;
                                $tmp[$v->news_id]['tag_url'] = $v->tag_url;
                            }
                            $tmp[$v->news_id]['tags'][] = (array) $v;
                        }
                    }
                    foreach ($row as $k => $v) {
                        $row[$k] = $tmp[$v->news_id];
                    }
                }
            }
        } else {
            if (PHP_VERSION < 7) {
                $mongo = app_load_mongo();
                $where = [];
                if ($date_start) {
                    $where['news_date_publish']['$gt'] = $date_start;
                }
                if ($date_end) {
                    $where['news_date_publish']['$lt'] = $date_end;
                }
                if ($condition && is_array($condition)) {
                    foreach ($condition as $key => $value) {
                        if (is_array($value)) {
                            $mongo = $mongo->where_in($key, $value);
                        } else {
                            $where[$key] = $value;
                        }
                    }
                }
                if ($where) {
                    $mongo = $mongo->where($where);
                }

                $row = toLatin1($mongo->order_by(array($type => 'DESC'))->get($mongo_prefix.'trendings', $limit)->result_array());
            } else {
                $collection = $mongo_prefix.'trendings';
                $cimongo = app_load_mongo7($collection);
                $where = [];
                if ($date_start) {
                    $where['news_date_publish']['$gt'] = $date_start;
                }
                if ($date_end) {
                    $where['news_date_publish']['$lt'] = $date_end;
                }
                if ($condition && is_array($condition)) {
                    foreach ($condition as $key => $value) {
                        if (is_array($value)) {
                            $where[$key]['$in'] = $value;
                        } else {
                            $where[$key] = $value;
                        }
                    }
                }
                $cursor = $cimongo->find($where, ['limit' => $limit, 'sort' => [$type => -1]]);
                $row = [];
                foreach ($cursor as $document) {
                    $row[] = json_decode(json_encode($document), true); //(array) $document;
                }
            }
        }
        return $row;
    }
}

if (!function_exists('saveContactUs')) {
    function saveContactUs($name, $email, $message, $domain_id)
    {
        $data = [
            'date'  => time(),
            'name'  => trim($name),
            'email' => trim($email),
            'message' => trim($message),
            'domain_id' => $domain_id
        ];
        $c = new Controller();
        $v = $c->validator($data);
        $v->rule('required', ['name', 'email', 'message']);
        $v->rule('email', ['email']);

        if ($v->validate()) {
            $data['story'] = $data['message'];
            unset($data['message']);
            $cimongo = app_load_mongo();
            $cimongo->insert('contact_us', $data);
            $id = get_object_vars($cimongo->insert_id())['$id'];
            return $id;
        } else {
            return $v->errors();
        }
    }
}

/*
* Insert customer service.
* param	$data		array	array of input data
*		list param
*			[
*				'name'        => string|required
*				'email_from'  => string|required|email
*				'email_to'    => string|required|email
*				'address'     => string
*				'phone'       => numeric|required|maxLength[20]
*				'subjects'    => string|required
*				'contents'    => string|required
*				'type'        => required|string || numeric
*							list option of string ['Contact Us', 'Advertise', 'Complaint']
*							list option of numeric [1 (Contact Us), 2 (Advertise), 3 (complaint)]
*			]
* param	$domain_id	int		site domain_id
* @return	array|string	Array of validation error message OR inserted ID
*/
if (!function_exists('insertCustomerService')) {
    function insertCustomerService($data=[], $domain_id)
    {
        $availableType = [
            1 => 'Contact Us',
            2 => 'Advertise',
            3 => 'Complaint',
        ];
        
        $data = [
            'date'       => time(),
            'name'       => isset($data['name']) ? trim($data['name']) : '',
            'email_from' => isset($data['email_from']) ? trim($data['email_from']) : '',
            'email_to'   => isset($data['email_to']) ? trim($data['email_to']) : '',
            'address'    => isset($data['address']) ? trim($data['address']) : '',
            'phone'      => isset($data['phone']) ? trim($data['phone']) : '',
            'subject'    => isset($data['subject']) ? trim($data['subject']) : '',
            'contents'   => isset($data['contents']) ? trim($data['contents']) : '',
            'type'       => isset($data['type']) && is_numeric(@$data['type']) && array_key_exists(@$data['type'], $availableType) ? $availableType[$data['type']] : (isset($data['type']) && in_array(ucwords(strtolower(@$data['type'])), $availableType) ? ucwords(strtolower(@$data['type'])): ''),
            'domain_id'  => $domain_id
        ];
        $c = new Controller();
        $v = $c->validator($data);
        
        $v->rule('required', ['contents', 'name', 'email_to', 'email_from', 'subject', 'phone', 'type']);
        $v->rule('email', ['email_to', 'email_from']);
        $v->rule('numeric', ['phone']);
        $v->rule('lengthMax', ['phone'], 20);

        if ($v->validate()) {
            $cimongo = app_load_mongo();
            $cimongo->insert('customer_service', $data);
            $id = get_object_vars($cimongo->insert_id())['$id'];
            return $id;
        } else {
            return $v->errors();
        }
    }
}

if (!function_exists('getShortenedURLFromID')) {
    function getShortenedURLFromID($integer)
    {
        $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $length = strlen($base);
        $out = '';
        while ($integer > $length - 1) {
            $out = @$base[fmod($integer, $length)] . $out;
            $integer = floor($integer / $length);
        }
        $ret = @$base[$integer] . $out;
        return $ret;
    }
}

if (!function_exists('getIDFromShortenedURL')) {
    function getIDFromShortenedURL($string)
    {
        $base = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $length = strlen($base);
        $size = strlen($string) - 1;
        $string = str_split($string);
        $out = strpos($base, array_pop($string));
        foreach ($string as $i => $char) {
            $out += strpos($base, $char) * pow($length, $size - $i);
        }
        return $out;
    }
}

if (!function_exists('redirectMigrateNews')) {
    function redirectMigrateNews($news_url)
    {
        $mongo_collection = 'migrate_news_log';
        $row = false;
        
        if (!Config::App()->get('cache')['mongocache_active']) {
            $row = DB::select("SELECT * FROM ".DB::getTablePrefix()."migrate_log WHERE migrate_news_url=:news_url ORDER BY migrate_datetime DESC LIMIT 0,1", ['news_url' => $news_url]);
            if (isset($row[0])) {
                $row['migrate_new_news_url_full'] = $row[0]->migrate_new_news_url_full;
                $row['migrate_new_domain_id'] = $row[0]->migrate_new_domain_id;
            }
        } else {
            if (PHP_VERSION < 7) {
                $mongo = app_load_mongo();
                $where = ['migrate_news_url' => $news_url];
                $mongo = $mongo->where($where);
                $row = $mongo->order_by(array('migrate_datetime' => 'DESC'))->get($mongo_collection, 1, 0)->result_array();
                $row = $row[0];
            } else {
                $cimongo = app_load_mongo7($mongo_collection);
                $where = ['migrate_news_url' => $tag_url];
                $cursor = $cimongo->find($where, ['limit' => 1, 'skip' => 1, 'sort' => ['migrate_datetime' => -1]]);
                foreach ($cursor as $document) {
                    $row = json_decode(json_encode($document), true); //(array) $document;
                }
            }
        }
        //filter custom case, tidak akan diredirect jika domain_id yang dituju adalah domain_id dari channel yg sedang dibuka
        if ($row && @$row['migrate_new_domain_id'] != Config::App()->get('domain_id')) {
            header("Location: ". $row['migrate_new_news_url_full']);
            die();
        } else {
            return false;
        }
    }
}

if (!function_exists('redirectShortURL')) {
    function redirectShortURL($url, $domain_id, $external = false)
    {
        $domain_id = intval($domain_id);
        $mongo = app_load_mongo();
        $mongo_data = $mongo->where(['short_url' => $url, 'domain_id' => $domain_id, 'external' => $external])->get('news_url', 1)->result_array();
        if (!$mongo_data || (isset($mongo_data[0]['created_date']) && isset($mongo_data[0]['exist']) && $mongo_data[0]['exist'] == false && (time() - $mongo_data[0]['created_date']) >= 300)) {
            $valid = true;
            if (!preg_match('|^[0-9a-zA-Z]{1,7}$|', $url)) {
                $valid = false;
            }
            if ($valid) {
                $shortened_id = getIDFromShortenedURL($url);

                if ($external) {
                    $row = DB::select("SELECT * FROM ".DB::getTablePrefix()."external_shortener WHERE id=:id", ['id' => $shortened_id]);
                    if (is_array($row) && count($row)) {
                        $mongo_data = [
                            'news_id'      => null,
                            'exist'        => true,
                            'short_url'    => $url,
                            'full_url'     => $row[0]->longurl,
                            'external'     => $external,
                            'domain_id'    => $domain_id,
                            'created_date' => time()
                        ];
                    } else {
                        $valid = false;
                    }
                } else {
                    if ($domain_id == 8) {
                        $logs = DB::select("SELECT * FROM ".DB::getTablePrefix()."migrate_log_id WHERE domain_id = :domain_id AND type = :type AND source_id = :id", ['domain_id' => $domain_id, 'type' => 'news', 'id' => $shortened_id]);
                        if (is_array($logs) && count($logs)) {
                            $shortened_id = $logs[0]->target_id;
                        }
                    }

                    $news = DB::select("SELECT news_url, news_type, news_entry, news_date_publish, news_category, news_id, news_domain_id FROM ".DB::getTablePrefix()."news WHERE news_id = :id AND news_domain_id = :domain", ['id' => $shortened_id, 'domain' => $domain_id]);
                    if (is_array($news) && count($news)) {
                        // $this->_log($shortened_id);
                        require_once(SYSTEM_PATH.'libraries/UrlGenerator.php');
                        $generator  = new UrlGenerator();
                        $news       = (array) $news[0];
                        $news       = $generator->generate($news);
                        $mongo_data = [
                            'news_id'      => intval($news['news_id']),
                            'exist'        => true,
                            'external'     => $external,
                            'short_url'    => $url,
                            'full_url'     => $news['news_url_full'],
                            'domain_id'    => intval($news['news_domain_id']),
                            'created_date' => time()
                        ];
                    } else {
                        $valid = false;
                    }
                }
            }
            if (!$valid) {
                $mongo_data = [
                    'news_id'      => null,
                    'exist'        => false,
                    'short_url'    => $url,
                    'external'     => $external,
                    'full_url'     => "",
                    'domain_id'    => $domain_id,
                    'created_date' => time()
                ];
            }
            // save to mongo
            $mongo      = app_load_mongo();
            $mongo->where(['short_url' => $url, 'domain_id' => $domain_id, 'external' => $external])->update('news_url', $mongo_data);
        } else {
            $mongo_data = $mongo_data[0];
        }

        if (!$mongo_data['exist']) {
            abort(404);
        } else {
            redirect($mongo_data['full_url']);
        }
    }
}

function api($url, $param = [], $method = 'get', $auth = false, $headers = false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($method == 'post') {
        $fields = is_array($param) ? http_build_query($param) : $param;
        curl_setopt($ch, CURLOPT_POST, true);
        // curl_setopt($ch,CURLOPT_POST, count($param));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($auth) {
        curl_setopt($ch, CURLOPT_USERPWD, $auth);
    }
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
