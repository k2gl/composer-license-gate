<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate\Tests;

use K2gl\ComposerLicenseGate\LicenseChecker;
use K2gl\ComposerLicenseGate\Policy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function K2gl\PHPUnitFluentAssertions\fact;

#[CoversClass(LicenseChecker::class)]
#[CoversClass(Policy::class)]
final class LicenseCheckerTest extends TestCase
{
    public function testAllowListAcceptsAnAllowedLicense(): void
    {
        $checker = new LicenseChecker(new Policy(allow: ['MIT', 'Apache-2.0']));

        fact($checker->check('acme/thing', ['MIT'])->isViolation())->false();
    }

    public function testAllowListRejectsALicenseNotOnIt(): void
    {
        $checker = new LicenseChecker(new Policy(allow: ['MIT']));
        $result = $checker->check('acme/thing', ['GPL-3.0-or-later']);

        fact($result->isViolation())->true();
        fact($result->message)->containsString('not in the allow-list');
    }

    public function testAllowListIsConservativeAboutDualLicensing(): void
    {
        // One disallowed option is enough to flag it.
        $checker = new LicenseChecker(new Policy(allow: ['MIT']));

        fact($checker->check('acme/thing', ['MIT', 'GPL-3.0-or-later'])->isViolation())->true();
    }

    public function testDenyListRejectsAMatchingLicense(): void
    {
        $checker = new LicenseChecker(new Policy(deny: ['GPL-*', 'AGPL-*']));
        $result = $checker->check('acme/thing', ['GPL-3.0-or-later']);

        fact($result->isViolation())->true();
        fact($result->message)->containsString('denied by policy');
    }

    public function testDenyListAllowsANonMatchingLicense(): void
    {
        $checker = new LicenseChecker(new Policy(deny: ['GPL-*']));

        fact($checker->check('acme/thing', ['MIT'])->isViolation())->false();
    }

    public function testWildcardMatchesByPrefix(): void
    {
        $checker = new LicenseChecker(new Policy(deny: ['GPL-*']));

        fact($checker->check('a/b', ['GPL-2.0-only'])->isViolation())->true();
        fact($checker->check('a/b', ['LGPL-3.0'])->isViolation())->false();
    }

    public function testMatchingIsCaseInsensitive(): void
    {
        $checker = new LicenseChecker(new Policy(allow: ['mit']));

        fact($checker->check('a/b', ['MIT'])->isViolation())->false();
    }

    public function testAllowListedPackageIsExempt(): void
    {
        $checker = new LicenseChecker(new Policy(deny: ['GPL-*'], allowPackages: ['acme/legacy']));
        $result = $checker->check('acme/legacy', ['GPL-3.0-or-later']);

        fact($result->isViolation())->false();
        fact($result->exempt)->true();
    }

    public function testMissingLicenseIsAllowedByDefault(): void
    {
        $checker = new LicenseChecker(new Policy(allow: ['MIT']));

        fact($checker->check('a/b', [])->isViolation())->false();
    }

    public function testMissingLicenseIsAViolationWhenRequired(): void
    {
        $checker = new LicenseChecker(new Policy(allow: ['MIT'], requireLicense: true));
        $result = $checker->check('a/b', []);

        fact($result->isViolation())->true();
        fact($result->message)->containsString('no license');
    }

    public function testNoPolicyAllowsEverything(): void
    {
        $checker = new LicenseChecker(new Policy);

        fact($checker->check('a/b', ['WTFPL'])->isViolation())->false();
    }
}
