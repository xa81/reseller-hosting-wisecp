<?php
function dna_http()
{
    $h = new DNAHosting_Http('https://1.2.3.4:2087');
    $t = new DNAHosting_FakeTransport();
    $h->setTransport($t);
    return array($h, $t);
}

test('Http basarili yaniti oldugu gibi dondurur', function () {
    list($h, $t) = dna_http();
    $t->push(200, '{"ok":1}');
    $r = $h->send('GET', '/json-api/listaccts', array('Authorization: WHM u:t'), null, 'listaccts');
    assertSame(200, $r['status']);
    assertSame('{"ok":1}', $r['body']);
    assertSame('https://1.2.3.4:2087/json-api/listaccts', $t->lastCall()['url']);
});

test('Http 403te govdeyi hata mesajina koyar', function () {
    list($h, $t) = dna_http();
    $t->push(403, '<html><body><h1>Access denied for reseller</h1></body></html>');
    assertThrows(function () use ($h) {
        $h->send('GET', '/json-api/listaccts', array(), null, 'listaccts');
    }, 'Access denied for reseller', 'govde ozeti hata mesajinda olmali');
});

test('Http 403 mesajinda HTTP kodunu da tasir', function () {
    list($h, $t) = dna_http();
    $t->push(403, 'yasak');
    $e = assertThrows(function () use ($h) {
        $h->send('GET', '/x', array(), null, 'test');
    }, '403');
    assertContains('yasak', $e->getMessage());
});

test('Http ag hatasini firlatir', function () {
    list($h, $t) = dna_http();
    $t->pushError('Could not resolve host');
    assertThrows(function () use ($h) {
        $h->send('GET', '/x', array(), null, 'test');
    }, 'Could not resolve host');
});

test('Http gizli dizeleri maskeler', function () {
    list($h, $t) = dna_http();
    $h->addSecret('SUPERGIZLITOKEN');
    assertSame('Authorization: WHM u:***', $h->mask('Authorization: WHM u:SUPERGIZLITOKEN'));
});

test('Http logger cagrilir ve maskelenmis veri gecer', function () {
    list($h, $t) = dna_http();
    $h->addSecret('GIZLI');
    $seen = array();
    $h->setLogger(function ($action, $request, $response) use (&$seen) {
        $seen[] = array($action, $request, $response);
    });
    $t->push(200, 'tamam');
    $h->send('POST', '/x', array('Authorization: WHM u:GIZLI'), 'p=GIZLI', 'olustur');
    assertSame(1, count($seen));
    assertSame('olustur', $seen[0][0]);
    assertSame(false, strpos($seen[0][1], 'GIZLI'));
    assertContains('***', $seen[0][1]);
});

test('Http hatada da loglar', function () {
    list($h, $t) = dna_http();
    $seen = array();
    $h->setLogger(function ($a, $b, $c) use (&$seen) { $seen[] = $a; });
    $t->push(500, 'patladi');
    try {
        $h->send('GET', '/x', array(), null, 'test');
    } catch (Exception $e) {
    }
    assertSame(1, count($seen), 'hata yolunda da log yazilmali');
});

test('Http zaman asimi tasiyiciya gecer', function () {
    list($h, $t) = dna_http();
    $h->setTimeout(400);
    $t->push(200, 'ok');
    $h->send('POST', '/x', array(), 'a=1', 'test');
    assertSame(400, $t->lastCall()['timeout']);
});

test('Http HTTP hatasi mesajinda da gizli dizeleri maskeler', function () {
    // Loga giden her sey mask()ten geciyordu ama istisnaya konan sey gecmiyordu.
    // O mesaj $this->error e, admin arayuzune ve openPanel() uzerinden musterinin
    // tarayicisina kadar gidiyor.
    list($h, $t) = dna_http();
    $h->addSecret('SUPERGIZLITOKEN');
    $t->push(403, 'Access denied for SUPERGIZLITOKEN');
    $e = assertThrows(function () use ($h) {
        $h->send('GET', '/x', array(), null, 'test');
    }, '***');
    assertSame(false, strpos($e->getMessage(), 'SUPERGIZLITOKEN'));
});

test('Http tasima hatasi mesajinda da gizli dizeleri maskeler', function () {
    list($h, $t) = dna_http();
    $h->addSecret('SUPERGIZLITOKEN');
    $t->pushError('Could not connect with SUPERGIZLITOKEN');
    $e = assertThrows(function () use ($h) {
        $h->send('GET', '/x', array(), null, 'test');
    }, '***');
    assertSame(false, strpos($e->getMessage(), 'SUPERGIZLITOKEN'));
});

test('summarise cok baytli karakteri ortadan kesmez', function () {
    // Bayt bazli kirpma bozuk UTF-8 uretir ve o metin sonra sayfaya ciziliyor.
    $out = DNAHosting_Http::summarise(str_repeat('ö', 20), 11);
    assertContains('...', $out);
    assertSame(true, mb_check_encoding($out, 'UTF-8'), 'gecerli UTF-8 olmali');
});

test('summarise HTMLi temizler ve kirpar', function () {
    $out = DNAHosting_Http::summarise('<html>  <b>Bir</b>   <i>iki</i>  </html>', 300);
    assertSame('Bir iki', $out);
    $long = DNAHosting_Http::summarise(str_repeat('x', 500), 10);
    assertSame(13, strlen($long), 'kirpilan metin ... ile bitmeli');
    assertContains('...', $long);
});

test('summarise gecersiz UTF-8 bayt dizisini degradeli sekilde isler', function () {
    $invalidUtf8 = "Bir\xC3\x28iki";
    $result = DNAHosting_Http::summarise($invalidUtf8, 300);
    assertTrue(is_string($result), 'sonuc string olmali');
    assertTrue(strlen($result) > 0, 'sonuc bos olmamali');
});
