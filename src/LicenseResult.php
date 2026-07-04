<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate;

/**
 * The outcome of checking one package's licenses against the policy: allowed
 * (possibly because the package is exempt), or a violation with a reason.
 */
final class LicenseResult
{
    private function __construct(
        public readonly bool $allowed,
        public readonly bool $exempt,
        public readonly string $message,
    ) {}

    public static function allowed(string $message = ''): self
    {
        return new self(true, false, $message);
    }

    public static function exempt(): self
    {
        return new self(true, true, 'allow-listed');
    }

    public static function violation(string $message): self
    {
        return new self(false, false, $message);
    }

    public function isViolation(): bool
    {
        return ! $this->allowed;
    }
}
