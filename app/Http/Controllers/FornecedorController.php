<?php

namespace App\Http\Controllers;

use App\Models\contacto;
use App\Models\Distrito;
use App\Models\Fornecedor;
use App\Models\provincia;
use App\Models\tiposContacotos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{public function index()
{
    return Model::latest()->paginate(20);
}

public function store(Request $request)
{
    $empresa = 1;

    // PROVÍNCIA
    $provincia = provincia::firstOrCreate([
        'Nome' => $request->provincia
    ]);

    // DISTRITO
    $distrito = Distrito::firstOrCreate(
        [
            'Nome' => $request->distrito
        ],
        [
            'provincia_id' => $provincia->id
        ]
    );

    // FORNECEDOR
   $fornecedor = Fornecedor::updateOrCreate(
    // Condições para encontrar o registro (WHERE)
    [
        'email' => $request->email,
        'documento' => $request->nuit
    ],
    // Dados para criar ou atualizar
    [
        'empresa_id' => $empresa,
        'nome' => $request->nome,
        'endereco' => $request->endereco,
        'cidade' => $distrito->id,
        'provincia' => $provincia->id,
        'observacoes' => $request->observacoes,
        'ativo' => $request->ativo ?? 1,
    ]
);

    // CONTACTOS

    // dd($request->all() );
    foreach ($request->contactos as $item) {

    $tipo="";
        $tipo = tiposContacotos::where("Descricao",$item['tipo'])->first()??"1";



        contacto::updateOrCreate(
            [
                'proprietario_id'   => $fornecedor->id,
                'tipoproprietario'  => 'F',
                'valor'         => $item['valor']
            ],
            [
                'tipo_id' => $tipo->id
            ]
        );
    }

    return back()->with('success', 'Fornecedor criado com sucesso');
}

public function update(Request $request, Model $model)
{
    $model->update($request->all());

    return $model;
}

public function destroy(Model $model)
{
    $model->delete();

    return response()->json([
        'success' => true
    ]);
}


public function show($termo)
{
    $termoLimpo = strtolower(str_replace(' ', '', $termo));

    return Fornecedor::with(['distrito', 'provincia', 'contactos.tipocontacto'])
        ->where(function ($q) use ($termoLimpo) {
            $q->whereRaw('REPLACE(nome, " ", "") LIKE ? COLLATE utf8mb4_general_ci', ["%$termoLimpo%"])
              ->orWhereRaw('REPLACE(email, " ", "") LIKE ? COLLATE utf8mb4_general_ci', ["%$termoLimpo%"])
              ->orWhereRaw('REPLACE(documento, " ", "") LIKE ? COLLATE utf8mb4_general_ci', ["%$termoLimpo%"]);
        })
        ->first();
}
}
