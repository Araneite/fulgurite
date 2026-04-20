# Roadmap

Cette roadmap décrit les principales directions produit prévues pour Fulgurite. Elle ne constitue pas une promesse de release ni un calendrier fixe.

## Restauration Depuis Une Copie De Sauvegarde

Permettre de lancer une restauration depuis une copie de sauvegarde, et pas seulement depuis le dépôt principal. Cela doit rendre la reprise plus souple lorsque le dépôt principal est indisponible, dégradé ou volontairement séparé de la cible de restauration.

## Gestion Multi-Workspace

Introduire une séparation par workspace pour qu’une même instance Fulgurite puisse gérer plusieurs contextes opérationnels. Les workspaces doivent aider à isoler dépôts, jobs, utilisateurs, scopes et paramètres pour différentes équipes, clients ou environnements.

## Calendrier Des Exécutions Passées Et Futures

Ajouter une vue calendrier pour l’activité planifiée. Elle doit montrer les exécutions passées, les prochains runs prévus et des indicateurs utiles pour les sauvegardes, copies, tâches globales et opérations de maintenance.

## Score De Santé Des Sauvegardes

Ajouter un indicateur global visible dès le dashboard. Il doit combiner le taux de réussite, l'ancienneté des snapshots, l'intégrité vérifiée et la présence d'une politique de rétention configurée dans un score simple, lisible par un décideur non technique.

## Tests De Restauration Automatisés

Permettre de planifier des vérifications périodiques de restauration. Fulgurite doit restaurer un sous-ensemble de fichiers, vérifier les checksums et indiquer si les données sont réellement récupérables, pas seulement si les sauvegardes s'exécutent.

## Gestion Avancée Des Logs

Améliorer le stockage, la recherche, le filtrage et la consultation des logs. L’objectif est de faciliter le diagnostic sans obliger les utilisateurs à lire de la sortie brute partout.

## Rotation Automatisée Des Secrets

Ajouter des workflows contrôlés pour renouveler les secrets sensibles : mots de passe de dépôts, tokens API, éléments SSH ou identifiants d’intégrations externes. La rotation doit être traçable et réduire le risque opérationnel manuel.

## Documentation Associée

- [README.md](README.md)
- [Installation et prise en main](../../docs/fr/02-getting-started.md)
- [Référence des paramètres](../../docs/fr/13-settings-reference.md)
- [Politique de sécurité](SECURITY.md)
