<?php

namespace App\Services;

use App\Events\ActionLogged;
use App\Models\ActionLog;
use App\Models\AuditIdentityLink;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ActionLogger
{

	public function log(
        ?string           $action,
        string           $severity = 'info',
        ?Authenticatable $user = null,
        ?string          $description = null,
        ?Model      $target = null,
        array            $metadata = [],
        bool             $dispatchEvent = false,
        array            $context = []
    ): ?ActionLog {
        $log_level = config("app.log_level");
        $availableLogLevels = ["no_log", "info", "warning", "failed", "critical"];

        if (!in_array($log_level, $availableLogLevels)) {
            $log_level = "info";
        }

        $keepIndex = array_search($log_level, $availableLogLevels);
        $logLevels = array_filter($availableLogLevels, function ($value, $index) use ($keepIndex){
            $arr = [];
            if ($index >= $keepIndex) {
                $arr[] = $value;
            }

            return $arr;
        }, ARRAY_FILTER_USE_BOTH);

        if (in_array("no_log", $logLevels)) return null;

        if (!in_array($severity, $logLevels)) return null;

        $request = request();
        $user = $user ??= auth()->user();

        [$targetType, $targetId] = $this->resolveTarget($target);

        $action = $this->resolveAction($action);

        $log = ActionLog::create([
            'user_id'=> $user?->id,
            'user_role'=> $this->resolveUserRole($user),
            'severity'=> $severity,
            'action'=> $action,
            'description'=> $description,
            'target_type'=> $targetType,
            'target_id'=> $targetId,
            'ip_address'=> $request?->ip(),
            'user_agent'=> $request?->userAgent(),
            'url'=> $request?->fullUrl(),
            'method'=> $request?->method(),
            'metadata'=> $metadata ?: null,
            'created_at'=> now(),
        ]);

        if ($log && $user && $this->shouldStoreAuditIdentity($action, $severity, $metadata)) {
            AuditIdentityLink::create([
                'action_log_id' => $log->id,
                'user_id_snapshot' => $user->id,
                'actor_identifier' => 'user#' . $user->id,
                'actor_username_snapshot' => $user->username,
                'email_encrypted' => $user->email,
                'first_name_encrypted' => $user->first_name,
                'last_name_encrypted' => $user->last_name,
                'purpose' => 'legal_defense',
                'retention_until' => now()->addYear(),
                'created_by' => auth()->id(),
            ]);
        }


        if ($dispatchEvent) {
            DB::afterCommit(function () use ($log, $context) {
                event(new ActionLogged($log, $context));
            });
        }

        return $log;
    }

    public function info(?string $action, ?string $description = null, array $metadata = [], ?Model $target = null) {
        return $this->log(
            action: $action,
            description: $description,
            target: $target,
            metadata: $metadata
        );
    }

    public function warning(?string $action, ?string $description = null, array $metadata = [], ?Model $target = null) {
        $this->log(
            action: $action,
            severity: 'warning',
            description: $description,
            target: $target,
            metadata: $metadata,
        );
    }

    public function success(?string $action, ?string $description = null, array $metadata = [], ?Model $target = null) {
        $this->log(
            action: $action,
            severity: 'success',
            description: $description,
            target: $target,
            metadata: $metadata,
        );
    }

    public function failed(?string $action, ?string $description = null, array $metadata = [], ?Model $target = null) {
        $this->log(
            action: $action,
            severity: 'failed',
            description: $description,
            target: $target,
            metadata: $metadata,
        );
    }

    public function critical(?string $action, ?string $description = null, array $metadata = [], ?Model $target = null) {
        $this->log(
            action: $action,
            severity: 'critical',
            description: $description,
            target: $target,
            metadata: $metadata,
        );
    }

    protected function resolveTarget(Model|int|null $target =null): array {
        if ($target instanceof Model) {
            return [
                $target::class,
                $target->getKey()
            ];
        }

        $routeTarget = $this->resolveTargetFromRoute();

        if ($routeTarget !== null) {
            return $routeTarget;
        }

        return [null, null];
    }

    protected function resolveTargetFromRoute(): ?array {
        $route = request()?->route();

        if (!$route) return null;

        foreach ($route->parameters() as $parameter) {
            if ($parameter instanceof Model) {
                return [
                    $parameter::class,
                    $parameter->getKey()
                ];
            }
        }

        return null;
    }

    protected function resolveAction(?string $action = null): string {
        if ($action !== null && $action !== '') return $action;

        $route = request()?->route();

        if ($route?->getName()) return $route->getName();

        if ($route?->uri()) return sprintf('%s %s', request()->method(), $route->uri());

        return sprintf('%s %s', request()->method(), request()->path());
    }

    protected function resolveUserRole(?Authenticatable $user): ?string {
        if (!$user instanceof User) {
            return null;
        }

        if ($user->relationLoaded('role') && $user->role) {
            return $user->role->name;
        }

        if ($user->role_id) {
            return $user->role()->value('name');
        }

        return is_string($user->role ?? null) ? $user->role : null;
    }

    protected function shouldStoreAuditIdentity(?string $action, string $severity, array $metadata = []): bool
    {
        return in_array($severity, ['failed', 'critical'], true)
            || in_array($action, [
                'snapshot.delete',
                'snapshot.force_delete',
                'repo.delete',
                'repo.purge',
                'user.forceDelete',
            ], true);
    }
}
