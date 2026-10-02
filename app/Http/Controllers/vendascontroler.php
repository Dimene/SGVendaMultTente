<?php

namespace App\Http\Controllers;

use App\Models\Armazem;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\gruposItem;
use App\Models\loja;
use App\Models\loja_desc;
use App\Models\Produto;
use App\Models\produtoitems;
use App\Models\tabela_ivas;
use App\Models\User;
use App\Models\VendaItem;
use App\Models\vendas;
use App\Models\ViaPagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;
use function Termwind\render;

class vendascontroler extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    if(!auth()->user()->can('venda-index')){

        return Inertia::render('componets/alerta');
        }

   
  
        $vendas=vendas::with([
        'itens.produto.produto.categoria',
        'viaPagamento',
        'cliente',
        'usuario'
    ])->get();




$collec=collect();

$viaspagamentos=ViaPagamento::all();

// dd($vendas);
foreach($vendas as $item){

$collec->push((object)
[
    "Fatura"=>$item->reiboNr,
    "Via_pagamento"=>$item->viaPagamento->nome,
    "Valor_pago"=>$item->valor_pago,
    "Troco"=>$item->Troco,
    "Data"=>Carbon::parse($item->created_at)->format('d-m-Y'),
    "Horas"=>Carbon::parse($item->created_at)->format('H:m:s'),
    "Items"=>count($item->itens),
    "Total"=>$item->total,
    "id"=>$item->id,
    "usuario"=>$item->usuario->name,
    "cliente"=>$item->cliente->nome,
    'detalhes'=>$item

]);

}
// dd($collec);



return  Inertia::render('vendas/vendasIndex',[
    'vendas'=>$collec,
    'viaspagamentos'=>$viaspagamentos
    ]);
// return  Inertia::render('vendas/vendasIndex',['vendas'=>$collec]);
    }

    /**
     * Show the form for creating a new resource.
     */
   public function create($idloja)
{


// dd($idloja);

$item=collect(loja::where('loja_id',$idloja)->pluck("produtoitem_id")->toArray());





$item=produtoitems::whereIn('id',$item)->with(['produto.categoria','outrosatributos','produtoloja.loja'])

->get();

 
$tabela_ivas=tabela_ivas::all();

$viaspagamentos=ViaPagamento::all();


$clientes=Cliente::all();
//  dd($item,$clientes,);
    return Inertia::render('vendas/vendasCreate', [
        'grupo'=>$item,
        'tabela_ivas'=>$tabela_ivas,
        'viaspagamentos'=>$viaspagamentos,
        'clientes'=>$clientes,
        'idloja'=>$idloja,
    ]);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //


          $loja=($request->all()["itens"][0]["loja"]["loja_id"]);
        
$vendasTotal=vendas::whereMonth("created_at",Carbon::now()->format('m'))
->whereYear("created_at",Carbon::now()->format('Y'))->max('reiboNr');

if($vendasTotal==0){
$vendasTotal=1;
}
else{
 $vendasTotal = (int) substr($vendasTotal, 6)+1;
}


$TlaoNunero = str_pad($vendasTotal, 4, '0', STR_PAD_LEFT);
$fatura=Carbon::now()->format('Ym').''.$TlaoNunero;

$usuario=auth()->user()->id;
         $idvendas=   vendas::create(['via_pagamento_id'=>$request["via_pagamento_id"],
        'valor_pago'=>$request["valor_pago"],
        'troco'=>$request["troco"],
        'cliente_id'=>$request["cliente_id"],
        'loja_id'=>$loja,
        'usario_id'=>$usuario,
        'referencia'=>$request["referencia"],
        'reiboNr'=>$fatura,
        'total'=>$request['total']
        ]);

    foreach($request->itens as $item){
    //   $itemget=  produtoitems::where("id",$item['produto_id'])->first();
$idloja_id=($item["loja"]["loja_id"]);

 $idprodutoloaj=$item["loja"]["id"];
//  $idprodutoloaj=$item["loja"]["loja_id"];

//  dd($idprodutoloaj);

 $quantidade=$item["loja"]["Quantidade"]-$item["quantidade"];
 loja::where("id",$idprodutoloaj)
 ->update(["Quantidade"=> $quantidade]);

       
            VendaItem::create([
                'venda_id'=>$idvendas->id,
        'produto_id'=>$item["loja"]['produtoitem_id'],
        'quantidade'=>$item['quantidade'],
        'preco_unitario'=>$item['preco_unitario'],
        'desconto'=>$item['desconto'],
        'iva'=>$item['iva'],
        'loja_id'=>$idloja_id,
        'subtotal'=>$item['total_linha'],

            ]);

        
    }

    }




    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {


    // $venda=vendas::where("id",$id)->with([
    //     'itens.produto.produto.categoria',
    //     'viaPagamento'
    // ])->first();






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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }



    public function reverter($id)
    {


$dados=vendas::where("id",$id)->with('itens.produtoloja')->first();
foreach($dados->itens as $item){
    $quatidade=$item->produtoloja->Quantidade+$item->quantidade;
    loja::where("id",$item->produtoloja->id)->update(["Quantidade"=>$quatidade]);
}
vendaitem::where("venda_id",$id)->delete();
vendas::where("id",$id)->delete();


redirect()->route('vendas.index')->with('success', 'Venda revertida com sucesso.');


    }

public function relatorios()
{
    $funcionarios    = User::all();          // ← renomeado
    $viasdepagamento = ViaPagamento::all();
    $empresa         = Empresa::first();
    $lojas=loja_desc::all();

    return Inertia::render('vendas/relatorioVendas', [
        'funcionarios'    => $funcionarios,  // ← nome que não colide
        'viasdepagamento' => $viasdepagamento,
        'empresa'         => $empresa,       // ← sem cifrão
        'lojas'         => $lojas,       // ← sem cifrão
           // ← sem cifrão
    ]);
}
public function relatorioDados($id,$datainicio,$datafim){




$id=$id==100?collect(loja_desc::pluck('id')->toArray()):collect($id);
$datainicio??Carbon::now()->format('y-m-d');


if($datainicio=="null"){
  $datainicio=Carbon::now()->format('y-m-d');
}
if($datafim=="null"){
$datafim=Carbon::now()->format('y-m-d');
}


$dados=vendas:: whereIn('loja_id',$id)->whereDate("created_at",">=",$datainicio)
->whereDate("created_at","<=",$datafim)->with([
        'itens.produto.produto.categoria',
        'viaPagamento',
        'cliente',
        'usuario'
          
    ])->get();

$collec=collect();

$viaspagamentos=ViaPagamento::all();
foreach($dados as $item){

//  dd($item->itens);
  $totalIva = $item->itens->sum(function ($produto) {
        return ($produto->preco_unitario * $produto->quantidade) * ($produto->iva / 100);
    });

    $Total = $item->itens->sum(function ($produto) {
        return ($produto->subtotal);
    });


    $valorsemiva = $item->itens->sum(function ($produto) {
        return ($produto->preco_unitario* $produto->quantidade);
    });

$totalItems = $item->itens->sum(function ($produto) {
        return ($produto->quantidade);
    });


$totalvcompra = $item->itens->sum(function ($produto) {


        return ($produto->produto->preco_compra* $produto->quantidade);
    });


$collec->push((object)
[
    "Fatura"=>$item->reiboNr,
    "Via_pagamento"=>$item->viaPagamento->nome,
    "Valor_pago"=>$item->valor_pago,
    "Troco"=>$item->Troco,
    "Data"=>Carbon::parse($item->created_at)->format('d-m-Y'),
    "Horas"=>Carbon::parse($item->created_at)->format('H:m:s'),
    "Items"=>$totalItems,
    "Total"=>$Total,
    "id"=>$item->id,
    "usuario"=>$item->usuario->name,
    'detalhes'=>$item,
    "totaliva"=>$totalIva,
    "semiva"=>$valorsemiva ,
    "cliente"=>$item->cliente->nome,
    "totalcompra"=>$totalvcompra ,
    "lucro"=>$valorsemiva-$totalvcompra ,

]);
}



// dd($collec);
return response()->json($collec);


}


public function passarLoja()
{
    $armazem = Armazem::with([
        'produtositem' => function ($query) {
            $query->where('estoque', '>', 0)->with(['produto.grupo','produto.categoria',"outrosatributos"]);
        },
    ])
    ->whereHas('produtositem', function ($query) {
        $query->where('estoque', '>', 0);
    })
    ->get();
    $lojas=loja_desc::all();
    return Inertia::render('Produtos/passar-loja', [
        'armazem' => $armazem,
        'lojas'=>$lojas
    ]);
}

public function addicionarlojas(Request $request)
{
    $usuario = auth()->id();

    $colecaoDados=collect();
     $nomeloja=loja_desc::where("id",$request->loja_id)->first()->Desc;
    //  dd($request->all());

    try {

        DB::transaction(function () use ($request, $usuario,  $colecaoDados) {





            foreach ($request->itens as $Item) {



            $Item=(object)$Item;
                /*
                |--------------------------------------------------------------------------
                | 1. Procurar produto no armazém
                |--------------------------------------------------------------------------
                */
                $produto = ProdutoItems::where('id', $Item->produto_id)->with('produto.categoria')
                    ->lockForUpdate()
                    ->first();

                if (!$produto) {
                    throw new \Exception(
                        "Produto {$Item->produto_id} não encontrado no armazém."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 2. Verificar estoque
                |--------------------------------------------------------------------------
                */
                $quantidadeTransferir = (int) $Item->quantidade;

                $colecaoDados->push(['nome'=>$produto->produto->nome,
                "categoria"=>$produto->produto->categoria->nome,
                "Quantidade"=>$quantidadeTransferir,
                "precoVenda1"=>$Item->preco_venda1 ?? $produto->preco_venda1 ?? 0,
                "precoVenda2"=>$Item->preco_venda2 ?? $produto->preco_venda2 ?? 0,

                ]);

                if ($quantidadeTransferir <= 0) {
                    throw new \Exception(
                        "Quantidade inválida para o produto {$Item->produto_id}."
                    );
                }

                if ($produto->estoque < $quantidadeTransferir) {
                    throw new \Exception(
                        "Estoque insuficiente para o produto {$Item->produto_id}. " .
                        "Disponível: {$produto->estoque}, solicitado: {$quantidadeTransferir}."
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 3. Retirar quantidade do armazém
                |--------------------------------------------------------------------------
                */
                $produto->estoque -= $quantidadeTransferir;
                $produto->save();

                /*
                |--------------------------------------------------------------------------
                | 4. Procurar produto na loja
                |--------------------------------------------------------------------------
                */
                $lojaQuantidade = Loja::where('loja_id', $request->loja_id)
                    ->where('produtoitem_id', $Item->produto_id)
                    ->lockForUpdate()
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | 5. Calcular nova quantidade da loja
                |--------------------------------------------------------------------------
                */
                $quantidadeLoja = ($lojaQuantidade?->Quantidade ?? 0)
                    + $quantidadeTransferir;

                /*
                |--------------------------------------------------------------------------
                | 6. Criar ou atualizar estoque da loja
                |--------------------------------------------------------------------------
                */
                Loja::updateOrCreate(
                    [
                        'produtoitem_id' => $Item->produto_id,
                        'loja_id'        => $request->loja_id,
                    ],
                    [
                        'Quantidade' => $quantidadeLoja,
                        'user_id'    => $usuario,
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'dados'=>$colecaoDados,
            'nomeloja'=>$nomeloja,
            'dadosvindo'=>$request->all(),
            'message' => 'Produtos transferidos para a loja com sucesso.'
        ]);

    } catch (\Throwable $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 422);
    }
}
}
