<?php
namespace App\Http\Controllers;

use App\Exports\produtosExport;
use App\Models\Armazem;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\gruposItem;
use App\Models\Produto;
use App\Models\produtoitems;
use App\Models\produtos_atributos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;


class ProdutoController extends Controller
{
    public function index()
    {
        return Produto::latest()->get();
    }


public function store(Request $request)
{




//   return response()->json($request->all());

    if (!$request->items) {
        return response()->json([
            'ok' => false,
            'message' => 'Nenhum item recebido'
        ], 422);
    }

    foreach ($request->items as $Item) {

        $grupo = gruposItem::where("nome", $Item["grupo"] ?? null)->with('listaatributo')->first();



        if (!$grupo) {
            return response()->json([
                'ok' => false,
                'message' => 'Grupo não encontrado: ' . ($Item["grupo"] ?? 'null')
            ], 422);
        }




     $produto=   Produto::updateOrCreate(
            [
                'empresa_id' => 1,
                'nome' => $Item["Nome"] ?? null,
            ],
            [
                'categoria_id' => $Item["categoria"] ?? null,
                'estoque_minimo' => 1,
                'grupo_id' =>$grupo["id"],
                'ativo' => 1

            ]
        );



//  dd($Item,$request->compra_id);

 $idarmazem=Armazem::where("Descricao",$Item["armazem"])->first()->id;
   $dadosItem= produtoitems::updateOrCreate(
    [
        'produto_id' => $produto->id, // Condição para procurar
        'compra_id' => $request->compra_id, // Condição para procurar
    ],
    [

        'preco_compra' => $Item["Preço Compra"],
        'iva' => 16,
        'preco_venda' => $Item["Preço Venda"],
        'estoque' => $Item["Stock"],
        'Armazem_id' => $idarmazem,
        'desconto' => 1,
    ]
);


                // 'categoria_id' => $Item["categoria"] ?? null,


// $atributo=Categoria::where('atributos')->first();
$dadosss=categoria::where("id",$Item['categoria'])->with('atributos')->first();

$atributoscategoria=collect();
$atributogrupo=collect();
$fotos=collect();


$outros_Atributos = collect(explode('|', $Item["outros_Atributos"]))
    ->mapWithKeys(function ($item) {
        [$chave, $valor] = array_map('trim', explode(':', $item, 2));
        return [$chave => is_numeric($valor) ? $valor + 0 : $valor];
    })
    ->toArray();
$outros_Atr = json_encode($outros_Atributos);


  foreach($Item["fotos"] as $key=>$itemdados){
    $atributos=$key."".$Item["Nome"];
$fotos->push($this->salvarImagemBase64($itemdados));
    }


produtos_atributos::updateOrCreate(['produto_item_id'=>$dadosItem->id],[
        'outrosAtributos'=>$outros_Atr,
        'fotos'=>$fotos]);


    }





    //  return $request->all();





    return response()->json([
        'ok' => true,
        'message' => 'Produtos guardados com sucesso'
    ]);
}



    public function show($id)



    {

$titulo="";
$collecao=collect();
        $dados=Compra::where("id",$id)->with(['produtosItem.produto.categoria.grupo',"produtosItem.outrosatributos"])->first();

$categorias=Categoria::with('atributos')->get();


        foreach ($dados->produtosItem as $item) {
$titulo= $item->produto->categoria->grupo->nome;
    $produto = [
        'id'      => $item->id,
        'categoria'      => $item->produto->categoria->id,
        'Nome'           => $item->produto->nome,
        'grupo'          => $item->produto->categoria->grupo->nome,
        'Preço Compra'   => $item->preco_compra,
        'Preço Venda'    =>  $item->preco_venda,
        'Venda com IVA'  => $item->preco_venda,
        'Stock'          => $item->estoque,
        'fotos'          => json_decode($item->outrosatributos?->fotos, true),
    ];


    // Converter o JSON para array

    // echo($item->outrosatributos);
    $atributos = json_decode($item->outrosatributos?->outrosAtributos, true) ?? [];


    // Juntar os atributos ao produto
    $produto = array_merge($produto, $atributos);

    // Adicionar à coleção
    $collecao->push((object)$produto);
}


//   dd($collecao);
return response()->json(["dados"=>$collecao]);
    }

    public function update(Request $request, $id)
    {
        $produto = Produto::findOrFail($id);
        $produto->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $produto
        ]);
    }

public function destroy(Request $request)
{
    $item = produtoitems::where("compra_id", $request->compra_id)
        ->where('id', $request->item['id'])
        ->first();


    if(!$item){
        return response()->json([
            'success'=>false,
            'message'=>'Item não encontrado'
        ],404);
    }


    // atributos do produto
    $outros_Atrib = produtos_atributos::where(
        'produto_item_id',
        $item->id
    )->first();


    if($outros_Atrib){

        // caso tenha fotos
        // foreach($outros_Atrib->foto as $foto){
        //     Storage::delete($foto);
        // }

        $outros_Atrib->delete();
    }


    // eliminar produto principal
    Produto::where(
        'id',
        $item->produto_id
    )->delete();


    // eliminar item da compra
    $item->delete();


    return response()->json([
        'success'=>true,
        'message'=>'Produto eliminado'
    ]);
}


      public function create(){
$grupoItem=gruposItem::with('listaatributo','categoria')->get();


$dados=[];
   foreach($grupoItem as $item){



$collect=collect();

$collect['Foto']='';
$collect['categoria']='';
$collect['Nome']='';


foreach($item->listaatributo as $item2 ){
   $collect[$item2->Descricao]="";
}
$collect['stoque']='';
$collect['preco_compra']='';
$collect['preco_venda']='';
$collect['venda_iva']='';

$dados[$item->nome][0]=$collect;


   }



   $dados=collect($dados);
   $def=collect($dados);

// dd( $dados);



     return Inertia::render('Produtos/ProdutoCreate',["grupoItem" =>$grupoItem,
     "dados"=>$dados,
     "def"=>$dados,
     ]);

      }




      function salvarImagemBase64($imagemBase64)
{
    // separar tipo e dados
    preg_match("/^data:image\/(\w+);base64,/", $imagemBase64, $type);

    if (!$type) {
        return null;
    }

    $imagemBase64 = substr($imagemBase64, strpos($imagemBase64, ',') + 1);
    $extension = $type[1]; // png, jpg, etc

    $imagemBase64 = base64_decode($imagemBase64);

    $fileName = 'produtos/' .Str::random(20) . '.' . $extension;

    Storage::disk('public')->put($fileName, $imagemBase64);

    return $fileName;
}


public function import()  {


$categorias = gruposItem::where('id',1)->first();

return Excel::download(
    new ProdutosExport($categorias),
    'ModeloImportacao.xlsx'
);

}
}
