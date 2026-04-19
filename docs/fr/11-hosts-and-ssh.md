---
title: "Documentation Fulgurite — Hôtes et clés SSH"
description: "Configurer les hôtes distants et les clés SSH pour les sauvegardes et restaurations distantes."
lang: "fr"
section: "user"
---
# Gérer les hôtes distants et les clés SSH

Les fonctions distantes ne reposent pas uniquement sur Restic. Elles s'appuient aussi sur une couche SSH gérée dans l'interface: hôtes pour les sauvegardes via SSH, clés pour les restaurations distantes, les tests et le déploiement initial des accès.

## Navigation

### Documentation utilisateur

- [01. Vue d’ensemble](01-index.md)
- [02. Installation et prise en main](02-getting-started.md)
- [03. Concepts et navigation](03-concepts-and-navigation.md)
- [04. Dépôts Restic](04-repositories.md)
- [05. Jobs de sauvegarde](05-backup-jobs.md)
- [06. Jobs de copie](06-copy-jobs.md)
- [07. Explorateur et restaurations](07-explorer-and-restores.md)
- [08. Planification et worker](08-scheduler-and-worker.md)
- [09. Monitoring et performance](09-monitoring-and-performance.md)
- [10. Notifications](10-notifications.md)
- [11. Hôtes et SSH](11-hosts-and-ssh.md) (page actuelle)
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


## Page Hôtes

Les hôtes représentent les machines sur lesquelles certains backup jobs pourront s'exécuter via SSH. Chaque hôte enregistré en base regroupe un ensemble de champs structurés qui décrivent complètement la connexion.

- **Nom lisible**: libellé affiché dans l'interface pour identifier la machine.

- **`hostname`**: nom DNS ou adresse IP de la machine cible.

- **`user`**: utilisateur SSH employé pour la connexion.

- **`port`**: port SSH (par défaut `22`).

- **`ssh_key_id`**: référence vers une clé SSH déjà déclarée dans la page Clés SSH.

- **`sudo_password_ref`**: référence SecretStore optionnelle contenant le mot de passe sudo, pour les opérations nécessitant une élévation ciblée.

- **`sudo_password_file`**: champ historique de compatibilité, lu seulement comme fallback legacy et migré vers SecretStore quand c'est possible.

- Description libre, test de connexion et assistant de setup guidé.

La vérification de connexion (`check`) utilise la commande `ssh` native du serveur et capture le résultat (code de retour, stdout, stderr) pour afficher un diagnostic lisible dans l'interface.

## Page Clés SSH

La gestion des clés permet de générer, importer, tester et déployer les accès nécessaires à certains flux distants.

- Génération d'une clé privée/publique avec nom, user, host et port.

- Import d'une clé existante.

- Affichage de la clé publique et bouton de copie.

- Commande prête à coller pour l'ajout dans `authorized_keys`.

- Test de connexion.

- Déploiement assisté de la clé sur une machine distante avec mot de passe ponctuel.

Les clés privées générées ou importées sont stockées dans `SecretStore`. Le champ historique `private_key_file` conserve une référence, souvent de la forme `secret://...`, et peut encore pointer vers un ancien fichier local sur des installations migrées. Quand le client SSH natif a besoin d'un chemin fichier, Fulgurite matérialise au runtime un fichier temporaire strictement protégé, puis le supprime après usage; ce fichier temporaire n'est pas le stockage principal de la clé.

## Différence entre hôte et clé SSH

| Brique | Usage principal | Exemples |
| --- | --- | --- |
| **Hôte** | Représenter une machine d'exécution distante pour un backup job. | Sauvegarder `/var/www` sur un serveur applicatif via SSH. |
| **Clé SSH** | Servir aux restaurations distantes, aux tests et au déploiement des accès. | Envoyer une restauration vers une autre machine via rsync/SSH. |

## Sudo et chemins protégés

Le mot de passe sudo optionnel sur un hôte n'est pas systématique, mais il devient utile si les chemins à sauvegarder ne sont pas lisibles par l'utilisateur SSH standard. Il permet à Fulgurite de tenter des exécutions nécessitant une élévation ciblée. Le mot de passe n'est jamais stocké directement en base: le mécanisme courant écrit une référence `sudo_password_ref` vers `SecretStore`. Le champ `sudo_password_file` reste uniquement un fallback legacy pour les anciennes installations et peut être migré automatiquement vers SecretStore.

> Comme pour toute automatisation privilégiée, limitez ces configurations aux cas nécessaires, isolez les hôtes par périmètre et combinez-les avec des scopes utilisateurs stricts.

## Suite

Le chapitre suivant couvre le profil utilisateur, les rôles, la matrice de permissions, les sessions, le 2FA, WebAuthn et les politiques de sécurité du produit.

[Précédent Notifications](10-notifications.md) [Suivant Utilisateurs et sécurité](12-security-and-access.md)

## Parcours

- Précédent: [10. Notifications](10-notifications.md)
- Suivant: [12. Sécurité et accès](12-security-and-access.md)
