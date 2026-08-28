<h1 align="center">DNA Reseller Hosting</h1>

<p align="center">
  <strong>Sell shared hosting on both cPanel/WHM and Plesk from a single WiseCP server module.</strong><br>
  One module, two panels — you never pick the panel type, the module finds it itself.
</p>

<p align="center">
  <img alt="WiseCP" src="https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="cPanel/WHM" src="https://img.shields.io/badge/cPanel%2FWHM-supported-FF6C2C?style=flat-square">
  <img alt="Plesk" src="https://img.shields.io/badge/Plesk-supported-53BCE6?style=flat-square">
  <img alt="License" src="https://img.shields.io/badge/license-proprietary-lightgrey?style=flat-square">
</p>

<p align="center">
  <a href="README.md">Türkçe</a>
  · <strong>English</strong>
  · <a href="README.de.md">Deutsch</a>
  · <a href="README.ru.md">Русский</a>
  · <a href="README.az.md">Azərbaycan</a>
  · <a href="README.ar.md">العربية</a>
  · <a href="README.es.md">Español</a>
  · <a href="README.fr.md">Français</a>
</p>

---

## Table of contents

- [Overview](#overview)
- [Feature matrix](#feature-matrix)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Step 1 — Add a server](#step-1--add-a-server)
  - [Step 2 — Server groups (optional)](#step-2--server-groups-optional)
  - [Step 3 — Define the product](#step-3--define-the-product)
- [Troubleshooting](#troubleshooting)
- [Logs](#logs)
- [Changelog](#changelog)
- [License](#license)

---

## Overview

It drives both panel families through a single server record. You enter the IP, the reseller username
and one credential; the module actually queries the server — a real API call, not a guess — and
remembers which panel answered.

| | |
|---|---|
| **Module type** | WiseCP server (Servers) module |
| **Folder name** | `DNAHosting` |
| **Version** | 1.0.0 |
| **Supported panels** | cPanel/WHM, Plesk |
| **PHP** | 7.4 – 8.4 |
| **Interface languages** | Turkish, English (`lang/tr.php`, `lang/en.php`) |

---

## Feature matrix

| Operation | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Connection test and automatic panel detection | ✔ | ✔ |
| Account creation | ✔ | ✔ |
| Suspend / unsuspend | ✔ | ✔ |
| Termination | ✔ | ✔ (ownership verified) |
| Password change | ✔ | ✔ |
| Package / plan change | ✔ | ✔ |
| Disk and bandwidth usage (on the client's service page) | ✔ | ✔ |
| One-click panel login — client area | ✔ | ✔ |
| One-click panel login — admin area | ✔ | ✔ |

---

## Requirements

- A self-hosted **WiseCP** installation you have admin access to
- PHP with the **cURL** and **SimpleXML** extensions enabled (present in almost every default build)
- Either a **cPanel/WHM reseller account** (with a WHM API token) or a **Plesk reseller account** (with
  an API key or the panel password itself)
- Outbound network access from the WiseCP server to the panel server on the panel's API port

No database table is created, nothing is installed through Composer, there is no build step.

---

## Installation

Copy the module folder into your WiseCP installation:

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← the entire folder goes here
```

That is the whole installation. There is nothing to run afterwards — no migration, no cache warm-up, no
separate activation step. The module shows up in the list the next time you open the add-server screen.

---

## Configuration

### Step 1 — Add a server

**Products / Services → Hosting/Server → Shared Server Settings → `Add New Shared Server`**

Fill in the **Server Automation Information** section of the form:

| Field | What to enter |
|---|---|
| **Server Automation Type** | `DNAHosting` — this is the folder name, it appears in the list as-is |
| **IP Address** | The real address of the panel server; the module connects here |
| **Username** | Your reseller username on that panel |
| **Password** | **cPanel:** the WHM API token. **Plesk:** the API key or the reseller's panel password |
| **Connect with SSL** | Check it |
| **Port** | `2087` for cPanel, `8443` for Plesk |

The **Hostname** field at the top of the form is a label for you only — the module uses the **IP
Address** field, not that one, to connect. Your servers appear under this label on the list screen.

### Step 2 — Server groups (optional)

If you have more than one server, you can create a group under **Shared Server Settings → `Server
Groups`** and bind the product to the group instead of a single server. The group edit screen offers
two distribution types:

- **Always add to the least full server.**
- **Fill one server completely, then move on to the least full server.**

Servers are moved between the **Unassigned → Assigned** lists with `Add` / `Remove`.

> [!IMPORTANT]
> **Keep a group homogeneous per panel.** The package list on the product form is pulled from the
> **single** server selected at that moment. If a group holds both a cPanel and a Plesk server, the
> package name you picked may have no counterpart on the other panel, and an order landing on that
> server fails with "package not found".

### Step 3 — Define the product

**Products / Services → Hosting/Server → Web Hosting Packages** → open the package → **Module
Settings** tab.

Under **Server Selection**, choose **Single Server** or **Server Group** and pick your DNAHosting server
(or group). The moment the selection is made, the module draws its own fields:

| Field | Meaning |
|---|---|
| **Detected panel** | The panel the module actually found on that server — for example `cPanel / WHM`. This is where you see that detection works; if something is wrong, the error text appears on this line. |
| **Package / Plan** | The package list pulled live from that server |
| **Automatic Setup** | When on, the order is provisioned automatically; when off, admin approval is required |

The package list depends on the panel:

- **cPanel:** every package in the server's `listpkgs` output. If your packages carry the usual reseller
  prefix (for example `bakcay328_paket1`), the module resolves the prefix itself.
- **Plesk:** every service plan defined on the server.

Pick the package, fill in the rest of the form as usual and save. The product is now sellable — when an
order is placed, the full account creation flow runs against the server you configured.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| A call (usually the connection test) fails with `HTTP 403`, or the error text mentions a `cpanelresult` envelope | The reseller account behind the token has no WHM-level privilege for that function; WHM answered with the cPanel **user** API instead of WHM API 1 | In WHM, open **Resellers → Edit Reseller's ACL List** and grant that reseller the privileges the module uses: account listing and summary, account creation, suspension, termination, password change, package upgrade, package listing, bandwidth read and session creation. Then regenerate the token **while logged in as that reseller** under **WHM → Development → Manage API Tokens** — a token generated from the cPanel interface carries no WHM access |
| `Plesk (11003)` | The API key was generated for an address other than the IP WiseCP connects from | Generate a new key on the Plesk server for the correct IP, or put the panel password in the Password field |
| `Plesk (1014)` | Plesk rejected the request body — an element is missing or in the wrong place for the XML-API version this server speaks | Verify you are running the current version of the module; the module log shows exactly which element Plesk objected to |
| An error text instead of the package list on the product form | Detection or the package call failed; the reason is written on the same line | Apply one of the rows above according to the concrete error in the text |

Every other HTTP error arrives with a plain-text summary extracted from the panel's response body; a
bare status code is never the whole story. Check the module log for the full request and response.

---

## Logs

**Tools → Logs → Module Logs**

Every request the module sends and every response it receives is written here, tagged with the operation
name (for example `createacct`, `webspace.add`). Records are only kept while the **Module Logs** feature
is on; that switch is at the top of the same page.

> [!NOTE]
> The server's API token/password, any account passwords the module generates or changes, and SSO
> session tokens — in both the request and the response — are masked with `***` before anything is
> written.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version-by-version changes.

---

## License

Proprietary. All rights reserved.
