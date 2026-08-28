<h1 align="center">DNA Reseller Hosting</h1>

<p align="center">
  <strong>Tək bir WiseCP server modulu ilə həm cPanel/WHM, həm də Plesk üzərindən paylaşılan hostinq satın.</strong><br>
  Bir modul, iki panel — panel tipini siz seçmirsiniz, modul onu özü tapır.
</p>

<p align="center">
  <img alt="WiseCP" src="https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="cPanel/WHM" src="https://img.shields.io/badge/cPanel%2FWHM-dəstəklənir-FF6C2C?style=flat-square">
  <img alt="Plesk" src="https://img.shields.io/badge/Plesk-dəstəklənir-53BCE6?style=flat-square">
  <img alt="Lisenziya" src="https://img.shields.io/badge/lisenziya-xüsusi-lightgrey?style=flat-square">
</p>

<p align="center">
  <a href="README.md">Türkçe</a>
  · <a href="README.en.md">English</a>
  · <a href="README.de.md">Deutsch</a>
  · <a href="README.ru.md">Русский</a>
  · <strong>Azərbaycan</strong>
  · <a href="README.ar.md">العربية</a>
  · <a href="README.es.md">Español</a>
  · <a href="README.fr.md">Français</a>
</p>

---

## Mündəricat

- [Ümumi baxış](#ümumi-baxış)
- [Funksiya matrisi](#funksiya-matrisi)
- [Tələblər](#tələblər)
- [Quraşdırma](#quraşdırma)
- [Konfiqurasiya](#konfiqurasiya)
  - [Addım 1 — Server əlavə etmək](#addım-1--server-əlavə-etmək)
  - [Addım 2 — Server qrupları (istəyə bağlı)](#addım-2--server-qrupları-istəyə-bağlı)
  - [Addım 3 — Məhsulun təyin edilməsi](#addım-3--məhsulun-təyin-edilməsi)
- [Nasazlıqların aradan qaldırılması](#nasazlıqların-aradan-qaldırılması)
- [Loglar](#loglar)
- [Dəyişiklik jurnalı](#dəyişiklik-jurnalı)
- [Lisenziya](#lisenziya)

---

## Ümumi baxış

Modul hər iki panel ailəsini vahid bir server qeydi üzərindən idarə edir. IP-ni, diler istifadəçi adını
və bir giriş məlumatını daxil edirsiniz; modul serveri həqiqətən sorğulayır — təxmin deyil, real bir API
çağırışı — və hansı panelin cavab verdiyini yadda saxlayır.

| | |
|---|---|
| **Modul tipi** | WiseCP server (Servers) modulu |
| **Qovluq adı** | `DNAHosting` |
| **Versiya** | 1.0.0 |
| **Dəstəklənən panellər** | cPanel/WHM, Plesk |
| **PHP** | 7.4 – 8.4 |
| **İnterfeys dilləri** | Türk, İngilis (`lang/tr.php`, `lang/en.php`) |

---

## Funksiya matrisi

| Əməliyyat | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Bağlantı testi və avtomatik panel təyini | ✔ | ✔ |
| Hesab yaratmaq | ✔ | ✔ |
| Dayandırmaq / bərpa etmək | ✔ | ✔ |
| Ləğv etmək | ✔ | ✔ (sahiblik yoxlanışı ilə) |
| Şifrə dəyişmək | ✔ | ✔ |
| Paket / plan dəyişmək | ✔ | ✔ |
| Disk və trafik istifadəsi (müştərinin xidmət səhifəsində) | ✔ | ✔ |
| Bir kliklə panelə giriş — müştəri paneli | ✔ | ✔ |
| Bir kliklə panelə giriş — admin paneli | ✔ | ✔ |

---

## Tələblər

- Admin girişiniz olan, öz serverinizdə qurulmuş bir **WiseCP**
- **cURL** və **SimpleXML** genişlənmələri aktiv olan PHP (demək olar ki, hər standart quraşdırmada var)
- Ya bir **cPanel/WHM diler hesabı** (WHM API tokeni ilə), ya da bir **Plesk diler hesabı** (API açarı
  və ya birbaşa panel şifrəsi ilə)
- WiseCP serverindən panel serverinə, panelin API portu üzərindən çıxış şəbəkə girişi

Verilənlər bazasında cədvəl yaradılmır, Composer ilə heç nə quraşdırılmır, build addımı yoxdur.

---

## Quraşdırma

Modul qovluğunu WiseCP quraşdırmanıza kopyalayın:

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← qovluğun hamısı bura
```

Quraşdırma bundan ibarətdir. Sonrasında icra ediləcək bir şey yoxdur — miqrasiya yoxdur, keş qızdırması
yoxdur, ayrıca aktivləşdirmə addımı yoxdur. Modul, server əlavə etmə ekranını növbəti dəfə açanda
siyahıda görünür.

---

## Konfiqurasiya

> Aşağıdakı menyu yolları WiseCP-nin ingilis dilli interfeysinə görədir.

### Addım 1 — Server əlavə etmək

**Products / Services → Hosting/Server → Shared Server Settings → `Add New Shared Server`**

Formanın **Server Automation Information** bölməsini doldurun:

| Sahə | Nə daxil edilir |
|---|---|
| **Server Automation Type** | `DNAHosting` — bu, qovluq adıdır, siyahıda olduğu kimi görünür |
| **IP Address** | Panel serverinin real ünvanı; modul bura qoşulur |
| **Username** | Həmin paneldəki diler istifadəçi adınız |
| **Password** | **cPanel:** WHM API tokeni. **Plesk:** API açarı və ya dilerin panel şifrəsi |
| **Connect with SSL** | İşarələyin |
| **Port** | cPanel üçün `2087`, Plesk üçün `8443` |

Formanın yuxarısındakı **Hostname** sahəsi yalnız sizin üçün bir etiketdir — modul qoşulmaq üçün onu
yox, **IP Address** sahəsini istifadə edir. Serverləriniz siyahı ekranında bu etiketlə görünür.

### Addım 2 — Server qrupları (istəyə bağlı)

Birdən çox serveriniz varsa, **Shared Server Settings → `Server Groups`** altında qrup yaradıb məhsulu
tək serverə deyil, qrupa bağlaya bilərsiniz. Qrupun redaktə ekranında iki paylama tipi var:

- **Həmişə ən az dolu olan serverə əlavə et.**
- **Bir serveri tam dolana qədər doldur. Sonra ən az dolu olan serverə keç.**

Serverlər **Unassigned → Assigned** siyahıları arasında `Add` / `Remove` ilə daşınır.

> [!IMPORTANT]
> **Qrupu panel baxımından eyni cinsli saxlayın.** Məhsul formasındakı paket siyahısı həmin an seçilmiş
> **tək bir** serverdən çəkilir. Bir qrupda həm cPanel, həm Plesk serveri varsa, seçdiyiniz paket adının
> digər paneldə qarşılığı olmaya bilər və həmin serverə düşən sifariş "paket tapılmadı" ilə uğursuz
> olar.

### Addım 3 — Məhsulun təyin edilməsi

**Products / Services → Hosting/Server → Web Hosting Packages** → paketi açın → **Module Settings**
bölməsi.

**Server Selection** altında **Single Server** və ya **Server Group** seçib DNAHosting serverinizi (və ya
qrupunuzu) işarələyin. Seçim edildiyi anda modul öz sahələrini çəkir:

| Sahə | Mənası |
|---|---|
| **Detected panel** | Modulun həmin serverdə həqiqətən tapdığı panel — məsələn `cPanel / WHM`. Təyinin işlədiyini burada görürsünüz; problem varsa bu sətirdə xəta mətni görünür. |
| **Package / Plan** | Həmin serverdən canlı çəkilən paket siyahısı |
| **Automatic Setup** | Açıq olduqda sifariş avtomatik quraşdırılır; bağlı olduqda admin təsdiqi tələb olunur |

Paket siyahısı panelə görə gəlir:

- **cPanel:** serverin `listpkgs` çıxışındakı hər paket. Paketləriniz adi diler ön şəkilçisini daşıyırsa
  (məsələn `bakcay328_paket1`), modul ön şəkilçini özü həll edir.
- **Plesk:** serverdə təyin edilmiş hər xidmət planı.

Paketi seçin, formanın qalanını həmişəki kimi doldurub yadda saxlayın. Məhsul artıq satıla bilər —
sifariş verildikdə tam hesab yaratma axını konfiqurasiya etdiyiniz serverə qarşı işləyir.

---

## Nasazlıqların aradan qaldırılması

| Əlamət | Səbəb | Həll |
|---|---|---|
| Bir çağırış (əksərən bağlantı testi) `HTTP 403` ilə düşür və ya xəta mətnində `cpanelresult` zərfi keçir | Tokenin arxasındakı diler hesabının həmin funksiya üçün WHM səviyyəsində icazəsi yoxdur; WHM, WHM API 1 əvəzinə cPanel **istifadəçi** API-si ilə cavab verib | WHM-də **Resellers → Edit Reseller's ACL List** açıb dilerə modulun istifadə etdiyi icazələri verin: hesab siyahısı və hesab xülasəsi, hesab yaratma, dayandırma, ləğv etmə, şifrə dəyişmə, paket yüksəltmə, paket siyahısı, trafik oxuma və sessiya yaratma. Sonra tokeni **həmin diler kimi daxil olmuş vəziyyətdə** **WHM → Development → Manage API Tokens** üzərindən yenidən yaradın — cPanel interfeysindən yaradılan token WHM girişi daşımır |
| `Plesk (11003)` | API açarı, WiseCP-nin qoşulduğu IP-dən fərqli bir ünvan üçün yaradılıb | Plesk serverində düzgün IP üçün yeni açar yaradın və ya Password sahəsinə panel şifrəsini yazın |
| `Plesk (1014)` | Plesk sorğu gövdəsini rədd etdi — bir element əskikdir və ya bu serverin danışdığı XML-API versiyası üçün yanlış yerdədir | Modulun güncəl versiyasını işlətdiyinizi yoxlayın; modul logu Pleskin tam olaraq hansı elementə etiraz etdiyini göstərir |
| Məhsul formasında paket siyahısı yerinə xəta mətni | Təyin və ya paket çağırışı uğursuz oldu; səbəb eyni sətirdə yazılıb | Mətndəki konkret xətaya görə yuxarıdakı sətirlərdən birini tətbiq edin |

Digər hər HTTP xətası panelin cavab gövdəsindən çıxarılmış düz mətn xülasə ilə gəlir; çılpaq bir status
kodu heç vaxt hekayənin hamısı deyil. Tam sorğu və cavab üçün modul loguna baxın.

---

## Loglar

**Tools → Logs → Module Logs**

Modulun göndərdiyi hər sorğu və aldığı hər cavab, əməliyyat adı ilə (məsələn `createacct`,
`webspace.add`) etiketlənərək bura yazılır. Qeydlər yalnız **Module Logs** funksiyası açıq olduqda
saxlanılır; həmin açar eyni səhifənin yuxarısındadır.

> [!NOTE]
> Serverin API tokeni/şifrəsi, modulun yaratdığı və ya dəyişdirdiyi hesab şifrələri və SSO sessiya
> tokenləri — həm sorğuda, həm cavabda — yazılmadan əvvəl `***` ilə maskalanır.

---

## Dəyişiklik jurnalı

Versiya-versiya dəyişikliklər üçün [CHANGELOG.md](CHANGELOG.md) faylına baxın.

---

## Lisenziya

Xüsusi. Bütün hüquqlar qorunur.
