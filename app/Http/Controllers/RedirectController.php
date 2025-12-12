<?php

namespace App\Http\Controllers;

use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    /**
     * Handle the short link redirect.
     */
    public function handle(Request $request, string $code): RedirectResponse
    {
        $redirect = Redirect::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        // Record the click with detailed tracking
        $redirect->recordClick(
            userId: auth()->id(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            referer: $request->header('referer')
        );

        return redirect()->away($redirect->url);
    }
}
