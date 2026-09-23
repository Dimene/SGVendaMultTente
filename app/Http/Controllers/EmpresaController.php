<?php

namespace App\Http\Controllers;

use App\Models\Armazem;
use App\Models\Empresa;
use App\Models\loja_desc;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EmpresaController extends Controller
{
    public function index()
    {
        return Inertia::render('Configuracoes/EmpresaIndex', [
            'empresa' => Empresa::firstOrCreate(['id' => 1], [
                'nome' => 'Minha empresa',
                'pais' => 'Moçambique',
            ]),
            'lojas' => loja_desc::query()->orderBy('Desc')->get(['id', 'Desc']),
            'armazens' => Armazem::query()->orderBy('Descricao')->get(['id', 'Descricao']),
        ]);
    }

  public function update(Request $request, Empresa $empresa)
{
    $dados = $request->validate([
        'nome' => ['required', 'string', 'max:200'],
        'nome_fantasia' => ['nullable', 'string', 'max:150'],
        'documento' => ['nullable', 'string', 'max:50'],
        'email' => ['nullable', 'email', 'max:255'],
        'telefone' => ['nullable', 'string', 'max:30'],
        'celular' => ['nullable', 'string', 'max:30'],
        'endereco' => ['nullable', 'string'],
        'cidade' => ['nullable', 'string', 'max:100'],
        'provincia' => ['nullable', 'string', 'max:100'],
        'pais' => ['required', 'string', 'max:100'],
        'site' => ['nullable', 'string', 'max:255'],

        'logo' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:2048'
        ],

        'lojas' => ['nullable', 'array'],
        'lojas.*.id' => ['nullable', 'integer'],
        'lojas.*.nome' => ['required', 'string', 'max:255'],

        'armazens' => ['nullable', 'array'],
        'armazens.*.id' => ['nullable', 'integer'],
        'armazens.*.nome' => ['required', 'string', 'max:255'],
    ]);

    DB::transaction(function () use ($request, $empresa, &$dados) {

        /*
        |--------------------------------------------------------------------------
        | LOGO
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('logo')) {

            // Apaga a logo antiga
            if ($empresa->logo) {
                Storage::disk('public')->delete($empresa->logo);
            }

            // Guarda a nova logo
            $dados['logo'] = $request->file('logo')
                ->store('empresa', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | EMPRESA
        |--------------------------------------------------------------------------
        */

        $empresa->update(
            collect($dados)
                ->except(['lojas', 'armazens'])
                ->toArray()
        );


        /*
        |--------------------------------------------------------------------------
        | LOJAS
        |--------------------------------------------------------------------------
        */

        foreach ($dados['lojas'] ?? [] as $loja) {

            if (!empty($loja['id'])) {

                $registro = loja_desc::find($loja['id']);

                if (!$registro) {
                    $registro = new loja_desc();
                }

            } else {

                $registro = new loja_desc();
            }

            $registro->Desc = $loja['nome'];

            $registro->save();
        }


        /*
        |--------------------------------------------------------------------------
        | ARMAZÉNS
        |--------------------------------------------------------------------------
        */

        foreach ($dados['armazens'] ?? [] as $armazem) {

            if (!empty($armazem['id'])) {

                $registro = Armazem::find($armazem['id']);

                if (!$registro) {
                    $registro = new Armazem();
                }

            } else {

                $registro = new Armazem();
            }

            $registro->Descricao = $armazem['nome'];

            $registro->save();
        }
    });

    return back()->with(
        'success',
        'Dados da empresa atualizados com sucesso.'
    );
}

public function show($id){
   
}
}
