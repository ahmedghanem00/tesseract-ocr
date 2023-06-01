<?php declare(strict_types=1);
/*
 * This file is part of the TesseractOCR package.
 *
 * (c) Ahmed Ghanem <ahmedghanem7361@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ahmedghanem00\TesseractOCR\Tests\unit;

use ahmedghanem00\TesseractOCR\ConfigBag;
use ahmedghanem00\TesseractOCR\Exception\EmptyResultException;
use ahmedghanem00\TesseractOCR\Exception\Execution\InvalidConfigException;
use ahmedghanem00\TesseractOCR\Exception\Execution\UnsuccessfulExecutionException;
use ahmedghanem00\TesseractOCR\Exception\Execution\UnsupportedLanguageException;
use ahmedghanem00\TesseractOCR\Exception\Execution\WrongDPIException;
use ahmedghanem00\TesseractOCR\Tesseract;
use Exception;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;
use Smalot\PdfParser\Parser;
use stdClass;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

/**
 *
 */
class TesseractTest extends TestCase
{
    /**
     * @covers Tesseract::getVersion
     * @return void
     */
    public function testGetVersion(): void
    {
        $tesseract = new Tesseract();

        $version = $tesseract->getVersion();

        $this->assertStringContainsString("tesseract ", $version); # Not very good assertion but it just a temp for now
    }

    /**
     * @covers Tesseract::getVersion
     * @return void
     */
    public function testGetVersionWithInvalidBinaryPath(): void
    {
        $tesseract = new Tesseract("/not/exist/path");

        $this->expectException(UnsuccessfulExecutionException::class);

        $tesseract->getVersion();
    }

    /**
     * @covers Tesseract::getSupportedLanguages
     * @return void
     */
    public function testGetSupportedLanguages(): void
    {
        $tesseract = new Tesseract();

        $this->assertIsArray($tesseract->getSupportedLanguages());
    }

    /**
     * @covers Tesseract::getSupportedLanguages
     * @return void
     */
    public function testGetSupportedLanguagesWithWrongTessDataPath(): void
    {
        $tesseract = new Tesseract();

        $tesseract->setTessDataDirPath(__DIR__);

        if (PHP_OS === 'Linux') {
            $this->expectException(UnsupportedLanguageException::class);
            $tesseract->getSupportedLanguages();
        } else {
            $this->assertEmpty($tesseract->getSupportedLanguages());
        }
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognize(): void
    {
        $tesseract = new Tesseract();

        $actualText = $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 120);

        $this->assertStringContainsString("This is text contained in the first paragraph", $actualText);
        $this->assertStringContainsString("This is text contained in the second paragraph", $actualText);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithWrongTessDataPath(): void
    {
        $tesseract = new Tesseract();

        $tesseract->setTessDataDirPath(__DIR__);
        $this->expectException(UnsupportedLanguageException::class);

        $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 120);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithMisConfiguredDPI(): void
    {
        $tesseract = new Tesseract();

        $this->expectException(WrongDPIException::class);

        $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 20); # out of range - lower
        $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 5000); # out of range - higher
        $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 90); # in range but less than the estimated dpi of the input image
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithInvalidConfig(): void
    {
        $tesseract = new Tesseract();

        $this->expectException(InvalidConfigException::class);
        $config = ConfigBag::new()->setParameter("wrong-param", "aaaaaa");

        $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 120, config: $config);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithTimeout(): void
    {
        $tesseract = new Tesseract();

        $tesseract->setProcessTimeout(0.1); # too low timeout
        $this->expectException(ProcessTimedOutException::class);

        $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 120);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithOnlineImage(): void
    {
        $tesseract = new Tesseract();

        $actualText = $tesseract->recognize("https://i.ibb.co/tHbyrHr/Untitled-1.jpg");

        $this->assertStringContainsString("Play Run Work", $actualText);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithOutputAsPDF(): void
    {
        $tesseract = new Tesseract();

        $pdfBinary = $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 120, outputAsPDF: true);
        $pdf = (new Parser())->parseContent($pdfBinary);

        $this->assertIsArray($pdf->getPages());
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithEmptyGDImage(): void
    {
        $tesseract = new Tesseract();

        $this->expectException(EmptyResultException::class);
        $emptyImage = (new ImageManager(['driver' => 'gd']))->canvas(400, 400);

        $tesseract->recognize($emptyImage, dpi: 70);
        $tesseract->recognize($emptyImage->getCore(), dpi: 70);
        $tesseract->recognize($emptyImage->getEncoded(), dpi: 70);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithEmptyImagickImage(): void
    {
        $tesseract = new Tesseract();

        $this->expectException(EmptyResultException::class);
        $emptyImage = (new ImageManager(['driver' => 'imagick']))->canvas(400, 400);

        $tesseract->recognize($emptyImage, dpi: 70);
        $tesseract->recognize($emptyImage->getCore(), dpi: 70);
        $tesseract->recognize($emptyImage->getEncoded(), dpi: 70);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     * @throws Exception
     */
    public function testRecognizeWithInvalidImageSource(): void
    {
        $tesseract = new Tesseract();

        $this->expectException(NotReadableException::class);

        $tesseract->recognize("wrong-image-data");
        $tesseract->recognize([]);
        $tesseract->recognize(new stdClass());
        $tesseract->recognize(100);
    }
}
