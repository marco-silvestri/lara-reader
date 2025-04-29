<?php

namespace App\Console\Commands;

use App\Jobs\ProcessShardTTSJob;
use App\Models\Book;
use Illuminate\Console\Command;

class SendBookShardsToTTS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tts:send-book-shards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $book = Book::find(1);

        foreach ($book->chapters as $chapter)
        {
            foreach($chapter->shards as $shard)
            {
                $x = new ProcessShardTTSJob($shard);
                $x->handle();
                sleep(1);
            }
        }
    }
}
