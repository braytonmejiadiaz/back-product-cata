<?php

namespace App\Http\Controllers;

use App\Services\AiMarketingGenerator;
use App\Models\MarketingCampaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class AiMarketingController extends Controller
{
    public function history()
    {
        try {
            $campaigns = Auth::user()
                ->marketingCampaigns()
                ->with('versions')
                ->orderBy('generated_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $campaigns
            ]);
        } catch (Exception $e) {
            Log::error('Error al obtener historial: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }

    public function regenerate(Request $request, $campaignId)
    {
        try {
            $campaign = MarketingCampaign::where('id', $campaignId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $generator = new AiMarketingGenerator();
            $newVersion = $generator->regenerateCampaign($campaign, $request->all());

            $campaign->versions()->create([
                'version_data' => json_encode($newVersion, JSON_UNESCAPED_UNICODE),
                'created_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $newVersion
            ]);
        } catch (Exception $e) {
            Log::error('Error al regenerar campaña: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al regenerar la campaña'
            ], 500);
        }
    }

    protected function prepareCampaignData($userId, $campaign)
    {
        return [
            'user_id' => $userId,
            'campaign_name' => $this->ensureString($campaign['name']),
            'campaign_description' => $this->ensureString($campaign['description']),
            'target_audience' => $this->ensureJson($campaign['target_audience']),
            'ad_copy' => $this->ensureJson($campaign['ad_copy']),
            'visual_recommendations' => $this->ensureJson($campaign['visual_style']),
            'call_to_action' => $this->ensureString($campaign['call_to_action']),
            'status' => 'draft',
            'generated_at' => now()
        ];
    }

    protected function ensureString($value): string
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return (string)($value ?? '');
    }

    protected function ensureJson($data)
    {
        if (is_array($data) || is_object($data)) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        if (is_string($data) && json_decode($data) !== null) {
            return $data;
        }
        return json_encode([]);
    }


    public function generate(Request $request)
{
    $user = Auth::user();

    try {
        $request->validate([
            'tone' => 'nullable|string|in:profesional,divertido,emocional',
            'style' => 'nullable|string|in:moderno,minimalista,vibrante',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id,user_id,'.$user->id
        ]);

        $generator = new AiMarketingGenerator();
        $campaigns = $generator->generateForUser($user, $request->only(['tone', 'style', 'product_ids']));

        $savedCampaigns = [];

        DB::transaction(function () use ($user, $campaigns, &$savedCampaigns) {
            foreach ($campaigns as $campaign) {
                $campaignData = [
                    'user_id' => $user->id,
                    'campaign_name' => $campaign['name'],
                    'campaign_description' => $campaign['description'],
                    'target_audience' => json_encode($campaign['target_audience']),
                    'ad_copy' => json_encode($campaign['ad_copy']),
                    'visual_recommendations' => json_encode($campaign['visual_style']),
                    'call_to_action' => $campaign['call_to_action'],
                    'status' => 'draft',
                    'generated_at' => now()
                ];

                $saved = MarketingCampaign::create($campaignData);
                $saved->versions()->create(['version_data' => $campaignData]);
                $savedCampaigns[] = $saved;
            }
        });

        return response()->json([
            'success' => true,
            'data' => $savedCampaigns,
            'message' => 'Campañas generadas exitosamente'
        ]);

    } catch (Exception $e) {
        Log::error('Error al generar campañas: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al generar las campañas',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

}
