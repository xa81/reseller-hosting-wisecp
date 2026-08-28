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

        // server.create_session yaniti PLESKSESSID'yi tasir; onu ancak cagri
        // loglandiktan sonra addSecret'e verebiliriz. Desen burada, deger
        // bilinmeden once kaydedilir. Kapsam bu eylemle sinirli oldugu icin
        // diger cagrilarin <id> alanlari (webspace, customer) okunur kalir.
        $this->http->addResponseRedaction('server.create_session', array(
            '/(<id>)[^<]*(<\/id>)/' => '$1***$2',
        ));
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

    private function resultOrNull(SimpleXMLElement $packet, $path)
    {
        try {
            return self::resultOf($packet, $path);
        } catch (DNAHosting_Exception $e) {
            if ($e->getCode() === 1013 || $e->getCode() === 1015) {
                return null;
            }
            throw $e;
        }
    }

    public function findCustomer($externalId)
    {
        $packet = $this->request(
            '<customer><get><filter><external-id>' . self::esc($externalId) . '</external-id></filter>'
            . '<dataset><gen_info/></dataset></get></customer>',
            'customer.get'
        );

        $result = $this->resultOrNull($packet, 'customer/get');
        if ($result === null) {
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

        $result = $this->resultOrNull($packet, 'customer/get');
        if ($result === null) {
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

        $result = $this->resultOrNull($packet, 'webspace/get');
        if ($result === null) {
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

    const OUR_SUSPENSION_BIT = 32;

    /**
     * Musteriyi ve aboneligi olusturur.
     *
     * Yeniden calistirilabilir olacak sekilde yazildi: kosulsuz bir customer.add,
     * zaman asimina ugrayan ya da yeniden denenen bir saglamada geride oksuz bir
     * musteri birakir ve sonraki deneme "login zaten var" ile patlar. cPanel yolunda
     * bunun icin accountSummary kurtarmasi var; Plesk tarafinin karsiligi budur.
     */
    public function createAccount(array $a)
    {
        // Uretilen hesap sifresi de bir sirdir: kayit edilmezse Http onu
        // maskelemeden loglar.
        $this->http->addSecret($a['password']);

        $plan = $this->resolvePlan($a['plan']);

        $customer   = $this->findCustomer($a['external_id']);
        $customerId = $customer === null
            ? $this->addCustomer($a)
            : (int) $customer['id'];

        // Bu domaine ait bir abonelik zaten olabilir ve BASKASININ olabilir: sonlandirilmadan
        // silinmis eski bir hizmet, elle acilmis bir site, mukerrer bir siparis. Devralmak
        // yeni musteriye hicbir sey vermeden "basarili" raporlar ve sonlandirma dahil
        // sonraki her islemi bize ait olmayan canli bir siteye dogrultur.
        $existing = $this->findWebspace($a['domain']);
        if ($existing !== null && $existing['owner_id'] !== $customerId) {
            throw new DNAHosting_Exception(
                '"' . $a['domain'] . '" icin bu sunucuda zaten bir abonelik var ve baska bir '
                . 'musteriye ait (owner-id ' . $existing['owner_id'] . '). Devralmayi reddediyoruz.'
            );
        }

        $webspaceId = $existing !== null
            ? (int) $existing['id']
            : $this->addWebspace($a, $customerId, $plan);

        return array(
            'username'    => $a['username'],
            'password'    => $a['password'],
            'customer_id' => $customerId,
            'webspace_id' => $webspaceId,
        );
    }

    private function addCustomer(array $a)
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
        return (int) self::resultOf($packet, 'customer/add')->id;
    }

    private function addWebspace(array $a, $customerId, array $plan)
    {
        $ip = $this->firstSharedIp();

        // Sira WHMCS 1.6.3.0 sablonuyla birebir: gen_setup, hosting, prefs, plan-name.
        $webspaceXml = '<webspace><add>'
            . '<gen_setup>'
            . '<name>' . self::esc($a['domain']) . '</name>'
            . '<owner-id>' . (int) $customerId . '</owner-id>'
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
            $packet = $this->request($webspaceXml, 'webspace.add');
            return (int) self::resultOf($packet, 'webspace/add')->id;
        } catch (DNAHosting_Exception $e) {
            if (!self::isResumableAddFailure($e)) {
                throw $e;
            }
            $recovered = $this->findWebspace($a['domain']);
            if ($recovered === null || $recovered['owner_id'] !== (int) $customerId) {
                throw $e;
            }
            return (int) $recovered['id'];
        }
    }

    /**
     * webspace.add hatasinin "arayip devam et" ile kapatilabilir olup olmadigi.
     *
     * Yalnizca iki durum bunu hakli kilar: Plesk "zaten var" diyor (onceki bir deneme
     * buraya kadar gelmis), ya da istek Plesk'in API katmanindan hic yanit alamamis
     * (kod 0 — zaman asimi, tasima hatasi, HTTP >= 400, bozuk XML): abonelik olusmus
     * ama yanit kaybolmus olabilir.
     *
     * Baska her hata YAYILMALIDIR. Plesk web sunucusunu yapilandirirken patladiginda
     * geride yarim bir abonelik birakir — o durumda arama BASARILI olur ve gercek hata
     * kaybolur, yani hostingi olmayan bir abonelik icin "basarili" raporlamis oluruz.
     */
    private static function isResumableAddFailure(DNAHosting_Exception $e)
    {
        if ($e->getCode() === 0) {
            return true;
        }
        return $e->getCode() === 1007
            || stripos($e->getMessage(), 'already exists') !== false;
    }

    public function webspaceStatus($webspaceId)
    {
        $packet = $this->request(
            '<webspace><get><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<dataset><gen_info/></dataset></get></webspace>',
            'webspace.get'
        );
        $result = self::resultOf($packet, 'webspace/get');
        return (int) $result->data->gen_info->status;
    }

    public function suspend($webspaceId)
    {
        return $this->setStatus($webspaceId, $this->webspaceStatus($webspaceId) | self::OUR_SUSPENSION_BIT);
    }

    public function unsuspend($webspaceId)
    {
        return $this->setStatus($webspaceId, $this->webspaceStatus($webspaceId) & ~self::OUR_SUSPENSION_BIT);
    }

    private function setStatus($webspaceId, $status)
    {
        $packet = $this->request(
            '<webspace><set><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<values><gen_setup><status>' . (int) $status . '</status></gen_setup></values>'
            . '</set></webspace>',
            'webspace.set'
        );
        self::resultOf($packet, 'webspace/set');
        return true;
    }

    /**
     * Aboneligi siler; musteriyi ancak GUVENLIYSE siler.
     *
     * Sahiplik guardi (external-id) musterinin bizim oldugunu kanitlar — cagirmadan once
     * DNAHosting_Module::pleskTargets() icinde dogrulanir. Ama musterinin YALNIZCA bu
     * abonelige sahip oldugunu kanitlamaz: panelde ayni musteri altina ikinci bir alan adi
     * eklenmisse, musteriyi silmek bir WiseCP hizmetini sonlandirirken digerinin sitesini
     * de sessizce silerdi. Bu yuzden once webspace id ile silinir, sonra musterinin kalan
     * abonelikleri sayilir ve musteri yalnizca sifirda kaldirilir. Sayim COZULEMEZSE
     * "bos degil" sayilir.
     */
    public function terminate($webspaceId, $customerId, $expectedExternalId)
    {
        $webspaceId = (int) $webspaceId;
        if ($webspaceId <= 0) {
            throw new DNAHosting_Exception(
                'Silme reddedildi: bu hizmet icin bir Plesk abonelik kimligi cozulemedi. '
                . 'Kimliksiz bir filtre sunucudaki her abonelikle eslesir.'
            );
        }

        $packet = $this->request(
            '<webspace><del><filter><id>' . $webspaceId . '</id></filter></del></webspace>',
            'webspace.del'
        );
        try {
            self::resultOf($packet, 'webspace/del');
        } catch (DNAHosting_Exception $e) {
            // 1013 "nesne bulunamadi" zaten varmak istedigimiz son durumdur.
            if ($e->getCode() !== 1013) {
                throw $e;
            }
        }

        $customerId = (int) $customerId;
        if ($customerId <= 0) {
            return true;
        }

        if ($this->countWebspacesOwnedBy($customerId) > 0) {
            return true;
        }

        // Son kapi: silinecek musteri gercekten bizim external-id'mizi tasiyor mu.
        if ($this->customerExternalId($customerId) !== (string) $expectedExternalId) {
            return true;
        }

        $packet = $this->request(
            '<customer><del><filter><id>' . $customerId . '</id></filter></del></customer>',
            'customer.del'
        );
        self::resultOf($packet, 'customer/del');
        return true;
    }

    /**
     * Musteriye ait abonelik sayisi. Sayim cozulemezse 1 doner — yani "bos degil".
     * Bosluk kanitlanamadan bir musteri silinmez.
     */
    private function countWebspacesOwnedBy($customerId)
    {
        try {
            $packet = $this->request(
                '<webspace><get><filter><owner-id>' . (int) $customerId . '</owner-id></filter>'
                . '<dataset><gen_info/></dataset></get></webspace>',
                'webspace.get.byowner'
            );
        } catch (DNAHosting_Exception $e) {
            return 1;
        }

        if (!isset($packet->webspace->get->result)) {
            return 1;
        }

        $count = 0;
        foreach ($packet->webspace->get->result as $result) {
            if ((string) $result->status === 'ok') {
                $count++;
            }
        }
        return $count;
    }

    public function changePassword($customerId, $webspaceId, $password)
    {
        $this->http->addSecret($password);

        $packet = $this->request(
            '<customer><set><filter><id>' . (int) $customerId . '</id></filter>'
            . '<values><gen_info><passwd>' . self::esc($password) . '</passwd></gen_info></values>'
            . '</set></customer>',
            'customer.set'
        );
        self::resultOf($packet, 'customer/set');

        $packet = $this->request(
            '<webspace><set><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<values><hosting><vrt_hst>'
            . '<property><name>ftp_password</name><value>' . self::esc($password) . '</value></property>'
            . '</vrt_hst></hosting></values>'
            . '</set></webspace>',
            'webspace.set'
        );
        self::resultOf($packet, 'webspace/set');
        return true;
    }

    public function changePlan($webspaceId, array $plan)
    {
        $packet = $this->request(
            '<webspace><switch-subscription><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<plan-guid>' . self::esc($plan['guid']) . '</plan-guid>'
            . '</switch-subscription></webspace>',
            'webspace.switch-subscription'
        );
        self::resultOf($packet, 'webspace/switch-subscription');
        return true;
    }

    public function usage($webspaceId)
    {
        $packet = $this->request(
            '<webspace><get><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<dataset><stat/><limits/></dataset></get></webspace>',
            'webspace.get'
        );
        $result = self::resultOf($packet, 'webspace/get');

        $limits = array();
        if (isset($result->data->limits->limit)) {
            foreach ($result->data->limits->limit as $limit) {
                $limits[(string) $limit->name] = (float) $limit->value;
            }
        }

        return array(
            'disk_used'  => isset($result->data->stat->real_size) ? (int) $result->data->stat->real_size : 0,
            'disk_limit' => self::limitToBytes($limits, 'disk_space'),
            'bw_used'    => isset($result->data->stat->traffic) ? (int) $result->data->stat->traffic : 0,
            'bw_limit'   => self::limitToBytes($limits, 'max_traffic'),
        );
    }

    private static function limitToBytes(array $limits, $name)
    {
        if (!isset($limits[$name]) || $limits[$name] < 0) {
            return 0;
        }
        return (int) $limits[$name];
    }

    public function createSession($login, $clientIp)
    {
        $packet = $this->request(
            '<server><create_session>'
            . '<login>' . self::esc($login) . '</login>'
            . '<data><user_ip>' . self::esc($clientIp) . '</user_ip><source_server/></data>'
            . '</create_session></server>',
            'server.create_session'
        );
        $result = self::resultOf($packet, 'server/create_session');

        $sessionId = (string) $result->id;
        if ($sessionId === '') {
            throw new DNAHosting_Exception('Plesk oturum kimligi dondurmedi.');
        }

        // PLESKSESSID canli bir kimlik bilgisidir; sir olarak kaydedilirse sonraki her
        // log satirinda maskelenir.
        $this->http->addSecret($sessionId);

        return ($this->server['secure'] ? 'https' : 'http') . '://'
            . $this->server['ip'] . ':' . $this->server['port']
            . '/enterprise/rsession_init.php?PLESKSESSID=' . rawurlencode($sessionId);
    }
}
