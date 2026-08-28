<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Module extends ServerModule
{
    public $force_setup = false;

    private $panel     = null;
    private $drivers   = array();
    private $transport = null;
    private $storage   = array();

    public function __construct($server, $options = array())
    {
        $this->_name = __CLASS__;
        parent::__construct($server, $options);
    }

    protected function define_server_info($server = array())
    {
        if (!class_exists('DNAHosting_Http')) {
            include __DIR__ . DS . 'init.php';
        }
    }

    /** Testler icin: gercek cURL yerine sahte tasiyici enjekte eder. */
    public function useTransport(callable $transport)
    {
        $this->transport = $transport;
        $this->panel     = null;
        $this->drivers   = array();
        return $this;
    }

    private function http()
    {
        $scheme = $this->server['secure'] ? 'https' : 'http';
        $http   = new DNAHosting_Http($scheme . '://' . $this->server['ip'] . ':' . $this->server['port']);

        if ($this->transport) {
            $http->setTransport($this->transport);
        }

        $name = $this->_name;
        $http->setLogger(function ($action, $request, $response) use ($name) {
            Modules::save_log('Servers', $name, $action, $request, $response, '');
        });

        return $http;
    }

    private function makeDriver($panel)
    {
        if ($panel === 'plesk') {
            return new DNAHosting_Plesk($this->server, $this->http());
        }
        return new DNAHosting_Cpanel($this->server, $this->http());
    }

    /** Tespit edilen panel: 'cpanel' veya 'plesk'. Tespit basarisizsa firlatir. */
    public function panel()
    {
        if ($this->panel !== null) {
            return $this->panel;
        }

        $self     = $this;
        $detector = new DNAHosting_Detector($this->server, function ($panel) use ($self) {
            return $self->driverFor($panel);
        });

        $detector->setCache(
            function ($key) { return DNAHosting_Module::cacheRead($key); },
            function ($key, $value) { DNAHosting_Module::cacheWrite($key, $value); }
        );

        $found       = $detector->detect();
        $this->panel = $found['panel'];

        if ($this->panel === 'plesk' && !empty($found['auth'])) {
            $this->driverFor('plesk')->setAuthMode($found['auth']);
        }

        return $this->panel;
    }

    /** Panel basina tek surucu ornegi tutar (istek ici bellek). */
    public function driverFor($panel)
    {
        if (!isset($this->drivers[$panel])) {
            $this->drivers[$panel] = $this->makeDriver($panel);
        }
        return $this->drivers[$panel];
    }

    /** Aktif panelin surucusu. */
    public function driver()
    {
        return $this->driverFor($this->panel());
    }

    public static function cacheRead($key)
    {
        if (!class_exists('Cache')) {
            return null;
        }
        try {
            $cache = new Cache('dnahosting');
            if (!$cache->isCached($key)) {
                return null;
            }
            $value = $cache->retrieve($key);
            return is_array($value) ? $value : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function cacheWrite($key, array $value)
    {
        if (!class_exists('Cache')) {
            return;
        }
        try {
            $cache = new Cache('dnahosting');
            $cache->store($key, $value, 604800);
        } catch (Throwable $e) {
            // Onbellek saf optimizasyon; lisans alan adi tutmazsa sessizce iskalar.
        }
    }

    /**
     * Surucu istisnasini WiseCP sozlesmesine cevirir: $this->error + false.
     *
     * Exception degil Throwable alir. Bir surucuden gelen TypeError Exception turevi
     * degildir; yalnizca Exception yakalanirsa WiseCP'ye fatal olarak kacar, kullanici
     * bos bir hata gorur ve islem "failed" olarak bile kaydedilmez.
     */
    private function failed(Throwable $e)
    {
        $this->error = $e->getMessage();
        return false;
    }

    public function testConnect()
    {
        try {
            $this->panel();
            return true;
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    public function getPlans()
    {
        try {
            if ($this->panel() === 'plesk') {
                return $this->driver()->listPlans();
            }
            return $this->driver()->listPackages();
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    /** Ayri bir metoda cikarildi: testler ADMINISTRATOR sabitini tanimlamadan admin dalini sinayabilsin diye. */
    protected function isAdminArea()
    {
        return defined('ADMINISTRATOR');
    }

    public function use_method($param = '')
    {
        $param  = str_replace('-', '_', $param);
        $prefix = $this->isAdminArea() ? 'use_adminArea_' : 'use_clientArea_';
        if ($param === '') {
            return null;
        }
        if (!method_exists($this, $prefix . $param)) {
            return null;
        }
        return $this->{$prefix . $param}();
    }

    public function externalId()
    {
        return 'wisecp-' . (isset($this->order['id']) ? (int) $this->order['id'] : 0);
    }

    public function UsernameGenerator($domain = '', $half_mixed = false)
    {
        try {
            $panel = $this->panel();
        } catch (Throwable $e) {
            // Panel tespiti basarisiz oldu; sessizce cPanel kurallarina duser (8 karakter,
            // harfle basla, [a-z0-9]) — bu kurallara uyan bir ad Plesk icin de gecerlidir,
            // bu yuzden dogru panel sonradan belirlense bile ad reddedilmez. Bu bir
            // hata degil, zarif bir geri dusustur; $this->error kasitli olarak set edilmez.
            $panel = 'cpanel';
        }
        return DNAHosting_Support::usernameFor($domain, $panel);
    }

    /** Urunun kendi module_data'sindaki paket adi (urun formunun yazdigi yer). */
    private function productPlan()
    {
        $moduleData = isset($this->product['module_data']) ? $this->product['module_data'] : array();
        if (is_string($moduleData)) {
            $moduleData = json_decode($moduleData, true);
        }
        if (!is_array($moduleData)) {
            return '';
        }
        if (isset($moduleData['create_account']['plan'])
            && trim((string) $moduleData['create_account']['plan']) !== '') {
            return (string) $moduleData['create_account']['plan'];
        }
        if (isset($moduleData['plan']) && trim((string) $moduleData['plan']) !== '') {
            return (string) $moduleData['plan'];
        }
        return '';
    }

    /** Siparise olusturma aninda kopyalanmis paket adi. */
    private function creationPlan(array $options)
    {
        $creation = isset($options['creation_info']) ? $options['creation_info'] : array();
        if (isset($creation['plan']) && trim((string) $creation['plan']) !== '') {
            return (string) $creation['plan'];
        }
        return '';
    }

    /**
     * Paket adini cozer.
     *
     * $preferProduct = false (hesap acma): siparisin creation_info'su onceliklidir; orada
     * o siparis icin secilmis paket durur.
     * $preferProduct = true (yukseltme/dusurme): urunun module_data'si onceliklidir — bkz.
     * apply_updowngrade().
     */
    private function planOf(array $options, $preferProduct = false)
    {
        $fromProduct  = $this->productPlan();
        $fromCreation = $this->creationPlan($options);

        if ($preferProduct) {
            return $fromProduct !== '' ? $fromProduct : $fromCreation;
        }
        return $fromCreation !== '' ? $fromCreation : $fromProduct;
    }

    public function createAccount($domain, $options = array())
    {
        try {
            $panel    = $this->panel();
            $domain   = DNAHosting_Support::domainKey($domain);
            $username = isset($options['username']) && $options['username'] !== ''
                ? $options['username']
                : DNAHosting_Support::usernameFor($domain, $panel);
            $password = isset($options['password']) && $options['password'] !== ''
                ? $options['password']
                : DNAHosting_Support::password(14);

            $account = array(
                'username'    => $username,
                'password'    => $password,
                'domain'      => $domain,
                'plan'        => $this->planOf($options),
                'email'       => isset($this->user['email']) ? $this->user['email'] : '',
                'name'        => isset($this->user['full_name']) ? $this->user['full_name'] : $username,
                'external_id' => $this->externalId(),
            );

            $created = $this->driver()->createAccount($account);

            // Sifre burada bilerek acik metin: WiseCP cekirdeginin createAccount kaydetme
            // yolu ftp_info.password'u kendisi Crypt::encode() ile sarmalayip oyle saklar
            // (coremio/helpers/orders.php:2851-2853). Burada onceden kodlarsak cift kodlama
            // olur ve cekirdegin decode_str() cagrisi cozemez.
            return array(
                'username' => $created['username'],
                'password' => $created['password'],
                'ftp_info' => array(
                    'ip'       => $this->server['ip'],
                    'host'     => 'ftp.' . $domain,
                    'username' => $created['username'],
                    'password' => $created['password'],
                    'port'     => 21,
                ),
            );
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    /**
     * Plesk islemleri icin abonelik + musteri kimligini domainden yeniden turetir ve
     * cifti SAHIPLIK ACISINDAN DOGRULAR.
     *
     * §2.5 geregi Plesk'in id'lerini siparise yazamiyoruz, bu yuzden kimlik her istekte
     * yeniden bulunuyor. findWebspace() ise o alan adini tasiyan HANGI abonelik varsa onu
     * dondurur — sahiplik hakkinda hicbir sey soylemez. Dogrulama yalnizca terminate()
     * icinde yapilirken suspend(), unsuspend(), changePassword(), changePlan() ve usage()
     * korumasizdi; en somut zarar changePassword()'dur, cunku hem Plesk musterisinin
     * giris sifresini hem webspace'in FTP sifresini o alan adi HANGI abonelige cozuluyorsa
     * onun uzerinde sifirlar.
     *
     * Dogrulama burada bir kez yapilir ve dogrulanmis cift ezberlenir; bes islem de guardi
     * miras alir.
     */
    private function pleskTargets()
    {
        if (isset($this->storage['plesk_targets'])) {
            return $this->storage['plesk_targets'];
        }

        $driver   = $this->driver();
        $domain   = DNAHosting_Support::domainKey($this->orderDomain());
        $webspace = $driver->findWebspace($domain);
        if (!$webspace) {
            throw new DNAHosting_Exception(
                '"' . $domain . '" alan adina ait abonelik panelde bulunamadi.'
            );
        }

        $expected = $this->externalId();
        $actual   = $driver->customerExternalId($webspace['owner_id']);
        if ($actual !== (string) $expected) {
            throw new DNAHosting_Exception(
                'Guvenlik nedeniyle islem reddedildi: "' . $domain . '" alan adina ait abonelik'
                . ' bu modul tarafindan olusturulmamis. Paneldeki musterinin external-id degeri "'
                . ($actual !== '' ? $actual : '(bos)') . '", beklenen "' . $expected . '".'
            );
        }

        $targets = array('webspace_id' => $webspace['id'], 'customer_id' => $webspace['owner_id']);
        $this->storage['plesk_targets'] = $targets;
        return $targets;
    }

    private function orderDomain()
    {
        if (isset($this->options['domain']) && $this->options['domain'] !== '') {
            return $this->options['domain'];
        }
        if (isset($this->order['options']['domain'])) {
            return $this->order['options']['domain'];
        }
        return '';
    }

    private function panelUser()
    {
        if (!isset($this->config['user']) || $this->config['user'] === '') {
            throw new DNAHosting_Exception($this->lang['error-no-order']);
        }
        return $this->config['user'];
    }

    public function suspend()
    {
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->suspend($t['webspace_id']);
            }
            return $this->driver()->suspendAccount($this->panelUser(), 'WiseCP');
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    public function unsuspend()
    {
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->unsuspend($t['webspace_id']);
            }
            return $this->driver()->unsuspendAccount($this->panelUser());
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    public function suspend_reseller()   { return $this->suspend(); }
    public function unsuspend_reseller() { return $this->unsuspend(); }
    public function removeReseller($user = false) { return $this->removeAccount($user); }
    /** Cekirdekte cagri yeri yok; bayi hesabi satmiyoruz. Niyeti belgelemek icin duruyor. */
    public function setReseller($user, $params = array())   { return true; }
    /** Cekirdekte cagri yeri yok; bayi hesabi satmiyoruz. Niyeti belgelemek icin duruyor. */
    public function setupReseller($user = false, $params = array()) { return true; }

    public function removeAccount($user = false)
    {
        try {
            $domainKey = DNAHosting_Support::domainKey($this->orderDomain());
            $shared    = $this->otherActiveServices($domainKey);
            if ($shared) {
                throw new DNAHosting_Exception(
                    $this->lang['error-shared-domain']
                    . ' Ayni alan adini kullanan diger hizmet numaralari: ' . implode(', ', $shared)
                );
            }

            if ($this->panel() === 'plesk') {
                // Sahiplik guardi pleskTargets() icinde calisti; terminate() external-id'yi
                // yalnizca MUSTERIYI silmeden onceki son kapi olarak yeniden dogrular.
                $t = $this->pleskTargets();
                return $this->driver()->terminate($t['webspace_id'], $t['customer_id'], $this->externalId());
            }
            return $this->driver()->terminateAccount($user ? $user : $this->panelUser());
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    /**
     * Ayni sunucuda ayni alan adini kullanan diger aktif/askidaki hizmetlerin kimlikleri.
     * server_id JSON icinde hem tirnakli hem tirnaksiz kodlanabildigi icin iki desen de aranir
     * (cekirdegin kendi deseni: coremio/models/admin/products.php:376-378).
     */
    protected function otherActiveServices($domainKey)
    {
        if ($domainKey === '' || !class_exists('Models') || !isset(Models::$init->db)) {
            return array();
        }

        $serverId = (int) $this->server['id'];
        $orderId  = isset($this->order['id']) ? (int) $this->order['id'] : 0;

        $stmt = Models::$init->db->select('id,options')->from('users_products');
        $stmt->where('type', '=', 'hosting', '&&');
        $stmt->where('module', '=', $this->_name, '&&');
        if ($orderId) {
            $stmt->where('id', '!=', $orderId, '&&');
        }
        $stmt->where('(');
        $stmt->where('status', '=', 'active', '||');
        $stmt->where('status', '=', 'suspended', '');
        $stmt->where(')', '', '', '&&');
        $stmt->where('(');
        $stmt->where('options', 'LIKE', '%"server_id":"' . $serverId . '"%', '||');
        $stmt->where('options', 'LIKE', '%"server_id":' . $serverId . '%', '');
        $stmt->where(')', '', '', '');

        $rows = $stmt->build() ? $stmt->fetch_assoc() : array();
        if (!$rows) {
            return array();
        }

        $matches = array();
        foreach ($rows as $row) {
            $options = json_decode($row['options'], true);
            if (!is_array($options) || !isset($options['domain'])) {
                continue;
            }
            if (DNAHosting_Support::domainKey($options['domain']) === $domainKey) {
                $matches[] = (int) $row['id'];
            }
        }
        return $matches;
    }

    public function changePassword($oldpw, $newpw)
    {
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->changePassword($t['customer_id'], $t['webspace_id'], $newpw);
            }
            return $this->driver()->changePassword($this->panelUser(), $newpw);
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    public function change_plan($plan)
    {
        if (trim((string) $plan) === '') {
            return true;
        }
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->changePlan($t['webspace_id'], $this->driver()->resolvePlan($plan));
            }
            return $this->driver()->changePackage($this->panelUser(), $plan);
        } catch (Throwable $e) {
            return $this->failed($e);
        }
    }

    /**
     * Yeni paketi UYGULAR — ve yeni paket yalnizca urunun module_data'sinda vardir.
     *
     * Cekirdek bu cagriyi ESKI siparis secenekleriyle yapar: hosting_module_operation()
     * once $orderopt = $order["options"] der, sonra apply_updowngrade($orderopt, $product)
     * cagirir (coremio/helpers/orders.php:3061-3076). $order["options"]["creation_info"]
     * siparis olusturulurken ESKI urunden yazilmistir (orders.php:1147-1152) ve cekirdek
     * onu yeni urune gore ancak bu cagri DONDUKTEN sonra tazeler (orders.php:254-255).
     *
     * Bu yuzden creation_info'ya once bakmak her yukseltmede eski paketi yeniden uygular,
     * panel "tamam" der, cekirdek yukseltmeyi basarili sayar ve yeni fiyati faturalar —
     * musteri almadigi bir yukseltmenin parasini oder. Tasarim §5.5 zaten "urunun
     * module_data'sindan yeni paketi okur" diyor; dogru kaynak odur.
     */
    public function apply_updowngrade($orderopt = array(), $product = array())
    {
        if ($product) {
            $this->product = $product;
        }

        $plan = $this->planOf(is_array($orderopt) ? $orderopt : array(), true);

        // change_plan('') tek basina true doner — bu izole halde dogrudur, ama burada
        // "hicbir sey degistirmeden basarili" raporlanan bir yukseltme demek olur.
        if (trim((string) $plan) === '') {
            $this->error = $this->lang['error-no-plan'];
            return false;
        }

        return $this->change_plan($plan);
    }

    /** Cekirdekte cagri yeri yok; WiseCP hosting modullerinden bu metodu hic istemiyor. */
    public function modifyAccount($params = array())
    {
        return false;
    }

    /**
     * Alan adi degisikligi panelde karsiligi olmayan bir islemdir.
     *
     * update_hosting() adminin domaini degistirmesine izin verir ve yeni degeri
     * $set_options'a katar (coremio/controllers/admin/orders.php:4338).
     * - cPanel: hesap eski alan adini sunmaya devam eder, WiseCP yenisini gosterir.
     *   Kozmetik olarak yanlis ama geri donulebilir; engellemiyoruz.
     * - Plesk: abonelik domain adiyla bulunuyor. Yeni ad panelde yoksa pleskTargets()
     *   bir daha asla eslesmez ve SONLANDIRMA dahil her islem kalici olarak basarisiz
     *   olur; abonelik modul uzerinden silinemez ve bayi kotasini yemeye devam eder.
     *
     * @return bool true: devam edilebilir. false: $this->error dolduruldu.
     */
    private function allowDomainChange($oldDomain, $newDomain)
    {
        try {
            $isPlesk = $this->panel() === 'plesk';
        } catch (Throwable $e) {
            $this->failed($e);
            return false;
        }

        if (!$isPlesk) {
            return true;
        }

        $this->error = str_replace(
            array('{old}', '{new}'),
            array($oldDomain, $newDomain),
            $this->lang['error-domain-change-plesk']
        );
        return false;
    }

    public function apply_options($old_options, $new_options = array())
    {
        $oldConfig = isset($old_options['config']) ? $old_options['config'] : array();
        $newConfig = isset($new_options['config']) ? $new_options['config'] : array();

        $oldDomain = isset($old_options['domain']) ? (string) $old_options['domain'] : '';
        $newDomain = isset($new_options['domain']) ? (string) $new_options['domain'] : '';
        if ($oldDomain !== '' && $newDomain !== ''
            && DNAHosting_Support::domainKey($oldDomain) !== DNAHosting_Support::domainKey($newDomain)
            && !$this->allowDomainChange($oldDomain, $newDomain)) {
            return false;
        }

        $newUser = isset($newConfig['user']) ? $newConfig['user'] : '';
        if ($newUser === '') {
            return $new_options;
        }

        $plain  = isset($newConfig['password']) ? (string) $newConfig['password'] : '';
        $stored = isset($oldConfig['password']) ? (string) $oldConfig['password'] : '';

        if ($plain === '') {
            // Alan bos geldi — ornegin kayitli deger cozulemedigi icin form bos cizildi.
            // Kayitli sifreyi silmek yerine oldugu gibi tasiyoruz.
            $newConfig['password'] = $stored;
        } else {
            // Karsilastirma KODLANMIS taraflar uzerinden yapilir. $plain adminin yazdigi
            // duz metin, $stored ise get_order()'in cozmeden biraktigi kodlanmis degerdir
            // (coremio/controllers/admin/orders.php:583) — ikisi asla esit olamaz, yani
            // duz metni dogrudan karsilastirmak her "Kaydet"te canli panel sifresini
            // sifirlardi. Crypt::chip() sabit IV kullandigindan encode() belirlenimlidir
            // ve iki sifreli metni karsilastirmak guvenlidir (coremio/classes/Crypt.php);
            // cekirdegin kendi cPanel modulu de bunu boyle yapar (cPanel.php:581-583).
            $encoded = $this->encode_str($plain);
            if ($encoded !== $stored) {
                $this->config['user'] = $newUser;
                if (!$this->changePassword('', $plain)) {
                    return false;
                }
            }
            $newConfig['password'] = $encoded;
        }

        // Sifre burada bilerek kodlanmis (encode_str()): apply_options()'un donus degeri
        // cekirdek tarafindan araya bir kodlama adimi girmeden dogrudan kaydedilir
        // (coremio/controllers/admin/orders.php:4340-4346). Aktivasyon e-postasi ise
        // config.password ve ftp_info.password uzerinde kosulsuzca decode_str() calistirir
        // (coremio/classes/Modules.php); acik metin birakirsak o cozme adimi bozulur.
        $domain = isset($new_options['domain']) ? $new_options['domain'] : $this->orderDomain();
        $new_options['config']   = $newConfig;
        $new_options['ftp_info'] = array(
            'ip'       => $this->server['ip'],
            'host'     => 'ftp.' . DNAHosting_Support::domainKey($domain),
            'username' => $newUser,
            'password' => isset($newConfig['password']) ? $newConfig['password'] : '',
            'port'     => 21,
        );

        return $new_options;
    }

    public function usageSnapshot()
    {
        if (isset($this->storage['usage'])) {
            return $this->storage['usage'];
        }

        if ($this->panel() === 'plesk') {
            $t     = $this->pleskTargets();
            $usage = $this->driver()->usage($t['webspace_id']);
        } else {
            $usage = $this->driver()->usage($this->panelUser());
        }

        $this->storage['usage'] = $usage;
        return $usage;
    }

    private function usageBlock($usedKey, $limitKey)
    {
        try {
            $usage = $this->usageSnapshot();
        } catch (Throwable $e) {
            return $this->failed($e);
        }

        $used  = (int) $usage[$usedKey];
        $limit = (int) $usage[$limitKey];

        return array(
            'limit'        => $limit,
            'used'         => $used,
            'used-percent' => DNAHosting_Support::percent($used, $limit),
            'format-limit' => DNAHosting_Support::formatBytes($limit),
            'format-used'  => $used > 0 ? DNAHosting_Support::formatBytes($used) : '0 KB',
        );
    }

    public function getDisk()
    {
        return $this->usageBlock('disk_used', 'disk_limit');
    }

    public function getBandwidth($user = false)
    {
        return $this->usageBlock('bw_used', 'bw_limit');
    }

    /** Cekirdekte cagri yeri yok (tasarim §3.3 spekulatif listeliyor); goruntuleme yardimcisi. */
    public function getSummary()
    {
        if (!isset($this->config['user']) || $this->config['user'] === '') {
            return false;
        }

        $summary = array('domain' => $this->orderDomain());
        try {
            $panel = $this->panel();
            $summary['panel'] = $panel === 'plesk' ? $this->lang['panel-plesk'] : $this->lang['panel-cpanel'];
        } catch (Throwable $e) {
            // Bu bir goruntuleme yardimcisidir: panel tespiti basarisiz olursa
            // yanlis bir etiket uydurmak yerine 'panel' alani sessizce atlanir.
            // $this->error kasitli olarak set edilmez.
        }
        return $summary;
    }

    private static function clientIp()
    {
        if (class_exists('UserManager') && method_exists('UserManager', 'GetIP')) {
            $ip = UserManager::GetIP();
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    private function openPanel($service)
    {
        try {
            if ($this->panel() === 'plesk') {
                $url = $this->driver()->createSession($this->panelUser(), self::clientIp());
            } else {
                $url = $this->driver()->createSession($this->panelUser(), $service, self::clientIp());
            }
        } catch (Throwable $e) {
            $this->failed($e);
            echo htmlspecialchars($this->error, ENT_QUOTES, 'UTF-8');
            return false;
        }

        if (class_exists('Utility') && method_exists('Utility', 'redirect')) {
            Utility::redirect($url);
        } else {
            header('Location: ' . $url);
        }
        return true;
    }

    public function use_clientArea_SingleSignOn()
    {
        return $this->openPanel('cpaneld');
    }

    public function use_adminArea_SingleSignOn()
    {
        return $this->openPanel('cpaneld');
    }

    public function panel_links_for_client()
    {
        return array(
            'panel' => array(
                'url'   => $this->area_link . '?inc=use_method&method=SingleSignOn',
                'color' => 'blue',
                'icon'  => 'fa fa-sign-in',
                'name'  => $this->lang['login-panel'],
            ),
        );
    }

    public function panel_links_for_admin()
    {
        return array(
            'panel' => array(
                'url'  => $this->area_link . '?operation=hosting_use_method&use_method=SingleSignOn',
                'name' => $this->lang['login-panel'],
            ),
        );
    }

    public function clientArea()
    {
        try {
            $panel = $this->panel();
        } catch (Throwable $e) {
            // Goruntuleme yardimcisi: panel tespit edilemezse sablona bos birakilir,
            // hata firlatilmaz ve $this->error kasitli olarak set edilmez.
            $panel = '';
        }

        $page = $this->page ? $this->page : 'home';
        return $this->get_page('clientArea-' . $page, array(
            'LANG'     => $this->lang,
            'panel'    => $panel,
            'username' => isset($this->config['user']) ? $this->config['user'] : '',
            'domain'   => $this->orderDomain(),
            // Yalnizca sablonun ihtiyac duyabilecegi alanlar aktarilir; $this->server
            // bayi API sifresini de tasir ve musteri tarayicisina cizilen bir kapsama
            // asla girmemelidir.
            'server'   => array(
                'ip'     => isset($this->server['ip']) ? $this->server['ip'] : '',
                'port'   => isset($this->server['port']) ? $this->server['port'] : '',
                'secure' => isset($this->server['secure']) ? $this->server['secure'] : '',
            ),
        ));
    }
}
