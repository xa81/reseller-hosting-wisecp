<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Module extends ServerModule
{
    public $force_setup = false;

    private $panel     = null;
    private $drivers   = array();
    private $transport = null;
    private $storage   = array();

    public function __construct($server, $options = array())
    {
        $this->_name = __CLASS__;
        parent::__construct($server, $options);
    }

    protected function define_server_info($server = array())
    {
        if (!class_exists('DNAHosting_Http')) {
            include __DIR__ . DS . 'init.php';
        }
    }

    /** Testler icin: gercek cURL yerine sahte tasiyici enjekte eder. */
    public function useTransport(callable $transport)
    {
        $this->transport = $transport;
        $this->panel     = null;
        $this->drivers   = array();
        return $this;
    }

    private function http()
    {
        $scheme = $this->server['secure'] ? 'https' : 'http';
        $http   = new DNAHosting_Http($scheme . '://' . $this->server['ip'] . ':' . $this->server['port']);

        if ($this->transport) {
            $http->setTransport($this->transport);
        }

        $name = $this->_name;
        $http->setLogger(function ($action, $request, $response) use ($name) {
            Modules::save_log('Servers', $name, $action, $request, $response, '');
        });

        return $http;
    }

    private function makeDriver($panel)
    {
        if ($panel === 'plesk') {
            return new DNAHosting_Plesk($this->server, $this->http());
        }
        return new DNAHosting_Cpanel($this->server, $this->http());
    }

    /** Tespit edilen panel: 'cpanel' veya 'plesk'. Tespit basarisizsa firlatir. */
    public function panel()
    {
        if ($this->panel !== null) {
            return $this->panel;
        }

        $self     = $this;
        $detector = new DNAHosting_Detector($this->server, function ($panel) use ($self) {
            return $self->driverFor($panel);
        });

        $detector->setCache(
            function ($key) { return DNAHosting_Module::cacheRead($key); },
            function ($key, $value) { DNAHosting_Module::cacheWrite($key, $value); }
        );

        $found       = $detector->detect();
        $this->panel = $found['panel'];

        if ($this->panel === 'plesk' && !empty($found['auth'])) {
            $this->driverFor('plesk')->setAuthMode($found['auth']);
        }

        return $this->panel;
    }

    /** Panel basina tek surucu ornegi tutar (istek ici bellek). */
    public function driverFor($panel)
    {
        if (!isset($this->drivers[$panel])) {
            $this->drivers[$panel] = $this->makeDriver($panel);
        }
        return $this->drivers[$panel];
    }

    /** Aktif panelin surucusu. */
    public function driver()
    {
        return $this->driverFor($this->panel());
    }

    public static function cacheRead($key)
    {
        if (!class_exists('Cache')) {
            return null;
        }
        try {
            $cache = new Cache('dnahosting');
            if (!$cache->isCached($key)) {
                return null;
            }
            $value = $cache->retrieve($key);
            return is_array($value) ? $value : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function cacheWrite($key, array $value)
    {
        if (!class_exists('Cache')) {
            return;
        }
        try {
            $cache = new Cache('dnahosting');
            $cache->store($key, $value, 604800);
        } catch (Throwable $e) {
            // Onbellek saf optimizasyon; lisans alan adi tutmazsa sessizce iskalar.
        }
    }

    private function failed(Exception $e)
    {
        $this->error = $e->getMessage();
        return false;
    }

    public function testConnect()
    {
        try {
            $this->panel();
            return true;
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function getPlans()
    {
        try {
            if ($this->panel() === 'plesk') {
                return $this->driver()->listPlans();
            }
            return $this->driver()->listPackages();
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /** Ayri bir metoda cikarildi: testler ADMINISTRATOR sabitini tanimlamadan admin dalini sinayabilsin diye. */
    protected function isAdminArea()
    {
        return defined('ADMINISTRATOR');
    }

    public function use_method($param = '')
    {
        $param  = str_replace('-', '_', $param);
        $prefix = $this->isAdminArea() ? 'use_adminArea_' : 'use_clientArea_';
        if ($param === '') {
            return null;
        }
        if (!method_exists($this, $prefix . $param)) {
            return null;
        }
        return $this->{$prefix . $param}();
    }
}
