<?php

namespace Tests\Feature;

use App\Enums\Role\RoleScopes;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\RoleHierarchyChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleHierarchyCheckerTest extends TestCase
{
    use RefreshDatabase;
    
    private RoleHierarchyChecker $checker;
    
    protected function setUp(): void {
        parent::setUp();
        
        $this->checker = app(RoleHierarchyChecker::class);
    }
    
    public function test_higher_user_can_manage_lower_user(): void {
        [$actor, $target, $project] = $this->projectUsers(
            actorLevel: 10,
            targetLevel: 20,
        );
        
        $this->assertTrue($this->checker->canManageUser($actor, $target, $project));
    }
    
    public function test_lower_user_cannot_manage_higher_user(): void {
        [$actor, $target, $project] = $this->projectUsers(
            actorLevel: 20,
            targetLevel: 10,
        );
        
        $this->assertFalse($this->checker->canManageUser($actor, $target, $project));
    }
    
    public function test_same_level_user_cannot_be_managed(): void {
        [$actor, $target, $project] = $this->projectUsers(
            actorLevel: 10,
            targetLevel: 10,
        );
        
        $this->assertFalse(
            $this->checker->canManageUser($actor, $target, $project)
        );
    }
    
    public function test_actor_without_role_cannot_manage_user(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachProjectRole($target, $project, 20);
        
        $this->assertFalse($this->checker->canManageUser($actor, $target, $project));
    }
    
    public function test_target_without_role_in_project_cannot_be_managed(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = Project::factory()->create();
        
        $this->attachProjectRole($actor, $project, 10);
        
        $this->assertFalse($this->checker->canManageUser($actor, $target, $project));
    }
    
    public function test_role_from_another_project_is_ignored(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole($actor, $projectA, 10);
        $this->attachProjectRole($target, $projectB, 20);
        
        $this->assertFalse($this->checker->canManageUser($actor, $target, $projectA));
    }
    
    public function test_best_level_is_used_when_user_has_multiple_roles(): void {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, 40);
        $this->attachProjectRole($actor, $project, 10);
        $this->attachProjectRole($target, $project, 20);
        
        $this->assertSame(
            10,
            $this->checker->projectLevel($actor, $project)
        );
        $this->assertTrue($this->checker->canManageUser($actor, $target, $project));
    }
    
    public function test_system_level_has_priority_over_project_level(): void {
        $user = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachSystemRole($user, 5);
        $this->attachProjectRole($user, $project, 20);
        
        $this->assertSame(
            5,
            $this->checker->effectiveLevel($user, $project)
        );
    }
    
    public function test_best_system_level_is_used(): void {
        $user = User::factory()->create();
        
        $this->attachSystemRole($user, 30);
        $this->attachSystemRole($user, 10);
        
        $this->assertSame(
            10,
            $this->checker->systemLevel($user)
        );
    }
    
    public function test_project_level_does_not_use_another_project(): void {
        $user = User::factory()->create();
        $projectA = $this->createProject();
        $projectB = $this->createProject();
        
        $this->attachProjectRole($user, $projectA, 10);
        
        $this->assertNull($this->checker->projectLevel($user, $projectB));
    }
    
    private function projectUsers(int $actorLevel, int $targetLevel): array {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        $project = $this->createProject();
        
        $this->attachProjectRole($actor, $project, $actorLevel);
        $this->attachProjectRole($target, $project, $targetLevel);
        
        return [$actor, $target, $project];
    }
    
    private function attachSystemRole(User $user, int $level): Role {
        return  $this->attachRole(
            user: $user,
            scope: RoleScopes::System,
            project: null,
            level:  $level
        );
    }
    
    private function attachProjectRole(User $user, Project $project, int $level): Role {
        return $this->attachRole(
            user: $user,
            scope: RoleScopes::Project,
            project: $project,
            level:  $level
        );
    }
    
    private function attachRole(User $user, RoleScopes $scope, ?Project $project, int $level): Role {
        $role = Role::factory()->create([
            'scope'=> $scope,
            'project_id'=> $project?->getKey(),
            'permissions' => [],
            'level' => $level,
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
