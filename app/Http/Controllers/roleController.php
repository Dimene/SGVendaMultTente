<?php

namespace App\Http\Controllers;

use App\Models\permission_role;
use App\Models\role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class roleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles=role::with(['permissions','users'])->get();

    //  dd($roles);
     return Inertia::render('roles/rolesindex',["roles"=>$roles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
    // dd($request->all());

   $roles= role::updateOrCreate([
        'name'=>$request->name],[
        'label'=>$request->label,
        'description'=>$request->description
    ]);



    foreach($request->permissoes as $permission_id):
    permission_role::create([
        'role_id'=>$roles->id,
        'permission_id'=>$permission_id
    ]);
endforeach;

    return redirect()->route('roles.index')->with('success','Papel  creado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        // dd($request->all());

    role::where("id",$id)->update([
            'name'=>$request->name,
            'label'=>$request->label,
            'description'=>$request->description
        ]);

        permission_role::where("role_id",$id)->delete();
        
foreach($request->permissoes as $permission_id):
    permission_role::create([
        'role_id'=>$id,
        'permission_id'=>$permission_id
    ]);
endforeach;

        return redirect()->route('roles.index')->with('success','papel atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */

        public function destroy($id)
{
    $role = role::where('id', $id)
        ->whereDoesntHave('users')
        ->first();

    if (!$role) {
        return response()->json([
            'success' => false,
            'message' => 'Este papel não pode ser eliminado porque está associado a um ou mais usuários.'
        ], 422);
    }

    $role->delete();

    return response()->json([
        'success' => true,
        'message' => 'Papel eliminado com sucesso.'
    ]);
}
    


   public function mostrar()
{
    $roles = role::all();

    return response()->json($roles);
}
}
