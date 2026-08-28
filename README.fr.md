<h1 align="center">DNA Reseller Hosting</h1>

<p align="center">
  <strong>Vendez de l'hébergement mutualisé sur cPanel/WHM et Plesk avec un seul module serveur WiseCP.</strong><br>
  Un module, deux panneaux — vous ne choisissez jamais le type de panneau, le module le trouve lui-même.
</p>

<p align="center">
  <img alt="WiseCP" src="https://img.shields.io/badge/WiseCP-self--hosted-4A90D9?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%20%E2%80%93%208.4-777BB4?style=flat-square&logo=php&logoColor=white">
  <img alt="cPanel/WHM" src="https://img.shields.io/badge/cPanel%2FWHM-pris%20en%20charge-FF6C2C?style=flat-square">
  <img alt="Plesk" src="https://img.shields.io/badge/Plesk-pris%20en%20charge-53BCE6?style=flat-square">
  <img alt="Licence" src="https://img.shields.io/badge/licence-propriétaire-lightgrey?style=flat-square">
</p>

<p align="center">
  <a href="README.md">Türkçe</a>
  · <a href="README.en.md">English</a>
  · <a href="README.de.md">Deutsch</a>
  · <a href="README.ru.md">Русский</a>
  · <a href="README.az.md">Azərbaycan</a>
  · <a href="README.ar.md">العربية</a>
  · <a href="README.es.md">Español</a>
  · <strong>Français</strong>
</p>

---

## Sommaire

- [Vue d'ensemble](#vue-densemble)
- [Matrice des fonctionnalités](#matrice-des-fonctionnalités)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Étape 1 — Ajouter un serveur](#étape-1--ajouter-un-serveur)
  - [Étape 2 — Groupes de serveurs (facultatif)](#étape-2--groupes-de-serveurs-facultatif)
  - [Étape 3 — Définir le produit](#étape-3--définir-le-produit)
- [Dépannage](#dépannage)
- [Journaux](#journaux)
- [Journal des modifications](#journal-des-modifications)
- [Licence](#licence)

---

## Vue d'ensemble

Le module pilote les deux familles de panneaux à partir d'un seul enregistrement serveur. Vous saisissez
l'IP, le nom d'utilisateur revendeur et un identifiant ; le module interroge réellement le serveur — un
véritable appel API, pas une supposition — et retient quel panneau a répondu.

| | |
|---|---|
| **Type de module** | Module serveur (Servers) WiseCP |
| **Nom du dossier** | `DNAHosting` |
| **Version** | 1.0.0 |
| **Panneaux pris en charge** | cPanel/WHM, Plesk |
| **PHP** | 7.4 – 8.4 |
| **Langues de l'interface** | turc, anglais (`lang/tr.php`, `lang/en.php`) |

---

## Matrice des fonctionnalités

| Opération | cPanel/WHM | Plesk |
|---|:---:|:---:|
| Test de connexion et détection automatique du panneau | ✔ | ✔ |
| Création de compte | ✔ | ✔ |
| Suspension / réactivation | ✔ | ✔ |
| Résiliation | ✔ | ✔ (avec vérification de propriété) |
| Changement de mot de passe | ✔ | ✔ |
| Changement de pack / plan | ✔ | ✔ |
| Consommation disque et trafic (sur la page du service client) | ✔ | ✔ |
| Connexion au panneau en un clic — espace client | ✔ | ✔ |
| Connexion au panneau en un clic — espace administrateur | ✔ | ✔ |

---

## Prérequis

- Une installation **WiseCP** auto-hébergée à laquelle vous avez un accès administrateur
- PHP avec les extensions **cURL** et **SimpleXML** activées (présentes dans presque toutes les
  installations par défaut)
- Soit un **compte revendeur cPanel/WHM** (avec un jeton API WHM), soit un **compte revendeur Plesk**
  (avec une clé API ou directement le mot de passe du panneau)
- Un accès réseau sortant du serveur WiseCP vers le serveur du panneau, sur le port API du panneau

Aucune table de base de données n'est créée, rien n'est installé via Composer, il n'y a pas d'étape de
build.

---

## Installation

Copiez le dossier du module dans votre installation WiseCP :

```
coremio/
└── modules/
    └── Servers/
        └── DNAHosting/     ← le dossier entier va ici
```

L'installation se résume à cela. Rien à exécuter ensuite : pas de migration, pas de préchauffage de
cache, pas d'étape d'activation séparée. Le module apparaît dans la liste à la prochaine ouverture de
l'écran d'ajout de serveur.

---

## Configuration

> Les chemins de menu ci-dessous correspondent à l'interface WiseCP en anglais.

### Étape 1 — Ajouter un serveur

**Products / Services → Hosting/Server → Shared Server Settings → `Add New Shared Server`**

Remplissez la section **Server Automation Information** du formulaire :

| Champ | Ce qu'il faut saisir |
|---|---|
| **Server Automation Type** | `DNAHosting` — c'est le nom du dossier, il apparaît tel quel dans la liste |
| **IP Address** | L'adresse réelle du serveur du panneau ; c'est là que le module se connecte |
| **Username** | Votre nom d'utilisateur revendeur sur ce panneau |
| **Password** | **cPanel :** le jeton API WHM. **Plesk :** la clé API ou le mot de passe du panneau du revendeur |
| **Connect with SSL** | Cochez-le |
| **Port** | `2087` pour cPanel, `8443` pour Plesk |

Le champ **Hostname** en haut du formulaire n'est qu'une étiquette pour vous — pour se connecter, le
module utilise le champ **IP Address**, pas celui-ci. Vos serveurs apparaissent sous cette étiquette dans
l'écran de liste.

### Étape 2 — Groupes de serveurs (facultatif)

Si vous avez plusieurs serveurs, vous pouvez créer un groupe dans **Shared Server Settings → `Server
Groups`** et rattacher le produit au groupe plutôt qu'à un serveur unique. L'écran d'édition du groupe
propose deux types de répartition :

- **Toujours ajouter sur le serveur le moins rempli.**
- **Remplir un serveur entièrement, puis passer au serveur le moins rempli.**

Les serveurs se déplacent entre les listes **Unassigned → Assigned** avec `Add` / `Remove`.

> [!IMPORTANT]
> **Gardez un groupe homogène côté panneau.** La liste des packs du formulaire produit est chargée depuis
> le **seul** serveur sélectionné à cet instant. Si un groupe contient à la fois un serveur cPanel et un
> serveur Plesk, le nom de pack choisi peut n'avoir aucun équivalent sur l'autre panneau, et une commande
> qui atterrit sur ce serveur échoue avec « pack introuvable ».

### Étape 3 — Définir le produit

**Products / Services → Hosting/Server → Web Hosting Packages** → ouvrez le pack → onglet **Module
Settings**.

Sous **Server Selection**, choisissez **Single Server** ou **Server Group**, puis cochez votre serveur (ou
groupe) DNAHosting. Dès que la sélection est faite, le module dessine ses propres champs :

| Champ | Signification |
|---|---|
| **Detected panel** | Le panneau que le module a réellement trouvé sur ce serveur — par exemple `cPanel / WHM`. C'est ici que vous voyez que la détection fonctionne ; en cas de problème, le texte d'erreur s'affiche sur cette ligne. |
| **Package / Plan** | La liste des packs chargée en direct depuis ce serveur |
| **Automatic Setup** | Activé, la commande est provisionnée automatiquement ; désactivé, une validation administrateur est requise |

La liste des packs dépend du panneau :

- **cPanel :** chaque pack de la sortie `listpkgs` du serveur. Si vos packs portent le préfixe revendeur
  habituel (par exemple `bakcay328_paket1`), le module résout le préfixe lui-même.
- **Plesk :** chaque plan de service défini sur le serveur.

Choisissez le pack, remplissez le reste du formulaire comme d'habitude et enregistrez. Le produit est
désormais vendable — à la commande, tout le flux de création de compte s'exécute sur le serveur que vous
avez configuré.

---

## Dépannage

| Symptôme | Cause | Solution |
|---|---|---|
| Un appel (le plus souvent le test de connexion) échoue avec `HTTP 403`, ou le texte d'erreur mentionne une enveloppe `cpanelresult` | Le compte revendeur derrière le jeton n'a pas le privilège au niveau WHM pour cette fonction ; WHM a répondu avec l'API **utilisateur** cPanel au lieu de WHM API 1 | Dans WHM, ouvrez **Resellers → Edit Reseller's ACL List** et accordez au revendeur les privilèges utilisés par le module : liste et résumé des comptes, création de compte, suspension, résiliation, changement de mot de passe, montée en gamme de pack, liste des packs, lecture du trafic et création de session. Régénérez ensuite le jeton **en étant connecté en tant que ce revendeur** dans **WHM → Development → Manage API Tokens** — un jeton généré depuis l'interface cPanel ne donne aucun accès WHM |
| `Plesk (11003)` | La clé API a été générée pour une adresse autre que l'IP depuis laquelle WiseCP se connecte | Générez une nouvelle clé sur le serveur Plesk pour la bonne IP, ou saisissez le mot de passe du panneau dans le champ Password |
| `Plesk (1014)` | Plesk a rejeté le corps de la requête — un élément manque ou se trouve au mauvais endroit pour la version XML-API que parle ce serveur | Vérifiez que vous utilisez la version actuelle du module ; le journal du module indique précisément l'élément contesté par Plesk |
| Un texte d'erreur à la place de la liste des packs dans le formulaire produit | La détection ou l'appel des packs a échoué ; la raison est écrite sur la même ligne | Appliquez l'une des lignes ci-dessus selon l'erreur concrète affichée |

Toute autre erreur HTTP arrive avec un résumé en texte brut extrait du corps de réponse du panneau ; un
code d'état nu n'est jamais toute l'histoire. Consultez le journal du module pour la requête et la réponse
complètes.

---

## Journaux

**Tools → Logs → Module Logs**

Chaque requête envoyée par le module et chaque réponse reçue sont écrites ici, étiquetées avec le nom de
l'opération (par exemple `createacct`, `webspace.add`). Les enregistrements ne sont conservés que tant que
la fonction **Module Logs** est active ; cet interrupteur se trouve en haut de la même page.

> [!NOTE]
> Le jeton API ou mot de passe du serveur, les mots de passe de compte générés ou modifiés par le module
> et les jetons de session SSO — dans la requête comme dans la réponse — sont masqués par `***` avant
> toute écriture.

---

## Journal des modifications

Consultez [CHANGELOG.md](CHANGELOG.md) pour les changements version par version.

---

## Licence

Propriétaire. Tous droits réservés.
