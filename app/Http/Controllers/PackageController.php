<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q');

        $packages = \App\Models\Package::where('name', 'LIKE', "%{$query}%")
                        ->select('id', 'name')
                        ->limit(10)
                        ->get();

        return response()->json($packages);
    }
}
