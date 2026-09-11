<?php

namespace App\Exports;

use App\Models\Categoria;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;

class ListasExport implements
    FromCollection,
    WithTitle
{

    public function collection()
    {

        $lista = collect();


        // CATEGORIAS

        $lista->push([
            "=== CATEGORIA ==="
        ]);


        $categorias = Categoria::all();


        foreach ($categorias as $categoria) {

            $lista->push([
                $categoria->nome
            ]);

        }



        // IVA

        $lista->push([
            "=== IVA ==="
        ]);

        $lista->push([0]);
        $lista->push([16]);
        $lista->push([17]);



        // LUCRO

        $lista->push([
            "=== LUCRO ==="
        ]);

        $lista->push([5]);
        $lista->push([10]);
        $lista->push([20]);
        $lista->push([30]);



        return $lista;

    }



    public function title(): string
    {
        return "Listas";
    }

}
