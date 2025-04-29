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

    private function splitTextIntoShards(string $text, int $target = 500, int $min = 400, int $max = 700): array
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $shards = [];
        $buffer = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') continue;

            $prospective = $buffer === '' ? $sentence : $buffer . ' ' . $sentence;
            $length = strlen($prospective);

            if ($length < $min) {
                // Keep building the buffer
                $buffer = $prospective;
            } elseif ($length <= $max) {
                // Acceptable size — store and reset
                $buffer = $prospective;
                $shards[] = trim($buffer);
                $buffer = '';
            } else {
                // Too long if we add, so flush current buffer and start new one
                if (!empty($buffer)) {
                    $shards[] = trim($buffer);
                    $buffer = $sentence;
                } else {
                    // Single sentence too long — force break (edge case)
                    $shards[] = trim($sentence);
                    $buffer = '';
                }
            }
        }

        // Add leftover
        if (!empty($buffer)) {
            $shards[] = trim($buffer);
        }

        return $shards;
    }
}
