<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Models\loja_desc;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $empresa =Empresa::where("id",1)->first();
        $loja=loja_desc::all();
        $usuario=User::where("id",$request->user()?->id??0)->with("roles.permissions")->first();
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ], 'empresa' =>  $empresa,
            'usuario'=>$usuario,
            'loja'=>$loja,
        ];
    }
}
