<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PersonalController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manage-users');

        return Inertia::render('Admin/Personal', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role', 'created_at']),
            'roleOptions' => UserRole::managementRoleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('manage-users');

        User::query()->create($request->validated());

        return redirect()->route('admin.personal')->with('success', 'Empleado creado correctamente.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        $data = $request->safe()->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        if ($user->id === $request->user()?->id && $data['role'] !== UserRole::ADMIN) {
            return back()->withErrors([
                'role' => 'No puedes quitarte el rol de administrador a ti mismo.',
            ]);
        }

        $user->update($data);

        return redirect()->route('admin.personal')->with('success', 'Empleado actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('manage-users');

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'delete' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        if ($user->role === UserRole::ADMIN && User::query()->where('role', UserRole::ADMIN)->count() <= 1) {
            return back()->withErrors([
                'delete' => 'Debe existir al menos un administrador en el sistema.',
            ]);
        }

        $user->delete();

        return redirect()->route('admin.personal')->with('success', 'Empleado eliminado correctamente.');
    }
}
