<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class produtoitems extends Model
{
    protected $table="produtoitems";
   protected $fillable = [
        'produto_id',
        'preco_compra',
        'iva',
        'preco_venda',
        'estoque',
        'desconto',
        'compra_id',
        'Armazem_id'
    ];


    public function produtoatributos(){
        return $this->hasOne(produtos_atributos::class,'produto_item_id','id');
    }

    public function produto(){
        return $this->hasOne(produto::class,'id','produto_id');


    } public function outrosatributos(){
        return $this->hasOne(produtos_atributos::class,'produto_item_id','id');
    }

}
