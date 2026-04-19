---
title: "index"
lang: "fr"
section: "user"
---
# Comprendre tout le périmètre
de Fulgurite avant de l'exploiter

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md) (page actuelle)
- [02. Installation et prise en main](02-getting-started.md)
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


Strike. Save. Restore.

Fulgurite est une interface web source-available pour piloter des dépôts Restic, automatiser des sauvegardes et des copies, explorer les snapshots, lancer des restaurations, gérer les utilisateurs, surveiller la santé de l'instance et intégrer l'outil à votre SI.

Licence PolyForm Noncommercial 1.0.0 Source-available, auto-hébergé, sans dépendance cloud obligatoire. Le fichier LICENSE racine fait autorité. Sections documentées 15 De l'installation à l'API, toute la surface fonctionnelle couverte. 3 Bases de données supportées: SQLite, MySQL/MariaDB, PostgreSQL 10 Canaux de notification intégrés, du mail au push navigateur 4 Rôles système: viewer, operator, restore-operator, admin

## Structure de la documentation

Organisée en 3 groupes logiques pour correspondre aux profils des utilisateurs: déploiement, exploitation quotidienne et administration.

Commencer · 01–03

### Installation & Concepts

Prérequis, wizard, première configuration et modèle mental de l'outil.

Setup Stable Exploitation · 04–10

### Dépôts, Jobs & Monitoring

Dépôts Restic, backup jobs, copy jobs, explorateur, scheduler et notifications.

Ops Stable Administration · 11–15

### Sécurité & Intégrations

Hôtes SSH, utilisateurs, rôles, 2FA, thèmes, paramètres et API publique.

Admin Stable Entrée cron unique

### Architecture scheduler

Une seule ligne cron pilote tous les jobs, copies et tâches globales.

Architecture À lire Dépôt Job Snapshot Restauration

## Parcours recommandés

Le meilleur point d'entrée dépend de votre contexte. Vous n'avez pas besoin de tout lire dans l'ordre.

Je déploie une nouvelle instance Installation → Dépôts → Backup jobs → Planification 02→04→05→08 Je reprends une instance existante Concepts → Explorateur → Notifications → Monitoring 03→07→10→09 Je gère les accès et la sécurité Utilisateurs et sécurité → Paramètres 12→14 J'intègre Fulgurite à mon SI API et intégrations → Notifications → Webhooks 15→10

## Architecture fonctionnelle

## Périmètre fonctionnel actuel

Le projet ne se limite pas à une interface de consultation Restic. Il expose déjà un ensemble cohérent de fonctions d'exploitation, d'administration et d'intégration.

### Stockage et données

- **Dépôts Restic** avec secrets en fichier ou via Infisical.

- **Exploration des snapshots** avec navigation, recherche, diff et téléchargements.

- **Rétentions** appliquées à l'échelle d'un job ou d'un dépôt.

### Exécution et automatisation

- **Backup jobs** locaux ou distants avec hooks, tags, exclusions et retry policy.

- **Copy jobs** pour la réplication vers une seconde cible, y compris en `rclone:...`.

- **Cron central**, tâches globales et worker dédié pour la file interne.

### Sécurité et administration

- **Utilisateurs, rôles, permissions et scopes** sur dépôts et hôtes.

- **TOTP, WebAuthn, sessions et re-auth** pour les actions sensibles.

- **Thèmes, API, webhooks et observabilité** pour adapter et intégrer l'instance.

> Une idée importante traverse toute la documentation: Fulgurite s'appuie sur une **entrée cron unique**. Il n'y a pas une ligne cron par job. Le cron central décide quels backups, copies et tâches globales doivent partir.

## Parcours recommandés selon votre besoin

Vous n'avez pas besoin de tout lire dans l'ordre. Le meilleur point d'entrée dépend du contexte dans lequel vous arrivez sur Fulgurite.

1. **Je déploie une nouvelle instance.** Commencez par [Installation et prise en main](02-getting-started.md), puis lisez [Dépôts Restic](04-repositories.md), [Backup jobs](05-backup-jobs.md) et [Planification et worker](08-scheduler-and-worker.md).

2. **Je dois exploiter une instance existante.** Passez d'abord par [Concepts et navigation](03-concepts-and-navigation.md), puis consultez [Explorateur et restaurations](07-explorer-and-restores.md), [Notifications](10-notifications.md) et [Suivi, logs et performance](09-monitoring-and-performance.md).

3. **Je gère les accès ou la sécurité.** Lisez [Utilisateurs et sécurité](12-security-and-access.md), puis complétez avec [Référence des paramètres](13-settings-reference.md).

4. **Je veux intégrer Fulgurite à un SI.** Dirigez-vous vers [API et intégrations](14-api-and-integrations.md), puis revenez sur [Notifications](10-notifications.md) pour les canaux et politiques de diffusion.

## Carte de la documentation

Chaque page est construite pour être lisible seule, mais l'ensemble suit une progression logique allant de l'installation vers l'exploitation avancée.

| Page | Ce que vous y trouverez | Quand la lire |
| --- | --- | --- |
| **Installation et prise en main** | Prérequis, wizard d'installation, base de données, création du premier admin et première configuration. | Avant le premier lancement ou lors d'une reprise de projet. |
| **Concepts et navigation** | Modèle mental de Fulgurite, relation entre dépôts, snapshots, jobs, restaurations, scheduler et scopes d'accès. | Quand l'interface semble riche ou quand il faut former un nouvel utilisateur. |
| **Dépôts / Jobs / Explorateur** | Les briques métier principales pour sauvegarder, répliquer et retrouver les données. | Pour toute opération de sauvegarde ou de restauration. |
| **Sécurité / Paramètres / API** | Accès, rôles, 2FA, passkeys, politiques, API publique, webhooks signés et intégrations externes. | Pour industrialiser l'outil ou ouvrir l'usage à plusieurs équipes. |

## Architecture fonctionnelle en une lecture

Le produit peut être lu comme une chaîne de traitement simple: on déclare des dépôts, on attache des tâches, on produit des snapshots, on les explore ou on les restaure, puis on supervise le tout avec des notifications, des rôles et des intégrations.

### Flux métier principal

- **1. Dépôt**: déclaration d'un backend Restic avec son secret et sa politique d'alerte.

- **2. Job**: ajout d'un backup job ou d'un copy job avec planification, notifications, retry et rétention.

- **3. Exécution**: lancement manuel, cron central ou worker selon le contexte.

- **4. Snapshot**: le contenu devient consultable depuis l'explorateur et indexable pour la recherche.

- **5. Restauration**: restauration locale ou distante, complète ou partielle, avec historique et logs.

### Flux d'administration transverse

- **Accès**: utilisateurs, invitations, rôles, permissions, scopes, sessions et 2FA.

- **Surveillance**: dashboard, notifications, logs d'activité, logs cron, page performance et file de jobs.

- **Personnalisation**: thèmes, branding, préférences utilisateur et page de démarrage.

- **Intégrations**: SMTP, chat ops, push web, Infisical, API REST et webhooks sortants.

> La suite la plus naturelle si vous commencez tout juste est [Installation et prise en main](02-getting-started.md). Si vous voulez comprendre le langage de l'application avant de configurer quoi que ce soit, passez plutôt par [Concepts et navigation](03-concepts-and-navigation.md).

## Prochaine étape

Ouvrez maintenant le chapitre d'installation si vous déployez une nouvelle instance, ou le chapitre des concepts si vous voulez d'abord vous approprier le modèle de l'outil.

[Suivant Installation et prise en main](02-getting-started.md)

## Parcours

- Suivant: [02. Installation et prise en main](02-getting-started.md)
