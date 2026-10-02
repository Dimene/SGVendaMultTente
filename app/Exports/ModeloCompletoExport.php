<?php

namespace App\Exports;

use App\Models\Categoria;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;



class ModeloCompletoExport implements WithMultipleSheets
{


    protected $abas;

    protected $categorias;



    public function __construct(
        $abas,
        $categorias
    ) {

        $this->abas = $abas;

        $this->categorias = $categorias;

    }



    public function sheets(): array
    {

        $sheets = [];


        foreach($this->abas as $aba) {



        $categorias=Categoria::get();
        // dd($categorias);
            $sheets[] = new ModeloProdutoExport(
                $aba->nome
            );


        }


        return $sheets;

    }


}