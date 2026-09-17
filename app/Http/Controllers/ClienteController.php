<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
public function index()
{
    return Cliente::latest()->paginate(20);
}

public function store(Request $request)
{
    $dados = $request->validate([
        'nome' => ['required', 'string', 'max:255'],
        'documento' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255'],
        'telefone' => ['nullable', 'string', 'max:255'],
        'celular' => ['nullable', 'string', 'max:255'],
        'endereco' => ['nullable', 'string'],
        'cidade' => ['nullable', 'string', 'max:255'],
        'provincia' => ['nullable', 'string', 'max:255'],
        'limite_credito' => ['nullable', 'numeric', 'min:0'],
    ]);

    return response()->json(Cliente::create($dados), 201);
}

public function update(Request $request, Cliente $cliente)
{
    $cliente->update($request->all());

    return $cliente;
}

public function destroy(Cliente $cliente)
{
    $cliente->delete();

    return response()->json([
        'success' => true
    ]);
}
}
