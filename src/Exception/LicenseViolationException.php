<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate\Exception;

use RuntimeException;

/** A package's license violates the policy while running in enforce mode. */
final class LicenseViolationException extends RuntimeException {}
