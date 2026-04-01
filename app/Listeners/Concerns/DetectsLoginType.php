<?php

declare(strict_types=1);

namespace App\Listeners\Concerns;

use Illuminate\Http\Request;

trait DetectsLoginType
{
    protected function detectLoginType(Request $request): string
    {
        $accept = $request->header('Accept', '');
        $userAgent = $request->header('User-Agent', '');

        if (str_contains($accept, 'application/json')) {
            return 'api';
        }

        if (preg_match('/(iOS|Android|Mobile)/i', $userAgent)) {
            return 'mobile';
        }

        return 'web';
    }
}
