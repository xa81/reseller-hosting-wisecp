<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Http
{
    private $base;
    private $timeout   = 30;
    private $transport = null;
    private $logger    = null;
    private $secrets   = array();

    public function __construct($baseUrl)
    {
        $this->base = rtrim($baseUrl, '/');
    }

    public function setTransport(callable $transport)
    {
        $this->transport = $transport;
        return $this;
    }

    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setTimeout($seconds)
    {
        $this->timeout = (int) $seconds;
        return $this;
    }

    public function addSecret($value)
    {
        $value = (string) $value;
        if (strlen($value) >= 4) {
            $this->secrets[] = $value;
        }
        return $this;
    }

    public function mask($text)
    {
        $text = (string) $text;
        foreach ($this->secrets as $secret) {
            $text = str_replace($secret, '***', $text);
        }
        return $text;
    }

    public function send($method, $path, array $headers, $body, $action)
    {
        $url       = $this->base . $path;
        $transport = $this->transport ? $this->transport : array($this, 'curl');
        $result    = call_user_func($transport, $method, $url, $headers, $body, $this->timeout);

        $logRequest = $this->mask($method . ' ' . $url
            . ($headers ? "\n" . implode("\n", $headers) : '')
            . ($body !== null && $body !== '' ? "\n\n" . $this->stringify($body) : ''));

        if (!empty($result['error'])) {
            $this->log($action, $logRequest, $this->mask('TASIMA HATASI: ' . $result['error']));
            throw new DNAHosting_Exception($result['error']);
        }

        $status = (int) $result['status'];
        $rbody  = (string) $result['body'];
        $this->log($action, $logRequest, $this->mask('HTTP ' . $status . "\n\n" . $rbody));

        if ($status >= 400) {
            $summary = self::summarise($rbody);
            throw new DNAHosting_Exception('HTTP ' . $status . ($summary ? ': ' . $summary : ''));
        }

        return array('status' => $status, 'body' => $rbody);
    }

    public static function summarise($body, $limit = 300)
    {
        $text = strip_tags((string) $body);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $replaced = preg_replace('/\s+/u', ' ', $text);
        $text = trim($replaced === null ? $text : $replaced);
        if ($text === '') {
            return '';
        }
        if (strlen($text) > $limit) {
            $text = substr($text, 0, $limit) . '...';
        }
        return $text;
    }

    private function stringify($body)
    {
        return is_array($body) ? http_build_query($body) : (string) $body;
    }

    private function log($action, $request, $response)
    {
        if ($this->logger) {
            call_user_func($this->logger, $action, $request, $response);
        }
    }

    private function curl($method, $url, $headers, $body, $timeout)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->stringify($body));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->stringify($body));
            }
        }
        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return array(
            'status' => $status,
            'body'   => $response === false ? '' : $response,
            'error'  => $error,
        );
    }
}
