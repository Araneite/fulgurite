<?php

namespace Tests\Feature;

use App\Enums\Role\RoleScopes;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\PermissionChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionCheckerTest extends TestCase
{
    use RefreshDatabase;
    
    private PermissionChecker $checker;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->checker = app(PermissionChecker::class);
    }
    
    public function test_system_permission_is_allowed_without_project(): void {
        $user = User::factory()->create();
        
        $this->attachRole(
            user: $user, 
            scope: RoleScopes::System,
            permissions: ['users:view']
        );
        
        $this->assertTrue($this->checker->allows($user, 'users:view'));
    }
    
    public function test_system_permission_is_available_in_every_project(): void {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::System,
            permissions: ['users:view'],
        );
        
        $this->assertTrue($this->checker->allows($user, 'users:view', $projectA));
        $this->assertTrue($this->checker->allows($user, 'users:view', $projectB));
    }
    
    public function test_project_permission_is_allowed_on_its_project(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:update'],
            project: $project
        );
        
        $this->assertTrue($this->checker->allows($user, 'users:update', $project));
    }
    
    public function test_project_permission_is_denied_without_project_context(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:update'],
            project: $project
        );
        
        $this->assertFalse($this->checker->allows($user, 'users:update'));
    }
    
    public function test_project_permission_is_denied_on_another_project(): void {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:update'],
            project: $projectA
        );
        
        $this->assertFalse($this->checker->allows($user, 'users:update', $projectB));
    }
    
    public function test_wildcard_allow_every_action_of_the_resource(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:*'],
            project: $project
        );
        
        $this->assertTrue($this->checker->allows($user, 'users:view', $project));
        $this->assertTrue($this->checker->allows($user, 'users:update', $project));
        $this->assertTrue($this->checker->allows($user, 'users:delete', $project));
    }
    
    public function test_wildcard_does_not_allow_another_resource(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:*'],
            project: $project
        );
        
        $this->assertFalse($this->checker->allows($user, 'projects:view', $project));
    }
    
    public function test_permissions_from_multiple_roles_are_combined(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:view'],
            project: $project
        );
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:update'],
            project: $project
        );
        
        $this->assertTrue($this->checker->allows($user, 'users:view', $project));
        $this->assertTrue($this->checker->allows($user, 'users:update', $project));
        $this->assertFalse($this->checker->allows($user, 'users:delete', $project));
    }
    
    public function test_system_and_project_permissions_are_combined(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::System,
            permissions: ['users:view'],
        );
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:update'],
            project: $project
        );
        
        $this->assertTrue($this->checker->allows($user, 'users:view', $project));
        $this->assertTrue($this->checker->allows($user, 'users:update', $project));
        $this->assertFalse($this->checker->allows($user, 'users:delete', $project));
    }
    
    public function test_permissions_do_not_leak_between_projects(): void {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:view'],
            project: $projectA
        );
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:update'],
            project: $projectB
        );
        
        $this->assertFalse($this->checker->allows($user, 'users:update', $projectA));
        $this->assertFalse($this->checker->allows($user, 'users:view', $projectB));
    }
    
    public function test_role_without_permissions_grants_nothing(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: [],
            project: $project
        );
        
        $this->assertFalse($this->checker->allows($user, 'users:view', $project));
    }
    
    public function test_user_without_roles_has_no_permissions(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->assertFalse($this->checker->allows($user, 'users:view', $project));
    }
    
    public function test_authorized_project_ids_contains_only_allowed_projects(): void {
        $user = User::factory()->create();
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $projectC = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:view'],
            project: $projectA
        );
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:*'],
            project: $projectB
        );
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['projects:view'],
            project: $projectC
        );
        
        $projectIds = $this->checker->authorizedProjectIds($user, 'users:view');
        
        sort($projectIds);
        
        $excepted = [
            $projectA->getKey(),
            $projectB->getKey(),
        ];
        
        sort($excepted);
        
        $this->assertSame($excepted, $projectIds);
    }
    
    public function test_authorized_project_ids_are_unique(): void {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:view'],
            project: $project
        );
        $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            permissions: ['users:*'],
            project: $project
        );
        
        $this->assertSame(
            [$project->getKey()],
            $this->checker->authorizedProjectIds($user, 'users:view')
        );
    }
    
    #[DataProvider('invalidPermissions')]
    public function test_invalid_permission_format_is_rejected(string $permission): void {
        $user = User::factory()->create();
        
        $this->expectException(InvalidArgumentException::class);
        
        $this->checker->allows($user, $permission);
    }
    
    public static function invalidPermissions(): array {
        return [
            'empty'=> [''],
            'resource only'=> ['users'],
            'action only'=> [':view'],
            'missing action'=> ['users:'],
            'multiple separator'=> ['users:view:all'],
            'space'=> ['users: view']
        ];
    }
    
    private function attachRole(
        User $user,
        RoleScopes $scope,
        array $permissions,
        ?project $project = null,
        int $level = 100,
    ): Role {
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
}
