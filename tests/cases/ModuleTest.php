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
    public function use_clientArea_SingleSignOn() { return 'client'; }
    public function use_adminArea_SingleSignOn() { return 'admin'; }
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
