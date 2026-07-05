<?php

declare(strict_types=1);

namespace K2gl\ComposerLicenseGate;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Plugin\PluginInterface;
use K2gl\ComposerLicenseGate\Exception\LicenseViolationException;

/**
 * Checks each package's declared license against the policy as Composer installs
 * or updates it, reporting the result — and, under `enforce` mode, failing the
 * install on a violation.
 *
 * Configure via `extra.k2gl-license-gate` (see {@see Policy}).
 */
final class Plugin implements PluginInterface, EventSubscriberInterface
{
    private IOInterface $io;

    private Policy $policy;

    private LicenseChecker $checker;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
        $this->policy = Policy::fromExtra($composer->getPackage()->getExtra());
        $this->checker = new LicenseChecker($this->policy);
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}

    public function uninstall(Composer $composer, IOInterface $io): void {}

    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [
            PackageEvents::POST_PACKAGE_INSTALL => 'onPackage',
            PackageEvents::POST_PACKAGE_UPDATE => 'onPackage',
        ];
    }

    public function onPackage(PackageEvent $event): void
    {
        if ($this->policy->isOff()) {
            return;
        }
        $operation = $event->getOperation();
        $package = match (true) {
            $operation instanceof InstallOperation => $operation->getPackage(),
            $operation instanceof UpdateOperation => $operation->getTargetPackage(),
            default => null,
        };

        if ($package === null) {
            return;
        }
        // getLicense() lives on CompletePackageInterface, not the base PackageInterface.
        $licenses = $package instanceof CompletePackageInterface ? array_values($package->getLicense()) : [];
        $this->report($package->getName(), $this->checker->check($package->getName(), $licenses));
    }

    private function report(string $package, LicenseResult $result): void
    {
        if (! $result->isViolation()) {
            if ($result->message !== '') {
                $this->io->write(sprintf('  <info>✓ license</info> %s (%s)', $package, $result->message), true, IOInterface::VERBOSE);
            }

            return;
        }
        $message = sprintf('%s — %s', $package, $result->message);

        if ($this->policy->isEnforcing()) {
            throw new LicenseViolationException($message);
        }
        $this->io->writeError(sprintf('  <warning>! license policy: %s</warning>', $message));
    }
}
