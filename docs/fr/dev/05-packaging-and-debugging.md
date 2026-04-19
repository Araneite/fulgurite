---
title: "Fulgurite developer docs - Packaging and debugging"
description: "Packaging des themes, contraintes de securite, installation et checklist de debug."
lang: "fr"
section: "developer"
---
# Preparer le package final et debugger un theme

Un theme n est pas termine quand le rendu vous plait. Il faut aussi verifier que son archive respecte les contraintes du moteur, que ses fichiers sont dans la bonne allowlist et que le shell tient sur desktop comme sur mobile.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](../01-index.md)
- [02. Installation et prise en main](../02-getting-started.md)
- [03. Concepts et navigation](../03-concepts-and-navigation.md)
- [04. Dépôts Restic](../04-repositories.md)
- [05. Jobs de sauvegarde](../05-backup-jobs.md)
- [06. Jobs de copie](../06-copy-jobs.md)
- [07. Explorateur et restaurations](../07-explorer-and-restores.md)
- [08. Planification et worker](../08-scheduler-and-worker.md)
- [09. Monitoring et performance](../09-monitoring-and-performance.md)
- [10. Notifications](../10-notifications.md)
- [11. Hôtes et SSH](../11-hosts-and-ssh.md)
- [12. Sécurité et accès](../12-security-and-access.md)
- [13. Référence des paramètres](../13-settings-reference.md)
- [14. API et intégrations](../14-api-and-integrations.md)
- [15. Thèmes et personnalisation](../15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](01-index.md)
- [02. Thèmes à variables](02-theme-variables.md)
- [03. Thèmes avancés](03-advanced-themes.md)
- [04. Contexte et template tags](04-context-and-template-tags.md)
- [05. Packaging et debug](05-packaging-and-debugging.md) (page actuelle)


## Structure ZIP attendue

L archive doit contenir a sa racine `theme.json`, et si besoin `style.css`, `README.md`, `LICENSE` ainsi que les dossiers `slots/`, `parts/` et `pages/`. Un dossier racine unique est tolere et sera retire automatiquement pendant l installation.

- Taille max de l archive: 2 Mo.

- Taille decompressee max: 8 Mo.

- Nombre de fichiers limite.

- Profondeur de dossier limitee.

- Pas de fichiers caches, pas de path traversal, pas de dossiers hors allowlist.

## Modes d installation a garder en tete

Un theme peut etre installe depuis le disque, depuis une archive ZIP ou depuis une URL HTTPS. Dans tous les cas, un theme advanced doit etre traite comme du code PHP execute sur le serveur.

> Ne livrez pas un theme advanced comme s il s agissait d une simple ressource graphique. C est une extension applicative, et elle doit etre revue avec le meme niveau d exigence qu un patch du core.

## Checklist de debug rapide

- Le theme n apparait pas: verifier `theme.json`, `id`, type et variables requises.

- Le CSS ne change pas: verifier le theme actif et le scope `[data-theme="id"]`.

- Un override est ignore: verifier le nom exact du slot ou de la part et son emplacement.

- Une page custom ne se charge pas: verifier que le core appelle vraiment le point d extension vise.

- Le rendu casse sur mobile: verifier la largeur du shell, la sidebar, la topbar et les tableaux trop larges.

## Checklist finale

- `id` stable et court.

- `theme.json` valide et coherent avec le nom du dossier.

- Variables requises presentes.

- `style.css` scope avec `[data-theme="id"]`.

- Sorties dynamiques echappees.

- Pas d acces directs inutiles aux classes internes.

- Verification visuelle desktop et mobile.

- Verification du dashboard si `pages/dashboard.php` est fourni.

## Section complete

Vous avez maintenant une base web complete pour developper, verifier et distribuer des themes Fulgurite.

[Precedent Contexte et template tags](04-context-and-template-tags.md) [Retour Vue d ensemble](01-index.md)

## Parcours

- Précédent: [04. Contexte et template tags](04-context-and-template-tags.md)
