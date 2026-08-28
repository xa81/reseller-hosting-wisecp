<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Http
{
    private $base;
    private $timeout   = 30;
    private $transport = null;
    private $logger    = null;
    private $secrets   = array();
    private $redactions = array();

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

    /**
     * Bir eylemin YANIT govdesine, loga yazilmadan once uygulanacak desenler.
     *
     * addSecret() yalnizca degeri ONCEDEN bilinen sirlar icindir. Oturum acma
     * cagrilarinda kimlik bilgisi yanitin KENDISINDE gelir: cagiran onu ancak
     * send() satiri loglandiktan SONRA addSecret'e verebilir, yani jetonu tasiyan
     * satirin kendisi maskesiz kalir. Desen ise deger bilinmeden once kaydedilir.
     *
     * Kapsam bilerek eylem adidir, tek atimlik bir bayrak degil: Plesk request()
     * kimlik hatasinda IKINCI bir istek atar ve tek atimlik bir bayrak orada
     * tukenip asil yaniti maskesiz birakirdi.
     *
     * @param string $action   send()'e verilen eylem adi
     * @param array  $patterns desen => yerine konacak metin (preg_replace)
     */
    public function addResponseRedaction($action, array $patterns)
    {
        $action = (string) $action;
        if (!isset($this->redactions[$action])) {
            $this->redactions[$action] = array();
        }
        foreach ($patterns as $pattern => $replacement) {
            $this->redactions[$action][$pattern] = $replacement;
        }
        return $this;
    }

    /**
     * Kayitli desenleri uygular. Desen calistirilamazsa (ornegin PCRE geri izleme
     * siniri) govde OLDUGU GIBI BIRAKILMAZ; bir guvenlik denetiminin sessizce
     * acilmasindansa teshis kaybi yeglenir.
     */
    private function redactResponse($action, $body)
    {
        if (empty($this->redactions[$action])) {
            return $body;
        }
        foreach ($this->redactions[$action] as $pattern => $replacement) {
            $replaced = preg_replace($pattern, $replacement, $body);
            if ($replaced === null) {
                return '[yanit gizlendi: redaksiyon uygulanamadi]';
            }
            $body = $replaced;
        }
        return $body;
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
            // Mesaj $this->error e, oradan admin arayuzune ve openPanel() uzerinden
            // musterinin tarayicisina kadar gidiyor; log gibi o da maskelenmeli.
            throw new DNAHosting_Exception($this->mask($result['error']));
        }

        $status = (int) $result['status'];
        $rbody  = (string) $result['body'];

        // Redaksiyon YALNIZCA log ve hata kopyasina uygulanir: cagirana ham govde
        // doner, yoksa oturum URLsi ayristirilamazdi.
        $safeBody = $this->redactResponse($action, $rbody);
        $this->log($action, $logRequest, $this->mask('HTTP ' . $status . "\n\n" . $safeBody));

        if ($status >= 400) {
            // Ozet de redakte edilmis govdeden alinir: bu metin hata mesaji olarak
            // admin arayuzune ve musterinin tarayicisina kadar gidiyor.
            $summary = self::summarise($safeBody);
            throw new DNAHosting_Exception($this->mask('HTTP ' . $status . ($summary ? ': ' . $summary : '')));
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
        // Bayt bazli kirpma cok baytli bir karakterin ortasindan gecip bozuk UTF-8
        // uretebilir; bu metin sonra hata mesaji olarak sayfaya ciziliyor.
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $limit) {
                $text = mb_substr($text, 0, $limit, 'UTF-8') . '...';
            }
        } elseif (strlen($text) > $limit) {
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
