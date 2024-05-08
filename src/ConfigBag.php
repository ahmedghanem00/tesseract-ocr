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

namespace ahmedghanem00\TesseractOCR;

use InvalidArgumentException;

/**
 *
 */
class ConfigBag
{
    /**
     * @var array
     */
    private array $parameters = [];

    /**
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setParameter(string $key, string $value): self
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getParameter(string $key): string
    {
        if (!isset($this->parameters[$key])) {
            throw new InvalidArgumentException("The targeted key ( $key ) cannot be fetched as it does not exist");
        }

        return $this->parameters[$key];
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeParameter(string $key): self
    {
        if (!isset($this->parameters[$key])) {
            throw new InvalidArgumentException("The targeted key ( $key ) cannot be deleted as it does not exist");
        }

        unset($this->parameters[$key]);

        return $this;
    }

    /**
     * @return array
     */
    public function buildToArguments(): array
    {
        $arguments = [];

        foreach ($this->parameters as $key => $value) {
            $arguments[] = "-c";
            $arguments[] = "$key=$value";
        }

        return $arguments;
    }
}
