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

**Manage the cPanel and Plesk reseller hosting accounts provided by DomainNameAPI from a single WiseCP server module.**

One module, two panels. You never pick the panel type — the module asks the server itself and
remembers which panel answered.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-supported-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-supported-53BCE6?style=flat-square)

</div>

---

## 📑 Contents

- [✨ What it does](#-what-it-does)
- [📋 Requirements](#-requirements)
- [🚀 Installation](#-installation)
- [🔍 Logs and troubleshooting](#-logs-and-troubleshooting)
- [🧩 Things worth knowing](#-things-worth-knowing)
- [📄 Changelog](#-changelog)

---

## ✨ What it does

| Feature | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Connection test and automatic panel detection | ✅ | ✅ |
| Account creation (safe to retry) | ✅ | ✅ |
| Suspend / unsuspend | ✅ | ✅ |
| Termination | ✅ | ✅ *(ownership verified)* |
| Password change | ✅ | ✅ |
| Package / plan change (upgrade and the service screen) | ✅ | ✅ |
| Disk and bandwidth gauges on the service overview | ✅ | ✅ |
| Disk and bandwidth usage for metered billing | ✅ | ✅ |
| One-click login to the control panel | ✅ | ✅ |

> 💡 **Built for DomainNameAPI reseller hosting.** The module is made for the cPanel/WHM and Plesk
> reseller accounts that DomainNameAPI provides. It does not need root or admin access; it works with
> the permissions of your DomainNameAPI reseller account, and every account it creates counts against
> your quota. No reseller hosting account yet? Get one at
> [dm.domainnameapi.com/hosting](https://dm.domainnameapi.com/hosting).

---

## 📋 Requirements

- **WiseCP 5** or **WiseCP 3**, self-hosted, with administrator access; each has its own package
- **PHP** 8.2 or newer on WiseCP 5, as WiseCP 5 requires; 7.4 – 8.4 on WiseCP 3
- PHP extensions: the ones WiseCP 5 already requires, plus `simplexml` (enabled in standard PHP builds)
- A **DomainNameAPI reseller hosting account**: cPanel/WHM (WHM API token) &nbsp;or&nbsp; Plesk
  (secret key or the panel password)
- Outbound access from the WiseCP server to the panel server on the panel's API port

> ✅ No database table is created, no cron job is required, and there are no composer dependencies.
> Nothing is sent anywhere except to the panel server you configure.

---

## 🚀 Installation

### 1️⃣ Install the module

Every release carries two packages. Pick the one for your WiseCP version:

| WiseCP | Package | Source in this repository |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **The packages are not interchangeable.** WiseCP 5 changed the server module contract, so the
> WiseCP 3 module does not load on WiseCP 5 and the other way round.

Upload the package from the module manager, or extract it into the root of your WiseCP
installation. The archive carries the full path, so the module lands in:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← here
```

The steps below are for **WiseCP 5**. On WiseCP 3 the screens are named differently; see
[WiseCP 3](#-things-worth-knowing) under *Things worth knowing*.

### 2️⃣ Add the server

**Products → Hosting/Server → Server Settings → Add New Server.** Under **Server Automation Module**,
on the **Web Hosting** tab, pick **DNA Reseller Hosting** (listed under *Other*).

| Field | What to enter |
|---|---|
| **Address** | The panel server. Keep the **https://** switch on. Prefer a host name the panel's certificate covers; an IP address only passes certificate verification when the certificate covers that IP |
| **Port** | `2087` is filled in for cPanel. For Plesk, press **Use Custom Port** and enter `8443` |
| **Verify SSL Certificate** | Keep it on. Turn it off only for a panel that runs on a self-signed certificate |
| **Username** | Your reseller username on that panel |
| **Password** | **cPanel:** a WHM API token created as that reseller. **Plesk:** a secret key, or the panel password |
| **Hostname** | The panel server's host name; WiseCP also shows it on server and service records. When it is a full host name such as `server.example.com`, the module connects to it instead of **Address**, so certificate verification works even when **Address** holds an IP |

![Server settings on WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Test the connection

Press **Test Connection**. A green result confirms the credentials and the detected panel; a failure
shows the HTTP code or the panel's own error. See [Logs and troubleshooting](#-logs-and-troubleshooting).

### 4️⃣ Create a server group *(optional)*

If you have more than one server, create a group under **Server Groups** and bind the product to the
group instead of a single server.

> ⚠️ **Keep a group homogeneous per panel.** The package list on the product form comes from one
> server. If a group mixes cPanel and Plesk, the name you picked may not exist on the other panel and
> an order landing there fails with *package not found*. The module stores the package **name**, not
> the panel's id, so a name that exists on every server of the group works on all of them.

### 5️⃣ Configure the product

Create the hosting product and choose **DNA Reseller Hosting** under **Module Assignment**. On the
product's **Automation** tab, pick **Single Server** (or a server group) and the server. The module
then draws one field:

| Setting | Value |
|---|---|
| **Package / Plan** | The list pulled live from the server; the detected panel is shown in the label, for example *Package / Plan (cPanel / WHM)*. If the server cannot be reached the field becomes a text box with the reason under it |

> 💡 **Do not worry about the package prefix on cPanel.** A package listed as `reseller_basic` on the
> panel can be entered as `basic`; the module resolves the prefix itself.

> ℹ️ **Resource Limits** on the same tab are not sent to the panel. The account's disk, bandwidth and
> other limits come from the package or plan you pick; WiseCP says so on the service screen as well.

**Save it — the module is ready to use.** 🎉

---

## 🔍 Logs and troubleshooting

Every request the module sends to the panel, and the response it returned, is written to the
**module log** while module logging is enabled in WiseCP. Each entry is tagged with the panel call,
such as `createacct` or `webspace.add`.

> 🔐 The server's token or password, every account password the module generates or changes, and
> single sign-on session tokens are masked with `***` **before anything is written**, in the request
> and in the response alike.

### Common errors

| Symptom | Cause and fix |
|---|---|
| `HTTP 403`, or *the server answered with the cPanel user API* | The reseller behind the token has no WHM privilege for that call. Grant account listing, creation, suspension, termination, password, package change, package listing, bandwidth and session privileges in **WHM → Resellers → Edit Reseller's ACL List**, then create the token **as that reseller** |
| A certificate error | The certificate does not cover the address you entered. Enter the hostname the certificate covers, or turn off **Verify SSL Certificate** for a self-signed panel |
| Plesk **11003** | The secret key was issued for a different IP. Create a new one for the address WiseCP connects from, or use the panel password |
| *Refused for safety: the subscription … was not created by this service* | On Plesk the module only acts on customers it created for that service. The subscription was created by hand or by another service |

---

## 🧩 Things worth knowing

<details>
<summary><b>Retries are safe</b></summary>

<br>

WiseCP's module queue runs a failed creation again. The module saves the username and the encrypted
password before it calls the panel, so a retry reuses them: an account that was created but whose
response was lost is adopted instead of failing on *already exists*. Termination of an account that is
already gone counts as done.

</details>

<details>
<summary><b>Panel differences</b></summary>

<br>

- **Every Plesk operation is ownership-verified.** The customer the module creates carries the
  service number as its external id, and that tag is checked before any operation. The module can
  never act on a subscription created by hand.
- **Changing the domain of a live Plesk service** is not carried to the panel: a Plesk subscription is
  found by its domain, so later operations will report that no subscription exists. Rename the
  subscription in Plesk as well. On cPanel the account keeps serving the old domain until you change
  it in the panel.
- **Duplicate domain protection.** Termination is refused while the same domain is still attached to
  another active or suspended service on the same server.

</details>

<details>
<summary><b>Panel detection</b></summary>

<br>

The panel type is never configured. On the first call the module probes the server and remembers for
a week which panel answered; **Test Connection** always probes again.

The port only decides which panel is tried **first**: `8443` and `8880` try Plesk first, anything
else tries cPanel first. A wrong guess slows detection down, it does not break it. While detecting,
the module sends the configured credential to the other panel's API endpoint on the **same** server
as well; it never contacts any other host.

</details>

<details>
<summary><b>Credentials</b></summary>

<br>

Both panels take their credential in the **Password** field, which WiseCP stores encrypted. The
**Access Hash** field is stored as plain text, so this module does not offer it and never reads it.
On Plesk the module tries the credential as a secret key first and falls back to the login and
password on its own.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

The WiseCP 3 package (`v3/coremio/`) offers the same provisioning on WiseCP 3's own screens:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** Choose
   `DNAHosting` as **Server Automation Type** and fill in **IP Address**, **Username**, **Password**
   (the WHM API token, the Plesk secret key or the Plesk password), **Connect with SSL** and **Port**
   (`2087` for cPanel, `8443` for Plesk). The **Hostname** field there is only a label; the module
   connects to **IP Address**. Press **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → open the package → **Module
   Settings** tab. Pick the server or group; the module shows the **Detected panel** and the
   **Package / Plan** list.

Differences from the WiseCP 5 package:

- WiseCP 3 has no certificate verification switch for server modules. When the server's
  **Hostname** is a full host name the panel's certificate covers, the package connects to it and
  verifies the certificate; with only an IP address it connects to the IP without verification.
- The client area shows disk and bandwidth usage and a button to log in to the panel; WiseCP 5's
  service overview cards do not exist on WiseCP 3.
- Changing the domain of a live service is refused on Plesk instead of being left to the operator.

</details>

<details>
<summary><b>Out of scope</b></summary>

<br>

Email, database and file management tools, selling reseller accounts, importing existing accounts
into WiseCP, and a *log in as the server administrator* button. The module holds a **reseller**
credential only, never root. Importing is left out on purpose: an imported Plesk subscription would
not carry this module's ownership tag, and every later operation on it would be refused.

</details>

---

## 📄 Changelog

Version-by-version changes are in [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · cPanel & Plesk reseller module for WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [Reseller panel](https://dm.domainnameapi.com/hosting)

</div>
