# Fulgurite

Fulgurite est une interface web auto-hébergée et source-available pour exploiter des sauvegardes Restic.

Elle aide les équipes techniques à gérer les dépôts, jobs de sauvegarde, jobs de copie, explorations de snapshots, restaurations, notifications, accès utilisateurs et intégrations API depuis une interface web.

> Les fichiers anglais à la racine du dépôt sont les documents de référence. Cette version française est fournie pour faciliter la lecture.

## Statut Du Projet

Fulgurite est en développement actif. Le dépôt propre contient actuellement le cœur applicatif, l’interface publique, les scripts opérationnels, les tests, les traductions et la documentation validée.

Les fichiers Docker, le site web, les métadonnées GitHub et certains documents de planification restent exclus jusqu’à revue.

## Fonctionnalités

- Gestion des dépôts Restic et des secrets.
- Configuration de jobs de sauvegarde locaux ou distants.
- Réplication avec des jobs de copie.
- Exploration de snapshots, recherche de fichiers et restaurations.
- Planification via un scheduler central et un worker.
- Notifications multicanales.
- Utilisateurs, rôles, scopes, TOTP et WebAuthn.
- API à tokens scopés et webhooks signés.
- Thèmes utilisateur et thèmes avancés développeur.

## Documentation

- [Documentation française](../../docs/fr/01-index.md)
- [Documentation anglaise](../../docs/en/01-index.md)
- [Documentation développeur des thèmes](../../docs/fr/dev/01-index.md)
- [Developer theme documentation](../../docs/en/dev/01-index.md)

## Documents Projet En Français

- [Contribution](CONTRIBUTING.md)
- [Support](SUPPORT.md)
- [Gouvernance](GOVERNANCE.md)
- [Code de conduite](CODE_OF_CONDUCT.md)
- [Conditions contributeur](CONTRIBUTOR_TERMS.md)
- [Licence](LICENSING.md)
- [Sécurité](SECURITY.md)
- [Roadmap](ROADMAP.md)

## Licence

Fulgurite est source-available, mais pas Open Source au sens OSI.

Le dépôt public est distribué sous [PolyForm Noncommercial 1.0.0](../../LICENSE). L’usage commercial nécessite un accord écrit séparé.

Voir [LICENSING.md](LICENSING.md) pour le résumé français, et [../../LICENSING.md](../../LICENSING.md) pour le document anglais de référence.
