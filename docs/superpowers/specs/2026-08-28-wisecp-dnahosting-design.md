# DNAHosting — WiseCP Sunucu Modülü Tasarımı

**Tarih:** 2026-08-28
**Durum:** Onaylandı, uygulamaya hazır
**Kaynak modül:** `bakcay/reseller-hosting-whmcs` — `modules/servers/dnahosting` (WHMCS, 3.941 satır)

---

## 1. Amaç

Tek bir WiseCP sunucu modülü ile hem **cPanel/WHM** hem **Plesk** reseller hesapları üzerinden paylaşımlı hosting satmak. Admin yalnızca sunucu IP'si, reseller kullanıcı adı ve bir kimlik bilgisi (API token veya şifre) girer; modül panelin hangisi olduğunu kendisi bulur.

Bu belge, halihazırda WHMCS için yazılmış ve canlıda kısmen doğrulanmış modülün WiseCP'ye portunu tanımlar.

### Kapsam

**İçinde:** hesap oluşturma, askıya alma, askıyı kaldırma, sonlandırma, şifre değiştirme, paket değiştirme, bağlantı testi, disk/trafik kullanımı, müşteri SSO, admin SSO, müşteri paneli sayfası, aktivasyon e-postası şablonları, ürün formu.

**Dışında:** e-posta hesabı ve yönlendirme yönetimi (`getEmailList`, `addNewEmail`, `setQuota`, `getForwardsList` ve arkadaşları), reseller hesabı satışı (`setupReseller`), `listAccounts`, `getDomains`.

E-posta yönetimi bilinçli olarak dışarıda: `config.php`'deki `supported` listesine konmadığı sürece WiseCP bu ekranları hiç göstermez (`coremio/controllers/website/account_products.php:4050`), yani eksikliği kullanıcıya yarım bir arayüz olarak yansımaz.

---

## 2. Doğrulanmış WiseCP sözleşmesi

Bu bölümdeki her madde `wisecp-decoded` kaynağından okunarak doğrulanmıştır. **`WISECP_REFERANS/06_MODUL_SISTEMI.md` bu konuda güvenilmezdir** — `class cPanel extends ServerModule` ve `config.php` içinde `meta`/`settings` anahtarları olduğunu iddia eder; gerçekte sınıf adı `cPanel_Module`, config formatı tamamen farklıdır. Referans dokümana değil, kaynağa bakılacak.

Resmî dokümantasyon (`dev.wisecp.com`, `docs.wisecp.com`) bu belgenin yazıldığı tarihte erişilemez durumdadır; tüm makale URL'leri WiseCP 5 tanıtım sayfasına 301 ile yönlenmektedir.

### 2.1 Sınıf ve isimlendirme

Çekirdek modülü `$server["type"] . "_Module"` diye örnekler (`coremio/helpers/orders.php:2771`). Yani:

```
klasör adı = sınıf öneki = servers tablosundaki type sütunu = admin dropdown'ında görünen etiket
```

Dropdown etiketi klasör adının ta kendisidir (`templates/admin/add-hosting-shared-server.php:265`). Sonradan değiştirmek kurulu sunucuları bozar.

**Karar: `DNAHosting`** → `DNAHosting_Module`, `coremio/modules/Servers/DNAHosting/`.

### 2.2 Temel sınıf

`ServerModule` (`coremio/classes/Modules.php:239`). Sağladıkları:

- `__construct($server, $options)` — `$this->config`'i `config.php` + dışarıdan gelen options ile birleştirir, `$this->lang`'i yükler, `$this->admin`'i doldurur, sonra `define_server_info($server)` çağırır
- `set_order($order)` — `$this->order`, `$this->product`, `$this->user`, `$this->val_of_conf_opt`, `$this->id_of_conf_opt`, `$this->requirements` doldurur
- `activation_infos($type, $order, $lang)` — aktivasyon e-postası sayfasını render eder, şifreleri çözer
- `get_page($file, $vars)`, `encode_str()`, `decode_str()`
- `clientArea_buttons_output()`, `adminArea_buttons_output()`, `panel_links_for_client()`, `panel_links_for_admin()`
- `save_log()` → `Modules::save_log($type, $module, $action, $request, $response, $processed)`

**Sağlamadıkları (bizim yazmamız gereken):** `use_method()`, `testConnect()`, `getPlans()`, `createAccount()`, `suspend()`, `unsuspend()`, `removeAccount()`, `changePassword()`, `change_plan()`, `apply_options()`, `apply_updowngrade()`, `getDisk()`, `getBandwidth()`, `getSummary()`, `clientArea()`, `define_server_info()`.

### 2.3 Yaşam döngüsü çağrıları

Hepsi `coremio/helpers/orders.php::hosting_module_operation()` içinden:

| Çağrı | Satır | Koşul | Beklenen dönüş |
|---|---|---|---|
| `createAccount($domain, $createopt)` | 2843 | `create`/`active`/`approve` ve `config.user` yoksa | `['username','password','ftp_info'=>[...]]` veya `false` |
| `suspend()` / `suspend_reseller()` | 2899 | `creation_info.reseller` doluysa `_reseller` çeşidi | bool |
| `unsuspend()` / `unsuspend_reseller()` | 2934 | aynı | bool |
| `removeAccount()` / `removeReseller()` | 2970 | aynı | bool |
| `apply_updowngrade($orderopt, $product)` | 3076, 3368, 3701 | paket yükseltme/düşürme | bool |

`false` dönüldüğünde çekirdek `$operations->error` alanını okuyup kullanıcıya gösterir ve işlemi `failed` sayar. **Hata mesajını `$this->error`'a yazmak zorunludur**, aksi halde kullanıcı boş bir hata görür.

### 2.4 `force_setup` tuzağı

`ServerModule::$force_setup` varsayılan olarak `true` (`coremio/classes/Modules.php:242`). Çekirdek şunu yapar (`coremio/helpers/orders.php:2780`):

```php
if ((!property_exists($operations,"force_setup") || $operations->force_setup)
    && $order["status"] == "inprocess") {
    $username = self::UsernameGenerator($domain);
    $password = str_replace("%",".",self::generate_password(12));
    if ($server["type"] == "Plesk") {          // <-- satır 2785
        $password = /* sembol içeren güçlü şifre */;
    }
    ...
}
```

Panel tipi karşılaştırması **sunucu tipi string'ine sabit kodlanmış.** Bizim tipimiz `DNAHosting` olacağı için Plesk'e giden hesaplar zayıf şifre üreticisini kullanır ve Plesk'in şifre politikasına takılır.

**Karar: `public $force_setup = false;`** — `Plesk_Module`'ün yaptığı gibi (`Plesk.php:11`). Kullanıcı adını ve şifreyi hedef panele uygun biçimde `createAccount()` içinde kendimiz üretiriz.

### 2.5 `createAccount()` dönüşünün daraltılması

Çekirdek dönen diziden **yalnızca üç anahtarı** okur (`orders.php:2847-2853`):

```php
$orderopt["config"]["user"]     = $create["username"];
$orderopt["config"]["password"] = Crypt::encode($create["password"], Config::get("crypt/user"));
$orderopt["ftp_info"]           = $create["ftp_info"];   // ftp_info.password doluysa
```

Başka hiçbir anahtar kalıcılaşmaz. **Plesk'in webspace ve customer ID'lerini sipariş verisine yazamayız.**

**Karar:** kimlik yeniden türetilebilir olmalı.
- **cPanel:** `config.user` = cPanel kullanıcı adı. WHM API zaten bu anahtarla çalışır, ek bilgiye gerek yok.
- **Plesk:** oluşturma anında `external_id = "wisecp-" . $order["id"]` yazılır. Bu değer hem **kimlik** hem **sahiplik kanıtıdır**, ama iki yolda farklı kullanılır:

  - **Hesap açarken** müşteri doğrudan `external_id` ile aranır (`findCustomer()`); bulunursa yeniden kullanılır, bulunmazsa oluşturulur. Bu, zaman aşımına uğrayan bir sağlamanın geride öksüz müşteri bırakmasını ve ikinci denemenin mükerrer login ile patlamasını önler.
  - **Sonraki her işlemde** Plesk'in webspace ve customer ID'lerini sipariş verisine yazamadığımız için kimlik yeniden türetilir: abonelik alan adıyla bulunur (`findWebspace()`), **sonra sahibinin `external_id`'si bizimkiyle karşılaştırılır**. Doğrulama `pleskTargets()` içinde bir kez yapılır ve doğrulanmış (webspace_id, customer_id) çifti istek boyunca ezberlenir; askıya alma, askıdan indirme, şifre değiştirme, paket değiştirme, kullanım ve sonlandırma bu tek guard'ı miras alır.

  `findWebspace()` tek başına sahiplik hakkında hiçbir şey söylemez — o alan adını taşıyan hangi abonelik varsa onu döndürür — bu yüzden doğrulama atlanamaz. Alan adı normalizasyonu için bkz. §5.8.

### 2.6 Sunucu kaydı ve kimlik bilgisi

`servers` tablosuna yazılan alanlar (`coremio/controllers/admin/products.php:2578`):

```
type, name, ns1..ns4, maxaccounts, full_alert, cost_price, cost_currency,
ip, username, password, access_hash, secure, port, updowngrade_remove_server
```

Dikkat edilecekler:

- **`hostname` alanı yok** — yalnızca `ip`. WHMCS'teki hostname/IP ikilisi ve ona bağlı tüm mantık düşüyor.
- `password` **zorunlu** (`Validation::isEmpty` ile reddediliyor) ve `Crypt::encode` ile **şifreli** saklanıyor.
- `access_hash` opsiyonel ve **düz metin** saklanıyor. Alanın etiketi çekirdek dil dosyasından geliyor (`coremio/locale/tr/cm/admin/products.php:701` → "Erişim Anahtarı (Access Hash)"), modül dil dosyasıyla değiştirilemiyor.
- Benzersizlik `(ip, username)` çifti üzerinde (`coremio/models/admin/products.php:809`). Aynı IP'ye farklı reseller kullanıcılarıyla birden fazla sunucu eklenebilir.

**Karar: tek alan — `password`.** `access-hash` bayrağı `config.php`'de açılmaz. İçine ya API token'ı ya panel şifresi yazılır:

- **cPanel:** her zaman WHM API token → `Authorization: WHM <user>:<token>`
- **Plesk:** önce `KEY: <secret-key>` başlığıyla denenir; kimlik doğrulama hatası gelirse HTTP basic auth'a düşülür. Kazanan yöntem panel tipiyle birlikte önbelleğe yazılır, yani tahmin sunucu başına bir kez yapılır.

Gerekçe: kimlik bilgisi veritabanında şifreli durur, çekirdekten gelen yanıltıcı "Access Hash" etiketiyle uğraşılmaz, admin cPanel için asla kullanılmayacak sahte bir şifre uydurmak zorunda kalmaz. Ne cPanel ne Plesk SSO'su panel şifresine ihtiyaç duyar — ikisi de API session'ı üzerinden çalışır — dolayısıyla ikinci bir alana ihtiyaç yoktur.

### 2.7 Ürün formu ve `module_data`

Ürün formundaki modül alanları, modülün kendi sayfası tarafından render edilir (`coremio/controllers/admin/products.php:1002`):

```php
echo Modules::getPage("Servers", $server["type"], "create-account-form-elements", $data);
```

Bu çağrı **seçili bir `server_id` ile** yapılır (`get_shared_server_mdata()`, satır 976-1001). Yani form render edilirken hangi sunucuyla konuşulacağı bellidir ve `$module->getPlans()` somut bir panele karşı çalışır.

Form alanları `module_data[...]` adıyla POST edilir ve JSON olarak `products.module_data` sütununa yazılır (`products.php:1718`). **İsimli dizidir** — WHMCS'in `configoptionN` pozisyonel bağlamasının donmuş ordinal haritası derdi yoktur; alan eklemek, çıkarmak, yeniden adlandırmak serbesttir.

Modül tarafında okunuşu:

```php
$module_data    = Utility::jdecode($product["module_data"], true);
$create_account = $module_data["create_account"] ?? $module_data;
$plan           = $create_account["plan"] ?? false;
```

Sipariş anında `$orderopt["creation_info"]` içine taşınır ve `createAccount($domain, $options)` çağrısına `$options["creation_info"]` olarak ulaşır.

### 2.8 Kullanım verisi

WHMCS'teki toplu cron senkronu **yoktur**. Kullanım, müşteri servis sayfasını açtığında tek servis için çekilir (`coremio/controllers/website/account_products.php:4050`):

```php
if (in_array("disk-bandwidth-usage", $module->config["supported"])
    && (method_exists($module,"getDisk") || method_exists($module,"getBandwidth"))) {
    $bandwidth = $module->getBandwidth();
    $disk      = $module->getDisk();
}
```

Beklenen dönüş şekli (`HestiaCP.php:225-275` referans alındı):

```php
[
  'limit'        => <bayt, sınırsızsa 0>,
  'used'         => <bayt>,
  'used-percent' => <0-100 tamsayı>,
  'format-limit' => <"10 GB" | "∞">,
  'format-used'  => <"1.2 GB" | "0 KB">,
]
```

`FileManager::converByte()`, `FileManager::formatByte()` ve `Utility::getPercent()` çekirdekte mevcut, bunlar kullanılacak.

### 2.9 SSO ve buton yönlendirmesi

`use_method($param)` çekirdek tarafından çağrılır (`account_products.php:4102`) ama **temel sınıfta yoktur** — modülün yazması gerekir. cPanel modülündeki desen (`cPanel.php:1103`):

```php
public function use_method($param = '') {
    $param  = str_replace('-', '_', $param);
    $prefix = defined('ADMINISTRATOR') ? 'use_adminArea_' : 'use_clientArea_';
    if ($param && method_exists($this, $prefix.$param)) return $this->{$prefix.$param}();
}
```

SSO metotları çıktıyı kendileri basar (`Utility::redirect($link)` veya otomatik gönderilen bir form) ve `true` döner.

`config["type"] == "hosting"` olduğunda müşteri paneli butonları `panel_links_for_client()` üzerinden gelir; `clientArea_buttons_output()`'un `SingleSignOn2` dalı bizi ilgilendirmez.

### 2.10 Önbellek altyapısı

`Cache` sınıfı (`coremio/classes/Cache.php`) dosya tabanlı, `store($key,$data,$ttl)` / `retrieve($key)` / `isCached($key)` / `erase($key)` sunuyor.

**İki uyarı:**

1. `store()` ve `isCached()`, kurulum alan adını lisans dosyasındaki alan adıyla karşılaştırıyor; eşleşmezse `store()` sessizce hiçbir şey yapmıyor, `isCached()` her zaman `false` dönüyor. Lisanssız bir geliştirme kurulumunda önbellek kalıcı olarak ıskalar.
2. `retrieve()` **hiçbir zaman veri döndürmüyor** — doğrulandı, bkz. §8 risk 2. Decode edilmiş gövdedeki `$timestamp === false` dalı `$type = "data"` atayıp fonksiyonun sonundan düşüyor; `return` ifadesi yok, dolayısıyla her çağrı `null` veriyor.

**Karar:** önbellek **saf bir optimizasyondur, doğruluk bağımlılığı değildir.** Modül, önbellek kalıcı olarak ıskaladığında da doğru çalışmalı — yalnızca sunucu başına istek başına bir fazladan probe yapmalıdır. Panel tespiti ayrıca istek içi bellekte de tutulur (`$this->storage`), böylece tek bir istekte tek probe yapılır. Uygulamanın ilk adımı `Cache::retrieve()`'nin canlıda gerçekten veri döndürdüğünü doğrulamaktır; döndürmüyorsa `Cache` tamamen bırakılır ve yalnızca istek içi bellek kullanılır.

---

## 3. Mimari

### 3.1 Dosya yapısı

```
coremio/modules/Servers/DNAHosting/
├── DNAHosting.php                        WiseCP giriş noktası, yönlendirici
├── config.php                            type, portlar, supported[]
├── init.php                              lib include'ları
├── index.html                            dizin listeleme koruması
├── lib/
│   ├── Http.php                          ortak cURL katmanı, loglama, maskeleme
│   ├── Cpanel.php                        WHM API 1 sürücüsü
│   ├── Plesk.php                         Plesk XML-API sürücüsü
│   ├── Detector.php                      panel tespiti + önbellek
│   └── Exception.php
├── lang/
│   ├── en.php
│   └── tr.php
└── pages/
    ├── create-account-form-elements.php  ürün formu (paket seçimi)
    ├── order-detail.php                  admin sipariş detayı
    ├── clientArea-home.php               müşteri paneli
    ├── activation-html.php               aktivasyon e-postası (HTML)
    └── activation-text.php               aktivasyon e-postası (düz metin)
```

WiseCP'de modül içi otomatik yükleyici yoktur; `define_server_info()` içinden `include __DIR__ . DS . 'init.php'` yapılır (HestiaCP ve Plesk modüllerinin deseni).

Her `lib/` dosyası `defined("CORE_FOLDER") or exit;` ile korunur — WiseCP çekirdeğinin kendi deseni.

### 3.2 Bileşenler ve sorumluluklar

Her bileşen tek bir işten sorumludur ve arayüzü üzerinden bağımsız anlaşılabilir.

**`DNAHosting_Module`** — WiseCP'nin gördüğü tek yüzey. Hiçbir HTTP çağrısı yapmaz, hiçbir XML/JSON ayrıştırmaz. Görevi: aktif sürücüyü seçmek, sipariş verisini sürücünün anladığı argümanlara çevirmek, sürücünün fırlattığı istisnayı `$this->error` + `false` sözleşmesine dönüştürmek.

**`Detector`** — bir sunucu kaydı alır, `'cpanel'` veya `'plesk'` döner. Port yalnızca **sıralama ipucudur** (8443/8880 → önce Plesk, aksi halde önce cPanel); karar her zaman gerçek bir API probe'una dayanır. Sonucu (panel tipi + Plesk için kazanan kimlik doğrulama yöntemi + protokol sürümü) önbelleğe yazar.

**`Cpanel`** — WHM API 1 istemcisi. `Authorization: WHM user:token`. Okuma çağrıları GET, yazma çağrıları POST. `metadata.result` zarfını çözer; `cpanelresult` zarfı görürse bunu "WHM erişimi yok, cPanel kullanıcı API'sine düşülmüş" olarak yorumlar ve anlamlı bir hata verir.

**`Plesk`** — Plesk XML-API istemcisi. `/enterprise/control/agent.php`, `KEY:` veya basic auth. Yalnızca `<webspace>` operatörü kullanılır — `<domain>` operatörü Plesk 18.0.80+ tarafından 1014 ile reddedilir.

**`Http`** — cURL sarmalayıcı. Zaman aşımları, `CURLOPT_FOLLOWLOCATION = false` (token sızıntısını önlemek için), HTTP >= 400 durumunda gövde özeti çıkarma, ve `Modules::save_log()` ile maskelenmiş istek/yanıt loglaması.

### 3.3 Metot haritası

| WiseCP metodu | WHMCS karşılığı | Not |
|---|---|---|
| `define_server_info($server)` | — | panel tespiti + sürücü kurulumu |
| `testConnect()` | `TestConnection` | probe sonucunu önbelleğe yazar |
| `getPlans()` | `listPackages` / `listPlans` | aktif panelin paket listesi |
| `createAccount($domain,$opt)` | `CreateAccount` | kimlik bilgilerini kendisi üretir |
| `suspend()` / `unsuspend()` | `SuspendAccount` / `UnsuspendAccount` | |
| `removeAccount($user=false)` | `TerminateAccount` | mükerrer domain guard'ı dahil |
| `changePassword($old,$new)` | `ChangePassword` | |
| `change_plan($plan)` | `ChangePackage` | |
| `apply_updowngrade($orderopt,$product)` | `ChangePackage` sarmalayıcısı | `change_plan()`'a delege eder |
| `apply_options($old,$new)` | `AdminServicesTabFields` | admin sipariş düzenleme |
| `getDisk()` / `getBandwidth()` | `UsageUpdate` sürücüleri | tek servis, talep anında |
| `getSummary()` | — | **spekülatif:** çekirdekte çağrı yeri yok, görüntüleme yardımcısı olarak duruyor |
| `UsernameGenerator($domain)` | dahili | panele uygun kullanıcı adı |
| `use_method($param)` | — | buton yönlendirici |
| `use_clientArea_SingleSignOn()` | `ServiceSingleSignOn` | |
| `use_adminArea_SingleSignOn()` | `AdminSingleSignOn` | **müşterinin** paneline giriş — admin tarafından, o sipariş için |
| `clientArea()` | `ClientArea` | |
| `clientArea_buttons()` | `ClientAreaCustomButtonArray` | |

`suspend_reseller()`, `unsuspend_reseller()`, `removeReseller()` normal metotlara alias edilir. Biz reseller'ız, reseller satmıyoruz; ama `creation_info.reseller` yanlışlıkla dolarsa çekirdek bu çeşitleri çağırır ve tanımlı değillerse `method_exists` kontrolü sayesinde normalleri kullanır — yine de açıkça alias etmek niyeti belgeler ve `removeReseller` için `removeAccount`'a düşmeyi garanti eder.

`use_adminArea_root_SingleSignOn()` **tanımlanmaz.** Tanımlanırsa admin panelinde "root paneline giriş" butonu belirir; bizim elimizde root erişimi yok, buton yanıltıcı olur.

---

## 4. Veri modeli

### 4.1 Sunucu kaydı (admin tarafından girilen)

| Alan | İçerik |
|---|---|
| `ip` | Panel sunucusunun IP adresi veya hostname'i |
| `port` | 2087 (cPanel) / 8443 (Plesk) — sıralama ipucu olarak da kullanılır |
| `secure` | TLS |
| `username` | Reseller kullanıcı adı |
| `password` | API token **veya** panel şifresi (şifreli saklanır) |

### 4.2 Sipariş verisi (`orders.options`)

```
server_id                       çekirdek yazar
domain                          çekirdek yazar
config.user                     panel kullanıcı adı — cPanel user / Plesk webspace login
config.password                 Crypt::encode edilmiş
ftp_info.{ip,host,username,password,port}
creation_info.plan              ürün formundan gelen paket adı
creation_info.*                 ürün formundaki diğer alanlar
```

`config` altına kendi anahtarlarımızı ekleyemeyiz (bkz. §2.5). Plesk kimliği `external_id = "wisecp-" . $order["id"]` ile panelden yeniden türetilir.

### 4.3 Ürün verisi (`products.module_data`)

```
create_account.plan             paket / plan adı
```

Şimdilik tek alan. `module_data` isimli bir dizi olduğu için ileride alan eklemek serbesttir.

---

## 5. Akışlar

### 5.1 Sunucu ekleme / bağlantı testi

1. Admin sunucu formunu doldurur, kaydeder.
2. `config["server-info-checker"] = true` olduğu için çekirdek `testConnect()` çağırır (`products.php:2561`).
3. `Detector` port ipucuna göre sıralanmış listeyi dener:
   - **cPanel probe:** `listaccts` (veya `listpkgs`). `metadata.result == 1` → cPanel.
   - **Plesk probe:** `<server><get><gen_info/></get></server>` önce `KEY:` ile, kimlik hatasında basic auth ile. Yanıt `<packet>` ise → Plesk.
4. Kazanan panel + kimlik yöntemi + protokol sürümü önbelleğe yazılır.
5. İkisi de yanıt vermezse `$this->error` her iki denemenin de somut hatasını içerir (HTTP kodu, gövde özeti) ve `false` döner.

### 5.2 Hesap oluşturma

1. Çekirdek `set_order($order)` sonra `createAccount($domain, $options)` çağırır.
2. `force_setup = false` olduğu için kullanıcı adı ve şifre **bizde** üretilir:
   - Kullanıcı adı: `UsernameGenerator($domain)`, cPanel için 8 karakter sınırı ve rakamla başlamama kuralı; Plesk için daha gevşek.
   - Şifre: her iki panelin de politikasını geçecek şekilde (büyük/küçük harf, rakam, sembol).
   - `$options["username"]` / `$options["password"]` doluysa (admin elle girmişse) onlar kullanılır.
3. Paket adı `$options["creation_info"]["plan"]`'dan alınır, sürücünün `resolvePackage()` / `resolvePlan()` metoduyla panelin gerçek paket adına eşlenir (reseller ön eki temizlenir).
4. **cPanel:** `createacct`. Zaman aşımı riskine karşı, başarısızlıkta `accountSummary` ile kurtarma denenir — hesap gerçekte oluşmuşsa ve domain eşleşiyorsa başarı sayılır.
5. **Plesk:** akış **yeniden çalıştırılabilir** olacak şekilde yazılmıştır — her adım nesnenin zaten var olup olmadığına bakar, çünkü yarım kalan bir sağlama yeniden denendiğinde koşulsuz bir `customer.add` geride öksüz müşteri bırakır ve ikinci deneme mükerrer login ile patlar:
   1. Müşteri `external_id = "wisecp-" . $order["id"]` ile aranır (`findCustomer()`); yoksa `customer.add` ile oluşturulur, varsa **yeniden kullanılır**.
   2. Abonelik alan adıyla aranır. Varsa ve **sahibi bizim müşterimiz değilse işlem reddedilir** — devralmak, sonlandırma dâhil sonraki her işlemi bize ait olmayan canlı bir siteye doğrultur. Varsa ve sahibi bizimse devralınır (yarım kalan sağlamanın tamamlanması).
   3. Yoksa `webspace.add` gönderilir. IP adresi `<ip><get/>` ile alınır, **yalnızca `type='shared'` olanlar** aday kabul edilir; hiç bulunamazsa sunucunun `ip` alanına düşülür. Paket WHMCS 1.6.3.0 şablonuyla birebir aynı sırada gönderilir (`gen_setup` içinde `ip_address` zorunludur — çıkarılırsa 1014 alınır).
   4. `webspace.add` başarısız olursa kurtarma **dar tutulur**: yalnızca Plesk "zaten var" dediğinde ve isteğin Plesk'in API katmanından hiç yanıt almadığı durumda (zaman aşımı, taşıma hatası, HTTP ≥ 400, bozuk XML) abonelik aranıp devam edilir. Başka her hata yayılır — Plesk web sunucusunu yapılandırırken patladığında geride yarım bir abonelik bırakır, o durumda arama **başarılı** olur ve gerçek hata kaybolur; hosting'i olmayan bir abonelik için "başarılı" raporlamış oluruz.
6. Dönüş: `['username','password','ftp_info']`.

### 5.3 Askıya alma / kaldırma

- **cPanel:** `suspendacct` / `unsuspendacct`.
- **Plesk:** webspace'in `gen_setup.status` alanı. **Bit 32 (reseller askısı)** kullanılır; yalnızca kendi bitimizi set/clear ederiz, admin askısını (bit 16) ezmeyiz. Bkz. §8 — bu değer canlıda doğrulanmamıştır.

### 5.4 Sonlandırma

1. **Mükerrer domain guard'ı:** aynı sunucuda aynı domain'e sahip başka bir `active`/`suspended` sipariş varsa işlem reddedilir. Domain karşılaştırması normalize edilir (küçük harf, sondaki nokta atılır, UTF-8 farkındalıklı).
2. **cPanel:** `removeacct`.
3. **Plesk sahiplik guard'ı:** `pleskTargets()` aboneliği alan adıyla bulur ve sahibinin `external_id`'sini bizimkiyle karşılaştırır; tutmuyorsa işlem reddedilir — bu, elle oluşturulmuş veya başka bir sistemin yönettiği bir aboneliği silmeye karşı sigortadır (bkz. §2.5; aynı guard askıya alma, şifre değiştirme, paket değiştirme ve kullanım yollarını da korur).
4. **Plesk silme sırası:** önce **abonelik `id` ile silinir** (`webspace.del`; 1014'ten kaçınmak için `<domain>` değil `<webspace>` operatörü, `id` filtresi zorunlu — filtresiz bir silme sunucudaki her aboneliği kapsar). Sonra müşterinin **kalan abonelikleri sayılır** ve müşteri **yalnızca sıfırda** kaldırılır; müşteri silmek ona ait her aboneliği zincirleme sildiği için, panelden aynı müşteri altına eklenmiş ikinci bir site tek bir WiseCP hizmetinin sonlandırılmasıyla birlikte yok olurdu. **Sayım çözülemezse "boş değil" sayılır** ve müşteri bırakılır. Müşteri silinmeden hemen önce `external_id` bir kez daha doğrulanır.

### 5.5 Paket değiştirme

`apply_updowngrade()` yeni paketi **ürünün `module_data`'sından** okur ve `change_plan()`'a delege eder.

Kaynak seçimi kritiktir: çekirdek bu çağrıyı **eski** sipariş seçenekleriyle yapar (`$orderopt = $order["options"]`, `coremio/helpers/orders.php:3061-3076`) ve `options.creation_info`'yu yeni ürüne göre ancak modül **döndükten sonra** tazeler (`orders.php:254-255`). Dolayısıyla `creation_info` yükseltme anında hâlâ **eski** paketi taşır; oradan okumak her yükseltmede eski paketi yeniden uygular, panel "tamam" der, çekirdek yükseltmeyi başarılı sayar ve yeni fiyatı faturalar. Ürün tarafı boşsa `creation_info`'ya düşülür; **iki taraf da boşsa işlem başarılı sayılmaz**, hata döndürülür — aksi hâlde `change_plan('')`'ın masum `true`'su, hiçbir şey değiştirmeden başarı raporlayan bir yükseltmeye dönüşür.

- **cPanel:** `changepackage`. Başarısız olan override'lar (kota, trafik) dizi olarak toplanır ve hata mesajında raporlanır, sessizce yutulmaz.
- **Plesk:** `webspace.change_plan`, plan adı önce GUID'e çevrilir.

### 5.6 Kullanım

Müşteri servis sayfasını açar → `getBandwidth()` ve `getDisk()` çağrılır → aktif sürücüden tek hesabın verisi çekilir → bayt + formatlı metin + yüzde döndürülür. Sonuç kısa ömürlü (5 dakika) önbelleğe yazılır ki sayfa yenilemeleri panele yük bindirmesin.

### 5.7 SSO

- **Müşteri:** `use_clientArea_SingleSignOn()` → aktif sürücünün `createSession()` metodu → panel URL'ine yönlendirme.
  - cPanel: `create_user_session` (`service=cpaneld`), istemci IP'si geçirilir.
  - Plesk: `<session_setup>` operatörü, istemci IP'si geçirilir.
- **Admin:** `use_adminArea_SingleSignOn()` → aynı akış, **o siparişin müşterisinin** paneline.

  Bu, WHMCS'ten yanlış taşınmış bir gereksinimdi. WHMCS'te `AdminSingleSignOn` **sunucu düzeyinde** bir hook'tur ve bağlı bir hizmet yoktur, dolayısıyla reseller'ın kendi paneline açılır. WiseCP'de ise **sipariş düzeyinde** bir butondur: `Modules::hosting_use_method()` belirli bir sipariş üzerinden çağırır (`coremio/classes/Modules.php:1024`) ve WiseCP'nin kendi cPanel modülü de bu butondan müşterinin panelini açar (`coremio/modules/Servers/cPanel/cPanel.php:1157-1168`). Uygulama baştan doğruydu; yanlış olan bu belgeydi. Yanlış taşınan bu gereksinim, `createSession()` içinde hiçbir zaman erişilemeyen bir `whostmgrd` port dalı bırakmıştı; o dal kaldırıldı.
- İstemci IP'si WiseCP'nin kendi yardımcılarından alınır; WHMCS'teki `CurrentUser::getIP()` karşılığı uygulama sırasında doğrulanacaktır.

---

### 5.8 Sağlama sonrası alan adı değişikliği

`update_hosting()` admin'in sipariş formundan `domain` alanını değiştirmesine izin verir ve yeni değeri `$set_options` içine katar (`coremio/controllers/admin/orders.php:4338`). Bunun bir panel karşılığı yoktur ve iki panelde sonucu farklıdır:

- **cPanel:** hesap eski alan adını sunmaya devam eder, WiseCP yenisini gösterir. Kozmetik olarak yanlıştır ve panelden düzeltilebilir. **Engellenmez.**
- **Plesk:** kimlik alan adından yeniden türetildiği için (§2.5) `pleskTargets()` yeni adı sonsuza kadar arar ve bulamaz. Askıya alma, askıdan indirme, şifre değiştirme, paket değiştirme, kullanım **ve sonlandırma** kalıcı olarak başarısız olur; abonelik modül üzerinden silinemez ve bayi kotasını yemeye devam eder. **`apply_options()` bu değişikliği reddeder** ve mesajda eski ile yeni alan adını anıp iki çıkış yolunu söyler: önce Plesk'te aboneliği yeniden adlandırmak, ya da hizmeti sonlandırıp yeniden oluşturmak.

Çekirdeğin kendi modülleri bu duruma bir `modifyDomain()` çağrısıyla tepki verir; `DNAHosting_Module` böyle bir metot tanımlamaz — bir bayi hesabının Plesk'te aboneliği yeniden adlandırma yetkisi olduğu doğrulanmadı ve sessizce kabul etmek yukarıdaki kilitlenmeyi üretir.

### 5.9 Alan adı normalizasyonu (IDN)

Çekirdek `createAccount()` çağrısına domaini **punycode'a çevirerek** verir (`coremio/helpers/orders.php:2838-2840`: `idn_to_ascii($orderopt["domain"], 0, INTL_IDNA_VARIANT_UTS46)`) ama sonucu `$orderopt["domain"]` içine **geri yazmaz**. Yani abonelik panelde `xn--…` adıyla açılırken sonraki her işlem `options.domain`'i Unicode hâliyle okur.

Bu yüzden `DNAHosting_Support::domainKey()` yalnızca kırpma ve küçük harfe çevirme yapmaz, ASCII dışı adları `idn_to_ascii()` ile **punycode'a da çevirir** (`function_exists` ile korunmuş; zaten ASCII olan adlar dokunulmadan geçer). Aksi hâlde `findWebspace()` bir IDN siparişini hiç bulamaz ve o hizmetin Plesk yaşam döngüsünün tamamı — sonlandırma dâhil — kalıcı olarak kırılır. cPanel etkilenmez; o `config.user` üzerinden çalışır.

## 6. Hata yönetimi ve loglama

**Sözleşme:** sürücüler istisna fırlatır, `DNAHosting_Module` yakalar, `$this->error`'a insan tarafından okunabilir bir mesaj yazar ve `false` döner. Sessiz yutma yoktur — WHMCS portunda en çok zaman kaybettiren şey buydu.

**Loglama:** her API çağrısı `Modules::save_log("Servers", "DNAHosting", $action, $request, $response, $processed)` ile kayda geçer. Çekirdek dizileri `Utility::jencode()` ile serileştirir (`Modules.php:229-231`), yani diziyi olduğu gibi vermek doğrudur.

**Maskeleme:** token, şifre ve session URL'leri loga yazılmadan önce maskelenir. Bunun **iki** mekanizması var ve ikisi de gerekli:

- `DNAHosting_Http::addSecret()` — değeri **önceden bilinen** sırlar için (sunucu token'ı/şifresi, üretilen hesap şifresi). Kayıttan sonraki her satırda maskelenir.
- `DNAHosting_Http::addResponseRedaction($action, $patterns)` — değeri **yanıtın kendisinde** gelen sırlar için. SSO oturum açma çağrıları (`create_user_session`, `server.create_session`) böyledir: çağıran değeri ancak o satır **loglandıktan sonra** öğrenir, yani `addSecret()` tek başına jetonu taşıyan satırı kurtaramaz. Desen, değer bilinmeden önce eylem adına kaydedilir.

Redaksiyonun kapsamı bilerek **eylem adıdır**, tek atımlık bir bayrak değil: Plesk `request()` kimlik hatasında ikinci bir istek atar ve tek atımlık bir bayrak orada tükenip asıl yanıtı maskesiz bırakırdı. Redaksiyon yalnızca **log ve hata kopyasına** uygulanır — çağırana ham gövde döner — ve gövdenin tamamını değil yalnızca kimlik alanlarını gizler, böylece aşağıdaki teşhis kalitesi şartı korunur.

**Hata mesajı kalitesi** — WHMCS portunda öğrenilen dersler doğrudan taşınır:

- HTTP >= 400'de gövde **mutlaka** özetlenir; "HTTP 403" tek başına teşhis edilemez.
- cPanel `cpanelresult` zarfı → "reseller'ın WHM erişimi yok, ACL kontrol edin" mesajına çevrilir.
- Plesk 11003 → "anahtar başka bir IP için üretilmiş" mesajına çevrilir.
- Plesk 1014 → istek gövdesinin hangi elemanının reddedildiği mesaja dahil edilir.

---

## 7. WHMCS'ten taşınmayanlar

| Ne | Neden |
|---|---|
| `dnahosting_UsageUpdate` toplu senkronu | WiseCP kullanımı talep anında tek servis için çeker; toplu cron kavramı yok |
| `dnahosting_servicesOnServer`, `dnahosting_writeUsage` | yukarıdakinin parçaları |
| `hooks.php` (Access Hash etiket yaması) | `access-hash` alanını hiç açmıyoruz |
| `whmcs.json` | WiseCP karşılığı yok |
| `templates/*.tpl` (Smarty) | WiseCP `pages/*.php` kullanır |
| `lib/Cache.php` (`tblconfiguration` üzerinden) | WiseCP'nin `Cache` sınıfı var |
| Panel Type ayar alanı (`configoption1`) | ürün formu somut bir sunucuyla açılıyor; tespit gerçek probe |
| Disk Quota / Bandwidth / Dedicated IP ayar alanları | pakete bırakılıyor; WHMCS'te bunlar cPanel'e özgüydü ve Plesk tarafında ölü alanlardı |

---

## 8. Riskler ve canlı doğrulama listesi

Bu maddeler tasarımla kapatılamaz; canlı sunucuda doğrulanmaları gerekir.

| # | Risk | Doğrulama |
|---|---|---|
| 1 | **Plesk askı bitmask'ı.** Bizim WHMCS modülümüz 32 (reseller askısı) kullanıyor; WiseCP'nin kendi Plesk modülü root olmayan sahip için 1 kullanıyor (`Plesk.php:1227`). **Hiçbiri canlıda doğrulanmadı** — WHMCS tarafında suspend/unsuspend hiç çalıştırılmadı. 32 ile gidiyoruz. | Bir test hesabını askıya al, panelde durumu ve sitenin gerçekten kapandığını gör, askıyı kaldır, geri döndüğünü gör |
| 2 | ~~`Cache::retrieve()` gerçekten veri döndürüyor mu~~ **Kapandı — doğrulanmış olumsuz.** `Cache::retrieve()` (`coremio/classes/Cache.php:80-91`) `$timestamp === false` dalında `$type = "data"` atıyor ve fonksiyonun sonundan **hiçbir şey döndürmeden** düşüyor, yani her zaman `null` veriyor. Bu belirsiz decoder çıktısı değil: dalda `return` ifadesi yok. Modül doğru davranıyor — istek başına tam olarak bir fazladan probe, tasarlandığı gibi — ama **her `cacheWrite()` boşa giden I/O.** Birileri buna dönmek isterse: `retrieveAll()` (`Cache.php:95-108`) doğru unserialize ediyor ve `isCached($key)` üzerinden okunabilir, ancak `isCached()` **son kullanma tarihini kontrol etmiyor**, dolayısıyla çağıranın kendi TTL kontrolünü yazması gerekir. | ~~Uygulamanın ilk adımı~~ Doğrulandı |
| 3 | ~~İstemci IP'sini almanın WiseCP'deki doğru yolu~~ **Kapandı.** `UserManager::GetIP()` (`coremio/classes/UserManager.php:388`) doğru yöntem; `DNAHosting.php::clientIp()` bunu kullanıyor ve `IP` doğrulamasından geçmeyen sonuçları eler. | ~~SSO uygulanırken doğrulanır~~ Doğrulandı |
| 4 | `getPlans()` dönüş şeklinin ürün formunda beklenen anahtarlarla uyumu | Form render edilerek görülür |
| 5 | Terminate, SSO ve kullanım yolları **WHMCS tarafında da hiç canlı çalıştırılmadı** | İki modül için de aynı canlı test turu gerekir |
| 6 | **Ayrıştırma hatası yolunda redaksiyon atlanıyor.** `Plesk::attempt()` ve `Cpanel::unwrap()`, gövde ayrıştırılamadığında hata mesajlarını **ham** gövdeden kuruyor (`summarise($response['body'])`) — yani `Http`'nin yanıt redaksiyonunu baypas ediyorlar. `openPanel()` de `$this->error`'u müşterinin tarayıcısına basıyor. Dar bir yol: ayrıştırıcının reddettiği **ve** ilk 300 karakterinde jetonu taşıyan bir SSO yanıtı gerekiyor. Final incelemede kapsam dışı olarak işaretlendi, merge'i engellemedi. | Temiz çözüm: `Http::send()` redakte edilmiş kopyayı da döndürsün, iki sürücü de özeti **ondan** çıkarsın |

---

## 9. Test planı

**Statik:** PHP 7.4 sözdizimi denetimi (WiseCP'nin decode hedefi), PHP 8.x deprecation taraması, tüm `lib/` dosyalarında `defined("CORE_FOLDER")` guard'ı, WiseCP'ye dönen dizilerde closure bulunmaması.

**Entegrasyon (canlı, her iki panelde de):**

1. Sunucu ekle → bağlantı testi yeşil, doğru panel tespit edildi
2. Ürün oluştur → paket listesi doğru panelden geldi
3. Sipariş → hesap açıldı, aktivasyon e-postası doğru bilgileri taşıyor, FTP bilgileri çalışıyor
4. Müşteri paneli → disk/trafik gösteriliyor, SSO panele giriyor
5. Admin → SSO reseller paneline giriyor
6. Askıya al → panelde askıya alındı ve site kapandı; askıyı kaldır → geri döndü
7. Paket değiştir → limitler panelde güncellendi
8. Şifre değiştir → yeni şifreyle panele girilebiliyor
9. Sonlandır → hesap silindi; **mükerrer domain guard'ı testi:** aynı domainle ikinci bir sipariş varken sonlandırma reddediliyor
10. **Plesk sahiplik guard'ı testi:** panelde elle oluşturulmuş bir aboneliği modül üzerinden silmeye çalış → reddedilmeli

**Loglama:** her adımdan sonra WiseCP modül logunda istek ve yanıtın okunabilir biçimde yazıldığı, token ve şifrelerin maskelendiği doğrulanır.

---

## 10. Kapsam dışı

- E-posta hesabı ve yönlendirme yönetimi
- Reseller hesabı satışı (`setupReseller`, `removeReseller`'ın gerçek uygulaması)
- DNS bölge yönetimi
- SSL sipariş entegrasyonu
- Çok dilli README (WHMCS projesindeki 9 dil buraya sonraki bir turda taşınabilir)
