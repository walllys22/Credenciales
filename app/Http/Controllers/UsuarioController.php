<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => User::orderBy('name')->paginate(25)]);
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new User(['active' => true, 'role' => 'impresiones'])]);
    }

    public function edit(User $usuario)
    {
        return view('usuarios.form', compact('usuario'));
    }

    private function data(Request $request, User $usuario): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('users')->ignore($usuario->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($usuario->id)],
            'role' => ['required', Rule::in(['superadmin', 'impresiones'])],
            'active' => 'required|boolean',
            'password' => [$usuario->exists ? 'nullable' : 'required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    public function store(Request $request)
    {
        User::create($this->data($request, new User));

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado.');
    }

    public function update(Request $request, User $usuario)
    {
        $data = $this->data($request, $usuario);
        DB::transaction(function () use ($request, $usuario, $data) {
            User::where('role', 'superadmin')->orderBy('id')->lockForUpdate()->get();
            $usuario->refresh();
            if ($usuario->id === $request->user()->id && ($data['role'] !== 'superadmin' || ! $data['active'])) {
                throw ValidationException::withMessages(['role' => 'No puedes quitarte el acceso de administrador.']);
            }
            if ($usuario->isSuperAdmin() && $usuario->active && ($data['role'] !== 'superadmin' || ! $data['active']) && User::where('role', 'superadmin')->where('active', true)->count() <= 1) {
                throw ValidationException::withMessages(['role' => 'Debe permanecer un superadmin activo.']);
            }
            $usuario->update($data);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $usuario)
    {
        DB::transaction(function () use ($request, $usuario) {
            User::where('role', 'superadmin')->orderBy('id')->lockForUpdate()->get();
            $usuario->refresh();
            if ($usuario->id === $request->user()->id || ($usuario->isSuperAdmin() && $usuario->active && User::where('role', 'superadmin')->where('active', true)->count() <= 1)) {
                throw ValidationException::withMessages(['usuario' => 'No puedes eliminar tu cuenta ni el último superadmin activo.']);
            }
            $usuario->delete();
        });

        return back()->with('success', 'Usuario eliminado.');
    }
}
