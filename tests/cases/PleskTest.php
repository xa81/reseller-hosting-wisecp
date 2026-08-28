<?php
function dna_plesk()
{
    $server = array(
        'ip' => '5.6.7.8', 'port' => 8443, 'secure' => 1,
        'username' => 'bayi', 'password' => 'ANAHTAR-1234-5678',
    );
    $http = new DNAHosting_Http('https://5.6.7.8:8443');
    $t    = new DNAHosting_FakeTransport();
    $http->setTransport($t);
    return array(new DNAHosting_Plesk($server, $http), $t);
}

function dna_packet($inner)
{
    return '<?xml version="1.0" encoding="UTF-8"?><packet>' . $inner . '</packet>';
}

test('Plesk dogru uc noktaya POST atar', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    $p->request('<server><get><gen_info/></get></server>', 'test');
    $call = $t->lastCall();
    assertSame('POST', $call['method']);
    assertContains('/enterprise/control/agent.php', $call['url']);
    assertContains('<packet>', $call['body']);
});

test('Plesk once KEY basligiyla dener', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    $p->request('<server><get><gen_info/></get></server>', 'test');
    assertTrue(in_array('KEY: ANAHTAR-1234-5678', $t->lastCall()['headers']));
    assertSame('key', $p->authMode());
});

test('Plesk kimlik hatasinda basic autha duser', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<system><status>error</status><errcode>1001</errcode>'
        . '<errtext>Authentication failed</errtext></system>'));
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    $p->request('<server><get><gen_info/></get></server>', 'test');
    $headers = $t->lastCall()['headers'];
    assertTrue(in_array('HTTP_AUTH_LOGIN: bayi', $headers));
    assertTrue(in_array('HTTP_AUTH_PASSWD: ANAHTAR-1234-5678', $headers));
    assertSame('basic', $p->authMode());
    assertSame(2, count($t->calls));
});

test('Plesk basic auth da basarisizsa anlamli hata verir', function () {
    list($p, $t) = dna_plesk();
    $err = dna_packet('<system><status>error</status><errcode>1001</errcode>'
        . '<errtext>Authentication failed</errtext></system>');
    $t->push(200, $err);
    $t->push(200, $err);
    assertThrows(function () use ($p) {
        $p->request('<server><get><gen_info/></get></server>', 'test');
    }, 'Authentication failed');
    assertSame(2, count($t->calls));
});

test('Plesk 11003 hatasini IP aciklamasina cevirir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<system><status>error</status><errcode>11003</errcode>'
        . '<errtext>The key is not valid for this IP</errtext></system>'));
    $e = assertThrows(function () use ($p) {
        $p->request('<server><get><gen_info/></get></server>', 'test');
    }, '11003');
    assertContains('IP', $e->getMessage());
});

test('Plesk 1014 hatasini oldugu gibi tasir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><add><result><status>error</status><errcode>1014</errcode>'
        . '<errtext>Element ip_address should be specified in gen_setup</errtext></result></add></webspace>'));
    $packet = $p->request('<webspace><add/></webspace>', 'webspace.add');
    $e = assertThrows(function () use ($packet) {
        DNAHosting_Plesk::resultOf($packet, 'webspace/add');
    }, 'ip_address');
    assertContains('1014', $e->getMessage());
});

test('Plesk bozuk XMLi anlamli hataya cevirir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, '<html><body>Plesk yukleniyor</body></html>');
    assertThrows(function () use ($p) {
        $p->request('<server><get/></server>', 'test');
    }, 'XML');
});

test('Plesk resultOf basarili sonucu dondurur', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><id>42</id></result></get></webspace>'));
    $packet = $p->request('<webspace><get/></webspace>', 'webspace.get');
    $result = DNAHosting_Plesk::resultOf($packet, 'webspace/get');
    assertSame('42', (string) $result->id);
});

test('Plesk testConnection true doner', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    assertTrue($p->testConnection());
});

test('Plesk setAuthMode basic ile KEY probasini atlar', function () {
    list($p, $t) = dna_plesk();
    $p->setAuthMode('basic');
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    $p->request('<server><get><gen_info/></get></server>', 'test');
    $headers = $t->lastCall()['headers'];
    assertTrue(in_array('HTTP_AUTH_LOGIN: bayi', $headers));
    assertTrue(in_array('HTTP_AUTH_PASSWD: ANAHTAR-1234-5678', $headers));
    assertSame(1, count($t->calls));
});

test('Plesk dovusken dugmede mode kilitlenir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<system><status>error</status><errcode>1001</errcode>'
        . '<errtext>Authentication failed</errtext></system>'));
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    $p->request('<server><get><gen_info/></get></server>', 'test');
    assertSame('basic', $p->authMode());
    $t->push(200, dna_packet('<server><get><result><status>ok</status></result></get></server>'));
    $p->request('<server><get><gen_info/></get></server>', 'test');
    $headers = $t->lastCall()['headers'];
    assertTrue(in_array('HTTP_AUTH_LOGIN: bayi', $headers));
    assertSame(3, count($t->calls));
});

test('Plesk dusus hatasini ayirt eder', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<system><status>error</status><errcode>1015</errcode>'
        . '<errtext>Permission denied or object not found</errtext></system>'));
    assertThrows(function () use ($p) {
        $p->request('<server><get><gen_info/></get></server>', 'test');
    }, '1015');
    assertSame(1, count($t->calls));
});

test('Plesk listPlans ad ve guid dondurur', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get>'
        . '<result><status>ok</status><id>1</id><name>Baslangic</name><guid>g-1</guid></result>'
        . '<result><status>ok</status><id>2</id><name>Pro</name><guid>g-2</guid></result>'
        . '</get></service-plan>'));
    $plans = $p->listPlans();
    assertSame(2, count($plans));
    assertSame('Baslangic', $plans[0]['name']);
    assertSame('g-2', $plans[1]['guid']);
});

test('Plesk resolvePlan buyuk kucuk harf gozetmez', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get>'
        . '<result><status>ok</status><name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    assertSame('g-2', $p->resolvePlan('pro')['guid']);
});

test('Plesk resolvePlan bulunamayinca mevcutlari listeler', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get>'
        . '<result><status>ok</status><name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $e = assertThrows(function () use ($p) { $p->resolvePlan('yok'); }, 'yok');
    assertContains('Pro', $e->getMessage());
});

test('Plesk firstSharedIp yalnizca shared adresi secer', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.1</ip_address><type>exclusive</type></ip>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    assertSame('10.0.0.2', $p->firstSharedIp());
});

test('Plesk firstSharedIp hicbiri paylasimli degilse sunucu IPsine duser', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.1</ip_address><type>exclusive</type></ip>'
        . '</addresses></result></get></ip>'));
    assertSame('5.6.7.8', $p->firstSharedIp());
});

test('Plesk findCustomer external-id ile filtreler', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>musteri77</login></gen_info></data></result></get></customer>'));
    $c = $p->findCustomer('wisecp-501');
    assertSame(77, $c['id']);
    assertSame('musteri77', $c['login']);
    assertContains('<external-id>wisecp-501</external-id>', $t->lastCall()['body']);
});

test('Plesk findCustomer yoksa null doner', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></customer>'));
    assertSame(null, $p->findCustomer('wisecp-yok'));
});

test('Plesk findWebspace domain ile bulur', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><id>9</id>'
        . '<data><gen_info><name>ornek.com</name><owner-id>77</owner-id></gen_info></data>'
        . '</result></get></webspace>'));
    $w = $p->findWebspace('ornek.com');
    assertSame(9, $w['id']);
    assertSame('ornek.com', $w['name']);
    assertSame(77, $w['owner_id']);
});

test('Plesk customerExternalId kayitli degilse bos dize doner', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>musteri77</login></gen_info></data></result></get></customer>'));
    assertSame('', $p->customerExternalId(77));
});
