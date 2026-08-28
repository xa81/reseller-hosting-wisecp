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
    return array(new DNAHosting_Plesk($server, $http), $t, $http);
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

test('Plesk findWebspace 1013 hatasinda null doner', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    assertSame(null, $p->findWebspace('yok.com'));
});

test('Plesk findCustomer 11003 hatasinda firlatir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><get><result><status>error</status><errcode>11003</errcode>'
        . '<errtext>The key is not valid for this IP</errtext></result></get></customer>'));
    $e = assertThrows(function () use ($p) {
        $p->findCustomer('wisecp-501');
    }, '11003');
    assertContains('11003', $e->getMessage());
});

test('Plesk findWebspace 11003 hatasinda firlatir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>11003</errcode>'
        . '<errtext>The key is not valid for this IP</errtext></result></get></webspace>'));
    $e = assertThrows(function () use ($p) {
        $p->findWebspace('ornek.com');
    }, '11003');
    assertContains('11003', $e->getMessage());
});

/** Bos bir panelde saglama icin beklenen yanit dizisi. */
function dna_plesk_fresh_create($t)
{
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<customer><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></customer>'));
    $t->push(200, dna_packet('<customer><add><result><status>ok</status><id>77</id></result></add></customer>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    $t->push(200, dna_packet('<webspace><add><result><status>ok</status><id>9</id></result></add></webspace>'));
}

function dna_plesk_account($overrides = array())
{
    return array_merge(array(
        'username' => 'ornek1', 'password' => 'Gizli.123!', 'domain' => 'ornek.com',
        'plan' => 'Pro', 'email' => 'a@b.c', 'name' => 'Ornek Musteri',
        'external_id' => 'wisecp-501',
    ), $overrides);
}

test('Plesk createAccount musteri sonra webspace olusturur', function () {
    list($p, $t) = dna_plesk();
    dna_plesk_fresh_create($t);

    $r = $p->createAccount(array(
        'username' => 'ornek1', 'password' => 'Gizli.123!', 'domain' => 'ornek.com',
        'plan' => 'Pro', 'email' => 'a@b.c', 'name' => 'Ornek Musteri',
        'external_id' => 'wisecp-501',
    ));
    assertSame('ornek1', $r['username']);
    assertSame(77, $r['customer_id']);
    assertSame(9, $r['webspace_id']);

    $webspaceBody = $t->lastCall()['body'];
    assertContains('<owner-id>77</owner-id>', $webspaceBody);
    assertContains('<htype>vrt_hst</htype>', $webspaceBody);
    assertContains('<plan-name>Pro</plan-name>', $webspaceBody);
    assertSame(2, substr_count($webspaceBody, '<ip_address>10.0.0.2</ip_address>'),
        'ip_address hem gen_setup hem vrt_hst altinda gecmeli');
});

test('Plesk createAccount external-id yazar', function () {
    list($p, $t) = dna_plesk();
    dna_plesk_fresh_create($t);
    $p->createAccount(dna_plesk_account(array('name' => 'Ornek')));
    assertContains('<customer><add>', $t->calls[2]['body']);
    assertContains('<external-id>wisecp-501</external-id>', $t->calls[2]['body']);
});

test('Plesk createAccount once external-id ile arar, bulursa musteriyi yeniden kullanir', function () {
    // Zaman asimina ugrayan ya da yeniden denenen bir saglama, kosulsuz customer.add
    // ile geride oksuz bir musteri birakir; ikinci deneme "login zaten var" ile patlar.
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>ornek1</login></gen_info></data></result></get></customer>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    $t->push(200, dna_packet('<webspace><add><result><status>ok</status><id>9</id></result></add></webspace>'));

    $r = $p->createAccount(dna_plesk_account());
    assertSame(77, $r['customer_id']);
    assertSame(9, $r['webspace_id']);
    foreach ($t->calls as $call) {
        assertSame(false, strpos($call['body'], '<customer><add>'), 'ikinci bir musteri acilmamali');
    }
});

test('Plesk createAccount yarim kalan saglamada mevcut webspacei devralir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>ornek1</login></gen_info></data></result></get></customer>'));
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><id>9</id>'
        . '<data><gen_info><name>ornek.com</name><owner-id>77</owner-id></gen_info></data>'
        . '</result></get></webspace>'));

    $r = $p->createAccount(dna_plesk_account());
    assertSame(9, $r['webspace_id']);
    assertSame(3, count($t->calls), 'webspace zaten varsa yeniden olusturulmamali');
});

test('Plesk createAccount baskasina ait bir aboneligi devralmayi reddeder', function () {
    // Devralmak, sonlandirma dahil sonraki her islemi bize ait olmayan canli bir
    // siteye dogrulturdu.
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<customer><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></customer>'));
    $t->push(200, dna_packet('<customer><add><result><status>ok</status><id>77</id></result></add></customer>'));
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><id>9</id>'
        . '<data><gen_info><name>ornek.com</name><owner-id>99</owner-id></gen_info></data>'
        . '</result></get></webspace>'));

    $e = assertThrows(function () use ($p) {
        $p->createAccount(dna_plesk_account());
    }, 'baska bir musteriye ait');
    assertContains('99', $e->getMessage());
    assertSame(4, count($t->calls), 'webspace.add hic gonderilmemeli');
});

test('Plesk createAccount yanit kaybolduysa webspacei arayarak kurtarir', function () {
    // cPanel yolundaki accountSummary kurtarmasinin karsiligi.
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<customer><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></customer>'));
    $t->push(200, dna_packet('<customer><add><result><status>ok</status><id>77</id></result></add></customer>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    $t->pushError('Operation timed out after 300000 milliseconds');
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><id>9</id>'
        . '<data><gen_info><name>ornek.com</name><owner-id>77</owner-id></gen_info></data>'
        . '</result></get></webspace>'));

    $r = $p->createAccount(dna_plesk_account());
    assertSame(9, $r['webspace_id']);
});

test('Plesk createAccount gercek bir webspace.add hatasini yutmaz', function () {
    // Plesk web sunucusunu yapilandirirken patladiginda geride YARIM bir abonelik
    // birakir; arama BASARILI olur. Her hatada aramaya duserek devam etmek, hostingi
    // olmayan bir abonelik icin "basarili" raporlamak olurdu.
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<customer><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></customer>'));
    $t->push(200, dna_packet('<customer><add><result><status>ok</status><id>77</id></result></add></customer>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    $t->push(200, dna_packet('<webspace><add><result><status>error</status><errcode>1023</errcode>'
        . '<errtext>Failed to configure the web server</errtext></result></add></webspace>'));

    assertThrows(function () use ($p) {
        $p->createAccount(dna_plesk_account());
    }, 'Failed to configure the web server');
});

test('Plesk createAccount webspace.add govdesi sabit sirada kurulur', function () {
    list($p, $t) = dna_plesk();
    dna_plesk_fresh_create($t);

    $p->createAccount(dna_plesk_account());

    $body = $t->lastCall()['body'];

    $posGenSetup = strpos($body, '<gen_setup>');
    $posHosting  = strpos($body, '<hosting>');
    $posPrefs    = strpos($body, '<prefs>');
    $posPlanName = strpos($body, '<plan-name>');
    assertTrue($posGenSetup !== false, '<gen_setup> bulunamadi');
    assertTrue($posHosting !== false, '<hosting> bulunamadi');
    assertTrue($posPrefs !== false, '<prefs> bulunamadi');
    assertTrue($posPlanName !== false, '<plan-name> bulunamadi');
    assertTrue($posGenSetup < $posHosting, 'gen_setup hosting dan once gelmeli');
    assertTrue($posHosting < $posPrefs, 'hosting prefs dan once gelmeli');
    assertTrue($posPrefs < $posPlanName, 'prefs plan-name dan once gelmeli');

    $posName    = strpos($body, '<name>', $posGenSetup);
    $posOwnerId = strpos($body, '<owner-id>', $posGenSetup);
    $posIp      = strpos($body, '<ip_address>', $posGenSetup);
    $posHtype   = strpos($body, '<htype>', $posGenSetup);
    $posStatus  = strpos($body, '<status>', $posGenSetup);
    assertTrue($posName !== false, 'gen_setup/name bulunamadi');
    assertTrue($posOwnerId !== false, 'gen_setup/owner-id bulunamadi');
    assertTrue($posIp !== false, 'gen_setup/ip_address bulunamadi');
    assertTrue($posHtype !== false, 'gen_setup/htype bulunamadi');
    assertTrue($posStatus !== false, 'gen_setup/status bulunamadi');
    assertTrue($posName < $posOwnerId, 'name owner-id dan once gelmeli');
    assertTrue($posOwnerId < $posIp, 'owner-id ip_address dan once gelmeli');
    assertTrue($posIp < $posHtype, 'ip_address htype dan once gelmeli');
    assertTrue($posHtype < $posStatus, 'htype status dan once gelmeli');
});

test('Plesk suspend mevcut duruma 32 bitini ekler', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status>'
        . '<data><gen_info><status>16</status></gen_info></data></result></get></webspace>'));
    $t->push(200, dna_packet('<webspace><set><result><status>ok</status></result></set></webspace>'));
    assertTrue($p->suspend(9));
    assertContains('<status>48</status>', $t->lastCall()['body'], '16 | 32 = 48');
});

test('Plesk unsuspend yalnizca 32 bitini kaldirir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status>'
        . '<data><gen_info><status>48</status></gen_info></data></result></get></webspace>'));
    $t->push(200, dna_packet('<webspace><set><result><status>ok</status></result></set></webspace>'));
    assertTrue($p->unsuspend(9));
    assertContains('<status>16</status>', $t->lastCall()['body'], 'admin askisi korunmali');
});

test('Plesk terminate abonelik kimligi yoksa hicbir sey silmez', function () {
    // Kimliksiz bir <filter> sunucudaki HER abonelikle eslesir.
    list($p, $t) = dna_plesk();
    assertThrows(function () use ($p) {
        $p->terminate(0, 77, 'wisecp-501');
    }, 'abonelik kimligi');
    assertSame(0, count($t->calls), 'hicbir istek gonderilmemeli');
});

test('Plesk terminate once webspace siler, musteri bossa musteriyi de siler', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><del><result><status>ok</status></result></del></webspace>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>m</login><external-id>wisecp-501</external-id></gen_info></data>'
        . '</result></get></customer>'));
    $t->push(200, dna_packet('<customer><del><result><status>ok</status></result></del></customer>'));

    assertTrue($p->terminate(9, 77, 'wisecp-501'));
    assertSame(4, count($t->calls));
    assertContains('<webspace><del>', $t->calls[0]['body']);
    assertContains('<id>9</id>', $t->calls[0]['body']);
    assertContains('<owner-id>77</owner-id>', $t->calls[1]['body'], 'kalan abonelikler sayilmali');
    assertContains('<customer><del>', $t->calls[3]['body']);
});

test('Plesk terminate musterinin baska aboneligi varsa musteriyi silmez', function () {
    // Sahiplik guardi musterinin BIZIM oldugunu kanitlar; SADECE bu abonelige sahip
    // oldugunu kanitlamaz. Panelden ikinci bir site eklenmisse, bir WiseCP hizmetini
    // sonlandirmak digerini de sessizce silerdi.
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><del><result><status>ok</status></result></del></webspace>'));
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><id>12</id>'
        . '<data><gen_info><name>ikinci.com</name><owner-id>77</owner-id></gen_info></data>'
        . '</result></get></webspace>'));

    assertTrue($p->terminate(9, 77, 'wisecp-501'));
    assertSame(2, count($t->calls), 'customer.del hic gonderilmemeli');
    assertSame(false, strpos($t->lastCall()['body'], '<customer>'));
});

test('Plesk terminate sayim cozulemezse musteriyi silmez', function () {
    // Bosluk KANITLANAMADIYSA musteri bos sayilmaz.
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><del><result><status>ok</status></result></del></webspace>'));
    $t->push(200, dna_packet('<system><status>error</status><errcode>11003</errcode>'
        . '<errtext>The key is not valid for this IP</errtext></system>'));

    assertTrue($p->terminate(9, 77, 'wisecp-501'));
    assertSame(2, count($t->calls), 'customer.del hic gonderilmemeli');
});

test('Plesk terminate external-id tutmuyorsa musteriyi silmez', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><del><result><status>ok</status></result></del></webspace>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>m</login><external-id>baska-sistem-9</external-id></gen_info></data>'
        . '</result></get></customer>'));

    assertTrue($p->terminate(9, 77, 'wisecp-501'));
    assertSame(3, count($t->calls), 'customer.del hic gonderilmemeli');
});

test('Plesk terminate webspace zaten yoksa (1013) devam eder', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><del><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></del></webspace>'));
    $t->push(200, dna_packet('<webspace><get><result><status>error</status><errcode>1013</errcode>'
        . '<errtext>Object not found</errtext></result></get></webspace>'));
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>m</login><external-id>wisecp-501</external-id></gen_info></data>'
        . '</result></get></customer>'));
    $t->push(200, dna_packet('<customer><del><result><status>ok</status></result></del></customer>'));
    assertTrue($p->terminate(9, 77, 'wisecp-501'));
});

test('Plesk terminate webspace silme gercek hatasini yutmaz', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><del><result><status>error</status><errcode>1023</errcode>'
        . '<errtext>Operation failed</errtext></result></del></webspace>'));
    assertThrows(function () use ($p) {
        $p->terminate(9, 77, 'wisecp-501');
    }, '1023');
    assertSame(1, count($t->calls));
});

test('Plesk changePassword hem panel hem FTP sifresini degistirir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><set><result><status>ok</status></result></set></customer>'));
    $t->push(200, dna_packet('<webspace><set><result><status>ok</status></result></set></webspace>'));
    assertTrue($p->changePassword(77, 9, 'YeniGizli.9!'));
    assertSame(2, count($t->calls));
    assertContains('<passwd>YeniGizli.9!</passwd>', $t->calls[0]['body']);
    assertContains('ftp_password', $t->calls[1]['body']);
});

test('Plesk changePlan switch-subscription ve plan-guid kullanir', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><switch-subscription><result><status>ok</status>'
        . '</result></switch-subscription></webspace>'));
    assertTrue($p->changePlan(9, array('name' => 'Pro', 'guid' => 'g-2')));
    assertContains('<switch-subscription>', $t->lastCall()['body']);
    assertContains('<plan-guid>g-2</plan-guid>', $t->lastCall()['body']);
});

test('Plesk usage stat ve limits okur', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<webspace><get><result><status>ok</status><data>'
        . '<stat><real_size>536870912</real_size><traffic>1048576</traffic></stat>'
        . '<limits><limit><name>disk_space</name><value>2147483648</value></limit>'
        . '<limit><name>max_traffic</name><value>-1</value></limit></limits>'
        . '</data></result></get></webspace>'));
    $u = $p->usage(9);
    assertSame(536870912, $u['disk_used']);
    assertSame(2147483648, $u['disk_limit']);
    assertSame(1048576, $u['bw_used']);
    assertSame(0, $u['bw_limit'], '-1 sinirsiz demektir, 0 olarak raporlanir');
});

test('Plesk createSession oturum URLsi kurar', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<server><create_session><result><status>ok</status>'
        . '<id>SESS123</id></result></create_session></server>'));
    $url = $p->createSession('ornek1', '9.9.9.9');
    assertSame('https://5.6.7.8:8443/enterprise/rsession_init.php?PLESKSESSID=SESS123', $url);
    assertContains('<user_ip>9.9.9.9</user_ip>', $t->lastCall()['body']);
});

test('Plesk createSession oturum kimligini sir olarak kaydeder', function () {
    // PLESKSESSID canli bir kimlik bilgisidir; loga duz yazilmamalidir.
    list($p, $t, $h) = dna_plesk();
    $t->push(200, dna_packet('<server><create_session><result><status>ok</status>'
        . '<id>SESS1234567</id></result></create_session></server>'));
    $p->createSession('ornek1', '9.9.9.9');
    assertSame('PLESKSESSID=***', $h->mask('PLESKSESSID=SESS1234567'));
});
