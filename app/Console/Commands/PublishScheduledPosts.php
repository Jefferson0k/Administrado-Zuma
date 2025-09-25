<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Publish posts whose scheduled time has arrived';

    public function handle(): int
    {
        // important if you use app timezone
        $now = Carbon::now(); // uses config('app.timezone')

        $count = 0;

        DB::transaction(function () use (&$count, $now) {
            $due = Post::where('state_id', 1) // 1 = Creado (draft)
                ->whereNotNull('fecha_programada')
                ->where('fecha_programada', '<=', $now)
                ->lockForUpdate()
                ->get();

            foreach ($due as $post) {
                $post->state_id = 2; // 2 = Publicado
                $post->fecha_publicacion = $now;
                // keep updated_user_id as is or null
                $post->save();
                $count++;
            }
        });

        $this->info("Published {$count} scheduled post(s).");
        return self::SUCCESS;
    }
}
