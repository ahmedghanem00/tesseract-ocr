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
use GdImage;
use Intervention\Image\Exception\NotReadableException;
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

        $this->assertStringContainsString("tesseract v", $version);
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

        $this->assertEmpty($tesseract->getSupportedLanguages());
    }

    /**
     * @covers Tesseract::recognize
     * @return void
     */
    public function testRecognize(): void
    {
        $tesseract = new Tesseract();

        $expectedText = file_get_contents(__DIR__ . '/Data/paragraph1');
        $actualText = $tesseract->recognize(__DIR__ . "/Data/paragraph1.png", dpi: 120);

        $this->assertStringContainsString($expectedText, $actualText);
    }

    /**
     * @covers Tesseract::recognize
     * @return void
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
     */
    public function testRecognizeWithEmptyImage(): void
    {
        $tesseract = new Tesseract();

        $this->expectException(EmptyResultException::class);

        $tesseract->recognize($this->createEmptyImage());
    }

    /**
     * @return GdImage
     */
    private function createEmptyImage(): GdImage
    {
        $img = imagecreatetruecolor(400, 400);

        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, 120, 20, $bg);

        return $img;
    }

    /**
     * @covers Tesseract::recognize
     * @return void
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
