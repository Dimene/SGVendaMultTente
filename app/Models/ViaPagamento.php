<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViaPagamento extends Model
{
    protected $table = 'via_pagamentos';

    protected $fillable = [
        'nome',
        'descricao',
    ];

    public function vendas(): HasMany
    {
        return $this->hasMany(vendas::class, 'via_pagamento_id');
    }
}
