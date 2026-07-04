<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate;

/**
 * Decides whether a package's declared licenses satisfy the {@see Policy}.
 *
 * With an allow-list, every declared license must match (a package is rejected
 * if any license is not allowed — a conservative reading, so a dual-licensed
 * package with one disallowed option is flagged for review and can be exempted
 * via `allow-packages`). With a deny-list, any matching license rejects the
 * package. Patterns match case-insensitively, with a trailing `*` as a prefix
 * wildcard.
 */
final class LicenseChecker
{
    public function __construct(private readonly Policy $policy) {}

    /** @param list<string> $licenses the package's declared SPDX licenses */
    public function check(string $package, array $licenses): LicenseResult
    {
        if (in_array($package, $this->policy->allowPackages, true)) {
            return LicenseResult::exempt();
        }
        $licenses = array_values(array_filter($licenses, static fn (string $l): bool => $l !== ''));

        if ($licenses === []) {
            return $this->policy->requireLicense
                ? LicenseResult::violation('declares no license')
                : LicenseResult::allowed('no license declared');
        }

        if ($this->policy->allow !== []) {
            foreach ($licenses as $license) {
                if (! $this->matchesAny($license, $this->policy->allow)) {
                    return LicenseResult::violation(sprintf('license "%s" is not in the allow-list', $license));
                }
            }

            return LicenseResult::allowed(implode(', ', $licenses));
        }

        if ($this->policy->deny !== []) {
            foreach ($licenses as $license) {
                if ($this->matchesAny($license, $this->policy->deny)) {
                    return LicenseResult::violation(sprintf('license "%s" is denied by policy', $license));
                }
            }
        }

        return LicenseResult::allowed(implode(', ', $licenses));
    }

    /** @param list<string> $patterns */
    private function matchesAny(string $license, array $patterns): bool
    {
        $license = strtolower($license);

        foreach ($patterns as $pattern) {
            $pattern = strtolower($pattern);

            if (str_ends_with($pattern, '*')) {
                if (str_starts_with($license, substr($pattern, 0, -1))) {
                    return true;
                }
            } elseif ($license === $pattern) {
                return true;
            }
        }

        return false;
    }
}
