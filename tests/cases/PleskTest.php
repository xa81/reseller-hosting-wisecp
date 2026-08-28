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
