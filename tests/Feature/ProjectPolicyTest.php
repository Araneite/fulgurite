<?php

namespace Tests\Feature;

use App\Enums\Role\RoleScopes;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ProjectPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = app(ProjectPolicy::class);
    }

    public function test_system_permission_can_view_global_project_list(): void
    {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['projects:view'],
        );

        $this->assertTrue(
            $this->policy->viewAny($actor),
        );
    }

    public function test_project_permission_cannot_view_unfiltered_global_list(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:view'],
        );

        $this->assertFalse(
            $this->policy->viewAny($actor),
        );
    }

    public function test_missing_permission_cannot_view_global_project_list(): void
    {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: [],
        );

        $this->assertFalse(
            $this->policy->viewAny($actor),
        );
    }

    public function test_system_permission_can_view_every_project(): void
    {
        $actor = User::factory()->create();

        $projectA = $this->createProject();
        $projectB = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['projects:view'],
        );

        $this->assertTrue(
            $this->policy->view($actor, $projectA),
        );

        $this->assertTrue(
            $this->policy->view($actor, $projectB),
        );
    }

    public function test_project_permission_can_view_its_project(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:view'],
        );

        $this->assertTrue(
            $this->policy->view($actor, $project),
        );
    }

    public function test_project_permission_cannot_view_another_project(): void
    {
        $actor = User::factory()->create();

        $projectA = $this->createProject();
        $projectB = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['projects:view'],
        );

        $this->assertFalse(
            $this->policy->view($actor, $projectB),
        );
    }

    public function test_project_wildcard_can_view_its_project(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:*'],
        );

        $this->assertTrue(
            $this->policy->view($actor, $project),
        );
    }

    public function test_system_permission_can_create_project(): void
    {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['projects:create'],
        );

        $this->assertTrue(
            $this->policy->create($actor),
        );
    }

    public function test_project_permission_cannot_create_project(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:create'],
        );

        $this->assertFalse(
            $this->policy->create($actor),
        );
    }

    public function test_missing_permission_cannot_create_project(): void
    {
        $actor = User::factory()->create();

        $this->attachSystemRole(
            user: $actor,
            permissions: [],
        );

        $this->assertFalse(
            $this->policy->create($actor),
        );
    }

    #[DataProvider('scopedAbilities')]
    public function test_system_permission_allows_scoped_project_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: [$permission],
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('scopedAbilities')]
    public function test_project_permission_allows_action_on_its_project(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [$permission],
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('scopedAbilities')]
    public function test_project_permission_denies_action_on_another_project(
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
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $projectB,
            ),
        );
    }

    #[DataProvider('scopedAbilities')]
    public function test_missing_permission_denies_scoped_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [],
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('scopedAbilities')]
    public function test_project_wildcard_allows_scoped_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:*'],
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('globalAbilities')]
    public function test_system_permission_allows_global_project_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: [$permission],
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('globalAbilities')]
    public function test_project_permission_cannot_perform_global_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: [$permission],
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('globalAbilities')]
    public function test_project_wildcard_cannot_perform_global_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:*'],
        );

        $this->assertFalse(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    #[DataProvider('globalAbilities')]
    public function test_system_wildcard_allows_global_action(
        string $ability,
        string $permission,
    ): void {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['projects:*'],
        );

        $this->assertTrue(
            $this->policy->{$ability}(
                $actor,
                $project,
            ),
        );
    }

    public function test_permissions_from_multiple_project_roles_are_combined(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:view'],
        );

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:update'],
        );

        $this->assertTrue(
            $this->policy->view($actor, $project),
        );

        $this->assertTrue(
            $this->policy->update($actor, $project),
        );

        $this->assertFalse(
            $this->policy->manage($actor, $project),
        );
    }

    public function test_system_and_project_permissions_are_combined(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachSystemRole(
            user: $actor,
            permissions: ['projects:view'],
        );

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:update'],
        );

        $this->assertTrue(
            $this->policy->view($actor, $project),
        );

        $this->assertTrue(
            $this->policy->update($actor, $project),
        );
    }

    public function test_gate_discovers_project_policy(): void
    {
        $actor = User::factory()->create();
        $project = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $project,
            permissions: ['projects:update'],
        );

        $this->assertTrue(
            Gate::forUser($actor)->allows(
                'update',
                $project,
            ),
        );
    }

    public function test_gate_denies_project_from_another_scope(): void
    {
        $actor = User::factory()->create();

        $projectA = $this->createProject();
        $projectB = $this->createProject();

        $this->attachProjectRole(
            user: $actor,
            project: $projectA,
            permissions: ['projects:update'],
        );

        $this->assertFalse(
            Gate::forUser($actor)->allows(
                'update',
                $projectB,
            ),
        );
    }

    public static function scopedAbilities(): array
    {
        return [
            'update' => [
                'update',
                'projects:update',
            ],
            'manage' => [
                'manage',
                'projects:manage',
            ],
        ];
    }

    public static function globalAbilities(): array
    {
        return [
            'delete' => [
                'delete',
                'projects:delete',
            ],
            'restore' => [
                'restore',
                'projects:restore',
            ],
            'force delete' => [
                'forceDelete',
                'projects:force-delete',
            ],
        ];
    }
    
    private function attachSystemRole(
        User $user,
        array $permissions,
        int $level = 10,
    ): Role {
        $role = Role::factory()->create([
            'scope' => RoleScopes::System,
            'project_id' => null,
            'permissions' => $permissions,
            'level' => $level,
        ]);

        $user->roles()->attach($role);
        $user->unsetRelation('roles');

        return $role;
    }

    private function attachProjectRole(
        User $user,
        Project $project,
        array $permissions,
        int $level = 10,
    ): Role {
        $role = Role::factory()->create([
            'scope' => RoleScopes::Project,
            'project_id' => $project->getKey(),
            'permissions' => $permissions,
            'level' => $level,
        ]);

        $user->roles()->attach($role);
        $user->unsetRelation('roles');

        return $role;
    }

    private function createProject(): Project
    {
        return Project::factory()->create([
            'deleted_at' => null,
        ]);
    }
}
