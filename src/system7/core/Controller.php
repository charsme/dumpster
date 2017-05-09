<?php

/**
 * Controller
 *
 * @package
 * @author unisbadri
 * @copyright 2011
 * @version $Id$
 * @access public
 */
class Controller
{
    private static $instance;
    public $config = array();
    public $db = null;
    protected $model = null;

    /**
     * Controller::__construct()
     *
     * @access public
     * @return
     */
    public function __construct()
    {
        $CFG = &load_object('Config');
        $this->config = $CFG->get();

        $this->uri = &load_object('Router');

        $this->autoload();
        self::$instance = &$this;
    }

    public static function &App()
    {
        if (!self::$instance) {
            self::$instance = new Controller();
        }
        return self::$instance;
    }

    /**
     * Controller::file()
     *
     * Digunakan untuk meload file berdasarkan path yang diberikan.
     *
     * @access private
     * @param mixed $file_names
     * @return
     */
    public function file($file_names = null)
    {
        if ($file_names != null) {
            if (is_array($file_names)) {
                foreach ($file_names as $file) {
                    if (file_exists($file)) {
                        include_once($file);
                    }
                }
            } elseif (is_string($file_names)) {
                if (file_exists($file_names)) {
                    include_once($file_names);
                }
            }
        }
    }

    /**
     * Controller::autoload()
     *
     * Digunakan untuk meload library, helper dan model berdasarkan path yang diberikan.
     *
     * @access private
     * @param mixed $file_names
     * @return
     */
    public function autoload()
    {
        $this->library($this->config["libraries"]);
        $this->model($this->config["models"]);
        $this->helper($this->config["helpers"]);
    }

    // ----------------------------------------------------------------------------------------------------------------------

    /**
     * Controller::helper()
     *
     * Digunakan untuk meload helper dari folder helper.
     *
     * @access protected
     * @param mixed $helper_name
     * @return
     */
    public function helper($helper_name = null)
    {
        if ($helper_name != null) {
            if (is_array($helper_name)) {
                foreach ($helper_name as $file) {
                    if (file_exists(HELPER_PATH . $file . '.php')) {
                        include_once(HELPER_PATH . $file . '.php');
                    }
                }
            } elseif (is_string($helper_name)) {
                if (file_exists(HELPER_PATH . $helper_name . '.php')) {
                    include_once(HELPER_PATH . $helper_name . '.php');
                }
            }
        }
    }

    // ----------------------------------------------------------------------------------------------------------------------

    /**
     * Controller::library()
     *
     * Digunakan untuk meload library dari folder library.
     *
     * @access protected
     * @param mixed $library_name
     * @return
     */
    public function library($library_name = null)
    {
        if ($library_name != null) {
            if (is_array($library_name)) {
                foreach ($library_name as $file) {
                    if (!class_exists($file, false) && file_exists(LIBRARY_PATH . $file . '.php')) {
                        include_once(LIBRARY_PATH . $file . '.php');
                    }
                    if (class_exists($file)) {
                        $this->$file = new $file();
                    }
                }
            } elseif (is_string($library_name)) {
                if (!class_exists($library_name, false) && file_exists(LIBRARY_PATH . $library_name . '.php')) {
                    include_once(LIBRARY_PATH . $library_name . '.php');
                }
                if (class_exists($library_name)) {
                    $this->$library_name = new $library_name();
                }
            }
        }
    }

    // ----------------------------------------------------------------------------------------------------------------------

    /**
     * Controller::model()
     *
     * Digunakan untuk meload model dari folder model.
     *
     * @access protected
     * @param mixed $model_name
     * @return
     */
    public function model($model_name = null, $identifier = null, $onlyInclude = false)
    {
        if (!class_exists('Model')) {
            require(SYSTEM_PATH . "core/Model.php");
            require(BASE_PATH . "core/CModel.php");
        }

        if ($model_name != null) {
            if (is_array($model_name)) {
                $model = new stdClass();
                foreach ($model_name as $file) {
                    if (file_exists(MODEL_PATH . $file . '.php')) {
                        include_once(MODEL_PATH . $file . '.php');
                        if ($onlyInclude === false && class_exists($file)) {
                            $file = strtolower($file);
                            $this->$file = new $file();
                        }
                    }
                }
                return $model;
            } elseif (is_string($model_name)) {
                if (file_exists(MODEL_PATH . $model_name . '.php')) {
                    include_once(MODEL_PATH . $model_name . '.php');
                    if ($onlyInclude === false && class_exists($model_name)) {
                        $identifier = (!$identifier) ? $model_name : $identifier;
                        $model_name = strtolower($model_name);
                        $this->$identifier = new $model_name();
                    }
                }
            }
        }
    }

    // ----------------------------------------------------------------------------------------------------------------------

    /**
     * Controller::view()
     *
     * Fungsi ini digunakan untuk memperoleh view files yang sudah berisi data dinamik, data untuk view file diset
     * di fungsi Controller::add_data_set()
     *
     * @access protected
     * @see Controller::add_data_set()
     * @param mixed $filename
     * @param array $data
     * @param bool $return
     * @return string Isi dari view files atau pesan error bila file view tidak ditemukan.
     */
    public function view($filename, $data = array(), $return = false, $is_cache = true)
    {
        $OUT = Output::App();
        if ($return) {
            return $OUT->view($filename, $data, true);
        } else {
            $OUT->view($filename, $data, false, $is_cache);
        }
    }

    // ----------------------------------------------------------------------------------------------------------------------

    /**
     * Controller::query()
     *
     * Shortcut untuk melakukan query sql
     *
     * @access public
     * @param string $query
     * @param boolean $is_object
     * @return mixed array data atau false jika gagal
     */
    public function query($query, $is_object = true)
    {
        $this->db->sql_freeresult();
        return $this->db->query($query, $is_object);
    }

    /**
     * fungsi untuk inisialisasi validator
     *
     * @param  [array] $data data yang akan di validasi
     * @return [objext]      validator object
     */
    public function validator($data)
    {
        return new Valitron\Validator($data);
    }

    public function __destruct()
    {
    }
}
