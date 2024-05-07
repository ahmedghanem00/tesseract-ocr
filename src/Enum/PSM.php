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

namespace ahmedghanem00\TesseractOCR\Enum;

/**
 *
 */
enum PSM: int
{
    case OSD_ONLY = 0;
    case AUTOMATIC_WITH_OSD = 1;
    case AUTOMATIC_WITHOUT_OSD = 2;
    case FULLY_AUTOMATIC_WITHOUT_OSD = 3;
    case SINGLE_COLUMN = 4;
    case SINGLE_BLOCK_VERTICAL = 5;
    case SINGLE_BLOCK = 6;
    case SINGLE_TEXT_LINE = 7;
    case SINGLE_WORD = 8;
    case SINGLE_WORD_CIRCLE = 9;
    case SINGLE_CHARACTER = 10;
    case SPARSE_TEXT = 11;
    case SPARSE_TEXT_WITH_OSD = 12;
    case RAW_LINE = 13;
}
