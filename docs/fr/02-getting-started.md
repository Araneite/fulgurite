---
title: "Documentation Fulgurite — Installation et prise en main"
description: "Installer Fulgurite, comprendre le wizard, choisir la base de données et lancer la première sauvegarde."
lang: "fr"
section: "user"
---
# Installation et prise en main

Le setup de Fulgurite ne demande ni pipeline de build ni dépendances lourdes. En revanche, il mérite un minimum de méthode: vérifier les prérequis, choisir la bonne base, générer la configuration web server adaptée, créer l'administrateur initial et préparer les répertoires dont l'application a besoin.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md) (page actuelle)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md)
- [06. Jobs de copie](06-copy-jobs.md)
- [07. Explorateur et restaurations](07-explorer-and-restores.md)
- [08. Planification et worker](08-scheduler-and-worker.md)
- [09. Monitoring et performance](09-monitoring-and-performance.md)
- [10. Notifications](10-notifications.md)
- [11. Hôtes et SSH](11-hosts-and-ssh.md)
- [12. Sécurité et accès](12-security-and-access.md)
- [13. Référence des paramètres](13-settings-reference.md)
- [14. API et intégrations](14-api-and-integrations.md)
- [15. Thèmes et personnalisation](15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Prérequis vérifiés par l'application

Le wizard commence par un contrôle de l'environnement. Cette étape vous indique rapidement si le serveur est dans un état déployable.

| Contrôle | Pourquoi c'est important | Ce que le wizard vérifie |
| --- | --- | --- |
| **Version PHP** | Le projet cible un runtime moderne et des API récentes côté serveur. | PHP 8.2 ou plus, avec les extensions requises pour le cœur du produit. |
| **PDO et drivers** | La base peut être SQLite, MySQL/MariaDB ou PostgreSQL. | `pdo_sqlite`, `pdo_mysql` et `pdo_pgsql` selon le moteur choisi. L'extension `pdo_sqlite` est **strictement obligatoire** dans tous les cas, même si vous choisissez MySQL ou PostgreSQL comme base principale — Fulgurite s'en sert pour sa base de recherche interne et pour certaines opérations runtime. |
| **Fonctions annexes** | Certaines fonctions dépendent d'extensions optionnelles. | `gd` et `zip` sont facultatives. `zip` devient néanmoins requise si vous voulez importer des thèmes personnalisés au format zip depuis l'administration. |
| **Permissions d'écriture** | L'application doit pouvoir stocker sa configuration et ses données runtime. | Écriture sur `config/` (configuration runtime) et sur `data/` (base SQLite, logs, secrets, passwords, clés SSH, cache, thèmes). |

## Déverrouillage par bootstrap token one-shot

L'accès à `public/setup.php` n'est plus ouvert en direct. Le wizard est protégé par `SetupGuard` et nécessite un token bootstrap à usage unique généré côté serveur.

```text
php scripts/setup-bootstrap.php create --ttl=30
```

- Le token est créé en CLI puis saisi dans l'écran de déverrouillage du setup.

- Le fichier de bootstrap est stocké temporairement dans `data/setup-bootstrap.json`.

- Le setup ouvre une session d'installation courte, renouvelée tant que vous progressez.

- En fin d'installation, le bootstrap est consommé et supprimé automatiquement.

> Ce mécanisme évite de laisser un assistant d'installation accessible publiquement sans preuve de contrôle serveur.

## Les 6 étapes du wizard

L'assistant d'installation suit un ordre concret. Le comprendre vous aide à savoir quoi préparer en amont et ce qui sera automatisé pour vous.

1. **Prérequis.** Vérification de l'environnement PHP, des extensions utiles et des permissions sur les répertoires attendus.

2. **Base de données.** Sélection du driver et test de connexion.

3. **Serveur web.** Détection Apache, Nginx ou LiteSpeed et génération d'un bloc adapté.

4. **Administrateur.** Saisie du premier compte admin, avec mot de passe et coordonnées de base.

5. **Application.** Réglages initiaux comme le nom d'application et le fuseau horaire.

6. **Installation.** Création des répertoires, écriture de `config/database.php`, initialisation du schéma, création du premier admin et marquage de l'instance comme installée.

## Choisir la bonne base de données

Le code prend en charge trois moteurs. Le bon choix dépend du volume, des habitudes d'exploitation et du niveau de mutualisation attendu.

### SQLite

- Option la plus simple à déployer.

- Fichier principal: `data/fulgurite.db`.

- Base de recherche séparée: `data/fulgurite-search.db`.

- Le setup active déjà un mode WAL et un timeout d'attente sur les verrous.

### MySQL / MariaDB / PostgreSQL

- Plus adaptés si votre standard interne repose déjà sur un SGBD centralisé.

- Le wizard teste la connectivité et les identifiants avant de poursuivre.

- Le schéma applicatif sera initialisé via la couche base du projet.

- Même dans ce mode, l'extension `pdo_sqlite` reste indispensable côté PHP.

## Configuration serveur générée par le wizard

Le setup génère des blocs de configuration utiles pour Apache, Nginx ou LiteSpeed, avec un pointage vers `/public` et des exemples de logs et de cache d'assets.

```text
Structure minimum attendue après installation
config/database.php
data/fulgurite.db
data/fulgurite-search.db
data/passwords/
data/ssh_keys/
data/cache/
data/themes/
data/.installed
```

## Fichiers et répertoires créés automatiquement

La phase finale du wizard prépare les fondations de l'instance.

- **Écriture de la config DB** dans `config/database.php`.

- **Création des répertoires runtime**: `data/passwords`, `data/ssh_keys`, `data/cache` et `data/themes`.

- **Initialisation du schéma** applicatif via la couche base.

- **Création du premier utilisateur admin**.

- **Application des premiers settings** comme le nom d'application et le fuseau horaire.

- **Marquage de l'instance comme installée** grâce à `data/.installed`.

## Configurer votre première sauvegarde avec le wizard Sauvegarde rapide

Le moyen le plus rapide de mettre en place une première sauvegarde est d'utiliser le flux guidé **Sauvegarde rapide**. Ce wizard en 8 étapes vous accompagne de la sélection de la cible jusqu'à la confirmation du job créé, sans nécessiter de configuration manuelle préalable.

Accès: bouton **Sauvegarde rapide** dans la barre de navigation supérieure, ou depuis le dashboard.

1. **Sélection de la cible.** Choisissez où stocker la sauvegarde: hôte SSH distant, chemin local, etc.

2. **Configuration de la clé SSH.** Le wizard peut générer une nouvelle paire de clés SSH. La clé publique est affichée pour être copiée dans le fichier `authorized_keys` de la cible. Une seule clé SSH par machine est gérée — si une clé existe déjà pour cet hôte, elle est réutilisée.

3. **Vérification de l'hôte.** Validation de l'empreinte de la clé hôte (host key fingerprint). Le wizard peut rafraîchir l'empreinte si le serveur a été réinstallé.

4. **Création du dépôt.** Initialisation du dépôt Restic sur la cible.

5. **Nom du job.** Attribution d'un nom au backup job.

6. **Planification.** Définition du calendrier d'exécution automatique.

7. **Politique de rétention.** Configuration des règles de conservation des snapshots.

8. **Confirmation.** Récapitulatif et création effective du job.

- **Contrôles preflight à chaque étape:** le wizard vérifie la connectivité SSH, l'empreinte de l'hôte, la présence de Restic sur la cible et l'espace disque disponible. Les résultats sont affichés sous forme d'icônes succès / avertissement / erreur / information.

- **Système de templates:** quatre templates intégrés couvrent les cas courants, tous internationalisés:

 - `builtin:system-server`— Serveur Linux générique: `/etc`, `/home`, `/root`, `/var/spool/cron`

 - `builtin:linux-web`— Serveur web: `/etc`, `/var/www`, `/var/spool/cron`

 - `builtin:mysql-server`— Serveur MySQL: `/etc/mysql`, `/var/lib/mysql`

 - `builtin:postgres-server`— Serveur PostgreSQL: `/etc/postgresql`, `/var/lib/postgresql`
Les utilisateurs peuvent également créer et enregistrer leurs propres templates (préfixe `custom:`) pour les réutiliser dans de futures sauvegardes rapides, ou dupliquer un modèle existant avant de l'adapter.

- **Humanisation des erreurs:** les erreurs SSH et SFTP sont traduites en messages d'action clairs et compréhensibles.

- **Sécurité:** une seule clé SSH par machine est appliquée — le wizard ne crée pas de doublon si une clé a déjà été générée pour cet hôte.

> Une fois le wizard terminé, le backup job est créé et la première sauvegarde peut être déclenchée immédiatement depuis la page du job.

## Les premiers réglages à faire sans attendre

Voici l'ordre le plus efficace pour transformer une instance fraîchement installée en un outil de sauvegarde réellement exploitable.

1. **Vérifier les Paramètres.** Fixez le fuseau horaire, le nom d'application, le mail et les intégrations éventuelles.

2. **Lancer le wizard Sauvegarde rapide.** C'est le chemin le plus direct pour créer votre premier job — voir la section [ci-dessus](#quick-backup).

3. **Ou ajouter un dépôt manuellement.** Créez votre première cible dans [Dépôts Restic](04-repositories.md) puis définissez un backup job avec chemins sources, exclusions, tags et rétention.

4. **Activer le cron central.** Rendez-vous dans [Planification et worker](08-scheduler-and-worker.md).

5. **Lancer un backup manuel.** Vérifiez le log avant de vous reposer sur la planification.

6. **Tester une restauration.** Validez très tôt que les données sont récupérables.

> Une fois ce socle en place, vous pouvez basculer vers [Concepts et navigation](03-concepts-and-navigation.md) pour comprendre plus finement la logique de l'outil.

## Et ensuite?

Le chapitre suivant explique le modèle mental de Fulgurite: ce qu'est un dépôt, ce que pilote un backup job, pourquoi le cron est centralisé et comment les scopes influencent la visibilité des utilisateurs.

[Précédent Vue d'ensemble](01-index.md) [Suivant Concepts et navigation](03-concepts-and-navigation.md)

## Parcours

- Précédent: [01. Vue d’ensemble](01-index.md)
- Suivant: [03. Concepts et navigation](03-concepts-and-navigation.md)
