# composer-license-gate

A Composer plugin that checks your dependencies' licenses against an allow/deny
policy as they're installed — and, in enforce mode, **fails the install** on a
violation. It runs inside Composer's install/update lifecycle, so a disallowed
license (say, GPL creeping into a proprietary product through a transitive
dependency) is caught automatically in local installs and in CI, without a
separate command to remember.

Unlike report-only license checkers you invoke by hand, this is a gate: the
policy lives in `composer.json` and is enforced on every `composer install` and
`composer update`.

## Install

```bash
composer require --dev k2gl/composer-license-gate
```

## Configure

All configuration lives under `extra.k2gl-license-gate` in your root
`composer.json`:

```json
{
  "extra": {
    "k2gl-license-gate": {
      "mode": "enforce",
      "allow": ["MIT", "Apache-2.0", "BSD-2-Clause", "BSD-3-Clause", "ISC"],
      "deny": ["GPL-*", "AGPL-*"],
      "allow-packages": ["acme/legacy-thing"],
      "require-license": false
    }
  }
}
```

- **`mode`**
  - `warn` (default) — report violations but don't stop.
  - `enforce` — fail the install when a package violates the policy.
  - `off` — do nothing.
- **`allow`** — an allow-list. If set, a package is accepted only when *every*
  declared license matches one of these patterns. Takes precedence over `deny`.
- **`deny`** — a deny-list. A package is rejected if *any* declared license
  matches one of these.
- **`allow-packages`** — package names (`vendor/name`) exempt from the check, for
  the odd dependency you've reviewed and accepted.
- **`require-license`** — treat a package that declares no license as a violation.

Patterns are SPDX identifiers, matched case-insensitively, with a trailing `*` as
a prefix wildcard — `GPL-*` matches `GPL-2.0-only`, `GPL-3.0-or-later`, and so on.

Use **either** `allow` (a strict whitelist — safest) **or** `deny` (block known-bad
licenses, permit the rest). If both are set, `allow` wins.

## What you'll see

Under `enforce`, a violation aborts the install:

```
  ! license policy: acme/widget — license "GPL-3.0-or-later" is denied by policy
```

Under `warn`, the same line is printed but the install continues.

## Notes

- A dual-licensed package (e.g. `MIT OR GPL-3.0`) is read conservatively: if any
  of its licenses is disallowed, it's flagged. Accept it deliberately with
  `allow-packages` once you've confirmed you're using it under the allowed option.
- Checks run per package as Composer installs or updates it, so only the packages
  actually being changed are checked — a full audit happens naturally on a clean
  `composer install`.

## Requirements

- PHP 8.1+
- Composer 2 (`composer-plugin-api ^2.0`)

## License

MIT
