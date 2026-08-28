<h1 align="center">DNA Reseller Hosting</h1>

<p align="center">
  <strong>Verkaufen Sie Shared Hosting über cPanel/WHM und Plesk mit einem einzigen WiseCP-Servermodul.</strong><br>
  Ein Modul, zwei Panels — Sie wählen den Paneltyp nie aus, das Modul findet ihn selbst.
</p>

<p align="center">
  <img alt="WiseCP" src="https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="cPanel/WHM" src="https://img.shields.io/badge/cPanel%2FWHM-unterstützt-FF6C2C?style=flat-square">
  <img alt="Plesk" src="https://img.shields.io/badge/Plesk-unterstützt-53BCE6?style=flat-square">
  <img alt="Lizenz" src="https://img.shields.io/badge/Lizenz-proprietär-lightgrey?style=flat-square">
</p>

<p align="center">
  <a href="README.md">Türkçe</a>
  · <a href="README.en.md">English</a>
  · <strong>Deutsch</strong>
  · <a href="README.ru.md">Русский</a>
  · <a href="README.az.md">Azərbaycan</a>
  · <a href="README.ar.md">العربية</a>
  · <a href="README.es.md">Español</a>
  · <a href="README.fr.md">Français</a>
</p>

---

## Inhaltsverzeichnis

- [Überblick](#überblick)
- [Funktionsmatrix](#funktionsmatrix)
- [Voraussetzungen](#voraussetzungen)
- [Installation](#installation)
- [Konfiguration](#konfiguration)
  - [Schritt 1 — Server hinzufügen](#schritt-1--server-hinzufügen)
  - [Schritt 2 — Servergruppen (optional)](#schritt-2--servergruppen-optional)
  - [Schritt 3 — Produkt anlegen](#schritt-3--produkt-anlegen)
- [Fehlerbehebung](#fehlerbehebung)
- [Logs](#logs)
- [Changelog](#changelog)
- [Lizenz](#lizenz)

---

## Überblick

Das Modul steuert beide Panel-Familien über einen einzigen Servereintrag. Sie tragen die IP, den
Reseller-Benutzernamen und eine Zugangsinformation ein; das Modul fragt den Server tatsächlich ab — ein
echter API-Aufruf, keine Vermutung — und merkt sich, welches Panel geantwortet hat.

| | |
|---|---|
| **Modultyp** | WiseCP-Servermodul (Servers) |
| **Ordnername** | `DNAHosting` |
| **Version** | 1.0.0 |
| **Unterstützte Panels** | cPanel/WHM, Plesk |
| **PHP** | 7.4 – 8.4 |
| **Oberflächensprachen** | Türkisch, Englisch (`lang/tr.php`, `lang/en.php`) |

---

## Funktionsmatrix

| Vorgang | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Verbindungstest und automatische Panel-Erkennung | ✔ | ✔ |
| Konto anlegen | ✔ | ✔ |
| Sperren / Entsperren | ✔ | ✔ |
| Kündigung | ✔ | ✔ (mit Eigentumsprüfung) |
| Passwortänderung | ✔ | ✔ |
| Paket- / Planwechsel | ✔ | ✔ |
| Speicher- und Traffic-Verbrauch (auf der Dienstseite des Kunden) | ✔ | ✔ |
| Ein-Klick-Panel-Login — Kundenbereich | ✔ | ✔ |
| Ein-Klick-Panel-Login — Adminbereich | ✔ | ✔ |

---

## Voraussetzungen

- Eine selbst gehostete **WiseCP**-Installation, zu der Sie Administratorzugang haben
- PHP mit aktivierten Erweiterungen **cURL** und **SimpleXML** (in nahezu jeder Standardinstallation
  vorhanden)
- Entweder ein **cPanel/WHM-Reseller-Konto** (mit einem WHM-API-Token) oder ein
  **Plesk-Reseller-Konto** (mit einem API-Schlüssel oder direkt dem Panel-Passwort)
- Ausgehender Netzwerkzugriff vom WiseCP-Server zum Panel-Server auf dem API-Port des Panels

Es wird keine Datenbanktabelle angelegt, nichts über Composer installiert, es gibt keinen Build-Schritt.

---

## Installation

Kopieren Sie den Modulordner in Ihre WiseCP-Installation:

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← der komplette Ordner kommt hierher
```

Das ist die gesamte Installation. Danach ist nichts auszuführen — keine Migration, kein Cache-Warmup,
kein separater Aktivierungsschritt. Das Modul erscheint in der Liste, sobald Sie den Server-Hinzufügen-
Dialog das nächste Mal öffnen.

---

## Konfiguration

> Die Menüpfade unten entsprechen der englischsprachigen WiseCP-Oberfläche.

### Schritt 1 — Server hinzufügen

**Products / Services → Hosting/Server → Shared Server Settings → `Add New Shared Server`**

Füllen Sie den Abschnitt **Server Automation Information** des Formulars aus:

| Feld | Was einzutragen ist |
|---|---|
| **Server Automation Type** | `DNAHosting` — das ist der Ordnername, er erscheint genau so in der Liste |
| **IP Address** | Die tatsächliche Adresse des Panel-Servers; das Modul verbindet sich hierhin |
| **Username** | Ihr Reseller-Benutzername auf diesem Panel |
| **Password** | **cPanel:** das WHM-API-Token. **Plesk:** der API-Schlüssel oder das Panel-Passwort des Resellers |
| **Connect with SSL** | Aktivieren |
| **Port** | `2087` für cPanel, `8443` für Plesk |

Das Feld **Hostname** am oberen Rand des Formulars ist nur eine Bezeichnung für Sie — zum Verbinden
nutzt das Modul das Feld **IP Address**, nicht dieses. In der Listenansicht erscheinen Ihre Server unter
dieser Bezeichnung.

### Schritt 2 — Servergruppen (optional)

Wenn Sie mehrere Server haben, können Sie unter **Shared Server Settings → `Server Groups`** eine Gruppe
anlegen und das Produkt statt an einen einzelnen Server an die Gruppe binden. Der Bearbeitungsdialog der
Gruppe bietet zwei Verteilungsarten:

- **Immer auf den am wenigsten ausgelasteten Server legen.**
- **Einen Server vollständig füllen, danach auf den am wenigsten ausgelasteten Server wechseln.**

Server werden mit `Add` / `Remove` zwischen den Listen **Unassigned → Assigned** verschoben.

> [!IMPORTANT]
> **Halten Sie eine Gruppe panelseitig homogen.** Die Paketliste im Produktformular wird von dem in
> diesem Moment ausgewählten **einen** Server geladen. Enthält eine Gruppe sowohl einen cPanel- als auch
> einen Plesk-Server, hat der gewählte Paketname auf dem anderen Panel möglicherweise keine Entsprechung
> — eine Bestellung, die auf diesem Server landet, scheitert dann mit „Paket nicht gefunden".

### Schritt 3 — Produkt anlegen

**Products / Services → Hosting/Server → Web Hosting Packages** → Paket öffnen → Reiter **Module
Settings**.

Wählen Sie unter **Server Selection** entweder **Single Server** oder **Server Group** und markieren Sie
Ihren DNAHosting-Server (bzw. Ihre Gruppe). Sobald die Auswahl steht, zeichnet das Modul seine eigenen
Felder:

| Feld | Bedeutung |
|---|---|
| **Detected panel** | Das Panel, das das Modul auf diesem Server tatsächlich gefunden hat — zum Beispiel `cPanel / WHM`. Hier sehen Sie, dass die Erkennung funktioniert; bei einem Problem erscheint in dieser Zeile der Fehlertext. |
| **Package / Plan** | Die live von diesem Server geladene Paketliste |
| **Automatic Setup** | Aktiviert wird die Bestellung automatisch bereitgestellt; deaktiviert ist eine Admin-Freigabe nötig |

Die Paketliste hängt vom Panel ab:

- **cPanel:** jedes Paket aus der `listpkgs`-Ausgabe des Servers. Tragen Ihre Pakete das übliche
  Reseller-Präfix (zum Beispiel `bakcay328_paket1`), löst das Modul das Präfix selbst auf.
- **Plesk:** jeder auf dem Server definierte Service-Plan.

Paket auswählen, den Rest des Formulars wie gewohnt ausfüllen und speichern. Das Produkt ist damit
verkaufsfertig — bei einer Bestellung läuft der komplette Kontoanlage-Ablauf gegen den von Ihnen
konfigurierten Server.

---

## Fehlerbehebung

| Symptom | Ursache | Lösung |
|---|---|---|
| Ein Aufruf (meist der Verbindungstest) scheitert mit `HTTP 403`, oder im Fehlertext taucht ein `cpanelresult`-Umschlag auf | Das Reseller-Konto hinter dem Token hat für diese Funktion keine WHM-Berechtigung; WHM hat statt mit WHM API 1 mit der cPanel-**Benutzer**-API geantwortet | Öffnen Sie in WHM **Resellers → Edit Reseller's ACL List** und geben Sie dem Reseller die vom Modul genutzten Rechte: Konten auflisten und Kontoübersicht, Konto anlegen, sperren, kündigen, Passwort ändern, Paket-Upgrade, Pakete auflisten, Traffic lesen und Sitzung erzeugen. Erzeugen Sie das Token anschließend **als dieser Reseller angemeldet** unter **WHM → Development → Manage API Tokens** neu — ein aus der cPanel-Oberfläche erzeugtes Token trägt keinen WHM-Zugriff |
| `Plesk (11003)` | Der API-Schlüssel wurde für eine andere Adresse erzeugt als die IP, von der aus WiseCP verbindet | Erzeugen Sie auf dem Plesk-Server einen neuen Schlüssel für die richtige IP, oder tragen Sie das Panel-Passwort in das Feld Password ein |
| `Plesk (1014)` | Plesk hat den Request-Body abgelehnt — ein Element fehlt oder steht für die XML-API-Version dieses Servers an der falschen Stelle | Stellen Sie sicher, dass Sie die aktuelle Modulversion einsetzen; das Modullog zeigt, welches Element Plesk genau beanstandet hat |
| Statt der Paketliste erscheint im Produktformular ein Fehlertext | Die Erkennung oder der Paketabruf ist fehlgeschlagen; der Grund steht in derselben Zeile | Wenden Sie je nach konkretem Fehler im Text eine der Zeilen oben an |

Jeder andere HTTP-Fehler kommt mit einer Klartext-Zusammenfassung aus dem Antwortkörper des Panels; ein
nackter Statuscode ist nie die ganze Geschichte. Für den vollständigen Request und die Antwort sehen Sie
ins Modullog.

---

## Logs

**Tools → Logs → Module Logs**

Jeder Request, den das Modul sendet, und jede Antwort, die es erhält, wird hier festgehalten —
gekennzeichnet mit dem Operationsnamen (zum Beispiel `createacct`, `webspace.add`). Aufzeichnungen
entstehen nur, solange die Funktion **Module Logs** eingeschaltet ist; dieser Schalter sitzt oben auf
derselben Seite.

> [!NOTE]
> Das API-Token bzw. Passwort des Servers, alle vom Modul erzeugten oder geänderten Kontopasswörter und
> SSO-Sitzungstoken werden — sowohl im Request als auch in der Antwort — vor dem Schreiben mit `***`
> maskiert.

---

## Changelog

Änderungen Version für Version finden Sie in [CHANGELOG.md](CHANGELOG.md).

---

## Lizenz

Proprietär. Alle Rechte vorbehalten.
