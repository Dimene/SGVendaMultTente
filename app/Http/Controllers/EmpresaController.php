<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;

class EmpresaController extends Controller
{
    public function index()
    {
        return Empresa::latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|max:200',
        ]);

        $empresa = Empresa::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $empresa
        ]);
    }

    public function show(Empresa $empresa)
    {
        return $empresa;
    }

    public function update(Request $request, Empresa $empresa)
    {
        $empresa->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $empresa
        ]);
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();

        return response()->json([
            'success' => true,
            'message' => 'Empresa removida'
        ]);
    }
}
