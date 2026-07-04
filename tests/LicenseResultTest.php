<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate\Tests;

use K2gl\ComposerLicenseGate\LicenseResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(LicenseResult::class)]
final class LicenseResultTest extends TestCase
{
    public function testAllowed(): void
    {
        $result = LicenseResult::allowed('MIT');

        fact($result->allowed)->true();
        fact($result->isViolation())->false();
        fact($result->exempt)->false();
        fact($result->message)->is('MIT');
    }

    public function testExempt(): void
    {
        $result = LicenseResult::exempt();

        fact($result->allowed)->true();
        fact($result->exempt)->true();
        fact($result->isViolation())->false();
    }

    public function testViolation(): void
    {
        $result = LicenseResult::violation('license "GPL-3.0" is denied by policy');

        fact($result->isViolation())->true();
        fact($result->allowed)->false();
        fact($result->message)->containsString('denied');
    }
}
