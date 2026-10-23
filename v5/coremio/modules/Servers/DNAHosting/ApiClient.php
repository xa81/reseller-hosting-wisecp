<?php
    namespace WISECP\Modules\Servers\DNAHosting;

    defined("CORE_FOLDER") or exit("You can not get in here!");

    /**
     * HTTP transport shared by the cPanel/WHM and Plesk drivers.
     *
     * TLS verification follows the "Verify SSL" switch on the server form
     * (\ServerModule::tls_verify()). The core shows that switch only for modules whose
     * ApiClient.php calls it, which is why this file sits at the module root.
     *
     * Every request is written to the module log with known secrets masked: the API
     * token, generated account passwords and session tokens returned by the panel.
     */
    class ApiClient
    {
        private string $base;
        private int $timeout = 30;
        private bool $verify_ssl;
        /** @var callable|null */
        private $transport = null;
        /** @var callable|null */
        private $logger = null;
        /** @var callable */
        private $t;
        private array $secrets    = [];
        private array $redactions = [];

        /** Log entries keep this many characters of a response body. */
        private const LOG_BODY_LIMIT = 2000;

        /**
         * @param callable|null $transport replaces cURL; returns ['status', 'body', 'error']
         */
        public function __construct(array $server, callable $translate, ?callable $transport = null)
        {
            $scheme           = !empty($server['secure']) ? 'https' : 'http';
            $this->base       = $scheme . '://' . self::host($server) . ':' . (int) ($server['port'] ?? 0);
            $this->verify_ssl = \ServerModule::tls_verify($server);
            $this->t          = $translate;
            $this->transport  = $transport;
        }

        /**
         * The host to connect to: the server's hostname when set, its IP otherwise.
         * A certificate is issued for a name, so with "Verify SSL Certificate" on, an IP
         * address would fail the host check on almost every panel.
         */
        public static function host(array $server): string
        {
            $name = trim((string) ($server['name'] ?? ''));
            $ip   = trim((string) ($server['ip'] ?? ''));
            return $name !== '' && preg_match('/^[a-z0-9.-]+$/i', $name) && str_contains($name, '.') ? $name : $ip;
        }

        public function setLogger(callable $logger): static
        {
            $this->logger = $logger;
            return $this;
        }

        public function setTimeout(int $seconds): static
        {
            $this->timeout = $seconds;
            return $this;
        }

        /**
         * Registers a secret in every form it can take in a request: as typed, URL-encoded
         * (cPanel form bodies and query strings) and XML-escaped (Plesk packets).
         */
        public function addSecret(string $value): static
        {
            if (strlen($value) < 4) return $this;
            foreach ([$value, urlencode($value), rawurlencode($value), htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8')] as $form)
                $this->secrets[$form] = true;
            return $this;
        }

        public function mask(string $text): string
        {
            // Longest first, so a secret that contains another is masked whole.
            $secrets = array_keys($this->secrets);
            usort($secrets, fn($a, $b) => strlen($b) <=> strlen($a));
            foreach ($secrets as $secret)
                $text = str_replace((string) $secret, '***', $text);
            return $text;
        }

        /**
         * Patterns applied to one action's RESPONSE body before it is logged.
         *
         * addSecret() covers values known in advance. Session calls return the
         * credential in the response itself, so the caller can only register it after
         * the line carrying it has been logged; a pattern registered up front covers
         * that line. The scope is the action name rather than a one-shot flag because
         * the Plesk driver may retry an action with a second authentication mode.
         */
        public function addResponseRedaction(string $action, array $patterns): static
        {
            foreach ($patterns as $pattern => $replacement)
                $this->redactions[$action][$pattern] = $replacement;
            return $this;
        }

        /** A pattern that fails to run hides the body instead of leaving it unmasked. */
        private function redactResponse(string $action, string $body): string
        {
            foreach ($this->redactions[$action] ?? [] as $pattern => $replacement) {
                $replaced = preg_replace($pattern, $replacement, $body);
                if ($replaced === null) return '[response hidden: redaction failed]';
                $body = $replaced;
            }
            return $body;
        }

        /** A response excerpt that is safe to put in an error message. */
        public function safeSummary(string $action, string $body, int $limit = 300): string
        {
            return self::summarise($this->mask($this->redactResponse($action, $body)), $limit);
        }

        public function send(string $method, string $path, array $headers, ?string $body, string $action): array
        {
            $url       = $this->base . $path;
            $transport = $this->transport ?: [$this, 'curl'];
            $result    = call_user_func($transport, $method, $url, $headers, $body, $this->timeout);

            $logRequest = $this->mask($method . ' ' . $url
                . ($headers ? "\n" . implode("\n", $headers) : '')
                . ($body !== null && $body !== '' ? "\n\n" . $body : ''));

            if (!empty($result['error'])) {
                $this->log($action, $logRequest, $this->mask('TRANSPORT ERROR: ' . $result['error']));
                throw new ModuleException(($this->t)('error-transport', $this->mask((string) $result['error'])));
            }

            $status = (int) ($result['status'] ?? 0);
            $raw    = (string) ($result['body'] ?? '');

            // Redaction applies to the log and the error copy only: the caller gets the
            // raw body, otherwise a session URL could not be parsed.
            $safeBody = $this->redactResponse($action, $raw);
            $logBody = mb_strlen($safeBody, 'UTF-8') > self::LOG_BODY_LIMIT
                ? mb_substr($safeBody, 0, self::LOG_BODY_LIMIT, 'UTF-8') . '...'
                : $safeBody;
            $this->log($action, $logRequest, $this->mask('HTTP ' . $status . "\n\n" . $logBody));

            if ($status >= 400 || $status === 0) {
                $summary = self::summarise($safeBody);
                throw new ModuleException($this->mask('HTTP ' . $status . ($summary !== '' ? ': ' . $summary : '')));
            }

            return ['status' => $status, 'body' => $raw];
        }

        public static function summarise(string $body, int $limit = 300): string
        {
            $text     = html_entity_decode(strip_tags($body), ENT_QUOTES, 'UTF-8');
            $replaced = preg_replace('/\s+/u', ' ', $text);
            $text     = trim($replaced ?? $text);
            if ($text === '') return '';

            // Byte truncation could split a multibyte character; this text is rendered.
            if (mb_strlen($text, 'UTF-8') > $limit)
                $text = mb_substr($text, 0, $limit, 'UTF-8') . '...';
            return $text;
        }

        private function log(string $action, string $request, string $response): void
        {
            if ($this->logger) call_user_func($this->logger, $action, $request, $response);
        }

        private function curl(string $method, string $url, array $headers, ?string $body, int $timeout): array
        {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => $timeout,
            ] + \ServerModule::curl_tls_options($this->verify_ssl));

            if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string) $body);
            }
            elseif ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $response = curl_exec($ch);
            $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            return [
                'status' => $status,
                'body'   => $response === false ? '' : (string) $response,
                'error'  => $error,
            ];
        }
    }
