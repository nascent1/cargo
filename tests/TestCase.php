<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesTestData;

    /** Traits that rebuild or empty the whole database. */
    private const DESTRUCTIVE_TRAITS = [RefreshDatabase::class, DatabaseMigrations::class, DatabaseTruncation::class];

    /**
     * Tests run only on a database whose name ends in "_test" (built by scripts/make-test-db.sh).
     * The check sits here, before any trait opens a transaction or migrates, so nothing is written
     * to a working database. The whole run stops: every other test would fail the same way.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $problem = self::unsafeDatabaseReason($app['config']->get("database.connections.{$connection}.database"));

        if ($problem !== null) {
            fwrite(STDERR, PHP_EOL . $problem . PHP_EOL);
            exit(2);
        }

        return $app;
    }

    /** Why this database may not be used for tests, or null when it may. */
    public static function unsafeDatabaseReason(mixed $database): ?string
    {
        if (is_string($database) && str_ends_with($database, '_test')) {
            return null;
        }

        return 'Tests refuse to run: the database is "' . (is_string($database) ? $database : '') . '", and its name must end in "_test". '
            . 'Run scripts/make-test-db.sh, and clear any cached configuration with "php artisan config:clear".';
    }

    protected function setUp(): void
    {
        // Passenger-era files rebuild the database from migrations, which would empty the test database.
        if (!getenv('ALLOW_DB_REFRESH') && array_intersect(self::DESTRUCTIVE_TRAITS, class_uses_recursive(static::class))) {
            $this->markTestSkipped('Rebuilds the database. Set ALLOW_DB_REFRESH=1 to run it; then rebuild the test database with scripts/make-test-db.sh.');
        }

        parent::setUp();

        // The test database is a copy of development data, and .env may hold real gateway settings:
        // any HTTP call a test hasn't faked fails instead of reaching a provider (no real SMS, no real push).
        Http::preventStrayRequests();

        $this->seedReferenceData();
    }
}
