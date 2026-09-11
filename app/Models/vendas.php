<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class vendas extends Model
{
    use SoftDeletes;

    protected $table = 'vendas';

    protected $fillable = [
        'via_pagamento_id',
        'valor_pago',
        'troco',
        'usario_id',
        'reiboNr',
        'total'
    ];

    public function itens()
    {
        return $this->hasMany(VendaItem::class, 'venda_id');
    }

    public function usuario()
    {
        return $this->hasOne(User::class, 'id','usario_id');
    }

    public function viaPagamento()
    {
        return $this->hasOne(ViaPagamento::class,'id','via_pagamento_id');
    }
}
