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

        // create_user_session yanitinin KENDISI canli bir kimlik bilgisidir; onu
        // ancak cagri loglandiktan sonra addSecret'e verebiliriz. Desen burada,
        // deger bilinmeden once kaydedilir. Yalnizca kimlik alanlari gizlenir:
        // metadata/reason teshis icin gorunur kalir.
        $this->http->addResponseRedaction('create_user_session', array(
            '/("(?:url|session|cp_security_token)"\s*:\s*")[^"]*(")/i' => '$1***$2',
            '/cpsess[0-9]+/i' => 'cpsess***',
        ));
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
                . $this->http->safeSummary($function, $body)
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
        $this->http->addSecret($a['password']);
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
            try {
                $summary = $this->accountSummary($a['username']);
                if ($summary && isset($summary['domain'])
                    && strcasecmp($summary['domain'], $a['domain']) === 0) {
                    // Hesap aslinda acilmis, yalnizca yanit gecikmis.
                    return array('username' => $a['username'], 'password' => $a['password']);
                }
            } catch (DNAHosting_Exception $summaryError) {
                // accountsummary icin bir hata oldu, ilk hatayi tekrar firlat
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
            $message = $e->getMessage();
            if (stripos($message, 'does not exist') !== false || stripos($message, 'not exist') !== false) {
                return null;
            }
            throw $e;
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
        $this->http->addSecret($password);
        $this->call('passwd', array('user' => $username, 'password' => $password));
        return true;
    }

    public function changePackage($username, $plan)
    {
        $this->call('changepackage', array('user' => $username, 'pkg' => $this->resolvePackage($plan)));
        return true;
    }

    /** Disk accountsummary'den, trafik showbw'den — bkz. bandwidth(). */
    public function usage($username)
    {
        $summary = $this->accountSummary($username);
        if (!$summary) {
            throw new DNAHosting_Exception('"' . $username . '" hesabi sunucuda bulunamadi.');
        }

        $bandwidth = $this->bandwidth($username);

        return array(
            'disk_used'  => self::toBytes(isset($summary['diskused']) ? $summary['diskused'] : 0),
            'disk_limit' => self::toBytes(isset($summary['disklimit']) ? $summary['disklimit'] : 0),
            'bw_used'    => $bandwidth['used'],
            'bw_limit'   => $bandwidth['limit'],
        );
    }

    /**
     * Bir hesabin bu ayki trafigi ve trafik siniri, BAYT olarak.
     *
     * Kaynak bilerek showbw: sinirin bayt olarak bildirildigi tek yer orasidir.
     * listaccts'te de bir bwlimit var ama BASKA bir birimde; ikisini karistirmak
     * siniri yaklasik 10^6 katsayisiyla yanlis gosterir ve her musteriyi %100 dolu
     * olarak raporlar. accountsummary'nin totalbytes/limit alanlari ise cogu surumde
     * hic bulunmaz — o zaman da trafik sessizce "0 / sonsuz" cikar. Iki hata da
     * sessiz oldugu icin kaynak tek yerde sabitlenmistir.
     *
     * showbw argumansiz cagrilir: bayinin kendi hesaplarini dondurur ve satir
     * kullanici adina gore burada secilir — WHMCS modulunun canlida dogrulanmis
     * davranisi da budur.
     */
    public function bandwidth($username)
    {
        $rows = self::bandwidthRows($this->call('showbw'));

        foreach ($rows as $row) {
            if (!isset($row['user']) || strcasecmp((string) $row['user'], (string) $username) !== 0) {
                continue;
            }
            return array(
                'used'  => isset($row['totalbytes']) ? self::toBytes($row['totalbytes'], 1) : 0,
                'limit' => isset($row['limit']) ? self::toBytes($row['limit'], 1) : 0,
            );
        }

        // Hesap showbw ciktisinda yoksa (ornegin yeni acilmis) henuz trafik olusmamistir.
        return array('used' => 0, 'limit' => 0);
    }

    /** showbw hesap satirlari; WHM surumleri arasinda zarf sekli degisebiliyor. */
    private static function bandwidthRows(array $data)
    {
        if (isset($data['bandwidth'][0]['acct']) && is_array($data['bandwidth'][0]['acct'])) {
            return $data['bandwidth'][0]['acct'];
        }
        if (isset($data['bandwidth']['acct']) && is_array($data['bandwidth']['acct'])) {
            return $data['bandwidth']['acct'];
        }
        if (isset($data['acct']) && is_array($data['acct'])) {
            return $data['acct'];
        }
        return array();
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

    /**
     * Musteri icin tek kullanimlik giris URLsi.
     *
     * WHM API 1'in create_user_session'i yalnizca user ve service alir. WiseCP'nin kendi
     * cPanel modulu de (coremio/modules/Servers/cPanel/cPanel.php:1205, :1259), WHMCS'in
     * kendi modulu de baska bir sey gondermez: client_ip diye bir arguman yoktur — WHM
     * oturumu gordugu adrese baglar — ve bos bir locale WHM tarafindan harfiyen alinabilir.
     * $clientIp imzada kaliyor cunku Plesk surucusu onu gercekten kullaniyor.
     */
    public function createSession($username, $service = 'cpaneld', $clientIp = '')
    {
        $data = $this->call('create_user_session', array('user' => $username, 'service' => $service));
        if (empty($data['url'])) {
            throw new DNAHosting_Exception('Sunucu oturum baglantisi dondurmedi.');
        }

        $url = (string) $data['url'];
        if (strpos($url, 'http') !== 0) {
            $scheme = $this->server['secure'] ? 'https' : 'http';
            $url    = $scheme . '://' . $this->server['ip'] . ':2083' . $url;
        }

        // WHM her zaman SSL portunda bir https URLsi uretir. secure=0 bir sunucuda sema
        // ve port BIRLIKTE indirilmelidir; yalnizca semayi degistirmek http://host:2083
        // verir ve orada dinleyen bir sey yoktur.
        if (empty($this->server['secure'])) {
            $url = str_replace(
                array('https:', ':2087', ':2083', ':2096'),
                array('http:', ':2086', ':2082', ':2095'),
                $url
            );
        }

        // URL bir tarayiciya veriliyor: duz http(s) olmayan hicbir seyi kabul etme —
        // baska bir seyle yanit veren bir panel, musteriyi gonderecegimiz yer degildir.
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host']) || !isset($parts['scheme'])
            || !in_array(strtolower($parts['scheme']), array('http', 'https'), true)) {
            throw new DNAHosting_Exception('Sunucu kullanilamaz bir oturum baglantisi dondurdu.');
        }

        // cpsess... jetonu ayri bir alanda degil, yolun icinde gelir: URLnin kendisi canli
        // bir kimlik bilgisidir. Sir olarak kaydedilirse sonraki her log satirinda maskelenir.
        $this->http->addSecret($url);

        return $url;
    }
}
