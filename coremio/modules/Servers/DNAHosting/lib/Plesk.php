<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Plesk
{
    const ENDPOINT = '/enterprise/control/agent.php';

    private $server;
    private $http;
    private $authMode = 'key';
    private $authSettled = false;
    private $plans = null;

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
                && $this->isAuthFailure($e);
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
            $code = (string) $packet->system->errcode;
            throw new DNAHosting_Exception(self::describe($code, (string) $packet->system->errtext), (int) $code);
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
            $code = (string) $result->errcode;
            throw new DNAHosting_Exception(self::describe($code, (string) $result->errtext), (int) $code);
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

    private function isAuthFailure(DNAHosting_Exception $e)
    {
        if ($e->getCode() === 1001) {
            return true;
        }
        // Narrow fallback for malformed errors with no code: accept text match only if code is 0
        if ($e->getCode() === 0 && stripos($e->getMessage(), 'Authentication failed') !== false) {
            return true;
        }
        return false;
    }

    public function testConnection()
    {
        $packet = $this->request('<server><get><gen_info/></get></server>', 'server.get');
        self::resultOf($packet, 'server/get');
        return true;
    }

    public function listPlans()
    {
        if ($this->plans !== null) {
            return $this->plans;
        }

        $packet = $this->request(
            '<service-plan><get><filter/></get></service-plan>',
            'service-plan.get'
        );

        $list = array();
        if (isset($packet->{'service-plan'}->get->result)) {
            foreach ($packet->{'service-plan'}->get->result as $result) {
                if ((string) $result->status !== 'ok') {
                    continue;
                }
                $list[] = array(
                    'name' => (string) $result->name,
                    'guid' => (string) $result->guid,
                );
            }
        }

        $this->plans = $list;
        return $list;
    }

    public function resolvePlan($configured)
    {
        $configured = trim((string) $configured);
        if ($configured === '') {
            throw new DNAHosting_Exception('Urun icin bir plan secilmemis.');
        }

        $plans = $this->listPlans();
        $names = array();
        foreach ($plans as $plan) {
            $names[] = $plan['name'];
            if (strcasecmp($plan['name'], $configured) === 0) {
                return $plan;
            }
        }

        throw new DNAHosting_Exception(
            '"' . $configured . '" plani sunucuda bulunamadi. Mevcut planlar: '
            . ($names ? implode(', ', $names) : '(hic plan yok)')
        );
    }

    public function firstSharedIp()
    {
        $packet = $this->request('<ip><get/></ip>', 'ip.get');
        $result = self::resultOf($packet, 'ip/get');

        if (isset($result->addresses->ip)) {
            foreach ($result->addresses->ip as $row) {
                if (strcasecmp((string) $row->type, 'shared') === 0) {
                    $address = trim((string) $row->ip_address);
                    if ($address !== '') {
                        return $address;
                    }
                }
            }
        }

        return (string) $this->server['ip'];
    }

    public function findCustomer($externalId)
    {
        $packet = $this->request(
            '<customer><get><filter><external-id>' . self::esc($externalId) . '</external-id></filter>'
            . '<dataset><gen_info/></dataset></get></customer>',
            'customer.get'
        );

        try {
            $result = self::resultOf($packet, 'customer/get');
        } catch (DNAHosting_Exception $e) {
            return null;
        }

        return array(
            'id'    => (int) $result->id,
            'login' => (string) $result->data->gen_info->login,
        );
    }

    public function customerExternalId($customerId)
    {
        $packet = $this->request(
            '<customer><get><filter><id>' . (int) $customerId . '</id></filter>'
            . '<dataset><gen_info/></dataset></get></customer>',
            'customer.get'
        );

        try {
            $result = self::resultOf($packet, 'customer/get');
        } catch (DNAHosting_Exception $e) {
            return '';
        }

        return isset($result->data->gen_info->{'external-id'})
            ? (string) $result->data->gen_info->{'external-id'}
            : '';
    }

    public function findWebspace($domain)
    {
        $packet = $this->request(
            '<webspace><get><filter><name>' . self::esc($domain) . '</name></filter>'
            . '<dataset><gen_info/></dataset></get></webspace>',
            'webspace.get'
        );

        try {
            $result = self::resultOf($packet, 'webspace/get');
        } catch (DNAHosting_Exception $e) {
            return null;
        }

        return array(
            'id'       => (int) $result->id,
            'name'     => (string) $result->data->gen_info->name,
            'owner_id' => (int) $result->data->gen_info->{'owner-id'},
        );
    }

    public static function esc($value)
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
