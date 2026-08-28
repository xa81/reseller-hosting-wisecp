<?php
require_once __DIR__ . '/../stubs/wisecp.php';
require_once dirname(__DIR__) . '/../coremio/modules/Servers/DNAHosting/DNAHosting.php';

/**
 * Sayfalari cekirdegin yaptigi gibi render eder: degiskenler kapsama acilir ve
 * dosya include edilir (coremio/classes/Modules.php::getPage deseni).
 */
function dna_render_page($file, array $vars)
{
    extract($vars);
    ob_start();
    include dirname(__DIR__) . '/../coremio/modules/Servers/DNAHosting/pages/' . $file . '.php';
    return ob_get_clean();
}

function dna_page_module()
{
    return new DNAHosting_Module(array(
        'id' => 3, 'name' => 'test', 'ip' => '1.2.3.4', 'port' => 2087, 'secure' => 1,
        'username' => 'bayi', 'password' => 'GIZLI123456',
    ));
}

test('order-detail sayfasi verisini $order["options"] icinden okur', function () {
    // Cekirdek bu sayfayi ["module"=>..., "order"=>...] ile render eder; "options" diye
    // bir degisken vermez (coremio/controllers/admin/orders.php:2999 ve :4713).
    $html = dna_render_page('order-detail', array(
        'module' => dna_page_module(),
        'order'  => array('options' => array(
            'domain' => 'ornek.com',
            'config' => array('user' => 'ornek1', 'password' => 'ENC:gizlisifre'),
        )),
    ));
    assertContains('ornek.com', $html, 'alan adi bos ciziliyor');
    assertContains('ornek1', $html, 'kullanici adi bos ciziliyor');
});

test('order-detail sayfasi cekirdegin aradigi config[user] ve config[password] girdilerini basar', function () {
    // templates/admin/hosting-order-detail.php bu iki girdiyi ada gore ariyor (1365, 1464,
    // 1467, 1494) ve update_hosting() onlari Filter::POST("config") ile geri okuyor.
    // Girdi yoksa Filter::POST false doner, options.config "false" olarak uzerine yazilir
    // ve config.user kaybolur — o andan sonra askiya alma, sonlandirma, sifre degistirme,
    // SSO ve kullanim yollarinin HEPSI kapanir.
    $html = dna_render_page('order-detail', array(
        'module' => dna_page_module(),
        'order'  => array('options' => array(
            'domain' => 'ornek.com',
            'config' => array('user' => 'ornek1', 'password' => 'ENC:gizlisifre'),
        )),
    ));
    assertContains('name="config[user]"', $html);
    assertContains('name="config[password]"', $html);
    assertContains('value="ornek1"', $html);
    // Sifre alani cozulmus haliyle cizilir (cekirdegin cPanel sayfasindaki desen).
    assertContains('value="gizlisifre"', $html);
});

test('order-detail sayfasi cizdigi her degeri kacisla yazar', function () {
    $html = dna_render_page('order-detail', array(
        'module' => dna_page_module(),
        'order'  => array('options' => array(
            'domain' => 'a"><script>alert(1)</script>.com',
            'config' => array('user' => 'x"><b>', 'password' => 'ENC:p"><i>'),
        )),
    ));
    assertSame(false, strpos($html, '<script>'), 'ham <script> cikmamali');
    assertSame(false, strpos($html, 'x"><b>'), 'kullanici adi kacisilmali');
    assertSame(false, strpos($html, 'p"><i>'), 'sifre kacisilmali');
    assertContains('&lt;script&gt;', $html);
});

test('order-detail sayfasi kayitli creation_info.plan degerini geri gonderir', function () {
    // creation_info de Filter::POST ile geri okunuyor; render edilmezse kayitli paket adi
    // her "Kaydet"te siliniyor.
    $html = dna_render_page('order-detail', array(
        'module' => dna_page_module(),
        'order'  => array('options' => array(
            'domain'        => 'ornek.com',
            'config'        => array('user' => 'ornek1'),
            'creation_info' => array('plan' => 'bayi_pro'),
        )),
    ));
    assertContains('name="creation_info[plan]"', $html);
    assertContains('value="bayi_pro"', $html);
});

test('order-detail sayfasi siparis bos gelse de patlamaz', function () {
    $html = dna_render_page('order-detail', array(
        'module' => dna_page_module(),
        'order'  => false,
    ));
    assertContains('name="config[user]"', $html);
    assertContains('value=""', $html);
});
