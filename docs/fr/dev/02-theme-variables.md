---
title: "Fulgurite developer docs - Variable themes"
description: "Reference du format variables: manifeste, variables supportees, validation et workflow."
lang: "fr"
section: "developer"
---
# Developper un theme a variables

Le format `variables` est la voie la plus simple pour personnaliser Fulgurite. Il permet de changer les couleurs, la typographie et quelques tokens visuels sans introduire de PHP ni de surcharge de layout.

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
- [02. Thèmes à variables](02-theme-variables.md) (page actuelle)
- [03. Thèmes avancés](03-advanced-themes.md)
- [04. Contexte et template tags](04-context-and-template-tags.md)
- [05. Packaging et debug](05-packaging-and-debugging.md)


## Manifeste minimal

Un theme a variables repose sur un manifeste JSON contenant un identifiant stable, des metadonnees et le bloc `variables`.

```text
{
  "id": "ocean",
  "name": "Ocean",
  "description": "Palette bleu nuit",
  "author": "Jane Doe",
  "version": "1.0",
  "type": "variables",
  "variables": {
    "--bg": "#0a1628",
    "--bg2": "#0d1f3c",
    "--bg3": "#152849",
    "--border": "#1e3a5f",
    "--text": "#cdd9e5",
    "--text2": "#7d9ab5",
    "--accent": "#39c5cf",
    "--accent2": "#1a9aa4",
    "--green": "#26a641",
    "--red": "#f85149",
    "--yellow": "#e3b341",
    "--purple": "#bc8cff"
  }
}
```

## Variables requises et optionnelles

| Variable | Role principal | Statut |
| --- | --- | --- |
| `--bg`, `--bg2`, `--bg3` | Fonds principal, secondaire et tertiaire | Requis |
| `--border` | Bordures et separateurs | Requis |
| `--text`, `--text2` | Texte principal et secondaire | Requis |
| `--accent`, `--accent2` | Liens, focus, etats actifs | Requis |
| `--green`, `--red`, `--yellow`, `--purple` | Etats et accents complementaires | Requis |
| `--font-mono`, `--font-sans` | Polices du shell | Optionnel |
| `--radius`, `--shadow` | Arrondis et ombres | Optionnel |

Les valeurs par defaut du shell UI se trouvent dans `public/assets/app.css`. Un theme a variables remplace uniquement ces tokens, il ne modifie pas la structure HTML.

## Regles imposees par le moteur

- `id` doit faire 1 a 32 caracteres et n utiliser que `a-z`, chiffres, tirets et underscores.

- `name` est obligatoire, `description` et `author` ont une longueur maximale.

- `version` accepte seulement un format court base sur lettres, chiffres, points, underscores et tirets.

- Les valeurs CSS sont filtrees pour rejeter `@import` non autorise, `javascript:`, `expression`, balises HTML et caracteres dangereux.

> La validation est centralisee dans `src/ThemeManager.php`. Quand vous avez un doute sur un token ou un format, c est le bon fichier a relire avant de livrer.

## Workflow recommande

1. Creer un manifeste minimal avec toutes les variables requises.

2. Verifier le contraste de base sur la sidebar, les cartes et les etats de formulaire.

3. Ajouter ensuite les variables optionnelles pour aligner la typographie et la densite.

4. Passer seulement apres cela a un theme `advanced` si le besoin depasse les tokens.

## Passer au niveau suivant

La section suivante explique comment structurer un theme advanced, brancher un `style.css` et surcharger des slots, parts ou pages.

[Precedent Vue d ensemble](01-index.md) [Suivant Themes advanced](03-advanced-themes.md)

## Parcours

- Précédent: [01. Vue d’ensemble développeur](01-index.md)
- Suivant: [03. Thèmes avancés](03-advanced-themes.md)
