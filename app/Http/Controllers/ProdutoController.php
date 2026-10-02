<?php
namespace App\Http\Controllers;

use App\Exports\ModeloCompletoExport;
use App\Exports\ModeloProdutoExport;
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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;


class ProdutoController extends Controller
{
    public function index()
    {
        return Produto::latest()->get();
    }


public function store(Request $request)
{


//  dd($request->all());

//   return response()->json($request->all());

    if (!$request->items) {
        return response()->json([
            'ok' => false,
            'message' => 'Nenhum item recebido'
        ], 422);
    }

    foreach ($request->items as $Item) {

        $grupo = gruposItem::where("nome", $Item["grupo"] ?? null)->with('listaatributo')->first();
         

// dd($grupo);

        if (!$grupo) {
            return response()->json([
                'ok' => false,
                'message' => 'Grupo não encontrado: ' . ($Item["grupo"] ?? 'null')
            ], 422);
        }



$categoria="";
      if (!is_int($Item["categoria"])){
      $categoria = Categoria::updateOrCreate(
    // 1º argumento: como identificar a categoria (chave única)
    [
        'nome'     => $Item["categoria"],
        'tipo'     => 'PRODUTO',
        'grupo_id' => $grupo['id'],
    ],
    // 2º argumento: valores a criar/atualizar
    [
        'ativo' => 1,
    ]
);
 $Item["categoria"]=$categoria->id;
        }
       
      
    //   dd($Item["categoria"],$categoria);

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
        'iva' =>$Item['IVA'],
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

    // ==========================================
    // COMPRA NÃO ENCONTRADA
    // ==========================================

    if (!$compra) {
        return response()->json([
            'dados' => []
        ]);
    }

    // ==========================================
    // COLEÇÃO FINAL
    // ==========================================

    $collecao = collect();

    // ==========================================
    // PERCORRER OS PRODUTOS DA COMPRA
    // ==========================================

    foreach ($compra->produtosItem as $item) {

        // ==========================================
        // PRODUTO
        // ==========================================

        $produto = $item->produto;

        // ==========================================
        // CATEGORIA
        // ==========================================

        $categoria = $produto?->categoria;

        // ==========================================
        // GRUPO
        // ==========================================

        $grupo = $categoria?->grupo;

        // ==========================================
        // ATRIBUTOS DO GRUPO
        // ==========================================

        $grupoAtributos = $grupo?->listaatributo ?? collect();

        // ==========================================
        // ATRIBUTOS DA CATEGORIA
        // ==========================================

        $categoriaAtributos = $categoria?->atributos ?? collect();

        // ==========================================
        // OUTROS ATRIBUTOS GUARDADOS
        // ==========================================

        $dadosAtributos = json_decode(
            $item->outrosatributos?->outrosAtributos ?? '{}',
            true
        ) ?? [];

        // ==========================================
        // FOTOS
        // ==========================================

        $fotos = json_decode(
            $item->outrosatributos?->fotos ?? '[]',
            true
        ) ?? [];

        // Garantir array
        if (!is_array($fotos)) {
            $fotos = [];
        }

        // ==========================================
        // ARMAZÉM
        // ==========================================

        $armazem = $item->Armazem_id
            ? Armazem::find($item->Armazem_id)
            : null;

        // ==========================================
        // CAMPOS QUE JÁ SÃO COLUNAS DA TABELA
        // ==========================================

        $camposTabela = [
            'id',
            'foto',
            'fotos',

            'categoria',
            'Nome',
            'grupo',
            'armazem',

            'IVA',
            'iva',
            'lucro',

            'Desconto (%)',

            'Stock',

            'Preço Compra',
            'Preço Venda',

            'Preço Venda cliente 1',
            'Preço Venda cliente 2',

            'Venda com IVA cliente 1',
            'Venda com IVA cliente 2',

            'outros_Atributos',
        ];

        // ==========================================
        // ATRIBUTOS DO GRUPO
        // ==========================================

        foreach ($grupoAtributos as $atributo) {

            /*
             * Preferimos "nome" como identificador técnico.
             * Se não existir, usamos "Descricao".
             */
            $nomeAtributo =
                $atributo->nome
                ?? $atributo->Descricao
                ?? null;

            if (!$nomeAtributo) {
                continue;
            }

            /*
             * Registrar como campo conhecido da tabela.
             */
            $camposTabela[] = $nomeAtributo;

        }

        // ==========================================
        // ATRIBUTOS DA CATEGORIA
        // ==========================================

        foreach ($categoriaAtributos as $atributo) {

            $nomeAtributo =
                $atributo->nome
                ?? $atributo->Descricao
                ?? null;

            if (!$nomeAtributo) {
                continue;
            }

            /*
             * Registrar como campo conhecido.
             */
            $camposTabela[] = $nomeAtributo;
        }

        // ==========================================
        // REMOVER DUPLICADOS
        // ==========================================

        $camposTabela = array_unique($camposTabela);

        // ==========================================
        // LINHA PRINCIPAL DO PRODUTO
        // ==========================================

        $linhaProduto = [

            // --------------------------------------
            // IDENTIFICAÇÃO
            // --------------------------------------

            'id' => $item->id,

            // --------------------------------------
            // CATEGORIA
            // --------------------------------------

            'categoria' => $categoria?->id ?? null,

            // --------------------------------------
            // NOME
            // --------------------------------------

            'Nome' => $produto?->nome ?? '',

            // --------------------------------------
            // GRUPO
            // --------------------------------------

            'grupo' => $grupo?->nome ?? 'Sem grupo',

            // --------------------------------------
            // ARMAZÉM
            // --------------------------------------

            'armazem' =>
                $armazem?->Descricao
                ?? $item->Armazem_id
                ?? '',

            // --------------------------------------
            // IVA
            // --------------------------------------

            'IVA' => (float) ($item->iva ?? 0),

            'iva' => (float) ($item->iva ?? 0),

            // --------------------------------------
            // LUCRO
            // --------------------------------------

            'lucro' => (float) ($item->lucro ?? 0),

            // --------------------------------------
            // PREÇO DE COMPRA
            // --------------------------------------

            'Preço Compra' =>
                (float) ($item->preco_compra ?? 0),

            // --------------------------------------
            // PREÇO VENDA CLIENTE 1
            // --------------------------------------

            'Preço Venda cliente 1' =>
                (float) ($item->preco_venda1 ?? 0),

            // --------------------------------------
            // PREÇO VENDA CLIENTE 2
            // --------------------------------------

            'Preço Venda cliente 2' =>
                (float) ($item->preco_venda2 ?? 0),

            // --------------------------------------
            // DESCONTO
            // --------------------------------------

            'Desconto (%)' =>
                (float) ($item->desconto ?? 0),

            // --------------------------------------
            // VENDA COM IVA CLIENTE 1
            // --------------------------------------

            'Venda com IVA cliente 1' =>
                round(
                    (float) ($item->preco_venda1 ?? 0)
                    * (float) ($item->iva ?? 1),
                    2
                ),

            // --------------------------------------
            // VENDA COM IVA CLIENTE 2
            // --------------------------------------

            'Venda com IVA cliente 2' =>
                round(
                    (float) ($item->preco_venda2 ?? 0)
                    * (float) ($item->iva ?? 1),
                    2
                ),

            // --------------------------------------
            // STOCK
            // --------------------------------------

            'Stock' =>
                (int) ($item->estoque ?? 0),

            // --------------------------------------
            // FOTOS
            // --------------------------------------

            'fotos' => $fotos,

        ];

        // ==========================================
        // COLOCAR ATRIBUTOS CONHECIDOS COMO
        // CAMPOS NORMAIS DA LINHA
        // ==========================================

        foreach ($dadosAtributos as $key => $valor) {

            // Ignorar vazio
            if (
                $valor === null ||
                $valor === ''
            ) {
                continue;
            }

            /*
             * Verificar se este atributo pertence
             * às colunas configuradas.
             */
            if (in_array($key, $camposTabela, true)) {

                $linhaProduto[$key] =
                    is_numeric($valor)
                        ? (float) $valor
                        : $valor;
            }
        }

        // ==========================================
        // FORMAR "OUTROS ATRIBUTOS"
        // ==========================================

        $outrosAtributos = [];

        foreach ($dadosAtributos as $key => $valor) {

            // --------------------------------------
            // IGNORAR VAZIOS
            // --------------------------------------

            if (
                $valor === null ||
                $valor === ''
            ) {
                continue;
            }

            // --------------------------------------
            // SE JÁ É COLUNA, NÃO VAI PARA OUTROS
            // --------------------------------------

            if (in_array($key, $camposTabela, true)) {
                continue;
            }

            // --------------------------------------
            // É REALMENTE UM OUTRO ATRIBUTO
            // --------------------------------------

            if (is_array($valor)) {

                $valor = implode(
                    ', ',
                    array_map(
                        fn ($v) => is_scalar($v)
                            ? (string) $v
                            : json_encode($v),
                        $valor
                    )
                );
            }

            $outrosAtributos[] =
                $key . ': ' . $valor;
        }

        // ==========================================
        // GUARDAR OUTROS ATRIBUTOS
        // ==========================================

        $linhaProduto['outros_Atributos'] =
            !empty($outrosAtributos)
                ? implode(' | ', $outrosAtributos)
                : '';

        // ==========================================
        // ADICIONAR À COLEÇÃO
        // ==========================================

        $collecao->push(
            (object) $linhaProduto
        );
    }

    // ==========================================
    // RETORNAR PARA O VUE
    // ==========================================

    return response()->json([
        'dados' => $collecao
    ]);
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
        foreach($outros_Atrib->foto as $foto){
            Storage::delete($foto);
        }

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

 // app/Http/Controllers/ProdutoController.php

public function gerarModeloImport($grupo)
{
    // // Buscar grupo + atributos dinâmicos
    // $grupoItem = \App\Models\gruposItem::with('listaatributo')
    //     ->where('nome', $grupo)
    //     ->firstOrFail();

    // // ----- Cabeçalhos FIXOS -----
    // $headers = [
    //     'categoria',
    //     'Nome',
    //     'IVA',
    //     'lucro',
    //     'Desconto (%)',
    //     'Stock',
    //     'Preço Compra',
    //     'Preço Venda cliente 1',
    //     'Preço Venda cliente 2',
    //     'Venda com IVA cliente 1',
    //     'Venda com IVA cliente 2',
    //     'armazem',           // descrição ou ID do armazém
    //     'outros_Atributos',
    // ];

    // // ----- Atributos dinâmicos (intercalados antes dos fixos de preço) -----
    // $atributosDinamicos = $grupoItem->listaatributo->pluck('Descricao')->toArray();

    // // Inserir atributos dinâmicos logo depois de "Desconto (%)"
    // $posInsercao = array_search('Desconto (%)', $headers) + 1;
    // array_splice($headers, $posInsercao, 0, $atributosDinamicos);

    // // ----- Criar ficheiro -----
    // $spreadsheet = new Spreadsheet();
    // $sheet = $spreadsheet->getActiveSheet();
    // $sheet->setTitle('Modelo ' . $grupo);

    // // Linha 1: cabeçalhos
    // $col = 'A';
    // foreach ($headers as $h) {
    //     $sheet->setCellValue($col . '1', $h);
    //     $col++;
    // }

    // // Estilo do cabeçalho
    // $ultimaCol = chr(ord('A') + count($headers) - 1);
    // $sheet->getStyle("A1:{$ultimaCol}1")->applyFromArray([
    //     'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    //     'fill' => [
    //         'fillType' => Fill::FILL_SOLID,
    //         'startColor' => ['rgb' => '4F46E5'],
    //     ],
    //     'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
    //     'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    // ]);
    // $sheet->getRowDimension(1)->setRowHeight(28);
    // $sheet->freezePane('A2');

    // // Linha 2: exemplo (opcional — ajuda o utilizador)
    // $exemplo = [
    //     'categoria'                => '1',       // ID da categoria
    //     'Nome'                     => 'Parafuso M8',
    //     'IVA'                      => 16,
    //     'lucro'                    => 20,
    //     'Desconto (%)'             => 0,
    //     'Stock'                    => 100,
    //     'Preço Compra'             => 50.00,
    //     'Preço Venda cliente 1'    => 90.00,
    //     'Preço Venda cliente 2'    => 85.00,
    //     'Venda com IVA cliente 1'  => 104.40,
    //     'Venda com IVA cliente 2'  => 98.60,
    //     'armazem'                  => 'Armazém Central',
    //     'outros_Atributos'         => 'Cor: Prateado | Material: Aço',
    // ];
    // foreach ($atributosDinamicos as $atrib) {
    //     $exemplo[$atrib] = ''; // deixa vazio no exemplo
    // }

    // $col = 'A';
    // foreach ($headers as $h) {
    //     $sheet->setCellValue($col . '2', $exemplo[$h] ?? '');
    //     $col++;
    // }
    // $sheet->getStyle("A2:{$ultimaCol}2")->getFont()->setItalic(true)->getColor()->setRGB('9CA3AF');

    // // Auto-ajustar largura
    // foreach (range('A', $ultimaCol) as $c) {
    //     $sheet->getColumnDimension($c)->setAutoSize(true);
    // }

    // // ----- Stream da resposta -----
    // $nome = 'Modelo_' . preg_replace('/\s+/', '_', $grupo) . '.xlsx';

    // return new StreamedResponse(function () use ($spreadsheet) {
    //     $writer = new Xlsx($spreadsheet);
    //     $writer->save('php://output');
    // }, 200, [
    //     'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    //     'Content-Disposition' => 'attachment; filename="' . $nome . '"',
    //     'Cache-Control'       => 'max-age=0',
    // ]);

    //  $nome = 'Modelo_' .
    //     preg_replace('/\s+/', '_', $grupo) .
    //     '.xlsx';


    //   return Excel::download(
    //     new ModeloProdutoExport($grupo),
    //     $nome
    // );


    $abas = [

        'eletronicos',
        'P.A'

    ];

    $abas=gruposItem::all();



    $categorias = [

        'acessores',
        'processadores'

    ];




    return Excel::download(

        new ModeloCompletoExport(
            $abas,
            $categorias
        ),

        'modelo_produtos.xlsx'

    );
}
}
