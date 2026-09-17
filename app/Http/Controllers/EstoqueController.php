<?php

namespace App\Http\Controllers;

use App\Models\produtoitems;
use Inertia\Inertia;

class EstoqueController extends Controller
{
    public function index()
    {
        $inventario = produtoitems::query()
            ->with(['produto.categoria', 'produtoloja.loja'])
            ->get()
            ->map(function (produtoitems $item) {
                $armazem = (int) ($item->estoque ?? 0);
                $loja = (int) ($item->produtoloja?->Quantidade ?? 0);
                $quantidadeTotal = $armazem + $loja;
                $precoVenda1 = (float) ($item->preco_venda1 ?? 0);
                $precoVenda2 = (float) ($item->preco_venda2 ?? 0);
                $iva = (float) ($item->iva ?? 0);

                $receitaVenda1 = $quantidadeTotal * $precoVenda1;
                $receitaVenda2 = $quantidadeTotal * $precoVenda2;

                return [
                    'id' => $item->id,
                    'produto' => $item->produto?->nome ?? 'Produto sem nome',
                    'categoria' => $item->produto?->categoria?->nome ?? 'Sem categoria',
                    'armazem' => $armazem,
                    'loja' => $loja,
                    'quantidade_total' => $quantidadeTotal,
                    'preco_venda1' => $precoVenda1,
                    'preco_venda2' => $precoVenda2,
                    'iva_percentual' => $iva,
                    'receita_venda1' => round($receitaVenda1, 2),
                    'iva_venda1' => round($receitaVenda1 * $iva / 100, 2),
                    'receita_venda2' => round($receitaVenda2, 2),
                    'iva_venda2' => round($receitaVenda2 * $iva / 100, 2),
                    'loja_nome' => $item->produtoloja?->loja?->Desc,
                ];
            })
            ->filter(fn (array $item) => $item['quantidade_total'] > 0)
            ->values();

        return Inertia::render('Estoque/EstoqueIndex', [
            'inventario' => $inventario,
        ]);
    }
}