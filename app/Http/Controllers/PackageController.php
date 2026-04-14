<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Package;

class PackageController extends Controller
{
    public function search(Request $request)
    {
        $searchTerm = $request->query('q');   // Better than get('q')

        $query = Package::select('id', 'name')
            ->where('name', 'LIKE', "%{$searchTerm}%");

        // Filter packages based on user level
        if (Auth::check()) {
            $level = Auth::user()->userType?->level ?? 0;

            if ($level === 3) {
                $query->where('type', 'division');
            } elseif ($level === 1) {
                $query->where('type', 'school');
            }
            // You can add more levels here later
            // elseif ($level === 4) {
            //     $query->where('type', 'region');
            // }
        }

        $packages = $query->limit(10)->get();

        return response()->json($packages);
    }
} 