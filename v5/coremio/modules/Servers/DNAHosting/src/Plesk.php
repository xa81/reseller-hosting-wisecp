<?php
    namespace WISECP\Modules\Servers\DNAHosting;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    /**
     * Plesk XML-RPC driver, authenticated as a reseller (secret key or login/password).
     *
     * Plesk ids are never stored on the service: a subscription is found by its domain
     * and the customer that owns it must carry this service's external id before any
     * operation runs (see DNAHosting::pleskTargets()).
     */
    class Plesk
    {
        private const ENDPOINT = '/enterprise/control/agent.php';

        /** Status bit this module sets on suspension; other bits belong to the panel. */
        public const OUR_SUSPENSION_BIT = 32;

        private string $authMode    = 'key';
        private bool   $authSettled = false;
        private ?array $plans       = null;
        /** @var callable */
        private $t;

        public function __construct(private array $server, private ApiClient $http, callable $translate)
        {
            $this->t = $translate;
            $this->http->addSecret((string) ($server['password'] ?? ''));
            $this->http->setTimeout(300);

            // server.create_session returns PLESKSESSID. The second pattern does not
            // depend on the body shape: a proxy error page may echo the session id.
            $this->http->addResponseRedaction('server.create_session', [
                '/(<id>)[^<]*(<\/id>)/'        => '$1***$2',
                '/(PLESKSESSID=)[^&"\'\s<]+/i' => '$1***',
            ]);
        }

        public function setAuthMode(string $mode): void
        {
            if ($mode !== 'key' && $mode !== 'basic') return;
            $this->authMode    = $mode;
            $this->authSettled = true;
        }

        public function authMode(): string
        {
            return $this->authMode;
        }

        /** Tries the secret key first and falls back to login/password once. */
        public function request(string $bodyXml, string $action): \SimpleXMLElement
        {
            try {
                return $this->attempt($bodyXml, $action, $this->authMode);
            } catch (ModuleException $e) {
                if ($this->authSettled || $this->authMode !== 'key' || !self::isAuthFailure($e)) throw $e;
            }

            $packet            = $this->attempt($bodyXml, $action, 'basic');
            $this->authMode    = 'basic';
            $this->authSettled = true;
            return $packet;
        }

        private function attempt(string $bodyXml, string $action, string $mode): \SimpleXMLElement
        {
            $headers = ['Content-Type: text/xml', 'HTTP_PRETTY_PRINT: TRUE'];
            if ($mode === 'key')
                $headers[] = 'KEY: ' . ($this->server['password'] ?? '');
            else {
                $headers[] = 'HTTP_AUTH_LOGIN: ' . ($this->server['username'] ?? '');
                $headers[] = 'HTTP_AUTH_PASSWD: ' . ($this->server['password'] ?? '');
            }

            $body     = '<?xml version="1.0" encoding="UTF-8"?><packet>' . $bodyXml . '</packet>';
            $response = $this->http->send('POST', self::ENDPOINT, $headers, $body, $action);

            $previous = libxml_use_internal_errors(true);
            $packet   = simplexml_load_string($response['body'], \SimpleXMLElement::class, LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            if ($packet === false || $packet->getName() !== 'packet')
                throw new ModuleException(($this->t)('error-bad-xml', $action, $this->http->safeSummary($action, $response['body'])));

            if ((string) ($packet->system->status ?? '') === 'error') {
                $code = (string) $packet->system->errcode;
                throw new ModuleException($this->describe($code, (string) $packet->system->errtext), (int) $code);
            }

            return $packet;
        }

        public function resultOf(\SimpleXMLElement $packet, string $path): \SimpleXMLElement
        {
            $node = $packet;
            foreach (explode('/', $path) as $step) {
                if (!isset($node->{$step})) throw new ModuleException(($this->t)('error-bad-xml', $path, '<' . $step . '>'));
                $node = $node->{$step};
            }
            if (!isset($node->result)) throw new ModuleException(($this->t)('error-bad-xml', $path, '<result>'));

            $result = $node->result;
            if ((string) $result->status === 'error') {
                $code = (string) $result->errcode;
                throw new ModuleException($this->describe($code, (string) $result->errtext), (int) $code);
            }
            return $result;
        }

        private function resultOrNull(\SimpleXMLElement $packet, string $path): ?\SimpleXMLElement
        {
            try {
                return $this->resultOf($packet, $path);
            } catch (ModuleException $e) {
                if (in_array($e->getCode(), [1013, 1015], true)) return null;
                throw $e;
            }
        }

        private function describe(string $code, string $text): string
        {
            $text = trim($text) !== '' ? trim($text) : ($this->t)('error-no-reason');
            $hint = match ($code) {
                '11003'        => ' ' . ($this->t)('hint-plesk-11003'),
                '1013', '1015' => ' ' . ($this->t)('hint-plesk-not-found'),
                default        => '',
            };
            return 'Plesk (' . ($code !== '' ? $code : '?') . '): ' . $text . $hint;
        }

        private static function isAuthFailure(ModuleException $e): bool
        {
            return $e->getCode() === 1001
                || ($e->getCode() === 0 && stripos($e->getMessage(), 'Authentication failed') !== false);
        }

        public static function esc(string $value): string
        {
            return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        }

        public function testConnection(): bool
        {
            $this->resultOf($this->request('<server><get><gen_info/></get></server>', 'server.get'), 'server/get');
            return true;
        }

        /** @return list<array{name: string, guid: string}> */
        public function listPlans(): array
        {
            if ($this->plans !== null) return $this->plans;

            $packet = $this->request('<service-plan><get><filter/></get></service-plan>', 'service-plan.get');

            $list = [];
            foreach ($packet->{'service-plan'}->get->result ?? [] as $result)
                if ((string) $result->status === 'ok')
                    $list[] = ['name' => (string) $result->name, 'guid' => (string) $result->guid];

            return $this->plans = $list;
        }

        public function resolvePlan(string $configured): array
        {
            $configured = trim($configured);
            if ($configured === '') throw new ModuleException(($this->t)('error-no-plan'));

            $plans = $this->listPlans();
            foreach ($plans as $plan)
                if (strcasecmp($plan['name'], $configured) === 0) return $plan;

            $names = array_column($plans, 'name');
            throw new ModuleException(($this->t)('error-plan-not-found', $configured, $names ? implode(', ', $names) : '-'));
        }

        private function firstSharedIp(): string
        {
            $result = $this->resultOf($this->request('<ip><get/></ip>', 'ip.get'), 'ip/get');

            foreach ($result->addresses->ip ?? [] as $row) {
                $address = trim((string) $row->ip_address);
                if ($address !== '' && !str_contains($address, ':') && strcasecmp((string) $row->type, 'shared') === 0)
                    return $address;
            }

            return (string) ($this->server['ip'] ?? '');
        }

        public function findCustomer(string $externalId): ?array
        {
            $packet = $this->request(
                '<customer><get><filter><external-id>' . self::esc($externalId) . '</external-id></filter>'
                . '<dataset><gen_info/></dataset></get></customer>',
                'customer.get'
            );

            $result = $this->resultOrNull($packet, 'customer/get');
            if ($result === null) return null;

            return ['id' => (int) $result->id, 'login' => (string) $result->data->gen_info->login];
        }

        public function customerExternalId(int $customerId): string
        {
            $packet = $this->request(
                '<customer><get><filter><id>' . $customerId . '</id></filter>'
                . '<dataset><gen_info/></dataset></get></customer>',
                'customer.get'
            );

            $result = $this->resultOrNull($packet, 'customer/get');
            return $result === null ? '' : (string) ($result->data->gen_info->{'external-id'} ?? '');
        }

        public function findWebspace(string $domain): ?array
        {
            $packet = $this->request(
                '<webspace><get><filter><name>' . self::esc($domain) . '</name></filter>'
                . '<dataset><gen_info/></dataset></get></webspace>',
                'webspace.get'
            );

            $result = $this->resultOrNull($packet, 'webspace/get');
            if ($result === null) return null;

            return [
                'id'       => (int) $result->id,
                'name'     => (string) $result->data->gen_info->name,
                'owner_id' => (int) $result->data->gen_info->{'owner-id'},
            ];
        }

        /**
         * Creates the customer and the subscription. Safe to run twice: the customer is
         * found by external id and an existing subscription is adopted only when that
         * same customer owns it.
         */
        public function createAccount(array $a): void
        {
            $this->http->addSecret($a['password']);

            $plan       = $this->resolvePlan($a['plan']);
            $customer   = $this->findCustomer($a['external_id']);
            $customerId = $customer === null ? $this->addCustomer($a) : (int) $customer['id'];

            // A subscription for this domain may exist and belong to someone else: a
            // service deleted without termination, a site added by hand. Adopting it
            // would report success and aim every later call, termination included, at
            // a live site that is not ours.
            $existing = $this->findWebspace($a['domain']);
            if ($existing !== null && $existing['owner_id'] !== $customerId)
                throw new ModuleException(($this->t)('error-domain-owned', $a['domain'], (string) $existing['owner_id']));

            if ($existing === null) $this->addWebspace($a, $customerId, $plan);
        }

        private function addCustomer(array $a): int
        {
            $packet = $this->request(
                '<customer><add><gen_info>'
                . '<pname>' . self::esc($a['name']) . '</pname>'
                . '<login>' . self::esc($a['username']) . '</login>'
                . '<passwd>' . self::esc($a['password']) . '</passwd>'
                . '<email>' . self::esc($a['email']) . '</email>'
                . '<external-id>' . self::esc($a['external_id']) . '</external-id>'
                . '</gen_info></add></customer>',
                'customer.add'
            );
            return (int) $this->resultOf($packet, 'customer/add')->id;
        }

        private function addWebspace(array $a, int $customerId, array $plan): void
        {
            $ip = $this->firstSharedIp();

            $xml = '<webspace><add>'
                . '<gen_setup>'
                . '<name>' . self::esc($a['domain']) . '</name>'
                . '<owner-id>' . $customerId . '</owner-id>'
                . '<ip_address>' . self::esc($ip) . '</ip_address>'
                . '<htype>vrt_hst</htype>'
                . '<status>0</status>'
                . '</gen_setup>'
                . '<hosting><vrt_hst>'
                . '<property><name>ftp_login</name><value>' . self::esc($a['username']) . '</value></property>'
                . '<property><name>ftp_password</name><value>' . self::esc($a['password']) . '</value></property>'
                . '<ip_address>' . self::esc($ip) . '</ip_address>'
                . '</vrt_hst></hosting>'
                . '<prefs><www>true</www></prefs>'
                . '<plan-name>' . self::esc($plan['name']) . '</plan-name>'
                . '</add></webspace>';

            try {
                $this->resultOf($this->request($xml, 'webspace.add'), 'webspace/add');
            } catch (ModuleException $e) {
                if (!self::isResumableAddFailure($e)) throw $e;
                $recovered = $this->findWebspace($a['domain']);
                if ($recovered === null || $recovered['owner_id'] !== $customerId) throw $e;
            }
        }

        /**
         * Only "already exists" and a lost response justify looking the subscription up
         * and carrying on. Any other error must surface: Plesk leaves a half-built
         * subscription behind when web server configuration fails, and finding it would
         * report success for a subscription without hosting.
         */
        private static function isResumableAddFailure(ModuleException $e): bool
        {
            return $e->getCode() === 0 || $e->getCode() === 1007 || stripos($e->getMessage(), 'already exists') !== false;
        }

        public function webspaceInfo(int $webspaceId): array
        {
            $packet = $this->request(
                '<webspace><get><filter><id>' . $webspaceId . '</id></filter>'
                . '<dataset><gen_info/><stat/><limits/></dataset></get></webspace>',
                'webspace.get'
            );
            $result = $this->resultOf($packet, 'webspace/get');

            $limits = [];
            foreach ($result->data->limits->limit ?? [] as $limit)
                $limits[(string) $limit->name] = (float) $limit->value;

            $limitBytes = fn(string $name): int => !isset($limits[$name]) || $limits[$name] < 0 ? 0 : (int) $limits[$name];

            return [
                'status'     => (int) $result->data->gen_info->status,
                'ip'         => (string) ($result->data->gen_info->dns_ip_address ?? ''),
                'disk_used'  => (int) ($result->data->stat->real_size ?? $result->data->gen_info->real_size ?? 0),
                'disk_limit' => $limitBytes('disk_space'),
                'bw_used'    => (int) ($result->data->stat->traffic ?? 0),
                'bw_limit'   => $limitBytes('max_traffic'),
            ];
        }

        public function suspend(int $webspaceId): void
        {
            $this->setStatus($webspaceId, $this->webspaceInfo($webspaceId)['status'] | self::OUR_SUSPENSION_BIT);
        }

        public function unsuspend(int $webspaceId): void
        {
            $this->setStatus($webspaceId, $this->webspaceInfo($webspaceId)['status'] & ~self::OUR_SUSPENSION_BIT);
        }

        private function setStatus(int $webspaceId, int $status): void
        {
            $packet = $this->request(
                '<webspace><set><filter><id>' . $webspaceId . '</id></filter>'
                . '<values><gen_setup><status>' . $status . '</status></gen_setup></values>'
                . '</set></webspace>',
                'webspace.set'
            );
            $this->resultOf($packet, 'webspace/set');
        }

        /**
         * Deletes the subscription, then the customer only when it is safe.
         *
         * The external id proves the customer is ours, not that this is its only
         * subscription; deleting the customer would take a second site with it. So the
         * subscription goes by id, the remaining subscriptions are counted and the
         * customer is removed only at zero. A count that cannot be read counts as not
         * empty.
         */
        public function terminate(int $webspaceId, int $customerId, string $expectedExternalId): void
        {
            if ($webspaceId <= 0) throw new ModuleException(($this->t)('error-no-webspace-id'));

            try {
                $this->resultOf($this->request(
                    '<webspace><del><filter><id>' . $webspaceId . '</id></filter></del></webspace>',
                    'webspace.del'
                ), 'webspace/del');
            } catch (ModuleException $e) {
                if ($e->getCode() !== 1013) throw $e;
            }

            if ($customerId <= 0 || $this->countWebspacesOwnedBy($customerId) > 0) return;
            if ($this->customerExternalId($customerId) !== $expectedExternalId) return;

            $this->resultOf($this->request(
                '<customer><del><filter><id>' . $customerId . '</id></filter></del></customer>',
                'customer.del'
            ), 'customer/del');
        }

        private function countWebspacesOwnedBy(int $customerId): int
        {
            try {
                $packet = $this->request(
                    '<webspace><get><filter><owner-id>' . $customerId . '</owner-id></filter>'
                    . '<dataset><gen_info/></dataset></get></webspace>',
                    'webspace.get.byowner'
                );
            } catch (ModuleException) {
                return 1;
            }

            if (!isset($packet->webspace->get->result)) return 1;

            // A customer without subscriptions still gets an "ok" result that carries only
            // the filter id; only results naming a subscription id count.
            $count = 0;
            foreach ($packet->webspace->get->result as $result)
                if ((string) $result->status === 'ok' && trim((string) ($result->id ?? '')) !== '') $count++;
            return $count;
        }

        public function changePassword(int $customerId, int $webspaceId, string $password): void
        {
            $this->http->addSecret($password);

            $this->resultOf($this->request(
                '<customer><set><filter><id>' . $customerId . '</id></filter>'
                . '<values><gen_info><passwd>' . self::esc($password) . '</passwd></gen_info></values>'
                . '</set></customer>',
                'customer.set'
            ), 'customer/set');

            $this->resultOf($this->request(
                '<webspace><set><filter><id>' . $webspaceId . '</id></filter>'
                . '<values><hosting><vrt_hst>'
                . '<property><name>ftp_password</name><value>' . self::esc($password) . '</value></property>'
                . '</vrt_hst></hosting></values>'
                . '</set></webspace>',
                'webspace.set'
            ), 'webspace/set');
        }

        public function changePlan(int $webspaceId, array $plan): void
        {
            $this->resultOf($this->request(
                '<webspace><switch-subscription><filter><id>' . $webspaceId . '</id></filter>'
                . '<plan-guid>' . self::esc($plan['guid']) . '</plan-guid>'
                . '</switch-subscription></webspace>',
                'webspace.switch-subscription'
            ), 'webspace/switch-subscription');
        }

        public function createSession(string $login, string $clientIp): string
        {
            $result = $this->resultOf($this->request(
                '<server><create_session>'
                . '<login>' . self::esc($login) . '</login>'
                // Plesk expects the client address base64-encoded.
                . '<data><user_ip>' . self::esc(base64_encode($clientIp)) . '</user_ip><source_server/></data>'
                . '</create_session></server>',
                'server.create_session'
            ), 'server/create_session');

            $sessionId = (string) $result->id;
            if ($sessionId === '') throw new ModuleException(($this->t)('error-no-session'));

            $this->http->addSecret($sessionId);

            return (!empty($this->server['secure']) ? 'https' : 'http') . '://'
                . ApiClient::host($this->server) . ':' . (int) $this->server['port']
                . '/enterprise/rsession_init.php?PLESKSESSID=' . rawurlencode($sessionId);
        }
    }
