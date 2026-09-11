<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class loja extends Model
{
    use SoftDeletes;

    protected $table = 'loja';

    protected $fillable = [
        'produtoitem_id',
        'Quantidade',
        'loja_id',
        'user_id',
    ];

    protected $casts = [
        'produtoitem_id' => 'integer',
        'Quantidade'     => 'integer',
        'loja_id'        => 'integer',
        'user_id'        => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    public function produtoItem()
    {
        return $this->belongsTo(produtoitems::class, 'produtoitem_id');
    }

    public function loja()
    {
        return $this->belongsTo(loja_desc::class, 'loja_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
