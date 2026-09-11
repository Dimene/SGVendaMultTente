<?php
namespace App\Http\Controllers;



use App\Models\Categoria;
use App\Models\CategoriaAtributo;
use App\Models\gruposItem;
use App\Models\listaatributos;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoriaController extends Controller
{
    public function index()
    {
        return Categoria::latest()->paginate(20);
    }

    public function store(Request $request)
    {

$grupo=gruposItem::where("nome",$request->grupo)->first()?->id;

 $dados=Categoria::updateOrCreate([
     'nome'=>$request->categoria,
        'grupo_id'=>$grupo],
['tipo'=>'PRODUTO',
'ativo'=>1,
   ]);

   foreach($request->atributos as $item):
$atributo=listaatributos::updateOrCreate(["Descricao"=>$item["nome"]]);
CategoriaAtributo::updateOrCreate(["categoria_id"=>$dados->id, "atributo_id"=>$atributo->id]);


   endforeach;
   $categorias=Categoria::where('grupo_id',$grupo)->with('atributos')->get();
return  Response()->json(["sucesss"=>1,"categorias"=>$categorias]);
    }

    public function show(Categoria $categoria)
    {
        return $categoria;
    }

    public function update(Request $request, Categoria $categoria)
    {
        $categoria->update($request->all());

        return response()->json($categoria);
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->delete();

        return response()->json([
            'message' => 'Categoria removida'
        ]);
    }



  public function atributos($categoria)
{
    $categoria = Categoria::with('atributos')
        ->find($categoria);

    return response()->json($categoria);
}



public function create(){



return Inertia::render("compras/categoriaCreate",['habaactiva'=>'eletronicos']);
}

}
