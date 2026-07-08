<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchWebController extends Controller
{
    public function index(): \Inertia\Response
    {
        $branches = \App\Models\Branch::withCount(['users', 'tickets', 'packages'])->get();
        return \Inertia\Inertia::render('Settings/Branches/Index', [
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        \App\Models\Branch::create($validated);

        return back()->with('success', 'Sucursal creada exitosamente.');
    }

    public function update(Request $request, \App\Models\Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return back()->with('success', 'Sucursal actualizada exitosamente.');
    }

    public function toggleActivo(\App\Models\Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);
        return back()->with('success', 'Estado de la sucursal actualizado.');
    }
}
