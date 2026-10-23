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

**Gestisca gli account di hosting reseller cPanel e Plesk forniti da DomainNameAPI da un unico modulo server per WiseCP.**

Un modulo, due pannelli. Il tipo di pannello non lo sceglie lei — il modulo interroga direttamente
il server e ricorda quale pannello ha risposto.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-supportato-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-supportato-53BCE6?style=flat-square)

</div>

---

## 📑 Indice

- [✨ Cosa fa](#-cosa-fa)
- [📋 Requisiti](#-requisiti)
- [🚀 Installazione](#-installazione)
- [🔍 Log e risoluzione dei problemi](#-log-e-risoluzione-dei-problemi)
- [🧩 Cose da sapere](#-cose-da-sapere)
- [📄 Changelog](#-changelog)

---

## ✨ Cosa fa

| Funzione | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Test di connessione e rilevamento automatico del pannello | ✅ | ✅ |
| Creazione dell'account (ripetibile in sicurezza) | ✅ | ✅ |
| Sospensione / riattivazione | ✅ | ✅ |
| Cessazione | ✅ | ✅ *(proprietà verificata)* |
| Cambio password | ✅ | ✅ |
| Cambio pacchetto / piano (upgrade e schermata del servizio) | ✅ | ✅ |
| Indicatori di spazio disco e traffico nella panoramica del servizio | ✅ | ✅ |
| Utilizzo di spazio disco e traffico per la fatturazione a consumo | ✅ | ✅ |
| Accesso al pannello di controllo con un clic | ✅ | ✅ |

> 💡 **Pensato per l'hosting reseller di DomainNameAPI.** Il modulo è fatto per gli account reseller
> cPanel/WHM e Plesk che DomainNameAPI fornisce. Non serve accesso root o amministrativo; lavora con i
> permessi del suo account reseller DomainNameAPI, e ogni account creato viene scalato dalla sua
> quota. Non ha ancora un account di hosting reseller? Ne ottenga uno su
> [dm.domainnameapi.com/hosting](https://dm.domainnameapi.com/hosting).

---

## 📋 Requisiti

- **WiseCP 5** o **WiseCP 3** installato su un suo server, con accesso amministrativo; ciascuno ha il proprio pacchetto
- **PHP** 8.2 o superiore su WiseCP 5, come richiesto da WiseCP 5; 7.4 – 8.4 su WiseCP 3
- Estensioni PHP: quelle già richieste da WiseCP 5, più `simplexml` (attiva nelle build PHP standard)
- Un **account di hosting reseller DomainNameAPI**: cPanel/WHM (WHM API token) &nbsp;oppure&nbsp; Plesk
  (chiave segreta o password del pannello)
- Accesso in uscita dal server WiseCP verso il server del pannello, sulla porta API del pannello

> ✅ Non viene creata alcuna tabella nel database, non serve un cron e non ci sono dipendenze
> composer. Nulla viene inviato altrove se non al server del pannello che lei configura.

---

## 🚀 Installazione

### 1️⃣ Installi il modulo

Ogni release contiene due pacchetti. Scelga quello per la sua versione di WiseCP:

| WiseCP | Pacchetto | Sorgente in questo repository |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **I pacchetti non sono intercambiabili.** WiseCP 5 ha cambiato il contratto dei moduli server,
> quindi il modulo per WiseCP 3 non si carica su WiseCP 5 e viceversa.

Carichi il pacchetto dal gestore dei moduli, oppure lo estragga nella radice della sua
installazione WiseCP. L'archivio contiene il percorso completo, quindi il modulo finisce in:

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← qui
```

I passaggi seguenti valgono per **WiseCP 5**. Su WiseCP 3 le schermate hanno nomi diversi; veda
[WiseCP 3](#-cose-da-sapere) in *Cose da sapere*.

### 2️⃣ Aggiunga il server

**Products → Hosting/Server → Server Settings → Add New Server.** In **Server Automation Module**,
nella scheda **Web Hosting**, scelga **DNA Reseller Hosting** (elencato sotto *Other*).

| Campo | Cosa inserire |
|---|---|
| **Address** | Il server del pannello. Lasci attivo l'interruttore **https://**. Preferisca un nome host coperto dal certificato del pannello; un indirizzo IP supera la verifica del certificato solo se il certificato copre quell'IP |
| **Port** | Per cPanel è precompilato `2087`. Per Plesk prema **Use Custom Port** e inserisca `8443` |
| **Verify SSL Certificate** | Lo lasci attivo. Lo disattivi solo per un pannello che usa un certificato autofirmato |
| **Username** | Il suo nome utente reseller su quel pannello |
| **Password** | **cPanel:** un token API WHM creato come quel reseller. **Plesk:** una chiave segreta, oppure la password del pannello |
| **Hostname** | Il nome host del server del pannello; WiseCP lo mostra anche nei record di server e servizi. Se è un nome host completo come `server.example.com`, il modulo si collega a esso invece che ad **Address**, così la verifica del certificato funziona anche quando **Address** contiene un IP |

![Impostazioni del server in WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Verifichi la connessione

Prema **Test Connection**. Un esito verde conferma le credenziali e il pannello rilevato; in caso di
errore viene mostrato il codice HTTP o l'errore del pannello stesso. Veda
[Log e risoluzione dei problemi](#-log-e-risoluzione-dei-problemi).

### 4️⃣ Crei un gruppo di server *(facoltativo)*

Se ha più di un server, crei un gruppo in **Server Groups** e colleghi il prodotto al gruppo anziché
a un singolo server.

> ⚠️ **Mantenga ogni gruppo omogeneo per pannello.** L'elenco dei pacchetti nel modulo prodotto
> proviene da un solo server. Se un gruppo mescola cPanel e Plesk, il nome scelto potrebbe non
> esistere sull'altro pannello e l'ordine che finisce lì fallisce con *pacchetto non trovato*. Il
> modulo memorizza il **nome** del pacchetto, non l'id del pannello, quindi un nome presente su ogni
> server del gruppo funziona su tutti.

### 5️⃣ Configuri il prodotto

Crei il prodotto hosting e scelga **DNA Reseller Hosting** in **Module Assignment**. Nella scheda
**Automation** del prodotto scelga **Single Server** (o un gruppo di server) e il server. Il modulo
disegna quindi un solo campo:

| Impostazione | Valore |
|---|---|
| **Package / Plan** | L'elenco letto in tempo reale dal server; il pannello rilevato è indicato nell'etichetta, ad esempio *Package / Plan (cPanel / WHM)*. Se il server non è raggiungibile, il campo diventa una casella di testo con il motivo sotto |

> 💡 **Su cPanel non si preoccupi del prefisso del pacchetto.** Un pacchetto che nel pannello compare
> come `reseller_basic` può essere inserito come `basic`; il modulo risolve il prefisso da sé.

> ℹ️ I **Resource Limits** della stessa scheda non vengono inviati al pannello. Disco, banda e altri
> limiti dell'account derivano dal pacchetto o dal piano scelto; WiseCP lo indica anche nella
> schermata del servizio.

**Salvi — il modulo è pronto all'uso.** 🎉

---

## 🔍 Log e risoluzione dei problemi

Ogni richiesta che il modulo invia al pannello, e la risposta ricevuta, viene scritta nel
**log del modulo** finché il logging dei moduli è attivo in WiseCP. Ogni voce è etichettata con la
chiamata al pannello, come `createacct` o `webspace.add`.

> 🔐 Il token o la password del server, ogni password di account generata o modificata dal modulo e
> i token di sessione del single sign-on vengono mascherati con `***` **prima che venga scritto
> qualsiasi cosa**, sia nella richiesta sia nella risposta.

### Errori più frequenti

| Sintomo | Causa e soluzione |
|---|---|
| `HTTP 403`, oppure *il server ha risposto con l'API utente di cPanel* | Il reseller a cui appartiene il token non ha il privilegio WHM per quella chiamata. In **WHM → Resellers → Edit Reseller's ACL List** conceda i privilegi di elenco account, creazione, sospensione, cessazione, password, cambio pacchetto, elenco pacchetti, traffico e sessione, quindi crei il token **come quel reseller** |
| Un errore di certificato | Il certificato non copre l'indirizzo inserito. Inserisca l'hostname coperto dal certificato, oppure disattivi **Verify SSL Certificate** per un pannello con certificato autofirmato |
| Plesk **11003** | La chiave segreta è stata emessa per un altro IP. Ne crei una nuova per l'indirizzo da cui WiseCP si collega, oppure usi la password del pannello |
| *Rifiutato per sicurezza: l'abbonamento … non è stato creato da questo servizio* | Su Plesk il modulo agisce solo sui clienti che ha creato per quel servizio. L'abbonamento è stato creato a mano o da un altro servizio |

---

## 🧩 Cose da sapere

<details>
<summary><b>I nuovi tentativi sono sicuri</b></summary>

<br>

La coda dei moduli di WiseCP ripete una creazione fallita. Il modulo salva il nome utente e la
password cifrata prima di chiamare il pannello, quindi un nuovo tentativo li riutilizza: un account
creato ma la cui risposta è andata persa viene adottato invece di fallire con *esiste già*. La
cessazione di un account che non esiste più viene considerata completata.

</details>

<details>
<summary><b>Differenze tra i pannelli</b></summary>

<br>

- **Ogni operazione su Plesk verifica la proprietà.** Il cliente creato dal modulo porta il numero
  del servizio come id esterno, e questo marcatore viene controllato prima di qualunque operazione.
  Il modulo non può mai agire su un abbonamento creato a mano.
- **Il cambio di dominio di un servizio Plesk attivo** non viene riportato sul pannello: un
  abbonamento Plesk si ritrova tramite il dominio, quindi le operazioni successive segnaleranno che
  non esiste alcun abbonamento. Rinomini l'abbonamento anche in Plesk. Su cPanel l'account continua
  a servire il vecchio dominio finché non lo cambia nel pannello.
- **Protezione dai domini duplicati.** La cessazione viene rifiutata finché lo stesso dominio è
  ancora collegato a un altro servizio attivo o sospeso sullo stesso server.

</details>

<details>
<summary><b>Rilevamento del pannello</b></summary>

<br>

Il tipo di pannello non si configura mai. Alla prima chiamata il modulo sonda il server e ricorda
per una settimana quale pannello ha risposto; **Test Connection** sonda sempre di nuovo.

La porta decide soltanto quale pannello viene provato **per primo**: `8443` e `8880` provano prima
Plesk, qualunque altra prova prima cPanel. Un'ipotesi sbagliata rallenta il rilevamento, non lo
rompe. Durante il rilevamento il modulo invia la credenziale configurata anche all'endpoint API
dell'altro pannello sullo **stesso** server; non contatta mai nessun altro host.

</details>

<details>
<summary><b>Credenziali</b></summary>

<br>

Entrambi i pannelli ricevono la credenziale nel campo **Password**, che WiseCP conserva cifrato. Il
campo **Access Hash** viene conservato in chiaro, perciò questo modulo non lo offre e non lo legge
mai. Su Plesk il modulo prova la credenziale prima come chiave segreta e ripiega da sé su login e
password.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

Il pacchetto per WiseCP 3 (`v3/coremio/`) offre lo stesso provisioning sulle schermate proprie di
WiseCP 3:

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** Scelga
   `DNAHosting` come **Server Automation Type** e compili **IP Address**, **Username**, **Password**
   (il token API WHM, la chiave segreta Plesk o la password Plesk), **Connect with SSL** e **Port**
   (`2087` per cPanel, `8443` per Plesk). Il campo **Hostname** lì è solo un'etichetta; il modulo si
   collega a **IP Address**. Prema **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → apra il pacchetto → scheda
   **Module Settings**. Scelga il server o il gruppo; il modulo mostra il **Detected panel** e
   l'elenco **Package / Plan**.

Differenze rispetto al pacchetto per WiseCP 5:

- WiseCP 3 non ha un interruttore di verifica del certificato per i moduli server. Quando il
  **Hostname** del server è un nome host completo coperto dal certificato del pannello, il pacchetto
  vi si connette e verifica il certificato; con un solo indirizzo IP si connette all'IP senza verifica.
- L'area clienti mostra l'uso di disco e banda e un pulsante per accedere al pannello; le schede di
  panoramica del servizio di WiseCP 5 non esistono su WiseCP 3.
- Su Plesk il cambio di dominio di un servizio attivo viene rifiutato invece di essere lasciato
  all'operatore.

</details>

<details>
<summary><b>Fuori ambito</b></summary>

<br>

Strumenti di gestione di e-mail, database e file, la vendita di account reseller, l'importazione in
WiseCP di account esistenti e un pulsante *accedi come amministratore del server*. Il modulo
custodisce soltanto una credenziale **reseller**, mai root. L'importazione è esclusa di proposito:
un abbonamento Plesk importato non porterebbe il marcatore di proprietà di questo modulo, e ogni
operazione successiva su di esso verrebbe rifiutata.

</details>

---

## 📄 Changelog

Le modifiche versione per versione sono in [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · Modulo reseller cPanel & Plesk per WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [Pannello reseller](https://dm.domainnameapi.com/hosting)

</div>
