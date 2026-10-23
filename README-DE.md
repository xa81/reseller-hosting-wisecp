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

**Verwalten Sie cPanel- und Plesk-Reseller-Konten aus einem einzigen WiseCP-Servermodul.**

Ein Modul, zwei Panels. Sie wählen den Paneltyp nie aus — das Modul fragt den Server selbst und
merkt sich, welches Panel geantwortet hat.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-unterst%C3%BCtzt-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-unterst%C3%BCtzt-53BCE6?style=flat-square)

</div>

---

## 📑 Inhalt

- [✨ Was es kann](#-was-es-kann)
- [📋 Voraussetzungen](#-voraussetzungen)
- [🚀 Installation](#-installation)
- [🔍 Protokolle und Fehlersuche](#-protokolle-und-fehlersuche)
- [🧩 Wissenswertes](#-wissenswertes)
- [📄 Changelog](#-changelog)

---

## ✨ Was es kann

| Funktion | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Verbindungstest und automatische Panel-Erkennung | ✅ | ✅ |
| Konto anlegen (sicher wiederholbar) | ✅ | ✅ |
| Sperren / entsperren | ✅ | ✅ |
| Kündigung | ✅ | ✅ *(mit Eigentumsprüfung)* |
| Passwortänderung | ✅ | ✅ |
| Paket- / Planwechsel (Upgrade und Dienstansicht) | ✅ | ✅ |
| Speicher- und Traffic-Anzeigen in der Dienstübersicht | ✅ | ✅ |
| Speicher- und Traffic-Verbrauch für verbrauchsabhängige Abrechnung | ✅ | ✅ |
| Ein-Klick-Login ins Control Panel | ✅ | ✅ |

> 💡 **Für Reseller gebaut.** Root- oder Admin-Zugang ist nicht nötig; das Modul arbeitet mit den
> Rechten Ihres eigenen Reseller-Kontos, und jedes angelegte Konto zählt gegen Ihr Kontingent.

---

## 📋 Voraussetzungen

- **WiseCP 5** oder **WiseCP 3**, selbst gehostet, mit Administratorzugang; jede Version hat ihr eigenes Paket
- **PHP** 8.2 oder neuer unter WiseCP 5, wie von WiseCP 5 verlangt; 7.4 – 8.4 unter WiseCP 3
- PHP-Erweiterungen: die, die WiseCP 5 ohnehin verlangt, plus `simplexml` (in Standard-PHP-Builds aktiviert)
- Ein **cPanel/WHM-Reseller-Konto** (WHM-API-Token) &nbsp;oder&nbsp; ein **Plesk-Reseller-Konto**
  (Secret Key oder das Panel-Passwort)
- Ausgehender Zugriff vom WiseCP-Server auf den Panel-Server über den API-Port des Panels

> ✅ Es wird keine Datenbanktabelle angelegt, kein Cronjob benötigt, und es gibt keine
> Composer-Abhängigkeiten. Nichts wird irgendwohin gesendet außer an den Panel-Server, den Sie
> konfigurieren.

---

## 🚀 Installation

### 1️⃣ Modul installieren

Jedes Release enthält zwei Pakete. Wählen Sie das für Ihre WiseCP-Version:

| WiseCP | Paket | Quelle in diesem Repository |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **Die Pakete sind nicht austauschbar.** WiseCP 5 hat den Vertrag für Servermodule geändert, daher
> lädt das WiseCP-3-Modul nicht unter WiseCP 5 und umgekehrt.

Laden Sie das Paket über den Modulmanager hoch oder entpacken Sie es ins Stammverzeichnis
Ihrer WiseCP-Installation. Das Archiv enthält den vollständigen Pfad, das Modul landet also in:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← hier
```

Die folgenden Schritte gelten für **WiseCP 5**. Unter WiseCP 3 heißen die Bildschirme anders; siehe
[WiseCP 3](#-wissenswertes) unter *Wissenswertes*.

### 2️⃣ Server hinzufügen

**Products → Hosting/Server → Server Settings → Add New Server.** Wählen Sie unter **Server Automation Module**
im Tab **Web Hosting** das Modul **DNA Reseller Hosting** (aufgeführt unter *Other*).

| Feld | Was eintragen |
|---|---|
| **Address** | Der Panel-Server. Lassen Sie den Schalter **https://** an. Bevorzugen Sie einen Hostnamen, den das Zertifikat des Panels abdeckt; eine IP-Adresse besteht die Zertifikatsprüfung nur, wenn das Zertifikat diese IP abdeckt |
| **Port** | Für cPanel ist `2087` vorausgefüllt. Für Plesk klicken Sie auf **Use Custom Port** und tragen `8443` ein |
| **Verify SSL Certificate** | Lassen Sie es an. Schalten Sie es nur für ein Panel mit selbstsigniertem Zertifikat aus |
| **Username** | Ihr Reseller-Benutzername auf diesem Panel |
| **Password** | **cPanel:** ein WHM-API-Token, erstellt als dieser Reseller. **Plesk:** ein Secret Key oder das Panel-Passwort |
| **Hostname** | Der Hostname des Panel-Servers; WiseCP zeigt ihn auch bei Server- und Diensteinträgen an. Ist es ein vollständiger Hostname wie `server.example.com`, verbindet sich das Modul damit statt mit **Address**, sodass die Zertifikatsprüfung auch dann funktioniert, wenn **Address** eine IP enthält |

![Servereinstellungen in WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Verbindung testen

Klicken Sie auf **Test Connection**. Ein grünes Ergebnis bestätigt die Zugangsdaten und das erkannte
Panel; ein Fehlschlag zeigt den HTTP-Code oder den eigenen Fehler des Panels. Siehe
[Protokolle und Fehlersuche](#-protokolle-und-fehlersuche).

### 4️⃣ Servergruppe anlegen *(optional)*

Wenn Sie mehr als einen Server haben, legen Sie unter **Server Groups** eine Gruppe an und binden Sie
das Produkt an die Gruppe statt an einen einzelnen Server.

> ⚠️ **Halten Sie eine Gruppe pro Panel einheitlich.** Die Paketliste im Produktformular stammt von
> einem Server. Mischt eine Gruppe cPanel und Plesk, existiert der gewählte Name auf dem anderen Panel
> womöglich nicht, und eine dort landende Bestellung schlägt mit *Paket nicht gefunden* fehl. Das Modul
> speichert den Paket-**Namen**, nicht die ID des Panels; ein Name, der auf jedem Server der Gruppe
> existiert, funktioniert also auf allen.

### 5️⃣ Produkt konfigurieren

Legen Sie das Hosting-Produkt an und wählen Sie unter **Module Assignment** **DNA Reseller Hosting**. Wählen Sie
im Tab **Automation** des Produkts **Single Server** (oder eine Servergruppe) und den Server. Das Modul
zeigt dann ein Feld:

| Einstellung | Wert |
|---|---|
| **Package / Plan** | Die live vom Server abgerufene Liste; das erkannte Panel steht in der Beschriftung, zum Beispiel *Package / Plan (cPanel / WHM)*. Ist der Server nicht erreichbar, wird das Feld zu einem Textfeld mit dem Grund darunter |

> 💡 **Keine Sorge wegen des Paket-Präfixes bei cPanel.** Ein Paket, das im Panel als
> `reseller_basic` geführt wird, kann als `basic` eingetragen werden; das Modul löst das Präfix selbst
> auf.

> ℹ️ **Resource Limits** im selben Tab werden nicht an das Panel gesendet. Speicherplatz, Bandbreite und
> weitere Limits des Kontos ergeben sich aus dem gewählten Paket bzw. Plan; WiseCP weist auch im
> Dienstbildschirm darauf hin.

**Speichern — das Modul ist einsatzbereit.** 🎉

---

## 🔍 Protokolle und Fehlersuche

Jede Anfrage, die das Modul an das Panel sendet, und die zurückgegebene Antwort werden ins
**Modulprotokoll** geschrieben, solange die Modulprotokollierung in WiseCP aktiviert ist. Jeder
Eintrag ist mit dem Panel-Aufruf gekennzeichnet, etwa `createacct` oder `webspace.add`.

> 🔐 Token oder Passwort des Servers, jedes Kontopasswort, das das Modul erzeugt oder ändert, und
> Single-Sign-on-Sitzungstoken werden **vor jedem Schreibvorgang** mit `***` maskiert, in der Anfrage
> ebenso wie in der Antwort.

### Häufige Fehler

| Symptom | Ursache und Lösung |
|---|---|
| `HTTP 403` oder *der Server hat mit der cPanel-Benutzer-API geantwortet* | Der Reseller hinter dem Token hat kein WHM-Recht für diesen Aufruf. Erteilen Sie die Rechte für Kontenauflistung, Anlegen, Sperren, Kündigen, Passwort, Paketwechsel, Paketauflistung, Traffic und Sitzungen unter **WHM → Resellers → Edit Reseller's ACL List** und erstellen Sie das Token dann **als dieser Reseller** |
| Ein Zertifikatsfehler | Das Zertifikat deckt die eingetragene Adresse nicht ab. Tragen Sie den Hostnamen ein, den das Zertifikat abdeckt, oder schalten Sie **Verify SSL Certificate** für ein selbstsigniertes Panel aus |
| Plesk **11003** | Der Secret Key wurde für eine andere IP ausgestellt. Erstellen Sie einen neuen für die Adresse, von der aus WiseCP sich verbindet, oder verwenden Sie das Panel-Passwort |
| *Aus Sicherheitsgründen abgelehnt: das Abonnement … wurde nicht von diesem Dienst angelegt* | Bei Plesk handelt das Modul nur für Kunden, die es für diesen Dienst angelegt hat. Das Abonnement wurde von Hand oder von einem anderen Dienst angelegt |

---

## 🧩 Wissenswertes

<details>
<summary><b>Wiederholungen sind sicher</b></summary>

<br>

Die Modulwarteschlange von WiseCP führt eine fehlgeschlagene Anlage erneut aus. Das Modul speichert
den Benutzernamen und das verschlüsselte Passwort, bevor es das Panel aufruft, sodass eine Wiederholung
sie wiederverwendet: Ein Konto, das angelegt wurde, dessen Antwort aber verloren ging, wird übernommen,
statt an *existiert bereits* zu scheitern. Die Kündigung eines Kontos, das bereits weg ist, gilt als
erledigt.

</details>

<details>
<summary><b>Panel-Unterschiede</b></summary>

<br>

- **Jede Plesk-Operation wird auf Eigentum geprüft.** Der Kunde, den das Modul anlegt, trägt die
  Dienstnummer als externe ID, und diese Kennung wird vor jeder Operation geprüft. Das Modul kann nie
  auf ein von Hand angelegtes Abonnement einwirken.
- **Eine Domainänderung an einem aktiven Plesk-Dienst** wird nicht ans Panel übertragen: Ein
  Plesk-Abonnement wird über seine Domain gefunden, daher melden spätere Operationen, dass kein
  Abonnement existiert. Benennen Sie das Abonnement auch in Plesk um. Bei cPanel bedient das Konto
  weiterhin die alte Domain, bis Sie sie im Panel ändern.
- **Schutz vor doppelten Domains.** Die Kündigung wird abgelehnt, solange dieselbe Domain noch an
  einem anderen aktiven oder gesperrten Dienst auf demselben Server hängt.

</details>

<details>
<summary><b>Panel-Erkennung</b></summary>

<br>

Der Paneltyp wird nie konfiguriert. Beim ersten Aufruf prüft das Modul den Server und merkt sich eine
Woche lang, welches Panel geantwortet hat; **Test Connection** prüft immer neu.

Der Port entscheidet nur, welches Panel **zuerst** versucht wird: `8443` und `8880` versuchen zuerst
Plesk, alles andere zuerst cPanel. Eine falsche Vermutung verlangsamt die Erkennung, sie bricht sie
nicht. Während der Erkennung sendet das Modul die eingetragenen Zugangsdaten auch an den API-Endpunkt
des anderen Panels auf **demselben** Server; einen anderen Host kontaktiert es nie.

</details>

<details>
<summary><b>Zugangsdaten</b></summary>

<br>

Beide Panels nehmen ihre Zugangsdaten im Feld **Password** entgegen, das WiseCP verschlüsselt
speichert. Das Feld **Access Hash** wird im Klartext gespeichert, deshalb bietet dieses Modul es nicht
an und liest es nie. Bei Plesk versucht das Modul die Zugangsdaten zuerst als Secret Key und fällt von
selbst auf Login und Passwort zurück.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

Das WiseCP-3-Paket (`v3/coremio/`) bietet dieselbe Bereitstellung in den eigenen Bildschirmen von WiseCP 3:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** Wählen Sie
   `DNAHosting` als **Server Automation Type** und füllen Sie **IP Address**, **Username**, **Password**
   (das WHM-API-Token, den Plesk Secret Key oder das Plesk-Passwort), **Connect with SSL** und **Port**
   (`2087` für cPanel, `8443` für Plesk) aus. Das Feld **Hostname** ist dort nur eine Beschriftung; das
   Modul verbindet sich mit **IP Address**. Klicken Sie auf **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → Paket öffnen → Reiter **Module
   Settings**. Wählen Sie den Server oder die Gruppe; das Modul zeigt das **Detected panel** und die
   Liste **Package / Plan**.

Unterschiede zum WiseCP-5-Paket:

- WiseCP 3 hat für Servermodule keinen Schalter zur Zertifikatsprüfung. Ist der **Hostname** des
  Servers ein vollständiger Hostname, den das Zertifikat des Panels abdeckt, verbindet sich das Paket
  mit ihm und prüft das Zertifikat; mit nur einer IP-Adresse verbindet es sich ohne Prüfung mit der IP.
- Der Kundenbereich zeigt Speicher- und Traffic-Verbrauch sowie eine Schaltfläche zur Anmeldung am Panel;
  die Übersichtskarten der Dienste aus WiseCP 5 gibt es unter WiseCP 3 nicht.
- Das Ändern der Domain eines aktiven Dienstes wird unter Plesk abgelehnt, statt es dem Betreiber zu
  überlassen.

</details>

<details>
<summary><b>Nicht im Funktionsumfang</b></summary>

<br>

E-Mail-, Datenbank- und Dateiverwaltungswerkzeuge, der Verkauf von Reseller-Konten, der Import
bestehender Konten in WiseCP und eine Schaltfläche *als Serveradministrator anmelden*. Das Modul hält
nur **Reseller**-Zugangsdaten, nie Root. Der Import ist bewusst ausgelassen: Ein importiertes
Plesk-Abonnement trüge nicht die Eigentumskennung dieses Moduls, und jede spätere Operation darauf
würde abgelehnt.

</details>

---

## 📄 Changelog

Die Änderungen Version für Version stehen in [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · cPanel- & Plesk-Reseller-Modul für WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [Reseller-Panel](https://dm.domainnameapi.com/hosting)

</div>
