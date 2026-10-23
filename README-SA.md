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

**أدِر حسابات الموزّعين على cPanel وPlesk من وحدة خوادم واحدة في WiseCP.**

وحدة واحدة، لوحتان. أنت لا تختار نوع اللوحة — الوحدة تسأل الخادم بنفسها وتتذكر أي لوحة
استجابت.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-supported-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-supported-53BCE6?style=flat-square)

</div>

---

## 📑 المحتويات

- [✨ ما الذي تقوم به](#-ما-الذي-تقوم-به)
- [📋 المتطلبات](#-المتطلبات)
- [🚀 التثبيت](#-التثبيت)
- [🔍 السجلات وحل المشكلات](#-السجلات-وحل-المشكلات)
- [🧩 أمور ينبغي معرفتها](#-أمور-ينبغي-معرفتها)
- [📄 سجل التغييرات](#-سجل-التغييرات)

---

## ✨ ما الذي تقوم به

| الميزة | cPanel/WHM | Plesk |
|---|:---:|:---:|
| اختبار الاتصال والكشف التلقائي عن اللوحة | ✅ | ✅ |
| إنشاء الحسابات (إعادة المحاولة آمنة) | ✅ | ✅ |
| التعليق / إلغاء التعليق | ✅ | ✅ |
| الإنهاء | ✅ | ✅ *(مع التحقق من الملكية)* |
| تغيير كلمة المرور | ✅ | ✅ |
| تغيير الباقة / الخطة (الترقية وشاشة الخدمة) | ✅ | ✅ |
| مؤشرات القرص وحركة البيانات في نظرة الخدمة العامة | ✅ | ✅ |
| استهلاك القرص وحركة البيانات للفوترة حسب الاستخدام | ✅ | ✅ |
| الدخول إلى لوحة التحكم بنقرة واحدة | ✅ | ✅ |

> 💡 **مصمّمة للموزّعين.** لا تحتاج إلى صلاحية root أو مدير؛ تعمل الوحدة بصلاحيات حساب الموزّع
> الخاص بك، وكل حساب تنشئه يُحتسب من حصتك.

---

## 📋 المتطلبات

- **WiseCP 5** أو **WiseCP 3** مثبّتة على خادمك، ولديك صلاحية مدير عليها؛ ولكلٍّ منهما حزمته الخاصة
- **PHP** 8.2 أو أحدث على WiseCP 5، كما تشترط WiseCP 5؛ و7.4 – 8.4 على WiseCP 3
- امتدادات PHP: تلك التي تشترطها WiseCP 5 أصلًا، إضافةً إلى `simplexml` (مفعَّل في إصدارات PHP القياسية)
- إمّا **حساب موزّع cPanel/WHM** (رمز WHM API) &nbsp;أو&nbsp; **حساب موزّع Plesk**
  (مفتاح سري أو كلمة مرور اللوحة)
- نفاذ صادر من خادم WiseCP إلى خادم اللوحة على منفذ API الخاص باللوحة

> ✅ لا يُنشأ أي جدول في قاعدة البيانات، ولا حاجة إلى مهمة cron، ولا توجد اعتماديات composer.
> ولا يُرسَل أي شيء إلى أي جهة سوى خادم اللوحة الذي تضبطه.

---

## 🚀 التثبيت

### 1️⃣ ثبِّت الوحدة

كل إصدار يحمل حزمتين. اختر الحزمة المناسبة لإصدار WiseCP لديك:

| WiseCP | الحزمة | المصدر في هذا المستودع |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **الحزمتان غير قابلتين للتبادل.** غيّرت WiseCP 5 عقد وحدات الخوادم، لذا لا تُحمَّل وحدة
> WiseCP 3 على WiseCP 5، والعكس كذلك.

ارفع الحزمة من مدير الوحدات، أو فك ضغطها في جذر تثبيت WiseCP لديك. يحمل الأرشيف المسار
كاملًا، فتستقر الوحدة في:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← هنا
```

الخطوات أدناه خاصة بـ **WiseCP 5**. أما في WiseCP 3 فأسماء الشاشات مختلفة؛ راجع
[WiseCP 3](#-أمور-ينبغي-معرفتها) ضمن *أمور ينبغي معرفتها*.

### 2️⃣ أضف الخادم

**Products → Hosting/Server → Server Settings → Add New Server.** ضمن **Server Automation Module**،
في تبويب **Web Hosting**، اختر **DNA Reseller Hosting** (مدرجة تحت *Other*).

| الحقل | ما يُدخل فيه |
|---|---|
| **Address** | خادم اللوحة. أبقِ مفتاح **https://** مفعّلًا. يُفضَّل اسم مضيف تغطيه شهادة اللوحة؛ ولا يجتاز عنوان IP التحقق من الشهادة إلا إذا كانت الشهادة تغطي ذلك العنوان |
| **Port** | تُملأ القيمة `2087` مسبقًا لـ cPanel. ولـ Plesk اضغط **Use Custom Port** وأدخل `8443` |
| **Verify SSL Certificate** | أبقِه مفعَّلًا. عطّله فقط للوحة تعمل بشهادة موقَّعة ذاتيًا |
| **Username** | اسم مستخدم الموزّع لديك على تلك اللوحة |
| **Password** | **cPanel:** رمز WHM API منشأ بصفة ذلك الموزّع. **Plesk:** مفتاح سري أو كلمة مرور اللوحة |
| **Hostname** | اسم مضيف خادم اللوحة؛ ويعرضه WiseCP أيضًا في سجلات الخوادم والخدمات. وعندما يكون اسم مضيف كاملًا مثل `server.example.com` تتصل الوحدة به بدلًا من **Address**، فيعمل التحقق من الشهادة حتى لو احتوى **Address** على عنوان IP |

![إعدادات الخادم في WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ اختبر الاتصال

اضغط **Test Connection**. النتيجة الخضراء تؤكد بيانات الاعتماد واللوحة المكتشفة؛ وعند الفشل يظهر
رمز HTTP أو خطأ اللوحة نفسها. راجع [السجلات وحل المشكلات](#-السجلات-وحل-المشكلات).

### 4️⃣ أنشئ مجموعة خوادم *(اختياري)*

إن كان لديك أكثر من خادم، أنشئ مجموعة ضمن **Server Groups** واربط المنتج بالمجموعة بدل خادم
واحد.

> ⚠️ **حافظ على تجانس المجموعة من حيث اللوحة.** قائمة الباقات في نموذج المنتج تأتي من خادم واحد.
> وإذا جمعت المجموعة بين cPanel وPlesk، فقد لا يوجد الاسم الذي اخترته على اللوحة الأخرى، فيفشل
> الطلب الذي يقع هناك برسالة *الباقة غير موجودة*. تخزّن الوحدة **اسم** الباقة لا معرّفها في اللوحة،
> لذا فالاسم الموجود على كل خوادم المجموعة يعمل عليها جميعًا.

### 5️⃣ اضبط المنتج

أنشئ منتج الاستضافة واختر **DNA Reseller Hosting** ضمن **Module Assignment**. وفي تبويب **Automation**
الخاص بالمنتج اختر **Single Server** (أو مجموعة خوادم) والخادم. ثم ترسم الوحدة حقلًا واحدًا:

| الإعداد | القيمة |
|---|---|
| **Package / Plan** | القائمة المجلوبة مباشرة من الخادم؛ وتظهر اللوحة المكتشفة في التسمية، مثل *Package / Plan (cPanel / WHM)*. وإن تعذّر الوصول إلى الخادم يتحوّل الحقل إلى مربع نص يظهر السبب تحته |

> 💡 **لا تشغل بالك ببادئة الباقة على cPanel.** الباقة التي تظهر في اللوحة باسم `reseller_basic`
> يمكن إدخالها بصيغة `basic`؛ فالوحدة تحلّ البادئة بنفسها.

> ℹ️ لا تُرسَل **Resource Limits** الموجودة في التبويب نفسه إلى اللوحة. فالمساحة وعرض النطاق وسائر حدود
> الحساب تأتي من الباقة أو الخطة التي تختارها؛ ويذكر WiseCP ذلك أيضًا في شاشة الخدمة.

**احفظ — الوحدة جاهزة للاستخدام.** 🎉

---

## 🔍 السجلات وحل المشكلات

كل طلب ترسله الوحدة إلى اللوحة، والاستجابة التي أعادتها، يُكتب في **سجل الوحدة** ما دام تسجيل
الوحدات مفعَّلًا في WiseCP. ويُوسَم كل سجل باستدعاء اللوحة، مثل `createacct` أو `webspace.add`.

> 🔐 رمز الخادم أو كلمة مروره، وكل كلمة مرور حساب تولّدها الوحدة أو تغيّرها، ورموز جلسات الدخول
> الموحّد تُقنَّع بـ `***` **قبل كتابة أي شيء**، في الطلب والاستجابة على حد سواء.

### الأخطاء الشائعة

| العَرَض | السبب والحل |
|---|---|
| `HTTP 403` أو *أجاب الخادم بواجهة API الخاصة بمستخدم cPanel* | الموزّع صاحب الرمز لا يملك صلاحية WHM لذلك الاستدعاء. امنح صلاحيات سرد الحسابات والإنشاء والتعليق والإنهاء وكلمة المرور وتغيير الباقة وسرد الباقات وحركة البيانات والجلسات من **WHM → Resellers → Edit Reseller's ACL List**، ثم أنشئ الرمز **بصفة ذلك الموزّع** |
| خطأ في الشهادة | الشهادة لا تغطي العنوان الذي أدخلته. أدخل اسم المضيف الذي تغطيه الشهادة، أو عطّل **Verify SSL Certificate** للوحة ذات شهادة موقَّعة ذاتيًا |
| Plesk **11003** | المفتاح السري صادر لعنوان IP مختلف. أنشئ مفتاحًا جديدًا للعنوان الذي يتصل منه WiseCP، أو استخدم كلمة مرور اللوحة |
| *رُفض حفاظًا على السلامة: الاشتراك … لم يُنشأ بواسطة هذه الخدمة* | على Plesk لا تتعامل الوحدة إلا مع العملاء الذين أنشأتها لتلك الخدمة. وقد أُنشئ الاشتراك يدويًا أو بواسطة خدمة أخرى |

---

## 🧩 أمور ينبغي معرفتها

<details>
<summary><b>إعادة المحاولة آمنة</b></summary>

<br>

تعيد قائمة انتظار الوحدات في WiseCP تشغيل عملية الإنشاء الفاشلة. تحفظ الوحدة اسم المستخدم وكلمة
المرور المشفّرة قبل أن تستدعي اللوحة، فتعيد المحاولة استخدامهما: الحساب الذي أُنشئ لكن ضاعت
استجابته يُعتمَد بدل أن يفشل برسالة *موجود مسبقًا*. وإنهاء حساب لم يعد موجودًا يُعدّ منجزًا.

</details>

<details>
<summary><b>الفروق بين اللوحتين</b></summary>

<br>

- **كل عملية على Plesk تخضع للتحقق من الملكية.** يحمل العميل الذي تنشئه الوحدة رقم الخدمة معرّفًا
  خارجيًا له، ويُفحص هذا الوسم قبل أي عملية. ولا يمكن للوحدة أبدًا أن تتعامل مع اشتراك أُنشئ يدويًا.
- **تغيير نطاق خدمة قائمة على Plesk** لا يُنقل إلى اللوحة: يُعثر على اشتراك Plesk عبر نطاقه، لذا
  ستفيد العمليات اللاحقة بعدم وجود اشتراك. أعد تسمية الاشتراك في Plesk أيضًا. أما على cPanel فيظل
  الحساب يخدم النطاق القديم حتى تغيّره في اللوحة.
- **الحماية من تكرار النطاق.** يُرفض الإنهاء ما دام النطاق نفسه مرتبطًا بخدمة أخرى نشطة أو معلَّقة
  على الخادم ذاته.

</details>

<details>
<summary><b>الكشف عن اللوحة</b></summary>

<br>

لا يُضبط نوع اللوحة أبدًا. تسبر الوحدة الخادم في أول استدعاء وتتذكر لمدة أسبوع أي لوحة استجابت؛
أما **Test Connection** فيسبر الخادم من جديد في كل مرة.

المنفذ يحدد فقط أي لوحة تُجرَّب **أولًا**: المنفذان `8443` و`8880` يجربان Plesk أولًا، وأي قيمة
أخرى تجرب cPanel أولًا. التخمين الخاطئ يبطئ الكشف ولا يعطّله. أثناء الكشف ترسل
الوحدة بيانات الاعتماد المُعدّة أيضًا إلى نقطة API الخاصة باللوحة الأخرى على الخادم **نفسه**، ولا
تتصل أبدًا بأي مضيف آخر.

</details>

<details>
<summary><b>بيانات الاعتماد</b></summary>

<br>

كلتا اللوحتين تأخذان بيانات الاعتماد من حقل **Password**، الذي تخزّنه WiseCP مشفَّرًا. أما حقل
**Access Hash** فيُخزَّن نصًا صريحًا، لذا لا تعرضه هذه الوحدة ولا تقرؤه أبدًا. وعلى Plesk تجرّب
الوحدة بيانات الاعتماد أولًا كمفتاح سري ثم تتراجع من تلقاء نفسها إلى اسم الدخول وكلمة المرور.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

توفّر حزمة WiseCP 3 (`v3/coremio/`) إدارة الحسابات نفسها على شاشات WiseCP 3 الخاصة بها:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** اختر
   `DNAHosting` في **Server Automation Type** واملأ **IP Address** و**Username** و**Password**
   (رمز WHM API، أو المفتاح السري لـ Plesk، أو كلمة مرور Plesk) و**Connect with SSL** و**Port**
   (`2087` لـ cPanel، و`8443` لـ Plesk). حقل **Hostname** هناك مجرد تسمية؛ فالوحدة تتصل بـ
   **IP Address**. اضغط **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → افتح الباقة → تبويب **Module
   Settings**. اختر الخادم أو المجموعة؛ فتعرض الوحدة **Detected panel** وقائمة **Package / Plan**.

الفروق عن حزمة WiseCP 5:

- لا يوجد في WiseCP 3 مفتاح للتحقق من الشهادة لوحدات الخوادم. عندما يكون **Hostname** الخادم اسم
  مضيف كاملًا تغطيه شهادة اللوحة، تتصل الحزمة به وتتحقق من الشهادة؛ أما مع عنوان IP فقط فتتصل
  بعنوان IP دون تحقق.
- تعرض منطقة العميل استهلاك القرص وحركة البيانات وزرًّا لتسجيل الدخول إلى اللوحة؛ أما بطاقات نظرة
  عامة على الخدمة في WiseCP 5 فغير موجودة في WiseCP 3.
- يُرفض تغيير نطاق خدمة فعّالة على Plesk بدلًا من تركه للمشغّل.

</details>

<details>
<summary><b>خارج النطاق</b></summary>

<br>

أدوات إدارة البريد وقواعد البيانات والملفات، وبيع حسابات الموزّعين، واستيراد الحسابات القائمة إلى
WiseCP، وزر *الدخول بصفة مدير الخادم*. فالوحدة لا تحمل سوى بيانات اعتماد **موزّع**، ولا تحمل root
أبدًا. وقد استُبعد الاستيراد عن قصد: فالاشتراك المستورد على Plesk لن يحمل وسم الملكية الخاص بهذه
الوحدة، وسترفض كل عملية لاحقة عليه.

</details>

---

## 📄 سجل التغييرات

التغييرات إصدارًا بإصدار موجودة في [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · وحدة cPanel و Plesk للموزّعين على WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [لوحة الموزّع](https://dm.domainnameapi.com/hosting)

</div>
