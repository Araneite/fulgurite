<?php

namespace App\Traits;

use App\Exceptions\PaginationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait HasApiPagination
{
    public function scopeApiPaginate(
        Builder $query,
        Request $request,
        int $defaultPerPage = 10,
        int $maxPerPage = 100
    ): LengthAwarePaginator {
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = max(1, min((int) $request->integer('per_page', $defaultPerPage), $maxPerPage));
        
        $paginator = $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());
        
        if ($paginator->total() > 0 && $page > $paginator->lastPage()) {
            throw new PaginationException(
                trans("internal.errors.pagination.404.message"),
                [
                    "page"=> trans('internal.errors.pagination.404.description', [
                        "page"=> $page,
                        "last_page"=> $paginator->lastPage()
                    ]),
                ]
            );
        }
        
        return $paginator;
    }

    public function scopeApiList(
        Builder $query,
        Request $request,
        int $defaultPerPage = 10,
        int $maxPerPage = 100,
        int $autoPaginateThreshold = 50
    ): Collection|LengthAwarePaginator {
        $hasPaginationParams = $request->hasAny(['page', 'per_page']);

        if (! $hasPaginationParams) {
            $total = (clone $query)->count();

            if ($total <= $autoPaginateThreshold) {
                return $query->get();
            }
        }

        $page = max(1, (int) $request->integer('page', 1));
        $perPage = (int) $request->integer('per_page', $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $paginator = $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        if ($paginator->total() > 0 && $page > $paginator->lastPage()) {
            throw new PaginationException(
                trans('internal.errors.pagination.404.message'),
                [
                    'page' => trans('internal.errors.pagination.404.description', [
                        'page' => $page,
                        'last_page' => $paginator->lastPage(),
                    ]),
                ]
            );
        }

        return $paginator;
    }
}
