---
title: "Documentation Fulgurite — Explorateur et restaurations"
description: "Explorer les snapshots, comparer des versions, télécharger des fichiers et lancer des restaurations complètes ou partielles."
lang: "fr"
section: "user"
---
# Explorer les snapshots et restaurer des données

L'explorateur permet de naviguer dans les snapshots d'un dépôt, de rechercher ou comparer du contenu, de télécharger des fichiers ou des dossiers, de gérer les tags, de supprimer des snapshots, d'appliquer une rétention et de lancer des restaurations locales ou distantes.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md)
- [06. Jobs de copie](06-copy-jobs.md)
- [07. Explorateur et restaurations](07-explorer-and-restores.md) (page actuelle)
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


## Parcourir un dépôt et ses snapshots

L'explorateur se concentre sur un dépôt, puis sur un snapshot et un chemin. Cette logique rend la navigation précise et conserve le contexte technique du contenu affiché.

- Vous choisissez d'abord le dépôt, puis le snapshot à explorer.

- La navigation se fait ensuite dans l'arborescence du snapshot.

- La recherche de fichiers s'appuie sur l'index `snapshot_search_index` et la table `snapshot_index_cache`, alimentés incrémentalement à chaque nouveau snapshot. Un snapshot fraîchement créé n'est donc interrogeable qu'une fois son indexation terminée.

- La taille maximale de certains affichages ou extractions peut être bornée par les paramètres d'exploration.

## Comparer des snapshots ou un fichier entre deux snapshots

Deux types de comparaison sont déjà présents dans l'interface.

### Diff de snapshots

- Compare un snapshot courant avec un autre.

- Les différences sont filtrées par ajouts, suppressions et modifications.

- Utile pour comprendre l'évolution globale d'un système sauvegardé.

### Diff de fichier

- Compare le contenu d'un fichier précis entre deux snapshots.

- La fonction utilise l'extraction du contenu via Restic puis un calcul de diff côté application.

- Pratique pour une validation fine avant restauration.

## Télécharger des fichiers ou des dossiers

L'explorateur permet de sortir du contenu sans passer par une restauration complète.

- **Téléchargement de fichier** direct depuis un snapshot.

- **Téléchargement de dossier** sous forme d'archive, avec un support des formats `tar.gz` et `zip`.

- Le dossier est reconstitué temporairement avant empaquetage.

- Ces actions sont journalisées dans les logs d'activité.

> La taille maximale d'un téléchargement de fichier unique est bornée par le paramètre `explore_max_file_size_mb`(Paramètres → Données). Pour les téléchargements d'archive, gardez à l'esprit que les très grosses arborescences peuvent saturer la RAM du serveur au moment de la reconstitution temporaire: préférez alors une restauration ciblée.

## Restaurations complètes ou partielles

Fulgurite sait restaurer un snapshot entier ou une sélection de fichiers. Le même dépôt peut être restauré localement ou à distance, selon vos droits et les clés SSH disponibles.

| Mode | Ce qu'il fait | Champs utilisés |
| --- | --- | --- |
| **Restauration complète locale** | Rejoue un snapshot entier vers un chemin du serveur Fulgurite. | `restore_default_target` et chemin cible saisi dans la fenêtre modale. |
| **Restauration complète distante** | Envoie le contenu vers une machine distante via SSH. | Clé SSH, chemin distant, éventuel filtre `include`. |
| **Restauration partielle locale** | Restaure uniquement la sélection de fichiers ou dossiers choisis dans l'explorateur. | Chemin local par défaut pour les restaurations partielles. |
| **Restauration partielle distante** | Projette cette sélection vers une machine distante. | Clé SSH et chemin distant par défaut des restaurations partielles. |

### Destination gérée ou chemin original

Avant de lancer une restauration, le `RestoreTargetPlanner` calcule la destination et affiche un aperçu du chemin résolu. Aucune saisie libre de chemin n'est possible.

### Mode géré (défaut)

- Place les fichiers dans un sous-répertoire sûr sous la racine configurée (`restore_default_target` dans les Paramètres).

- Chemin calculé: `< racine > /< hostname > /< nom-du-job >` pour les jobs de sauvegarde, ou `< racine > /< hostname > /< nom-du-dépôt >` pour les restaurations directes depuis un dépôt.

- Pour les restaurations distantes, le répertoire de destination est créé via SSH si nécessaire.

- Paramètre API: `destination_mode=managed`, `append_context_subdir=true`.

### Mode original (admin uniquement)

- Restaure vers le chemin exact enregistré dans le snapshot.

- Réservé au rôle `admin`. L'interface affiche un avertissement explicite lors de la sélection de ce mode.

- Pour les restaurations distantes, le flag `--delete` n'est pas utilisé afin d'éviter les suppressions accidentelles.

- Paramètre API: `destination_mode=original`.

Le chemin de destination résolu (`resolved_target`) est retourné dans la réponse API et enregistré dans l'historique des restaurations. Les restaurations complètes et partielles (sélection de fichiers) supportent toutes les deux ces deux modes.

Pour les restaurations distantes, le transfert s'effectue via SSH avec `rsync`. Le binaire `rsync` est requis pour les synchronisations et restaurations distantes: il n'existe pas de fallback automatique vers `scp`. Le wizard de setup vérifie sa présence et peut proposer son installation. En local uniquement, si `rsync` n'est pas utilisable, Fulgurite peut recopier l'arborescence extraite via PHP.

> La fenêtre modale de restauration complète rappelle explicitement que l'opération écrasera les fichiers existants. C'est un point de vigilance important en production.

## Historique des restaurations

La page dédiée aux restaurations permet de garder une trace consultable et paginée de toutes les récupérations.

- Date et heure de lancement.

- Dépôt, snapshot et mode utilisé.

- Destination locale ou distante (chemin résolu `resolved_target` enregistré).

- Mode de destination utilisé (géré ou original).

- Filtre de chemin éventuellement appliqué.

- Utilisateur initiateur, statut, durée et logs techniques.

- Taille de page configurable dans les Paramètres.

Chaque restauration est historisée dans la table `restore_runs` et reste consultable depuis l'onglet **Historique des restaurations**: même si un opérateur n'a plus accès à l'explorateur, la trace de l'opération (auteur, date, statut, destination) demeure disponible pour audit.

## Tags, suppression de snapshots, rétention et vérifications

L'explorateur n'est pas seulement un outil de lecture. Il donne aussi accès à des actions de gestion avancée sur les snapshots et sur le dépôt.

- **Gestion des tags** de snapshot.

- **Suppression de snapshot** si les permissions adéquates sont présentes.

- **Rétention de dépôt** avec simulation ou application, et mémorisation de la politique dans la base.

- **Vérification d'intégrité** du dépôt.

> Les actions de suppression et de rétention sont définitives du point de vue des données cibles. Utilisez le mode simulation de rétention quand il est proposé et limitez ces permissions aux profils appropriés.

## Et pour l'automatisation?

Le chapitre suivant explique comment le cron central, les tâches globales et le worker coopèrent pour lancer les jobs, traiter la file interne et exécuter les opérations de fond.

[Précédent Copy jobs](06-copy-jobs.md) [Suivant Planification et worker](08-scheduler-and-worker.md)

## Parcours

- Précédent: [06. Jobs de copie](06-copy-jobs.md)
- Suivant: [08. Planification et worker](08-scheduler-and-worker.md)
