<?php

namespace App\Http\Controllers;

use App\Models\role;
use App\Models\User;
use App\Models\role_user;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuario = User::with('roles')->get();
         $roles = role::all();

   

        return Inertia::render('Auth/usuarioList', [
            'usuarios' => $usuario,
            'roles' => $roles
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email'
            ],

            'papel' => [
                'required',
                'exists:roles,id'
            ],
        ]);


        $senha = "1234567890";

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($senha),
        ]);


        // Criar relação usuário → papel
        role_user::create([
            'user_id' => $user->id,
            'role_id' => $request->papel
        ]);


        return redirect()
            ->route('usuario.index')
            ->with('success', 'Usuário registado com sucesso.');
    }


    public function update(Request $request, $id)
    {
        
        $request->validate([
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email,' . $id
            ],

            'papel' => [
                'required',
                'exists:roles,id'
            ],
        ]);


        // Procurar o usuário
        $user = User::findOrFail($id);


        // Atualizar dados do usuário
        $user->update([
            'name' => $request->name,
            'email' => $request->email
        ]);


        // Atualizar ou criar relação com o papel
        role_user::updateOrCreate(
            [
                'user_id' => $user->id
            ],
            [
                'role_id' => $request->papel
            ]
        );


        return redirect()
            ->route('usuario.index')
            ->with('success', 'Usuário actualizado com sucesso.');
    }


 public function destroy($id)
{
    $user = User::findOrFail($id);

    $user->delete();

    return response()->json([
        'success' => true,
        'message' => 'Usuário eliminado com sucesso.'
    ]);
}

public function resetarSenha($id)
{
    $user = User::findOrFail($id);

    // Definir a nova senha padrão
    $novaSenha = "1234567890";

    // Atualizar a senha do usuário
    $user->update([
        'password' => Hash::make($novaSenha)
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Senha resetada com sucesso.'
    ]);
}
public function resetarSenhapessoal()
{


// $id=auth()->user()->id;
// dd($id);
//     $user = User::findOrFail($id);

//     // Definir a nova senha padrão
//     $novaSenha = "1234567890";

//     // Atualizar a senha do usuário
//     $user->update([
//         'password' => Hash::make($novaSenha)
//     ]);

    return Inertia::render('Auth.ResetPassword');
}
}