<?php

declare(strict_types=1);

namespace Tests;

use App\Http\Middleware\EnforceSingleSession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            EnforceSingleSession::class,
            VerifyCsrfToken::class,
        ]);
    }
}
