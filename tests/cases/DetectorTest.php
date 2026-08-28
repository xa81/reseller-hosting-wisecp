<?php
class DNAHosting_FakeDriver
{
    private $ok;
    private $why;
    public $authMode = 'key';
    public function __construct($ok, $why = '') { $this->ok = $ok; $this->why = $why; }
    public function testConnection()
    {
        if ($this->ok) { return true; }
        throw new DNAHosting_Exception($this->why);
    }
    public function authMode() { return $this->authMode; }
}

function dna_detector($port, array $drivers)
{
    $server = array(
        'ip' => '1.2.3.4', 'port' => $port, 'secure' => 1,
        'username' => 'bayi', 'password' => 'GIZLI123',
    );
    $seen = new ArrayObject();
    $factory = function ($panel) use ($drivers, $seen) {
        $seen[] = $panel;
        return $drivers[$panel];
    };
    return array(new DNAHosting_Detector($server, $factory), $seen);
}

test('Detector 8443te once Pleski dener', function () {
    assertSame(array('plesk', 'cpanel'), DNAHosting_Detector::order(8443));
    assertSame(array('plesk', 'cpanel'), DNAHosting_Detector::order(8880));
    assertSame(array('cpanel', 'plesk'), DNAHosting_Detector::order(2087));
    assertSame(array('cpanel', 'plesk'), DNAHosting_Detector::order(0));
});

test('Detector ilk yanit vereni secer', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(true),
        'plesk'  => new DNAHosting_FakeDriver(true),
    ));
    assertSame('cpanel', $d->detect()['panel']);
    assertSame(1, count($seen), 'ilk deneme tuttuysa ikincisi denenmemeli');
});

test('Detector ilk basarisiz olunca ikinciye gecer', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(false, 'HTTP 404'),
        'plesk'  => new DNAHosting_FakeDriver(true),
    ));
    $r = $d->detect();
    assertSame('plesk', $r['panel']);
    assertSame('key', $r['auth']);
    assertSame(2, count($seen));
});

test('Detector ikisi de basarisizsa iki hatayi da anlatir', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(false, 'HTTP 403: Access denied'),
        'plesk'  => new DNAHosting_FakeDriver(false, 'Plesk (1001): Authentication failed'),
    ));
    $e = assertThrows(function () use ($d) { $d->detect(); }, 'Access denied');
    assertContains('Authentication failed', $e->getMessage());
});

test('Detector onbellekten okur ve hic probe yapmaz', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(false, 'olmamali'),
        'plesk'  => new DNAHosting_FakeDriver(false, 'olmamali'),
    ));
    $d->setCache(
        function ($key) { return array('panel' => 'plesk', 'auth' => 'basic'); },
        function ($key, $value) { }
    );
    $r = $d->detect();
    assertSame('plesk', $r['panel']);
    assertSame('basic', $r['auth']);
    assertSame(0, count($seen));
});

test('Detector basarili tespiti onbellege yazar', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(true),
        'plesk'  => new DNAHosting_FakeDriver(true),
    ));
    $written = array();
    $d->setCache(
        function ($key) { return null; },
        function ($key, $value) use (&$written) { $written[$key] = $value; }
    );
    $d->detect();
    assertSame(1, count($written));
    assertSame('cpanel', current($written)['panel']);
});

test('Detector onbellek yazamasa bile calisir', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(true),
        'plesk'  => new DNAHosting_FakeDriver(true),
    ));
    $d->setCache(
        function ($key) { return null; },
        function ($key, $value) { throw new TypeError('lisans alan adi tutmuyor'); }
    );
    assertSame('cpanel', $d->detect()['panel'], 'onbellek hatasi tespiti bozmamali');
});

test('Detector onbellekten okumasa bile calisir', function () {
    list($d, $seen) = dna_detector(2087, array(
        'cpanel' => new DNAHosting_FakeDriver(true),
        'plesk'  => new DNAHosting_FakeDriver(true),
    ));
    $d->setCache(
        function ($key) { throw new TypeError('lisans alan adi tutmuyor'); },
        function ($key, $value) { }
    );
    assertSame('cpanel', $d->detect()['panel'], 'onbellek okuma hatasi tespiti bozmamali');
});

test('Detector anahtari kimlik bilgisi degisince degisir', function () {
    $a = array('ip' => '1.2.3.4', 'port' => 2087, 'secure' => 1, 'username' => 'u', 'password' => 'p1');
    $b = $a; $b['password'] = 'p2';
    $c = $a; $c['ip'] = '9.9.9.9';
    assertTrue(DNAHosting_Detector::cacheKey($a) !== DNAHosting_Detector::cacheKey($b));
    assertTrue(DNAHosting_Detector::cacheKey($a) !== DNAHosting_Detector::cacheKey($c));
    assertSame(DNAHosting_Detector::cacheKey($a), DNAHosting_Detector::cacheKey($a));
    assertSame(false, strpos(DNAHosting_Detector::cacheKey($a), 'p1'), 'anahtar sifreyi acikca tasimamali');
});

/** testConnection Exception turevi olmayan bir hata firlatir. */
class DNAHosting_ProbeExploder
{
    public function testConnection() { throw new TypeError('probe patladi'); }
}

test('Detector probe sirasindaki Throwable i de yakalar', function () {
    $detector = new DNAHosting_Detector(
        array('ip' => '1.2.3.4', 'port' => 2087, 'username' => 'u', 'password' => 'p'),
        function ($panel) { return new DNAHosting_ProbeExploder(); }
    );
    $e = assertThrows(function () use ($detector) {
        $detector->detect();
    }, 'ne cPanel ne Plesk');
    assertContains('probe patladi', $e->getMessage(), 'somut sebep raporlanmali');
});
