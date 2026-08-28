<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Cpanel
{
    private $server;
    private $http;
    private $packages = null;

    private static $readOnly = array(
        'listaccts', 'listpkgs', 'accountsummary', 'myprivs', 'showbw', 'version',
    );

    public function __construct(array $server, DNAHosting_Http $http)
    {
        $this->server = $server;
        $this->http   = $http;
        $this->http->addSecret($server['password']);
    }

    public function call($function, array $args = array())
    {
        $read    = in_array($function, self::$readOnly, true);
        $args    = array_merge(array('api.version' => 1), $args);
        $query   = http_build_query($args);
        $headers = array(
            'Authorization: WHM ' . $this->server['username'] . ':' . $this->server['password'],
        );

        $this->http->setTimeout($read ? 30 : 400);

        if ($read) {
            $result = $this->http->send('GET', '/json-api/' . $function . '?' . $query, $headers, null, $function);
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $result = $this->http->send('POST', '/json-api/' . $function, $headers, $query, $function);
        }

        return $this->unwrap($result['body'], $function);
    }

    private function unwrap($body, $function)
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new DNAHosting_Exception(
                $function . ': sunucu gecerli JSON dondurmedi. '
                . DNAHosting_Http::summarise($body)
            );
        }

        if (isset($decoded['cpanelresult'])) {
            $inner = $decoded['cpanelresult'];
            $why   = isset($inner['error']) ? $inner['error'] : 'bilinmeyen hata';
            throw new DNAHosting_Exception(
                'Sunucu WHM yerine cPanel kullanici APIsi ile yanit verdi — bu kullanicinin WHM erisimi yok. '
                . 'WHM > Reseller Center uzerinden ACL yetkilerini kontrol edin. Panelin dedigi: ' . $why
            );
        }

        if (isset($decoded['metadata']['result']) && (int) $decoded['metadata']['result'] !== 1) {
            $why = isset($decoded['metadata']['reason']) ? $decoded['metadata']['reason'] : 'sebep bildirilmedi';
            throw new DNAHosting_Exception($why);
        }

        return isset($decoded['data']) ? $decoded['data'] : $decoded;
    }

    public function testConnection()
    {
        $this->call('listaccts', array('want' => 'domain'));
        return true;
    }

    public function listPackages()
    {
        if ($this->packages !== null) {
            return $this->packages;
        }
        $data = $this->call('listpkgs');
        $list = array();
        $rows = isset($data['pkg']) ? $data['pkg'] : array();
        foreach ($rows as $row) {
            if (!isset($row['name'])) {
                continue;
            }
            $list[] = array(
                'name'    => (string) $row['name'],
                'quota'   => isset($row['QUOTA']) ? (string) $row['QUOTA'] : '',
                'bwlimit' => isset($row['BWLIMIT']) ? (string) $row['BWLIMIT'] : '',
            );
        }
        $this->packages = $list;
        return $list;
    }

    public function dePrefix($name)
    {
        $name = (string) $name;
        $at   = strpos($name, '_');
        return $at === false ? $name : substr($name, $at + 1);
    }

    public function resolvePackage($configured)
    {
        $configured = trim((string) $configured);
        if ($configured === '') {
            throw new DNAHosting_Exception('Urun icin bir paket secilmemis.');
        }

        $packages = $this->listPackages();
        $names    = array();
        foreach ($packages as $package) {
            $names[] = $package['name'];
            if (strcasecmp($package['name'], $configured) === 0) {
                return $package['name'];
            }
        }
        foreach ($packages as $package) {
            if (strcasecmp($this->dePrefix($package['name']), $configured) === 0) {
                return $package['name'];
            }
        }

        throw new DNAHosting_Exception(
            '"' . $configured . '" paketi sunucuda bulunamadi. Mevcut paketler: '
            . ($names ? implode(', ', $names) : '(hic paket yok)')
        );
    }

    public function createAccount(array $a)
    {
        $plan = $this->resolvePackage($a['plan']);

        $args = array(
            'username'    => $a['username'],
            'domain'      => $a['domain'],
            'password'    => $a['password'],
            'plan'        => $plan,
            'contactemail' => isset($a['email']) ? $a['email'] : '',
        );

        try {
            $this->call('createacct', $args);
        } catch (DNAHosting_Exception $e) {
            $summary = $this->accountSummary($a['username']);
            if ($summary && isset($summary['domain'])
                && strcasecmp($summary['domain'], $a['domain']) === 0) {
                // Hesap aslinda acilmis, yalnizca yanit gecikmis.
                return array('username' => $a['username'], 'password' => $a['password']);
            }
            throw $e;
        }

        return array('username' => $a['username'], 'password' => $a['password']);
    }

    public function accountSummary($username)
    {
        try {
            $data = $this->call('accountsummary', array('user' => $username));
        } catch (DNAHosting_Exception $e) {
            return null;
        }
        if (isset($data['acct'][0])) {
            return $data['acct'][0];
        }
        return null;
    }

    public function suspendAccount($username, $reason = '')
    {
        $this->call('suspendacct', array('user' => $username, 'reason' => $reason));
        return true;
    }

    public function unsuspendAccount($username)
    {
        $this->call('unsuspendacct', array('user' => $username));
        return true;
    }

    public function terminateAccount($username)
    {
        $this->call('removeacct', array('user' => $username, 'keepdns' => 0));
        return true;
    }

    public function changePassword($username, $password)
    {
        $this->call('passwd', array('user' => $username, 'password' => $password));
        return true;
    }

    public function changePackage($username, $plan)
    {
        $this->call('changepackage', array('user' => $username, 'pkg' => $this->resolvePackage($plan)));
        return true;
    }

    public function usage($username)
    {
        $summary = $this->accountSummary($username);
        if (!$summary) {
            throw new DNAHosting_Exception('"' . $username . '" hesabi sunucuda bulunamadi.');
        }

        return array(
            'disk_used'  => self::toBytes(isset($summary['diskused']) ? $summary['diskused'] : 0),
            'disk_limit' => self::toBytes(isset($summary['disklimit']) ? $summary['disklimit'] : 0),
            'bw_used'    => self::toBytes(isset($summary['totalbytes']) ? $summary['totalbytes'] : 0, 1),
            'bw_limit'   => self::toBytes(isset($summary['limit']) ? $summary['limit'] : 0, 1),
        );
    }

    /**
     * cPanel disk degerlerini "512M" gibi son ekle, trafigi ise cift bayt olarak verir.
     * $bareIsBytes=1 ise son eksiz sayilar bayt, degilse megabayt sayilir.
     */
    public static function toBytes($value, $bareIsBytes = 0)
    {
        $value = trim((string) $value);
        if ($value === '' || strcasecmp($value, 'unlimited') === 0) {
            return 0;
        }
        $unit   = strtoupper(substr($value, -1));
        $number = (float) $value;
        $scales = array('K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776);
        if (isset($scales[$unit])) {
            return (int) round($number * $scales[$unit]);
        }
        return (int) round($number * ($bareIsBytes ? 1 : 1048576));
    }

    public function createSession($username, $service = 'cpaneld', $clientIp = '')
    {
        $args = array('user' => $username, 'service' => $service);
        if ($clientIp !== '') {
            $args['locale'] = '';
            $args['client_ip'] = $clientIp;
        }
        $data = $this->call('create_user_session', $args);
        if (empty($data['url'])) {
            throw new DNAHosting_Exception('Sunucu oturum baglantisi dondurmedi.');
        }

        $url = (string) $data['url'];
        if (strpos($url, 'http') !== 0) {
            $scheme = $this->server['secure'] ? 'https' : 'http';
            $port   = $service === 'whostmgrd' ? 2087 : 2083;
            $url    = $scheme . '://' . $this->server['ip'] . ':' . $port . $url;
        }
        return $url;
    }
}
