<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request, $id)
    {
        $branch = \App\Models\Branch::findOrFail($id);
        
        if ($request->user() && $request->user()->role === 'admin') {
            session(['active_branch_id' => $branch->id]);
            return back()->with('success', 'Sucursal cambiada a: ' . $branch->name);
        }
        
        abort(403);
    }
}
