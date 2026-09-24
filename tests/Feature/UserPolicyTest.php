<?php

namespace Tests\Feature;

use App\Enums\Role\RoleScopes;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;
    
    private UserPolicy $policy;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->policy = app(UserPolicy::class);
    }
    
    public function test_system_role_can_view_any_user(): void {
        $actor = User::factory()->create();
        
        $this->attachSystemRole($actor, ['users:view'], 10);
        
        $this->assertTrue($this->policy->viewAny($actor));
    }
    
    public function test_project_role_cannot_view_global_list_before_query_scoping(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, ['users:view'], 10);
        
        $this->assertFalse($this->policy->viewAny($actor));
    }
    
    public function test_project_role_can_view_user_from_same_project(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, ['users:view'], 10);
        $this->attachProjectRole($target, $project, [], 20);
        
        $this->assertTrue($this->policy->view($actor, $target, $project));
    }
    
    public function test_project_role_cannot_view_user_from_another_project(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole($actor, $projectA, ['users:view'], 10);
        $this->attachProjectRole($target, $projectB, ['users:view'], 20);
        
        $this->assertFalse($this->policy->view($actor, $target, $projectA));
    }
    
    public function test_view_does_not_require_hierarchy(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, ['users:view'], 20);
        $this->attachProjectRole($target, $project, ['users:view'], 10);
        
        $this->assertTrue($this->policy->view($actor, $target, $project));
    }

    public function test_view_sensitive_requires_its_own_permission(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole($actor, $project, ['users:view'], 10);

        $this->attachProjectRole($target, $project, [], 20);

        $this->assertFalse($this->policy->viewSensitive($actor, $target, $project),
        );
    }

    public function test_system_permission_can_create_user(): void
    {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            $actor,
            ['users:create'],
            10,
        );

        $this->assertTrue(
            $this->policy->create($actor),
        );
    }

    public function test_project_permission_cannot_create_global_user(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            $actor,
            $project,
            ['users:create'],
            10,
        );

        $this->assertFalse(
            $this->policy->create($actor),
        );
    }
    
    #[DataProvider('managedAbilities')]
    public function test_system_actor_can_manage_lower_system_user(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $this->attachSystemRole($actor, [$permission], 10);
        
        $this->attachSystemRole($target, [], 20);
        
        $this->assertTrue($this->policy->{$ability}($actor, $target));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_missing_permissions_is_always_denied(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $this->attachSystemRole($actor, [], 10);
        $this->attachSystemRole($target, [], 20);
        
        $this->assertFalse($this->policy->{$ability}($actor, $target));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_same_system_level_is_denied(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $this->attachSystemRole($actor, [$permission], 10);
        $this->attachSystemRole($target, [$permission], 10);
        
        $this->assertFalse($this->policy->{$ability}($actor, $target));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_lower_system_role_cannot_manage_higher_system_role(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $this->attachSystemRole($actor, [$permission], 20);
        $this->attachSystemRole($target, [], 10);
        
        $this->assertFalse($this->policy->{$ability}($actor, $target));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_project_actor_can_manage_lower_user_in_same_project(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, [$permission], 10);
        $this->attachProjectRole($target, $project, [], 20);
        
        $this->assertTrue($this->policy->{$ability}($actor, $target, $project));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_project_permission_requires_project_context(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, [$permission], 10);
        $this->attachProjectRole($target, $project, [], 20);
        
        $this->assertFalse($this->policy->{$ability}($actor, $target));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_project_actor_cannot_manage_user_from_another_project(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole($actor, $projectA, [$permission], 10);
        $this->attachProjectRole($actor, $projectB, [], 20);
        
        $this->assertFalse($this->policy->{$ability}($actor, $target, $projectA));
    }
    
    #[DataProvider('managedAbilities')]
    public function test_project_actor_cannot_manage_same_level_user(string $ability, string $permission): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, [$permission], 10);
        $this->attachProjectRole($target, $project, [], 10);
        
        $this->assertFalse($this->policy->{$ability}($actor, $target, $project));
    }
    
    public function test_wildcard_can_authorize_update(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, ['users:*'], 10);
        $this->attachProjectrole($target, $project, [], 20);
        
        $this->assertTrue($this->policy->update($actor, $target, $project));
    }
    
    public function test_user_cannot_manage_itself_at_same_level(): void {
        $actor = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, ['users:update'], 10);
        
        $this->assertFalse($this->policy->update($actor, $actor, $project));
    }
    
    public static function managedAbilities(): array {
        return [
            'update'=> ['update', 'users:update'],
            'manage'=> ['manage', 'users:manage'],
            'delete'=> ['delete', 'users:delete'],
            'restore'=> ['restore', 'users:restore'],
            'force delete'=> ['forceDelete', 'users:force-delete'],
        ];
    }
    
    private function attachSystemRole(User $user, array $permissions, int $level): Role {
        return  $this->attachRole(
            user: $user,
            scope:  RoleScopes::System,
            project: null,
            permissions: $permissions,
            level: $level
        );
    }
    
    private function attachProjectRole(User $user, Project $project, array $permissions, int $level): Role {
        return $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            project: $project,
            permissions: $permissions,
            level: $level
        );
    }
    
    private function attachRole(User $user, RoleScopes $scope, ?Project $project, array $permissions, int $level): Role {
        $role = Role::factory()->create([
            'scope'=> $scope,
            'project_id'=> $project?->getKey(),
            'permissions'=> $permissions,
            'level'=> $level
        ]);
        
        $user->roles()->attach($role);
        $user->unsetRelation('roles');

        return $role;
    }
    
    private function createProject(): Project {
        return Project::factory()->create([
            'deleted_at' => null,
        ]);
    }
}
