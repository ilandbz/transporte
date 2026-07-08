<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserWebController extends Controller
{
    public function index(): Response
    {
        $users = User::with('branch')->orderBy('name')->get();
        $branches = \App\Models\Branch::where('is_active', true)->get();
        return Inertia::render('Settings/Users/Index', compact('users', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:200',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:admin,conductor,counter',
            'branch_id'=> 'required|exists:branches,id',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'branch_id'=> $validated['branch_id'],
        ]);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:200',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'role'     => 'required|in:admin,conductor,counter',
            'password' => 'nullable|string|min:8',
            'branch_id'=> 'required|exists:branches,id',
        ]);

        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
            'branch_id' => $validated['branch_id'],
            ...($validated['password'] ? ['password' => Hash::make($validated['password'])] : []),
        ]);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }
        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }
}
