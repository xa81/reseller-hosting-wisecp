<?php
test('usernameFor cPanel icin 8 karakteri asmaz ve harfle baslar', function () {
    $u = DNAHosting_Support::usernameFor('123ornek-sitesi.com', 'cpanel');
    assertTrue(strlen($u) <= 8, 'uzunluk 8i asmamali, gelen: ' . $u);
    assertTrue(preg_match('/^[a-z][a-z0-9]*$/', $u) === 1, 'gecersiz kullanici adi: ' . $u);
});

test('usernameFor Plesk icin 16 karaktere kadar izin verir', function () {
    $u = DNAHosting_Support::usernameFor('cokuzunbirdomainadi.com', 'plesk');
    assertTrue(strlen($u) <= 16, 'gelen: ' . $u);
    assertTrue(strlen($u) > 8, 'Plesk icin cPanel sinirina takilmamali, gelen: ' . $u);
});

test('usernameFor tireleri atar', function () {
    assertSame(false, strpos(DNAHosting_Support::usernameFor('a-b-c.com', 'cpanel'), '-'));
});

test('password her karakter sinifindan icerir', function () {
    for ($i = 0; $i < 50; $i++) {
        $p = DNAHosting_Support::password(14);
        assertSame(14, strlen($p));
        assertTrue(preg_match('/[a-z]/', $p) === 1, 'kucuk harf yok: ' . $p);
        assertTrue(preg_match('/[A-Z]/', $p) === 1, 'buyuk harf yok: ' . $p);
        assertTrue(preg_match('/[0-9]/', $p) === 1, 'rakam yok: ' . $p);
        assertTrue(preg_match('/[!.#%*+=?@_-]/', $p) === 1, 'sembol yok: ' . $p);
    }
});

test('domainKey normalize eder', function () {
    assertSame('ornek.com', DNAHosting_Support::domainKey('  ORNEK.com.  '));
    assertSame('ornek.com', DNAHosting_Support::domainKey('Ornek.Com'));
});

test('formatBytes okunabilir cikti verir', function () {
    assertSame('∞', DNAHosting_Support::formatBytes(0));
    assertSame('1 KB', DNAHosting_Support::formatBytes(1024));
    assertSame('1 MB', DNAHosting_Support::formatBytes(1048576));
    assertSame('1.5 GB', DNAHosting_Support::formatBytes(1610612736));
});

test('percent sinirli araliga kilitler', function () {
    assertSame(50, DNAHosting_Support::percent(512, 1024));
    assertSame(0, DNAHosting_Support::percent(512, 0));
    assertSame(100, DNAHosting_Support::percent(4096, 1024));
    assertSame(0, DNAHosting_Support::percent(0, 1024));
});
