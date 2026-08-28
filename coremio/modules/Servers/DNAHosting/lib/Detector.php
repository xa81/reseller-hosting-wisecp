<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Detector
{
    private $server;
    private $factory;
    private $cacheGet = null;
    private $cacheSet = null;

    public function __construct(array $server, callable $driverFactory)
    {
        $this->server  = $server;
        $this->factory = $driverFactory;
    }

    public function setCache(callable $get, callable $set)
    {
        $this->cacheGet = $get;
        $this->cacheSet = $set;
        return $this;
    }

    public static function order($port)
    {
        $port = (int) $port;
        if ($port === 8443 || $port === 8880) {
            return array('plesk', 'cpanel');
        }
        return array('cpanel', 'plesk');
    }

    public static function cacheKey(array $server)
    {
        return 'dnahosting_' . sha1(implode('|', array(
            isset($server['ip']) ? $server['ip'] : '',
            isset($server['port']) ? $server['port'] : '',
            isset($server['secure']) ? $server['secure'] : '',
            isset($server['username']) ? $server['username'] : '',
            sha1(isset($server['password']) ? $server['password'] : ''),
        )));
    }

    public function detect()
    {
        $key = self::cacheKey($this->server);

        $cached = $this->readCache($key);
        if ($cached && isset($cached['panel'])) {
            return array(
                'panel' => $cached['panel'],
                'auth'  => isset($cached['auth']) ? $cached['auth'] : '',
            );
        }

        $failures = array();
        foreach (self::order($this->server['port']) as $panel) {
            $driver = call_user_func($this->factory, $panel);
            try {
                $driver->testConnection();
            } catch (Exception $e) {
                $failures[] = strtoupper($panel) . ': ' . $e->getMessage();
                continue;
            }

            $found = array(
                'panel' => $panel,
                'auth'  => method_exists($driver, 'authMode') ? $driver->authMode() : '',
            );
            $this->writeCache($key, $found);
            return $found;
        }

        throw new DNAHosting_Exception(
            'Sunucuda ne cPanel ne Plesk yanit verdi. ' . implode(' | ', $failures)
        );
    }

    private function readCache($key)
    {
        if (!$this->cacheGet) {
            return null;
        }
        try {
            $value = call_user_func($this->cacheGet, $key);
        } catch (Exception $e) {
            return null;
        }
        return is_array($value) ? $value : null;
    }

    private function writeCache($key, array $value)
    {
        if (!$this->cacheSet) {
            return;
        }
        try {
            call_user_func($this->cacheSet, $key, $value);
        } catch (Exception $e) {
            // Onbellek saf optimizasyondur; yazilamamasi tespiti bozmaz.
        }
    }
}
