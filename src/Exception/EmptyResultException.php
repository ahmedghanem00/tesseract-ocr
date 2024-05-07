<?php

declare(strict_types=1);
/*
 * This file is part of the TesseractOCR package.
 *
 * (c) Ahmed Ghanem <ahmedghanem7361@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ahmedghanem00\TesseractOCR\Exception;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 *
 */
class EmptyResultException extends RuntimeException
{
    /**
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        parent::__construct(
            sprintf(
                'Command ( %s ) has executed but produced no output',
                $process->getCommandLine()
            )
        );
    }
}
