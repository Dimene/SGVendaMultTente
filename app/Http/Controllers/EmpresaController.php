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
            'site' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'lojas' => ['array'],
            'lojas.*.id' => ['nullable', 'integer'],
            'lojas.*.nome' => ['required', 'string', 'max:255'],
            'armazens' => ['array'],
            'armazens.*.id' => ['nullable', 'integer'],
            'armazens.*.nome' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($empresa, $dados, $request) {
            if ($request->hasFile('logo')) {
                if ($empresa->logo) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $dados['logo'] = $request->file('logo')->store('empresa', 'public');
            }

            $empresa->update(collect($dados)->except(['lojas', 'armazens'])->toArray());

            $lojaIds = [];
            foreach ($dados['lojas'] ?? [] as $loja) {
                $registro = !empty($loja['id'])
                    ? loja_desc::find($loja['id'])
                    : new loja_desc();
                $registro->Desc = $loja['nome'];
                $registro->save();
                $lojaIds[] = $registro->id;
            }

            if ($lojaIds) {
                loja_desc::whereNotIn('id', $lojaIds)->delete();
            }

            $armazemIds = [];
            foreach ($dados['armazens'] ?? [] as $armazem) {
                $registro = !empty($armazem['id'])
                    ? Armazem::find($armazem['id'])
                    : new Armazem();
                $registro->Descricao = $armazem['nome'];
                $registro->save();
                $armazemIds[] = $registro->id;
            }

            if ($armazemIds) {
                Armazem::whereNotIn('id', $armazemIds)->doesntHave('produtositem')->delete();
            }
        });

        return back()->with('success', 'Dados da empresa atualizados com sucesso.');
    }
}
