<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
   public function index()
{
    return Model::latest()->paginate(20);
}

public function store(Request $request)
{
    return Model::create($request->all());
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
}
