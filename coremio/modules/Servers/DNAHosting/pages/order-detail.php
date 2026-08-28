<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    /**
     * Admin siparis detayi — cekirdegin hosting formunun icine enjekte edilir.
     *
     * Cekirdek bu sayfayi ["module" => ..., "order" => ...] ile render eder; "options"
     * diye bir degisken YOKTUR (coremio/controllers/admin/orders.php:2999 ve :4713),
     * bu yuzden veri $order["options"] icinden okunur — cekirdegin kendi cPanel
     * modulunun yaptigi gibi (coremio/modules/Servers/cPanel/pages/order-detail.php:3-4).
     *
     * config[user] ve config[password] girdileri ZORUNLUDUR: admin sablonu bu iki alani
     * ada gore ariyor (templates/admin/hosting-order-detail.php:1365, 1464, 1467, 1494)
     * ve update_hosting() onlari Filter::POST("config") ile geri okuyor. Girdiler yoksa
     * Filter::POST false doner, cekirdegin budama korumasi (orders.php:4353) false'u
     * atlar ve options uzerine "config":false yazilir — options.config.user kaybolur.
     * Butun yasam dongusu dallari o anahtara bagli oldugundan (orders.php:2880, 2951,
     * 2997) hizmetin askiya alinmasi, sonlandirilmasi, sifresinin degistirilmesi, SSO'su
     * ve kullanim raporu kalici olarak imkansiz hale gelir.
     */

    $LANG     = $module->lang;
    $order    = isset($order) && $order ? $order : array();
    $options  = isset($order["options"]) ? $order["options"] : array();
    $config   = isset($options["config"]) ? $options["config"] : array();
    $creation = isset($options["creation_info"]) ? $options["creation_info"] : array();
    $domain   = isset($options["domain"]) ? $options["domain"] : '';

    $user = isset($config["user"]) ? $config["user"] : '';

    // Sifre veritabaninda kodlanmis durur ve get_order() onu cozmeden birakir
    // (coremio/controllers/admin/orders.php:583); forma cozulmus haliyle cizilir.
    $pass = '';
    if (isset($config["password"]) && is_string($config["password"]) && $config["password"] !== ''
        && class_exists("Crypt") && class_exists("Config")) {
        $decoded = Crypt::decode($config["password"], Config::get("crypt/user"));
        $pass    = is_string($decoded) ? $decoded : '';
    }

    $esc = function ($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    };
?>
<div class="formcon">
    <div class="yuzde30"><?php echo $esc($LANG["domain"]); ?></div>
    <div class="yuzde70"><?php echo $esc($domain); ?></div>
</div>

<div class="formcon">
    <div class="yuzde30"><?php echo $esc($LANG["username"]); ?></div>
    <div class="yuzde70">
        <input name="config[user]" type="text" value="<?php echo $esc($user); ?>">
    </div>
</div>

<div class="formcon">
    <div class="yuzde30"><?php echo $esc($LANG["password"]); ?></div>
    <div class="yuzde70">
        <input name="config[password]" type="text" placeholder="*******" value="<?php echo $esc($pass); ?>">
    </div>
</div>

<?php
    /**
     * creation_info da POST'tan geri okunuyor (update_hosting: Filter::POST("creation_info")).
     * Hic render edilmezse false doner ve kaydetme options.creation_info'yu ezip gecer,
     * yani siparise yazili paket adi kaybolur. Paketi buradan degistirilebilir yapmiyoruz —
     * paket degisikligi urun uzerinden, apply_updowngrade() ile yurur — ama tasidigi degeri
     * oldugu gibi geri gonderiyoruz.
     */
?>
<input type="hidden" name="creation_info[plan]" value="<?php echo $esc(isset($creation["plan"]) ? $creation["plan"] : ''); ?>">
