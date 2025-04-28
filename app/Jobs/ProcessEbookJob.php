<?php

namespace App\Jobs;

use App\Enums\ProcessingStatusEnum;
use App\Models\Book;
use App\Models\Shard;
use App\Models\Chapter;
use App\Services\EpubParserService;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessEbookJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Book $book) {}

    public function handle()
    {
        $parsedBook = new EpubParserService(storage_path('app/' . $this->book->path));
        foreach ($parsedBook->getChapters() as $chapterData) {

            dump($chapterData);
            $chapter = Chapter::create([
                'book_id' => $this->book->id,
                'title' => $chapterData['title'],
                'content' => $chapterData['content'] ?? 'pippo',
                'processing_status' => ProcessingStatusEnum::PENDING,
                'audio_path' => 'pi',
            ]);

            $shards = $this->splitTextIntoShards($chapterData['content']);

            foreach ($shards as $shardText) {
                Shard::create([
                    'chapter_id' => $chapter->id,
                    'content' => $shardText,
                    'processing_status' => ProcessingStatusEnum::PENDING,
                ]);
            }
        }

        $this->book->update(['processing_status' => ProcessingStatusEnum::DONE]);
    }

    private function splitTextIntoShards($text, $targetLength = 500)
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $shards = [];
        $currentShard = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            // Skip empty sentences
            if (empty($sentence)) {
                continue;
            }

            // If adding this sentence would make the shard too big
            if (strlen($currentShard . ' ' . $sentence) > $targetLength && !empty($currentShard)) {
                $shards[] = trim($currentShard);
                $currentShard = $sentence;
            } else {
                $currentShard .= ' ' . $sentence;
            }
        }

        // Add last shard if anything remains
        if (!empty($currentShard)) {
            $shards[] = trim($currentShard);
        }

        return $shards;
    }
}
