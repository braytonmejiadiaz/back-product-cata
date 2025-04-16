<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CustomDomainController extends Controller
{
    public function connect(Request $request)
    {
        $request->validate(['domain' => 'required|url']);

        $domain = parse_url($request->domain, PHP_URL_HOST);
        $verificationCode = Str::random(20);

        $request->user()->customDomain()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['domain' => $domain, 'verification_code' => $verificationCode]
        );

        return response()->json([
            'instructions' => [
                'cname' => 'Create a CNAME record pointing to app.treggio.co',
                'txt' => 'Or add a TXT record with: '.$verificationCode
            ]
        ]);
    }

    public function verify(Request $request)
    {
        $domain = $request->user()->customDomain;

        if (!$domain) abort(404);

        $isValid = $this->checkDns($domain->domain, $domain->verification_code);

        if ($isValid) {
            $domain->update(['is_verified' => true]);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid DNS configuration'], 400);
    }

    protected function checkDns($domain, $code)
    {
        $cnameRecords = dns_get_record($domain, DNS_CNAME);
        $txtRecords = dns_get_record($domain, DNS_TXT);

        $hasValidCname = collect($cnameRecords)->contains('target', 'app.treggio.co');
        $hasValidTxt = collect($txtRecords)->contains('txt', $code);

        return $hasValidCname || $hasValidTxt;
    }
}
