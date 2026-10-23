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

**Beheer de cPanel- en Plesk-resellerhostingaccounts die DomainNameAPI levert vanuit één WiseCP-servermodule.**

Eén module, twee panels. U kiest het paneltype nooit — de module vraagt het de server zelf en
onthoudt welk panel antwoordde.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-ondersteund-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-ondersteund-53BCE6?style=flat-square)

</div>

---

## 📑 Inhoud

- [✨ Wat de module doet](#-wat-de-module-doet)
- [📋 Vereisten](#-vereisten)
- [🚀 Installatie](#-installatie)
- [🔍 Logs en probleemoplossing](#-logs-en-probleemoplossing)
- [🧩 Goed om te weten](#-goed-om-te-weten)
- [📄 Changelog](#-changelog)

---

## ✨ Wat de module doet

| Functie | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Verbindingstest en automatische paneldetectie | ✅ | ✅ |
| Account aanmaken (veilig opnieuw te proberen) | ✅ | ✅ |
| Opschorten / heractiveren | ✅ | ✅ |
| Beëindigen | ✅ | ✅ *(eigendom geverifieerd)* |
| Wachtwoord wijzigen | ✅ | ✅ |
| Pakket / plan wijzigen (upgrade en het servicescherm) | ✅ | ✅ |
| Schijf- en dataverkeermeters in het serviceoverzicht | ✅ | ✅ |
| Schijf- en dataverbruik voor verbruiksafhankelijke facturering | ✅ | ✅ |
| Met één klik inloggen op het controlepaneel | ✅ | ✅ |

> 💡 **Gebouwd voor DomainNameAPI-resellerhosting.** De module is gemaakt voor de cPanel/WHM- en
> Plesk-reselleraccounts die DomainNameAPI levert. Root- of adminrechten zijn niet nodig; zij werkt
> met de rechten van uw DomainNameAPI-reselleraccount, en elk account dat zij aanmaakt telt mee voor
> uw quotum. Nog geen resellerhostingaccount? Vraag er een aan via
> [dm.domainnameapi.com/hosting](https://dm.domainnameapi.com/hosting).

---

## 📋 Vereisten

- **WiseCP 5** of **WiseCP 3**, zelf gehost, met beheerderstoegang; elk heeft een eigen pakket
- **PHP** 8.2 of nieuwer op WiseCP 5, zoals WiseCP 5 vereist; 7.4 – 8.4 op WiseCP 3
- PHP-extensies: de extensies die WiseCP 5 al vereist, plus `simplexml` (ingeschakeld in standaard PHP-builds)
- Een **DomainNameAPI-resellerhostingaccount**: cPanel/WHM (WHM API token) &nbsp;of&nbsp; Plesk
  (secret key of het panelwachtwoord)
- Uitgaande toegang van de WiseCP-server naar de panelserver op de API-poort van het panel

> ✅ Er wordt geen databasetabel aangemaakt, er is geen cronjob nodig en er zijn geen
> composer-afhankelijkheden. Er wordt nergens iets naartoe gestuurd behalve naar de panelserver die u
> configureert.

---

## 🚀 Installatie

### 1️⃣ Installeer de module

Elke release bevat twee pakketten. Kies het pakket voor uw WiseCP-versie:

| WiseCP | Pakket | Bron in deze repository |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **De pakketten zijn niet uitwisselbaar.** WiseCP 5 heeft het contract voor servermodules gewijzigd,
> dus de WiseCP 3-module laadt niet op WiseCP 5 en omgekeerd.

Upload het pakket via de modulebeheerder, of pak het uit in de hoofdmap van uw
WiseCP-installatie. Het archief bevat het volledige pad, dus de module komt terecht in:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← hier
```

De onderstaande stappen gelden voor **WiseCP 5**. Op WiseCP 3 hebben de schermen andere namen; zie
[WiseCP 3](#-goed-om-te-weten) onder *Goed om te weten*.

### 2️⃣ Voeg de server toe

**Products → Hosting/Server → Server Settings → Add New Server.** Kies onder **Server Automation Module**,
op het tabblad **Web Hosting**, **DNA Reseller Hosting** (vermeld onder *Other*).

| Veld | Wat invullen |
|---|---|
| **Address** | De panelserver. Laat de schakelaar **https://** aan. Gebruik bij voorkeur een hostnaam die het certificaat van het panel dekt; een IP-adres doorstaat de certificaatverificatie alleen als het certificaat dat IP dekt |
| **Port** | Voor cPanel is `2087` al ingevuld. Voor Plesk klikt u op **Use Custom Port** en vult u `8443` in |
| **Verify SSL Certificate** | Laat dit aan. Zet het alleen uit voor een panel dat op een zelfondertekend certificaat draait |
| **Username** | Uw resellergebruikersnaam op dat panel |
| **Password** | **cPanel:** een WHM API-token, aangemaakt als die reseller. **Plesk:** een secret key, of het panelwachtwoord |
| **Hostname** | De hostnaam van de panelserver; WiseCP toont die ook bij server- en dienstrecords. Is het een volledige hostnaam zoals `server.example.com`, dan maakt de module daarmee verbinding in plaats van met **Address**, zodat certificaatverificatie werkt, ook als **Address** een IP bevat |

![Serverinstellingen in WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Test de verbinding

Klik op **Test Connection**. Een groen resultaat bevestigt de inloggegevens en het gedetecteerde
panel; bij een fout ziet u de HTTP-code of de eigen foutmelding van het panel. Zie
[Logs en probleemoplossing](#-logs-en-probleemoplossing).

### 4️⃣ Maak een servergroep *(optioneel)*

Als u meer dan één server hebt, maak dan een groep aan onder **Server Groups** en koppel het product
aan de groep in plaats van aan één server.

> ⚠️ **Houd een groep homogeen per panel.** De pakketlijst in het productformulier komt van één
> server. Als een groep cPanel en Plesk mengt, bestaat de gekozen naam mogelijk niet op het andere
> panel en mislukt een bestelling die daar terechtkomt met *pakket niet gevonden*. De module slaat de
> pakket**naam** op, niet de id van het panel, dus een naam die op elke server van de groep bestaat,
> werkt op al die servers.

### 5️⃣ Configureer het product

Maak het hostingproduct aan en kies **DNA Reseller Hosting** onder **Module Assignment**. Kies op het
tabblad **Automation** van het product **Single Server** (of een servergroep) en de server. De module
toont dan één veld:

| Instelling | Waarde |
|---|---|
| **Package / Plan** | De lijst die live van de server wordt opgehaald; het gedetecteerde panel staat in het label, bijvoorbeeld *Package / Plan (cPanel / WHM)*. Als de server niet bereikbaar is, wordt het veld een tekstvak met de reden eronder |

> 💡 **Maak u geen zorgen over het pakketprefix op cPanel.** Een pakket dat op het panel als
> `reseller_basic` staat, kan als `basic` worden ingevuld; de module lost het prefix zelf op.

> ℹ️ **Resource Limits** op hetzelfde tabblad worden niet naar het panel gestuurd. Schijfruimte,
> bandbreedte en andere limieten van het account komen uit het gekozen pakket of plan; WiseCP vermeldt
> dit ook op het dienstscherm.

**Opslaan — de module is klaar voor gebruik.** 🎉

---

## 🔍 Logs en probleemoplossing

Elk verzoek dat de module naar het panel stuurt, en het antwoord dat het panel teruggaf, wordt in het
**modulelog** geschreven zolang modulelogging in WiseCP is ingeschakeld. Elke regel is gelabeld met
de panelaanroep, zoals `createacct` of `webspace.add`.

> 🔐 Het token of wachtwoord van de server, elk accountwachtwoord dat de module genereert of wijzigt,
> en single sign-on-sessietokens worden met `***` gemaskeerd **voordat er iets wordt weggeschreven**,
> zowel in het verzoek als in het antwoord.

### Veelvoorkomende fouten

| Symptoom | Oorzaak en oplossing |
|---|---|
| `HTTP 403`, of *de server antwoordde met de cPanel-gebruikers-API* | De reseller achter het token heeft geen WHM-recht voor die aanroep. Ken rechten toe voor het weergeven, aanmaken, opschorten en beëindigen van accounts, wachtwoorden, pakketwijziging, pakketweergave, dataverkeer en sessies in **WHM → Resellers → Edit Reseller's ACL List**, en maak het token daarna aan **als die reseller** |
| Een certificaatfout | Het certificaat dekt het ingevulde adres niet. Vul de hostnaam in die het certificaat dekt, of zet **Verify SSL Certificate** uit voor een panel met een zelfondertekend certificaat |
| Plesk **11003** | De secret key is voor een ander IP uitgegeven. Maak een nieuwe aan voor het adres waarvandaan WiseCP verbinding maakt, of gebruik het panelwachtwoord |
| *Uit veiligheidsoverwegingen geweigerd: het abonnement … is niet door deze dienst aangemaakt* | Op Plesk handelt de module alleen voor klanten die zij voor die dienst heeft aangemaakt. Het abonnement is handmatig of door een andere dienst aangemaakt |

---

## 🧩 Goed om te weten

<details>
<summary><b>Opnieuw proberen is veilig</b></summary>

<br>

De modulewachtrij van WiseCP voert een mislukte aanmaak opnieuw uit. De module slaat de
gebruikersnaam en het versleutelde wachtwoord op voordat zij het panel aanroept, zodat een nieuwe
poging ze hergebruikt: een account dat wel is aangemaakt maar waarvan het antwoord verloren ging,
wordt overgenomen in plaats van te mislukken op *bestaat al*. Het beëindigen van een account dat al
verdwenen is, telt als voltooid.

</details>

<details>
<summary><b>Verschillen tussen de panels</b></summary>

<br>

- **Elke Plesk-bewerking wordt op eigendom gecontroleerd.** De klant die de module aanmaakt, draagt
  het servicenummer als externe id, en dat kenmerk wordt vóór elke bewerking gecontroleerd. De module
  kan nooit iets doen met een handmatig aangemaakt abonnement.
- **Het domein van een actieve Plesk-dienst wijzigen** wordt niet naar het panel doorgezet: een
  Plesk-abonnement wordt op zijn domein gevonden, dus latere bewerkingen melden dat er geen abonnement
  bestaat. Hernoem het abonnement ook in Plesk. Op cPanel blijft het account het oude domein bedienen
  totdat u het in het panel wijzigt.
- **Bescherming tegen dubbele domeinen.** Beëindigen wordt geweigerd zolang hetzelfde domein nog aan
  een andere actieve of opgeschorte dienst op dezelfde server gekoppeld is.

</details>

<details>
<summary><b>Paneldetectie</b></summary>

<br>

Het paneltype wordt nooit geconfigureerd. Bij de eerste aanroep peilt de module de server en onthoudt
zij een week lang welk panel antwoordde; **Test Connection** peilt altijd opnieuw.

De poort bepaalt alleen welk panel **eerst** wordt geprobeerd: `8443` en `8880` proberen eerst Plesk,
al het andere probeert eerst cPanel. Een verkeerde gok vertraagt de detectie, maar breekt haar niet.
Tijdens de detectie stuurt de module de ingestelde inloggegevens ook naar het API-eindpunt van het
andere panel op **dezelfde** server; met een andere host neemt ze nooit contact op.

</details>

<details>
<summary><b>Inloggegevens</b></summary>

<br>

Beide panels nemen hun inloggegevens via het veld **Password**, dat WiseCP versleuteld opslaat. Het
veld **Access Hash** wordt als platte tekst opgeslagen, daarom biedt deze module het niet aan en leest
zij het nooit. Op Plesk probeert de module de inloggegevens eerst als secret key en valt zij zelf
terug op login en wachtwoord.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

Het WiseCP 3-pakket (`v3/coremio/`) biedt dezelfde provisioning in de eigen schermen van WiseCP 3:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** Kies
   `DNAHosting` als **Server Automation Type** en vul **IP Address**, **Username**, **Password**
   (het WHM API-token, de Plesk secret key of het Plesk-wachtwoord), **Connect with SSL** en **Port**
   (`2087` voor cPanel, `8443` voor Plesk) in. Het veld **Hostname** daar is alleen een label; de module
   maakt verbinding met **IP Address**. Klik op **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → open het pakket → tabblad **Module
   Settings**. Kies de server of groep; de module toont het **Detected panel** en de lijst
   **Package / Plan**.

Verschillen met het WiseCP 5-pakket:

- WiseCP 3 heeft geen schakelaar voor certificaatverificatie voor servermodules. Is de **Hostname**
  van de server een volledige hostnaam die het certificaat van het panel dekt, dan maakt het pakket
  daarmee verbinding en verifieert het het certificaat; met alleen een IP-adres maakt het zonder
  verificatie verbinding met het IP.
- Het klantgedeelte toont schijf- en bandbreedtegebruik en een knop om in te loggen op het panel; de
  overzichtskaarten voor diensten van WiseCP 5 bestaan niet op WiseCP 3.
- Het wijzigen van het domein van een actieve dienst wordt op Plesk geweigerd in plaats van aan de
  beheerder overgelaten.

</details>

<details>
<summary><b>Buiten scope</b></summary>

<br>

Beheertools voor e-mail, databases en bestanden, het verkopen van reselleraccounts, het importeren van
bestaande accounts in WiseCP, en een knop *inloggen als serverbeheerder*. De module heeft alleen
**reseller**-inloggegevens, nooit root. Importeren is bewust weggelaten: een geïmporteerd
Plesk-abonnement zou het eigendomskenmerk van deze module niet dragen, en elke latere bewerking erop
zou worden geweigerd.

</details>

---

## 📄 Changelog

De wijzigingen per versie staan in [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · cPanel- & Plesk-resellermodule voor WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [Resellerpanel](https://dm.domainnameapi.com/hosting)

</div>
