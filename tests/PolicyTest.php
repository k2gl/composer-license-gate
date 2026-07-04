<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate\Tests;

use K2gl\ComposerLicenseGate\Policy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(Policy::class)]
final class PolicyTest extends TestCase
{
    public function testDefaults(): void
    {
        $policy = Policy::fromExtra([]);

        fact($policy->mode)->is(Policy::MODE_WARN);
        fact($policy->allow)->is([]);
        fact($policy->deny)->is([]);
        fact($policy->requireLicense)->false();
    }

    public function testReadsConfig(): void
    {
        $policy = Policy::fromExtra(['k2gl-license-gate' => [
            'mode' => 'enforce',
            'allow' => ['MIT', 'Apache-2.0'],
            'deny' => ['GPL-*'],
            'allow-packages' => ['acme/legacy'],
            'require-license' => true,
        ]]);

        fact($policy->isEnforcing())->true();
        fact($policy->allow)->is(['MIT', 'Apache-2.0']);
        fact($policy->deny)->is(['GPL-*']);
        fact($policy->allowPackages)->is(['acme/legacy']);
        fact($policy->requireLicense)->true();
    }

    public function testFiltersNonStringListEntries(): void
    {
        $policy = Policy::fromExtra(['k2gl-license-gate' => ['allow' => ['MIT', 42, null, 'ISC']]]);

        fact($policy->allow)->is(['MIT', 'ISC']);
    }

    public function testUnknownModeFallsBackToWarn(): void
    {
        fact(Policy::fromExtra(['k2gl-license-gate' => ['mode' => 'nope']])->mode)->is(Policy::MODE_WARN);
    }

    public function testMalformedConfigIsIgnored(): void
    {
        fact(Policy::fromExtra(['k2gl-license-gate' => 'x'])->isOff())->false();
        fact(Policy::fromExtra([])->isOff())->false();
    }

    public function testOffMode(): void
    {
        fact(Policy::fromExtra(['k2gl-license-gate' => ['mode' => 'off']])->isOff())->true();
    }
}
