<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'nome',
        'estoque_minimo',
        'grupo_id',
        'ativo',
    ];

    public function categoria()
    {
        return $this->hasOne(Categoria::class,'id','categoria_id');
    }

    public function grupo()
    {
        return $this->hasOne(gruposItem::class,'id','grupo_id');
    }
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function produtoitems(){
return $this->hasMany(produtoitems::class,'produto_id','id');
    }

}
