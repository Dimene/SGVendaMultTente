<?php

namespace App\Exports;

use App\Models\Categoria;
use App\Models\gruposItem;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdutosPorGrupoExport implements
    WithHeadings,
    WithTitle,
    WithEvents,
    WithCustomStartCell
{
    protected $grupoId;
    protected $nomeAba;
    protected $categorias;
    protected $atributosExtras;
    protected $totalColunas;

    private const MAX_ROWS = 1000;
    private const LINHA_CABECALHO = 5;

    public function __construct($grupoId, $nomeAba)
    {
        $this->grupoId = $grupoId;
        $this->nomeAba = $this->sanitizeSheetName($nomeAba);

        $this->categorias = Categoria::where('grupo_id', $grupoId)->get();

        $grupo = gruposItem::with('listaatributo')->find($grupoId);
        $this->atributosExtras = $grupo->listaatributo ?? collect();

        $this->totalColunas = count($this->headings());
    }

    public function headings(): array
    {
        $colunas = [
            'categoria',
            'Nome',
            'Iva',
            'lucro',
            'Preço',
            'Stock',
            'preco de Compra/unidade',
            'preco de venda/unidade',
            'preco de venda/unidade com iva',
            'outros atributos'
        ];

        foreach ($this->atributosExtras as $item) {
            $colunas[] = $item->Descricao;
        }

        return $colunas;
    }

    public function title(): string
    {
        return $this->nomeAba;
    }

    public function startCell(): string
    {
        return 'A' . self::LINHA_CABECALHO;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $ultimaColuna = Coordinate::stringFromColumnIndex($this->totalColunas);

                // Título
                $sheet->setCellValue('A2', 'Planilha de importação');
                $sheet->mergeCells("A2:{$ultimaColuna}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'alignment' => ['horizontal' => 'center'],
                    'font' => ['bold' => true, 'size' => 14]
                ]);

                // IVA e LUCRO informativos
                $sheet->setCellValue('A3', 'IVA');
                $sheet->setCellValue('B3', 16);
                $sheet->setCellValue('A4', 'LUCRO');
                $sheet->setCellValue('B4', 25);
                $sheet->getStyle('A3:B4')->getFont()->setBold(true);

                // Aplicar validações (dropdowns) – referenciam a aba "Listas"
                $this->aplicarValidacoes($sheet);

                // Largura automática
                for ($i = 1; $i <= $this->totalColunas; $i++) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Bordas
                $sheet->getStyle("A" . self::LINHA_CABECALHO . ":{$ultimaColuna}15")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Cabeçalho em negrito
                $sheet->getStyle("A" . self::LINHA_CABECALHO . ":{$ultimaColuna}" . self::LINHA_CABECALHO)
                    ->getFont()->setBold(true);
            }
        ];
    }

    /**
     * Aplica validações (dropdowns) usando a aba "Listas"
     */
  private function aplicarValidacoes(Worksheet $sheet)
{

    $linhaInicio = self::LINHA_CABECALHO + 1;


    $mapeamento = [

        'A' => 'categoria',

        'C' => 'iva',

        'D' => 'lucro',

    ];


    foreach ($mapeamento as $coluna => $lista) {


        for ($linha = $linhaInicio; $linha <= self::MAX_ROWS; $linha++) {


            $this->criarValidacaoLista(

                $sheet,

                $coluna . $linha,

                $lista

            );


        }


    }

}

private function criarValidacaoLista(
    Worksheet $sheet,
    string $cell,
    string $listaNome
)
{


    $spreadsheet = $sheet->getParent();


    if (!$spreadsheet->sheetNameExists('Listas')) {
        return;
    }



    $listasSheet = $spreadsheet->getSheetByName('Listas');


    $linhaInicio = $this->encontrarLinhaLista(
        $listasSheet,
        $listaNome
    );


    if ($linhaInicio == 0) {
        return;
    }



    $linhaFim = $linhaInicio;


    $ultimaLinha = $listasSheet->getHighestRow();



    for ($i = $linhaInicio + 1; $i <= $ultimaLinha; $i++) {


        $valor = $listasSheet
            ->getCell('A'.$i)
            ->getValue();



        if (
            is_string($valor) &&
            str_starts_with($valor,'===')
        ) {

            break;

        }


        $linhaFim = $i;

    }



    $validation = $sheet
        ->getCell($cell)
        ->getDataValidation();



    $validation
        ->setType(DataValidation::TYPE_LIST)
        ->setErrorStyle(DataValidation::STYLE_STOP)
        ->setAllowBlank(true)
        ->setShowInputMessage(true)
        ->setShowErrorMessage(true)
        ->setShowDropDown(true)

        // CORRIGIDO AQUI
        ->setFormula1(
            "='Listas'!\$A\${$linhaInicio}:\$A\${$linhaFim}"
        );


}

    private function encontrarLinhaLista(Worksheet $listasSheet, string $nomeLista): int
    {
        $highestRow = $listasSheet->getHighestRow();
        $procura = "=== " . strtoupper($nomeLista) . " ===";

        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $listasSheet->getCell('A' . $row)->getValue();
            if (is_string($cellValue) && trim($cellValue) === $procura) {
                return $row + 1;
            }
        }
        return 0;
    }

    private function sanitizeSheetName($nome)
    {
        $nome = preg_replace('/[:\/\\\\?*\[\]]/', '', $nome);
        return substr($nome, 0, 31);
    }
}
