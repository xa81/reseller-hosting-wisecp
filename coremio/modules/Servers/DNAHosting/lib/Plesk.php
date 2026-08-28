<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Plesk
{
    const ENDPOINT = '/enterprise/control/agent.php';

    private $server;
    private $http;
    private $authMode = 'key';
    private $authSettled = false;

    public function __construct(array $server, DNAHosting_Http $http)
    {
        $this->server = $server;
        $this->http   = $http;
        $this->http->addSecret($server['password']);
        $this->http->setTimeout(300);
    }

    public function setAuthMode($mode)
    {
        if ($mode === 'key' || $mode === 'basic') {
            $this->authMode    = $mode;
            $this->authSettled = true;
        }
        return $this;
    }

    public function authMode()
    {
        return $this->authMode;
    }

    public function request($bodyXml, $action)
    {
        try {
            return $this->attempt($bodyXml, $action, $this->authMode);
        } catch (DNAHosting_Exception $e) {
            $canFallBack = !$this->authSettled
                && $this->authMode === 'key'
                && $this->isAuthFailure($e->getMessage());
            if (!$canFallBack) {
                throw $e;
            }
        }

        $packet = $this->attempt($bodyXml, $action, 'basic');
        $this->authMode    = 'basic';
        $this->authSettled = true;
        return $packet;
    }

    private function attempt($bodyXml, $action, $mode)
    {
        $headers = array('Content-Type: text/xml', 'HTTP_PRETTY_PRINT: TRUE');
        if ($mode === 'key') {
            $headers[] = 'KEY: ' . $this->server['password'];
        } else {
            $headers[] = 'HTTP_AUTH_LOGIN: ' . $this->server['username'];
            $headers[] = 'HTTP_AUTH_PASSWD: ' . $this->server['password'];
        }

        $body     = '<?xml version="1.0" encoding="UTF-8"?><packet>' . $bodyXml . '</packet>';
        $response = $this->http->send('POST', self::ENDPOINT, $headers, $body, $action);

        $previous = libxml_use_internal_errors(true);
        $packet   = simplexml_load_string($response['body']);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($packet === false || $packet->getName() !== 'packet') {
            throw new DNAHosting_Exception(
                $action . ': sunucu gecerli XML dondurmedi. ' . DNAHosting_Http::summarise($response['body'])
            );
        }

        if (isset($packet->system->status) && (string) $packet->system->status === 'error') {
            throw new DNAHosting_Exception(self::describe(
                (string) $packet->system->errcode,
                (string) $packet->system->errtext
            ));
        }

        if ($mode === 'key') {
            $this->authMode = 'key';
        }
        return $packet;
    }

    public static function resultOf(SimpleXMLElement $packet, $path)
    {
        $node = $packet;
        foreach (explode('/', $path) as $step) {
            if (!isset($node->{$step})) {
                throw new DNAHosting_Exception('Plesk yanitinda <' . $step . '> bulunamadi.');
            }
            $node = $node->{$step};
        }
        if (!isset($node->result)) {
            throw new DNAHosting_Exception('Plesk yanitinda <result> bulunamadi.');
        }

        $result = $node->result;
        if ((string) $result->status === 'error') {
            throw new DNAHosting_Exception(self::describe(
                (string) $result->errcode,
                (string) $result->errtext
            ));
        }
        return $result;
    }

    private static function describe($code, $text)
    {
        $text = trim($text) !== '' ? trim($text) : 'sebep bildirilmedi';
        $hint = '';
        if ($code === '11003') {
            $hint = ' Bu anahtar baska bir IP adresi icin uretilmis;'
                . ' Plesk > Tools & Settings > API anahtarlarindan bu sunucunun IP adresi icin yenisini olusturun.';
        } elseif ($code === '1014') {
            $hint = ' Istek govdesinde bir eleman Pleskin bekledigi yerde degil.';
        } elseif ($code === '1013' || $code === '1015') {
            $hint = ' Aranan nesne panelde bulunamadi.';
        }
        return 'Plesk (' . ($code !== '' ? $code : '?') . '): ' . $text . $hint;
    }

    private function isAuthFailure($message)
    {
        return stripos($message, 'Authentication failed') !== false
            || stripos($message, 'Permission denied') !== false
            || strpos($message, '(1001)') !== false;
    }

    public function testConnection()
    {
        $packet = $this->request('<server><get><gen_info/></get></server>', 'server.get');
        self::resultOf($packet, 'server/get');
        return true;
    }
}
