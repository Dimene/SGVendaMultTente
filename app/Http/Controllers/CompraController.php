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
        $compras = Compra::query()
            ->with([
                'produtosItem' => fn ($query) => $query->where('estoque', '>', 0),
                'produtosItem.produtoloja' => fn ($query) => $query->where('Quantidade', '>', 0),
                'fornecedor',
            ])
            ->latest()
            ->get()
            ->map(function (Compra $compra) {
                $quantidade = $compra->produtosItem->sum(
                    fn ($item) => (int) ($item->estoque ?? 0)
                );

                $valor = $compra->produtosItem->sum(
                    fn ($item) => (float) ($item->estoque ?? 0) * (float) ($item->preco_compra ?? 0)
                );

                $lojaQuantidade = $compra->produtosItem->sum(
                    fn ($item) => (int) ($item->produtoloja?->Quantidade ?? 0)
                );

                $lojaValor = $compra->produtosItem->sum(
                    fn ($item) => (float) ($item->produtoloja?->Quantidade ?? 0)
                        * (float) ($item->preco_compra ?? 0)
                );

                return array_merge($compra->toArray(), [
                    'fornecedor_nome' => $compra->fornecedor?->nome,
                    'quantidade' => $quantidade,
                    'valor' => round($valor, 2),
                    'loja_quantidade' => $lojaQuantidade,
                    'loja_valor' => round($lojaValor, 2),
                    'estoque_total' => $quantidade + $lojaQuantidade,
                    'valor_estoque_total' => round($valor + $lojaValor, 2),
                ]);
            });

        return Inertia::render('Compras/ComprasIndex', [
            'compras' => $compras,
        ]);
    }

    public function relatorios()
    {
        return Inertia::render('Compras/ComprasRelatorio', [
            'fornecedores' => Fornecedor::query()->orderBy('nome')->get(['id', 'nome']),
        ]);
    }

    public function relatorioDados(Request $request)
    {
        $filtros = $request->validate([
            'data_inicial' => ['nullable', 'date'],
            'data_final' => ['nullable', 'date', 'after_or_equal:data_inicial'],
            'fornecedor_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        $compras = Compra::query()
            ->with(['fornecedor', 'tipo', 'despesas', 'produtosItem'])
            ->when($filtros['data_inicial'] ?? null, fn ($query, $data) => $query->whereDate('created_at', '>=', $data))
            ->when($filtros['data_final'] ?? null, fn ($query, $data) => $query->whereDate('created_at', '<=', $data))
            ->when($filtros['fornecedor_id'] ?? null, fn ($query, $fornecedorId) => $query->where('fornecedor_id', $fornecedorId))
            ->when($filtros['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(function (Compra $compra) {
                $stock = $compra->produtosItem->sum(fn ($item) => (int) ($item->estoque ?? 0));
                $valorStock = $compra->produtosItem->sum(
                    fn ($item) => (float) ($item->estoque ?? 0) * (float) ($item->preco_compra ?? 0)
                );
                $despesas = $compra->despesas->sum(fn ($despesa) => (float) ($despesa->valor ?? 0));

                return [
                    'id' => $compra->id,
                    'numero_pedido' => $compra->numero_pedido,
                    'fornecedor' => $compra->fornecedor?->nome ?? 'Sem fornecedor',
                    'status' => $compra->status,
                    'data' => $compra->created_at?->format('Y-m-d'),
                    'itens' => $compra->produtosItem->count(),
                    'stock' => $stock,
                    'valor_stock' => round($valorStock, 2),
                    'despesas' => round($despesas, 2),
                    'total' => round($valorStock + $despesas, 2),
                ];
            });

        return response()->json($compras);
    }


public function create(Request $request){

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
        "initialPedido" => $request->query('pedido', ''),
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
