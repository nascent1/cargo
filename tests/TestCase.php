<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests run against the dev database, where real SMS / payment gateways may be configured:
        // any HTTP call a test hasn't faked fails instead of reaching a provider (no real SMS, no real push).
        Http::preventStrayRequests();

        $this->seedReferenceData();
    }
}
