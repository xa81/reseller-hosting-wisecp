# DNA Reseller Hosting

**Sell cPanel/WHM and Plesk shared hosting from a single WiseCP server module.**

One module, two panels. Point it at a server and it figures out on its own whether that server
runs cPanel/WHM or Plesk — you never set a panel type by hand.

![WiseCP](https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-supported-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-supported-53BCE6?style=flat-square)
![License](https://img.shields.io/badge/license-proprietary-lightgrey?style=flat-square)

---

## What it does

A single WiseCP server module drives both panel families through one server record. The admin
enters an IP, a reseller username and one credential; the module probes the server itself (a real
API call, not a guess) and remembers which panel answered.

| Feature | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Connection test with automatic panel detection | Yes | Yes |
| Account creation | Yes | Yes |
| Suspend / unsuspend | Yes | Yes |
| Termination | Yes | Yes, ownership-verified |
| Password change | Yes | Yes |
| Package / plan change | Yes | Yes |
| Disk & bandwidth usage (shown on the client's service page) | Yes | Yes |
| One-click login — client area | Yes | Yes |
| One-click login — admin area (to the reseller's own panel) | Yes | Yes |

Two safety behaviours worth knowing about up front:

- **Duplicate-domain guard.** Terminating a service is refused if the same domain is still active
  or suspended on another service on the same server — it protects against one domain's hosting
  being torn out from under a second, still-live order.
- **Plesk ownership guard.** Every account the module creates on Plesk is tagged with an internal
  identifier. Deleting a subscription that doesn't carry a matching tag is refused — so the module
  can never be pointed at someone else's manually-created Plesk subscription and asked to delete it.

**Not included:** email account / forwarder management, selling reseller accounts, importing
existing accounts into WiseCP, and an admin "log in to the root panel" button. The module only ever
holds a reseller credential — never root — so there is no root panel for it to open.

---

## Requirements

- A self-hosted **WiseCP** installation with admin access
- PHP with the **cURL** and **SimpleXML** extensions enabled (present on essentially every default
  PHP build)
- Either a **cPanel/WHM reseller account** (with a WHM API token — see below) or a **Plesk reseller
  account** (with an API key or just its panel password)
- Outbound network access from the WiseCP server to the panel server, on the panel's API port

No database table is created, nothing is installed via Composer, and there is no build step.

---

## Installation

Copy the module folder into your WiseCP installation:

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← copy the whole folder here
```

That's the entire installation. There is nothing to run afterwards — no migration, no cache
warm-up, no separate activation step. The module appears the next time you open the "Add Server"
screen.

---

## Adding the server

**Services → Hosting Management → Server Settings → Add New Server**

| Field | What to enter |
|---|---|
| **Server Automation Type** | `DNAHosting` — this is the folder name, shown as-is in the dropdown |
| **Hostname** | A label for your own reference (e.g. `panel1.example.com`); it is not used to connect |
| **IP Address** | The panel server's actual address — this is what the module connects to |
| **Username** | Your reseller username on that panel |
| **Password** | **cPanel:** a WHM API token. **Plesk:** either an API secret key or the reseller's panel password |
| **Access Hash** | Not shown — this field stays hidden for DNAHosting servers |
| **Port** | `2087` for cPanel, `8443` for Plesk (the form defaults to the cPanel port; change it for a Plesk server) |
| **SSL** | Checked |

Two details that are easy to miss:

- **The credential always goes in the Password field, on both panels.** WiseCP stores that field
  encrypted. This module doesn't use the "Access Hash" field at all — it stays hidden on the form
  and never appears for a DNAHosting server.
- **The port only decides which panel is probed first.** `8443`/`8880` makes the module try Plesk
  first, anything else tries cPanel first — but it always confirms with a real API call and falls
  back to the other panel if the first guess doesn't answer. A wrong port slows detection down; it
  does not break it.

Saving the server automatically runs a connection test (WiseCP does this for every server-type
module). A green result confirms both the credential and the detected panel; a failure shows the
concrete HTTP status or panel error — see [Troubleshooting](#troubleshooting).

---

## cPanel WHM API token — required ACLs

The WHM API token you put in the Password field can never exceed the ACLs already granted to the
reseller account that generated it. Before creating the token, make sure that reseller's ACL list
grants:

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

Generate the token itself from **WHM → Development → Manage API Tokens** while logged in as that
reseller — a token created inside cPanel's own interface (rather than WHM) never carries WHM access,
no matter what ACLs the account has.

---

## Plesk API key — the one thing that trips people up

A Plesk API key is bound to the IP address it was generated for. If you generate a key on your
workstation, or copy one from a different server, authentication fails as soon as your WiseCP
server tries to use it — that failure is reported as **`Plesk (11003)`**.

Generate the key **on the Plesk server itself**, for the address WiseCP will actually connect from
(its outbound/public IP — not the panel's own IP), via **Tools & Settings → API keys**.

If that's inconvenient, skip the key entirely and put your reseller account's **panel password** in
the server's Password field instead. The module always tries the credential as an API key first; if
that fails, it automatically falls back to HTTP basic auth — you don't need to tell it which one
you're using.

---

## Defining the product

**Services → Hosting Management → Hosting Packages → Create New Package**

Under **Server Selection**, choose **Single Server** and pick the DNAHosting server you added (if
you use a server group instead, the module still renders its form against one concrete server in
that group — keep panel-compatible servers grouped together).

Once a server is selected, the module renders its own **Package / Plan** field, populated live from
that exact server:

- **cPanel:** every package returned by the server's `listpkgs`. If your packages carry the usual
  reseller prefix (e.g. `bakcay_starter`), you can enter either the full name or just `starter` —
  the module resolves the prefix itself.
- **Plesk:** every service plan defined on the server.

Pick the package/plan, fill in the rest of the product form as usual, and save. The product is now
sellable — ordering it runs the full create-account flow against the server you configured.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| A call (often the connection test) fails with `HTTP 403`, or succeeds but the error text mentions a `cpanelresult` envelope | The reseller account behind the token doesn't have WHM-level access for that function, so WHM answered with the cPanel *user* API instead of WHM API 1 | In WHM, open **Resellers → Edit Reseller's ACL List** for this reseller and grant the ACLs listed in the *cPanel WHM API token* section above; regenerate the token from **WHM → Development → Manage API Tokens** while logged in as that reseller |
| `Plesk (11003)` | The API key was generated for a different IP address than the one WiseCP connects from | Generate a new key on the Plesk server for the correct IP (see the *Plesk API key* section above), or switch the Password field to the panel password instead |
| `Plesk (1014)` | Plesk rejected the request body — an element was missing or in the wrong place for the XML-API version this server speaks | Confirm you're running the current release of this module (older, hand-rolled Plesk requests using the deprecated `<domain>` operator are rejected by current Plesk with this same code); the module log shows exactly which element Plesk objected to |

> The diagnostic detail appended after a Plesk error code (e.g. the sentence after `Plesk (11003):`)
> is written in Turkish in this release, regardless of which admin language you're using — the table
> above is the English translation of what you'll actually see.

Any other HTTP error always comes with a plain-text summary of the panel's response body attached,
so a bare status code is never the whole story — check the module log for the full request and
response.

---

## Logs

**Tools → Process Logs → Module Activity Log**

Every request the module sends and every response it receives is recorded here, tagged with the
action (e.g. `createacct`, `webspace.add`). Logging only happens while the **Module Activity Log**
feature is switched on — that toggle lives at the top of the same page.

The server's API token/password and any account password the module generates or changes are masked
to `***` before anything is written, in both the request and the response.

---

## License

Proprietary. All rights reserved.
