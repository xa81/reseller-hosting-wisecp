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

**cPanel və Plesk diler hesablarını tək bir WiseCP server modulundan idarə edin.**

Bir modul, iki panel. Panel tipini siz seçmirsiniz — modul serverdən özü soruşur və hansı panelin
cavab verdiyini yadda saxlayır.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-d%C9%99st%C9%99kl%C9%99nir-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-d%C9%99st%C9%99kl%C9%99nir-53BCE6?style=flat-square)

</div>

---

## 📑 Mündəricat

- [✨ Nə edir](#-nə-edir)
- [📋 Tələblər](#-tələblər)
- [🚀 Quraşdırma](#-quraşdırma)
- [🔍 Loglar və problemlərin həlli](#-loglar-və-problemlərin-həlli)
- [🧩 Bilinməsi lazım olanlar](#-bilinməsi-lazım-olanlar)
- [📄 Dəyişiklik jurnalı](#-dəyişiklik-jurnalı)

---

## ✨ Nə edir

| Xüsusiyyət | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Bağlantı testi və panelin avtomatik təyini | ✅ | ✅ |
| Hesab yaradılması (təkrar cəhd təhlükəsizdir) | ✅ | ✅ |
| Dayandırma / bərpa | ✅ | ✅ |
| Ləğv etmə | ✅ | ✅ *(sahiblik yoxlanılır)* |
| Şifrə dəyişikliyi | ✅ | ✅ |
| Paket / plan dəyişikliyi (yüksəltmə və xidmət ekranı) | ✅ | ✅ |
| Xidmətin ümumi baxışında disk və trafik göstəriciləri | ✅ | ✅ |
| İstifadəyə görə hesablaşma üçün disk və trafik istifadəsi | ✅ | ✅ |
| İdarəetmə panelinə bir kliklə giriş | ✅ | ✅ |

> 💡 **Dilerlər üçün hazırlanıb.** Root və ya admin girişi tələb etmir; modul öz diler hesabınızın
> səlahiyyətləri ilə işləyir və yaratdığı hər hesab sizin kvotanızdan sayılır.

---

## 📋 Tələblər

- **WiseCP 5** və ya **WiseCP 3**, öz serverinizdə quraşdırılmış, administrator girişi ilə; hər birinin öz paketi var
- **PHP** WiseCP 5-də 8.2 və ya daha yeni, WiseCP 5-in tələb etdiyi kimi; WiseCP 3-də 7.4 – 8.4
- PHP genişləndirmələri: WiseCP 5-in onsuz da tələb etdikləri, üstəlik `simplexml` (standart PHP quruluşlarında aktivdir)
- **cPanel/WHM diler hesabı** (WHM API tokeni) &nbsp;və ya&nbsp; **Plesk diler hesabı**
  (gizli açar və ya panel şifrəsi)
- WiseCP serverindən panel serverinə panelin API portu üzərindən çıxış imkanı

> ✅ Heç bir verilənlər bazası cədvəli yaradılmır, cron işi tələb olunmur və composer asılılıqları
> yoxdur. Konfiqurasiya etdiyiniz panel serverindən başqa heç yerə heç nə göndərilmir.

---

## 🚀 Quraşdırma

### 1️⃣ Modulu yükləyin

Hər buraxılış iki paket daşıyır. WiseCP versiyanıza uyğun olanı seçin:

| WiseCP | Paket | Bu repozitoriyadakı mənbə |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **Paketlər bir-birini əvəz edə bilməz.** WiseCP 5 server modulu müqaviləsini dəyişdi, buna görə
> WiseCP 3 modulu WiseCP 5-də yüklənmir və əksinə.

Paketi modul menecerindən yükləyin və ya onu WiseCP quraşdırmanızın kök qovluğuna
açın. Arxiv tam yolu daşıyır, buna görə modul bura yerləşir:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← bura
```

Aşağıdakı addımlar **WiseCP 5** üçündür. WiseCP 3-də ekranların adları fərqlidir; *Bilinməsi
lazım olanlar* bölməsindəki [WiseCP 3](#-bilinməsi-lazım-olanlar) hissəsinə baxın.

### 2️⃣ Serveri əlavə edin

**Products → Hosting/Server → Server Settings → Add New Server.** **Server Automation Module** bölməsində,
**Web Hosting** tabında **DNA Reseller Hosting**-i seçin (*Other* altında göstərilir).

| Sahə | Nə daxil etməli |
|---|---|
| **Address** | Panel serveri. **https://** açarını aktiv saxlayın. Panelin sertifikatının əhatə etdiyi host adına üstünlük verin; IP ünvanı sertifikat yoxlamasından yalnız sertifikat həmin IP-ni əhatə etdikdə keçir |
| **Port** | cPanel üçün `2087` hazır doldurulur. Plesk üçün **Use Custom Port** düyməsinə basın və `8443` daxil edin |
| **Verify SSL Certificate** | Aktiv saxlayın. Yalnız öz-özünə imzalanmış sertifikatla işləyən panel üçün söndürün |
| **Username** | Həmin paneldəki diler istifadəçi adınız |
| **Password** | **cPanel:** həmin diler kimi yaradılmış WHM API tokeni. **Plesk:** gizli açar və ya panel şifrəsi |
| **Hostname** | Panel serverinin host adı; WiseCP onu server və xidmət qeydlərində də göstərir. `server.example.com` kimi tam host adı olduqda modul **Address** əvəzinə ona qoşulur, beləliklə **Address**-də IP olsa belə sertifikat yoxlaması işləyir |

![WiseCP 5-də server parametrləri](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Bağlantını yoxlayın

**Test Connection** düyməsini basın. Yaşıl nəticə giriş məlumatlarını və təyin edilmiş paneli
təsdiqləyir; uğursuzluq halında HTTP kodu və ya panelin öz xətası göstərilir. Bax:
[Loglar və problemlərin həlli](#-loglar-və-problemlərin-həlli).

### 4️⃣ Server qrupu yaradın *(istəyə bağlı)*

Birdən çox serveriniz varsa, **Server Groups** altında qrup yaradın və məhsulu tək bir server
əvəzinə qrupa bağlayın.

> ⚠️ **Qrupu panelə görə eynicinsli saxlayın.** Məhsul formasındakı paket siyahısı bir serverdən
> gəlir. Qrupda cPanel və Plesk qarışıqdırsa, seçdiyiniz ad digər paneldə mövcud olmaya bilər və ora
> düşən sifariş *paket tapılmadı* xətası ilə uğursuz olur. Modul panelin id-sini deyil, paketin
> **adını** saxlayır, buna görə qrupun bütün serverlərində mövcud olan ad hamısında işləyir.

### 5️⃣ Məhsulu tənzimləyin

Hostinq məhsulunu yaradın və **Module Assignment** bölməsində **DNA Reseller Hosting**-i seçin. Məhsulun
**Automation** tabında **Single Server**-i (və ya server qrupunu) və serveri seçin. Bundan sonra modul
bir sahə çəkir:

| Parametr | Dəyər |
|---|---|
| **Package / Plan** | Serverdən canlı çəkilən siyahı; təyin edilmiş panel etiketdə göstərilir, məsələn *Package / Plan (cPanel / WHM)*. Serverə çatmaq mümkün olmadıqda sahə mətn qutusuna çevrilir və səbəb onun altında göstərilir |

> 💡 **cPanel-də paket prefiksindən narahat olmayın.** Paneldə `reseller_basic` kimi görünən paket
> `basic` kimi daxil edilə bilər; modul prefiksi özü həll edir.

> ℹ️ Eyni tabdakı **Resource Limits** panelə göndərilmir. Hesabın disk, trafik və digər limitləri
> seçdiyiniz paketdən və ya plandan gəlir; WiseCP bunu xidmət ekranında da bildirir.

**Yadda saxlayın — modul istifadəyə hazırdır.** 🎉

---

## 🔍 Loglar və problemlərin həlli

WiseCP-də modul loqlaması aktiv olduğu müddətdə modulun panelə göndərdiyi hər sorğu və panelin
qaytardığı cavab **modul loguna** yazılır. Hər qeyd `createacct` və ya `webspace.add` kimi panel
çağırışı ilə işarələnir.

> 🔐 Serverin tokeni və ya şifrəsi, modulun yaratdığı və ya dəyişdirdiyi hər hesab şifrəsi və tək
> giriş (SSO) sessiya tokenləri həm sorğuda, həm də cavabda **heç nə yazılmazdan əvvəl** `***` ilə
> maskalanır.

### Tez-tez rast gəlinən xətalar

| Əlamət | Səbəb və həll |
|---|---|
| `HTTP 403` və ya *server cPanel istifadəçi API-si ilə cavab verdi* | Tokenin sahibi olan dilerin həmin çağırış üçün WHM səlahiyyəti yoxdur. **WHM → Resellers → Edit Reseller's ACL List** bölməsində hesabların siyahısı, yaradılma, dayandırma, ləğv etmə, şifrə, paket dəyişikliyi, paketlərin siyahısı, trafik və sessiya səlahiyyətlərini verin, sonra tokeni **həmin diler kimi** yaradın |
| Sertifikat xətası | Sertifikat daxil etdiyiniz ünvanı əhatə etmir. Sertifikatın əhatə etdiyi host adını daxil edin və ya öz-özünə imzalanmış sertifikatlı panel üçün **Verify SSL Certificate**-i söndürün |
| Plesk **11003** | Gizli açar başqa IP üçün verilib. WiseCP-nin qoşulduğu ünvan üçün yenisini yaradın və ya panel şifrəsindən istifadə edin |
| *Təhlükəsizlik üçün rədd edildi: abunəlik … bu xidmət tərəfindən yaradılmayıb* | Plesk-də modul yalnız həmin xidmət üçün özünün yaratdığı müştərilər üzərində əməliyyat aparır. Abunəlik əl ilə və ya başqa xidmət tərəfindən yaradılıb |

---

## 🧩 Bilinməsi lazım olanlar

<details>
<summary><b>Təkrar cəhdlər təhlükəsizdir</b></summary>

<br>

WiseCP-nin modul növbəsi uğursuz yaradılmanı yenidən işə salır. Modul paneli çağırmazdan əvvəl
istifadəçi adını və şifrələnmiş şifrəni saxlayır, buna görə təkrar cəhd onlardan yenidən istifadə
edir: yaradılmış, lakin cavabı itmiş hesab *artıq mövcuddur* xətası ilə uğursuz olmaq əvəzinə
mənimsənilir. Artıq mövcud olmayan hesabın ləğvi tamamlanmış sayılır.

</details>

<details>
<summary><b>Panel fərqləri</b></summary>

<br>

- **Hər Plesk əməliyyatında sahiblik yoxlanılır.** Modulun yaratdığı müştəri xidmət nömrəsini xarici
  id kimi daşıyır və bu işarə istənilən əməliyyatdan əvvəl yoxlanılır. Modul heç vaxt əl ilə
  yaradılmış abunəlik üzərində əməliyyat apara bilməz.
- **Aktiv Plesk xidmətinin domeninin dəyişdirilməsi** panelə ötürülmür: Plesk abunəliyi domeninə
  görə tapılır, buna görə sonrakı əməliyyatlar abunəliyin mövcud olmadığını bildirəcək. Abunəliyin
  adını Plesk-də də dəyişin. cPanel-də isə hesab siz paneldə dəyişənə qədər köhnə domenə xidmət
  göstərməyə davam edir.
- **Domen təkrarlanmasından qorunma.** Eyni domen eyni serverdə başqa aktiv və ya dayandırılmış
  xidmətə bağlı olduğu müddətdə ləğv etmə rədd edilir.

</details>

<details>
<summary><b>Panel təyini</b></summary>

<br>

Panel tipi heç vaxt konfiqurasiya edilmir. İlk çağırışda modul serveri yoxlayır və hansı panelin
cavab verdiyini bir həftəlik yadda saxlayır; **Test Connection** isə hər dəfə yenidən yoxlayır.

Port yalnız hansı panelin **ilk** sınanacağını müəyyən edir: `8443` və `8880` əvvəlcə Plesk-i,
qalan hər şey əvvəlcə cPanel-i sınayır. Yanlış təxmin təyini yavaşladır, pozmur. Təyin zamanı
modul daxil edilmiş giriş məlumatını **eyni** serverdəki digər panelin API ünvanına da göndərir;
başqa heç bir hosta qoşulmur.

</details>

<details>
<summary><b>Giriş məlumatları</b></summary>

<br>

Hər iki panel giriş məlumatını WiseCP-nin şifrələnmiş saxladığı **Password** sahəsindən alır.
**Access Hash** sahəsi isə açıq mətn kimi saxlanılır, buna görə bu modul onu təklif etmir və heç
vaxt oxumur. Plesk-də modul giriş məlumatını əvvəlcə gizli açar kimi sınayır və özü giriş adı və
şifrəyə keçir.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

WiseCP 3 paketi (`v3/coremio/`) eyni hesab idarəetməsini WiseCP 3-ün öz ekranlarında təqdim edir:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.**
   **Server Automation Type** olaraq `DNAHosting` seçin və **IP Address**, **Username**, **Password**
   (WHM API token, Plesk gizli açarı və ya Plesk şifrəsi), **Connect with SSL** və **Port**
   (cPanel üçün `2087`, Plesk üçün `8443`) sahələrini doldurun. Oradakı **Hostname** sahəsi sadəcə
   etiketdir; modul **IP Address**-ə qoşulur. **Test Connection** düyməsini basın.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → paketi açın → **Module
   Settings** tabı. Serveri və ya qrupu seçin; modul **Detected panel** məlumatını və
   **Package / Plan** siyahısını göstərir.

WiseCP 5 paketindən fərqləri:

- WiseCP 3-də server modulları üçün sertifikat yoxlama açarı yoxdur. Serverin **Hostname** sahəsi
  panel sertifikatının əhatə etdiyi tam host adıdırsa, paket ona qoşulur və sertifikatı yoxlayır;
  yalnız IP ünvanı olduqda IP-yə yoxlamasız qoşulur.
- Müştəri sahəsində disk və trafik istifadəsi, həmçinin panelə giriş düyməsi göstərilir; WiseCP 5-in
  xidmət icmalı kartları WiseCP 3-də yoxdur.
- Aktiv xidmətin domenini dəyişmək Plesk-də operatora buraxılmaq əvəzinə rədd edilir.

</details>

<details>
<summary><b>Əhatə xaricində</b></summary>

<br>

E-poçt, verilənlər bazası və fayl idarəetmə alətləri, diler hesablarının satışı, mövcud hesabların
WiseCP-yə idxalı və *server administratoru kimi daxil ol* düyməsi. Modul yalnız **diler** giriş
məlumatını saxlayır, heç vaxt root-u yox. İdxal qəsdən kənarda saxlanılıb: idxal edilmiş Plesk
abunəliyi bu modulun sahiblik işarəsini daşımayacaq və onun üzərində sonrakı hər əməliyyat rədd
ediləcək.

</details>

---

## 📄 Dəyişiklik jurnalı

Versiyadan versiyaya dəyişikliklər [CHANGELOG.md](CHANGELOG.md) faylındadır.

---

<div align="center">

**DNA Reseller Hosting** · WiseCP üçün cPanel və Plesk diler modulu

[domainnameapi.com](https://www.domainnameapi.com) · [Diler paneli](https://dm.domainnameapi.com/hosting)

</div>
