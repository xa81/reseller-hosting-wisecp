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
    return array(new DNAHosting_Cpanel($server, $http), $t, $http);
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

test('cPanel createAccount kimlik bilgilerini dondurur', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    $t->push(200, '{"metadata":{"result":1},"data":{}}');
    $r = $c->createAccount(array(
        'username' => 'ornek1', 'password' => 'Gizli.123!',
        'domain' => 'ornek.com', 'plan' => 'pro', 'email' => 'a@b.c',
    ));
    assertSame('ornek1', $r['username']);
    assertSame('Gizli.123!', $r['password']);
    $call = $t->lastCall();
    assertSame('POST', $call['method']);
    assertContains('createacct', $call['url']);
    assertContains('plan=bayi_pro', $call['body']);
});

test('cPanel createAccount zaman asiminda accountsummary ile kurtarir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    $t->pushError('Operation timed out after 400000 milliseconds');
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1","domain":"ornek.com"}]}}');
    $r = $c->createAccount(array(
        'username' => 'ornek1', 'password' => 'Gizli.123!',
        'domain' => 'ornek.com', 'plan' => 'pro', 'email' => 'a@b.c',
    ));
    assertSame('ornek1', $r['username']);
});

test('cPanel createAccount kurtarma domaini tutmuyorsa hatayi verir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    $t->pushError('Operation timed out');
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1","domain":"baska.com"}]}}');
    assertThrows(function () use ($c) {
        $c->createAccount(array(
            'username' => 'ornek1', 'password' => 'Gizli.123!',
            'domain' => 'ornek.com', 'plan' => 'pro', 'email' => 'a@b.c',
        ));
    }, 'Operation timed out');
});

test('cPanel suspend/unsuspend/terminate dogru fonksiyonu cagirir', function () {
    list($c, $t) = dna_cpanel();
    $ok = '{"metadata":{"result":1},"data":{}}';
    $t->push(200, $ok); assertTrue($c->suspendAccount('ornek1', 'Odeme yok'));
    assertContains('suspendacct', $t->lastCall()['url']);
    assertContains('reason=Odeme+yok', $t->lastCall()['body']);
    $t->push(200, $ok); assertTrue($c->unsuspendAccount('ornek1'));
    assertContains('unsuspendacct', $t->lastCall()['url']);
    assertContains('user=ornek1', $t->lastCall()['body']);
    $t->push(200, $ok); assertTrue($c->terminateAccount('ornek1'));
    assertContains('removeacct', $t->lastCall()['url']);
    assertContains('user=ornek1', $t->lastCall()['body']);
    assertContains('keepdns=0', $t->lastCall()['body'], 'keepdns dusulurse DNS bolgesi geride kalir');
});

test('cPanel changePackage plani cozer', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_kurumsal","QUOTA":"1","BWLIMIT":"1"}]}}');
    $t->push(200, '{"metadata":{"result":1},"data":{}}');
    assertTrue($c->changePackage('ornek1', 'kurumsal'));
    assertContains('pkg=bayi_kurumsal', $t->lastCall()['body']);
});

test('cPanel accountSummary hesap yoksa null doner', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":0,"reason":"account does not exist"}}');
    assertSame(null, $c->accountSummary('yokboyle'));
});

test('cPanel usage MByi bayta cevirir, unlimited sifir olur', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
        . '"diskused":"512M","disklimit":"2048M"}]}}');
    $t->push(200, '{"metadata":{"result":1},"data":{"bandwidth":[{"acct":[{"user":"ornek1",'
        . '"totalbytes":"1048576","limit":"unlimited"}]}]}}');
    $u = $c->usage('ornek1');
    assertSame(512 * 1024 * 1024, $u['disk_used']);
    assertSame(2048 * 1024 * 1024, $u['disk_limit']);
    assertSame(1048576, $u['bw_used']);
    assertSame(0, $u['bw_limit']);
});

test('cPanel usage sayisal trafik sinirini bayt olarak okur', function () {
    // showbw, sinirin BAYT olarak bildirildigi tek yerdir. accountsummary/listaccts
    // icindeki bwlimit baska bir birimdedir; karistirmak siniri ~10^6 katsayisiyla
    // yanlis gosterir ve her musteriyi %100 dolu raporlar.
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
        . '"diskused":"512M","disklimit":"2048M"}]}}');
    $t->push(200, '{"metadata":{"result":1},"data":{"bandwidth":[{"acct":[{"user":"ornek1",'
        . '"totalbytes":"2147483648","limit":"10737418240"}]}]}}');
    $u = $c->usage('ornek1');
    assertSame(10737418240, $u['bw_limit'], '10 GB bayt olarak okunmali');
    assertSame(2147483648, $u['bw_used']);
    assertContains('/json-api/showbw', $t->lastCall()['url']);
});

test('cPanel usage trafigi accountsummary yerine showbw den alir', function () {
    list($c, $t) = dna_cpanel();
    // accountsummary yaniti trafigi YANLIS birimde tasisa bile yok sayilmali.
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
        . '"diskused":"512M","disklimit":"2048M","totalbytes":"99","limit":"5000"}]}}');
    $t->push(200, '{"metadata":{"result":1},"data":{"bandwidth":[{"acct":[{"user":"ornek1",'
        . '"totalbytes":"1024","limit":"2048"}]}]}}');
    $u = $c->usage('ornek1');
    assertSame(1024, $u['bw_used']);
    assertSame(2048, $u['bw_limit']);
});

test('cPanel usage hesap showbw ciktisinda yoksa trafigi sifirlar', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
        . '"diskused":"512M","disklimit":"2048M"}]}}');
    $t->push(200, '{"metadata":{"result":1},"data":{"bandwidth":[{"acct":[{"user":"baska",'
        . '"totalbytes":"1024","limit":"2048"}]}]}}');
    $u = $c->usage('ornek1');
    assertSame(0, $u['bw_used']);
    assertSame(0, $u['bw_limit']);
});

test('cPanel createSession tam URL dondurur', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"https://1.2.3.4:2083/cpsess123/"}}');
    $url = $c->createSession('ornek1', 'cpaneld', '9.9.9.9');
    assertSame('https://1.2.3.4:2083/cpsess123/', $url);
    assertContains('create_user_session', $t->lastCall()['url']);
});

test('cPanel createSession yalnizca user ve service gonderir', function () {
    // WHM API 1'in create_user_session'inda client_ip diye bir arguman yok; WiseCP'nin
    // kendi cPanel modulu de (cPanel.php:1205, :1259) WHMCS'inki de yalnizca bu ikisini
    // gonderiyor. Bos bir locale ise WHM tarafindan harfiyen alinabilir.
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"https://1.2.3.4:2083/cpsess123/"}}');
    $c->createSession('ornek1', 'cpaneld', '9.9.9.9');
    $body = $t->lastCall()['body'];
    assertContains('user=ornek1', $body);
    assertContains('service=cpaneld', $body);
    assertSame(false, strpos($body, 'client_ip'), 'client_ip gonderilmemeli');
    assertSame(false, strpos($body, 'locale'), 'bos locale gonderilmemeli');
});

test('cPanel createSession SSL disi sunucuda hem semayi hem portu indirir', function () {
    // WHM her zaman SSL portunda bir https URLsi uretir. Yalnizca semayi degistirmek
    // http://host:2083 verir ve orada dinleyen bir sey yoktur.
    list($c, $t) = dna_cpanel(0);
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"https://1.2.3.4:2083/cpsess123/"}}');
    assertSame('http://1.2.3.4:2082/cpsess123/', $c->createSession('ornek1'));
});

test('cPanel createSession SSL disi sunucuda goreli URLyi de dogru kurar', function () {
    list($c, $t) = dna_cpanel(0);
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"/cpsess123/"}}');
    assertSame('http://1.2.3.4:2082/cpsess123/', $c->createSession('ornek1'));
});

test('cPanel createSession http(s) olmayan URLyi reddeder', function () {
    // URL bir tarayiciya veriliyor; baska bir seyle yanit veren bir panel, musteriyi
    // gonderecegimiz yer degildir.
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"javascript:alert(1)"}}');
    assertThrows(function () use ($c) { $c->createSession('ornek1'); }, 'kullanilamaz');
});

test('cPanel createSession oturum URLsini sir olarak kaydeder', function () {
    // cpsess jetonu yolun icinde durur: URLnin kendisi canli bir kimlik bilgisidir.
    list($c, $t, $h) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"https://1.2.3.4:2083/cpsess1234567/"}}');
    $url = $c->createSession('ornek1');
    assertSame('***', $h->mask($url), 'sonraki loglarda maskelenmeli');
});

test('cPanel createSession jetonu URETEN cagrinin log satirina yazmaz', function () {
    // addSecret ancak SONRAKI satirlari kurtarir: jetonu tasiyan satir, cagri
    // loglandigi anda henuz sir listesinde olmayan bir degeri icerir. Tasarim
    // dokumani (S6) oturum URLsinin loga YAZILMADAN once maskelenmesini sart kosar.
    list($c, $t, $h) = dna_cpanel();
    $lines = array();
    $h->setLogger(function ($action, $request, $response) use (&$lines) {
        $lines[] = $action . "\n" . $request . "\n" . $response;
    });
    $t->push(200, '{"metadata":{"result":1},"data":{'
        . '"url":"https://1.2.3.4:2083/cpsess1234567/login/?session=ornek1%3acpsess1234567",'
        . '"cp_security_token":"/cpsess1234567",'
        . '"session":"ornek1:GIZLIJETON9876"}}');
    $url = $c->createSession('ornek1');

    $log = implode("\n", $lines);
    assertSame(false, strpos($log, 'cpsess1234567'), 'cpsess jetonu loga duz yazilmamali');
    assertSame(false, strpos($log, 'GIZLIJETON9876'), 'session alani loga duz yazilmamali');
    // Redaksiyon yalnizca log kopyasina uygulanir; tarayiciya giden URL saglam kalmali.
    assertContains('cpsess1234567', $url, 'donen URL redakte edilmemeli');
});

test('cPanel redaksiyonu yalnizca oturum cagrisina bakar, diger yanitlar okunur kalir', function () {
    // Fazla redaksiyon da bir hatadir: listaccts yaniti teshis icin gorunur kalmali.
    list($c, $t, $h) = dna_cpanel();
    $lines = array();
    $h->setLogger(function ($action, $request, $response) use (&$lines) {
        $lines[] = $response;
    });
    $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1"}]}}');
    $c->call('listaccts');
    assertContains('"user":"ornek1"', implode("\n", $lines));
});

test('cPanel createSession goreli URLyi mutlaklastirir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"/cpsess123/"}}');
    assertSame('https://1.2.3.4:2083/cpsess123/', $c->createSession('ornek1'));
});

test('cPanel changePassword dogru fonksiyonu cagirir', function () {
    list($c, $t) = dna_cpanel();
    $ok = '{"metadata":{"result":1},"data":{}}';
    $t->push(200, $ok);
    assertTrue($c->changePassword('ornek1', 'YeniSifre.123!'));
    assertContains('passwd', $t->lastCall()['url']);
    assertContains('password=YeniSifre', $t->lastCall()['body']);
});

test('cPanel accountSummary taşıma hatasında null yerine hata firlatir, usage bunu gercek sebebe donerek "not found" demez', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":0,"reason":"API sikintili"}}');
    assertThrows(function () use ($c) {
        $c->accountSummary('ornek1');
    }, 'API sikintili');
    $t->push(200, '{"metadata":{"result":0,"reason":"API sikintili"}}');
    $e = assertThrows(function () use ($c) {
        $c->usage('ornek1');
    }, 'API sikintili');
    assertContains('API sikintili', $e->getMessage());
});
