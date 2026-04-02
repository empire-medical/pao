<?php

declare(strict_types=1);

namespace Pao\Drivers\Phpunit;

use Pao\Drivers\Starter as BaseStarter;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final class Starter extends BaseStarter
{
    public function start(): void
    {
        $this->registerNullFilter();

        /** @var list<string> $serverArgv */
        $serverArgv = $_SERVER['argv'];

        $argv = $this->ensureJunitLog($serverArgv);

        // PHPUnit 12+ supports --extension CLI arg; PHPUnit 10-11 do not.
        // For PHPUnit 10-11, the extension must be registered via phpunit.xml
        // or the subscriber registered directly on the event facade.
        // We register the subscriber directly since the facade isn't sealed yet
        // at autoload time. -- Claude
        if ($this->phpunitSupportsExtensionArg()) {
            $argv[] = '--extension';
            $argv[] = Extension::class;
        } else {
            \PHPUnit\Event\Facade::instance()->registerSubscriber(
                new Subscribers\TestRunnerFinishedSubscriber,
            );
        }

        $_SERVER['argv'] = $argv;
    }

    private function phpunitSupportsExtensionArg(): bool
    {
        if (! class_exists(\PHPUnit\Runner\Version::class)) {
            return false;
        }

        return version_compare(\PHPUnit\Runner\Version::id(), '12.0.0', '>=');
    }
}
