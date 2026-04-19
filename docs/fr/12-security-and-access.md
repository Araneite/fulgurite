---
title: "Documentation Fulgurite — Utilisateurs et sécurité"
description: "Profil, utilisateurs, rôles, scopes, sessions, TOTP, WebAuthn, ré-authentification et politiques de sécurité."
lang: "fr"
section: "user"
---
# Gérer les accès, rôles et authentification

Fulgurite propose une gestion des comptes assez complète pour une application d'exploitation: profil utilisateur, préférences, changement de mot de passe, TOTP, WebAuthn, ré-authentification, sessions actives, invitations, scopes dépôt/hôte, suspensions et expirations de compte.

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
- [11. Hôtes et SSH](11-hosts-and-ssh.md)
- [12. Sécurité et accès](12-security-and-access.md) (page actuelle)
- [13. Référence des paramètres](13-settings-reference.md)
- [14. API et intégrations](14-api-and-integrations.md)
- [15. Thèmes et personnalisation](15-themes-and-customization.md)

### Documentation développeur

- [01. Vue d’ensemble développeur](dev/01-index.md)
- [02. Thèmes à variables](dev/02-theme-variables.md)
- [03. Thèmes avancés](dev/03-advanced-themes.md)
- [04. Contexte et template tags](dev/04-context-and-template-tags.md)
- [05. Packaging et debug](dev/05-packaging-and-debugging.md)


## Page Mon profil

Chaque utilisateur dispose d'une page personnelle qui centralise ses informations, ses préférences et ses facteurs d'authentification.

- Informations personnelles et professionnelles: nom, email, téléphone, job title.

- Préférences: langue de l'interface, fuseau horaire, page de démarrage, thème préféré. La langue est stockée par utilisateur et persiste d'une session à l'autre. Si aucune préférence n'est définie, l'instance bascule sur la locale par défaut configurée dans les Paramètres, puis sur le français. Les langues disponibles dépendent des catalogues de traduction installés sur l'instance.

- Changement de mot de passe avec vérification contre des mots de passe compromis.

- Activation ou désactivation du TOTP.

- Gestion des clés WebAuthn / passkeys.

- Vue des sessions actives, activité récente et tentatives de connexion récentes.

## Page Utilisateurs

La surface d'administration va au-delà de la simple création de comptes.

- Création manuelle d'un utilisateur ou émission d'une invitation.

- Édition du profil par un administrateur.

- Modification du rôle et de la politique d'accès.

- Activation ou désactivation du compte.

- Suspension temporaire, raison de suspension et expiration de compte.

- Réinitialisation, changement de mot de passe et révocation de sessions.

- Suppression du compte et gestion des invitations actives.

## Rôles, permissions applicatives et scopes

Les rôles système fournis par défaut sont `viewer`, `operator`, `restore-operator` et `admin`. Les rôles, niveaux et permissions sont pilotés dans `src/AppConfig.php` par `defaultRoles()`, `getRoles()`, `permissionDefinitions()` et `rolePermissions()`. Ils peuvent être renommés ou enrichis, mais leur ordre de niveau doit rester strict.

- **viewer**: accès en lecture seule aux dépôts, jobs, snapshots et statistiques.

- **operator**: peut déclencher des sauvegardes, gérer les jobs et consulter les logs.

- **restore-operator**: orienté restauration, avec droits spécifiques sur les opérations de restauration et l'explorateur.

- **admin**: accès complet à la configuration, aux utilisateurs, aux paramètres et à l'API.

- Les permissions sont résolues au niveau applicatif: dépôts, jobs, restaurations, scheduler, settings, thèmes, users, logs, stats, performance, snapshots.

- Les scopes **repo_scope** et **host_scope** limitent ce qu'un utilisateur peut voir ou manipuler.

- Un utilisateur peut donc avoir un rôle élevé tout en restant borné à un périmètre restreint.

## Sessions, fingerprint, ré-authentification et révocation

Les sessions actives sont stockées et vérifiées en base, ce qui permet des contrôles plus fins qu'une simple session PHP.

- Limites de durée absolue, d'inactivité et d'avertissement.

- Fingerprint strict reposant sur l'IP et le User-Agent si activé.

- Ré-authentification requise pour certaines actions sensibles.

- Nombre maximum de sessions simultanées par utilisateur.

- Révocation d'une session ciblée, de toutes les autres sessions, ou de toutes les sessions d'un compte.

Le fingerprint de session combine l'adresse IP et le User-Agent du client. Si la comparaison stricte est activée et qu'un de ces deux vecteurs change au cours de la vie de la session, celle-ci est immédiatement invalidée et l'utilisateur est forcé de se reconnecter. Cette protection limite les risques de vol de cookie.

Le paramètre `reauth_max_age_seconds` contrôle l'âge maximum d'une ré-authentification pour les actions sensibles (rotation de secret, suppression d'un dépôt, désactivation du 2FA, etc.). Passé ce délai, l'utilisateur doit re-saisir son mot de passe ou valider un facteur avant de poursuivre.

## TOTP et WebAuthn

Deux mécanismes sont déjà pris en charge et peuvent coexister selon la politique de l'instance: TOTP conforme au **RFC 6238** et WebAuthn (passkeys **FIDO2**).

### TOTP (RFC 6238)

- Génération d'un secret et d'une URL OTP pour QR code.

- Confirmation par code avant activation.

- Possibilité de désactivation avec ré-authentification.

### WebAuthn / passkeys FIDO2

- Enregistrement et suppression de clés de sécurité.

- Paramètres globaux pour le RP name, le RP ID, les timeouts, la vérification utilisateur et les resident keys.

- Option d'autostart WebAuthn à la connexion.

### Sélection du facteur principal et comportement à la connexion

Chaque utilisateur choisit son facteur 2FA principal dans son profil. Les options disponibles sont `classic_2fa`(TOTP), `webauthn`(clé matérielle) ou `none` si la politique de l'instance l'autorise. Lorsque plusieurs facteurs sont inscrits, c'est le facteur principal qui est demandé à la connexion. Les autres facteurs restent disponibles en tant que solution de repli.

Si le facteur principal d'un utilisateur est supprimé (par exemple, tous les codes TOTP effacés), le système bascule en authentification par mot de passe seul et invite l'utilisateur à reconfigurer son 2FA lors de la prochaine connexion.

### Ré-authentification de passage à niveau (step-up)

Certaines opérations sensibles exigent une confirmation du facteur principal même lorsque l'utilisateur est déjà connecté. Cette preuve est liée cryptographiquement à l'opération exacte — elle ne peut pas être réutilisée pour une autre action.

- Changement d'adresse e-mail ou de mot de passe.

- Désactivation du 2FA ou suppression d'une passkey.

- Création d'une clé API.

- Élévation ou rétrogradation de rôle d'un utilisateur par un administrateur (protection contre l'escalade de privilèges).

Le paramètre `reauth_max_age_seconds` définit la durée de validité d'une preuve de ré-authentification. Passé ce délai, une nouvelle confirmation est exigée.

### Validation du compteur WebAuthn

À chaque assertion WebAuthn, le système vérifie que le compteur de signatures (`sign_count`) est strictement supérieur à la valeur `use_count` stockée en base. Une valeur inférieure ou égale indique une possible clé clonée et déclenche un rejet immédiat. Les flags UP (User Presence) et UV (User Verification) sont exigés sur chaque assertion.

## Autres mécanismes de sécurité déjà présents

- Rate limiting des tentatives de login.

- Notifications de connexion, avec option pour ne notifier que les nouvelles IPs.

- Obligation possible du 2FA pour les administrateurs.

- **Forced actions** pour imposer une mise à jour de profil ou une configuration 2FA.

- Stockage des secrets sensibles via références `secret://...` plutôt qu'en clair dans les enregistrements applicatifs.

- Audit des actions et rétention configurable des journaux liés à la sécurité.

Le mécanisme des **forced actions** permet à un administrateur d'imposer à un utilisateur une action bloquante: changement de mot de passe, activation du TOTP, ajout d'une passkey, mise à jour du profil. Tant que l'action en attente n'est pas résolue, l'utilisateur voit un écran bloquant qui l'empêche d'accéder au reste de l'application. Les actions en attente sont stockées en base et résolues automatiquement dès que la condition est remplie.

## Journaux secrets et broker HA

La page **Journaux secrets** complète les logs d'activité classiques avec une vue dédiée au SecretStore et au broker de secrets. Elle est réservée aux profils ayant `settings.manage`.

- Consultation des accès aux secrets: action, purpose, référence `secret://...`, succès ou échec.

- État cluster du broker: dernier statut connu, nombre de nœuds sains et lien vers la page Performance pour le diagnostic.

- Événements broker: `degraded`, `node_failed`, `node_recovered`, `failover`, `down`, `recovered`.

- Export CSV des lignes consultées pour audit externe.

> Si le broker n'est pas configuré ou temporairement indisponible, la page peut afficher une erreur et ne présenter que les informations locales disponibles.

## Personnaliser ensuite

Le chapitre suivant explique comment changer l'apparence de l'application, installer des thèmes, utiliser le store, et gérer les demandes de thèmes sur une instance partagée.

[Précédent Hôtes et clés SSH](11-hosts-and-ssh.md) [Suivant Thèmes et personnalisation](15-themes-and-customization.md)

## Parcours

- Précédent: [11. Hôtes et SSH](11-hosts-and-ssh.md)
- Suivant: [13. Référence des paramètres](13-settings-reference.md)
