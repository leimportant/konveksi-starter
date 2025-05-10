<?php

namespace App\Http\Controllers;

use App\Models\ModelRef;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // You can add any dashboard statistics or data here
        $total_order = ModelRef::count();
        $data = [
            'stats' => [
                'total_order' => $total_order,
                'total_transactions' => 7,
                'total_products' => 15,
                'total_users' => 5,
                // Add more statistics as needed
            ]
        ];

        return response()->json($data);
    }
}