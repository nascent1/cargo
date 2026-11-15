<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\TestCase as AppTestCase;

/** The rule that keeps tests off working data: only a database whose name ends in "_test" may be used. */
class TestDatabaseGuardTest extends TestCase
{
    #[Test]
    public function a_database_whose_name_ends_in_test_is_accepted(): void
    {
        $this->assertNull(AppTestCase::unsafeDatabaseReason('uclp_cargo_test'));
        $this->assertNull(AppTestCase::unsafeDatabaseReason('ferry_booking_test'));
    }

    #[Test]
    public function any_other_database_is_refused_with_the_way_to_fix_it(): void
    {
        foreach (['uclp_cargo', 'cargo_booking', 'test', 'uclp_cargo_test2', 'uclp_cargo_testing', 'test_uclp_cargo', '', null, ':memory:'] as $name) {
            $reason = AppTestCase::unsafeDatabaseReason($name);

            $this->assertNotNull($reason, var_export($name, true) . ' should be refused');
            $this->assertStringContainsString('must end in "_test"', $reason);
            $this->assertStringContainsString('scripts/make-test-db.sh', $reason);
        }

        $this->assertStringContainsString('"uclp_cargo"', AppTestCase::unsafeDatabaseReason('uclp_cargo'));
    }
}
