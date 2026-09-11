<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;

class CompraItem extends Model
{
    protected $fillable = [
        'compra_id',
        'produto_id',
        'lote_id',
        'quantidade',
        'quantidade_recebida',
        'preco_unitario',
        'desconto',
        'subtotal',
        'imposto',
        'total',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function produto()
    {
        return $this->belongsTo(produto::class);
    }
}
