<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use \Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function getUserMenus(): JsonResponse
    {
        $user = Auth::user();
        $userRoleIds = $user->roles->pluck('id');

        // If user has no roles, return empty array
        if ($userRoleIds->isEmpty()) {
            return response()->json([]);
        }

        $menus = Menu::whereHas('roles', function ($query) use ($userRoleIds) {
                $query->whereIn('roles.id', $userRoleIds);
            })
            ->whereNull('parent_id')
            ->with(['children' => function ($query) use ($userRoleIds) {
                $query->whereHas('roles', function ($subQuery) use ($userRoleIds) {
                    $subQuery->whereIn('roles.id', $userRoleIds);
                });
            }])
            ->orderBy('order')
            ->get()
            ->map(function ($menu) {
                return [
                    'title' => $menu->title,
                    'href' => $menu->href,
                    'icon' => $menu->icon,
                    'children' => $menu->children->map(function ($child) {
                        return [
                            'title' => $child->title,
                            'href' => $child->href,
                            'icon' => $child->icon,
                            'children' => $child->children->map(function ($grandChild) {
                                return [
                                    'title' => $grandChild->title,
                                    'href' => $grandChild->href,
                                    'icon' => $grandChild->icon,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            });

        return response()->json($menus);
    }

    public function getAllMenus(Request $request): JsonResponse
    {
        $role_id = $request->role_id;

        $checkedMenuIds = collect();
        if ($role_id) {
            $checkedMenuIds = DB::table('menus_role')
                ->where('role_id', $role_id)
                ->pluck('menu_id');
        }

        $menus = Menu::whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('order');
            }])
            ->orderBy('order')
            ->get()
            ->map(function ($menu) use ($checkedMenuIds) {
                $menuArray = [
                    'id' => $menu->id,
                    'checked' => $checkedMenuIds->contains($menu->id),
                    'title' => $menu->title,
                    'href' => $menu->href,
                    'icon' => $menu->icon,
                ];

                $menuArray['children'] = $menu->children->map(function ($child) use ($checkedMenuIds) {
                    return [
                        'id' => $child->id,
                        'checked' => $checkedMenuIds->contains($child->id),
                        'title' => $child->title,
                        'href' => $child->href,
                        'icon' => $child->icon,
                    ];
                })->values();

                return $menuArray;
            });

        return response()->json($menus);
    }
}
