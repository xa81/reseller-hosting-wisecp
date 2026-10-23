<?php
    namespace WISECP\Modules\Servers\DNAHosting;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    /**
     * Finds out which panel answers on the server record: cPanel/WHM or Plesk.
     *
     * The answer is cached per server and credential for a week, so only the first
     * call after a change pays for the probe. The connection test bypasses the cache.
     */
    class Detector
    {
        private const CACHE_NAME = 'dnahosting';
        private const CACHE_TTL  = 604800;

        /** @var callable */
        private $factory;
        /** @var callable */
        private $t;

        public function __construct(private array $server, callable $driverFactory, callable $translate)
        {
            $this->factory = $driverFactory;
            $this->t       = $translate;
        }

        /** Plesk ports are tried Plesk first; everything else cPanel first. */
        public static function order(int $port): array
        {
            return in_array($port, [8443, 8880], true) ? ['plesk', 'cpanel'] : ['cpanel', 'plesk'];
        }

        public static function cacheKey(array $server): string
        {
            return 'detect_' . sha1(implode('|', [
                $server['ip'] ?? '',
                $server['port'] ?? '',
                $server['secure'] ?? '',
                $server['username'] ?? '',
                sha1((string) ($server['password'] ?? '')),
            ]));
        }

        /** @return array{panel: string, auth: string} */
        public function detect(bool $useCache = true): array
        {
            $key = self::cacheKey($this->server);

            if ($useCache && ($cached = $this->readCache($key)) && isset($cached['panel']))
                return ['panel' => (string) $cached['panel'], 'auth' => (string) ($cached['auth'] ?? '')];

            $failures = [];
            foreach (self::order((int) ($this->server['port'] ?? 0)) as $panel) {
                $driver = call_user_func($this->factory, $panel);
                try {
                    $driver->testConnection();
                } catch (\Throwable $e) {
                    // A TypeError from a driver also means "this panel did not answer".
                    $failures[] = strtoupper($panel) . ': ' . $e->getMessage();
                    continue;
                }

                $found = ['panel' => $panel, 'auth' => method_exists($driver, 'authMode') ? $driver->authMode() : ''];
                $this->writeCache($key, $found);
                return $found;
            }

            throw new ModuleException(($this->t)('error-no-panel', implode(' | ', $failures)));
        }

        private function readCache(string $key): ?array
        {
            try {
                $cache = new \Cache(self::CACHE_NAME);
                if (!$cache->isCached($key)) return null;
                $value = $cache->retrieve($key);
                return is_array($value) ? $value : null;
            } catch (\Throwable) {
                return null;
            }
        }

        private function writeCache(string $key, array $value): void
        {
            try {
                (new \Cache(self::CACHE_NAME))->store($key, $value, self::CACHE_TTL);
            } catch (\Throwable) {
                // The cache is an optimisation; failing to write it does not break detection.
            }
        }
    }
