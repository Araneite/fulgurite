<?php

namespace Tests\Feature;

use App\Enums\Role\RoleScopes;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    use RefreshDatabase;
    
    private RolePolicy $policy;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->policy = app(RolePolicy::class);
    }
    
    public function test_system_role_can_view_global_role_list(): void {
        $actor = User::factory()->create();
        
        $this->attachSystemRole(
            user: $actor,
            permissions: ['roles:view'],
            level: 10
        );
        
        $this->assertTrue($this->policy->viewAny($actor));
    }
    
    public function test_project_role_cannot_view_global_role_list(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:view'],
            level: 10
        );
        
        $this->assertFalse($this->policy->viewAny($actor));
    }
    
    public function test_project_role_can_view_role_list_for_its_project(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:view'],
            level: 10
        );
        
        $this->assertTrue($this->policy->viewAny($actor, $project));
    }
    
    public function test_project_role_cannot_view_role_list_for_another_project(): void {
        $actor = User::factory()->create();
        
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['roles:view'],
            level: 10
        );
        
        $this->assertFalse($this->policy->viewAny($actor, $projectB));
    }
    
    public function test_actor_can_view_role_in_authorized_project(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:view'],
            level: 20
        );
        
        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10
        );
        
        $this->assertTrue($this->policy->view($actor, $targetRole));
    }
    
    public function test_actor_cannot_view_role_from_unauthorized_project(): void {
        $actor = User::factory()->create();
        
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['roles:view'],
            level: 10
        );
        
        $targetRole = $this->createProjectRole(
            project: $projectB,
            level: 20
        );
        
        $this->assertFalse($this->policy->view($actor, $targetRole));
    }
    
    public function test_system_view_permission_can_view_system_role(): void {
        $actor = User::factory()->create();
        
        $this->attachSystemRole(
            user: $actor,
            permissions: ['roles:view'],
            level: 20
        );
        
        $targetRole = $this->createSystemRole(level: 10);
        
        $this->assertTrue($this->policy->view($actor, $targetRole));
    }
    
    public function test_project_permission_cannot_view_system_role(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:view'],
            level: 10
        );
        
        $targetRole = $this->createSystemRole(level: 20);
        
        $this->assertFalse($this->policy->view($actor, $targetRole));
    }
    
    public function test_project_permission_can_create_role_in_its_project(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:create'],
            level: 10
        );
        
        $this->assertTrue($this->policy->create($actor, $project));
    }
    
    public function test_project_permission_cannot_create_role_in_another_project(): void {
        $actor = User::factory()->create();
        
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['roles:create'],
            level: 10
        );
        
        $this->assertFalse($this->policy->create($actor, $projectB));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_system_actor_can_manage_lower_system_role(string $ability, string $permission): void {
        $actor = User::factory()->create();
        
        $this->attachSystemRole(
            user: $actor,
            permissions: [$permission],
            level: 10
        );
        
        $targetRole = $this->createSystemRole(level: 20);
        
        $this->assertTrue($this->policy->{$ability}($actor, $targetRole));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_system_actor_cannot_manage_same_level_system_role(string $ability, string $permission): void {
        $actor = User::factory()->create();
        
        $this->attachSystemRole(
            user: $actor,
            permissions: [$permission],
            level: 10
        );
        
        $targetRole = $this->createSystemRole(level: 10);
        
        $this->assertFalse($this->policy->{$ability}($actor, $targetRole));
    }

    #[DataProvider('managedAbilities')]
    public function test_lower_system_actor_cannot_manage_higher_system_role(string $ability, string $permission): void {
        $actor = User::factory()->create();
        
        $this->attachSystemRole(
            user: $actor,
            permissions: [$permission],
            level: 20
        );
        
        $targetRole = $this->createSystemRole(level: 10);
        
        $this->assertFalse($this->policy->{$ability}($actor, $targetRole));
    }

    #[DataProvider('managedAbilities')]
    public function test_project_actor_can_manage_lower_role_in_same_project(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [$permission],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    #[DataProvider('managedAbilities')]
    public function test_project_actor_cannot_manage_same_level_role(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [$permission],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10,
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    #[DataProvider('managedAbilities')]
    public function test_project_actor_cannot_manage_higher_role(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [$permission],
            level: 20,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10,
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    #[DataProvider('managedAbilities')]
    public function test_project_actor_cannot_manage_role_from_another_project(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();

        $projectA = $this->createProject();
        $projectB = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: [$permission],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $projectB,
            level: 20,
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    #[DataProvider('managedAbilities')]
    public function test_missing_permission_is_denied(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    #[DataProvider('managedAbilities')]
    public function test_role_wildcard_authorizes_managed_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:*'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    public function test_system_actor_can_manage_lower_project_role(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['roles:update'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->update($actor, $targetRole),
        );
    }

    #[DataProvider('lockedRoleAbilities')]
    public function test_locked_role_cannot_be_mutated(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: [$permission],
            level: 1,
        );

        $targetRole = $this->createSystemRole(
            level: 20,
            locked: true,
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $targetRole,
            ),
        );
    }

    public function test_locked_role_can_still_be_assigned(): void
    {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['roles:grant'],
            level: 1,
        );

        $targetRole = $this->createSystemRole(
            level: 20,
            locked: true,
        );

        $this->assertTrue(
            $this->policy->assign($actor, $targetRole),
        );
    }

    public function test_assign_allows_lower_role(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->assign(
                $actor,
                $targetRole,
            ),
        );
    }

    public function test_assign_denies_same_level_role(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10,
        );

        $this->assertFalse(
            $this->policy->assign(
                $actor,
                $targetRole,
            ),
        );
    }

    public function test_assign_denies_higher_role(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 20,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10,
        );

        $this->assertFalse(
            $this->policy->assign(
                $actor,
                $targetRole,
            ),
        );
    }
    
    public function test_project_actor_can_grant_lower_role_to_lower_user(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $this->attachProjectRole(
            user: $subject,
            project: $project,
            permissions: [],
            level: 20,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 30,
        );

        $this->assertTrue(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_project_actor_can_grant_first_project_role(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_system_actor_can_grant_first_system_role(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createSystemRole(level: 20);

        $this->assertTrue(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_actor_cannot_grant_role_to_higher_user(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 20,
        );

        $this->attachProjectRole(
            user: $subject,
            project: $project,
            permissions: [],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 30,
        );

        $this->assertFalse(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_actor_cannot_grant_higher_role_to_lower_user(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 20,
        );

        $this->attachProjectRole(
            user: $subject,
            project: $project,
            permissions: [],
            level: 30,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10,
        );

        $this->assertFalse(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_actor_cannot_grant_role_to_itself(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertFalse(
            $this->policy->grant(
                $actor,
                $targetRole,
                $actor,
            ),
        );
    }

    public function test_actor_cannot_grant_role_already_owned_by_subject(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->attachRole($subject, $targetRole);

        $this->assertFalse(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_project_actor_cannot_grant_role_from_another_project(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();

        $projectA = $this->createProject();
        $projectB = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $projectB,
            level: 20,
        );

        $this->assertFalse(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_project_actor_cannot_grant_system_role(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createSystemRole(level: 20);

        $this->assertFalse(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_roles_wildcard_can_grant_role(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:*'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->grant(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_project_actor_can_revoke_lower_role_from_lower_user(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:revoke'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->attachRole($subject, $targetRole);

        $this->assertTrue(
            $this->policy->revoke(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_actor_cannot_revoke_role_from_higher_user(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:revoke'],
            level: 20,
        );

        $this->attachProjectRole(
            user: $subject,
            project: $project,
            permissions: [],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 30,
        );

        $this->attachRole($subject, $targetRole);

        $this->assertFalse(
            $this->policy->revoke(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_actor_cannot_revoke_higher_role(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:revoke'],
            level: 20,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 10,
        );

        $this->attachRole($subject, $targetRole);

        $this->assertFalse(
            $this->policy->revoke(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_actor_cannot_revoke_role_from_itself(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $actorRole = $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:revoke'],
            level: 10,
        );

        $this->assertFalse(
            $this->policy->revoke(
                $actor,
                $actorRole,
                $actor,
            ),
        );
    }

    public function test_actor_cannot_revoke_role_not_owned_by_subject(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:revoke'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertFalse(
            $this->policy->revoke(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_project_actor_cannot_revoke_role_from_another_project(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();

        $projectA = $this->createProject();
        $projectB = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['roles:revoke'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $projectB,
            level: 20,
        );

        $this->attachRole($subject, $targetRole);

        $this->assertFalse(
            $this->policy->revoke(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_roles_wildcard_can_revoke_role(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:*'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->attachRole($subject, $targetRole);

        $this->assertTrue(
            $this->policy->revoke(
                $actor,
                $targetRole,
                $subject,
            ),
        );
    }

    public function test_best_actor_level_is_used(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:update'],
            level: 30,
        );

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:update'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->update($actor, $targetRole),
        );
    }

    public function test_system_level_has_priority_for_project_role(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['roles:update'],
            level: 5,
        );

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [],
            level: 30,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            $this->policy->update($actor, $targetRole),
        );
    }

    public function test_gate_passes_subject_to_grant_policy(): void
    {
        $actor = User::factory()->create();
        $subject = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:grant'],
            level: 10,
        );

        $targetRole = $this->createProjectRole(
            project: $project,
            level: 20,
        );

        $this->assertTrue(
            Gate::forUser($actor)->allows(
                'grant',
                [$targetRole, $subject],
            ),
        );
    }

    public function test_gate_passes_project_to_view_any_policy(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['roles:view'],
            level: 10,
        );

        $this->assertTrue(
            Gate::forUser($actor)->allows(
                'viewAny',
                [Role::class, $project],
            ),
        );
    }

    public static function managedAbilities(): array
    {
        return [
            'update' => [
                'update',
                'roles:update',
            ],
            'manage' => [
                'manage',
                'roles:manage',
            ],
            'delete' => [
                'delete',
                'roles:delete',
            ],
            'restore' => [
                'restore',
                'roles:restore',
            ],
            'force delete' => [
                'forceDelete',
                'roles:force-delete',
            ],
        ];
    }

    public static function lockedRoleAbilities(): array
    {
        return [
            'update' => [
                'update',
                'roles:update',
            ],
            'manage' => [
                'manage',
                'roles:manage',
            ],
            'delete' => [
                'delete',
                'roles:delete',
            ],
            'force delete' => [
                'forceDelete',
                'roles:force-delete',
            ],
        ];
    }

    private function attachSystemRole(
        User $user,
        array $permissions,
        int $level,
    ): Role {
        $role = $this->createSystemRole(
            level: $level,
            permissions: $permissions,
        );

        $this->attachRole($user, $role);

        return $role;
    }

    private function attachProjectRole(
        User $user,
        Project $project,
        array $permissions,
        int $level,
    ): Role {
        $role = $this->createProjectRole(
            project: $project,
            level: $level,
            permissions: $permissions,
        );

        $this->attachRole($user, $role);

        return $role;
    }

    private function attachRole(
        User $user,
        Role $role,
    ): void {
        $user->roles()->attach($role);
        $user->unsetRelation('roles');
    }

    private function createSystemRole(
        int $level,
        array $permissions = [],
        bool $locked = false,
    ): Role {
        return Role::factory()->create([
            'scope' => RoleScopes::System,
            'project_id' => null,
            'permissions' => $permissions,
            'level' => $level,
            'locked' => $locked,
        ]);
    }

    private function createProjectRole(
        Project $project,
        int $level,
        array $permissions = [],
        bool $locked = false,
    ): Role {
        return Role::factory()->create([
            'scope' => RoleScopes::Project,
            'project_id' => $project->getKey(),
            'permissions' => $permissions,
            'level' => $level,
            'locked' => $locked,
        ]);
    }

    private function createProject(): Project
    {
        return Project::factory()->create([
            'deleted_at' => null,
        ]);
    }
}
