<h1 align="center">DNA Reseller Hosting</h1>

<p align="center">
  <strong>Tek WiseCP sunucu modülüyle hem cPanel/WHM hem Plesk üzerinden paylaşımlı hosting satın.</strong><br>
  Bir modül, iki panel — panel tipini siz seçmezsiniz, modül kendisi bulur.
</p>

<p align="center">
  <img alt="WiseCP" src="https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="cPanel/WHM" src="https://img.shields.io/badge/cPanel%2FWHM-destekleniyor-FF6C2C?style=flat-square">
  <img alt="Plesk" src="https://img.shields.io/badge/Plesk-destekleniyor-53BCE6?style=flat-square">
  <img alt="Lisans" src="https://img.shields.io/badge/lisans-özel-lightgrey?style=flat-square">
</p>

<p align="center">
  <strong>Türkçe</strong>
  · <a href="README.en.md">English</a>
  · <a href="README.de.md">Deutsch</a>
  · <a href="README.ru.md">Русский</a>
  · <a href="README.az.md">Azərbaycan</a>
  · <a href="README.ar.md">العربية</a>
  · <a href="README.es.md">Español</a>
  · <a href="README.fr.md">Français</a>
</p>

---

## İçindekiler

- [Genel bakış](#genel-bakış)
- [Özellik matrisi](#özellik-matrisi)
- [Gereksinimler](#gereksinimler)
- [Kurulum](#kurulum)
- [Yapılandırma](#yapılandırma)
  - [Adım 1 — Sunucu ekleme](#adım-1--sunucu-ekleme)
  - [Adım 2 — Sunucu grupları (isteğe bağlı)](#adım-2--sunucu-grupları-isteğe-bağlı)
  - [Adım 3 — Ürünü tanımlama](#adım-3--ürünü-tanımlama)
- [Sorun giderme](#sorun-giderme)
- [Loglar](#loglar)
- [Değişiklik günlüğü](#değişiklik-günlüğü)
- [Lisans](#lisans)

---

## Genel bakış

Tek bir sunucu kaydı üzerinden iki panel ailesini de sürer. IP, bayi kullanıcı adı ve bir kimlik
bilgisi girersiniz; modül sunucuyu gerçekten sorgular — tahmin değil, gerçek bir API çağrısı — ve
hangi panelin yanıt verdiğini hatırlar.

| | |
|---|---|
| **Modül türü** | WiseCP sunucu (Servers) modülü |
| **Klasör adı** | `DNAHosting` |
| **Sürüm** | 1.0.0 |
| **Desteklenen paneller** | cPanel/WHM, Plesk |
| **PHP** | 7.4 – 8.4 |
| **Arayüz dilleri** | Türkçe, English (`lang/tr.php`, `lang/en.php`) |

---

## Özellik matrisi

| İşlem | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Bağlantı testi ve otomatik panel tespiti | ✔ | ✔ |
| Hesap oluşturma | ✔ | ✔ |
| Askıya alma / askıdan indirme | ✔ | ✔ |
| Sonlandırma | ✔ | ✔ (sahiplik doğrulamalı) |
| Şifre değiştirme | ✔ | ✔ |
| Paket / plan değiştirme | ✔ | ✔ |
| Disk ve trafik kullanımı (müşterinin hizmet sayfasında) | ✔ | ✔ |
| Tek tıkla panel girişi — müşteri paneli | ✔ | ✔ |
| Tek tıkla panel girişi — yönetici paneli | ✔ | ✔ |

---

## Gereksinimler

- Yönetici erişiminiz olan, kendi sunucunuzda kurulu bir **WiseCP**
- **cURL** ve **SimpleXML** eklentileri açık bir PHP (neredeyse her varsayılan kurulumda vardır)
- Ya bir **cPanel/WHM bayi hesabı** (WHM API token'ı ile) ya da bir **Plesk bayi hesabı** (API anahtarı
  ya da doğrudan panel şifresiyle)
- WiseCP sunucusundan panel sunucusuna, panelin API portunda dışa açık ağ erişimi

Veritabanı tablosu oluşturulmaz, Composer ile bir şey kurulmaz, derleme adımı yoktur.

---

## Kurulum

Modül klasörünü WiseCP kurulumunuza kopyalayın:

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← klasörün tamamı buraya
```

Kurulum bundan ibarettir. Sonrasında çalıştırılacak bir şey yok — migration yok, önbellek ısıtma yok,
ayrı bir etkinleştirme adımı yok. Modül, sunucu ekleme ekranını bir sonraki açışınızda listede
görünür.

---

## Yapılandırma

### Adım 1 — Sunucu ekleme

**Ürünler / Hizmetler → Hosting/Sunucu → Paylaşımlı Sunucu Ayarları → `Yeni Paylaşımlı Sunucu Ekle`**

Formun **Sunucu Otomasyon Bilgileri** bölümünü doldurun:

| Alan | Ne girilir |
|---|---|
| **Sunucu Otomasyon Türü** | `DNAHosting` — klasör adıdır, listede olduğu gibi görünür |
| **IP Adresi** | Panel sunucusunun gerçek adresi; modül buraya bağlanır |
| **Kullanıcı Adı** | O paneldeki bayi kullanıcı adınız |
| **Şifre** | **cPanel:** WHM API token'ı. **Plesk:** API anahtarı ya da bayinin panel şifresi |
| **SSL ile Bağlan** | İşaretleyin |
| **Port** | cPanel için `2087`, Plesk için `8443` |

Formun üst kısmındaki **Hostname** alanı yalnızca sizin için bir etikettir — modül bağlanmak için onu
değil **IP Adresi** alanını kullanır. Sunucularınız liste ekranında bu etiketle görünür.

### Adım 2 — Sunucu grupları (isteğe bağlı)

Birden fazla sunucunuz varsa **Paylaşımlı Sunucu Ayarları → `Sunucu Grupları`** altında grup
oluşturup ürünü tek bir sunucu yerine gruba bağlayabilirsiniz. Grup düzenleme ekranında iki dağıtım
türü var:

- **Her zaman en düşük doluluktaki sunucuya ekle.**
- **Bir sunucu tamamen dolana kadar ekle. Ardından en düşük doluluktaki sunucuya geç.**

Sunucular **Atanmamış → Atanmış** listeleri arasında `Ekle` / `Kaldır` ile taşınır.

> [!IMPORTANT]
> **Grubu panel bazında homojen tutun.** Ürün formundaki paket listesi o an seçili olan **tek bir**
> sunucudan çekilir. Bir grupta hem cPanel hem Plesk sunucusu varsa seçtiğiniz paket adı diğer panelde
> karşılık bulmayabilir ve o sunucuya düşen sipariş "paket bulunamadı" ile başarısız olur.

### Adım 3 — Ürünü tanımlama

**Ürünler / Hizmetler → Hosting/Sunucu → Web Hosting Paketleri** → paketi açın → **Modül Ayarları**
sekmesi.

**Sunucu Seçimi** altında **Tekil Sunucu** ya da **Sunucu Grubu** seçip DNAHosting sunucunuzu (veya
grubunuzu) işaretleyin. Seçim yapıldığı anda modül kendi alanlarını çizer:

| Alan | Anlamı |
|---|---|
| **Tespit edilen panel** | Modülün o sunucuda gerçekten bulduğu panel — örneğin `cPanel / WHM`. Tespitin çalıştığını burada görürsünüz; bir sorun varsa bu satırda hata metni belirir. |
| **Paket / Plan** | O sunucudan canlı çekilen paket listesi |
| **Otomatik Kurulum** | Açıkken sipariş otomatik kurulur; kapalıyken yönetici onayı gerekir |

Paket listesi panele göre gelir:

- **cPanel:** sunucunun `listpkgs` çıktısındaki her paket. Paketleriniz alışıldık bayi ön ekini
  taşıyorsa (örneğin `bakcay328_paket1`) modül ön eki kendisi çözer.
- **Plesk:** sunucuda tanımlı her servis planı.

Paketi seçin, formun geri kalanını her zamanki gibi doldurup kaydedin. Ürün artık satılabilir —
sipariş verildiğinde tam hesap açma akışı yapılandırdığınız sunucuya karşı çalışır.

---

## Sorun giderme

| Belirti | Sebep | Çözüm |
|---|---|---|
| Bir çağrı (çoğunlukla bağlantı testi) `HTTP 403` ile düşüyor ya da hata metninde `cpanelresult` zarfı geçiyor | Token'ın arkasındaki bayi hesabının o fonksiyon için WHM düzeyinde yetkisi yok; WHM, WHM API 1 yerine cPanel **kullanıcı** API'siyle yanıt verdi | WHM'de **Resellers → Edit Reseller's ACL List**'i açıp bayiye modülün kullandığı yetkileri verin: hesap listeleme ve özeti, hesap oluşturma, askıya alma, sonlandırma, şifre değiştirme, paket yükseltme, paket listeleme, trafik okuma ve oturum oluşturma. Ardından token'ı, **o bayi olarak giriş yapmışken** **WHM → Development → Manage API Tokens**'tan yeniden üretin — cPanel arayüzünden üretilen token WHM erişimi taşımaz |
| `Plesk (11003)` | API anahtarı, WiseCP'nin bağlandığı IP'den başka bir adres için üretilmiş | Doğru IP için Plesk sunucusunda yeni anahtar üretin ya da Şifre alanına panel şifresini yazın |
| `Plesk (1014)` | Plesk istek gövdesini reddetti — bir eleman eksik ya da bu sunucunun konuştuğu XML-API sürümü için yanlış yerde | Modülün güncel sürümünü kullandığınızı doğrulayın; modül logu Plesk'in tam olarak hangi elemana itiraz ettiğini gösterir |
| Ürün formunda paket listesi yerine hata metni | Tespit ya da paket çağrısı başarısız oldu; sebep aynı satırda yazılıdır | Metindeki somut hataya göre yukarıdaki satırlardan birini uygulayın |

Diğer her HTTP hatası, panelin yanıt gövdesinden çıkarılmış düz metin bir özetle gelir; çıplak bir durum
kodu hiçbir zaman hikâyenin tamamı değildir. Tam istek ve yanıt için modül loguna bakın.

---

## Loglar

**Araçlar → İşlem Kayıtları (Logs) → Modül İşlem Kayıtları**

Modülün gönderdiği her istek ve aldığı her yanıt, işlem adıyla (örneğin `createacct`, `webspace.add`)
etiketlenerek buraya yazılır. Kayıt yalnızca **Modül İşlem Kayıtları** özelliği açıkken tutulur; o
anahtar aynı sayfanın üst kısmındadır.

> [!NOTE]
> Sunucunun API token'ı/şifresi, modülün ürettiği ya da değiştirdiği hesap şifreleri ve SSO oturum
> jetonları — hem istekte hem yanıtta — yazılmadan önce `***` ile maskelenir.

---

## Değişiklik günlüğü

Sürüm sürüm değişiklikler için [CHANGELOG.md](CHANGELOG.md) dosyasına bakın.

---

## Lisans

Özel. Tüm hakları saklıdır.
