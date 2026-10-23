<?php
    namespace WISECP\Modules\Servers\DNAHosting;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    /**
     * WHM API 1 driver, authenticated as a reseller with an API token.
     *
     * Every call runs with the reseller's own ACL: accounts are created inside the
     * reseller's quota and the reseller can only see and touch accounts it owns.
     */
    class Cpanel
    {
        private const READ_ONLY = ['listaccts', 'listpkgs', 'accountsummary', 'showbw', 'version', 'create_user_session'];

        private ?array $packages = null;
        /** @var callable */
        private $t;

        public function __construct(private array $server, private ApiClient $http, callable $translate)
        {
            $this->t = $translate;
            $this->http->addSecret((string) ($server['password'] ?? ''));

            // The create_user_session response IS a live credential. Only the token
            // fields are hidden; metadata stays readable for diagnosis.
            $this->http->addResponseRedaction('create_user_session', [
                '/("(?:url|session|cp_security_token)"\s*:\s*")[^"]*(")/i' => '$1***$2',
                '/cpsess[0-9]+/i'                                         => 'cpsess***',
            ]);
        }

        public function call(string $function, array $args = []): array
        {
            $read    = in_array($function, self::READ_ONLY, true);
            $query   = http_build_query(['api.version' => 1] + $args);
            $headers = ['Authorization: WHM ' . ($this->server['username'] ?? '') . ':' . ($this->server['password'] ?? '')];

            $this->http->setTimeout($read ? 30 : 400);

            if ($read)
                $result = $this->http->send('GET', '/json-api/' . $function . '?' . $query, $headers, null, $function);
            else {
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $result    = $this->http->send('POST', '/json-api/' . $function, $headers, $query, $function);
            }

            return $this->unwrap($result['body'], $function);
        }

        private function unwrap(string $body, string $function): array
        {
            $decoded = json_decode($body, true);
            if (!is_array($decoded))
                throw new ModuleException(($this->t)('error-bad-json', $function, $this->http->safeSummary($function, $body)));

            // A cPanel user API envelope means the credential has no WHM access at all.
            if (isset($decoded['cpanelresult']))
                throw new ModuleException(($this->t)('error-no-whm-access', (string) ($decoded['cpanelresult']['error'] ?? '')));

            // WHM API 1 always reports a result; a reply without one is not a success.
            if ((int) ($decoded['metadata']['result'] ?? 0) !== 1) {
                $reason = (string) ($decoded['metadata']['reason'] ?? $decoded['statusmsg'] ?? '');
                throw new ModuleException($this->http->mask($reason !== '' ? $reason : ($this->t)('error-no-reason')));
            }

            return is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;
        }

        /** Lists nothing on purpose: the search matches no account, the call only proves access. */
        public function testConnection(): bool
        {
            $this->call('listaccts', ['searchtype' => 'user', 'search' => '^$', 'want' => 'user']);
            return true;
        }

        /** Refuses to act on an account that does not exist or does not serve $domain. */
        public function assertServes(string $username, string $domain): void
        {
            $summary = $this->accountSummary($username);
            if ($summary === null) throw new ModuleException(($this->t)('error-account-missing', $username));
            if ($domain !== '' && strcasecmp((string) ($summary['domain'] ?? ''), $domain) !== 0)
                throw new ModuleException(($this->t)('error-not-ours-cpanel', $username, (string) ($summary['domain'] ?? ''), $domain));
        }

        /** @return list<array{name: string}> */
        public function listPackages(): array
        {
            if ($this->packages !== null) return $this->packages;

            $list = [];
            foreach ($this->call('listpkgs')['pkg'] ?? [] as $row)
                if (isset($row['name'])) $list[] = ['name' => (string) $row['name']];

            return $this->packages = $list;
        }

        /** Package names owned by a reseller carry the "reseller_" prefix on the panel. */
        private static function dePrefix(string $name): string
        {
            $at = strpos($name, '_');
            return $at === false ? $name : substr($name, $at + 1);
        }

        public function resolvePackage(string $configured): string
        {
            $configured = trim($configured);
            if ($configured === '') throw new ModuleException(($this->t)('error-no-plan'));

            $packages = $this->listPackages();
            foreach ($packages as $package)
                if (strcasecmp($package['name'], $configured) === 0) return $package['name'];
            foreach ($packages as $package)
                if (strcasecmp(self::dePrefix($package['name']), $configured) === 0) return $package['name'];

            $names = array_column($packages, 'name');
            throw new ModuleException(($this->t)('error-plan-not-found', $configured, $names ? implode(', ', $names) : '-'));
        }

        /**
         * Creates the account, or adopts it when an earlier attempt already created it
         * for the same domain (the queue retries create() after a timeout).
         */
        public function createAccount(array $a): void
        {
            $this->http->addSecret($a['password']);

            $existing = $this->accountSummary($a['username']);
            if ($existing !== null) {
                if (strcasecmp((string) ($existing['domain'] ?? ''), $a['domain']) === 0) return;
                throw new ModuleException(($this->t)('error-username-taken', $a['username']));
            }

            $args = [
                'username'     => $a['username'],
                'domain'       => $a['domain'],
                'password'     => $a['password'],
                'plan'         => $this->resolvePackage($a['plan']),
                'contactemail' => $a['email'] ?? '',
            ];

            try {
                $this->call('createacct', $args);
            } catch (ModuleException $e) {
                // The account may exist even though the response was lost.
                try {
                    $summary = $this->accountSummary($a['username']);
                } catch (ModuleException) {
                    throw $e;
                }
                if ($summary === null || strcasecmp((string) ($summary['domain'] ?? ''), $a['domain']) !== 0) throw $e;
            }
        }

        public function accountSummary(string $username): ?array
        {
            try {
                $data = $this->call('accountsummary', ['user' => $username]);
            } catch (ModuleException $e) {
                if (stripos($e->getMessage(), 'not exist') !== false) return null;
                throw $e;
            }
            return $data['acct'][0] ?? null;
        }

        public function suspendAccount(string $username, string $reason = ''): void
        {
            $this->call('suspendacct', ['user' => $username, 'reason' => $reason]);
        }

        public function unsuspendAccount(string $username): void
        {
            $this->call('unsuspendacct', ['user' => $username]);
        }

        /**
         * Removing an account that is already gone counts as done: the queue retries.
         * An account that serves another domain is never removed.
         */
        public function terminateAccount(string $username, string $domain): void
        {
            $summary = $this->accountSummary($username);
            if ($summary === null) return;
            if ($domain !== '' && strcasecmp((string) ($summary['domain'] ?? ''), $domain) !== 0)
                throw new ModuleException(($this->t)('error-not-ours-cpanel', $username, (string) ($summary['domain'] ?? ''), $domain));

            // WHM API 1 documents "username"; "user" is the older spelling some versions still read.
            $this->call('removeacct', ['username' => $username, 'user' => $username, 'keepdns' => 0]);
        }

        public function changePassword(string $username, string $password): void
        {
            $this->http->addSecret($password);
            $this->call('passwd', ['user' => $username, 'password' => $password]);
        }

        public function changePackage(string $username, string $plan): void
        {
            $this->call('changepackage', ['user' => $username, 'pkg' => $this->resolvePackage($plan)]);
        }

        /** Disk from accountsummary, bandwidth from showbw, all in bytes; 0 limit = unlimited. */
        public function usage(string $username): array
        {
            $summary = $this->accountSummary($username);
            if ($summary === null) throw new ModuleException(($this->t)('error-account-missing', $username));

            $bandwidth = $this->bandwidth($username);

            return [
                'disk_used'  => self::toBytes($summary['diskused'] ?? 0),
                'disk_limit' => self::toBytes($summary['disklimit'] ?? 0),
                'bw_used'    => $bandwidth['used'],
                'bw_limit'   => $bandwidth['limit'],
                'suspended'  => (int) ($summary['suspended'] ?? 0) === 1,
                'ip'         => (string) ($summary['ip'] ?? ''),
            ];
        }

        /**
         * This month's traffic and limit for one account, in bytes.
         *
         * showbw on purpose: it is the only call that reports the limit in bytes.
         * listaccts reports bwlimit in another unit, and accountsummary lacks the
         * fields on most versions; both mistakes are silent, so the source is fixed here.
         */
        public function bandwidth(string $username): array
        {
            // Filtered to this account: an unfiltered call returns every account of the
            // reseller, and that list would end up in this service's log.
            $data = $this->call('showbw', ['searchtype' => 'user', 'search' => '^' . preg_quote($username, '/') . '$']);
            $rows = $data['bandwidth'][0]['acct'] ?? $data['bandwidth']['acct'] ?? $data['acct'] ?? [];

            foreach (is_array($rows) ? $rows : [] as $row) {
                if (strcasecmp((string) ($row['user'] ?? ''), $username) !== 0) continue;
                return [
                    'used'  => self::toBytes($row['totalbytes'] ?? 0, true),
                    'limit' => self::toBytes($row['limit'] ?? 0, true),
                ];
            }

            // A fresh account has no traffic row yet.
            return ['used' => 0, 'limit' => 0];
        }

        /** "512M" style values; bare numbers are megabytes unless $bareIsBytes. */
        public static function toBytes(mixed $value, bool $bareIsBytes = false): int
        {
            $value = trim((string) $value);
            if ($value === '' || strcasecmp($value, 'unlimited') === 0) return 0;

            $scales = ['K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776];
            $unit   = strtoupper(substr($value, -1));
            $number = (float) $value;

            if (isset($scales[$unit])) return (int) round($number * $scales[$unit]);
            return (int) round($number * ($bareIsBytes ? 1 : 1048576));
        }

        /**
         * One-time login URL for the account's cPanel.
         *
         * WHM always answers with an https URL on the SSL port; on a server record with
         * SSL off the scheme and the port must change together.
         */
        public function createSession(string $username): string
        {
            $data = $this->call('create_user_session', ['user' => $username, 'service' => 'cpaneld']);
            $url  = (string) ($data['url'] ?? '');
            if ($url === '') throw new ModuleException(($this->t)('error-no-session'));

            if (!str_starts_with($url, 'http'))
                $url = (!empty($this->server['secure']) ? 'https' : 'http') . '://' . ApiClient::host($this->server) . ':2083' . $url;

            if (empty($this->server['secure']))
                $url = str_replace(['https:', ':2087', ':2083', ':2096'], ['http:', ':2086', ':2082', ':2095'], $url);

            // The URL goes to a browser: anything but plain http(s) is refused.
            $parts = parse_url($url);
            if (!is_array($parts) || empty($parts['host'])
                || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true))
                throw new ModuleException(($this->t)('error-no-session'));

            // The cpsess token travels inside the path, so the URL itself is a secret.
            $this->http->addSecret($url);

            return $url;
        }
    }
