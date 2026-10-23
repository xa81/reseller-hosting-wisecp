<div align="center">
  <a href="README-TR.md">TR <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/TR.png" alt="TR" height="20" /></a>
  <a href="README.md"> | EN <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/US.png" alt="EN" height="20" /></a>
  <a href="README-DE.md"> | DE <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/DE.png" alt="DE" height="20" /></a>
  <a href="README-SA.md"> | AR <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/SA.png" alt="AR" height="20" /></a>
  <a href="README-NL.md"> | NL <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/NL.png" alt="NL" height="20" /></a>
  <a href="README-AZ.md"> | AZ <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/AZ.png" alt="AZ" height="20" /></a>
  <a href="README-CN.md"> | CN <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/CN.png" alt="CN" height="20" /></a>
  <a href="README-FR.md"> | FR <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/FR.png" alt="FR" height="20" /></a>
  <a href="README-IT.md"> | IT <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/IT.png" alt="IT" height="20" /></a>
  <a href="README-RU.md"> | RU <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/RU.png" alt="RU" height="20" /></a>
  <a href="README-ES.md"> | ES <img style="padding-top: 8px" src="https://raw.githubusercontent.com/yammadev/flag-icons/master/png/ES.png" alt="ES" height="20" /></a>
</div>

<div align="center">

# DNA Reseller Hosting

**cPanel ve Plesk bayi hesaplarını tek bir WiseCP sunucu modülünden yönetin.**

Bir modül, iki panel. Panel tipini siz seçmezsiniz — modül sunucuya kendisi sorar ve hangi panelin
yanıt verdiğini hatırlar.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-destekleniyor-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-destekleniyor-53BCE6?style=flat-square)

</div>

---

## 📑 İçindekiler

- [✨ Neler yapar](#-neler-yapar)
- [📋 Gereksinimler](#-gereksinimler)
- [🚀 Kurulum](#-kurulum)
- [🔍 Kayıtlar ve sorun giderme](#-kayıtlar-ve-sorun-giderme)
- [🧩 Bilinmesi gerekenler](#-bilinmesi-gerekenler)
- [📄 Değişiklik günlüğü](#-değişiklik-günlüğü)

---

## ✨ Neler yapar

| Özellik | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Bağlantı testi ve otomatik panel tespiti | ✅ | ✅ |
| Hesap açma (yeniden denemeye dayanıklı) | ✅ | ✅ |
| Askıya alma / askıyı kaldırma | ✅ | ✅ |
| Sonlandırma | ✅ | ✅ *(sahiplik doğrulanır)* |
| Şifre değiştirme | ✅ | ✅ |
| Paket / plan değiştirme (yükseltme ve hizmet ekranı) | ✅ | ✅ |
| Hizmet genel bakışında disk ve trafik göstergeleri | ✅ | ✅ |
| Ölçümlü faturalama için disk ve trafik kullanımı | ✅ | ✅ |
| Kontrol paneline tek tıkla giriş | ✅ | ✅ |

> 💡 **Bayiler için yapıldı.** Root ya da yönetici erişimi gerekmez; modül kendi bayi hesabınızın
> yetkileriyle çalışır ve açtığı her hesap sizin kotanızdan düşer.

---

## 📋 Gereksinimler

- **WiseCP 5** ya da **WiseCP 3**, kendi sunucunuzda, yönetici erişimiyle; her birinin kendi paketi var
- **PHP**: WiseCP 5'te, WiseCP 5'in istediği gibi 8.2 ya da üstü; WiseCP 3'te 7.4 – 8.4
- PHP eklentileri: WiseCP 5'in zaten istedikleri, artı `simplexml` (standart PHP derlemelerinde açık gelir)
- Bir **cPanel/WHM bayi hesabı** (WHM API token) &nbsp;ya da&nbsp; bir **Plesk bayi hesabı**
  (gizli anahtar ya da panel şifresi)
- WiseCP sunucusundan panel sunucusunun API portuna giden erişim

> ✅ Veritabanı tablosu oluşturulmaz, cron gerekmez, composer bağımlılığı yoktur. Yapılandırdığınız
> panel sunucusu dışında hiçbir yere veri gönderilmez.

---

## 🚀 Kurulum

### 1️⃣ Modülü kurun

Her sürüm iki paket taşır. WiseCP sürümünüze uyanı seçin:

| WiseCP | Paket | Bu depodaki kaynağı |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **Paketler birbirinin yerine kullanılamaz.** WiseCP 5 sunucu modülü sözleşmesini değiştirdi;
> WiseCP 3 modülü WiseCP 5'te yüklenmez, tersi de geçerli.

Paketi modül yöneticisinden yükleyin ya da WiseCP kurulumunuzun köküne açın. Arşiv tam yolu taşır;
modül buraya yerleşir:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← burası
```

Aşağıdaki adımlar **WiseCP 5** içindir. WiseCP 3'te ekranların adları farklıdır; *Bilinmesi
gerekenler* altındaki [WiseCP 3](#-bilinmesi-gerekenler) bölümüne bakın.

### 2️⃣ Sunucuyu ekleyin

**Ürünler → Hosting/Sunucu → Sunucu Yönetimi → Yeni Sunucu Ekle.** **Sunucu Otomasyon Modülü**
bölümünde, **Web Hosting** sekmesinde **DNA Reseller Hosting**'i seçin (*Diğer* altında listelenir).

| Alan | Ne girilir |
|---|---|
| **Adres** | Panel sunucusu. **https://** anahtarını açık bırakın. Mümkünse panel sertifikasının kapsadığı bir sunucu adı girin; IP adresi, sertifika doğrulamasından ancak sertifika o IP'yi kapsıyorsa geçer |
| **Port** | cPanel için `2087` hazır gelir. Plesk için **Özel Port Kullan**'a basıp `8443` girin |
| **SSL Sertifikasını Doğrula** | Açık bırakın. Yalnızca kendinden imzalı sertifikayla çalışan bir panel için kapatın |
| **Kullanıcı Adı** | O paneldeki bayi kullanıcı adınız |
| **Parola** | **cPanel:** o bayi olarak oluşturulmuş bir WHM API token. **Plesk:** bir gizli anahtar ya da panel şifresi |
| **Hostname** | Panel sunucusunun sunucu adı; WiseCP bunu sunucu ve hizmet kayıtlarında da gösterir. `server.example.com` gibi tam bir sunucu adıysa modül **Adres** yerine buna bağlanır; böylece **Adres**'te IP olsa bile sertifika doğrulaması çalışır |

![WiseCP 5'te sunucu ayarları](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Bağlantıyı test edin

**Bağlantıyı Test Et**'e basın. Yeşil sonuç kimlik bilgilerini ve tespit edilen paneli doğrular;
başarısızlıkta HTTP kodu ya da panelin kendi hatası görünür. Bkz. [Kayıtlar ve sorun giderme](#-kayıtlar-ve-sorun-giderme).

### 4️⃣ Sunucu grubu oluşturun *(isteğe bağlı)*

Birden fazla sunucunuz varsa **Sunucu Grupları** altında bir grup oluşturun ve ürünü tek bir sunucu
yerine gruba bağlayın.

> ⚠️ **Bir grubu panel bazında tek tip tutun.** Ürün formundaki paket listesi tek bir sunucudan gelir.
> Grupta cPanel ve Plesk karışıksa seçtiğiniz ad diğer panelde olmayabilir ve oraya düşen sipariş
> *paket bulunamadı* hatası verir. Modül panelin kimliğini değil paketin **adını** saklar; grubun her
> sunucusunda bulunan bir ad hepsinde çalışır.

### 5️⃣ Ürünü yapılandırın

Hosting ürününü oluşturun ve **Modül Ataması** alanında **DNA Reseller Hosting**'i seçin. Ürünün
**Otomasyon** sekmesinde **Tek Sunucu**'yu (ya da bir sunucu grubunu) ve sunucuyu seçin. Modül tek bir
alan çizer:

| Ayar | Değer |
|---|---|
| **Paket / Plan** | Sunucudan canlı çekilen liste; tespit edilen panel etiketin içinde yazar, örneğin *Paket / Plan (cPanel / WHM)*. Sunucuya ulaşılamazsa alan metin kutusuna döner ve sebebi altında görünür |

> 💡 **cPanel'deki paket önekini dert etmeyin.** Panelde `reseller_basic` olarak görünen bir paket
> `basic` olarak da girilebilir; modül öneki kendisi çözer.

> ℹ️ Aynı sekmedeki **Kaynak Limitleri** panele gönderilmez. Hesabın disk, trafik ve diğer limitleri
> seçtiğiniz paketten ya da plandan gelir; WiseCP bunu hizmet ekranında da belirtir.

**Kaydedin — modül kullanıma hazır.** 🎉

---

## 🔍 Kayıtlar ve sorun giderme

WiseCP'de modül kayıtları açıkken modülün panele gönderdiği her istek ve aldığı yanıt **modül
kaydına** yazılır. Her satır `createacct` ya da `webspace.add` gibi panel çağrısının adıyla
işaretlenir.

> 🔐 Sunucunun token'ı ya da şifresi, modülün ürettiği ya da değiştirdiği her hesap şifresi ve tek
> oturum açma jetonları **hiçbir şey yazılmadan önce** `***` ile maskelenir; istekte de yanıtta da.

### Sık görülen hatalar

| Belirti | Sebep ve çözüm |
|---|---|
| `HTTP 403` ya da *sunucu WHM yerine cPanel kullanıcı API'si ile yanıt verdi* | Token'ın sahibi olan bayinin o çağrı için WHM yetkisi yok. **WHM → Resellers → Edit Reseller's ACL List** altında hesap listeleme, açma, askıya alma, sonlandırma, şifre, paket değiştirme, paket listeleme, trafik ve oturum yetkilerini verin; token'ı **o bayi olarak** oluşturun |
| Sertifika hatası | Sertifika girdiğiniz adresi kapsamıyor. Sertifikanın kapsadığı sunucu adını girin ya da kendinden imzalı bir panel için **SSL Sertifikasını Doğrula**'yı kapatın |
| Plesk **11003** | Gizli anahtar başka bir IP için üretilmiş. WiseCP'nin bağlandığı adres için yenisini oluşturun ya da panel şifresini kullanın |
| *Güvenlik nedeniyle reddedildi: … aboneliği bu hizmet tarafından oluşturulmamış* | Plesk'te modül yalnızca o hizmet için kendi açtığı müşteriler üzerinde işlem yapar. Abonelik elle ya da başka bir hizmet tarafından açılmış |

---

## 🧩 Bilinmesi gerekenler

<details>
<summary><b>Yeniden denemeler güvenli</b></summary>

<br>

WiseCP'nin modül kuyruğu başarısız bir hesap açmayı yeniden çalıştırır. Modül kullanıcı adını ve
şifreli parolayı panel çağrısından önce kaydeder, böylece yeniden deneme aynılarını kullanır: açılmış
ama yanıtı kaybolmuş bir hesap *zaten var* hatasıyla düşmek yerine devralınır. Zaten silinmiş bir
hesabın sonlandırılması tamamlanmış sayılır.

</details>

<details>
<summary><b>Panel farkları</b></summary>

<br>

- **Plesk'te her işlemde sahiplik doğrulanır.** Modülün açtığı müşteri, hizmet numarasını external-id
  olarak taşır ve her işlemden önce bu etiket kontrol edilir. Modül elle açılmış bir abonelik
  üzerinde asla işlem yapmaz.
- **Canlı bir Plesk hizmetinin alan adını değiştirmek** panele taşınmaz: Plesk aboneliği alan adıyla
  bulunur, bu yüzden sonraki işlemler abonelik bulunamadı der. Aboneliği Plesk'te de yeniden
  adlandırın. cPanel'de hesap, panelde değiştirene kadar eski alan adını sunmaya devam eder.
- **Mükerrer alan adı koruması.** Aynı alan adı aynı sunucuda başka bir aktif ya da askıdaki hizmete
  bağlıyken sonlandırma reddedilir.

</details>

<details>
<summary><b>Panel tespiti</b></summary>

<br>

Panel tipi hiç yapılandırılmaz. Modül ilk çağrıda sunucuyu yoklar ve hangi panelin yanıt verdiğini bir
hafta hatırlar; **Bağlantıyı Test Et** her seferinde yeniden yoklar.

Port yalnızca hangi panelin **önce** deneneceğine karar verir: `8443` ve `8880` önce Plesk'i, diğer
her şey önce cPanel'i dener. Yanlış tahmin tespiti yavaşlatır, bozmaz. Tespit sırasında modül,
girilen kimlik bilgisini **aynı** sunucudaki diğer panelin API ucuna da gönderir; başka hiçbir
sunucuya bağlanmaz.

</details>

<details>
<summary><b>Kimlik bilgileri</b></summary>

<br>

İki panel de kimlik bilgisini WiseCP'nin şifreli sakladığı **Parola** alanından alır. **Erişim
Anahtarı (Access Hash)** alanı düz metin saklandığı için bu modül onu sunmaz ve hiç okumaz. Plesk'te
modül kimlik bilgisini önce gizli anahtar olarak dener, olmazsa kullanıcı adı ve şifreye kendisi geçer.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

WiseCP 3 paketi (`v3/coremio/`) aynı hesap yönetimini WiseCP 3'ün kendi ekranlarında sunar:

1. **Ürünler / Hizmetler → Hosting/Sunucu → Paylaşımlı Sunucu Ayarları → Yeni Paylaşımlı Sunucu
   Ekle.** **Sunucu Otomasyon Türü** olarak `DNAHosting` seçin; **IP Adresi**, **Kullanıcı Adı**,
   **Şifre** (WHM API token, Plesk gizli anahtarı ya da Plesk şifresi), **SSL ile Bağlan** ve **Port**
   (cPanel için `2087`, Plesk için `8443`) alanlarını doldurun. Oradaki **Hostname** alanı yalnızca bir
   etikettir; modül **IP Adresi**'ne bağlanır. **Bağlantıyı Test Et**'e basın.
2. **Ürünler / Hizmetler → Hosting/Sunucu → Web Hosting Paketleri** → paketi açın → **Modül
   Ayarları** sekmesi. Sunucuyu ya da grubu seçin; modül **Tespit edilen panel** bilgisini ve
   **Paket / Plan** listesini gösterir.

WiseCP 5 paketinden farkları:

- WiseCP 3'te sunucu modülleri için sertifika doğrulama anahtarı yoktur. Sunucunun **Hostname**
  alanı, panel sertifikasının kapsadığı tam bir sunucu adıysa paket ona bağlanır ve sertifikayı
  doğrular; yalnızca IP adresi varsa IP'ye doğrulamasız bağlanır.
- Müşteri panelinde disk ve trafik kullanımı ile panele giriş düğmesi görünür; WiseCP 5'in hizmet
  genel bakış kartları WiseCP 3'te yoktur.
- Canlı bir hizmetin alan adını değiştirmek Plesk'te operatöre bırakılmak yerine reddedilir.

</details>

<details>
<summary><b>Kapsam dışı</b></summary>

<br>

E-posta, veritabanı ve dosya yönetimi araçları, bayi hesabı satışı, mevcut hesapların WiseCP'ye içe
aktarılması ve *sunucu yöneticisi olarak giriş* düğmesi. Modül yalnızca bir **bayi** kimlik bilgisi
tutar, root değil. İçe aktarma bilerek dışarıda: içe aktarılan bir Plesk aboneliği bu modülün sahiplik
etiketini taşımaz ve üzerindeki sonraki her işlem reddedilir.

</details>

---

## 📄 Değişiklik günlüğü

Sürüm sürüm değişiklikler [CHANGELOG.md](CHANGELOG.md) dosyasında.

---

<div align="center">

**DNA Reseller Hosting** · WiseCP için cPanel & Plesk bayi modülü

[domainnameapi.com](https://www.domainnameapi.com) · [Bayi paneli](https://dm.domainnameapi.com/hosting)

</div>
