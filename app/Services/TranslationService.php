namespace App\Services;

use Illuminate\Support\Facades\Http;

class TranslationService
{
    protected string $endpoint = 'https://libretranslate.com/translate';

    public function translate(string $text, string $target, string $source = 'auto'): string
    {
        try {
            $response = Http::post($this->endpoint, [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ]);

            return $response->json()['translatedText'] ?? $text;

        } catch (\Exception $e) {
            // fallback → return original text
            return $text;
        }
    }
}