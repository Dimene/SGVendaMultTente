<?php
namespace App\Http\Controllers;

use App\Models\Armazem;
use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\CompraTipo;
use App\Models\Compratipos;
use App\Models\despeas;
use App\Models\Distrito;
use App\Models\Fornecedor;
use App\Models\gruposAtributos;
use App\Models\gruposItem;
use App\Models\listaatributos;
use App\Models\lucro;
use App\Models\Produto;
use App\Models\provincia;
use App\Models\tabela_ivas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CompraController extends Controller
{
  public function index()
{
    $dados = Compra::latest()->get();


  return Inertia::render("Compras/ComprasIndex", [
    "compras" => $dados
]);
}



public function  create(){

// $produtos=Produto::with('categoria','grupo.listaatributo','produtoitems.produtoatributos.nomeatributo')->get();
//$produtos=Produto::with('categoria','grupo.listaatributo')->get();
$grupoItem=gruposItem::with('listaatributo','categoria')->get();
$listaatributos=listaatributos::all();
$gruposAtributos=gruposAtributos::all();

// dd($grupoItem,$listaatributos,$gruposAtributos);
// dd($grupoItem);
      $dados = Compra::all();
    $fornecedor=Fornecedor::all();
    $categora=Fornecedor::all();
$distritos=Distrito::all();
$provincia=provincia::all();
// dd($distritos);
$prodfutosGrup=collect();




$iva=tabela_ivas::all();
$lucro=lucro::all();

$armazem=Armazem::all();



  return Inertia::render("Compras/ComprasCreate", [
    "compras" => $dados,
    "categora" =>$categora,
    "fornecedor" =>$fornecedor,
    "distritos" =>$distritos,
    "provincia" =>$provincia,
    "grupoItem" =>$grupoItem,
    "iva" =>$iva,
    "lucro" =>$lucro,
    "armazem" =>$armazem

]);

}

public function store(Request $request)
{



    try {

        $tipocompra = Compratipos::where("Descricao", $request->tipoCompra)->firstOrFail();

        if (!$tipocompra) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de compra não encontrado.'
            ], 404);
        }

        $compra = Compra::updateOrCreate(
            [
                'empresa_id' => 1,
                'numero_pedido' => $request->Fatura,
            ],
            [
                'fornecedor_id' => $request->fornecedorid,
                'data_compra' => $request->dataCompra,
                'usuario_id' => auth()->id(),
                'observacoes' => $request->observacoes,
                'status' => $request->estado,
                'tipo_id' => $tipocompra->id,
            ]
        );


        foreach ($request->despesas as $item) {

            despeas::updateOrCreate(
                [
                    'compra_id' => $compra->id,
                    'tipo' => $item['tipo'],
                ],
                [
                    'Descricao' => $item['descricao'],
                    'valor' => $item['valor'],
                ]
            );
        }
$dado = [
    'success' => 1,
    'compra_id' => $compra->id,
    'message' => 'Compra salva com sucesso.'
];



return response()->json($dado);
    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Erro ao salvar a compra.',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function show($id)


    {
        $compra= Compra::where('numero_pedido',$id)->with(['tipo','despesas'])->first();


        return $compra;
    }



    public function dadosCompra(){




    return Inertia::render('Compras/DadosCompra');

    }

}
