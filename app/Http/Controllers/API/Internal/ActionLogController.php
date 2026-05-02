<?php

namespace App\Http\Controllers\API\Internal;

use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\LogArchiveRequest;
use App\Http\Resources\API\Internal\ActionLogCollection;
use App\Http\Resources\API\Internal\ActionLogResource;
use App\Http\Resources\API\Internal\BaseResource;
use App\Jobs\ArchiveActionLogsJob;
use App\Models\ActionLog;
use App\Traits\AuthorizesApiRequests;
use App\Traits\HandlesIncludes;
use App\Traits\HasPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ActionLogController extends Controller
{
    use HandlesIncludes, HasPermissions, AuthorizesApiRequests;
    
    //** Display a listing of logs */
    
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function index(Request $request) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "view", "internal.actions.logs.list", ActionLog::class);
        
        $query = ActionLog::query();
        
        $this->applyIncludes($request, $query, ["user", "target"]);
        
        $logs = $query->get();
        
        if ($request->query('page') || $request->query('per_page')) {
            $logs = $query->apiPaginate($request);
        }
        
        return (new ActionLogCollection($logs))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal.success.users.index.message'));
    }
    
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function show (Request $request, int $id) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "view", "internal.actions.logs.show", ActionLog::class);
        
        $query = ActionLog::query();
        
        $this->applyIncludes($request, $query, ["user", "target"]);
        
        $model = $query->find($id);
        
        if (!$model) {
            return BaseResource::error()
                ->setCode(404)
                ->setMessage(trans('internal.errors.logs.404.message'));
        }
        
        return (new ActionLogResource($model))
            ->success()
            ->setCode(200)
            ->setMessage(trans('internal.success.logs.show.message'))
            ->setDetails([
                "description"=> trans('internal.success.logs.show.description', ["id"=> $id]),
            ]);
    }
    
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function archive (LogArchiveRequest $request) {
        $user = Auth::user();
        
        $this->requireAuthenticatedUser();
        $this->authorizeUserAction($user, "archive", "internal.actions.logs.archive", ActionLog::class);
        
        $months = (int)config("app.logs_archived_after");
        
        $date = $request->validated("date") !== null
            ? Carbon::parse($request->validated("date"))
            : now()->subMonth($months);
        
        $logs = ActionLog::query()
            ->where("created_at", "<", $date)
            ->orderBy("created_at")
            ->orderBy("id")
            ->get();
        
        if ($logs->isEmpty()) {
            return BaseResource::make([
                'archived_count' => 0,
                'archive_before' => $date->toDateTimeString(),
                'archive_file' => null,
            ])->success()
                ->setCode(200)
                ->setMessage('Aucun log à archiver pour cette date.');
        }
        
        $directory = storage_path(config("app.log_directory"));
        
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
        
        ArchiveActionLogsJob::dispatch($date->toIso8601String());
        
        return BaseResource::make([
            'queued' => true,
            'archive_before' => $date->toDateTimeString(),
        ])->success()
            ->setCode(202)
            ->setMessage('Archivage des logs lance en arriere-plan.');
        
        
    }
}
