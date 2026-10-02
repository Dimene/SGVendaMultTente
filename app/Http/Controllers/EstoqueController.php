<?php

namespace App\Http\Controllers;

use App\Models\Armazem;
use App\Models\loja_desc;
use App\Models\Produto;
use App\Models\produtoitems;
use Inertia\Inertia;
use PHPUnit\TextUI\Configuration\Merger;

class EstoqueController extends Controller
{
   


public function dadosinventario()
{
    $produtos = Produto::with('produtoitems.produtoloja')->get();

    $armazems = Armazem::all();
    $lojas = loja_desc::all();

    $collect = collect();

    foreach ($produtos as $produto) {
$quantidade_total=0;
        $produtoItem = [
            'id' => $produto->id,
            'nome' => $produto->nome,
            'categoria' => $produto->categoria()->first()->nome,
        ];

        /*
        |--------------------------------------------------------------------------
        | ARMAZÉNS
        |--------------------------------------------------------------------------
        */
        foreach ($armazems as $armazem) {
 $qntdarmazem=$produto->produtoitems
                    ->where('Armazem_id', $armazem->id)
                    ->sum('estoque');
            $produtoItem[$armazem->id . 'armazem'] =$qntdarmazem;
               
                    $quantidade_total=$quantidade_total+ $qntdarmazem;
   $dadosarmazem=$produto->produtoitems
                    ->where('Armazem_id', $armazem->id)->first();

                    $produtoItem["preco_venda1"]= $dadosarmazem->preco_venda1;
                     $produtoItem["preco_venda2"]= $dadosarmazem->preco_venda2;
                    $produtoItem["iva_percentual"] =$dadosarmazem->iva; 
                  
    


                    
        }

        /*
        |--------------------------------------------------------------------------
        | LOJAS
        |--------------------------------------------------------------------------
        */
        foreach ($lojas as $loja) {
 
$quantidadeloja=$produto->produtoitems
                    ->flatMap(function ($produtoItem) use ($loja) {

                   
                        return ($produtoItem->produtoloja()->where('loja_id', $loja->id)->get());
                    })
                    ->sum('Quantidade');
 $produtoItem[$loja->id . 'loja'] =$quantidadeloja;

        
           
            $quantidade_total=$quantidade_total+$quantidadeloja;
           
                
        }
        
        $produtoItem["quantidadetotoal"]=$quantidade_total;
          
        ;
        

        $collect->push($produtoItem);
    }

    // dd($armazems);
    return [
        "linhas"=>$collect,
        "armazens"=>$armazems,
        "lojas"=>$lojas,
        ];
}

public function index()
    {

        return Inertia::render('Estoque/EstoqueIndex',
       $this->dadosinventario()
        );
    }
    public function visualizar()
    {

        return Inertia::render('Estoque/estoquevisualizar',
       $this->dadosinventario()
        );
    }

}