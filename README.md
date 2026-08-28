# DNA Reseller Hosting

**Tek WiseCP sunucu modülüyle hem cPanel/WHM hem Plesk üzerinden paylaşımlı hosting satın.**

Bir modül, iki panel. Sunucuyu tanımlarsınız; modül o sunucunun cPanel/WHM mi Plesk mi çalıştırdığını
**kendisi bulur** — hiçbir yerde panel tipi seçmezsiniz.

![WiseCP](https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-destekleniyor-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-destekleniyor-53BCE6?style=flat-square)
![Lisans](https://img.shields.io/badge/lisans-özel-lightgrey?style=flat-square)

---

## Ne yapar

Tek bir sunucu kaydı üzerinden iki panel ailesini de sürer. IP, bayi kullanıcı adı ve bir kimlik
bilgisi girersiniz; modül sunucuyu gerçekten sorgular — tahmin değil, gerçek bir API çağrısı — ve
hangi panelin yanıt verdiğini hatırlar.

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

Baştan bilinmesi gereken üç koruma:

- **Mükerrer alan adı koruması.** Aynı alan adı aynı sunucuda başka bir aktif ya da askıdaki hizmette
  hâlâ duruyorsa sonlandırma reddedilir. Bir alan adının hostingi, onu kullanan ikinci ve hâlâ canlı
  bir siparişin altından çekilemez.
- **Plesk sahiplik koruması.** Modülün Plesk'te açtığı her hesap dahilî bir kimlikle etiketlenir. Bir
  Plesk aboneliğinde **herhangi bir** işlem yapılmadan önce — askıya alma, askıdan indirme, şifre
  değiştirme, plan değiştirme, kullanım, sonlandırma — bu etiket kontrol edilir ve tutmuyorsa işlem
  reddedilir; modül panelde elle oluşturulmuş bir aboneliğe asla yönlendirilemez. Sonlandırmada önce
  abonelik silinir, müşteri ise ancak başka hiçbir aboneliği kalmadıysa kaldırılır. Bu sayı
  belirlenemezse müşteriye dokunulmaz.
- **Plesk'te alan adı değişikliği reddedilir.** Bir Plesk aboneliği alan adıyla bulunduğundan, mevcut
  bir hizmetin alan adını düzenlemek modülü o aboneliği bir daha bulamaz hâle getirir — sonlandırma
  dahil sonraki her işlem kalıcı olarak başarısız olur. Böyle bir kayıt, "önce Plesk'te aboneliği
  yeniden adlandırın ya da sonlandırıp yeniden oluşturun" diyen bir mesajla reddedilir. cPanel'de alan
  adı düzenlenebilir; hesap, siz panelde değiştirene kadar eski alan adına hizmet vermeye devam eder.

**Kapsam dışı:** e-posta hesabı ve yönlendirme yönetimi, bayi hesabı satışı, mevcut hesapların
WiseCP'ye içe aktarılması ve yöneticide "root paneline giriş" butonu. Modül yalnızca bir **bayi**
kimlik bilgisi tutar — root değil — dolayısıyla açabileceği bir root paneli yoktur.

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

## Sunucu ekleme

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

Kolayca gözden kaçan dört ayrıntı:

- **Port alanı kilitlidir.** Yanındaki **Standart Portu Değiştir** kutusunu işaretlemeden port
  yazamazsınız. Kutu işaretsizken alan modülün varsayılanını gösterir: SSL kapalıyken `2086`, **SSL ile
  Bağlan**'ı işaretlediğinizde `2087`. cPanel için bu kadarı yeter — SSL'i işaretleyin, port kendiliğinden
  2087 olur. **Plesk için `8443` girmeniz gerekir**, yani o kutuyu işaretlemek zorundasınız.
- **Kimlik bilgisi her iki panelde de Şifre alanına yazılır.** WiseCP bu alanı şifreli saklar. Bu modül
  **Erişim Anahtarı (Access Hash)** alanını hiç kullanmaz; alan DNAHosting sunucularında formda zaten
  görünmez.
- **Port yalnızca hangi panelin önce denendiğini belirler.** `8443`/`8880` girildiğinde önce Plesk,
  diğer her değerde önce cPanel denenir — ama karar her zaman gerçek bir API çağrısıyla verilir ve ilk
  tahmin yanıt vermezse diğerine geçilir. Yanlış port tespiti yavaşlatır, bozmaz.
- **`Bağlantıyı Sına`** butonu formu kaydetmeden denemenizi sağlar. Kaydettiğinizde WiseCP zaten
  otomatik bir bağlantı testi çalıştırır. Yeşil sonuç hem kimlik bilgisini hem tespit edilen paneli
  doğrular; hata durumunda somut HTTP kodu ya da panel hatası gösterilir — bkz.
  [Sorun giderme](#sorun-giderme).

---

## cPanel WHM API token'ı — gereken ACL'ler

Şifre alanına koyduğunuz token, onu üreten bayi hesabına verilmiş ACL'lerin ötesine asla geçemez.
Token'ı üretmeden önce o bayinin ACL listesinde şunların açık olduğundan emin olun:

```
list-accts
acct-summary
create-acct
suspend-acct
kill-acct
passwd
upgrade-account
list-pkgs
show-bandwidth
create-user-session
```

Token'ın kendisini, **o bayi olarak giriş yapmışken** **WHM → Development → Manage API Tokens**
üzerinden üretin. cPanel'in kendi arayüzünden (WHM'den değil) üretilen bir token, hesabın ACL'leri ne
olursa olsun WHM erişimi taşımaz.

---

## Plesk API anahtarı — herkesin takıldığı yer

Bir Plesk API anahtarı **üretildiği IP adresine bağlıdır.** Anahtarı kendi bilgisayarınızda üretir ya
da başka bir sunucudan kopyalarsanız, WiseCP sunucunuz onu kullanmaya kalktığı anda kimlik doğrulama
başarısız olur. Bu hata **`Plesk (11003)`** olarak raporlanır.

Anahtarı **Plesk sunucusunun kendisinde**, WiseCP'nin bağlanacağı adres için (WiseCP sunucusunun dışa
çıkan IP'si — panelin kendi IP'si değil) **Tools & Settings → API keys** üzerinden üretin.

Bu zahmetliyse anahtarı tümüyle atlayın ve bayi hesabınızın **panel şifresini** Şifre alanına yazın.
Modül kimlik bilgisini önce API anahtarı olarak dener; tutmazsa kendiliğinden HTTP basic auth'a düşer.
Hangisini kullandığınızı ona söylemeniz gerekmez.

---

## Sunucu grupları (isteğe bağlı)

Birden fazla sunucunuz varsa **Paylaşımlı Sunucu Ayarları → `Sunucu Grupları`** altında grup
oluşturup ürünü tek bir sunucu yerine gruba bağlayabilirsiniz. Grup düzenleme ekranında iki dağıtım
türü var:

- **Her zaman en düşük doluluktaki sunucuya ekle.**
- **Bir sunucu tamamen dolana kadar ekle. Ardından en düşük doluluktaki sunucuya geç.**

Sunucular **Atanmamış → Atanmış** listeleri arasında `Ekle` / `Kaldır` ile taşınır.

> **Grubu panel bazında homojen tutun.** Ürün formundaki paket listesi o an seçili olan **tek bir**
> sunucudan çekilir. Bir grupta hem cPanel hem Plesk sunucusu varsa seçtiğiniz paket adı diğer panelde
> karşılık bulmayabilir ve o sunucuya düşen sipariş "paket bulunamadı" ile başarısız olur.

---

## Ürünü tanımlama

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
| Bir çağrı (çoğunlukla bağlantı testi) `HTTP 403` ile düşüyor ya da hata metninde `cpanelresult` zarfı geçiyor | Token'ın arkasındaki bayi hesabının o fonksiyon için WHM düzeyinde yetkisi yok; WHM, WHM API 1 yerine cPanel **kullanıcı** API'siyle yanıt verdi | WHM'de **Resellers → Edit Reseller's ACL List**'i açıp yukarıdaki ACL'leri verin, ardından token'ı **WHM → Development → Manage API Tokens**'tan o bayi olarak yeniden üretin |
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

Sunucunun API token'ı/şifresi, modülün ürettiği ya da değiştirdiği hesap şifreleri ve SSO oturum
jetonları — hem istekte hem yanıtta — yazılmadan önce `***` ile maskelenir.

---

## Lisans

Özel. Tüm hakları saklıdır.
