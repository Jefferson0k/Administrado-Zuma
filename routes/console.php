<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;   // ⬅️ add
use Illuminate\Support\Facades\Log;        // ⬅️ add
use App\Models\Post;   

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// ⬇️ register the auto-publish job
Schedule::call(function () {
    $now = now();

    Log::info('[auto-publish] tick', ['now' => $now->toDateTimeString()]);

    Post::where('state_id', 1)
        ->whereNotNull('fecha_programada')
        ->where('fecha_programada', '<=', $now)
        ->orderBy('id')
        ->chunkById(100, function ($posts) use ($now) {
            foreach ($posts as $p) {
                // set published state and publish time (once)
                if (empty($p->fecha_publicacion)) {
                    $p->fecha_publicacion = $now;
                }
                $p->state_id = 2;
                $p->save();

                Log::info('[auto-publish] published', ['post_id' => $p->id]);
            }
        });
})
->everyMinute()
->name('posts-auto-publish')
->withoutOverlapping()
->onOneServer();
