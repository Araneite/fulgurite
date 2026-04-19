---
title: "Documentation Fulgurite — Notifications"
description: "Canaux de notification, centre interne, push navigateur, profils d'événements et politiques de diffusion dans Fulgurite."
lang: "fr"
section: "user"
---
# Configurer les canaux et politiques de notification

Le système de notification de Fulgurite va au-delà d'un simple envoi de mail. Il sait envoyer des messages sur plusieurs canaux externes, stocker des notifications internes par utilisateur, produire des push navigateur et appliquer des politiques par type d'événement.

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
- [10. Notifications](10-notifications.md) (page actuelle)
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


## Canaux de notification déjà pris en charge

Les intégrations actuellement présentes couvrent la plupart des usages courants d'exploitation. La liste complète des canaux gérés est la suivante:

- **Email / SMTP**— envoi classique via un serveur SMTP configuré dans les Paramètres.

- **Discord**— webhook Discord entrant.

- **Slack**— webhook Slack entrant.

- **Telegram**— bot Telegram avec token et chat ID.

- **ntfy**— publication sur un topic ntfy (auto-hébergé ou public).

- **Webhook générique**— POST JSON vers une URL libre (pour intégrer un SIEM, une plateforme on-call, un outil maison).

- **Microsoft Teams**— webhook entrant Teams.

- **Gotify**— serveur Gotify auto-hébergé.

- **In-app (cloche)**— centre de notifications interne, visible via l'icône cloche de l'interface.

- **Web Push navigateur**— notifications natives du navigateur pour les sessions ouvertes.

## Profils d'événements et politiques de diffusion

Les notifications ne sont pas seulement « activées » ou « désactivées ». Elles sont structurées par profil et par événement, puis résolues selon les règles définies globalement ou localement.

- Chaque dépôt, backup job, copy job ou tâche globale peut posséder sa propre politique.

- Les politiques peuvent hériter de la configuration globale puis ajouter ou retirer certains canaux.

- Les profils distincts permettent de différencier les besoins d'alerte pour un job, un dépôt, une connexion ou un rapport hebdomadaire.

La résolution effective d'une politique suit un **ordre de priorité** strict, du plus spécifique au plus général:

1. **Politique du job**(backup job, copy job, tâche globale): si une politique est définie au niveau du job, elle prime.

2. **Politique du dépôt parent**: si le job n'a pas de politique propre, Fulgurite remonte sur le dépôt concerné.

3. **Politique globale**: en l'absence de politique plus locale, la configuration globale de l'instance s'applique.

À chacun de ces trois niveaux, un canal peut être **explicitement exclu** pour désactiver localement une diffusion qui serait présente plus haut dans la chaîne. C'est ainsi que l'on peut, par exemple, activer globalement Slack pour toutes les alertes mais le retirer sur un dépôt spécifique trop bruyant, ou au contraire le remettre uniquement sur un job critique.

## Notifications internes et push navigateur

Les notifications ne partent pas seulement vers l'extérieur. Elles peuvent aussi vivre dans l'application et être ciblées vers les utilisateurs éligibles.

- Stockage en base de notifications liées à des utilisateurs.

- Filtrage possible sur les non lues.

- Marquer une notification comme lue ou tout marquer d'un coup.

- Supprimer une notification ou supprimer toutes les notifications déjà lues.

- Option de livraison navigateur pour les sessions ouvertes via le canal push web.

Les notifications internes sont purgées automatiquement selon le paramètre `app_notifications_retention_days`, qui définit la durée de vie maximale d'une notification stockée en base. La valeur par défaut est consultable dans les Paramètres: au-delà de cette fenêtre, les entrées sont effacées par la tâche de nettoyage de rétention, qu'elles aient été lues ou non.

## Événements déjà couverts

Le code gère déjà plusieurs familles d'événements, ce qui permet une diffusion assez fine. Les profils d'événements sont explicitement:

| Profil | Couvre |
| --- | --- |
| `repo` | Événements dépôt: dépôt stale, erreur d'accès, absence de snapshot, dépôt injoignable. |
| `backup_job` | Succès et échec d'un backup job, y compris les runs retentés. |
| `copy_job` | Succès et échec d'un copy job. |
| `weekly_report` | Diffusion du rapport hebdomadaire. |
| `integrity_check` | Succès et échec des vérifications d'intégrité planifiées. |
| `maintenance_vacuum` | Succès et échec de la maintenance vacuum des bases internes. |
| `login` | Événements de connexion utilisateur (succès, nouvel appareil, etc.). |
| `security` | Alertes de sécurité: échecs répétés, changement de mot de passe, activation TOTP / WebAuthn, IP suspecte. |
| `theme_request` | Demandes de thèmes soumises par les utilisateurs. |
| `disk_space` | Surveillance de l'espace disque: avertissement, état critique, retour à la normale. |
| `secret_broker` | Événements du broker HA: nœud dégradé, nœud défaillant, basculement vers un endpoint de secours, rétablissement complet du cluster, cluster hors service (tous les endpoints défaillants). Actif uniquement avec `HaBrokerSecretProvider` multi-endpoints. |

## Limitation des notifications répétées

Pour éviter le spam de notifications lors d'incidents persistants, le système applique une fenêtre de déduplication de 24 heures par combinaison profil + événement. Si une alerte du même type a déjà été envoyée dans les dernières 24 heures, elle est silencieusement ignorée.

> La déduplication est calculée par profil et par événement, pas globalement. Une alerte `disk_space.warning` et une alerte `repo.error` sont traitées de manière indépendante.

Les événements suivants sont **exempts de déduplication** et sont toujours envoyés, quel que soit le délai depuis le dernier envoi:

- `security`— alertes de sécurité (priorité maximale, jamais supprimées).

- `login`— événements de connexion.

- `weekly_report`— rapport hebdomadaire (émis une fois par semaine par conception).

- `backup_job.success` et `copy_job.success`— confirmations de succès de chaque run.

Exemple concret: si l'espace disque reste critique pendant 3 jours consécutifs, une seule notification `disk_space.critical` sera émise par période de 24 heures, et non une alerte à chaque passage de la tâche de surveillance.

## Tester les canaux et diffuser proprement

L'application expose déjà des points de test utiles avant d'attendre le premier incident réel.

- Test de notification depuis un dépôt ou certains écrans métier.

- Validation du SMTP et des webhooks génériques via les Paramètres.

- Usage des notifications internes pour vérifier la logique de ciblage sans sortir du navigateur.

> Le **Webhook générique** des notifications ne doit pas être confondu avec les **webhooks API signés**. Le premier sert à diffuser des alertes. Le second sert à émettre des événements applicatifs signés HMAC.

## Poursuivre vers l'administration distante

La prochaine section couvre les hôtes et les clés SSH, indispensables pour les sauvegardes distantes, les tests de connectivité et certaines restaurations hors du serveur Fulgurite.

[Précédent Suivi et performance](09-monitoring-and-performance.md) [Suivant Hôtes et clés SSH](11-hosts-and-ssh.md)

## Parcours

- Précédent: [09. Monitoring et performance](09-monitoring-and-performance.md)
- Suivant: [11. Hôtes et SSH](11-hosts-and-ssh.md)
