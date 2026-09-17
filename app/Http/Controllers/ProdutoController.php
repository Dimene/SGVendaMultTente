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


// dd($request->all());

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
        'preco_venda1' => $Item["Preço Venda cliente 1"],
        'preco_venda2' => $Item["Preço Venda cliente 2"],
        'estoque' => $Item["Stock"],
        'Armazem_id' => $idarmazem,
        'desconto' => min(100, max(0, (float) ($Item['Desconto (%)'] ?? $Item['desconto'] ?? 0))),
    ]
);


        $fotos = collect();
        $atributosExtras = [];

        $categoria = Categoria::with('atributos')->find($Item['categoria'] ?? null);

        foreach ($grupo->listaatributo as $itematributo) {
            $nomeAtributo = $itematributo->Descricao ?? $itematributo->nome;
            if (array_key_exists($nomeAtributo, $Item) && $Item[$nomeAtributo] !== '') {
                $atributosExtras[$nomeAtributo] = $Item[$nomeAtributo];
            }
        }

        foreach ($categoria?->atributos ?? [] as $itematributo) {
            $nomeAtributo = $itematributo->Descricao ?? $itematributo->nome;
            if (array_key_exists($nomeAtributo, $Item) && $Item[$nomeAtributo] !== '') {
                $atributosExtras[$nomeAtributo] = $Item[$nomeAtributo];
            }
        }

        if (is_string($Item['outros_Atributos'] ?? null) && trim($Item['outros_Atributos']) !== '') {
            $outrosAtributos = collect(explode('|', $Item['outros_Atributos']))
                ->mapWithKeys(function ($item) {
                    $item = trim($item);
                    if ($item === '') {
                        return [];
                    }

                    [$chave, $valor] = array_pad(array_map('trim', explode(':', $item, 2)), 2, '');
                    if ($chave === '') {
                        return [];
                    }

                    return [$chave => is_numeric($valor) ? $valor + 0 : $valor];
                })
                ->toArray();

            $atributosExtras = array_merge($atributosExtras, $outrosAtributos);
        }

        foreach ($Item['fotos'] ?? [] as $itemdados) {
            $fotos->push($this->salvarImagemBase64($itemdados));
        }

        produtos_atributos::updateOrCreate(['produto_item_id' => $dadosItem->id], [
            'outrosAtributos' => json_encode($atributosExtras),
            'fotos' => $fotos,
        ]);


    }





    //  return $request->all();





    return response()->json([
        'ok' => true,
        'message' => 'Produtos guardados com sucesso'
    ]);
}



    public function show($id)
    {
        $compra = Compra::where('id', $id)
            ->with([
                'produtosItem.produto.categoria.grupo.listaatributo',
                'produtosItem.produto.categoria.atributos',
                'produtosItem.outrosatributos',
                'produtosItem.produto.categoria'
            ])
            ->first();

        if (!$compra) {
            return response()->json(['dados' => []]);
        }

        $collecao = collect();

        foreach ($compra->produtosItem as $item) {
            $produto = $item->produto;
            $grupo = $produto?->categoria?->grupo;
            $grupoAtributos = $grupo?->listaatributo ?? collect();
            $dadosAtributos = json_decode($item->outrosatributos?->outrosAtributos ?? '{}', true) ?? [];
            $fotos = json_decode($item->outrosatributos?->fotos ?? '[]', true) ?? [];
            $armazem = $item->Armazem_id ? Armazem::find($item->Armazem_id) : null;

            foreach ($grupoAtributos as $atributo) {
                $nomeAtributo = $atributo->Descricao ?? $atributo->nome;
                if (isset($dadosAtributos[$nomeAtributo])) {
                    $dadosAtributos[$nomeAtributo] = $dadosAtributos[$nomeAtributo];
                }
            }

            foreach ($produto?->categoria?->atributos ?? [] as $atributo) {
                $nomeAtributo = $atributo->Descricao ?? $atributo->nome;
                if (isset($dadosAtributos[$nomeAtributo])) {
                    $dadosAtributos[$nomeAtributo] = $dadosAtributos[$nomeAtributo];
                }
            }

            $linhaProduto = [
                'id' => $item->id,
                'categoria' => $produto?->categoria?->id ?? null,
                'Nome' => $produto?->nome ?? '',
                'grupo' => $grupo?->nome ?? 'Sem grupo',
                'armazem' => $armazem?->Descricao ?? $item->Armazem_id ?? '',
                'Preço Compra' => (float) ($item->preco_compra ?? 0),
                'iva' => (float) ($item->iva ?? 0),
                'Preço Venda cliente 1' => (float) ($item->preco_venda1 ?? 0),
                'Preço Venda cliente 2' => (float) ($item->preco_venda2 ?? 0),
                'Desconto (%)' => (float) ($item->desconto ?? 0),
                'Venda com IVA cliente 1' => round(((float) ($item->preco_venda1 ?? 0)) * ($item->iva ?? 1), 2),
                'Venda com IVA cliente 2' => round(((float) ($item->preco_venda2 ?? 0)) * ($item->iva ?? 1), 2),
                'Stock' => (int) ($item->estoque ?? 0),
                'fotos' => $fotos,
                'outros_Atributos' => !empty($dadosAtributos) ? implode(' | ', array_map(
                    fn ($chave, $valor) => $chave . ': ' . $valor,
                    array_keys($dadosAtributos),
                    array_values($dadosAtributos)
                )) : '',
            ];

            foreach ($dadosAtributos as $key => $valor) {
                $linhaProduto[$key] = is_numeric($valor) ? (float) $valor : $valor;
            }

            $collecao->push((object) $linhaProduto);
        }

        return response()->json(['dados' => $collecao]);
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
