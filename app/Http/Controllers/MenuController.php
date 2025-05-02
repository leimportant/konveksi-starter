<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\Log;

class MenuController extends Controller
{
    public function getUserMenus(): JsonResponse
    {
        $user = Auth::user();
        $userRoleIds = $user->roles->pluck('id');

        Log::info('User Role IDs: ' . $userRoleIds->implode(', '));
        // If user has no roles, return empty array
        if ($userRoleIds->isEmpty()) {
            return response()->json([]);
        }

        $menus = Menu::whereHas('roles', function ($query) use ($userRoleIds) {
                $query->whereIn('roles.id', $userRoleIds);
            })
            ->where('parent_id', 0)
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
}
