<?php
require_once __DIR__ . '/../stubs/wisecp.php';
require_once dirname(__DIR__) . '/../coremio/modules/Servers/DNAHosting/DNAHosting.php';

function dna_module($port, $transportSetup)
{
    $server = array(
        'id' => 3, 'name' => 'test', 'ip' => '1.2.3.4', 'port' => $port, 'secure' => 1,
        'username' => 'bayi', 'password' => 'GIZLI123456',
    );
    $module = new DNAHosting_Module($server);
    $t      = new DNAHosting_FakeTransport();
    call_user_func($transportSetup, $t);
    $module->useTransport($t);
    return array($module, $t);
}

test('Modul force_setup false olmali', function () {
    $m = new DNAHosting_Module(false);
    assertSame(false, $m->force_setup, 'true olursa cekirdek Pleske uygun olmayan sifre uretir');
});

test('Modul cPanel sunucusunu tespit eder', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    assertTrue($m->testConnect());
    assertSame('cpanel', $m->panel());
});

test('Modul Plesk sunucusunu tespit eder', function () {
    list($m, $t) = dna_module(8443, function ($t) {
        $t->push(200, '<?xml version="1.0"?><packet><server><get><result>'
            . '<status>ok</status></result></get></server></packet>');
    });
    assertTrue($m->testConnect());
    assertSame('plesk', $m->panel());
});

test('Modul baglanamayinca false doner ve error doldurur', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    assertSame(false, $m->testConnect());
    assertContains('Access denied', $m->error);
});

test('Modul getPlans cPanel paketlerini isim listesine cevirir', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":['
            . '{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    });
    $plans = $m->getPlans();
    assertSame(1, count($plans));
    assertSame('bayi_pro', $plans[0]['name']);
});

test('Modul getPlans hata durumunda false doner', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    assertSame(false, $m->getPlans());
    assertContains('Access denied', $m->error);
});

class DNAHosting_ModuleRouting extends DNAHosting_Module
{
    public $admin = false;

    public function use_clientArea_SingleSignOn() { return 'client'; }
    public function use_adminArea_SingleSignOn() { return 'admin'; }
    public function use_clientArea_change_password() { return 'sifre-degistirildi'; }

    protected function isAdminArea()
    {
        return $this->admin;
    }
}

test('Modul use_method musteri onekini kullanir', function () {
    // __CLASS__ derleme zamani sabiti oldugundan alt sinifta da _name 'DNAHosting_Module' kalir.
    $m = new DNAHosting_ModuleRouting(false);
    assertSame('client', $m->use_method('SingleSignOn'));
});

test('Modul use_method bilinmeyen metodu yok sayar', function () {
    $m = new DNAHosting_ModuleRouting(false);
    assertSame(null, $m->use_method('YokBoyleBirSey'));
    assertSame(null, $m->use_method(''));
});

test('Modul use_method admin onekini kullanir', function () {
    // ADMINISTRATOR sabitini tanimlamadan admin dalini sinamak icin isAdminArea() ezildi.
    $m = new DNAHosting_ModuleRouting(false);
    $m->admin = true;
    assertSame('admin', $m->use_method('SingleSignOn'));
});

test('Modul use_method tire donusumunu uygular', function () {
    $m = new DNAHosting_ModuleRouting(false);
    assertSame('sifre-degistirildi', $m->use_method('change-password'));
});

class DNAHosting_ModuleWithNeighbours extends DNAHosting_Module
{
    public $neighbours = array();
    protected function otherActiveServices($domainKey)
    {
        return $this->neighbours;
    }
}

function dna_module_n($port, $transportSetup)
{
    $server = array(
        'id' => 3, 'name' => 'test', 'ip' => '1.2.3.4', 'port' => $port, 'secure' => 1,
        'username' => 'bayi', 'password' => 'GIZLI123456',
    );
    $module = new DNAHosting_ModuleWithNeighbours($server);
    $t      = new DNAHosting_FakeTransport();
    call_user_func($transportSetup, $t);
    $module->useTransport($t);
    $module->order = array('id' => 501, 'owner_id' => 7);
    $module->user  = array('email' => 'musteri@ornek.com', 'full_name' => 'Ornek Musteri');
    return array($module, $t);
}

test('Modul createAccount cPanelde ftp_info ile doner', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $r = $m->createAccount('ornek.com', array('creation_info' => array('plan' => 'bayi_pro')));
    assertTrue(is_array($r), 'dizi bekleniyordu, error: ' . $m->error);
    assertTrue(strlen($r['username']) > 0);
    assertTrue(strlen($r['password']) >= 12);
    assertSame('ftp.ornek.com', $r['ftp_info']['host']);
    assertSame(21, $r['ftp_info']['port']);
    assertSame($r['username'], $r['ftp_info']['username']);
});

test('Modul createAccount paket secilmemisse anlamli hata verir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    assertSame(false, $m->createAccount('ornek.com', array()));
    assertContains('paket', strtolower($m->error));
});

test('Modul suspend ve unsuspend cPanelde calisir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    assertTrue($m->suspend());
    assertContains('suspendacct', $t->lastCall()['url']);
});

test('Modul suspend_reseller suspende alias', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    assertTrue($m->suspend_reseller());
    assertContains('suspendacct', $t->lastCall()['url']);
});

test('Modul removeAccount ayni domainli komsu varsa reddeder', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    $m->config['user']    = 'ornek1';
    $m->options['domain'] = 'ornek.com';
    $m->neighbours        = array(777);
    assertSame(false, $m->removeAccount());
    assertContains('777', $m->error);
    // Guard panel() cagrilmadan once firlar, bu yuzden hic HTTP istegi cikmaz.
    assertSame(0, count($t->calls), 'hicbir istek gonderilmemeli');
});

test('Modul removeAccount komsu yoksa siler', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user']    = 'ornek1';
    $m->options['domain'] = 'ornek.com';
    assertTrue($m->removeAccount());
    assertContains('removeacct', $t->lastCall()['url']);
});

test('Modul change_plan bos plani sessizce gecer', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    $m->config['user'] = 'ornek1';
    assertTrue($m->change_plan(''));
    // Bos plan panel() cagrilmadan true doner.
    assertSame(0, count($t->calls));
});

test('Modul apply_updowngrade eski creation_info yerine yeni urunun planini uygular', function () {
    // Cekirdek bu cagriyi ESKI siparis secenekleriyle yapar (orders.php:3070-3076) ve
    // creation_info'yu ancak modul donduktun SONRA tazeler (orders.php:254-255).
    // creation_info'ya once bakilirsa her yukseltme eski paketi yeniden uygular.
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":['
            . '{"name":"eski","QUOTA":"1","BWLIMIT":"1"},'
            . '{"name":"yeni","QUOTA":"2","BWLIMIT":"2"}]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    $ok = $m->apply_updowngrade(
        array('creation_info' => array('plan' => 'eski')),
        array('module_data' => array('create_account' => array('plan' => 'yeni')))
    );
    assertTrue($ok, 'yukseltme basarili olmali, error: ' . $m->error);
    assertContains('pkg=yeni', $t->lastCall()['body']);
    assertSame(false, strpos($t->lastCall()['body'], 'pkg=eski'), 'eski paket tele cikmamali');
});

test('Modul apply_updowngrade duz module_data.plan dalini da okur', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":['
            . '{"name":"bayi_kurumsal","QUOTA":"1","BWLIMIT":"1"}]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    $ok = $m->apply_updowngrade(
        array('creation_info' => array('plan' => 'eski')),
        array('module_data' => array('plan' => 'kurumsal'))
    );
    assertTrue($ok, 'error: ' . $m->error);
    assertContains('pkg=bayi_kurumsal', $t->lastCall()['body']);
});

test('Modul apply_updowngrade module_data JSON dizesi olarak gelse de cozer', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"yeni","QUOTA":"1","BWLIMIT":"1"}]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    $ok = $m->apply_updowngrade(
        array('creation_info' => array('plan' => 'eski')),
        array('module_data' => '{"create_account":{"plan":"yeni"}}')
    );
    assertTrue($ok, 'error: ' . $m->error);
    assertContains('pkg=yeni', $t->lastCall()['body']);
});

test('Modul apply_updowngrade hicbir yerde plan yoksa hata verir, sessizce basarili donmez', function () {
    // change_plan('') tek basina true doner; Critical 1 duzeltmesinden sonra bu,
    // "hicbir sey degistirmeden basarili" raporlayan bir yukseltme demek olurdu.
    list($m, $t) = dna_module_n(2087, function ($t) { });
    $m->config['user'] = 'ornek1';
    assertSame(false, $m->apply_updowngrade(
        array('creation_info' => array()),
        array('module_data' => array())
    ));
    assertContains('plan', strtolower($m->error));
    assertSame(0, count($t->calls), 'plan cozulemediyse panele hic gidilmemeli');
});

test('Modul apply_updowngrade urun yoksa creation_info planina duser', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"eski","QUOTA":"1","BWLIMIT":"1"}]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    $ok = $m->apply_updowngrade(array('creation_info' => array('plan' => 'eski')), array());
    assertTrue($ok, 'error: ' . $m->error);
    assertContains('pkg=eski', $t->lastCall()['body']);
});

test('Modul externalId siparis kimliginden turer', function () {
    list($m, $t) = dna_module_n(2087, function ($t) { });
    assertSame('wisecp-501', $m->externalId());
});

test('Modul UsernameGenerator panele uygun ad uretir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    $u = $m->UsernameGenerator('cokuzunbirdomain.com');
    assertTrue(strlen($u) <= 8, 'cPanel icin 8i asmamali: ' . $u);
});

test('Modul UsernameGenerator panel tespiti basarisiz olunca cPanel kurallarina duser', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    $u = $m->UsernameGenerator('ornek.com');
    assertTrue(strlen($u) > 0 && strlen($u) <= 8, 'cPanel kurallarina uygun olmali: ' . $u);
    assertTrue((bool) preg_match('/^[a-z][a-z0-9]*$/', $u), 'desene uymuyor: ' . $u);
    assertSame(null, $m->error, 'zarif geri dusus hata olarak isaretlenmemeli');
});

test('Modul apply_options kodlanmis sifreyi hem config hem ftp_info alaninda doner', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $old = array('config' => array('user' => 'ornek1', 'password' => 'ENC:eskisifre'));
    $new = array('config' => array('user' => 'ornek1', 'password' => 'yenisifre123'), 'domain' => 'ornek.com');
    $r = $m->apply_options($old, $new);
    assertTrue(is_array($r), 'dizi bekleniyordu, error: ' . $m->error);
    assertSame('ENC:yenisifre123', $r['config']['password']);
    assertSame('ENC:yenisifre123', $r['ftp_info']['password']);
});

test('Modul getDisk WiseCP sozlesmesine uyar', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
            . '"diskused":"512M","disklimit":"1024M","totalbytes":"1048576","limit":"unlimited"}]}}');
    });
    $m->config['user'] = 'ornek1';
    $d = $m->getDisk();
    assertSame(536870912, $d['used']);
    assertSame(1073741824, $d['limit']);
    assertSame(50, $d['used-percent']);
    assertSame('1 GB', $d['format-limit']);
    assertSame('512 MB', $d['format-used']);
});

test('Modul getBandwidth sinirsizi sonsuz gosterir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
            . '"diskused":"512M","disklimit":"1024M","totalbytes":"1048576","limit":"unlimited"}]}}');
    });
    $m->config['user'] = 'ornek1';
    $b = $m->getBandwidth();
    assertSame(0, $b['limit']);
    assertSame('∞', $b['format-limit']);
    assertSame(0, $b['used-percent']);
});

test('Modul kullanim verisini tek istekte bir kez ceker', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
            . '"diskused":"512M","disklimit":"1024M","totalbytes":"1","limit":"1"}]}}');
    });
    $m->config['user'] = 'ornek1';
    $m->getDisk();
    $m->getBandwidth();
    assertSame(2, count($t->calls), 'tespit + tek kullanim cagrisi bekleniyor');
});

test('Modul getDisk hatada false doner', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    $m->config['user'] = 'ornek1';
    assertSame(false, $m->getDisk());
    assertContains('Access denied', $m->error);
});

test('Modul panel_links_for_client giris butonu verir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) { });
    $m->area_link = '/hizmet/501';
    $links = $m->panel_links_for_client();
    assertTrue(isset($links['panel']));
    assertContains('use_method', $links['panel']['url']);
    assertContains('SingleSignOn', $links['panel']['url']);
});

test('Modul root SSO metodu tanimlamaz', function () {
    assertSame(false, method_exists('DNAHosting_Module', 'use_adminArea_root_SingleSignOn'),
        'root erisimimiz yok, buton gosterilmemeli');
});

test('Modul getSummary ilk cagride dogru paneli tespit eder', function () {
    // getSummary() ilk cagrida panel() metodunu kullanmalidir; $this->panel ham ozelligi
    // henuz doldurulmamis olur — bu test tam da o hatayi yakalamak icin yazildi.
    list($m, $t) = dna_module_n(8443, function ($t) {
        $t->push(200, '<?xml version="1.0"?><packet><server><get><result>'
            . '<status>ok</status></result></get></server></packet>');
    });
    $m->config['user'] = 'ornek1';
    $s = $m->getSummary();
    assertTrue(is_array($s), 'dizi bekleniyordu');
    assertSame($m->lang['panel-plesk'], $s['panel']);
});

test('Modul getSummary panel tespiti basarisiz olunca panel alanini atlar', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    $m->config['user'] = 'ornek1';
    $s = $m->getSummary();
    assertTrue(is_array($s), 'dizi bekleniyordu');
    assertSame(false, isset($s['panel']), 'panel tespit edilemedigi icin alan atlanmali');
    assertSame(null, $m->error, 'goruntuleme yardimcisi servis hatasi uretmemeli');
});

test('Modul use_clientArea_SingleSignOn hata durumunda false doner ve mesaji basar', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    $m->config['user'] = 'ornek1';
    ob_start();
    $result = $m->use_clientArea_SingleSignOn();
    $output = ob_get_clean();
    assertSame(false, $result);
    assertContains('Access denied', $m->error);
    assertContains('Access denied', $output);
});
