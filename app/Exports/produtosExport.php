<?php

namespace App\Exports;

use App\Models\gruposItem;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class produtosExport implements WithMultipleSheets
{

    public function sheets(): array
    {
        $sheets = [];

        // Aba auxiliar dos dropdowns
        $sheets[] = new ListasExport();


        // Criar uma aba para cada grupo
        $grupos = gruposItem::all();

        foreach ($grupos as $grupo) {

            $sheets[] = new ProdutosPorGrupoExport(
                $grupo->id,
                $grupo->nome
            );

        }


        return $sheets;
    }
}
