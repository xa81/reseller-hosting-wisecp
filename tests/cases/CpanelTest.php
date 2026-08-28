<?php
function dna_cpanel($secure = 1)
{
    $server = array(
        'ip' => '1.2.3.4', 'port' => 2087, 'secure' => $secure,
        'username' => 'bayi', 'password' => 'TOKEN123456',
    );
    $http = new DNAHosting_Http(($secure ? 'https' : 'http') . '://1.2.3.4:2087');
    $t    = new DNAHosting_FakeTransport();
    $http->setTransport($t);
    return array(new DNAHosting_Cpanel($server, $http), $t);
}

test('cPanel WHM token basligi gonderir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    $c->call('listaccts');
    assertTrue(in_array('Authorization: WHM bayi:TOKEN123456', $t->lastCall()['headers']));
});

test('cPanel okuma cagrisi GET, yazma cagrisi POST', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{}}');
    $c->call('listaccts');
    assertSame('GET', $t->lastCall()['method']);
    assertSame(30, $t->lastCall()['timeout']);
    $t->push(200, '{"metadata":{"result":1},"data":{}}');
    $c->call('createacct', array('username' => 'x'));
    assertSame('POST', $t->lastCall()['method']);
    assertSame(400, $t->lastCall()['timeout']);
    assertContains('api.version=1', $t->lastCall()['body']);
});

test('cPanel api.version=1 ekler', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{}}');
    $c->call('listaccts');
    assertContains('api.version=1', $t->lastCall()['url']);
});

test('cPanel metadata.result=0 hatayi firlatir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":0,"reason":"Paket bulunamadi"}}');
    assertThrows(function () use ($c) { $c->call('createacct'); }, 'Paket bulunamadi');
});

test('cPanel cpanelresult zarfini WHM erisimi yok diye yorumlar', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"cpanelresult":{"apiversion":"2","error":"Access denied"}}');
    $e = assertThrows(function () use ($c) { $c->call('listaccts'); }, 'WHM');
    assertContains('Access denied', $e->getMessage());
});

test('cPanel bozuk JSONu anlamli hataya cevirir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '<html>Login</html>');
    assertThrows(function () use ($c) { $c->call('listaccts'); }, 'JSON');
});

test('cPanel testConnection listaccts kullanir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    assertTrue($c->testConnection());
    assertContains('/json-api/listaccts', $t->lastCall()['url']);
});

test('cPanel listPackages paketleri normalize eder', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":['
        . '{"name":"bayi_baslangic","QUOTA":"1000","BWLIMIT":"10000"},'
        . '{"name":"bayi_pro","QUOTA":"unlimited","BWLIMIT":"unlimited"}]}}');
    $p = $c->listPackages();
    assertSame(2, count($p));
    assertSame('bayi_baslangic', $p[0]['name']);
    assertSame('1000', $p[0]['quota']);
    assertSame('unlimited', $p[1]['bwlimit']);
});

test('cPanel dePrefix bayi on ekini atar', function () {
    list($c, $t) = dna_cpanel();
    assertSame('baslangic', $c->dePrefix('bayi_baslangic'));
    assertSame('baslangic', $c->dePrefix('baslangic'));
    assertSame('a_b', $c->dePrefix('bayi_a_b'));
});

test('cPanel resolvePackage on eksiz adi da bulur', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    assertSame('bayi_pro', $c->resolvePackage('pro'));
});

test('cPanel resolvePackage bulunamayinca mevcutlari listeler', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    $e = assertThrows(function () use ($c) { $c->resolvePackage('yok'); }, 'yok');
    assertContains('bayi_pro', $e->getMessage(), 'hata mesaji mevcut paketleri gostermeli');
});
