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
        'preco_venda1',
        'preco_venda2',
        'venda_1_iva',
        'venda_2_iva',
        'estoque',
        'desconto',
        'compra_id',
        'Armazem_id'
    ];


    public function produtoatributos(){
        return $this->hasOne(produtos_atributos::class,'produto_item_id','id');
    }
  public function produtoloja(){
        return $this->hasOne(loja::class,'produtoitem_id','id');
    }
    public function produto(){
        return $this->hasOne(produto::class,'id','produto_id');


    } public function outrosatributos(){
        return $this->hasOne(produtos_atributos::class,'produto_item_id','id');
    }

}
