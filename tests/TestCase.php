<?php

namespace Tests;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
        RateLimiter::clear('api');
        RateLimiter::clear('global');
    }
}
