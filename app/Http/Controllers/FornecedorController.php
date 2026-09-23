<?php

namespace App\Http\Controllers;

use App\Models\contacto;
use App\Models\Distrito;
use App\Models\Fornecedor;
use App\Models\provincia;
use App\Models\tiposContacotos;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FornecedorController extends Controller
{
public function index()


{
    $fornecdor=Fornecedor::with(['distrito', 'provincia', 'contactos.tipocontacto'])
            ->where('empresa_id', 1)
            ->latest()
            ->get();
    // dd($fornecdor);

    return Inertia::render('Compras/FornecedoresIndex', [
        'fornecedores' => $fornecdor, 
        'distritos' => Distrito::orderBy('Nome')->get(['id', 'Nome', 'provincia_id']),
        'provincias' => provincia::orderBy('Nome')->get(['id', 'Nome']),
        'tiposContacto' => tiposContacotos::orderBy('Descricao')->get(['id', 'Descricao']),
    ]);
}

public function store(Request $request)
{
    $this->normalizarDadosLegados($request);

    $dados = $request->validate([
        'nome' => ['required', 'string', 'min:3', 'max:255'],
        'nuit' => ['required', 'string', 'regex:/^\d{9}$/'],
        'email' => ['nullable', 'email', 'max:255'],
        'endereco' => ['nullable', 'string'],
        'distrito' => ['required', 'integer', 'exists:distritos,id'],
        'provincia' => ['required', 'integer', 'exists:provincias,id'],
        'observacoes' => ['nullable', 'string'],
        'ativo' => ['nullable', 'boolean'],
        'contactos' => ['required', 'array', 'min:1'],
        'contactos.*.tipo_id' => ['required', 'integer', 'exists:tiposcontactos,id'],
        'contactos.*.valor' => ['required', 'string', 'max:100'],
    ]);

    DB::transaction(function () use ($dados) {
        $fornecedor = Fornecedor::create([
            'empresa_id' => 1,
            'nome' => $dados['nome'],
            'documento' => $dados['nuit'],
            'email' => $dados['email'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'cidade' => $dados['distrito'],
            'provincia' => $dados['provincia'],
            'observacoes' => $dados['observacoes'] ?? null,
            'ativo' => $dados['ativo'] ?? true,
        ]);

        $this->sincronizarContactos($fornecedor, $dados['contactos']);
    });

    return back()->with('success', 'Fornecedor criado com sucesso.');
}

public function update(Request $request, Fornecedor $fornecedor)
{
    $this->normalizarDadosLegados($request);

    $dados = $request->validate([
        'nome' => ['required', 'string', 'min:3', 'max:255'],
        'nuit' => ['required', 'string', 'regex:/^\d{9}$/'],
        'email' => ['nullable', 'email', 'max:255'],
        'endereco' => ['nullable', 'string'],
        'distrito' => ['required', 'integer', 'exists:distritos,id'],
        'provincia' => ['required', 'integer', 'exists:provincias,id'],
        'observacoes' => ['nullable', 'string'],
        'ativo' => ['nullable', 'boolean'],
        'contactos' => ['required', 'array', 'min:1'],
        'contactos.*.tipo_id' => ['required', 'integer', 'exists:tiposcontactos,id'],
        'contactos.*.valor' => ['required', 'string', 'max:100'],
    ]);

    DB::transaction(function () use ($dados, $fornecedor) {
        $fornecedor->update([
            'nome' => $dados['nome'],
            'documento' => $dados['nuit'],
            'email' => $dados['email'] ?? null,
            'endereco' => $dados['endereco'] ?? null,
            'cidade' => $dados['distrito'],
            'provincia' => $dados['provincia'],
            'observacoes' => $dados['observacoes'] ?? null,
            'ativo' => $dados['ativo'] ?? true,
        ]);

        $this->sincronizarContactos($fornecedor, $dados['contactos']);
    });

    return back()->with('success', 'Fornecedor atualizado com sucesso.');
}

public function destroy(Fornecedor $fornecedor)
{
    $fornecedor->contactos()->delete();
    $fornecedor->delete();

    return response()->json([
        'success' => true
    ]);
}

private function sincronizarContactos(Fornecedor $fornecedor, array $contactos): void
{
    $fornecedor->contactos()->delete();

    foreach ($contactos as $item) {
        contacto::create([
            'proprietario_id' => $fornecedor->id,
            'tipoproprietario' => 'F',
            'tipo_id' => $item['tipo_id'],
            'valor' => $item['valor'],
        ]);
    }
}

private function normalizarDadosLegados(Request $request): void
{
    $provincia = $request->provincia;
    if ($provincia && !is_numeric($provincia)) {
        $provincia = provincia::where('Nome', $provincia)->value('id');
    }

    $distrito = $request->distrito;
    if ($distrito && !is_numeric($distrito)) {
        $distrito = Distrito::where('Nome', $distrito)->value('id');
    }

    $contactos = collect($request->input('contactos', []))->map(function ($contacto) {
        $tipoId = $contacto['tipo_id'] ?? null;
        if (!$tipoId && !empty($contacto['tipo'])) {
            $tipoId = tiposContacotos::whereRaw('LOWER(Descricao) = ?', [strtolower($contacto['tipo'])])
                ->value('id');
        }

        return [
            'tipo_id' => $tipoId,
            'valor' => $contacto['valor'] ?? '',
        ];
    })->values()->all();

    $request->merge([
        'provincia' => $provincia,
        'distrito' => $distrito,
        'ativo' => $request->has('ativo') ? $request->boolean('ativo') : $request->status !== 'inativo',
        'contactos' => $contactos,
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
