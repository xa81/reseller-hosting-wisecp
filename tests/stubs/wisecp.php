<?php
// Bu dosya WiseCP cekirdeginin AYNISI DEGILDIR. Yalnizca DNAHosting_Module'un
// kullandigi sozlesmeyi tasiyan bir test ikizidir.

class ServerModule
{
    protected $server = null;
    public $force_setup = true;
    public $area_link = null;
    public $page = false;
    public $_name = null;
    public $order = array();
    public $product = array();
    public $user = array();
    public $config = array();
    public $options = array();
    public $lang = null;
    public $error = null;

    public function __construct($server, $options = array())
    {
        $parts       = explode('_Module', $this->_name);
        $this->_name = $parts[0];
        $this->server = $server;

        $external      = isset($options['config']) ? $options['config'] : $options;
        $this->options = $options;
        $this->config  = array_merge(Modules::Config('Servers', $this->_name), $external);
        $this->lang    = Modules::Lang('Servers', $this->_name);

        if ($server) {
            $this->define_server_info($server);
        }
    }

    public function get_page($file, $vars = array()) { return ''; }
    protected function encode_str($s = '') { return 'ENC:' . $s; }
    protected function decode_str($s = '') { return strpos($s, 'ENC:') === 0 ? substr($s, 4) : $s; }
}

class Modules
{
    public static $logs = array();
    public static function Config($type, $name)
    {
        return include dirname(__DIR__) . '/../coremio/modules/Servers/' . $name . '/config.php';
    }
    public static function Lang($type, $name, $lang = 'en')
    {
        return include dirname(__DIR__) . '/../coremio/modules/Servers/' . $name . '/lang/en.php';
    }
    public static function save_log($type, $module, $action, $request = '', $response = '', $processed = '')
    {
        self::$logs[] = array($action, $request, $response);
        return true;
    }
    public static function getPage($type, $name, $page, $vars = array()) { return ''; }
}

class Crypt
{
    public static function encode($v, $k = '') { return 'ENC:' . $v; }
    public static function decode($v, $k = '') { return strpos($v, 'ENC:') === 0 ? substr($v, 4) : false; }
}

class Config
{
    public static function get($k) { return 'test-key'; }
}
