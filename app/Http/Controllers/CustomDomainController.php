<?php

namespace App\Http\Controllers;

use App\Models\CustomDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CustomDomainController extends Controller
{
    public function connect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|url|unique:custom_domains,domain'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => $validator->errors()->first()
            ], 422);
        }

        $domain = parse_url($request->domain, PHP_URL_HOST);
        $mainDomain = config('app.main_domain', 'app.treggio.co');

        // Validar que no sea el dominio principal
        if ($domain === $mainDomain) {
            return response()->json([
                'error' => 'No puedes usar el dominio principal de la aplicación'
            ], 422);
        }

        $verificationCode = Str::random(32);

        $customDomain = $request->user()->customDomain()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'domain' => $domain,
                'verification_code' => $verificationCode,
                'is_verified' => false
            ]
        );

        // Limpiar caché del dominio
        Cache::forget("user_for_domain:{$domain}");

        return response()->json([
            'success' => true,
            'domain' => $customDomain,
            'instructions' => [
                'cname' => 'Crea un registro CNAME apuntando a '.$mainDomain,
                'txt' => 'O agrega un registro TXT con el valor: '.$verificationCode
            ]
        ]);
    }

    public function verify(Request $request)
    {
        $domain = $request->user()->customDomain;

        if (!$domain) {
            return response()->json([
                'error' => 'No tienes un dominio configurado'
            ], 404);
        }

        $isValid = $this->checkDns($domain->domain, $domain->verification_code);

        if ($isValid) {
            $domain->update(['is_verified' => true]);
            Cache::forget("user_for_domain:{$domain->domain}");

            return response()->json([
                'success' => true,
                'domain' => $domain->fresh()
            ]);
        }

        return response()->json([
            'error' => 'Configuración DNS no válida. Verifica tus registros CNAME o TXT',
            'retry_url' => route('domain.verify')
        ], 400);
    }

    protected function checkDns($domain, $code)
    {
        try {
            $cnameRecords = dns_get_record($domain, DNS_CNAME);
            $txtRecords = dns_get_record($domain, DNS_TXT);

            $mainDomain = config('app.main_domain', 'app.treggio.co');
            $hasValidCname = collect($cnameRecords)->contains('target', $mainDomain);
            $hasValidTxt = collect($txtRecords)->contains('txt', $code);

            return $hasValidCname || $hasValidTxt;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getConfig(Request $request)
    {
        return response()->json([
            'slug' => $request->user()->slug,
            'custom_domain' => $request->user()->customDomain,
            'main_domain' => config('app.main_domain', 'app.treggio.co')
        ]);
    }

    public function disconnect(Request $request)
    {
        $domain = $request->user()->customDomain;

        if ($domain) {
            Cache::forget("user_for_domain:{$domain->domain}");
            $domain->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dominio desconectado correctamente'
            ]);
        }

        return response()->json([
            'error' => 'No tienes un dominio configurado'
        ], 404);
    }
}
