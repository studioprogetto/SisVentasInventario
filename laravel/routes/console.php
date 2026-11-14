<?php

// Business maintenance commands live in App\Console\Commands.
\Illuminate\Support\Facades\Schedule::call(fn () => \Illuminate\Support\Facades\DB::table('avisos_usuario')->where('eliminado','<=',now()->subDays(30))->delete())->daily();
