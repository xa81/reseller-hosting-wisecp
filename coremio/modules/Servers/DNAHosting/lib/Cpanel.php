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
}
