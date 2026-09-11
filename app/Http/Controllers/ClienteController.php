<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ClienteController extends Controller
{public function index()
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
