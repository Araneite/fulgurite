<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class DashboardPageRegistry
{

    public function forUser(?User $user): array {
        $pages = collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route)=> in_array("GET", $route->methods(), true))
            ->map(fn (LaravelRoute $route)=> $this->pageFromRoute($route))
            ->filter()
            ->filter(fn (array $page)=> $page["in_nav"] === true)
            ->filter(fn (array $page)=> $this->userCanAccess($user, $page))
            ->sortBy("order")
            ->values();
        
        return $this->buildSections($pages);
    }
    
    private function pageFromRoute(LaravelRoute $route): ?array {
        $meta = $route->defaults["dashboard_page"] ?? null;
        
        if (!is_array($meta)) return null;
        
        if ($route->parameterNames() !== []) return null;
        
        $name = $route->getName();
        $uri = $route->uri();
        
        return [
            "key"=> $meta["key"] ?? $name ?? $route->uri(),
            "in_nav"=> $meta["in_nav"] ?? false,
            'description'=> $meta["description"] ?? "",
            "label"=> $meta["label"] ?? $name ?? $route->uri(),
            "url"=> "/" . ltrim($uri, "/"),
            "icon"=> $meta["icon"] ?? "pi-circle",
            "section"=> $meta["section"] ?? "Dashboard",
            "permissions"=> $meta["permissions"] ?? [],
            "group"=> $meta["group"] ?? null,
            "order"=> $meta["order"] ?? 100,
        ];
    }
    
    private function buildSections(Collection $pages): array {
        return $pages
            ->groupBy("section")
            ->map(function (Collection $sectionPages, string $section) {
                $items = collect();
                
                foreach($sectionPages as $page) {
                    if (is_array($page["group"])) {
                        $groupKey = $page["group"]["key"];
                        
                        $group = $items->get($groupKey, [
                            "key"=> $groupKey,
                            "label"=> $page["group"]["label"],
                            "icon"=> $page["group"]["icon"] ?? "pi-folder",
                            "order"=> $page["group"]["order"] ?? 100,
                            "items"=> []
                        ]);
                        
                        $group["items"][] = $this->menuItem($page);
                        $items->put($groupKey, $group);
                        
                        continue;
                    }
                    
                    $items->put($page["key"], $this->menuItem($page));
                }
                
                return [
                    "label"=> $section,
                    "order"=> $sectionPages->min("section_order"),
                    "items"=> $items
                        ->sortBy("order")
                        ->map(function (array $item) {
                            if (isset($item["items"])) {
                                $item["items"] = collect($item["items"])
                                    ->sortBy("order")
                                    ->values()
                                    ->all();
                            }
                            
                            unset($item["order"]);
                            
                            return $item;
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy("order")
            ->map(function (array $section) {
                unset($section["order"]);
                
                return $section;
            })
            ->values()
            ->all();
    }
    
    private function menuItem(array $page): array {
        return [
            "key"=> $page["key"],
            "label"=> $page["label"],
            "url"=> $page["url"],
            "icon"=> $page["icon"],
            "order"=>   $page["order"],
            "in_nav"=> $page["in_nav"] ?? false,
        ];
    }
    
    private function userCanAccess(?User $user, array $page): bool {
        if ($user === null) return false;
        
        $permissions = $page["permissions"];
        
        if ($permissions === []) return true;
        
        return $user->hasAnyPermission($permissions);
    }
}
