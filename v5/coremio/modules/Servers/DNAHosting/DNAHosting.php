<?php
    namespace WISECP\Modules\Servers;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    use Exception;
    use ServerModule;
    use WISECP\Modules\Servers\DNAHosting\ApiClient;
    use WISECP\Modules\Servers\DNAHosting\Cpanel;
    use WISECP\Modules\Servers\DNAHosting\Detector;
    use WISECP\Modules\Servers\DNAHosting\ModuleException;
    use WISECP\Modules\Servers\DNAHosting\Plesk;
    use WISECP\Modules\Servers\DNAHosting\Support;

    /**
     * Sells shared hosting from a cPanel/WHM or Plesk RESELLER account.
     *
     * No root or admin access is needed: every call runs with the reseller's own
     * permissions and every account counts against the reseller's quota. The panel
     * type is not configured; the module asks the server which panel answers.
     */
    class DNAHosting extends ServerModule
    {
        private ?string $panel  = null;
        private string $auth    = '';
        private array $drivers  = [];
        private array $memo     = [];
        /** @var callable|null Replaces cURL when set. */
        private $transport = null;

        protected function define_server_info(array $server = []): void
        {
            include_once __DIR__ . DS . 'ApiClient.php';
            include_once __DIR__ . DS . 'src' . DS . 'ModuleException.php';
            include_once __DIR__ . DS . 'src' . DS . 'Support.php';
            include_once __DIR__ . DS . 'src' . DS . 'Cpanel.php';
            include_once __DIR__ . DS . 'src' . DS . 'Plesk.php';
            include_once __DIR__ . DS . 'src' . DS . 'Detector.php';

            // The server record changed: forget what the previous one told us.
            $this->panel   = null;
            $this->auth    = '';
            $this->drivers = [];
            $this->memo    = [];
        }


        // ---------------------------------------------------------------- plumbing

        /** Module text with positional placeholders; English is the fallback file. */
        private function t(string $key, string ...$args): string
        {
            $text = (string) ($this->lang[$key] ?? $key);
            if (!$args) return $text;
            try {
                return vsprintf($text, $args);
            } catch (\ValueError) {
                // A translation with the wrong number of placeholders still says something.
                return $text . ' ' . implode(' ', $args);
            }
        }

        /**
         * The server record the drivers sign in with. The credential is the Password
         * field only: WISECP stores it encrypted (varchar 255 fits an encrypted WHM token
         * or Plesk key), while the Access Hash column is stored as plain text, so this
         * module hides that field and never reads it.
         */
        private function credentials(): array
        {
            $server = is_array($this->server) ? $this->server : [];
            unset($server['access_hash']);
            return $server;
        }

        private function http(): ApiClient
        {
            $http = new ApiClient($this->credentials(), fn(string $key, string ...$args) => $this->t($key, ...$args), $this->transport);
            $http->setLogger(fn($action, $request, $response) => $this->save_log($action, $request, $response));
            return $http;
        }

        private function driverFor(string $panel): Cpanel|Plesk
        {
            if (!isset($this->drivers[$panel])) {
                $t = fn(string $key, string ...$args) => $this->t($key, ...$args);
                $this->drivers[$panel] = $panel === 'plesk'
                    ? new Plesk($this->credentials(), $this->http(), $t)
                    : new Cpanel($this->credentials(), $this->http(), $t);
            }
            return $this->drivers[$panel];
        }

        /** 'cpanel' or 'plesk'; throws when neither answers. */
        private function panel(bool $useCache = true): string
        {
            if ($this->panel !== null && $useCache) return $this->panel;

            if (empty($this->server['ip'])) throw new ModuleException($this->t('error-no-server'));

            $detector = new Detector(
                $this->credentials(),
                fn(string $panel) => $this->driverFor($panel),
                fn(string $key, string ...$args) => $this->t($key, ...$args)
            );
            $found = $detector->detect($useCache);

            $this->panel = $found['panel'];
            $this->auth  = $found['auth'];
            if ($this->panel === 'plesk' && $this->auth !== '') $this->plesk()->setAuthMode($this->auth);

            return $this->panel;
        }

        private function isPlesk(): bool
        {
            return $this->panel() === 'plesk';
        }

        private function cpanel(): Cpanel
        {
            return $this->driverFor('cpanel');
        }

        private function plesk(): Plesk
        {
            return $this->driverFor('plesk');
        }

        /**
         * Runs a lifecycle body and turns anything that is not an \Exception into one.
         * The core catches \Exception only; a TypeError from a malformed panel response
         * would otherwise abort the request without telling the operator anything.
         */
        private function guard(callable $body): mixed
        {
            try {
                return $body();
            } catch (Exception $e) {
                throw $e;
            } catch (\Throwable $e) {
                // The detail goes to the module log, not to the screen: it may name files.
                $this->save_log('internal-error', get_class($e), $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
                throw new ModuleException($this->t('error-internal'), 0, $e);
            }
        }

        private function domain(): string
        {
            return Support::domainKey((string) ($this->options['domain'] ?? $this->service['name'] ?? ''));
        }

        private function username(): string
        {
            $user = (string) ($this->options['config']['user'] ?? '');
            if ($user === '') throw new ModuleException($this->t('error-no-account'));
            return $user;
        }

        /**
         * The cPanel account of this service, ownership verified: the account must exist
         * and serve this service's domain. A username edited by hand on the service can
         * then never point a password change or suspension at somebody else's account.
         */
        private function ownedUser(): string
        {
            $user = $this->username();
            $key  = 'cpanel_owner|' . $user . '|' . $this->domain();
            if (isset($this->memo[$key])) return $user;

            $this->cpanel()->assertServes($user, $this->domain());
            $this->memo[$key] = true;
            return $user;
        }

        /** Marks the Plesk customer this module created; services are never re-numbered. */
        private function externalId(): string
        {
            return 'wisecp-' . (int) ($this->service['id'] ?? 0);
        }

        private function planFrom(mixed $moduleData): string
        {
            if (is_string($moduleData)) $moduleData = \Utility::jdecode($moduleData, true);
            return is_array($moduleData) ? trim((string) ($moduleData['plan'] ?? '')) : '';
        }

        /**
         * The Plesk subscription and customer of this service, ownership verified.
         *
         * findWebspace() returns whichever subscription carries the domain and says
         * nothing about who owns it. The owning customer must carry this service's
         * external id; otherwise every operation is refused, so a password change or
         * termination can never land on a site this module did not create.
         */
        private function pleskTargets(): array
        {
            // Keyed by service and domain: a guard result must never outlive the service it checked.
            $domain = $this->domain();
            $key    = 'plesk_targets|' . $this->externalId() . '|' . $domain;
            if (isset($this->memo[$key])) return $this->memo[$key];

            $webspace = $this->plesk()->findWebspace($domain);
            if (!$webspace) throw new ModuleException($this->t('error-subscription-missing', $domain));

            $expected = $this->externalId();
            $actual   = $this->plesk()->customerExternalId($webspace['owner_id']);
            if ($actual !== $expected)
                throw new ModuleException($this->t('error-not-ours', $domain, $actual !== '' ? $actual : '-', $expected));

            return $this->memo[$key] = ['webspace_id' => $webspace['id'], 'customer_id' => $webspace['owner_id']];
        }


        // ---------------------------------------------------------------- server form

        public function test_connect(): bool|array
        {
            return $this->guard(function () {
                // Fresh probe: the operator is checking credentials they just typed.
                $this->panel(false);
                return true;
            });
        }


        // ---------------------------------------------------------------- product form

        public function product_configuration(array $data = []): array
        {
            $label = $this->t('config-plan');
            $error = '';
            $plans = [];

            try {
                $panel = $this->panel();
                $rows  = $panel === 'plesk' ? $this->plesk()->listPlans() : $this->cpanel()->listPackages();
                foreach ($rows as $row) $plans[$row['name']] = ['text' => $row['name']];
                ksort($plans);
                $label .= ' (' . $this->t($panel === 'plesk' ? 'panel-plesk' : 'panel-cpanel') . ')';
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }

            // The option value is the plan NAME: on a load-balanced server group the
            // same plan has a different id on every machine.
            if ($plans)
                return ['plan' => [
                    'name'              => $label,
                    'description'       => $this->t('config-plan-desc'),
                    'type'              => 'dropdown',
                    'options'           => $plans,
                    'value'             => $data['plan'] ?? '',
                    'advanced_selector' => true,
                ]];

            $field = [
                'name'        => $label,
                'description' => $this->t('config-plan-desc'),
                'type'        => 'text',
                'value'       => $data['plan'] ?? '',
            ];
            if ($error !== '')
                $field['fieldOptions'] = [
                    'extraClass'  => 'is-invalid',
                    'fieldExtras' => '<div class="invalid-feedback">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>',
                ];

            return ['plan' => $field];
        }

        public function save_product_configuration(array &$values): void
        {
            $values['plan'] = trim(strip_tags((string) ($values['plan'] ?? '')));
        }

        public function service_configuration(): array
        {
            return ['fields' => $this->product_configuration($this->options['creation_info'] ?? [])];
        }

        /** A plan picked on the service screen is applied to the panel before it is saved. */
        public function save_service_configuration(array &$values): void
        {
            $this->guard(function () use (&$values) {
                $new = trim(strip_tags((string) ($values['creation_info']['plan'] ?? '')));
                $old = (string) ($this->options['creation_info']['plan'] ?? '');
                if ($new === '' || $new === $old || empty($this->options['config']['user'])) return;

                $this->applyPlan($new);
                $values['creation_info']['plan'] = $new;
            });
        }


        // ---------------------------------------------------------------- provisioning

        public function create(): array|bool
        {
            return $this->guard(function () {
                $domain = $this->domain();
                if ($domain === '') throw new ModuleException($this->t('error-no-domain'));

                $plan = trim((string) ($this->options['creation_info']['plan'] ?? ''));
                if ($plan === '') throw new ModuleException($this->t('error-no-plan'));

                $panel = $this->panel();

                // A retry reuses the identity of the first run instead of minting a new one.
                $username = (string) ($this->options['config']['user'] ?? '');
                $stored   = (string) ($this->options['config']['password'] ?? '');
                $password = $stored !== '' ? $this->decode_str($stored) : '';
                if ($username === '') $username = Support::usernameFor($domain, $panel);
                if ($password === '') $password = Support::password(14);

                $homeDir = $panel === 'plesk' ? '/var/www/vhosts/' . $domain : '/home/' . $username;

                // Written before the panel call, so a queue retry after a timeout finds
                // the same credentials and adopts the account instead of failing on it.
                if (($this->options['config']['user'] ?? '') !== $username || $stored === '') {
                    $this->options['config']['user']     = $username;
                    $this->options['config']['password'] = $this->encode_str($password);
                    $this->options['config']['home_dir'] = $homeDir;
                    $this->save_options();
                }

                $account = [
                    'username'    => $username,
                    'password'    => $password,
                    'domain'      => $domain,
                    'plan'        => $plan,
                    'email'       => (string) ($this->user['email'] ?? ''),
                    'name'        => trim((string) ($this->user['full_name'] ?? '')) ?: $username,
                    'external_id' => $this->externalId(),
                ];

                $panel === 'plesk' ? $this->plesk()->createAccount($account) : $this->cpanel()->createAccount($account);

                return [
                    'config' => [
                        'user'     => $username,
                        'password' => $this->encode_str($password),
                        'home_dir' => $homeDir,
                    ],
                ];
            });
        }

        public function suspend(): bool
        {
            return $this->guard(function () {
                if ($this->isPlesk()) $this->plesk()->suspend($this->pleskTargets()['webspace_id']);
                else $this->cpanel()->suspendAccount($this->ownedUser(), 'WISECP');
                return true;
            });
        }

        public function unsuspend(): bool
        {
            return $this->guard(function () {
                if ($this->isPlesk()) $this->plesk()->unsuspend($this->pleskTargets()['webspace_id']);
                else $this->cpanel()->unsuspendAccount($this->ownedUser());
                return true;
            });
        }

        public function cancel(): bool
        {
            return $this->guard(function () {
                $shared = $this->otherServicesOnDomain();
                if ($shared) throw new ModuleException($this->t('error-shared-domain', implode(', ', $shared)));

                if ($this->isPlesk()) {
                    try {
                        $t = $this->pleskTargets();
                    } catch (ModuleException $e) {
                        // Already gone: termination is retried by the queue.
                        if ($this->plesk()->findWebspace($this->domain()) === null) return true;
                        throw $e;
                    }
                    $this->plesk()->terminate($t['webspace_id'], $t['customer_id'], $this->externalId());
                }
                else $this->cpanel()->terminateAccount($this->username(), $this->domain());

                return true;
            });
        }

        /**
         * Other active or suspended services of this module on the same server and
         * domain. Terminating one of them must not delete the account the others use.
         */
        private function otherServicesOnDomain(): array
        {
            $domain   = $this->domain();
            $serverId = (int) ($this->server['id'] ?? 0);
            if ($domain === '' || $serverId <= 0) return [];

            $stmt = \WDB::select('id,options')->from('users_products');
            $stmt->where('module', '=', $this->_name, '&&');
            $stmt->where('id', '!=', (int) ($this->service['id'] ?? 0), '&&');
            $stmt->where("JSON_UNQUOTE(JSON_EXTRACT(options,'$.server_id'))", '=', $serverId, '&&');
            $stmt->where('(');
            $stmt->where('status', '=', 'active', '||');
            $stmt->where('status', '=', 'suspended');
            $stmt->where(')');

            $rows = $stmt->build() ? $stmt->fetch_assoc() : [];

            $ids = [];
            foreach ($rows ?: [] as $row) {
                $options = \Utility::jdecode((string) ($row['options'] ?? ''), true);
                if (is_array($options) && Support::domainKey((string) ($options['domain'] ?? '')) === $domain)
                    $ids[] = (int) $row['id'];
            }
            return $ids;
        }

        public function change_password(string $password): bool
        {
            return $this->guard(function () use ($password) {
                if ($this->isPlesk()) {
                    $t = $this->pleskTargets();
                    $this->plesk()->changePassword($t['customer_id'], $t['webspace_id'], $password);
                }
                else $this->cpanel()->changePassword($this->ownedUser(), $password);

                // Keep the stored copy in step, so single sign-on and the client view agree.
                $this->options['config']['password'] = $this->encode_str($password);
                $this->save_options();
                return true;
            });
        }

        /** The new plan lives in the NEW product's module data, not in creation_info. */
        public function upgrade(array $new_product = []): bool
        {
            return $this->guard(function () use ($new_product) {
                $plan = $this->planFrom($new_product['module_data'] ?? []);
                // Reporting success without changing anything would bill an upgrade
                // the customer never received.
                if ($plan === '') throw new ModuleException($this->t('error-no-plan'));

                $this->applyPlan($plan);
                return true;
            });
        }

        private function applyPlan(string $plan): void
        {
            if ($this->isPlesk())
                $this->plesk()->changePlan($this->pleskTargets()['webspace_id'], $this->plesk()->resolvePlan($plan));
            else
                $this->cpanel()->changePackage($this->ownedUser(), $plan);
        }


        // ---------------------------------------------------------------- client area

        public function sso_panel_login(): string
        {
            return $this->guard(function () {
                $ip = (string) \UserManager::GetIP();
                $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';

                if ($this->isPlesk()) {
                    // Ownership first: the login name alone could belong to anyone.
                    $this->pleskTargets();
                    return $this->plesk()->createSession($this->username(), $ip);
                }
                return $this->cpanel()->createSession($this->ownedUser());
            });
        }

        /** Disk and traffic in bytes; a zero limit means unlimited. */
        private function usage(): array
        {
            $key = 'usage|' . $this->externalId() . '|' . ($this->options['config']['user'] ?? '');
            if (isset($this->memo[$key])) return $this->memo[$key];

            $usage = $this->isPlesk()
                ? $this->plesk()->webspaceInfo($this->pleskTargets()['webspace_id'])
                : $this->cpanel()->usage($this->username());

            // Plesk: any non-zero subscription status means it is not serving the site.
            if (!isset($usage['suspended'])) $usage['suspended'] = (int) ($usage['status'] ?? 0) !== 0;

            return $this->memo[$key] = $usage;
        }

        public function dashboard_data(): array
        {
            $info = [
                'server'   => ['value' => (string) ($this->server['name'] ?? '')],
                'domain'   => ['value' => (string) ($this->options['domain'] ?? '')],
                'username' => ['value' => (string) ($this->options['config']['user'] ?? '')],
                'home_dir' => ['value' => (string) ($this->options['config']['home_dir'] ?? '')],
            ];

            try {
                $usage = $this->guard(fn() => $this->usage());
            } catch (\Throwable) {
                // Unread values keep the cache short so the card recovers on its own.
                return [
                    'gauges'       => ['disk_space' => ['used' => null], 'bandwidth' => ['used' => null]],
                    'account_info' => $info,
                ];
            }

            $total = fn(int $limit): int => $limit > 0 ? $limit : -1;

            $info['status'] = $usage['suspended']
                ? ['value' => \Language::g('needs/suspended'), 'badge_color' => 'warning']
                : ['value' => \Language::g('needs/active'), 'badge_color' => 'success', 'effect' => 'online'];

            return [
                'gauges' => [
                    'disk_space' => ['used' => $usage['disk_used'], 'total' => $total($usage['disk_limit'])],
                    'bandwidth'  => ['used' => $usage['bw_used'], 'total' => $total($usage['bw_limit'])],
                ],
                'account_info' => $info,
            ];
        }

        /** Usage for metered billing, in megabytes. */
        public function metrics_usage(): array
        {
            return $this->guard(function () {
                $usage = $this->usage();
                return [
                    'disk_space' => ['used' => round($usage['disk_used'] / 1048576, 2), 'unit' => 'MB'],
                    'bandwidth'  => ['used' => round($usage['bw_used'] / 1048576, 2), 'unit' => 'MB'],
                ];
            });
        }
    }
