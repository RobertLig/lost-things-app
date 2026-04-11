<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranslationService
{
    protected string $endpoint = 'http://127.0.0.1:5000/translate';

    public function translate(string $text, string $target, string $source = 'auto'): string
    {
        try {
            $response = Http::post($this->endpoint, [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ]);

            if (!$response->successful()) {
                \Log::error('Translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $text;
            }

            $data = $response->json();

            return $data['translatedText'] ?? $text;

        } catch (\Exception $e) {
            \Log::error('Translation exception', [
                'message' => $e->getMessage(),
            ]);

            return $text;
        }
    }
}