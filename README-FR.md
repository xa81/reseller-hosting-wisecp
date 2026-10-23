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

**Gérez les comptes d'hébergement revendeur cPanel et Plesk fournis par DomainNameAPI depuis un seul module serveur WiseCP.**

Un module, deux panneaux. Vous ne choisissez jamais le type de panneau — le module interroge
lui-même le serveur et retient quel panneau a répondu.

![WiseCP](https://img.shields.io/badge/WiseCP-5.x%20%7C%203.x-4A90D9?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white)
![cPanel](https://img.shields.io/badge/cPanel%2FWHM-pris%20en%20charge-FF6C2C?style=flat-square)
![Plesk](https://img.shields.io/badge/Plesk-pris%20en%20charge-53BCE6?style=flat-square)

</div>

---

## 📑 Sommaire

- [✨ Ce que fait le module](#-ce-que-fait-le-module)
- [📋 Prérequis](#-prérequis)
- [🚀 Installation](#-installation)
- [🔍 Journaux et dépannage](#-journaux-et-dépannage)
- [🧩 Bon à savoir](#-bon-à-savoir)
- [📄 Journal des modifications](#-journal-des-modifications)

---

## ✨ Ce que fait le module

| Fonction | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Test de connexion et détection automatique du panneau | ✅ | ✅ |
| Création de compte (relance sans risque) | ✅ | ✅ |
| Suspension / réactivation | ✅ | ✅ |
| Résiliation | ✅ | ✅ *(propriété vérifiée)* |
| Changement de mot de passe | ✅ | ✅ |
| Changement de pack / plan (mise à niveau et écran du service) | ✅ | ✅ |
| Jauges d'espace disque et de trafic dans l'aperçu du service | ✅ | ✅ |
| Consommation d'espace disque et de trafic pour la facturation à l'usage | ✅ | ✅ |
| Connexion en un clic au panneau de contrôle | ✅ | ✅ |

> 💡 **Conçu pour l'hébergement revendeur DomainNameAPI.** Le module est fait pour les comptes
> revendeur cPanel/WHM et Plesk que fournit DomainNameAPI. Aucun accès root ou administrateur n'est
> nécessaire ; il travaille avec les droits de votre compte revendeur DomainNameAPI, et chaque compte
> qu'il crée est décompté de votre quota. Pas encore de compte d'hébergement revendeur ? Obtenez-en un
> sur [dm.domainnameapi.com/hosting](https://dm.domainnameapi.com/hosting).

---

## 📋 Prérequis

- **WiseCP 5** ou **WiseCP 3**, auto-hébergé, avec un accès administrateur ; chacun a son propre paquet
- **PHP** 8.2 ou plus récent sous WiseCP 5, comme l'exige WiseCP 5 ; 7.4 – 8.4 sous WiseCP 3
- Extensions PHP : celles que WiseCP 5 exige déjà, plus `simplexml` (activée dans les builds PHP standard)
- Un **compte d'hébergement revendeur DomainNameAPI** : cPanel/WHM (WHM API token) &nbsp;ou&nbsp; Plesk
  (clé secrète ou mot de passe du panneau)
- Un accès sortant du serveur WiseCP vers le serveur du panneau sur le port d'API du panneau

> ✅ Aucune table de base de données n'est créée, aucune tâche cron n'est nécessaire et il n'y a
> aucune dépendance composer. Rien n'est envoyé nulle part, sauf au serveur de panneau que vous
> configurez.

---

## 🚀 Installation

### 1️⃣ Installez le module

Chaque version contient deux paquets. Choisissez celui qui correspond à votre version de WiseCP :

| WiseCP | Paquet | Source dans ce dépôt |
|---|---|---|
| **5.x** | `DNAHosting-WiseCP5-v2.x.x.zip` | `v5/coremio/` |
| **3.x** | `DNAHosting-WiseCP3-v1.x.x.zip` | `v3/coremio/` |

> ⚠️ **Les paquets ne sont pas interchangeables.** WiseCP 5 a modifié le contrat des modules serveur ;
> le module WiseCP 3 ne se charge donc pas sous WiseCP 5, et inversement.

Téléversez le paquet depuis le gestionnaire de modules, ou décompressez-le à la racine
de votre installation WiseCP. L'archive contient le chemin complet ; le module se retrouve donc dans :

```
wisecp/
└── coremio/
    └── modules/
        └── Servers/
            └── DNAHosting/     ← ici
```

Les étapes ci-dessous concernent **WiseCP 5**. Sous WiseCP 3, les écrans portent d'autres noms ; voir
[WiseCP 3](#-bon-à-savoir) dans *Bon à savoir*.

### 2️⃣ Ajoutez le serveur

**Products → Hosting/Server → Server Settings → Add New Server.** Sous **Server Automation Module**,
dans l'onglet **Web Hosting**, choisissez **DNA Reseller Hosting** (listé sous *Other*).

| Champ | Que saisir |
|---|---|
| **Address** | Le serveur du panneau. Laissez l'interrupteur **https://** activé. Préférez un nom d'hôte couvert par le certificat du panneau ; une adresse IP ne passe la vérification du certificat que si le certificat couvre cette IP |
| **Port** | `2087` est prérempli pour cPanel. Pour Plesk, cliquez sur **Use Custom Port** et saisissez `8443` |
| **Verify SSL Certificate** | Laissez-le activé. Désactivez-le uniquement pour un panneau qui utilise un certificat auto-signé |
| **Username** | Votre nom d'utilisateur revendeur sur ce panneau |
| **Password** | **cPanel :** un jeton d'API WHM créé en tant que ce revendeur. **Plesk :** une clé secrète, ou le mot de passe du panneau |
| **Hostname** | Le nom d'hôte du serveur du panneau ; WiseCP l'affiche aussi sur les fiches serveur et service. S'il s'agit d'un nom d'hôte complet comme `server.example.com`, le module s'y connecte à la place de **Address**, si bien que la vérification du certificat fonctionne même lorsque **Address** contient une IP |

![Paramètres du serveur dans WiseCP 5](https://raw.githubusercontent.com/domainreseller/reseller-hosting-wisecp/main/docs/images/v5-server-settings.png)

### 3️⃣ Testez la connexion

Cliquez sur **Test Connection**. Un résultat vert confirme les identifiants et le panneau détecté ; en
cas d'échec, le code HTTP ou l'erreur propre au panneau s'affiche. Voir
[Journaux et dépannage](#-journaux-et-dépannage).

### 4️⃣ Créez un groupe de serveurs *(facultatif)*

Si vous avez plus d'un serveur, créez un groupe sous **Server Groups** et liez le produit au groupe
plutôt qu'à un seul serveur.

> ⚠️ **Gardez chaque groupe homogène par panneau.** La liste des packs du formulaire produit provient
> d'un seul serveur. Si un groupe mélange cPanel et Plesk, le nom choisi peut ne pas exister sur
> l'autre panneau, et une commande qui y aboutit échoue avec *pack introuvable*. Le module enregistre
> le **nom** du pack, pas l'identifiant du panneau ; un nom qui existe sur chaque serveur du groupe
> fonctionne donc sur tous.

### 5️⃣ Configurez le produit

Créez le produit d'hébergement et choisissez **DNA Reseller Hosting** sous **Module Assignment**. Dans
l'onglet **Automation** du produit, choisissez **Single Server** (ou un groupe de serveurs) et le serveur.
Le module affiche alors un seul champ :

| Paramètre | Valeur |
|---|---|
| **Package / Plan** | La liste récupérée en direct depuis le serveur ; le panneau détecté est indiqué dans le libellé, par exemple *Package / Plan (cPanel / WHM)*. Si le serveur est injoignable, le champ devient une zone de texte avec la raison affichée en dessous |

> 💡 **Ne vous souciez pas du préfixe des packs sur cPanel.** Un pack listé `reseller_basic` sur le
> panneau peut être saisi comme `basic` ; le module résout lui-même le préfixe.

> ℹ️ Les **Resource Limits** du même onglet ne sont pas envoyées au panneau. L'espace disque, la bande
> passante et les autres limites du compte proviennent du pack ou du plan choisi ; WiseCP le signale
> aussi sur l'écran du service.

**Enregistrez — le module est prêt à l'emploi.** 🎉

---

## 🔍 Journaux et dépannage

Chaque requête que le module envoie au panneau, ainsi que la réponse renvoyée, est écrite dans le
**journal des modules** tant que la journalisation des modules est activée dans WiseCP. Chaque entrée
est étiquetée avec l'appel au panneau, par exemple `createacct` ou `webspace.add`.

> 🔐 Le jeton ou le mot de passe du serveur, chaque mot de passe de compte que le module génère ou
> modifie, ainsi que les jetons de session d'authentification unique sont masqués par `***` **avant
> toute écriture**, dans la requête comme dans la réponse.

### Erreurs fréquentes

| Symptôme | Cause et solution |
|---|---|
| `HTTP 403`, ou *le serveur a répondu avec l'API utilisateur cPanel* | Le revendeur derrière le jeton n'a pas le privilège WHM pour cet appel. Accordez les privilèges de liste, création, suspension et résiliation de comptes, de mot de passe, de changement de pack, de liste des packs, de trafic et de session dans **WHM → Resellers → Edit Reseller's ACL List**, puis créez le jeton **en tant que ce revendeur** |
| Une erreur de certificat | Le certificat ne couvre pas l'adresse saisie. Saisissez le nom d'hôte couvert par le certificat, ou désactivez **Verify SSL Certificate** pour un panneau auto-signé |
| Plesk **11003** | La clé secrète a été émise pour une autre IP. Créez-en une nouvelle pour l'adresse depuis laquelle WiseCP se connecte, ou utilisez le mot de passe du panneau |
| *Refusé par sécurité : l'abonnement … n'a pas été créé par ce service* | Sur Plesk, le module n'agit que sur les clients qu'il a créés pour ce service. L'abonnement a été créé à la main ou par un autre service |

---

## 🧩 Bon à savoir

<details>
<summary><b>Les relances sont sans risque</b></summary>

<br>

La file d'attente des modules de WiseCP relance une création qui a échoué. Le module enregistre le nom
d'utilisateur et le mot de passe chiffré avant d'appeler le panneau ; une relance les réutilise donc :
un compte créé mais dont la réponse s'est perdue est repris au lieu d'échouer sur *existe déjà*. La
résiliation d'un compte déjà supprimé est considérée comme effectuée.

</details>

<details>
<summary><b>Différences entre les panneaux</b></summary>

<br>

- **Chaque opération Plesk vérifie la propriété.** Le client que crée le module porte le numéro de
  service comme identifiant externe, et cette marque est vérifiée avant toute opération. Le module ne
  peut jamais agir sur un abonnement créé à la main.
- **Le changement de domaine d'un service Plesk actif** n'est pas répercuté sur le panneau : un
  abonnement Plesk est retrouvé par son domaine, donc les opérations suivantes signaleront qu'aucun
  abonnement n'existe. Renommez aussi l'abonnement dans Plesk. Sur cPanel, le compte continue de
  servir l'ancien domaine jusqu'à ce que vous le changiez dans le panneau.
- **Protection contre les domaines en double.** La résiliation est refusée tant que le même domaine
  est encore rattaché à un autre service actif ou suspendu sur le même serveur.

</details>

<details>
<summary><b>Détection du panneau</b></summary>

<br>

Le type de panneau n'est jamais configuré. Au premier appel, le module sonde le serveur et retient
pendant une semaine quel panneau a répondu ; **Test Connection** sonde toujours à nouveau.

Le port décide seulement quel panneau est essayé **en premier** : `8443` et `8880` essaient Plesk en
premier, tout autre port essaie cPanel en premier. Une mauvaise supposition ralentit la détection,
elle ne la casse pas. Pendant la détection, le module envoie aussi l'identifiant configuré au point
d'accès API de l'autre panneau sur le **même** serveur ; il ne contacte jamais aucun autre hôte.

</details>

<details>
<summary><b>Identifiants</b></summary>

<br>

Les deux panneaux prennent leur identifiant dans le champ **Password**, que WiseCP stocke chiffré. Le
champ **Access Hash** est stocké en texte brut ; ce module ne le propose donc pas et ne le lit jamais.
Sur Plesk, le module essaie d'abord l'identifiant comme clé secrète et se rabat de lui-même sur
l'identifiant de connexion et le mot de passe.

</details>

<details>
<summary><b>WiseCP 3</b></summary>

<br>

Le paquet WiseCP 3 (`v3/coremio/`) offre le même provisionnement dans les écrans propres à WiseCP 3 :

1. **Products / Services → Hosting/Server → Shared Server Settings → Add New Shared Server.** Choisissez
   `DNAHosting` comme **Server Automation Type** et renseignez **IP Address**, **Username**, **Password**
   (le jeton API WHM, la clé secrète Plesk ou le mot de passe Plesk), **Connect with SSL** et **Port**
   (`2087` pour cPanel, `8443` pour Plesk). Le champ **Hostname** n'y est qu'une étiquette ; le module
   se connecte à **IP Address**. Cliquez sur **Test Connection**.
2. **Products / Services → Hosting/Server → Web Hosting Packages** → ouvrez le paquet → onglet **Module
   Settings**. Choisissez le serveur ou le groupe ; le module affiche le **Detected panel** et la liste
   **Package / Plan**.

Différences avec le paquet WiseCP 5 :

- WiseCP 3 n'a pas d'option de vérification du certificat pour les modules serveur. Lorsque le
  **Hostname** du serveur est un nom d'hôte complet couvert par le certificat du panneau, le paquet
  s'y connecte et vérifie le certificat ; avec une simple adresse IP, il se connecte à l'IP sans
  vérification.
- L'espace client affiche l'utilisation du disque et de la bande passante ainsi qu'un bouton de
  connexion au panneau ; les cartes de vue d'ensemble du service de WiseCP 5 n'existent pas sous WiseCP 3.
- Le changement de domaine d'un service actif est refusé sur Plesk au lieu d'être laissé à l'opérateur.

</details>

<details>
<summary><b>Hors périmètre</b></summary>

<br>

Les outils de gestion des e-mails, des bases de données et des fichiers, la vente de comptes
revendeur, l'import de comptes existants dans WiseCP, et un bouton *se connecter en tant
qu'administrateur du serveur*. Le module ne détient qu'un identifiant **revendeur**, jamais root.
L'import est volontairement exclu : un abonnement Plesk importé ne porterait pas la marque de
propriété de ce module, et toute opération ultérieure sur celui-ci serait refusée.

</details>

---

## 📄 Journal des modifications

Les changements version par version se trouvent dans [CHANGELOG.md](CHANGELOG.md).

---

<div align="center">

**DNA Reseller Hosting** · Module revendeur cPanel & Plesk pour WiseCP

[domainnameapi.com](https://www.domainnameapi.com) · [Panneau revendeur](https://dm.domainnameapi.com/hosting)

</div>
