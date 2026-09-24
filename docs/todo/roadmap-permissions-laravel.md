# Roadmap — Gestion des permissions scopées par projet dans Laravel

## Objectif

Mettre en place un système d’autorisation Laravel basé sur :

- des permissions au format `model:permission` ;
- des wildcards comme `users:*` ;
- des rôles liés à un projet ;
- des rôles système valables sur toute l’instance ;
- un `PermissionChecker` central ;
- des Policies / Gates pour les actions métier ;
- un filtrage SQL explicite pour les listes.

---

## Phase 1 — Stabiliser le modèle de données

- [x] **Valider les relations `User` / `Role` / `Project`**
  - Vérifier comment un utilisateur possède ses rôles.
  - Vérifier qu’un rôle projet possède toujours un `project_id`.
  - Vérifier qu’un rôle système n’est associé à aucun projet.

- [x] **Identifier explicitement les rôles système**
  - Éviter de considérer uniquement `project_id = null` comme preuve qu’un rôle est système.
  - Prévoir un champ explicite comme `is_system` ou `scope`.
  - Ajouter les contraintes applicatives nécessaires pour empêcher un rôle projet de devenir global par erreur.

- [x] **Valider le stockage des permissions**
  - Conserver un tableau JSON de chaînes :
    ```json
    [
        "users:*",
        "projects:list",
        "projects:create",
        "logs:view"
    ]
    ```
  - Ajouter un cast Laravel vers `array`.

---

## Phase 2 — Définir le contrat du moteur de permissions

- [x] **Créer un `PermissionChecker`**
  - Exemple de contrat :
    ```php
    public function allows(
        User $user,
        string $permission,
        ?Project $project = null,
    ): bool;
    ```

- [x] **Définir précisément la règle de scope**
  - Avec un projet :
    - vérifier les rôles système ;
    - vérifier les rôles du projet concerné.
  - Sans projet :
    - vérifier uniquement les rôles système ;
    - ne jamais utiliser un rôle projet comme permission globale.

- [x] **Implémenter le matching des permissions**
  - Vérification exacte : `users:edit`.
  - Vérification wildcard : `users:*`.
  - Refuser les formats invalides si nécessaire.

- [x] **Gérer plusieurs rôles**
  - Une permission est accordée si au moins un rôle applicable la contient.
  - Plusieurs rôles d’un même projet doivent pouvoir se compléter.

---

## Phase 3 — Tester le `PermissionChecker` indépendamment

- [x] **Écrire des tests unitaires du moteur avant d’intégrer Laravel Gate**
  - Tester les rôles système.
  - Tester les rôles projet.
  - Tester les wildcards.
  - Tester l’absence de projet.
  - Tester plusieurs rôles.
  - Tester plusieurs projets.

Exemples importants :

```text
System role: users:list
Project A role: users:edit
Project B role: aucun droit users
```

Résultats attendus :

```text
users:list sans projet       => true
users:list sur Project A     => true
users:list sur Project B     => true

users:edit sans projet       => false
users:edit sur Project A     => true
users:edit sur Project B     => false
```

Autre scénario :

```text
Project A:
- Role 1: users:list
- Role 2: users:edit
```

Résultats attendus :

```text
users:list sur Project A => true
users:edit sur Project A => true
users:create sur Project A => false
```

---

## Phase 4 — Créer les Policies Laravel

- [ ] **Créer une Policy par ressource métier principale**
  - Exemple :
    - `UserPolicy`
    - `ProjectPolicy`
    - `LogPolicy`

- [ ] **Injecter `PermissionChecker` dans les Policies**
  - Les Policies ne doivent pas recalculer les rôles ou les scopes.
  - Elles doivent uniquement traduire une action métier vers une permission.

Exemple :

```php
public function update(
    User $currentUser,
    User $targetUser,
    Project $project,
): bool {
    return $this->permissions->allows(
        user: $currentUser,
        permission: 'users:edit',
        project: $project,
    );
}
```

- [ ] **Conserver les règles métier spécifiques dans les Policies**
  - Exemple : interdire certaines modifications sur soi-même.
  - Exemple : interdire la modification de certains comptes protégés.

---

## Phase 5 — Intégrer l’autorisation dans les Controllers / Routes

- [ ] **Conserver `auth` comme première protection**
  - `auth` vérifie uniquement l’identité de l’utilisateur.

- [ ] **Utiliser `Gate::authorize()` pour les actions avec contexte complexe**
  - Exemple :
    ```php
    Gate::authorize('update', [$targetUser, $project]);
    ```

- [ ] **Utiliser le middleware `can` ou `#[Authorize]` quand le contexte est simple**
  - Principalement lorsque la ressource nécessaire est déjà présente dans la route.

- [ ] **Éviter un middleware custom `permission:*` tant qu’il n’apporte rien de plus**
  - Ne pas dupliquer le fonctionnement de Gate / Policy.

---

## Phase 6 — Gérer les listes globales

- [ ] **Identifier les pages sans projet explicite**
  - Exemples :
    - `/users`
    - `/logs`
    - éventuellement `/projects`

- [ ] **Créer une méthode permettant de récupérer les projets autorisés**
  - Exemple :
    ```php
    authorizedProjectIds(
        User $user,
        string $permission,
    ): Collection;
    ```

- [ ] **Créer un Scope Eloquent ou un Query Object**
  - Exemple :
    ```php
    User::query()
        ->visibleTo($currentUser)
        ->paginate();
    ```

- [ ] **Gérer le cas d’une permission système**
  - Si `users:list` est accordé par un rôle système :
    - ne pas limiter la requête aux projets.
  - Sinon :
    - filtrer uniquement sur les projets autorisés.

- [ ] **Faire le filtrage en SQL**
  - Ne pas charger toutes les données avant de filtrer en PHP.

---

## Phase 7 — Préparer les permissions pour le frontend

- [ ] **Envoyer uniquement les capacités utiles à l’interface**
  - Exemple :
    ```php
    [
        'canCreate' => true,
        'canEdit' => false,
        'canDelete' => false,
    ]
    ```

- [ ] **Ne jamais considérer le frontend comme une protection**
  - Les permissions envoyées à Vue / Inertia servent uniquement à adapter l’UI.
  - Le backend doit refaire toutes les vérifications.

---

## Phase 8 — Ajouter les optimisations

- [ ] **Éviter les N+1**
  - Charger les rôles et projets nécessaires avec `with()` ou des requêtes dédiées.

- [ ] **Ajouter du cache uniquement après validation du comportement**
  - Ne pas commencer par le cache.
  - Prévoir une stratégie d’invalidation lors :
    - d’un changement de rôle ;
    - d’une modification des permissions ;
    - d’une affectation ou suppression de rôle.

- [ ] **Profiler les requêtes des listes globales**
  - Vérifier le coût de `whereHas`, `whereIn` et des pivots.
  - Ajouter les index nécessaires sur les clés de relation.

---

# Phase 9 — Tests d’intégration

- [ ] **Tester les Policies**
  - Autorisation correcte pour un projet autorisé.
  - Refus pour un autre projet.
  - Autorisation globale via rôle système.
  - Refus sans contexte projet pour une permission uniquement projet.

- [ ] **Tester les routes protégées**
  - Utilisateur non connecté → `401` / redirection attendue.
  - Utilisateur connecté sans permission → `403`.
  - Utilisateur autorisé → succès.

- [ ] **Tester les listes globales**
  - Créer 3 projets.
  - Donner `users:list` sur seulement 2 projets.
  - Vérifier que `/users` ne retourne aucun utilisateur appartenant uniquement au troisième projet.

- [ ] **Tester les rôles système**
  - Un rôle système avec `users:list` doit permettre de voir tous les projets.
  - Un rôle système avec `logs:view` ne doit pas accorder `users:list`.

- [ ] **Tester les wildcards**
  - `users:*` doit accepter :
    - `users:list`
    - `users:create`
    - `users:edit`
    - `users:delete`
  - `users:*` ne doit pas accepter :
    - `projects:list`
    - `logs:view`

---

## Exemple de scénario de test plus complet

Préparer :

```text
Utilisateur A

System role:
- logs:view

Project 1:
- Role Reader
  - users:list

Project 2:
- Role Manager
  - users:*
  - projects:edit

Project 3:
- aucun rôle
```

Vérifier :

```text
logs:view sans projet         => true

users:list Project 1          => true
users:edit Project 1          => false

users:list Project 2          => true
users:create Project 2        => true
users:delete Project 2        => true

projects:edit Project 2       => true

users:list Project 3          => false
users:list sans projet        => false
```

Pour `/users` :

```text
Les utilisateurs du Project 1 et du Project 2 doivent être visibles.
Les utilisateurs appartenant uniquement au Project 3 ne doivent pas apparaître.
```

---

# Phase 10 — Tests de sécurité et régression

- [ ] Tester qu’un rôle projet avec `project_id = null` invalide ne devient jamais global.
- [ ] Tester qu’un utilisateur ne peut pas forcer un `project_id` différent dans la requête.
- [ ] Tester les accès directs aux URLs sans passer par l’interface.
- [ ] Tester les appels API si l’application en expose.
- [ ] Tester les Jobs / Commands qui utilisent le `PermissionChecker` sans contexte HTTP.
- [ ] Ajouter les principaux cas de permission aux tests de régression du projet.

---

# Prompt réutilisable pour développer les tests

```text
Je travaille sur un système de permissions Laravel.

Architecture :
- permissions au format `model:permission`
- wildcard `model:*`
- un utilisateur peut avoir plusieurs rôles
- un rôle projet possède un `project_id`
- un rôle projet ne donne des permissions que sur son projet
- certains rôles système ne possèdent pas de `project_id`
- les rôles système donnent leurs permissions sur toute l’instance
- sans projet passé au PermissionChecker, seuls les rôles système doivent être pris en compte
- avec un projet, les permissions système et les permissions de ce projet doivent être prises en compte
- le cœur du système est un `PermissionChecker`
- les actions métier passent ensuite par les Policies Laravel

Je vais te fournir le code concerné.

Je veux que tu m’aides à écrire les tests Laravel correspondants.

Contraintes :
- utilise Pest / PHPUnit selon le style déjà présent dans mon projet ;
- ne suppose pas la structure des factories ou des relations : demande-moi le code manquant si nécessaire ;
- couvre les cas nominaux, les refus, les wildcards, les rôles système, les rôles projet et plusieurs projets ;
- ajoute les cas limites pertinents ;
- évite les tests redondants ;
- privilégie des tests lisibles avec une intention claire ;
- pour les Feature tests, vérifie également les codes HTTP et que les données non autorisées ne sont jamais retournées ;
- explique brièvement ce que chaque groupe de tests protège contre une éventuelle régression.
```

---

## Ordre recommandé

```text
1. Modèle de données
2. PermissionChecker
3. Tests unitaires du PermissionChecker
4. Policies
5. Gate / Controllers / Routes
6. Query Scoping pour les listes
7. Tests Feature / intégration
8. Permissions frontend
9. Optimisations / cache
10. Tests de sécurité et régression
```

L’idée principale est de rendre le `PermissionChecker` fiable avant de construire les couches Laravel autour de lui.
