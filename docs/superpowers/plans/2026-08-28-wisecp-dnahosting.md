# DNAHosting WiseCP Sunucu Modülü — Uygulama Planı

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tek bir WiseCP sunucu modülü ile hem cPanel/WHM hem Plesk reseller hesapları üzerinden paylaşımlı hosting satmak.

**Architecture:** `lib/` altındaki dört sınıf (Http, Cpanel, Plesk, Detector) WiseCP çekirdeğine **hiç bağımlı değildir** — bağımlılıklar (HTTP taşıyıcısı, logger, önbellek) dışarıdan enjekte edilir. Bu sayede tüm protokol mantığı canlı panel olmadan, sahte taşıyıcıyla test edilebilir. WiseCP'ye özgü her şey (`Modules::save_log`, `Crypt`, `Cache`, `Models::$init->db`) yalnızca `DNAHosting.php` ve `pages/` içinde kalır.

**Tech Stack:** PHP (7.2 uyumlu sözdizimi, PHP 8.4'e kadar deprecation'sız), cURL, SimpleXML, WiseCP `ServerModule` temel sınıfı. Test için harici bağımlılık yok — repo içinde ~70 satırlık koşucu.

**Spec:** `docs/superpowers/specs/2026-08-28-wisecp-dnahosting-design.md`

## Global Constraints

- **Sınıf/klasör adı `DNAHosting`.** Klasör adı = sınıf öneki = `servers.type` = admin dropdown etiketi. Değiştirilemez.
- **`public $force_setup = false;`** — çekirdeğin şifre üreticisi `$server["type"] == "Plesk"` diye sabit karşılaştırma yapar (`coremio/helpers/orders.php:2785`); bizim tipimiz eşleşmediği için kimlik bilgilerini kendimiz üretiriz.
- **Tüm global sınıf adları `DNAHosting_` ön ekli.** WiseCP'de modül içi otomatik yükleyici ve ad alanı yoktur; çakışma riski gerçektir.
- **Her `lib/` dosyası `defined("CORE_FOLDER") or exit("You can not get in here!");` ile başlar.**
- **PHP 7.2 uyumlu sözdizimi:** ad alanı yok, tipli özellik yok, ok fonksiyonu yok, `match` yok, kurucu özellik yükseltmesi yok, sondaki virgül parametre listesinde yok.
- **Kimlik bilgisi tek alan:** `servers.password`. `access-hash` bayrağı `config.php`'de **açılmaz**.
- **`createAccount()` yalnızca `['username','password','ftp_info']` döndürebilir** — başka anahtar kalıcılaşmaz (`coremio/helpers/orders.php:2847-2853`). Plesk kimliği `external_id = "wisecp-" . $order["id"]` ile yeniden türetilir.
- **Hata sözleşmesi:** sürücüler `DNAHosting_Exception` fırlatır; `DNAHosting_Module` yakalar, `$this->error`'a mesaj yazar, `false` döner. Sessiz yutma yasak.
- **Önbellek saf optimizasyondur.** `Cache::store()`/`isCached()` lisans alan adını kontrol eder ve eşleşmezse sessizce ıskalar. Modül önbellek hiç çalışmasa da doğru davranmalıdır.
- **Plesk yalnızca `<webspace>` operatörünü kullanır.** `<domain>` operatörü Plesk 18.0.80+ tarafından 1014 ile reddedilir.

## Dosya Yapısı

| Dosya | Sorumluluk |
|---|---|
| `coremio/modules/Servers/DNAHosting/DNAHosting.php` | WiseCP yüzeyi. Sürücü seçer, sipariş verisini argümana çevirir, istisnayı `error`+`false`'a dönüştürür. HTTP çağrısı yapmaz, protokol ayrıştırmaz. |
| `coremio/modules/Servers/DNAHosting/config.php` | `type`, port varsayılanları, `supported[]` |
| `coremio/modules/Servers/DNAHosting/init.php` | `lib/` include'ları |
| `coremio/modules/Servers/DNAHosting/lib/Exception.php` | `DNAHosting_Exception` |
| `coremio/modules/Servers/DNAHosting/lib/Http.php` | cURL sarmalayıcı: zaman aşımı, yönlendirme kapalı, gövde özeti, maskeleme, log kancası |
| `coremio/modules/Servers/DNAHosting/lib/Cpanel.php` | WHM API 1 sürücüsü |
| `coremio/modules/Servers/DNAHosting/lib/Plesk.php` | Plesk XML-API sürücüsü |
| `coremio/modules/Servers/DNAHosting/lib/Detector.php` | Panel tespiti + önbellek kancası |
| `coremio/modules/Servers/DNAHosting/lang/{en,tr}.php` | Dil dizeleri |
| `coremio/modules/Servers/DNAHosting/pages/*.php` | Ürün formu, sipariş detayı, müşteri paneli, aktivasyon şablonları |
| `tests/bootstrap.php` | Sabitler + `lib/` include'ları (çekirdek stub'ı gerektirmez) |
| `tests/run.php` | Test koşucusu |
| `tests/support/FakeTransport.php` | Sahte HTTP taşıyıcısı |
| `tests/cases/*.php` | Test dosyaları |

---

### Task 1: Test koşucusu ve sahte taşıyıcı

**Files:**
- Create: `tests/run.php`
- Create: `tests/bootstrap.php`
- Create: `tests/support/FakeTransport.php`
- Create: `tests/cases/SmokeTest.php`
- Create: `coremio/modules/Servers/DNAHosting/lib/Exception.php`
- Create: `coremio/modules/Servers/DNAHosting/index.html`

**Interfaces:**
- Consumes: yok (ilk görev)
- Produces: `test($name, callable $fn)`, `assertSame($expected, $actual, $msg = '')`, `assertTrue($cond, $msg = '')`, `assertContains($needle, $haystack, $msg = '')`, `assertThrows(callable $fn, $expectedMessageSubstring, $msg = '')` global fonksiyonları. `DNAHosting_FakeTransport` sınıfı: `push($status, $body)`, `pushError($curlError)`, `__invoke($method, $url, $headers, $body, $timeout)`, public `$calls` dizisi. `DNAHosting_Exception` sınıfı.

- [ ] **Step 1: Sahte taşıyıcıyı ve koşucuyu yaz**

`tests/support/FakeTransport.php`:

```php
<?php
class DNAHosting_FakeTransport
{
    public $calls = array();
    private $queue = array();

    public function push($status, $body)
    {
        $this->queue[] = array('status' => $status, 'body' => $body, 'error' => '');
        return $this;
    }

    public function pushError($curlError)
    {
        $this->queue[] = array('status' => 0, 'body' => '', 'error' => $curlError);
        return $this;
    }

    public function __invoke($method, $url, $headers, $body, $timeout)
    {
        $this->calls[] = array(
            'method'  => $method,
            'url'     => $url,
            'headers' => $headers,
            'body'    => $body,
            'timeout' => $timeout,
        );
        if (!$this->queue) {
            return array('status' => 0, 'body' => '', 'error' => 'FakeTransport: kuyruk bos');
        }
        return array_shift($this->queue);
    }

    public function lastCall()
    {
        return $this->calls ? $this->calls[count($this->calls) - 1] : null;
    }
}
```

`tests/bootstrap.php`:

```php
<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('CORE_FOLDER')) {
    define('CORE_FOLDER', 'coremio');
}

$moduleDir = dirname(__DIR__) . '/coremio/modules/Servers/DNAHosting/';

require_once __DIR__ . '/support/FakeTransport.php';
require_once $moduleDir . 'lib/Exception.php';
```

`tests/run.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';

$GLOBALS['dna_pass']   = 0;
$GLOBALS['dna_fail']   = 0;
$GLOBALS['dna_errors'] = array();
$GLOBALS['dna_test']   = '';

function test($name, callable $fn)
{
    $GLOBALS['dna_test'] = $name;
    try {
        $fn();
        $GLOBALS['dna_pass']++;
        echo ".";
    } catch (Exception $e) {
        $GLOBALS['dna_fail']++;
        $GLOBALS['dna_errors'][] = $name . "\n    " . $e->getMessage();
        echo "F";
    }
    if (($GLOBALS['dna_pass'] + $GLOBALS['dna_fail']) % 60 === 0) {
        echo "\n";
    }
}

function dna_fail_with($msg)
{
    throw new Exception($msg);
}

function assertSame($expected, $actual, $msg = '')
{
    if ($expected !== $actual) {
        dna_fail_with(($msg ? $msg . ' — ' : '') . 'beklenen ' . var_export($expected, true)
            . ', gelen ' . var_export($actual, true));
    }
}

function assertTrue($cond, $msg = '')
{
    if ($cond !== true) {
        dna_fail_with(($msg ? $msg . ' — ' : '') . 'true bekleniyordu, gelen ' . var_export($cond, true));
    }
}

function assertContains($needle, $haystack, $msg = '')
{
    if (strpos((string) $haystack, (string) $needle) === false) {
        dna_fail_with(($msg ? $msg . ' — ' : '') . var_export($needle, true)
            . ' bulunamadi. Gelen: ' . var_export($haystack, true));
    }
}

function assertThrows(callable $fn, $expectedMessageSubstring, $msg = '')
{
    try {
        $fn();
    } catch (Exception $e) {
        assertContains($expectedMessageSubstring, $e->getMessage(), $msg);
        return $e;
    }
    dna_fail_with(($msg ? $msg . ' — ' : '') . 'istisna bekleniyordu, firlatilmadi');
}

foreach (glob(__DIR__ . '/cases/*.php') as $case) {
    require $case;
}

echo "\n\n";
foreach ($GLOBALS['dna_errors'] as $err) {
    echo "FAIL: " . $err . "\n";
}
echo $GLOBALS['dna_pass'] . " gecti, " . $GLOBALS['dna_fail'] . " kaldi\n";
exit($GLOBALS['dna_fail'] > 0 ? 1 : 0);
```

- [ ] **Step 2: İlk testi yaz (henüz geçmemeli)**

`tests/cases/SmokeTest.php`:

```php
<?php
test('DNAHosting_Exception Exception turevidir', function () {
    $e = new DNAHosting_Exception('deneme');
    assertTrue($e instanceof Exception);
    assertSame('deneme', $e->getMessage());
});

test('FakeTransport kuyrugu sirayla dondurur', function () {
    $t = new DNAHosting_FakeTransport();
    $t->push(200, 'ilk')->push(500, 'ikinci');
    $a = $t('GET', 'http://x/1', array(), null, 30);
    $b = $t('POST', 'http://x/2', array('A: b'), 'govde', 30);
    assertSame('ilk', $a['body']);
    assertSame(500, $b['status']);
    assertSame(2, count($t->calls));
    assertSame('POST', $t->lastCall()['method']);
});
```

- [ ] **Step 3: Testi çalıştır, hata verdiğini gör**

```bash
php tests/run.php
```

Beklenen: `Failed opening required '.../lib/Exception.php'` — dosya henüz yok.

- [ ] **Step 4: Exception sınıfını ve dizin korumasını yaz**

`coremio/modules/Servers/DNAHosting/lib/Exception.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Exception extends Exception
{
}
```

`coremio/modules/Servers/DNAHosting/index.html` — WiseCP'nin dizin listeleme koruması deseni:

```html
<html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1></body></html>
```

Aynı `index.html` dosyası daha sonra `lib/`, `lang/` ve `pages/` altına da kopyalanacak.

- [ ] **Step 5: Testi çalıştır, geçtiğini gör**

```bash
php tests/run.php
```

Beklenen: `2 gecti, 0 kaldi`

- [ ] **Step 6: Commit**

```bash
git add tests coremio/modules/Servers/DNAHosting
git commit -m "test: bagimliliksiz test kosucusu ve sahte HTTP tasiyicisi"
```

---

### Task 2: `DNAHosting_Http` — taşıma katmanı

WHMCS modülünde bu katman üç kez ısırdı: gövde gösterilmeyen 403'ler, token sızdıran yönlendirmeler, ve `logModuleCall`'un beşinci parametresine dizi geçince yanıt sütununun `Array ( )` görünmesi. Üçü de burada kökten kapatılıyor.

**Files:**
- Create: `coremio/modules/Servers/DNAHosting/lib/Http.php`
- Create: `tests/cases/HttpTest.php`
- Modify: `tests/bootstrap.php` (Http include'u ekle)

**Interfaces:**
- Consumes: `DNAHosting_Exception` (Task 1)
- Produces: `DNAHosting_Http` sınıfı.
  - `__construct($baseUrl)` — `$baseUrl` şema+host+port, sonda eğik çizgi olmadan
  - `setTransport(callable $t)` — `$t($method, $url, $headers, $body, $timeout)` → `['status'=>int,'body'=>string,'error'=>string]`
  - `setLogger(callable $l)` — `$l($action, $request, $response)`, dönüş yok
  - `setTimeout($seconds)` — akıcı
  - `addSecret($value)` — maskelenecek gizli dize kaydeder
  - `send($method, $path, array $headers, $body, $action)` → `['status'=>int,'body'=>string]`; ağ hatasında veya HTTP >= 400'de `DNAHosting_Exception` fırlatır
  - `mask($text)` → gizli diziler `***` ile değiştirilmiş metin
  - `static summarise($body, $limit = 300)` → HTML etiketleri temizlenmiş, tek satıra indirilmiş, `$limit` karaktere kırpılmış özet

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/HttpTest.php`:

```php
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

test('summarise HTMLi temizler ve kirpar', function () {
    $out = DNAHosting_Http::summarise('<html>  <b>Bir</b>   <i>iki</i>  </html>', 300);
    assertSame('Bir iki', $out);
    $long = DNAHosting_Http::summarise(str_repeat('x', 500), 10);
    assertSame(13, strlen($long), 'kirpilan metin ... ile bitmeli');
    assertContains('...', $long);
});
```

`tests/bootstrap.php` sonuna ekle:

```php
require_once $moduleDir . 'lib/Http.php';
```

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Failed opening required '.../lib/Http.php'`

- [ ] **Step 3: `Http` sınıfını yaz**

`coremio/modules/Servers/DNAHosting/lib/Http.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Http
{
    private $base;
    private $timeout   = 30;
    private $transport = null;
    private $logger    = null;
    private $secrets   = array();

    public function __construct($baseUrl)
    {
        $this->base = rtrim($baseUrl, '/');
    }

    public function setTransport(callable $transport)
    {
        $this->transport = $transport;
        return $this;
    }

    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setTimeout($seconds)
    {
        $this->timeout = (int) $seconds;
        return $this;
    }

    public function addSecret($value)
    {
        $value = (string) $value;
        if (strlen($value) >= 4) {
            $this->secrets[] = $value;
        }
        return $this;
    }

    public function mask($text)
    {
        $text = (string) $text;
        foreach ($this->secrets as $secret) {
            $text = str_replace($secret, '***', $text);
        }
        return $text;
    }

    public function send($method, $path, array $headers, $body, $action)
    {
        $url       = $this->base . $path;
        $transport = $this->transport ? $this->transport : array($this, 'curl');
        $result    = call_user_func($transport, $method, $url, $headers, $body, $this->timeout);

        $logRequest = $this->mask($method . ' ' . $url
            . ($headers ? "\n" . implode("\n", $headers) : '')
            . ($body !== null && $body !== '' ? "\n\n" . $this->stringify($body) : ''));

        if (!empty($result['error'])) {
            $this->log($action, $logRequest, 'TASIMA HATASI: ' . $result['error']);
            throw new DNAHosting_Exception($result['error']);
        }

        $status = (int) $result['status'];
        $rbody  = (string) $result['body'];
        $this->log($action, $logRequest, $this->mask('HTTP ' . $status . "\n\n" . $rbody));

        if ($status >= 400) {
            $summary = self::summarise($rbody);
            throw new DNAHosting_Exception('HTTP ' . $status . ($summary ? ': ' . $summary : ''));
        }

        return array('status' => $status, 'body' => $rbody);
    }

    public static function summarise($body, $limit = 300)
    {
        $text = strip_tags((string) $body);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($text === '') {
            return '';
        }
        if (strlen($text) > $limit) {
            $text = substr($text, 0, $limit) . '...';
        }
        return $text;
    }

    private function stringify($body)
    {
        return is_array($body) ? http_build_query($body) : (string) $body;
    }

    private function log($action, $request, $response)
    {
        if ($this->logger) {
            call_user_func($this->logger, $action, $request, $response);
        }
    }

    private function curl($method, $url, $headers, $body, $timeout)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->stringify($body));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->stringify($body));
            }
        }
        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return array(
            'status' => $status,
            'body'   => $response === false ? '' : $response,
            'error'  => $error,
        );
    }
}
```

`CURLOPT_FOLLOWLOCATION = false` zorunlu: yönlendirme takip edilirse `Authorization` başlığı üçüncü bir hosta gider.

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `11 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Http.php tests
git commit -m "feat: HTTP tasima katmani — govde ozeti, maskeleme, yonlendirme kapali"
```

---

### Task 3: `DNAHosting_Cpanel` — çağrı katmanı, bağlantı testi, paketler

WHMCS portunda burada üç ayrı hata yaşandı: `cpanelresult` zarfı tanınmıyordu (reseller'ın WHM erişimi yokken anlamsız hata çıkıyordu), `myprivs` yanıtında `data.privileges[0]` yerine `data.privileges` okunuyordu (tüm ACL'ler eksik görünüyordu), ve okuma çağrıları POST ile gidiyordu. Üçü de testle sabitleniyor.

**Files:**
- Create: `coremio/modules/Servers/DNAHosting/lib/Cpanel.php`
- Create: `tests/cases/CpanelTest.php`
- Modify: `tests/bootstrap.php`

**Interfaces:**
- Consumes: `DNAHosting_Http`, `DNAHosting_Exception`
- Produces: `DNAHosting_Cpanel`
  - `__construct(array $server, DNAHosting_Http $http)` — `$server` anahtarları: `ip`, `port`, `secure`, `username`, `password`
  - `call($function, array $args = array())` → `array` (yanıtın `data` bölümü, yoksa tüm zarf); hata durumunda `DNAHosting_Exception`
  - `testConnection()` → `true` veya fırlatır
  - `listPackages()` → `array` — her eleman `array('name'=>string,'quota'=>string,'bwlimit'=>string)`
  - `dePrefix($name)` → `string` — `reseller_paket` → `paket`
  - `resolvePackage($configured)` → `string` — panelin tanıdığı gerçek paket adı; bulunamazsa fırlatır
- Kullanılan sabit: okuma çağrıları beyaz listesi `listaccts`, `listpkgs`, `accountsummary`, `myprivs`, `showbw`, `version`

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/CpanelTest.php`:

```php
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
    return array(new DNAHosting_Cpanel($server, $http), $t);
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
    $t->push(200, '{"metadata":{"result":1},"data":{}}');
    $c->call('createacct', array('username' => 'x'));
    assertSame('POST', $t->lastCall()['method']);
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
```

`tests/bootstrap.php` sonuna: `require_once $moduleDir . 'lib/Cpanel.php';`

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Failed opening required '.../lib/Cpanel.php'`

- [ ] **Step 3: `Cpanel` sınıfının bu bölümünü yaz**

`coremio/modules/Servers/DNAHosting/lib/Cpanel.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Cpanel
{
    private $server;
    private $http;
    private $packages = null;

    private static $readOnly = array(
        'listaccts', 'listpkgs', 'accountsummary', 'myprivs', 'showbw', 'version',
    );

    public function __construct(array $server, DNAHosting_Http $http)
    {
        $this->server = $server;
        $this->http   = $http;
        $this->http->addSecret($server['password']);
    }

    public function call($function, array $args = array())
    {
        $read    = in_array($function, self::$readOnly, true);
        $args    = array_merge(array('api.version' => 1), $args);
        $query   = http_build_query($args);
        $headers = array(
            'Authorization: WHM ' . $this->server['username'] . ':' . $this->server['password'],
        );

        $this->http->setTimeout($read ? 30 : 400);

        if ($read) {
            $result = $this->http->send('GET', '/json-api/' . $function . '?' . $query, $headers, null, $function);
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $result = $this->http->send('POST', '/json-api/' . $function, $headers, $query, $function);
        }

        return $this->unwrap($result['body'], $function);
    }

    private function unwrap($body, $function)
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new DNAHosting_Exception(
                $function . ': sunucu gecerli JSON dondurmedi. '
                . DNAHosting_Http::summarise($body)
            );
        }

        if (isset($decoded['cpanelresult'])) {
            $inner = $decoded['cpanelresult'];
            $why   = isset($inner['error']) ? $inner['error'] : 'bilinmeyen hata';
            throw new DNAHosting_Exception(
                'Sunucu WHM yerine cPanel kullanici APIsi ile yanit verdi — bu kullanicinin WHM erisimi yok. '
                . 'WHM > Reseller Center uzerinden ACL yetkilerini kontrol edin. Panelin dedigi: ' . $why
            );
        }

        if (isset($decoded['metadata']['result']) && (int) $decoded['metadata']['result'] !== 1) {
            $why = isset($decoded['metadata']['reason']) ? $decoded['metadata']['reason'] : 'sebep bildirilmedi';
            throw new DNAHosting_Exception($why);
        }

        return isset($decoded['data']) ? $decoded['data'] : $decoded;
    }

    public function testConnection()
    {
        $this->call('listaccts', array('want' => 'domain'));
        return true;
    }

    public function listPackages()
    {
        if ($this->packages !== null) {
            return $this->packages;
        }
        $data = $this->call('listpkgs');
        $list = array();
        $rows = isset($data['pkg']) ? $data['pkg'] : array();
        foreach ($rows as $row) {
            if (!isset($row['name'])) {
                continue;
            }
            $list[] = array(
                'name'    => (string) $row['name'],
                'quota'   => isset($row['QUOTA']) ? (string) $row['QUOTA'] : '',
                'bwlimit' => isset($row['BWLIMIT']) ? (string) $row['BWLIMIT'] : '',
            );
        }
        $this->packages = $list;
        return $list;
    }

    public function dePrefix($name)
    {
        $name = (string) $name;
        $at   = strpos($name, '_');
        return $at === false ? $name : substr($name, $at + 1);
    }

    public function resolvePackage($configured)
    {
        $configured = trim((string) $configured);
        if ($configured === '') {
            throw new DNAHosting_Exception('Urun icin bir paket secilmemis.');
        }

        $packages = $this->listPackages();
        $names    = array();
        foreach ($packages as $package) {
            $names[] = $package['name'];
            if (strcasecmp($package['name'], $configured) === 0) {
                return $package['name'];
            }
        }
        foreach ($packages as $package) {
            if (strcasecmp($this->dePrefix($package['name']), $configured) === 0) {
                return $package['name'];
            }
        }

        throw new DNAHosting_Exception(
            '"' . $configured . '" paketi sunucuda bulunamadi. Mevcut paketler: '
            . ($names ? implode(', ', $names) : '(hic paket yok)')
        );
    }
}
```

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `22 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Cpanel.php tests
git commit -m "feat: cPanel cagri katmani, baglanti testi ve paket cozumleme"
```

---

### Task 4: `DNAHosting_Cpanel` — hesap yaşam döngüsü, kullanım, SSO

`createacct` uzun sürer ve zaman aşımına uğrayabilir; hesap gerçekte açılmış olabilir. WHMCS portunda bunun için `accountsummary` ile kurtarma eklenmişti — domain eşleşiyorsa başarı sayılıyor. Aynı davranış burada da test altına alınıyor.

**Files:**
- Modify: `coremio/modules/Servers/DNAHosting/lib/Cpanel.php`
- Modify: `tests/cases/CpanelTest.php`

**Interfaces:**
- Consumes: Task 3'ün `call()`, `resolvePackage()`
- Produces: `DNAHosting_Cpanel` üzerinde
  - `createAccount(array $a)` → `array('username'=>string,'password'=>string)`. `$a` anahtarları: `username`, `password`, `domain`, `plan`, `email`
  - `suspendAccount($username, $reason = '')` → `true`
  - `unsuspendAccount($username)` → `true`
  - `terminateAccount($username)` → `true`
  - `changePassword($username, $password)` → `true`
  - `changePackage($username, $plan)` → `true`
  - `accountSummary($username)` → `array|null` — hesap yoksa `null`
  - `usage($username)` → `array('disk_used'=>int,'disk_limit'=>int,'bw_used'=>int,'bw_limit'=>int)` — bayt cinsinden, sınırsız `0`
  - `createSession($username, $service = 'cpaneld', $clientIp = '')` → `string` (tam URL)

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/CpanelTest.php` sonuna ekle:

```php
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
    $t->push(200, $ok); assertTrue($c->terminateAccount('ornek1'));
    assertContains('removeacct', $t->lastCall()['url']);
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
        . '"diskused":"512M","disklimit":"2048M","totalbytes":"1048576","limit":"unlimited"}]}}');
    $u = $c->usage('ornek1');
    assertSame(512 * 1024 * 1024, $u['disk_used']);
    assertSame(2048 * 1024 * 1024, $u['disk_limit']);
    assertSame(1048576, $u['bw_used']);
    assertSame(0, $u['bw_limit']);
});

test('cPanel createSession tam URL dondurur', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"https://1.2.3.4:2083/cpsess123/"}}');
    $url = $c->createSession('ornek1', 'cpaneld', '9.9.9.9');
    assertSame('https://1.2.3.4:2083/cpsess123/', $url);
    assertContains('create_user_session', $t->lastCall()['url']);
});

test('cPanel createSession goreli URLyi mutlaklastirir', function () {
    list($c, $t) = dna_cpanel();
    $t->push(200, '{"metadata":{"result":1},"data":{"url":"/cpsess123/"}}');
    assertSame('https://1.2.3.4:2083/cpsess123/', $c->createSession('ornek1'));
});
```

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Call to undefined method DNAHosting_Cpanel::createAccount()`

- [ ] **Step 3: Metotları `Cpanel` sınıfına ekle**

```php
    public function createAccount(array $a)
    {
        $plan = $this->resolvePackage($a['plan']);

        $args = array(
            'username'    => $a['username'],
            'domain'      => $a['domain'],
            'password'    => $a['password'],
            'plan'        => $plan,
            'contactemail' => isset($a['email']) ? $a['email'] : '',
        );

        try {
            $this->call('createacct', $args);
        } catch (DNAHosting_Exception $e) {
            $summary = $this->accountSummary($a['username']);
            if ($summary && isset($summary['domain'])
                && strcasecmp($summary['domain'], $a['domain']) === 0) {
                // Hesap aslinda acilmis, yalnizca yanit gecikmis.
                return array('username' => $a['username'], 'password' => $a['password']);
            }
            throw $e;
        }

        return array('username' => $a['username'], 'password' => $a['password']);
    }

    public function accountSummary($username)
    {
        try {
            $data = $this->call('accountsummary', array('user' => $username));
        } catch (DNAHosting_Exception $e) {
            return null;
        }
        if (isset($data['acct'][0])) {
            return $data['acct'][0];
        }
        return null;
    }

    public function suspendAccount($username, $reason = '')
    {
        $this->call('suspendacct', array('user' => $username, 'reason' => $reason));
        return true;
    }

    public function unsuspendAccount($username)
    {
        $this->call('unsuspendacct', array('user' => $username));
        return true;
    }

    public function terminateAccount($username)
    {
        $this->call('removeacct', array('user' => $username, 'keepdns' => 0));
        return true;
    }

    public function changePassword($username, $password)
    {
        $this->call('passwd', array('user' => $username, 'password' => $password));
        return true;
    }

    public function changePackage($username, $plan)
    {
        $this->call('changepackage', array('user' => $username, 'pkg' => $this->resolvePackage($plan)));
        return true;
    }

    public function usage($username)
    {
        $summary = $this->accountSummary($username);
        if (!$summary) {
            throw new DNAHosting_Exception('"' . $username . '" hesabi sunucuda bulunamadi.');
        }

        return array(
            'disk_used'  => self::toBytes(isset($summary['diskused']) ? $summary['diskused'] : 0),
            'disk_limit' => self::toBytes(isset($summary['disklimit']) ? $summary['disklimit'] : 0),
            'bw_used'    => self::toBytes(isset($summary['totalbytes']) ? $summary['totalbytes'] : 0, 1),
            'bw_limit'   => self::toBytes(isset($summary['limit']) ? $summary['limit'] : 0, 1),
        );
    }

    /**
     * cPanel disk degerlerini "512M" gibi son ekle, trafigi ise cift bayt olarak verir.
     * $bareIsBytes=1 ise son eksiz sayilar bayt, degilse megabayt sayilir.
     */
    public static function toBytes($value, $bareIsBytes = 0)
    {
        $value = trim((string) $value);
        if ($value === '' || strcasecmp($value, 'unlimited') === 0) {
            return 0;
        }
        $unit   = strtoupper(substr($value, -1));
        $number = (float) $value;
        $scales = array('K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776);
        if (isset($scales[$unit])) {
            return (int) round($number * $scales[$unit]);
        }
        return (int) round($number * ($bareIsBytes ? 1 : 1048576));
    }

    public function createSession($username, $service = 'cpaneld', $clientIp = '')
    {
        $args = array('user' => $username, 'service' => $service);
        if ($clientIp !== '') {
            $args['locale'] = '';
            $args['client_ip'] = $clientIp;
        }
        $data = $this->call('create_user_session', $args);
        if (empty($data['url'])) {
            throw new DNAHosting_Exception('Sunucu oturum baglantisi dondurmedi.');
        }

        $url = (string) $data['url'];
        if (strpos($url, 'http') !== 0) {
            $scheme = $this->server['secure'] ? 'https' : 'http';
            $port   = $service === 'whostmgrd' ? 2087 : 2083;
            $url    = $scheme . '://' . $this->server['ip'] . ':' . $port . $url;
        }
        return $url;
    }
```

Not: `create_user_session` okuma beyaz listesinde **değildir** — oturum üretmek bir yazma işlemidir ve POST ile gitmelidir.

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `31 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Cpanel.php tests
git commit -m "feat: cPanel hesap yasam dongusu, kullanim ve SSO"
```

---

### Task 5: `DNAHosting_Plesk` — taşıma, hata eşleme, bağlantı testi

`<packet>` etiketine sürüm **yazılmaz**. Plesk sürüm belirtilmediğinde desteklediği en son protokolü kullanır; sürüm pazarlığı yapmamak, WHMCS portunda protokol uyuşmazlığından çıkan sınıf hatalarını tümüyle ortadan kaldırır. Spec'te önbellekte tutulacağı söylenen "protokol sürümü" bu yüzden gereksizleşiyor — önbellekte yalnızca panel tipi ve kazanan kimlik doğrulama yöntemi tutulacak.

**Files:**
- Create: `coremio/modules/Servers/DNAHosting/lib/Plesk.php`
- Create: `tests/cases/PleskTest.php`
- Modify: `tests/bootstrap.php`

**Interfaces:**
- Consumes: `DNAHosting_Http`, `DNAHosting_Exception`
- Produces: `DNAHosting_Plesk`
  - `__construct(array $server, DNAHosting_Http $http)` — `$server`: `ip`, `port`, `secure`, `username`, `password`
  - `setAuthMode($mode)` — `'key'` veya `'basic'`; verilmezse `'key'` ile başlar ve kimlik hatasında `'basic'`e düşer
  - `authMode()` → `string` — son başarılı yöntem
  - `request($bodyXml, $action)` → `SimpleXMLElement` (`<packet>` kökü); hata durumunda `DNAHosting_Exception`
  - `testConnection()` → `true`
  - `static resultOf(SimpleXMLElement $packet, $path)` → ilk `<result>` düğümü, `status` `error` ise fırlatır

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/PleskTest.php`:

```php
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
```

`tests/bootstrap.php` sonuna: `require_once $moduleDir . 'lib/Plesk.php';`

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Failed opening required '.../lib/Plesk.php'`

- [ ] **Step 3: `Plesk` sınıfının taşıma bölümünü yaz**

`coremio/modules/Servers/DNAHosting/lib/Plesk.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Plesk
{
    const ENDPOINT = '/enterprise/control/agent.php';

    private $server;
    private $http;
    private $authMode = 'key';
    private $authSettled = false;

    public function __construct(array $server, DNAHosting_Http $http)
    {
        $this->server = $server;
        $this->http   = $http;
        $this->http->addSecret($server['password']);
        $this->http->setTimeout(300);
    }

    public function setAuthMode($mode)
    {
        if ($mode === 'key' || $mode === 'basic') {
            $this->authMode    = $mode;
            $this->authSettled = true;
        }
        return $this;
    }

    public function authMode()
    {
        return $this->authMode;
    }

    public function request($bodyXml, $action)
    {
        try {
            return $this->attempt($bodyXml, $action, $this->authMode);
        } catch (DNAHosting_Exception $e) {
            $canFallBack = !$this->authSettled
                && $this->authMode === 'key'
                && $this->isAuthFailure($e->getMessage());
            if (!$canFallBack) {
                throw $e;
            }
        }

        $packet = $this->attempt($bodyXml, $action, 'basic');
        $this->authMode    = 'basic';
        $this->authSettled = true;
        return $packet;
    }

    private function attempt($bodyXml, $action, $mode)
    {
        $headers = array('Content-Type: text/xml', 'HTTP_PRETTY_PRINT: TRUE');
        if ($mode === 'key') {
            $headers[] = 'KEY: ' . $this->server['password'];
        } else {
            $headers[] = 'HTTP_AUTH_LOGIN: ' . $this->server['username'];
            $headers[] = 'HTTP_AUTH_PASSWD: ' . $this->server['password'];
        }

        $body     = '<?xml version="1.0" encoding="UTF-8"?><packet>' . $bodyXml . '</packet>';
        $response = $this->http->send('POST', self::ENDPOINT, $headers, $body, $action);

        $previous = libxml_use_internal_errors(true);
        $packet   = simplexml_load_string($response['body']);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($packet === false || $packet->getName() !== 'packet') {
            throw new DNAHosting_Exception(
                $action . ': sunucu gecerli XML dondurmedi. ' . DNAHosting_Http::summarise($response['body'])
            );
        }

        if (isset($packet->system->status) && (string) $packet->system->status === 'error') {
            throw new DNAHosting_Exception(self::describe(
                (string) $packet->system->errcode,
                (string) $packet->system->errtext
            ));
        }

        if ($mode === 'key') {
            $this->authMode = 'key';
        }
        return $packet;
    }

    public static function resultOf(SimpleXMLElement $packet, $path)
    {
        $node = $packet;
        foreach (explode('/', $path) as $step) {
            if (!isset($node->{$step})) {
                throw new DNAHosting_Exception('Plesk yanitinda <' . $step . '> bulunamadi.');
            }
            $node = $node->{$step};
        }
        if (!isset($node->result)) {
            throw new DNAHosting_Exception('Plesk yanitinda <result> bulunamadi.');
        }

        $result = $node->result;
        if ((string) $result->status === 'error') {
            throw new DNAHosting_Exception(self::describe(
                (string) $result->errcode,
                (string) $result->errtext
            ));
        }
        return $result;
    }

    private static function describe($code, $text)
    {
        $text = trim($text) !== '' ? trim($text) : 'sebep bildirilmedi';
        $hint = '';
        if ($code === '11003') {
            $hint = ' Bu anahtar baska bir IP adresi icin uretilmis;'
                . ' Plesk > Tools & Settings > API anahtarlarindan bu sunucunun IP adresi icin yenisini olusturun.';
        } elseif ($code === '1014') {
            $hint = ' Istek govdesinde bir eleman Pleskin bekledigi yerde degil.';
        } elseif ($code === '1013' || $code === '1015') {
            $hint = ' Aranan nesne panelde bulunamadi.';
        }
        return 'Plesk (' . ($code !== '' ? $code : '?') . '): ' . $text . $hint;
    }

    private function isAuthFailure($message)
    {
        return stripos($message, 'Authentication failed') !== false
            || stripos($message, 'Permission denied') !== false
            || strpos($message, '(1001)') !== false;
    }

    public function testConnection()
    {
        $packet = $this->request('<server><get><gen_info/></get></server>', 'server.get');
        self::resultOf($packet, 'server/get');
        return true;
    }
}
```

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `40 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Plesk.php tests
git commit -m "feat: Plesk XML-API tasima katmani, KEY/basic dususu ve hata eslemesi"
```

---

### Task 6: `DNAHosting_Plesk` — planlar, paylaşımlı IP, müşteri/abonelik arama

İki tuzak burada sabitleniyor. Birincisi: IP listesi `addresses->ip` altındadır, `addresses->ip_info` altında **değil** — WHMCS portunda bu yüzden hiç IP bulunamıyordu. İkincisi: yalnızca `type` değeri `shared` olan adresler aday kabul edilir; `exclusive` bir adrese abonelik açmak başka bir müşterinin ayrılmış IP'sini çalar.

**Files:**
- Modify: `coremio/modules/Servers/DNAHosting/lib/Plesk.php`
- Modify: `tests/cases/PleskTest.php`

**Interfaces:**
- Consumes: Task 5'in `request()`, `resultOf()`
- Produces: `DNAHosting_Plesk` üzerinde
  - `listPlans()` → `array` — her eleman `array('name'=>string,'guid'=>string)`
  - `resolvePlan($configured)` → `array('name'=>string,'guid'=>string)`; bulunamazsa fırlatır
  - `firstSharedIp()` → `string` — paylaşımlı IP; hiç yoksa sunucunun `ip` alanı
  - `findCustomer($externalId)` → `array('id'=>int,'login'=>string)|null`
  - `findWebspace($domain)` → `array('id'=>int,'name'=>string,'owner_id'=>int)|null`
  - `customerExternalId($customerId)` → `string` — kayıtlı değilse boş dize

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/PleskTest.php` sonuna ekle:

```php
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
```

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Call to undefined method DNAHosting_Plesk::listPlans()`

- [ ] **Step 3: Metotları `Plesk` sınıfına ekle**

```php
    public function listPlans()
    {
        if ($this->plans !== null) {
            return $this->plans;
        }

        $packet = $this->request(
            '<service-plan><get><filter/></get></service-plan>',
            'service-plan.get'
        );

        $list = array();
        if (isset($packet->{'service-plan'}->get->result)) {
            foreach ($packet->{'service-plan'}->get->result as $result) {
                if ((string) $result->status !== 'ok') {
                    continue;
                }
                $list[] = array(
                    'name' => (string) $result->name,
                    'guid' => (string) $result->guid,
                );
            }
        }

        $this->plans = $list;
        return $list;
    }

    public function resolvePlan($configured)
    {
        $configured = trim((string) $configured);
        if ($configured === '') {
            throw new DNAHosting_Exception('Urun icin bir plan secilmemis.');
        }

        $plans = $this->listPlans();
        $names = array();
        foreach ($plans as $plan) {
            $names[] = $plan['name'];
            if (strcasecmp($plan['name'], $configured) === 0) {
                return $plan;
            }
        }

        throw new DNAHosting_Exception(
            '"' . $configured . '" plani sunucuda bulunamadi. Mevcut planlar: '
            . ($names ? implode(', ', $names) : '(hic plan yok)')
        );
    }

    public function firstSharedIp()
    {
        $packet = $this->request('<ip><get/></ip>', 'ip.get');
        $result = self::resultOf($packet, 'ip/get');

        if (isset($result->addresses->ip)) {
            foreach ($result->addresses->ip as $row) {
                if (strcasecmp((string) $row->type, 'shared') === 0) {
                    $address = trim((string) $row->ip_address);
                    if ($address !== '') {
                        return $address;
                    }
                }
            }
        }

        return (string) $this->server['ip'];
    }

    public function findCustomer($externalId)
    {
        $packet = $this->request(
            '<customer><get><filter><external-id>' . self::esc($externalId) . '</external-id></filter>'
            . '<dataset><gen_info/></dataset></get></customer>',
            'customer.get'
        );

        try {
            $result = self::resultOf($packet, 'customer/get');
        } catch (DNAHosting_Exception $e) {
            return null;
        }

        return array(
            'id'    => (int) $result->id,
            'login' => (string) $result->data->gen_info->login,
        );
    }

    public function customerExternalId($customerId)
    {
        $packet = $this->request(
            '<customer><get><filter><id>' . (int) $customerId . '</id></filter>'
            . '<dataset><gen_info/></dataset></get></customer>',
            'customer.get'
        );

        try {
            $result = self::resultOf($packet, 'customer/get');
        } catch (DNAHosting_Exception $e) {
            return '';
        }

        return isset($result->data->gen_info->{'external-id'})
            ? (string) $result->data->gen_info->{'external-id'}
            : '';
    }

    public function findWebspace($domain)
    {
        $packet = $this->request(
            '<webspace><get><filter><name>' . self::esc($domain) . '</name></filter>'
            . '<dataset><gen_info/></dataset></get></webspace>',
            'webspace.get'
        );

        try {
            $result = self::resultOf($packet, 'webspace/get');
        } catch (DNAHosting_Exception $e) {
            return null;
        }

        return array(
            'id'       => (int) $result->id,
            'name'     => (string) $result->data->gen_info->name,
            'owner_id' => (int) $result->data->gen_info->{'owner-id'},
        );
    }

    public static function esc($value)
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
```

Sınıfın özellik bildirimlerine `private $plans = null;` ekle.

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `49 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Plesk.php tests
git commit -m "feat: Plesk plan cozumleme, paylasimli IP secimi ve nesne arama"
```

---

### Task 7: `DNAHosting_Plesk` — hesap yaşam döngüsü, kullanım, SSO

`webspace.add` gövdesi WHMCS 1.6.3.0 şablonuyla birebir aynı sırada kurulur. `ip_address` hem `gen_setup` hem `hosting/vrt_hst` altında geçer — WHMCS portunda `gen_setup` altındaki çıkarıldığında Plesk 18.0.80 `1014: Element 'ip_address' should be specified in 'gen_setup'` ile reddetti.

Askı, durum alanını **okuyup kendi bitimizi ekleyerek** yazar. Doğrudan `status=32` yazmak, yöneticinin ayrı bir sebeple koyduğu askıyı (bit 16) sessizce kaldırırdı.

**Files:**
- Modify: `coremio/modules/Servers/DNAHosting/lib/Plesk.php`
- Modify: `tests/cases/PleskTest.php`

**Interfaces:**
- Consumes: Task 5 ve 6'nın tüm metotları
- Produces: `DNAHosting_Plesk` üzerinde
  - `createAccount(array $a)` → `array('username'=>string,'password'=>string,'customer_id'=>int,'webspace_id'=>int)`. `$a` anahtarları: `username`, `password`, `domain`, `plan`, `email`, `name`, `external_id`
  - `webspaceStatus($webspaceId)` → `int` — mevcut durum bitmaskesi
  - `suspend($webspaceId)` → `true` — bit 32 eklenir
  - `unsuspend($webspaceId)` → `true` — bit 32 çıkarılır
  - `terminate($customerId, $expectedExternalId)` → `true`; `external-id` uyuşmazsa fırlatır
  - `changePassword($customerId, $webspaceId, $password)` → `true` — hem panel hem FTP şifresi
  - `changePlan($webspaceId, array $plan)` → `true` — `$plan` Task 6'nın `resolvePlan()` dönüşü
  - `usage($webspaceId)` → `array('disk_used'=>int,'disk_limit'=>int,'bw_used'=>int,'bw_limit'=>int)`
  - `createSession($login, $clientIp)` → `string` (tam URL)
- Sabit: `const OUR_SUSPENSION_BIT = 32;`

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/PleskTest.php` sonuna ekle:

```php
test('Plesk createAccount musteri sonra webspace olusturur', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    $t->push(200, dna_packet('<customer><add><result><status>ok</status><id>77</id></result></add></customer>'));
    $t->push(200, dna_packet('<webspace><add><result><status>ok</status><id>9</id></result></add></webspace>'));

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
    $t->push(200, dna_packet('<service-plan><get><result><status>ok</status>'
        . '<name>Pro</name><guid>g-2</guid></result></get></service-plan>'));
    $t->push(200, dna_packet('<ip><get><result><status>ok</status><addresses>'
        . '<ip><ip_address>10.0.0.2</ip_address><type>shared</type></ip>'
        . '</addresses></result></get></ip>'));
    $t->push(200, dna_packet('<customer><add><result><status>ok</status><id>77</id></result></add></customer>'));
    $t->push(200, dna_packet('<webspace><add><result><status>ok</status><id>9</id></result></add></webspace>'));
    $p->createAccount(array(
        'username' => 'ornek1', 'password' => 'Gizli.123!', 'domain' => 'ornek.com',
        'plan' => 'Pro', 'email' => 'a@b.c', 'name' => 'Ornek', 'external_id' => 'wisecp-501',
    ));
    assertContains('<external-id>wisecp-501</external-id>', $t->calls[2]['body']);
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

test('Plesk terminate external-id tutmuyorsa reddeder', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>m</login><external-id>baska-sistem-9</external-id></gen_info>'
        . '</result></get></customer>'));
    $e = assertThrows(function () use ($p) {
        $p->terminate(77, 'wisecp-501');
    }, 'silme reddedildi');
    assertContains('baska-sistem-9', $e->getMessage());
    assertSame(1, count($t->calls), 'silme istegi hic gonderilmemeli');
});

test('Plesk terminate external-id tutuyorsa siler', function () {
    list($p, $t) = dna_plesk();
    $t->push(200, dna_packet('<customer><get><result><status>ok</status><id>77</id>'
        . '<data><gen_info><login>m</login><external-id>wisecp-501</external-id></gen_info>'
        . '</result></get></customer>'));
    $t->push(200, dna_packet('<customer><del><result><status>ok</status></result></del></customer>'));
    assertTrue($p->terminate(77, 'wisecp-501'));
    assertContains('<del>', $t->lastCall()['body']);
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
```

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Call to undefined method DNAHosting_Plesk::createAccount()`

- [ ] **Step 3: Metotları `Plesk` sınıfına ekle**

```php
    const OUR_SUSPENSION_BIT = 32;

    public function createAccount(array $a)
    {
        $plan = $this->resolvePlan($a['plan']);
        $ip   = $this->firstSharedIp();

        $customerXml = '<customer><add><gen_info>'
            . '<pname>' . self::esc($a['name']) . '</pname>'
            . '<login>' . self::esc($a['username']) . '</login>'
            . '<passwd>' . self::esc($a['password']) . '</passwd>'
            . '<email>' . self::esc($a['email']) . '</email>'
            . '<external-id>' . self::esc($a['external_id']) . '</external-id>'
            . '</gen_info></add></customer>';

        $packet     = $this->request($customerXml, 'customer.add');
        $customerId = (int) self::resultOf($packet, 'customer/add')->id;

        // Sira WHMCS 1.6.3.0 sablonuyla birebir: gen_setup, hosting, prefs, plan-name.
        $webspaceXml = '<webspace><add>'
            . '<gen_setup>'
            . '<name>' . self::esc($a['domain']) . '</name>'
            . '<owner-id>' . $customerId . '</owner-id>'
            . '<ip_address>' . self::esc($ip) . '</ip_address>'
            . '<htype>vrt_hst</htype>'
            . '<status>0</status>'
            . '</gen_setup>'
            . '<hosting><vrt_hst>'
            . '<property><name>ftp_login</name><value>' . self::esc($a['username']) . '</value></property>'
            . '<property><name>ftp_password</name><value>' . self::esc($a['password']) . '</value></property>'
            . '<ip_address>' . self::esc($ip) . '</ip_address>'
            . '</vrt_hst></hosting>'
            . '<prefs><www>true</www></prefs>'
            . '<plan-name>' . self::esc($plan['name']) . '</plan-name>'
            . '</add></webspace>';

        $packet      = $this->request($webspaceXml, 'webspace.add');
        $webspaceId  = (int) self::resultOf($packet, 'webspace/add')->id;

        return array(
            'username'    => $a['username'],
            'password'    => $a['password'],
            'customer_id' => $customerId,
            'webspace_id' => $webspaceId,
        );
    }

    public function webspaceStatus($webspaceId)
    {
        $packet = $this->request(
            '<webspace><get><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<dataset><gen_info/></dataset></get></webspace>',
            'webspace.get'
        );
        $result = self::resultOf($packet, 'webspace/get');
        return (int) $result->data->gen_info->status;
    }

    public function suspend($webspaceId)
    {
        return $this->setStatus($webspaceId, $this->webspaceStatus($webspaceId) | self::OUR_SUSPENSION_BIT);
    }

    public function unsuspend($webspaceId)
    {
        return $this->setStatus($webspaceId, $this->webspaceStatus($webspaceId) & ~self::OUR_SUSPENSION_BIT);
    }

    private function setStatus($webspaceId, $status)
    {
        $packet = $this->request(
            '<webspace><set><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<values><gen_setup><status>' . (int) $status . '</status></gen_setup></values>'
            . '</set></webspace>',
            'webspace.set'
        );
        self::resultOf($packet, 'webspace/set');
        return true;
    }

    public function terminate($customerId, $expectedExternalId)
    {
        $actual = $this->customerExternalId($customerId);
        if ($actual !== (string) $expectedExternalId) {
            throw new DNAHosting_Exception(
                'Guvenlik nedeniyle silme reddedildi: paneldeki musterinin external-id degeri "'
                . ($actual !== '' ? $actual : '(bos)') . '", beklenen "' . $expectedExternalId . '". '
                . 'Bu abonelik bu modul tarafindan olusturulmamis; elle silmeniz gerekir.'
            );
        }

        $packet = $this->request(
            '<customer><del><filter><id>' . (int) $customerId . '</id></filter></del></customer>',
            'customer.del'
        );
        self::resultOf($packet, 'customer/del');
        return true;
    }

    public function changePassword($customerId, $webspaceId, $password)
    {
        $packet = $this->request(
            '<customer><set><filter><id>' . (int) $customerId . '</id></filter>'
            . '<values><gen_info><passwd>' . self::esc($password) . '</passwd></gen_info></values>'
            . '</set></customer>',
            'customer.set'
        );
        self::resultOf($packet, 'customer/set');

        $packet = $this->request(
            '<webspace><set><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<values><hosting><vrt_hst>'
            . '<property><name>ftp_password</name><value>' . self::esc($password) . '</value></property>'
            . '</vrt_hst></hosting></values>'
            . '</set></webspace>',
            'webspace.set'
        );
        self::resultOf($packet, 'webspace/set');
        return true;
    }

    public function changePlan($webspaceId, array $plan)
    {
        $packet = $this->request(
            '<webspace><switch-subscription><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<plan-guid>' . self::esc($plan['guid']) . '</plan-guid>'
            . '</switch-subscription></webspace>',
            'webspace.switch-subscription'
        );
        self::resultOf($packet, 'webspace/switch-subscription');
        return true;
    }

    public function usage($webspaceId)
    {
        $packet = $this->request(
            '<webspace><get><filter><id>' . (int) $webspaceId . '</id></filter>'
            . '<dataset><stat/><limits/></dataset></get></webspace>',
            'webspace.get'
        );
        $result = self::resultOf($packet, 'webspace/get');

        $limits = array();
        if (isset($result->data->limits->limit)) {
            foreach ($result->data->limits->limit as $limit) {
                $limits[(string) $limit->name] = (float) $limit->value;
            }
        }

        return array(
            'disk_used'  => isset($result->data->stat->real_size) ? (int) $result->data->stat->real_size : 0,
            'disk_limit' => self::limitToBytes($limits, 'disk_space'),
            'bw_used'    => isset($result->data->stat->traffic) ? (int) $result->data->stat->traffic : 0,
            'bw_limit'   => self::limitToBytes($limits, 'max_traffic'),
        );
    }

    private static function limitToBytes(array $limits, $name)
    {
        if (!isset($limits[$name]) || $limits[$name] < 0) {
            return 0;
        }
        return (int) $limits[$name];
    }

    public function createSession($login, $clientIp)
    {
        $packet = $this->request(
            '<server><create_session>'
            . '<login>' . self::esc($login) . '</login>'
            . '<data><user_ip>' . self::esc($clientIp) . '</user_ip><source_server/></data>'
            . '</create_session></server>',
            'server.create_session'
        );
        $result = self::resultOf($packet, 'server/create_session');

        $sessionId = (string) $result->id;
        if ($sessionId === '') {
            throw new DNAHosting_Exception('Plesk oturum kimligi dondurmedi.');
        }

        return ($this->server['secure'] ? 'https' : 'http') . '://'
            . $this->server['ip'] . ':' . $this->server['port']
            . '/enterprise/rsession_init.php?PLESKSESSID=' . rawurlencode($sessionId);
    }
```

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `59 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Plesk.php tests
git commit -m "feat: Plesk hesap yasam dongusu, sahiplik guardi, kullanim ve SSO"
```

---

### Task 8: `DNAHosting_Detector` — panel tespiti

Port **yalnızca sıralama ipucudur.** WHMCS portunda bir tur boyunca porta karar verdirildi ve 8443 dışındaki bir portta çalışan Plesk kurulumlarında modül tümüyle yanlış panele gitti. Karar her zaman gerçek bir probe'a dayanır.

**Files:**
- Create: `coremio/modules/Servers/DNAHosting/lib/Detector.php`
- Create: `tests/cases/DetectorTest.php`
- Modify: `tests/bootstrap.php`

**Interfaces:**
- Consumes: `DNAHosting_Cpanel`, `DNAHosting_Plesk`, `DNAHosting_Exception`
- Produces: `DNAHosting_Detector`
  - `__construct(array $server, callable $driverFactory)` — `$driverFactory($panel)` → `'cpanel'` için `DNAHosting_Cpanel`, `'plesk'` için `DNAHosting_Plesk`
  - `setCache(callable $get, callable $set)` — `$get($key)` → `array|null`, `$set($key, array $value)` → dönüş yok sayılır
  - `detect()` → `array('panel'=>'cpanel'|'plesk', 'auth'=>string)`; ikisi de yanıt vermezse fırlatır
  - `static order($port)` → `array` — `array('plesk','cpanel')` veya `array('cpanel','plesk')`
  - `static cacheKey(array $server)` → `string`

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/DetectorTest.php`:

```php
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
        function ($key, $value) { throw new Exception('lisans alan adi tutmuyor'); }
    );
    assertSame('cpanel', $d->detect()['panel'], 'onbellek hatasi tespiti bozmamali');
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
```

`tests/bootstrap.php` sonuna: `require_once $moduleDir . 'lib/Detector.php';`

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Failed opening required '.../lib/Detector.php'`

- [ ] **Step 3: `Detector` sınıfını yaz**

`coremio/modules/Servers/DNAHosting/lib/Detector.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Detector
{
    private $server;
    private $factory;
    private $cacheGet = null;
    private $cacheSet = null;

    public function __construct(array $server, callable $driverFactory)
    {
        $this->server  = $server;
        $this->factory = $driverFactory;
    }

    public function setCache(callable $get, callable $set)
    {
        $this->cacheGet = $get;
        $this->cacheSet = $set;
        return $this;
    }

    public static function order($port)
    {
        $port = (int) $port;
        if ($port === 8443 || $port === 8880) {
            return array('plesk', 'cpanel');
        }
        return array('cpanel', 'plesk');
    }

    public static function cacheKey(array $server)
    {
        return 'dnahosting_' . sha1(implode('|', array(
            isset($server['ip']) ? $server['ip'] : '',
            isset($server['port']) ? $server['port'] : '',
            isset($server['secure']) ? $server['secure'] : '',
            isset($server['username']) ? $server['username'] : '',
            sha1(isset($server['password']) ? $server['password'] : ''),
        )));
    }

    public function detect()
    {
        $key = self::cacheKey($this->server);

        $cached = $this->readCache($key);
        if ($cached && isset($cached['panel'])) {
            return array(
                'panel' => $cached['panel'],
                'auth'  => isset($cached['auth']) ? $cached['auth'] : '',
            );
        }

        $failures = array();
        foreach (self::order($this->server['port']) as $panel) {
            $driver = call_user_func($this->factory, $panel);
            try {
                $driver->testConnection();
            } catch (Exception $e) {
                $failures[] = strtoupper($panel) . ': ' . $e->getMessage();
                continue;
            }

            $found = array(
                'panel' => $panel,
                'auth'  => method_exists($driver, 'authMode') ? $driver->authMode() : '',
            );
            $this->writeCache($key, $found);
            return $found;
        }

        throw new DNAHosting_Exception(
            'Sunucuda ne cPanel ne Plesk yanit verdi. ' . implode(' | ', $failures)
        );
    }

    private function readCache($key)
    {
        if (!$this->cacheGet) {
            return null;
        }
        try {
            $value = call_user_func($this->cacheGet, $key);
        } catch (Exception $e) {
            return null;
        }
        return is_array($value) ? $value : null;
    }

    private function writeCache($key, array $value)
    {
        if (!$this->cacheSet) {
            return;
        }
        try {
            call_user_func($this->cacheSet, $key, $value);
        } catch (Exception $e) {
            // Onbellek saf optimizasyondur; yazilamamasi tespiti bozmaz.
        }
    }
}
```

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `67 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/lib/Detector.php tests
git commit -m "feat: panel tespiti — port yalnizca siralama ipucu, karar gercek probe"
```

---

### Task 9: Modül iskeleti, saf yardımcılar ve `DNAHosting_Module` — kurulum/bağlantı/paketler

`config.php`, `init.php`, `lang/` ve modül sınıfının ilk yarısı tek görevde birleşiyor: hiçbiri diğeri olmadan sınanabilir bir çıktı üretmiyor.

Modül sınıfını sınamak için `ServerModule` temel sınıfının bir **test ikizi** yazılıyor. Bu ikiz çekirdeğin aynısı değildir — gerçek `ServerModule::__construct` oturum açmış admini de yükler (`coremio/classes/Modules.php:262-273`), test için gereksizdir. İkiz yalnızca bizim kullandığımız sözleşmeyi taşır: `$server`, `$config`, `$lang`, `$order`, `$options`, `$error` ve `define_server_info()` çağrısı.

**Files:**
- Create: `coremio/modules/Servers/DNAHosting/config.php`
- Create: `coremio/modules/Servers/DNAHosting/init.php`
- Create: `coremio/modules/Servers/DNAHosting/lib/Support.php`
- Create: `coremio/modules/Servers/DNAHosting/lang/en.php`
- Create: `coremio/modules/Servers/DNAHosting/lang/tr.php`
- Create: `coremio/modules/Servers/DNAHosting/DNAHosting.php`
- Create: `tests/stubs/wisecp.php`
- Create: `tests/cases/SupportTest.php`
- Create: `tests/cases/ModuleTest.php`
- Modify: `tests/bootstrap.php`

**Interfaces:**
- Consumes: `DNAHosting_Cpanel`, `DNAHosting_Plesk`, `DNAHosting_Detector`, `DNAHosting_Http`
- Produces:
  - `DNAHosting_Support::usernameFor($domain, $panel)` → `string` — cPanel için en fazla 8 karakter, harfle başlar, yalnızca `[a-z0-9]`; Plesk için en fazla 16 karakter
  - `DNAHosting_Support::password($length = 14)` → `string` — büyük harf, küçük harf, rakam ve sembol içerir
  - `DNAHosting_Support::domainKey($domain)` → `string` — küçük harfe indirilmiş, sondaki nokta atılmış
  - `DNAHosting_Module` — `$force_setup = false`, `panel()` → `'cpanel'|'plesk'`, `driver()` → aktif sürücü, `testConnect()` → bool, `getPlans()` → `array`, `use_method($param)`

- [ ] **Step 1: Saf yardımcı testlerini yaz**

`tests/cases/SupportTest.php`:

```php
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
```

- [ ] **Step 2: Testleri çalıştır, hata verdiğini gör**

```bash
php tests/run.php
```

Beklenen: `Class "DNAHosting_Support" not found`

- [ ] **Step 3: `Support` sınıfını yaz ve bootstrap'a ekle**

`coremio/modules/Servers/DNAHosting/lib/Support.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Support
{
    public static function usernameFor($domain, $panel)
    {
        $label = strtolower((string) $domain);
        $label = preg_replace('/^www\./', '', $label);
        $parts = explode('.', $label);
        $label = preg_replace('/[^a-z0-9]/', '', $parts[0]);

        if ($label === '' || !preg_match('/^[a-z]/', $label)) {
            $label = 'u' . $label;
        }

        $max = $panel === 'cpanel' ? 8 : 16;
        if (strlen($label) > $max) {
            $label = substr($label, 0, $max);
        }

        $min = $panel === 'cpanel' ? 6 : 8;
        while (strlen($label) < $min) {
            $label .= chr(random_int(97, 122));
        }

        // Sondaki birkac karakteri rastgeleleyerek ayni domainin cakismasini onle.
        $suffixLength = $panel === 'cpanel' ? 3 : 4;
        $keep         = max(1, strlen($label) - $suffixLength);
        $label        = substr($label, 0, $keep);
        for ($i = 0; $i < $suffixLength; $i++) {
            $label .= chr(random_int(97, 122));
        }

        return substr($label, 0, $max);
    }

    public static function password($length = 14)
    {
        $lower  = 'abcdefghijkmnpqrstuvwxyz';
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $digit  = '23456789';
        $symbol = '!.#%*+=?@_-';

        $chars = array(
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $digit[random_int(0, strlen($digit) - 1)],
            $symbol[random_int(0, strlen($symbol) - 1)],
        );

        $pool = $lower . $upper . $digit . $symbol;
        for ($i = count($chars); $i < $length; $i++) {
            $chars[] = $pool[random_int(0, strlen($pool) - 1)];
        }

        shuffle($chars);
        return implode('', $chars);
    }

    public static function domainKey($domain)
    {
        $domain = rtrim(trim((string) $domain), '.');
        return function_exists('mb_strtolower') ? mb_strtolower($domain, 'UTF-8') : strtolower($domain);
    }
}
```

`tests/bootstrap.php` sonuna: `require_once $moduleDir . 'lib/Support.php';`

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `72 gecti, 0 kaldi`

- [ ] **Step 5: `config.php`, `init.php` ve dil dosyalarını yaz**

`coremio/modules/Servers/DNAHosting/config.php`:

```php
<?php
    return [
        'type' => "hosting",
        'server-info-checker' => true,
        'server-info-port' => true,
        'server-info-not-secure-port' => 2086,
        'server-info-secure-port' => 2087,
        'supported' => [
            'disk-bandwidth-usage',
            'change-password',
        ],
    ];
```

`access-hash` bilerek yok: kimlik bilgisi tek alanda, `servers.password` icinde, sifreli saklanir.

`coremio/modules/Servers/DNAHosting/init.php`:

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

require_once __DIR__ . DS . 'lib' . DS . 'Exception.php';
require_once __DIR__ . DS . 'lib' . DS . 'Support.php';
require_once __DIR__ . DS . 'lib' . DS . 'Http.php';
require_once __DIR__ . DS . 'lib' . DS . 'Cpanel.php';
require_once __DIR__ . DS . 'lib' . DS . 'Plesk.php';
require_once __DIR__ . DS . 'lib' . DS . 'Detector.php';
```

`coremio/modules/Servers/DNAHosting/lang/en.php`:

```php
<?php
    return [
        'name'        => "DNA Reseller Hosting",
        'description' => "Sell shared hosting from a cPanel/WHM or Plesk reseller account. The panel type is detected automatically.",
        'select-plan'      => "Package / Plan",
        'no-plans'         => "No packages found on this server.",
        'panel-cpanel'     => "cPanel / WHM",
        'panel-plesk'      => "Plesk",
        'detected-panel'   => "Detected panel",
        'login-panel'      => "Log in to Control Panel",
        'username'         => "Username",
        'password'         => "Password",
        'domain'           => "Domain",
        'ftp-host'         => "FTP Host",
        'ftp-port'         => "FTP Port",
        'disk-usage'       => "Disk Usage",
        'bandwidth-usage'  => "Bandwidth Usage",
        'error-no-order'   => "This service has no panel account yet.",
        'error-shared-domain' => "This domain is still in use by another active service on the same server, so it was not terminated.",
    ];
```

`coremio/modules/Servers/DNAHosting/lang/tr.php` — aynı anahtarlar, Türkçe karşılıklarıyla:

```php
<?php
    return [
        'name'        => "DNA Bayi Hosting",
        'description' => "cPanel/WHM veya Plesk bayi hesabı üzerinden paylaşımlı hosting satın. Panel tipi otomatik tespit edilir.",
        'select-plan'      => "Paket / Plan",
        'no-plans'         => "Bu sunucuda hiç paket bulunamadı.",
        'panel-cpanel'     => "cPanel / WHM",
        'panel-plesk'      => "Plesk",
        'detected-panel'   => "Tespit edilen panel",
        'login-panel'      => "Kontrol Paneline Giriş",
        'username'         => "Kullanıcı Adı",
        'password'         => "Şifre",
        'domain'           => "Alan Adı",
        'ftp-host'         => "FTP Sunucusu",
        'ftp-port'         => "FTP Portu",
        'disk-usage'       => "Disk Kullanımı",
        'bandwidth-usage'  => "Trafik Kullanımı",
        'error-no-order'   => "Bu hizmetin henüz bir panel hesabı yok.",
        'error-shared-domain' => "Bu alan adı aynı sunucuda başka bir aktif hizmet tarafından hâlâ kullanılıyor, bu yüzden sonlandırılmadı.",
    ];
```

`index.html` dosyasını `lib/`, `lang/` ve `pages/` altına da kopyala.

- [ ] **Step 6: Modül sınıfı testlerini yaz**

`tests/stubs/wisecp.php` — çekirdek sözleşmesinin test ikizi:

```php
<?php
// Bu dosya WiseCP cekirdeginin AYNISI DEGILDIR. Yalnizca DNAHosting_Module'un
// kullandigi sozlesmeyi tasiyan bir test ikizidir.

class ServerModule
{
    protected $server = null;
    public $force_setup = true;
    public $area_link = null;
    public $page = false;
    public $_name = null;
    public $order = array();
    public $product = array();
    public $user = array();
    public $config = array();
    public $options = array();
    public $lang = null;
    public $error = null;

    public function __construct($server, $options = array())
    {
        $parts       = explode('_Module', $this->_name);
        $this->_name = $parts[0];
        $this->server = $server;

        $external      = isset($options['config']) ? $options['config'] : $options;
        $this->options = $options;
        $this->config  = array_merge(Modules::Config('Servers', $this->_name), $external);
        $this->lang    = Modules::Lang('Servers', $this->_name);

        if ($server) {
            $this->define_server_info($server);
        }
    }

    public function get_page($file, $vars = array()) { return ''; }
    protected function encode_str($s = '') { return 'ENC:' . $s; }
    protected function decode_str($s = '') { return strpos($s, 'ENC:') === 0 ? substr($s, 4) : $s; }
}

class Modules
{
    public static $logs = array();
    public static function Config($type, $name)
    {
        return include dirname(__DIR__) . '/../coremio/modules/Servers/' . $name . '/config.php';
    }
    public static function Lang($type, $name, $lang = 'en')
    {
        return include dirname(__DIR__) . '/../coremio/modules/Servers/' . $name . '/lang/en.php';
    }
    public static function save_log($type, $module, $action, $request = '', $response = '', $processed = '')
    {
        self::$logs[] = array($action, $request, $response);
        return true;
    }
    public static function getPage($type, $name, $page, $vars = array()) { return ''; }
}

class Crypt
{
    public static function encode($v, $k = '') { return 'ENC:' . $v; }
    public static function decode($v, $k = '') { return strpos($v, 'ENC:') === 0 ? substr($v, 4) : false; }
}

class Config
{
    public static function get($k) { return 'test-key'; }
}
```

`tests/cases/ModuleTest.php`:

```php
<?php
require_once __DIR__ . '/../stubs/wisecp.php';
require_once dirname(__DIR__) . '/../coremio/modules/Servers/DNAHosting/DNAHosting.php';

function dna_module($port, $transportSetup)
{
    $server = array(
        'id' => 3, 'name' => 'test', 'ip' => '1.2.3.4', 'port' => $port, 'secure' => 1,
        'username' => 'bayi', 'password' => 'GIZLI123456',
    );
    $module = new DNAHosting_Module($server);
    $t      = new DNAHosting_FakeTransport();
    call_user_func($transportSetup, $t);
    $module->useTransport($t);
    return array($module, $t);
}

test('Modul force_setup false olmali', function () {
    $m = new DNAHosting_Module(false);
    assertSame(false, $m->force_setup, 'true olursa cekirdek Pleske uygun olmayan sifre uretir');
});

test('Modul cPanel sunucusunu tespit eder', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    assertTrue($m->testConnect());
    assertSame('cpanel', $m->panel());
});

test('Modul Plesk sunucusunu tespit eder', function () {
    list($m, $t) = dna_module(8443, function ($t) {
        $t->push(200, '<?xml version="1.0"?><packet><server><get><result>'
            . '<status>ok</status></result></get></server></packet>');
    });
    assertTrue($m->testConnect());
    assertSame('plesk', $m->panel());
});

test('Modul baglanamayinca false doner ve error doldurur', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    assertSame(false, $m->testConnect());
    assertContains('Access denied', $m->error);
});

test('Modul getPlans cPanel paketlerini isim listesine cevirir', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":['
            . '{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
    });
    $plans = $m->getPlans();
    assertSame(1, count($plans));
    assertSame('bayi_pro', $plans[0]['name']);
});

test('Modul getPlans hata durumunda false doner', function () {
    list($m, $t) = dna_module(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    assertSame(false, $m->getPlans());
    assertContains('Access denied', $m->error);
});

class DNAHosting_ModuleRouting extends DNAHosting_Module
{
    public function use_clientArea_SingleSignOn() { return 'client'; }
    public function use_adminArea_SingleSignOn() { return 'admin'; }
}

test('Modul use_method musteri onekini kullanir', function () {
    // __CLASS__ derleme zamani sabiti oldugundan alt sinifta da _name 'DNAHosting_Module' kalir.
    $m = new DNAHosting_ModuleRouting(false);
    assertSame('client', $m->use_method('SingleSignOn'));
});

test('Modul use_method bilinmeyen metodu yok sayar', function () {
    $m = new DNAHosting_ModuleRouting(false);
    assertSame(null, $m->use_method('YokBoyleBirSey'));
    assertSame(null, $m->use_method(''));
});
```

- [ ] **Step 7: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Failed opening required '.../DNAHosting.php'`

- [ ] **Step 8: `DNAHosting.php`'nin ilk bölümünü yaz**

```php
<?php
defined("CORE_FOLDER") or exit("You can not get in here!");

class DNAHosting_Module extends ServerModule
{
    public $force_setup = false;

    private $panel     = null;
    private $drivers   = array();
    private $transport = null;
    private $storage   = array();

    public function __construct($server, $options = array())
    {
        $this->_name = __CLASS__;
        parent::__construct($server, $options);
    }

    protected function define_server_info($server = array())
    {
        if (!class_exists('DNAHosting_Http')) {
            include __DIR__ . DS . 'init.php';
        }
    }

    /** Testler icin: gercek cURL yerine sahte tasiyici enjekte eder. */
    public function useTransport(callable $transport)
    {
        $this->transport = $transport;
        $this->panel     = null;
        $this->drivers   = array();
        return $this;
    }

    private function http()
    {
        $scheme = $this->server['secure'] ? 'https' : 'http';
        $http   = new DNAHosting_Http($scheme . '://' . $this->server['ip'] . ':' . $this->server['port']);

        if ($this->transport) {
            $http->setTransport($this->transport);
        }

        $name = $this->_name;
        $http->setLogger(function ($action, $request, $response) use ($name) {
            Modules::save_log('Servers', $name, $action, $request, $response, '');
        });

        return $http;
    }

    private function makeDriver($panel)
    {
        if ($panel === 'plesk') {
            return new DNAHosting_Plesk($this->server, $this->http());
        }
        return new DNAHosting_Cpanel($this->server, $this->http());
    }

    /** Tespit edilen panel: 'cpanel' veya 'plesk'. Tespit basarisizsa firlatir. */
    public function panel()
    {
        if ($this->panel !== null) {
            return $this->panel;
        }

        $self     = $this;
        $detector = new DNAHosting_Detector($this->server, function ($panel) use ($self) {
            return $self->driverFor($panel);
        });

        $detector->setCache(
            function ($key) { return DNAHosting_Module::cacheRead($key); },
            function ($key, $value) { DNAHosting_Module::cacheWrite($key, $value); }
        );

        $found       = $detector->detect();
        $this->panel = $found['panel'];

        if ($this->panel === 'plesk' && !empty($found['auth'])) {
            $this->driverFor('plesk')->setAuthMode($found['auth']);
        }

        return $this->panel;
    }

    /** Panel basina tek surucu ornegi tutar (istek ici bellek). */
    public function driverFor($panel)
    {
        if (!isset($this->drivers[$panel])) {
            $this->drivers[$panel] = $this->makeDriver($panel);
        }
        return $this->drivers[$panel];
    }

    /** Aktif panelin surucusu. */
    public function driver()
    {
        return $this->driverFor($this->panel());
    }

    public static function cacheRead($key)
    {
        if (!class_exists('Cache')) {
            return null;
        }
        try {
            $cache = new Cache('dnahosting');
            if (!$cache->isCached($key)) {
                return null;
            }
            $value = $cache->retrieve($key);
            return is_array($value) ? $value : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function cacheWrite($key, array $value)
    {
        if (!class_exists('Cache')) {
            return;
        }
        try {
            $cache = new Cache('dnahosting');
            $cache->store($key, $value, 604800);
        } catch (Exception $e) {
            // Onbellek saf optimizasyon; lisans alan adi tutmazsa sessizce iskalar.
        }
    }

    private function failed(Exception $e)
    {
        $this->error = $e->getMessage();
        return false;
    }

    public function testConnect()
    {
        try {
            $this->panel();
            return true;
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function getPlans()
    {
        try {
            if ($this->panel() === 'plesk') {
                return $this->driver()->listPlans();
            }
            return $this->driver()->listPackages();
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function use_method($param = '')
    {
        $param  = str_replace('-', '_', $param);
        $prefix = defined('ADMINISTRATOR') ? 'use_adminArea_' : 'use_clientArea_';
        if ($param === '') {
            return null;
        }
        if (!method_exists($this, $prefix . $param)) {
            return null;
        }
        return $this->{$prefix . $param}();
    }
}
```

- [ ] **Step 9: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `80 gecti, 0 kaldi`

- [ ] **Step 10: Commit**

```bash
git add coremio/modules/Servers/DNAHosting tests
git commit -m "feat: modul iskeleti, saf yardimcilar, panel secimi ve baglanti testi"
```

---

### Task 10: `DNAHosting_Module` — hesap yaşam döngüsü

Mükerrer domain guard'ı veritabanına gider. Sorguyu `otherActiveServices()` adlı ayrı bir korumalı metoda ayırıyoruz; böylece guard mantığı veritabanı ikizi kurmadan, alt sınıfla geçersiz kılınarak sınanabilir.

**Files:**
- Modify: `coremio/modules/Servers/DNAHosting/DNAHosting.php`
- Modify: `tests/cases/ModuleTest.php`

**Interfaces:**
- Consumes: Task 9'un `panel()`, `driver()`, `failed()`; `DNAHosting_Support`
- Produces: `DNAHosting_Module` üzerinde
  - `createAccount($domain, $options = array())` → `array('username','password','ftp_info')|false`
  - `suspend()`, `unsuspend()` → bool; `suspend_reseller()`, `unsuspend_reseller()` bunlara alias
  - `removeAccount($user = false)` → bool; `removeReseller($user = false)` alias
  - `changePassword($oldpw, $newpw)` → bool
  - `change_plan($plan)` → bool
  - `apply_updowngrade($orderopt = array(), $product = array())` → bool
  - `apply_options($old_options, $new_options = array())` → `array|false`
  - `UsernameGenerator($domain = '', $half_mixed = false)` → string
  - `externalId()` → `string` — `"wisecp-" . $this->order['id']`
  - `protected otherActiveServices($domainKey)` → `array` — aynı sunucuda aynı domainli diğer aktif/askıdaki sipariş kimlikleri

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/ModuleTest.php` sonuna ekle:

```php
class DNAHosting_ModuleWithNeighbours extends DNAHosting_Module
{
    public $neighbours = array();
    protected function otherActiveServices($domainKey)
    {
        return $this->neighbours;
    }
}

function dna_module_n($port, $transportSetup)
{
    $server = array(
        'id' => 3, 'name' => 'test', 'ip' => '1.2.3.4', 'port' => $port, 'secure' => 1,
        'username' => 'bayi', 'password' => 'GIZLI123456',
    );
    $module = new DNAHosting_ModuleWithNeighbours($server);
    $t      = new DNAHosting_FakeTransport();
    call_user_func($transportSetup, $t);
    $module->useTransport($t);
    $module->order = array('id' => 501, 'owner_id' => 7);
    $module->user  = array('email' => 'musteri@ornek.com', 'full_name' => 'Ornek Musteri');
    return array($module, $t);
}

test('Modul createAccount cPanelde ftp_info ile doner', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"pkg":[{"name":"bayi_pro","QUOTA":"1","BWLIMIT":"1"}]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $r = $m->createAccount('ornek.com', array('creation_info' => array('plan' => 'bayi_pro')));
    assertTrue(is_array($r), 'dizi bekleniyordu, error: ' . $m->error);
    assertTrue(strlen($r['username']) > 0);
    assertTrue(strlen($r['password']) >= 12);
    assertSame('ftp.ornek.com', $r['ftp_info']['host']);
    assertSame(21, $r['ftp_info']['port']);
    assertSame($r['username'], $r['ftp_info']['username']);
});

test('Modul createAccount paket secilmemisse anlamli hata verir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    assertSame(false, $m->createAccount('ornek.com', array()));
    assertContains('paket', strtolower($m->error));
});

test('Modul suspend ve unsuspend cPanelde calisir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    assertTrue($m->suspend());
    assertContains('suspendacct', $t->lastCall()['url']);
});

test('Modul suspend_reseller suspende alias', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user'] = 'ornek1';
    assertTrue($m->suspend_reseller());
    assertContains('suspendacct', $t->lastCall()['url']);
});

test('Modul removeAccount ayni domainli komsu varsa reddeder', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    $m->config['user']    = 'ornek1';
    $m->options['domain'] = 'ornek.com';
    $m->neighbours        = array(777);
    assertSame(false, $m->removeAccount());
    assertContains('777', $m->error);
    // Guard panel() cagrilmadan once firlar, bu yuzden hic HTTP istegi cikmaz.
    assertSame(0, count($t->calls), 'hicbir istek gonderilmemeli');
});

test('Modul removeAccount komsu yoksa siler', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{}}');
    });
    $m->config['user']    = 'ornek1';
    $m->options['domain'] = 'ornek.com';
    assertTrue($m->removeAccount());
    assertContains('removeacct', $t->lastCall()['url']);
});

test('Modul change_plan bos plani sessizce gecer', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    $m->config['user'] = 'ornek1';
    assertTrue($m->change_plan(''));
    // Bos plan panel() cagrilmadan true doner.
    assertSame(0, count($t->calls));
});

test('Modul externalId siparis kimliginden turer', function () {
    list($m, $t) = dna_module_n(2087, function ($t) { });
    assertSame('wisecp-501', $m->externalId());
});

test('Modul UsernameGenerator panele uygun ad uretir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
    });
    $u = $m->UsernameGenerator('cokuzunbirdomain.com');
    assertTrue(strlen($u) <= 8, 'cPanel icin 8i asmamali: ' . $u);
});
```

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Call to undefined method DNAHosting_Module::createAccount()`

- [ ] **Step 3: Yaşam döngüsü metotlarını yaz**

Aşağıdakileri sınıfa ekle:

```php
    public function externalId()
    {
        return 'wisecp-' . (isset($this->order['id']) ? (int) $this->order['id'] : 0);
    }

    public function UsernameGenerator($domain = '', $half_mixed = false)
    {
        return DNAHosting_Support::usernameFor($domain, $this->panel());
    }

    private function planOf(array $options)
    {
        $creation = isset($options['creation_info']) ? $options['creation_info'] : array();
        if (isset($creation['plan']) && trim($creation['plan']) !== '') {
            return $creation['plan'];
        }

        $moduleData = isset($this->product['module_data']) ? $this->product['module_data'] : array();
        if (is_string($moduleData)) {
            $moduleData = json_decode($moduleData, true);
        }
        if (isset($moduleData['create_account']['plan'])) {
            return $moduleData['create_account']['plan'];
        }
        if (isset($moduleData['plan'])) {
            return $moduleData['plan'];
        }
        return '';
    }

    public function createAccount($domain, $options = array())
    {
        try {
            $panel    = $this->panel();
            $domain   = DNAHosting_Support::domainKey($domain);
            $username = isset($options['username']) && $options['username'] !== ''
                ? $options['username']
                : DNAHosting_Support::usernameFor($domain, $panel);
            $password = isset($options['password']) && $options['password'] !== ''
                ? $options['password']
                : DNAHosting_Support::password(14);

            $account = array(
                'username'    => $username,
                'password'    => $password,
                'domain'      => $domain,
                'plan'        => $this->planOf($options),
                'email'       => isset($this->user['email']) ? $this->user['email'] : '',
                'name'        => isset($this->user['full_name']) ? $this->user['full_name'] : $username,
                'external_id' => $this->externalId(),
            );

            $created = $this->driver()->createAccount($account);

            return array(
                'username' => $created['username'],
                'password' => $created['password'],
                'ftp_info' => array(
                    'ip'       => $this->server['ip'],
                    'host'     => 'ftp.' . $domain,
                    'username' => $created['username'],
                    'password' => $created['password'],
                    'port'     => 21,
                ),
            );
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /** Plesk islemleri icin abonelik kimligini domainden yeniden turetir. */
    private function pleskTargets()
    {
        if (isset($this->storage['plesk_targets'])) {
            return $this->storage['plesk_targets'];
        }

        $domain   = DNAHosting_Support::domainKey($this->orderDomain());
        $webspace = $this->driver()->findWebspace($domain);
        if (!$webspace) {
            throw new DNAHosting_Exception(
                '"' . $domain . '" alan adina ait abonelik panelde bulunamadi.'
            );
        }

        $targets = array('webspace_id' => $webspace['id'], 'customer_id' => $webspace['owner_id']);
        $this->storage['plesk_targets'] = $targets;
        return $targets;
    }

    private function orderDomain()
    {
        if (isset($this->options['domain']) && $this->options['domain'] !== '') {
            return $this->options['domain'];
        }
        if (isset($this->order['options']['domain'])) {
            return $this->order['options']['domain'];
        }
        return '';
    }

    private function panelUser()
    {
        if (!isset($this->config['user']) || $this->config['user'] === '') {
            throw new DNAHosting_Exception($this->lang['error-no-order']);
        }
        return $this->config['user'];
    }

    public function suspend()
    {
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->suspend($t['webspace_id']);
            }
            return $this->driver()->suspendAccount($this->panelUser(), 'WiseCP');
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function unsuspend()
    {
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->unsuspend($t['webspace_id']);
            }
            return $this->driver()->unsuspendAccount($this->panelUser());
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function suspend_reseller()   { return $this->suspend(); }
    public function unsuspend_reseller() { return $this->unsuspend(); }
    public function removeReseller($user = false) { return $this->removeAccount($user); }
    public function setReseller($user, $params = array())   { return true; }
    public function setupReseller($user = false, $params = array()) { return true; }

    public function removeAccount($user = false)
    {
        try {
            $domainKey = DNAHosting_Support::domainKey($this->orderDomain());
            $shared    = $this->otherActiveServices($domainKey);
            if ($shared) {
                throw new DNAHosting_Exception(
                    $this->lang['error-shared-domain']
                    . ' Ayni alan adini kullanan diger hizmet numaralari: ' . implode(', ', $shared)
                );
            }

            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->terminate($t['customer_id'], $this->externalId());
            }
            return $this->driver()->terminateAccount($user ? $user : $this->panelUser());
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    /**
     * Ayni sunucuda ayni alan adini kullanan diger aktif/askidaki hizmetlerin kimlikleri.
     * server_id JSON icinde hem tirnakli hem tirnaksiz kodlanabildigi icin iki desen de aranir
     * (cekirdegin kendi deseni: coremio/models/admin/products.php:376-378).
     */
    protected function otherActiveServices($domainKey)
    {
        if ($domainKey === '' || !class_exists('Models') || !isset(Models::$init->db)) {
            return array();
        }

        $serverId = (int) $this->server['id'];
        $orderId  = isset($this->order['id']) ? (int) $this->order['id'] : 0;

        $stmt = Models::$init->db->select('id,options')->from('users_products');
        $stmt->where('type', '=', 'hosting', '&&');
        $stmt->where('module', '=', $this->_name, '&&');
        if ($orderId) {
            $stmt->where('id', '!=', $orderId, '&&');
        }
        $stmt->where('(');
        $stmt->where('status', '=', 'active', '||');
        $stmt->where('status', '=', 'suspended', '');
        $stmt->where(')', '', '', '&&');
        $stmt->where('(');
        $stmt->where('options', 'LIKE', '%"server_id":"' . $serverId . '"%', '||');
        $stmt->where('options', 'LIKE', '%"server_id":' . $serverId . '%', '');
        $stmt->where(')', '', '', '');

        $rows = $stmt->build() ? $stmt->fetch_assoc() : array();
        if (!$rows) {
            return array();
        }

        $matches = array();
        foreach ($rows as $row) {
            $options = json_decode($row['options'], true);
            if (!is_array($options) || !isset($options['domain'])) {
                continue;
            }
            if (DNAHosting_Support::domainKey($options['domain']) === $domainKey) {
                $matches[] = (int) $row['id'];
            }
        }
        return $matches;
    }

    public function changePassword($oldpw, $newpw)
    {
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->changePassword($t['customer_id'], $t['webspace_id'], $newpw);
            }
            return $this->driver()->changePassword($this->panelUser(), $newpw);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function change_plan($plan)
    {
        if (trim((string) $plan) === '') {
            return true;
        }
        try {
            if ($this->panel() === 'plesk') {
                $t = $this->pleskTargets();
                return $this->driver()->changePlan($t['webspace_id'], $this->driver()->resolvePlan($plan));
            }
            return $this->driver()->changePackage($this->panelUser(), $plan);
        } catch (Exception $e) {
            return $this->failed($e);
        }
    }

    public function apply_updowngrade($orderopt = array(), $product = array())
    {
        if ($product) {
            $this->product = $product;
        }
        return $this->change_plan($this->planOf(is_array($orderopt) ? $orderopt : array()));
    }

    public function modifyAccount($params = array())
    {
        return false;
    }

    public function apply_options($old_options, $new_options = array())
    {
        $oldConfig = isset($old_options['config']) ? $old_options['config'] : array();
        $newConfig = isset($new_options['config']) ? $new_options['config'] : array();

        $newUser = isset($newConfig['user']) ? $newConfig['user'] : '';
        if ($newUser === '') {
            return $new_options;
        }

        $plain = isset($newConfig['password']) ? $newConfig['password'] : '';
        if ($plain !== '' && $plain !== (isset($oldConfig['password']) ? $oldConfig['password'] : '')) {
            $this->config['user'] = $newUser;
            if (!$this->changePassword('', $plain)) {
                return false;
            }
            $newConfig['password'] = $this->encode_str($plain);
        }

        $domain = isset($new_options['domain']) ? $new_options['domain'] : $this->orderDomain();
        $new_options['config']   = $newConfig;
        $new_options['ftp_info'] = array(
            'ip'       => $this->server['ip'],
            'host'     => 'ftp.' . DNAHosting_Support::domainKey($domain),
            'username' => $newUser,
            'password' => isset($newConfig['password']) ? $newConfig['password'] : '',
            'port'     => 21,
        );

        return $new_options;
    }
```

- [ ] **Step 4: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `89 gecti, 0 kaldi`

- [ ] **Step 5: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/DNAHosting.php tests
git commit -m "feat: hesap yasam dongusu ve mukerrer alan adi guardi"
```

---

### Task 11: `DNAHosting_Module` — kullanım, SSO, müşteri paneli

İstemci IP'sini almanın doğru yolu **`UserManager::GetIP()`** (`coremio/classes/UserManager.php:388`). Proxy başlığını da ele alır ve sonucu bellekte tutar. Spec'in 8. bölümündeki 3 numaralı risk böylece kapanıyor.

Bayt biçimlendirmesi çekirdeğin `FileManager::formatByte()` metodu yerine `DNAHosting_Support` içinde yapılır — sözleşme yalnızca bir dize istiyor, ve kendi uygulamamız testlerde çalıştırılabilir.

**Files:**
- Modify: `coremio/modules/Servers/DNAHosting/lib/Support.php`
- Modify: `coremio/modules/Servers/DNAHosting/DNAHosting.php`
- Create: `coremio/modules/Servers/DNAHosting/pages/clientArea-home.php`
- Modify: `tests/cases/SupportTest.php`, `tests/cases/ModuleTest.php`

**Interfaces:**
- Consumes: Task 10'un tümü
- Produces:
  - `DNAHosting_Support::formatBytes($bytes)` → `string` — `0` için `"∞"`, aksi halde `"1.2 GB"` biçimi
  - `DNAHosting_Support::percent($used, $limit)` → `int` — `0`–`100` arası; `$limit` sıfırsa `0`
  - `DNAHosting_Module::getDisk()` / `getBandwidth()` → WiseCP kullanım dizisi veya `false`
  - `DNAHosting_Module::getSummary()` → `array`
  - `DNAHosting_Module::use_clientArea_SingleSignOn()` / `use_adminArea_SingleSignOn()` → bool
  - `DNAHosting_Module::panel_links_for_client()` / `panel_links_for_admin()` → `array`
  - `DNAHosting_Module::clientArea()` → `string`
  - `DNAHosting_Module::usageSnapshot()` → `array` — sürücünün ham bayt dizisi, istek içi belleğe alınmış

- [ ] **Step 1: Başarısız testleri yaz**

`tests/cases/SupportTest.php` sonuna:

```php
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
```

`tests/cases/ModuleTest.php` sonuna:

```php
test('Modul getDisk WiseCP sozlesmesine uyar', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
            . '"diskused":"512M","disklimit":"1024M","totalbytes":"1048576","limit":"unlimited"}]}}');
    });
    $m->config['user'] = 'ornek1';
    $d = $m->getDisk();
    assertSame(536870912, $d['used']);
    assertSame(1073741824, $d['limit']);
    assertSame(50, $d['used-percent']);
    assertSame('1 GB', $d['format-limit']);
    assertSame('512 MB', $d['format-used']);
});

test('Modul getBandwidth sinirsizi sonsuz gosterir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
            . '"diskused":"512M","disklimit":"1024M","totalbytes":"1048576","limit":"unlimited"}]}}');
    });
    $m->config['user'] = 'ornek1';
    $b = $m->getBandwidth();
    assertSame(0, $b['limit']);
    assertSame('∞', $b['format-limit']);
    assertSame(0, $b['used-percent']);
});

test('Modul kullanim verisini tek istekte bir kez ceker', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[]}}');
        $t->push(200, '{"metadata":{"result":1},"data":{"acct":[{"user":"ornek1",'
            . '"diskused":"512M","disklimit":"1024M","totalbytes":"1","limit":"1"}]}}');
    });
    $m->config['user'] = 'ornek1';
    $m->getDisk();
    $m->getBandwidth();
    assertSame(2, count($t->calls), 'tespit + tek kullanim cagrisi bekleniyor');
});

test('Modul getDisk hatada false doner', function () {
    list($m, $t) = dna_module_n(2087, function ($t) {
        $t->push(403, 'Access denied');
        $t->push(403, 'Access denied');
    });
    $m->config['user'] = 'ornek1';
    assertSame(false, $m->getDisk());
    assertContains('Access denied', $m->error);
});

test('Modul panel_links_for_client giris butonu verir', function () {
    list($m, $t) = dna_module_n(2087, function ($t) { });
    $m->area_link = '/hizmet/501';
    $links = $m->panel_links_for_client();
    assertTrue(isset($links['panel']));
    assertContains('use_method', $links['panel']['url']);
    assertContains('SingleSignOn', $links['panel']['url']);
});

test('Modul root SSO metodu tanimlamaz', function () {
    assertSame(false, method_exists('DNAHosting_Module', 'use_adminArea_root_SingleSignOn'),
        'root erisimimiz yok, buton gosterilmemeli');
});
```

- [ ] **Step 2: Testleri çalıştır, hata verdiklerini gör**

```bash
php tests/run.php
```

Beklenen: `Class "DNAHosting_Support" ... formatBytes()` tanımsız

- [ ] **Step 3: `Support` biçimlendiricilerini ekle**

```php
    public static function formatBytes($bytes)
    {
        $bytes = (float) $bytes;
        if ($bytes <= 0) {
            return '∞';
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        $rounded = round($bytes, 1);
        $text    = ($rounded == (int) $rounded) ? (string) (int) $rounded : (string) $rounded;
        return $text . ' ' . $units[$index];
    }

    public static function percent($used, $limit)
    {
        $used  = (float) $used;
        $limit = (float) $limit;
        if ($limit <= 0 || $used <= 0) {
            return 0;
        }
        $percent = (int) round($used / $limit * 100);
        return $percent > 100 ? 100 : $percent;
    }
```

- [ ] **Step 4: Modülün kullanım ve SSO bölümünü yaz**

Aşağıdakileri sınıfa ekle (Task 9 geçici bir sonda bırakmadı — `DNAHosting_ModuleRouting` test alt sınıfı yönlendirmeyi kendi geçersiz kılmalarıyla ölçüyor, bu yüzden silinecek bir şey yok):

```php
    public function usageSnapshot()
    {
        if (isset($this->storage['usage'])) {
            return $this->storage['usage'];
        }

        if ($this->panel() === 'plesk') {
            $t     = $this->pleskTargets();
            $usage = $this->driver()->usage($t['webspace_id']);
        } else {
            $usage = $this->driver()->usage($this->panelUser());
        }

        $this->storage['usage'] = $usage;
        return $usage;
    }

    private function usageBlock($usedKey, $limitKey)
    {
        try {
            $usage = $this->usageSnapshot();
        } catch (Exception $e) {
            return $this->failed($e);
        }

        $used  = (int) $usage[$usedKey];
        $limit = (int) $usage[$limitKey];

        return array(
            'limit'        => $limit,
            'used'         => $used,
            'used-percent' => DNAHosting_Support::percent($used, $limit),
            'format-limit' => DNAHosting_Support::formatBytes($limit),
            'format-used'  => $used > 0 ? DNAHosting_Support::formatBytes($used) : '0 KB',
        );
    }

    public function getDisk()
    {
        return $this->usageBlock('disk_used', 'disk_limit');
    }

    public function getBandwidth($user = false)
    {
        return $this->usageBlock('bw_used', 'bw_limit');
    }

    public function getSummary()
    {
        if (!isset($this->config['user']) || $this->config['user'] === '') {
            return false;
        }
        return array(
            'panel'  => $this->panel === 'plesk' ? $this->lang['panel-plesk'] : $this->lang['panel-cpanel'],
            'domain' => $this->orderDomain(),
        );
    }

    private static function clientIp()
    {
        if (class_exists('UserManager') && method_exists('UserManager', 'GetIP')) {
            $ip = UserManager::GetIP();
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

    private function openPanel($service)
    {
        try {
            if ($this->panel() === 'plesk') {
                $url = $this->driver()->createSession($this->panelUser(), self::clientIp());
            } else {
                $url = $this->driver()->createSession($this->panelUser(), $service, self::clientIp());
            }
        } catch (Exception $e) {
            $this->failed($e);
            echo htmlspecialchars($this->error, ENT_QUOTES, 'UTF-8');
            return false;
        }

        if (class_exists('Utility') && method_exists('Utility', 'redirect')) {
            Utility::redirect($url);
        } else {
            header('Location: ' . $url);
        }
        return true;
    }

    public function use_clientArea_SingleSignOn()
    {
        return $this->openPanel('cpaneld');
    }

    public function use_adminArea_SingleSignOn()
    {
        return $this->openPanel('cpaneld');
    }

    public function panel_links_for_client()
    {
        return array(
            'panel' => array(
                'url'   => $this->area_link . '?inc=use_method&method=SingleSignOn',
                'color' => 'blue',
                'icon'  => 'fa fa-sign-in',
                'name'  => $this->lang['login-panel'],
            ),
        );
    }

    public function panel_links_for_admin()
    {
        return array(
            'panel' => array(
                'url'  => $this->area_link . '?operation=hosting_use_method&use_method=SingleSignOn',
                'name' => $this->lang['login-panel'],
            ),
        );
    }

    public function clientArea()
    {
        $page = $this->page ? $this->page : 'home';
        return $this->get_page('clientArea-' . $page, array(
            'LANG'     => $this->lang,
            'panel'    => $this->panel,
            'username' => isset($this->config['user']) ? $this->config['user'] : '',
            'domain'   => $this->orderDomain(),
            'server'   => $this->server,
        ));
    }
```

`use_adminArea_root_SingleSignOn()` **tanımlanmaz** — root erişimimiz yoktur, tanımlanırsa admin panelinde çalışmayan bir buton belirir.

`clientArea_buttons()` de **tanımlanmaz.** Spec §3.3 bunu `ClientAreaCustomButtonArray` karşılığı olarak listeliyor, ama `config["type"] == "hosting"` olduğu için giriş butonu `panel_links_for_client()` üzerinden geliyor (`coremio/classes/Modules.php:1007-1013`); `clientArea_buttons()` yalnızca bunun ötesinde ek buton eklemek için vardır ve bizim ek butonumuz yok. İkisini birden tanımlamak panelde çift buton gösterirdi.

- [ ] **Step 5: `pages/clientArea-home.php` dosyasını yaz**

```php
<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");
    $LANG     = isset($LANG) ? $LANG : array();
    $username = isset($username) ? $username : '';
    $domain   = isset($domain) ? $domain : '';
    $server   = isset($server) ? $server : array();
?>
<div class="dnahosting-panel">
    <table class="table">
        <tr>
            <th><?php echo $LANG['domain']; ?></th>
            <td><?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th><?php echo $LANG['username']; ?></th>
            <td><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th><?php echo $LANG['ftp-host']; ?></th>
            <td>ftp.<?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <th><?php echo $LANG['ftp-port']; ?></th>
            <td>21</td>
        </tr>
    </table>
</div>
```

Şifre bu sayfada **gösterilmez** — WiseCP zaten aktivasyon e-postasında iletir ve müşteri paneli sayfası önbelleklenebilir bir HTML parçasıdır.

- [ ] **Step 6: Testleri çalıştır, geçtiklerini gör**

```bash
php tests/run.php
```

Beklenen: `97 gecti, 0 kaldi`

- [ ] **Step 7: Commit**

```bash
git add coremio/modules/Servers/DNAHosting tests
git commit -m "feat: disk/trafik kullanimi, SSO ve musteri paneli sayfasi"
```

---

### Task 12: `pages/` — ürün formu, sipariş detayı, aktivasyon şablonları

Ürün formu somut bir `server_id` ile yüklenir (`coremio/controllers/admin/products.php:976-1001`), bu yüzden `$module->getPlans()` doğru panele gider. Panel tespiti başarısızsa form **paket listesi yerine hatayı gösterir** — WHMCS'te boş bir dropdown'ın "paket yok" mu "bağlanamadım" mı olduğu anlaşılmıyordu.

**Files:**
- Create: `coremio/modules/Servers/DNAHosting/pages/create-account-form-elements.php`
- Create: `coremio/modules/Servers/DNAHosting/pages/order-detail.php`
- Create: `coremio/modules/Servers/DNAHosting/pages/activation-html.php`
- Create: `coremio/modules/Servers/DNAHosting/pages/activation-text.php`

**Interfaces:**
- Consumes: `DNAHosting_Module::getPlans()`, `panel()`, `$module->lang`, `$module->error`
- Produces: `module_data[plan]` adlı POST alanı — `apply_updowngrade()` ve `createAccount()` bu adı okur (Task 10, `planOf()`)

- [ ] **Step 1: Ürün formunu yaz**

`pages/create-account-form-elements.php`:

```php
<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG           = $module->lang;
    $product        = isset($product) && $product ? $product : array();
    $module_data    = isset($product["module_data"]) ? Utility::jdecode($product["module_data"], true) : array();
    $create_account = isset($module_data["create_account"]) ? $module_data["create_account"] : $module_data;
    $selected       = isset($create_account["plan"]) ? $create_account["plan"] : '';

    $plans = $module->getPlans();
?>
<div class="formcon">
    <div class="yuzde30"><?php echo $LANG["detected-panel"]; ?></div>
    <div class="yuzde70">
        <?php
            if ($plans === false) {
                echo '<span class="error">' . htmlspecialchars((string) $module->error, ENT_QUOTES, 'UTF-8') . '</span>';
            } else {
                echo $module->panel() === 'plesk' ? $LANG["panel-plesk"] : $LANG["panel-cpanel"];
            }
        ?>
    </div>
</div>

<div class="formcon" id="plans_wrap">
    <div class="yuzde30"><?php echo $LANG["select-plan"]; ?></div>
    <div class="yuzde70">
        <?php if ($plans === false || !$plans): ?>
            <em><?php echo $LANG["no-plans"]; ?></em>
        <?php else: ?>
            <select name="module_data[plan]" id="module_data_plan">
                <?php foreach ($plans as $plan): ?>
                    <option value="<?php echo htmlspecialchars($plan["name"], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $plan["name"] === $selected ? ' selected' : ''; ?>>
                        <?php echo htmlspecialchars($plan["name"], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </div>
</div>
```

Not: `$module->getPlans()` cPanel'de `name/quota/bwlimit`, Plesk'te `name/guid` döndürür. Form yalnızca `name` kullanır, bu yüzden iki şekil de sorunsuz çalışır.

- [ ] **Step 2: Sipariş detayı sayfasını yaz**

`pages/order-detail.php`:

```php
<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG    = $module->lang;
    $options = isset($options) ? $options : array();
    $config  = isset($options["config"]) ? $options["config"] : array();
    $domain  = isset($options["domain"]) ? $options["domain"] : '';
?>
<table class="table">
    <tr>
        <th><?php echo $LANG["domain"]; ?></th>
        <td><?php echo htmlspecialchars($domain, ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
    <tr>
        <th><?php echo $LANG["username"]; ?></th>
        <td><?php echo htmlspecialchars(isset($config["user"]) ? $config["user"] : '', ENT_QUOTES, 'UTF-8'); ?></td>
    </tr>
</table>
```

- [ ] **Step 3: Aktivasyon şablonlarını yaz**

`pages/activation-html.php` — `activation_infos()` şifreleri çözülmüş hâlde geçirir (`coremio/classes/Modules.php:390-404`):

```php
<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG     = $module->lang;
    $options  = isset($options) ? $options : array();
    $config   = isset($options["config"]) ? $options["config"] : array();
    $ftp      = isset($options["ftp_info"]) ? $options["ftp_info"] : array();
    $domain   = isset($options["domain"]) ? $options["domain"] : '';
    $server   = isset($server) ? $server : array();
    $panelUrl = ($server["secure"] ? 'https' : 'http') . '://' . $server["ip"] . ':' . $server["port"];
    $esc      = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
?>
<table cellpadding="6" cellspacing="0" border="0">
    <tr><td><strong><?php echo $LANG["domain"]; ?></strong></td><td><?php echo $esc($domain); ?></td></tr>
    <tr><td><strong><?php echo $LANG["login-panel"]; ?></strong></td>
        <td><a href="<?php echo $esc($panelUrl); ?>"><?php echo $esc($panelUrl); ?></a></td></tr>
    <tr><td><strong><?php echo $LANG["username"]; ?></strong></td>
        <td><?php echo $esc(isset($config["user"]) ? $config["user"] : ''); ?></td></tr>
    <tr><td><strong><?php echo $LANG["password"]; ?></strong></td>
        <td><?php echo $esc(isset($config["password"]) ? $config["password"] : ''); ?></td></tr>
    <tr><td><strong><?php echo $LANG["ftp-host"]; ?></strong></td>
        <td><?php echo $esc(isset($ftp["host"]) ? $ftp["host"] : ''); ?></td></tr>
    <tr><td><strong><?php echo $LANG["ftp-port"]; ?></strong></td>
        <td><?php echo $esc(isset($ftp["port"]) ? $ftp["port"] : 21); ?></td></tr>
</table>
```

`pages/activation-text.php`:

```php
<?php
    defined("CORE_FOLDER") or exit("You can not get in here!");

    $LANG     = $module->lang;
    $options  = isset($options) ? $options : array();
    $config   = isset($options["config"]) ? $options["config"] : array();
    $ftp      = isset($options["ftp_info"]) ? $options["ftp_info"] : array();
    $domain   = isset($options["domain"]) ? $options["domain"] : '';
    $server   = isset($server) ? $server : array();
    $panelUrl = ($server["secure"] ? 'https' : 'http') . '://' . $server["ip"] . ':' . $server["port"];

    echo $LANG["domain"] . ": " . $domain . "\n";
    echo $LANG["login-panel"] . ": " . $panelUrl . "\n";
    echo $LANG["username"] . ": " . (isset($config["user"]) ? $config["user"] : '') . "\n";
    echo $LANG["password"] . ": " . (isset($config["password"]) ? $config["password"] : '') . "\n";
    echo $LANG["ftp-host"] . ": " . (isset($ftp["host"]) ? $ftp["host"] : '') . "\n";
    echo $LANG["ftp-port"] . ": " . (isset($ftp["port"]) ? $ftp["port"] : 21) . "\n";
```

- [ ] **Step 4: Sözdizimini doğrula**

```bash
find coremio/modules/Servers/DNAHosting -name '*.php' -print0 | xargs -0 -n1 php -l
```

Beklenen: her dosya için `No syntax errors detected`

- [ ] **Step 5: Testlerin hâlâ geçtiğini doğrula**

```bash
php tests/run.php
```

Beklenen: `97 gecti, 0 kaldi`

- [ ] **Step 6: Commit**

```bash
git add coremio/modules/Servers/DNAHosting/pages
git commit -m "feat: urun formu, siparis detayi ve aktivasyon sablonlari"
```

---

### Task 13: Yayın denetimi ve kurulum kılavuzu

**Files:**
- Create: `README.md`
- Create: `CHANGELOG.md`
- Modify: `docs/superpowers/specs/2026-08-28-wisecp-dnahosting-design.md` (kapanan riskleri işaretle)

**Interfaces:**
- Consumes: Task 1–12'nin tamamı
- Produces: yayına hazır modül ağacı ve kurulum kılavuzu

- [ ] **Step 1: Statik denetimi çalıştır**

```bash
find coremio/modules/Servers/DNAHosting -name '*.php' -print0 | xargs -0 -n1 php -l
```

Beklenen: hepsi `No syntax errors detected`

- [ ] **Step 2: Koruma satırlarını doğrula**

```bash
grep -L 'defined("CORE_FOLDER")' coremio/modules/Servers/DNAHosting/lib/*.php coremio/modules/Servers/DNAHosting/pages/*.php coremio/modules/Servers/DNAHosting/DNAHosting.php coremio/modules/Servers/DNAHosting/init.php
```

Beklenen: **hiçbir çıktı yok.** Çıktı veren her dosya guard'dan yoksundur ve düzeltilmelidir. (`config.php` ve `lang/*.php` bu listede yoktur — çekirdek onları `include` ile döndürür ve guard'lı olamazlar.)

- [ ] **Step 3: Testleri son kez çalıştır**

```bash
php tests/run.php
```

Beklenen: `97 gecti, 0 kaldi`

- [ ] **Step 4: `README.md` yaz**

Aşağıdaki bölümleri içermeli, WHMCS projesindeki README'nin yapısıyla aynı sırada:

1. **Başlık ve rozetler** — modül adı, desteklenen paneller (cPanel/WHM, Plesk), WiseCP sürüm gereksinimi, lisans
2. **Ne yapar** — tek modül, iki panel, otomatik tespit; hangi işlemleri kapsadığı
3. **Gereksinimler** — WiseCP kurulumu, PHP cURL + SimpleXML eklentileri, cPanel reseller hesabı veya Plesk reseller hesabı
4. **Kurulum** — `coremio/modules/Servers/DNAHosting/` altına kopyala; başka adım yok (veritabanı tablosu oluşturulmaz)
5. **Sunucu ekleme** — Yönetim > Ürünler/Hizmetler > Paylaşımlı Sunucular > Yeni. Alanlar:
   - **Tip:** `DNAHosting`
   - **IP Adresi:** panel sunucusunun adresi
   - **Kullanıcı Adı:** bayi kullanıcı adı
   - **Şifre:** cPanel'de **WHM API token'ı**, Plesk'te **secret key veya panel şifresi**
   - **Port:** cPanel 2087, Plesk 8443
   - **Güvenli:** işaretli
6. **cPanel ACL gereksinimleri** — token'ın sahip olması gereken yetkiler: `list-accts`, `acct-summary`, `create-acct`, `suspend-acct`, `kill-acct`, `passwd`, `upgrade-account`, `list-pkgs`, `show-bandwidth`, `create-user-session`
7. **Plesk anahtar notu** — API anahtarı **oluşturulduğu IP'ye bağlıdır**; WiseCP sunucusunun çıkış IP'si için üretilmelidir, aksi halde `11003` alınır
8. **Ürün tanımlama** — sunucuyu seç, açılan formdan paket seç
9. **Sorun giderme** — en az şu üç satır: `HTTP 403` + `cpanelresult` (WHM erişimi yok, ACL'leri kontrol et), `Plesk (11003)` (anahtar başka IP için), `Plesk (1014)` (istek gövdesi reddedildi, modül sürümünü kontrol et)
10. **Loglar** — Yönetim > Sistem > Modül Logları; token ve şifreler maskelenmiştir
11. **Lisans**

- [ ] **Step 5: `CHANGELOG.md` yaz**

```markdown
# Değişiklik Günlüğü

## 1.0.0

İlk sürüm.

- cPanel/WHM ve Plesk için tek modül, panel otomatik tespit ediliyor
- Hesap açma, askıya alma, askıyı kaldırma, sonlandırma
- Şifre değiştirme, paket değiştirme
- Disk ve trafik kullanımı
- Müşteri ve yönetici tek tıkla panel girişi
- Sonlandırmada mükerrer alan adı koruması
- Plesk'te external-id ile sahiplik doğrulaması
```

- [ ] **Step 6: Spec'teki kapanan riskleri işaretle**

`docs/superpowers/specs/2026-08-28-wisecp-dnahosting-design.md` §8 tablosunda 3 numaralı satırı (`İstemci IP'sini almanın WiseCP'deki doğru yolu`) **kapandı** olarak işaretle ve doğrulamayı yaz: `UserManager::GetIP()` (`coremio/classes/UserManager.php:388`).

2 numaralı risk (`Cache::retrieve()`) açık kalır — canlı kurulumda doğrulanacaktır. Modül önbellek çalışmasa da doğru çalıştığı için bu risk yalnızca performansı etkiler.

- [ ] **Step 7: Commit ve etiketle**

```bash
git add README.md CHANGELOG.md docs
git commit -m "docs: kurulum kilavuzu, degisiklik gunlugu ve kapanan risk isaretleri"
git tag v1.0.0
```

---

## Canlı Doğrulama (uygulama sonrası, kod değil)

Bu adımlar plan tarafından **otomatikleştirilemez** — gerçek bir cPanel ve gerçek bir Plesk sunucusu gerektirir. Spec §9'daki sıra izlenir. Özellikle şu üçü hiç canlı çalıştırılmamış yollardır:

1. **Plesk askı bitmask'ı (32).** Test hesabını askıya al, panelde durumu ve sitenin gerçekten kapandığını gör, askıyı kaldır. Bit 32 çalışmazsa bit 1 denenecek — `coremio/modules/Servers/Plesk/Plesk.php:1227` WiseCP'nin kendi modülünün root olmayan sahip için 1 kullandığını gösteriyor.
2. **Sonlandırma ve sahiplik guard'ı.** Panelde elle bir abonelik oluştur, modül üzerinden silmeyi dene — `external-id` uyuşmadığı için reddedilmeli.
3. **SSO.** Hem müşteri hem yönetici tarafından, her iki panelde.
