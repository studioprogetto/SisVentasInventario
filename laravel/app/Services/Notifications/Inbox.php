<?php

namespace App\Services\Notifications;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class Inbox
{
    public function mine(Usuario $user)
    {
        return DB::table('avisos_usuario')->where('usuario',$user->getKey());
    }

    public function sync(Usuario $user): void
    {
        $this->mine($user)->where('eliminado','<=',now()->subDays(30))->delete();
        foreach (app(NotificationFeed::class)->forUser($user) as $item) {
            $this->send($user->getKey(), $item['key'] ?? hash('sha256', $item['title'].$item['message'].today()->format('Y-m')), $item['title'], $item['message'], $item['url']);
        }
    }

    public function send(int $user, string $key, string $title, string $message, ?string $url = null): void
    {
        DB::table('avisos_usuario')->insertOrIgnore([
            'usuario'=>$user,'clave'=>$key,'titulo'=>$title,'mensaje'=>$message,'url'=>$url,'created_at'=>now(),
        ]);
    }

    public function admins(string $key, string $title, string $message): void
    {
        $ids = Usuario::where('activo',true)->whereHas('rol',fn($q)=>$q->where('nombre_rol','Administrador'))->pluck('id_usuario');
        foreach ($ids as $id) $this->send($id,$key,$title,$message);
    }

    public function unread(Usuario $user): int
    {
        $this->sync($user);
        return $this->mine($user)->whereNull('leido')->whereNull('eliminado')->count();
    }
}
