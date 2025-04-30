<?php

namespace App\Console\Commands;

use App\Enums\ProcessingStatusEnum;
use App\Models\Book;
use App\Models\Shard;
use Illuminate\Console\Command;
use App\Jobs\ProcessShardTTSJob;

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
        $shards = Shard::where('processing_status', ProcessingStatusEnum::PENDING)
        ->whereIn('chapter_id',
            $book->chapters->pluck('id')->values()->flatten()->toArray()
        )->get();
        $bar = $this->output->createProgressBar(count($shards));
        $bar->start();
        foreach ($shards as $shard) {
            $x = new ProcessShardTTSJob($shard);
            $x->handle();
            $bar->advance();
        }

        $bar->finish();
    }
}
