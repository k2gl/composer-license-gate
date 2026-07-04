<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate;

/**
 * The license policy, read from the root package's `extra.k2gl-license-gate`:
 *
 * ```json
 * "extra": {
 *   "k2gl-license-gate": {
 *     "mode": "enforce",
 *     "allow": ["MIT", "Apache-2.0", "BSD-2-Clause", "BSD-3-Clause", "ISC"],
 *     "deny": ["GPL-*", "AGPL-*"],
 *     "allow-packages": ["acme/legacy-thing"],
 *     "require-license": false
 *   }
 * }
 * ```
 *
 * - `mode` — `warn` (report only), `enforce` (fail the install on a violation),
 *   or `off`.
 * - `allow` — if non-empty, a package is allowed only when *every* declared
 *   license matches one of these SPDX patterns. Takes precedence over `deny`.
 * - `deny` — a package is rejected if *any* declared license matches one of these.
 * - `allow-packages` — package names (`vendor/name`) exempt from the check.
 * - `require-license` — treat a package that declares no license as a violation.
 *
 * Patterns are SPDX identifiers matched case-insensitively, with a trailing `*`
 * acting as a prefix wildcard (`GPL-*` matches `GPL-3.0-or-later`).
 */
final class Policy
{
    public const MODE_OFF = 'off';
    public const MODE_WARN = 'warn';
    public const MODE_ENFORCE = 'enforce';

    /**
     * @param self::MODE_*  $mode
     * @param list<string>  $allow
     * @param list<string>  $deny
     * @param list<string>  $allowPackages
     */
    public function __construct(
        public readonly string $mode = self::MODE_WARN,
        public readonly array $allow = [],
        public readonly array $deny = [],
        public readonly array $allowPackages = [],
        public readonly bool $requireLicense = false,
    ) {}

    /** @param array<string, mixed> $extra the root package's `extra` array */
    public static function fromExtra(array $extra): self
    {
        $config = $extra['k2gl-license-gate'] ?? null;

        if (! is_array($config)) {
            return new self;
        }
        $mode = $config['mode'] ?? self::MODE_WARN;

        return new self(
            mode: in_array($mode, [self::MODE_OFF, self::MODE_WARN, self::MODE_ENFORCE], true) ? $mode : self::MODE_WARN,
            allow: self::stringList($config['allow'] ?? null),
            deny: self::stringList($config['deny'] ?? null),
            allowPackages: self::stringList($config['allow-packages'] ?? null),
            requireLicense: (bool) ($config['require-license'] ?? false),
        );
    }

    public function isOff(): bool
    {
        return $this->mode === self::MODE_OFF;
    }

    public function isEnforcing(): bool
    {
        return $this->mode === self::MODE_ENFORCE;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }
}
