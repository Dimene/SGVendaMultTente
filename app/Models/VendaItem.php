<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendaItem extends Model
{
    protected $table = 'vendas_itens';

    protected $fillable = [
        'venda_id',
        'produto_id',
        'quantidade',
        'iva',
        'loja_id',
        'preco_unitario',
        'desconto',
        'subtotal',
    ];

    public function venda()
    {
        return $this->belongsTo(vendas::class, 'venda_id');
    }

    public function produto()
    {
        return $this->belongsTo(produtoitems::class, 'produto_id');
    }

    public function produtoloja(){
        return $this->hasOne(loja::class,'produtoitem_id','produto_id');
    }
}
