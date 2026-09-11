<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $fillable = [
        'empresa_id',
        'fornecedor_id',
        'usuario_id',
        'numero_pedido',
        'data_compra',
        'observacoes',
        'status',
        'tipo_id',


    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function tipo()
    {
        return $this->hasOne(Compratipos::class,'id','tipo_id');
    }

    public function despesas()
    {
        return $this->hasMany(despeas::class,'compra_id','id');
    }

    public function produtosItem()
    {
        return $this->hasMany(produtoitems::class,'compra_id','id');
    }


}
