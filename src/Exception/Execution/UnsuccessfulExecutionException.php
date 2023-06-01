<?php declare(strict_types=1);
/*
 * This file is part of the TesseractOCR package.
 *
 * (c) Ahmed Ghanem <ahmedghanem7361@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ahmedghanem00\TesseractOCR\Exception\Execution;

use RuntimeException;
use Symfony\Component\Process\Process;
use function Symfony\Component\String\u;

/**
 *
 */
class UnsuccessfulExecutionException extends RuntimeException
{
    /**
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        parent::__construct(
            sprintf(
                'Command ( %s ) has produced the error ( %s )',
                $process->getCommandLine(),
                $process->getErrorOutput()
            )
        );
    }

    /**
     * @param Process $process
     * @return static
     */
    public static function newFromProcess(Process $process): static
    {
        $stderr = u($process->getErrorOutput());

        $class = match (true) {
            $stderr->containsAny("Failed loading language") => UnsupportedLanguageException::class,
            $stderr->containsAny(['dpi is outside', 'Estimating resolution']) => WrongDPIException::class,
            $stderr->containsAny("Could not set option") => InvalidConfigException::class,

            default => self::class
        };

        return new $class($process);
    }
}
