<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    /**
     * Get the real client IP address, checking headers if behind a proxy.
     */
    protected function getClientIp(Request $request): ?string
    {
        $ip = $request->ip();

        // Check if IP is in a private range
        if ($this->isPrivateIp($ip)) {
            // Try to get real IP from proxy headers
            $forwardedFor = $request->header("X-Forwarded-For");
            if ($forwardedFor) {
                // X-Forwarded-For can contain multiple IPs (client, proxy1, proxy2, ...)
                // The first IP is typically the original client
                $ips = array_map("trim", explode(",", $forwardedFor));
                $ip = $ips[0];
            } elseif ($request->header("X-Real-IP")) {
                $ip = $request->header("X-Real-IP");
            } elseif ($request->header("CF-Connecting-IP")) {
                // Cloudflare
                $ip = $request->header("CF-Connecting-IP");
            }
        }

        return $ip;
    }

    /**
     * Check if an IP address is in a private range.
     */
    protected function isPrivateIp(string $ip): bool
    {
        // Check for IPv6 localhost
        if ($ip === "::1") {
            return true;
        }

        // Convert to long for IPv4 comparison
        $longIp = ip2long($ip);

        if ($longIp === false) {
            return false;
        }

        // Private IP ranges
        $privateRanges = [
            ["10.0.0.0", "10.255.255.255"], // 10.0.0.0/8
            ["172.16.0.0", "172.31.255.255"], // 172.16.0.0/12
            ["192.168.0.0", "192.168.255.255"], // 192.168.0.0/16
            ["127.0.0.0", "127.255.255.255"], // 127.0.0.0/8 (localhost)
        ];

        foreach ($privateRanges as [$start, $end]) {
            if ($longIp >= ip2long($start) && $longIp <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }
    /**
     * Handle the short link redirect.
     */
    public function handle(Request $request, string $code): RedirectResponse
    {
        $redirect = Redirect::where("code", $code)
            ->where("is_active", true)
            ->firstOrFail();

        // Record the click with detailed tracking
        $redirect->recordClick(
            userId: auth()->id(),
            ipAddress: $this->getClientIp($request),
            userAgent: $request->userAgent(),
            referer: $request->header("referer"),
        );

        return redirect()->away($redirect->url);
    }
}
