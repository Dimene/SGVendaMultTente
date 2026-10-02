<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


class ModeloProdutoExport implements
    FromArray,
    WithEvents,
    WithTitle
{

    /**
     * Linha onde ficam os cabeçalhos.
     */
    public const LINHA_HEADER = 3;

    /**
     * Linha onde ficam os dados de exemplo.
     */
    public const LINHA_EXEMPLO = 4;


    protected $nomeAba;


    public function __construct($nomeAba)
    {
        $this->nomeAba = $nomeAba;
    }



    /*
    |--------------------------------------------------------------------------
    | Nome da aba
    |--------------------------------------------------------------------------
    */

    public function title(): string
    {
        return substr($this->nomeAba, 0, 31);
    }



    /*
    |--------------------------------------------------------------------------
    | Cabeçalhos
    |--------------------------------------------------------------------------
    */

    public function headings(): array
    {
        return [
            'categoria',
            'Armazem',
            'Nome',
            'IVA',
            'lucro',
            'Stock',
            'Preço Compra',
            'Preço Venda Singulares',
            'Preço Venda Empresas',
            'Outros Atributos',
        ];
    }



    /*
    |--------------------------------------------------------------------------
    | Dados (vazio — escrevemos manualmente no AfterSheet)
    |--------------------------------------------------------------------------
    */

    public function array(): array
    {
        return [];
    }



    /*
    |--------------------------------------------------------------------------
    | Eventos Excel
    |--------------------------------------------------------------------------
    */

    public function registerEvents(): array
    {
        return [

            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();



                /*
                |--------------------------------------------------------------------------
                | 1) LOGOTIPO
                |--------------------------------------------------------------------------
                */

                $this->inserirLogo($sheet);



                /*
                |--------------------------------------------------------------------------
                | 2) TÍTULO
                |--------------------------------------------------------------------------
                */

                $this->inserirTitulo($sheet);



                /*
                |--------------------------------------------------------------------------
                | 3) ESCREVER CABEÇALHOS NA LINHA LINHA_HEADER
                |--------------------------------------------------------------------------
                */

                $headers = $this->headings();

                $col = 'A';
                foreach ($headers as $h) {
                    $sheet->setCellValue($col . self::LINHA_HEADER, $h);
                    $col++;
                }

                $ultimaColuna = $sheet->getHighestColumn();



                /*
                |--------------------------------------------------------------------------
                | 4) ESTILO DO CABEÇALHO
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle(
                    "A" . self::LINHA_HEADER . ":{$ultimaColuna}" . self::LINHA_HEADER
                )->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F46E5'],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical'   => 'center',
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                    ],
                ]);

                $sheet->getRowDimension(self::LINHA_HEADER)->setRowHeight(28);



                /*
                |--------------------------------------------------------------------------
                | 5) ESCREVER LINHA DE EXEMPLO (linha 4)
                |--------------------------------------------------------------------------
                */

                $exemplo = [
                    '',
                    'Produto exemplo',
                    'Cental',
                    16,
                    20,
                    10,
                    100,
                    150,
                    180,
                    'Cor:preta|tamanho:16GB|',
                ];

                $col = 'A';
                foreach ($exemplo as $valor) {
                    $sheet->setCellValue($col . self::LINHA_EXEMPLO, $valor);
                    $col++;
                }

                $sheet->getStyle(
                    "A" . self::LINHA_EXEMPLO . ":{$ultimaColuna}" . self::LINHA_EXEMPLO
                )->getFont()->setItalic(true)->getColor()->setRGB('9CA3AF');



                /*
                |--------------------------------------------------------------------------
                | 6) FREEZE — congela logo + título + cabeçalho
                |--------------------------------------------------------------------------
                */

                $sheet->freezePane('A' . (self::LINHA_HEADER + 1));



                /*
                |--------------------------------------------------------------------------
                | 7) AUTOFILTRO no cabeçalho
                |--------------------------------------------------------------------------
                */

                $sheet->setAutoFilter(
                    "A" . self::LINHA_HEADER . ":{$ultimaColuna}" . self::LINHA_HEADER
                );



                /*
                |--------------------------------------------------------------------------
                | 8) AUTO-SIZE DAS COLUNAS
                |--------------------------------------------------------------------------
                */

                foreach (range('A', $ultimaColuna) as $c) {
                    $sheet->getColumnDimension($c)->setAutoSize(true);
                }

            }

        ];
    }




    /*
    |--------------------------------------------------------------------------
    | Insere logotipo da empresa
    |--------------------------------------------------------------------------
    */

    protected function inserirLogo($sheet): void
    {
        $logoPath = $this->logoPath();

        if (!$logoPath) {
            return;
        }

        $drawing = new Drawing();
        $drawing->setName('Logotipo');
        $drawing->setDescription('Logotipo da empresa');
        $drawing->setPath($logoPath);
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setWorksheet($sheet);

        $sheet->getRowDimension(1)->setRowHeight(45);
    }




    /*
    |--------------------------------------------------------------------------
    | Título
    |--------------------------------------------------------------------------
    */

    protected function inserirTitulo($sheet): void
    {
        $empresa = \App\Models\Empresa::first();

        $nomeEmpresa = $empresa->nome ?? 'Empresa';

        $titulo = $nomeEmpresa . ' — ' . $this->nomeAba;

        $ultimaColuna = $sheet->getHighestColumn();

        $sheet->setCellValue('A2', $titulo);
        $sheet->mergeCells("A2:{$ultimaColuna}2");

        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 14,
                'color' => ['rgb' => '1F2937'],
            ],
            'alignment' => [
                'horizontal' => 'left',
                'vertical'   => 'center',
            ],
        ]);

        $sheet->getRowDimension(2)->setRowHeight(24);
    }




    /*
    |--------------------------------------------------------------------------
    | Caminho absoluto do logotipo
    |--------------------------------------------------------------------------
    */

    protected function logoPath(): ?string
    {
        $empresa = \App\Models\Empresa::first();

        if (!$empresa || empty($empresa->logo)) {
            return null;
        }

        $logo = ltrim($empresa->logo, '/');

        $storage = storage_path('app/public/' . $logo);
        if (file_exists($storage)) {
            return $storage;
        }

        $public = public_path($logo);
        if (file_exists($public)) {
            return $public;
        }

        return null;
    }
}