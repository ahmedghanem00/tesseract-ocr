<?php declare(strict_types=1);
/*
 * This file is part of the TesseractOCR package.
 *
 * (c) Ahmed Ghanem <ahmedghanem7361@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ahmedghanem00\TesseractOCR\Enum;

/**
 *
 */
enum OEM: int
{
    case LEGACY = 0;
    case LSTM = 1;
    case LEGACY_WITH_LSTM = 2;
    case AUTOMATIC_AVAILABLE = 3;
}
