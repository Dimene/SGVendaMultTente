<?php

namespace App\Http\Controllers;

use App\Models\vendas;
use App\Models\Produto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class homecontroller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
       
        if (auth()->user()->roles?->contains('name', 'Admin')) {
            return $this->dashabordadmin();
        }
         else {
            return $this->dashaborOperador();
        }

      
    }

    /**
     * Show the form for creating a new resource.
     */

public function dashaborOperador(){


 $agora = Carbon::now();

 $usuario= auth()->user()->id;
        $inicioAno = $agora->copy()->startOfYear();
        $fimAno = $agora->copy()->endOfYear();

        $vendas = vendas::where('usario_id',$usuario)->with([
            'itens.produto.produto.categoria',
            'viaPagamento',
            'cliente',
        ])->whereBetween('created_at', [$inicioAno, $fimAno])->get();

        $vendasMes = $vendas->filter(fn ($venda) => $venda->created_at?->isSameMonth($agora));
        $vendasHoje = $vendas->filter(fn ($venda) => $venda->created_at?->isSameDay($agora));
        $mesAnterior = $agora->copy()->subMonth();
        $vendasMesAnterior = $vendas->filter(fn ($venda) => $venda->created_at?->isSameMonth($mesAnterior));

        $total = fn ($colecao) => round($colecao->sum(fn ($venda) => (float) ($venda->total ?? 0)), 2);
        $totalMes = $total($vendasMes);
        $totalMesAnterior = $total($vendasMesAnterior);
        $crescimento = $totalMesAnterior > 0
            ? round((($totalMes - $totalMesAnterior) / $totalMesAnterior) * 100, 1)
            : ($totalMes > 0 ? 100 : 0);

        $itensMes = $vendasMes->flatMap->itens;
        $valorCompraMes = $itensMes->sum(
            fn ($item) => (float) ($item->quantidade ?? 0) * (float) ($item->produto?->preco_compra ?? 0)
        );
        $lucroMes = $itensMes->sum(
            fn ($item) => (float) ($item->subtotal ?? 0)
                - ((float) ($item->quantidade ?? 0) * (float) ($item->produto?->preco_compra ?? 0))
        );
        $ivaMes = $itensMes->sum(
            fn ($item) => (float) ($item->subtotal ?? 0) * ((float) ($item->iva ?? 0) / 100)
        );
        $vendasMensais = collect(range(1, 12))->map(
            fn ($mes) => $total($vendas->filter(fn ($venda) => $venda->created_at?->month === $mes))
        )->values();

        $inicioSemana = $agora->copy()->startOfWeek();
        $vendasSemana = collect(range(0, 6))->map(function ($dia) use ($vendas, $inicioSemana, $total) {
            $data = $inicioSemana->copy()->addDays($dia);
            return $total($vendas->filter(fn ($venda) => $venda->created_at?->isSameDay($data)));
        })->values();

        $agruparPercentuais = function ($itens, $nome, $valor) {
            $agrupado = $itens->groupBy($nome)->map(fn ($grupo) => $grupo->sum($valor));
            $totalAgrupado = $agrupado->sum();

            return $agrupado->map(fn ($valorGrupo, $nomeGrupo) => [
                'nome' => $nomeGrupo ?: 'Sem classificação',
                'valor' => $totalAgrupado > 0 ? round(($valorGrupo / $totalAgrupado) * 100, 1) : 0,
            ])->values();
        };

        $dadosDashboard = [
            'vendasHoje' => round($total($vendasHoje), 2),
            'vendasMes' => $totalMes,
            'crescimentoMes' => $crescimento,
            'lucro' => round($lucroMes, 2),
            'iva' => round($ivaMes, 2),
            'caixa' => round($vendasMes->sum(fn ($venda) => (float) ($venda->valor_pago ?? 0)), 2),
            'vendasMensais' => $vendasMensais,
            'vendasSemana' => $vendasSemana,
            'categorias' => $agruparPercentuais(
                $itensMes,
                fn ($item) => $item->produto?->produto?->categoria?->nome ?? 'Sem categoria',
                fn ($item) => (float) ($item->subtotal ?? 0)
            ),
            'pagamentos' => $agruparPercentuais(
                $vendasMes,
                fn ($venda) => $venda->viaPagamento?->nome ?? 'Sem pagamento',
                fn ($venda) => (float) ($venda->total ?? 0)
            ),
            'ultimasVendas' => $vendas->sortByDesc('created_at')->take(5)->map(fn ($venda) => [
                'fatura' => $venda->reiboNr,
                'cliente' => $venda->cliente?->nome ?? 'Consumidor final',
                'valor' => (float) ($venda->total ?? 0),
                'estado' => (float) ($venda->valor_pago ?? 0) >= (float) ($venda->total ?? 0) ? 'Pago' : 'Pendente',
                'data' => $venda->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'clientesTop' => $vendasMes->groupBy('cliente_id')->map(function ($grupo) {
                return [
                    'nome' => $grupo->first()->cliente?->nome ?? 'Consumidor final',
                    'compras' => $grupo->count(),
                    'total' => round($grupo->sum(fn ($venda) => (float) ($venda->total ?? 0)), 2),
                ];
            })->sortByDesc('total')->take(5)->values(),
        ];

        $produtos = Produto::with(['produtoitems'])
            ->where('ativo', 1)
            ->get()
            ->map(function ($produto) {
                $quantidade = $produto->produtoitems->sum(fn ($item) => (int) ($item->estoque ?? 0));
                return [
                    'produto' => $produto->nome,
                    'quantidade' => $quantidade,
                    'minimo' => (int) ($produto->estoque_minimo ?? 0),
                ];
            });

        $dadosDashboard['produtosEmStock'] = $produtos->sum('quantidade');
        $produtosBaixoStock = $produtos
            ->filter(fn ($produto) => $produto['quantidade'] <= $produto['minimo'])
            ->sortBy('quantidade')
            ->take(5)
            ->values();

        $dadosDashboard['produtosBaixoStock'] = $produtosBaixoStock;

        return Inertia::render('dashabordoperador', [
            'dashboardData'=>$dadosDashboard
           
        ]);
}

    public function dashabordadmin(){
  $agora = Carbon::now();
        $inicioAno = $agora->copy()->startOfYear();
        $fimAno = $agora->copy()->endOfYear();

        $vendas = vendas::with([
            'itens.produto.produto.categoria',
            'viaPagamento',
            'cliente',
        ])->whereBetween('created_at', [$inicioAno, $fimAno])->get();

        $vendasMes = $vendas->filter(fn ($venda) => $venda->created_at?->isSameMonth($agora));
        $vendasHoje = $vendas->filter(fn ($venda) => $venda->created_at?->isSameDay($agora));
        $mesAnterior = $agora->copy()->subMonth();
        $vendasMesAnterior = $vendas->filter(fn ($venda) => $venda->created_at?->isSameMonth($mesAnterior));

        $total = fn ($colecao) => round($colecao->sum(fn ($venda) => (float) ($venda->total ?? 0)), 2);
        $totalMes = $total($vendasMes);
        $totalMesAnterior = $total($vendasMesAnterior);
        $crescimento = $totalMesAnterior > 0
            ? round((($totalMes - $totalMesAnterior) / $totalMesAnterior) * 100, 1)
            : ($totalMes > 0 ? 100 : 0);

        $itensMes = $vendasMes->flatMap->itens;
        $valorCompraMes = $itensMes->sum(
            fn ($item) => (float) ($item->quantidade ?? 0) * (float) ($item->produto?->preco_compra ?? 0)
        );
        $lucroMes = $itensMes->sum(
            fn ($item) => (float) ($item->subtotal ?? 0)
                - ((float) ($item->quantidade ?? 0) * (float) ($item->produto?->preco_compra ?? 0))
        );
        $ivaMes = $itensMes->sum(
            fn ($item) => (float) ($item->subtotal ?? 0) * ((float) ($item->iva ?? 0) / 100)
        );
        $vendasMensais = collect(range(1, 12))->map(
            fn ($mes) => $total($vendas->filter(fn ($venda) => $venda->created_at?->month === $mes))
        )->values();

        $inicioSemana = $agora->copy()->startOfWeek();
        $vendasSemana = collect(range(0, 6))->map(function ($dia) use ($vendas, $inicioSemana, $total) {
            $data = $inicioSemana->copy()->addDays($dia);
            return $total($vendas->filter(fn ($venda) => $venda->created_at?->isSameDay($data)));
        })->values();

        $agruparPercentuais = function ($itens, $nome, $valor) {
            $agrupado = $itens->groupBy($nome)->map(fn ($grupo) => $grupo->sum($valor));
            $totalAgrupado = $agrupado->sum();

            return $agrupado->map(fn ($valorGrupo, $nomeGrupo) => [
                'nome' => $nomeGrupo ?: 'Sem classificação',
                'valor' => $totalAgrupado > 0 ? round(($valorGrupo / $totalAgrupado) * 100, 1) : 0,
            ])->values();
        };

        $dadosDashboard = [
            'vendasHoje' => round($total($vendasHoje), 2),
            'vendasMes' => $totalMes,
            'crescimentoMes' => $crescimento,
            'lucro' => round($lucroMes, 2),
            'iva' => round($ivaMes, 2),
            'caixa' => round($vendasMes->sum(fn ($venda) => (float) ($venda->valor_pago ?? 0)), 2),
            'vendasMensais' => $vendasMensais,
            'vendasSemana' => $vendasSemana,
            'categorias' => $agruparPercentuais(
                $itensMes,
                fn ($item) => $item->produto?->produto?->categoria?->nome ?? 'Sem categoria',
                fn ($item) => (float) ($item->subtotal ?? 0)
            ),
            'pagamentos' => $agruparPercentuais(
                $vendasMes,
                fn ($venda) => $venda->viaPagamento?->nome ?? 'Sem pagamento',
                fn ($venda) => (float) ($venda->total ?? 0)
            ),
            'ultimasVendas' => $vendas->sortByDesc('created_at')->take(5)->map(fn ($venda) => [
                'fatura' => $venda->reiboNr,
                'cliente' => $venda->cliente?->nome ?? 'Consumidor final',
                'valor' => (float) ($venda->total ?? 0),
                'estado' => (float) ($venda->valor_pago ?? 0) >= (float) ($venda->total ?? 0) ? 'Pago' : 'Pendente',
                'data' => $venda->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'clientesTop' => $vendasMes->groupBy('cliente_id')->map(function ($grupo) {
                return [
                    'nome' => $grupo->first()->cliente?->nome ?? 'Consumidor final',
                    'compras' => $grupo->count(),
                    'total' => round($grupo->sum(fn ($venda) => (float) ($venda->total ?? 0)), 2),
                ];
            })->sortByDesc('total')->take(5)->values(),
        ];

        $produtos = Produto::with(['produtoitems'])
            ->where('ativo', 1)
            ->get()
            ->map(function ($produto) {
                $quantidade = $produto->produtoitems->sum(fn ($item) => (int) ($item->estoque ?? 0));
                return [
                    'produto' => $produto->nome,
                    'quantidade' => $quantidade,
                    'minimo' => (int) ($produto->estoque_minimo ?? 0),
                ];
            });

        $dadosDashboard['produtosEmStock'] = $produtos->sum('quantidade');
        $produtosBaixoStock = $produtos
            ->filter(fn ($produto) => $produto['quantidade'] <= $produto['minimo'])
            ->sortBy('quantidade')
            ->take(5)
            ->values();

        $dadosDashboard['produtosBaixoStock'] = $produtosBaixoStock;

        return Inertia::render('Dashboard', [
            'dashboardData' => $dadosDashboard,
        ]);
    }


    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
