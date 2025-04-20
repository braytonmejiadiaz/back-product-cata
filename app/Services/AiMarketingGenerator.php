<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\User;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use Exception;
use JsonException;

class AiMarketingGenerator
{
    protected $httpClient;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('DeepSeek API Key no está configurado');
        }

        $this->httpClient = new Client([
            'base_uri' => config('services.deepseek.base_uri', 'https://api.deepseek.com/v1/'),
            'timeout' => config('services.deepseek.timeout', 30),
            'connect_timeout' => 15,
            'http_errors' => false,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    public function generateForUser(User $user, array $options = [])
    {
        try {
            $products = $user->products()
                ->with(['categorieFirst', 'categorieSecond', 'categorieThird'])
                ->get();

            if ($products->isEmpty()) {
                throw new Exception("El usuario no tiene productos para generar campañas");
            }

            $prompt = $this->buildPrompt($products, $options);
            Log::debug("Prompt generado:", ['prompt_preview' => substr($prompt, 0, 200) . '...']);

            $response = $this->makeApiRequest($prompt);
            $responseData = $this->processApiResponse($response);

            $campaigns = $this->parseCampaigns($responseData['choices'][0]['message']['content']);

            Log::info("Campañas generadas exitosamente", [
                'user_id' => $user->id,
                'campaigns_count' => count($campaigns)
            ]);

            return $campaigns;

        } catch (Exception $e) {
            Log::error('Error en generación de campañas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("No se pudieron generar las campañas. Por favor intente más tarde.");
        }
    }

    protected function buildPrompt($products, $options): string
    {
        $productInfo = $products->map(function($product) {
            $categories = [
                $product->categorieFirst->name ?? null,
                $product->categorieSecond->name ?? null,
                $product->categorieThird->name ?? null
            ];
            $categoryString = implode(' > ', array_filter($categories));

            $tags = $this->normalizeTags($product->tags);
            $tagsString = !empty($tags) ? 'Etiquetas: ' . implode(', ', $tags) : 'Sin etiquetas';

            return sprintf(
                "🔹 Producto: %s\n".
                "Descripción: %s\n".
                "Categorías: %s\n".
                "Precio: %s\n".
                "%s",
                $product->title,
                $product->description,
                $categoryString ?: "Sin categoría",
                $product->price ?? "No especificado",
                $tagsString
            );
        })->implode("\n\n");

        return "Genera EXACTAMENTE 3 campañas de marketing en formato JSON para estos productos:\n\n".
               "### Productos:\n{$productInfo}\n\n".
               "### Instrucciones:\n".
               "- Formato requerido: JSON válido\n".
               "- Cada campaña debe incluir: nombre, descripción, audiencia, textos publicitarios, estilo visual\n".
               "- Usa el siguiente formato:\n\n".
               '{
                 "campaigns": [
                   {
                     "name": "Nombre creativo",
                     "description": "Objetivo de campaña",
                     "target_audience": {
                       "age": "25-35",
                       "interests": ["moda", "tecnología"]
                     },
                     "ad_copy": ["Texto 1", "Texto 2"],
                     "visual_style": {
                       "colors": ["#FF5733"],
                       "mood": "moderno"
                     },
                     "call_to_action": "¡Compra ahora!"
                   }
                 ]
               }';
    }

    protected function normalizeTags($tags): array
    {
        if (is_null($tags)) {
            return [];
        }

        if (is_array($tags) && $this->isSimpleArray($tags)) {
            return array_map('strval', $tags);
        }

        if (is_string($tags)) {
            try {
                $decoded = json_decode($tags, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded) && !$this->isSimpleArray($decoded)) {
                    return $this->extractTagsFromComplexArray($decoded);
                }

                return is_array($decoded) ? array_map('strval', $decoded) : [strval($decoded)];
            } catch (JsonException $e) {
                return [strval($tags)];
            }
        }

        return [strval($tags)];
    }

    protected function isSimpleArray(array $array): bool
    {
        foreach ($array as $item) {
            if (is_array($item) || is_object($item)) {
                return false;
            }
        }
        return true;
    }

    protected function extractTagsFromComplexArray(array $complexArray): array
    {
        $tags = [];
        foreach ($complexArray as $item) {
            if (is_array($item) || is_object($item)) {
                $possibleFields = ['name', 'tag', 'title', 'label', 'value', 'item_id'];
                foreach ($possibleFields as $field) {
                    if (isset($item[$field])) {
                        $value = $item[$field];
                        $tags[] = is_numeric($value) && $field === 'item_id' ? 'item_'.$value : strval($value);
                        break;
                    }
                }
            } else {
                $tags[] = strval($item);
            }
        }
        return array_unique(array_filter($tags));
    }

    protected function makeApiRequest(string $prompt): array
    {
        $payload = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un generador de campañas de marketing. Responde EXCLUSIVAMENTE con el JSON solicitado, sin comentarios adicionales.'
                ],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];

        try {
            Log::debug("Enviando solicitud a DeepSeek API", ['payload' => $payload]);

            $response = $this->httpClient->post('chat/completions', [
                'json' => $payload
            ]);

            $responseContent = $response->getBody()->getContents();

            Log::debug("Respuesta de DeepSeek API", [
                'status' => $response->getStatusCode(),
                'headers' => json_encode($response->getHeaders()),
                'body_preview' => substr($responseContent, 0, 200)
            ]);

            return [
                'status' => $response->getStatusCode(),
                'content' => $responseContent
            ];

        } catch (Exception $e) {
            Log::error('Error en API DeepSeek', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("Error al conectar con el servicio de generación");
        }
    }

    protected function processApiResponse(array $response): array
    {
        $statusCode = $response['status'];
        $content = $response['content'];

        if ($statusCode === 401) {
            Log::error('Error de autenticación con DeepSeek', [
                'status' => $statusCode,
                'response_preview' => substr($content, 0, 200)
            ]);
            throw new Exception("Error de autenticación: Verifica tu API Key de DeepSeek");
        }

        if ($statusCode !== 200) {
            Log::error('Error en la API DeepSeek', [
                'status' => $statusCode,
                'response_preview' => substr($content, 0, 200)
            ]);
            throw new Exception("Error en API: Código {$statusCode}");
        }

        if (empty($content)) {
            throw new Exception("La respuesta de la API está vacía");
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Log::error('JSON inválido de DeepSeek', [
                'error' => $e->getMessage(),
                'content_preview' => substr($content, 0, 200)
            ]);
            throw new Exception("Formato de respuesta inválido");
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            Log::error('Estructura inesperada de DeepSeek', [
                'data_keys' => array_keys($data),
                'has_choices' => isset($data['choices']),
                'first_choice_keys' => isset($data['choices'][0]) ? array_keys($data['choices'][0]) : null
            ]);
            throw new Exception("Estructura de respuesta inesperada");
        }

        return $data;
    }

    protected function parseCampaigns(string $jsonContent): array
    {
        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['campaigns']) || !is_array($data['campaigns'])) {
                throw new Exception("El JSON no contiene el campo 'campaigns' o no es un array");
            }

            $campaigns = [];
            foreach ($data['campaigns'] as $campaign) {
                $campaigns[] = [
                    'name' => $this->ensureString($campaign['name'] ?? 'Campaña sin nombre'),
                    'description' => $this->ensureString($campaign['description'] ?? ''),
                    'target_audience' => $this->ensureArray($campaign['target_audience'] ?? []),
                    'ad_copy' => $this->ensureStringArray($campaign['ad_copy'] ?? []),
                    'visual_style' => $this->ensureArray($campaign['visual_style'] ?? []),
                    'call_to_action' => $this->ensureString($campaign['call_to_action'] ?? '')
                ];
            }

            return $campaigns;

        } catch (JsonException $e) {
            Log::error('Error parseando JSON de campañas', [
                'error' => $e->getMessage(),
                'json_preview' => substr($jsonContent, 0, 200)
            ]);
            throw new Exception("El contenido generado no es JSON válido: " . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Error procesando campañas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("Error al procesar las campañas generadas: " . $e->getMessage());
        }
    }

    protected function ensureString($value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string)($value ?? '');
    }

    protected function ensureArray($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($value) ? $value : [];
    }

    protected function ensureStringArray(array $items): array
    {
        return array_map(function($item) {
            return (string)($item ?? '');
        }, $items);
    }
}
