<?php

namespace App\Jobs;

use App\Enums\ProcessingStatusEnum;
use App\Models\Shard;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessShardTTSJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected Shard $shard;

    public function __construct(Shard $shard)
    {
        $this->shard = $shard;
    }

    public function handle(): void
    {
        // if ($this->shard->processing_status !== ProcessingStatusEnum::PENDING->value) {
        //     return;
        // }

        $this->shard->update(['processing_status' => ProcessingStatusEnum::PROCESSING]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'audio/mpeg',
            ])->post('host.docker.internal:8880/v1/audio/speech',[//config('services.kokoro.endpoint'), [
                'model' => 'kokoro',
                'input' => $this->shard->normalized_content,
                'voice' => 'af_aoede',
                'response_format' => 'mp3',
                'download_format' => 'mp3',
                'speed' => 1,
                'stream' => true,
                'return_download_link' => false,
                'lang_code' => 'a',
                'normalization_options' => [
                    'normalize' => true,
                    'unit_normalization' => false,
                    'url_normalization' => true,
                    'email_normalization' => true,
                    'optional_pluralization_normalization' => true,
                    'phone_normalization' => true,
                ],
            ]);

            if (!$response->successful()) {
                throw new \Exception('TTS API failed with status ' . $response->status());
            }

            // Save binary audio/mpeg stream to disk
            $filePath = 'tts_shards/' . $this->shard->chapter->book->id .'/'. $this->shard->chapter->id .'_'. $this->shard->id . '.mp3';
            Storage::disk('public')->put($filePath, $response->body());

            $this->shard->update([
                'processing_status' => ProcessingStatusEnum::DONE,
                'audio_path' => $filePath,
            ]);

        } catch (\Throwable $e) {
            dump($e->getMessage());
            $this->shard->update([
                'processing_status' => ProcessingStatusEnum::ERROR,
                //'error_message' => $e->getMessage(),
            ]);
        }
    }
}
