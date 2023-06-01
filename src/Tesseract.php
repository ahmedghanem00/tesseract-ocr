<?php declare(strict_types=1);
/*
 * This file is part of the TesseractOCR package.
 *
 * (c) Ahmed Ghanem <ahmedghanem7361@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace ahmedghanem00\TesseractOCR;

use ahmedghanem00\TesseractOCR\Enum\OEM;
use ahmedghanem00\TesseractOCR\Enum\PSM;
use ahmedghanem00\TesseractOCR\Exception\EmptyResultException;
use ahmedghanem00\TesseractOCR\Exception\Execution\UnsuccessfulExecutionException;
use ahmedghanem00\TesseractOCR\Exception\ParseException;
use Exception;
use Intervention\Image\Image;
use Intervention\Image\ImageManagerStatic;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

/**
 *
 */
class Tesseract
{
    /**
     * @var string
     */
    private string $binaryPath;

    /**
     * @var float
     */
    private float $processTimeout;

    /**
     * @var string|null
     */
    private ?string $tessDataDirPath = null;

    /**
     * @param string $binaryPath
     * @param float $processTimeout
     */
    public function __construct(string $binaryPath = 'tesseract', float $processTimeout = 20)
    {
        $this->setBinaryPath($binaryPath);
        $this->setProcessTimeout($processTimeout);
    }

    /**
     * @return string
     */
    public function getBinaryPath(): string
    {
        return $this->binaryPath;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setBinaryPath(string $path): self
    {
        $this->binaryPath = $path;

        return $this;
    }

    /**
     * @return float
     */
    public function getProcessTimeout(): float
    {
        return $this->processTimeout;
    }

    /**
     * @param float $timeout
     * @return $this
     */
    public function setProcessTimeout(float $timeout): self
    {
        $this->processTimeout = $timeout;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getTessDataDirPath(): ?string
    {
        return $this->tessDataDirPath;
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setTessDataDirPath(string $path): self
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(
                "The provided tess-data-dir ( $path ) does not exist or is not a valid directory"
            );
        }

        $this->tessDataDirPath = $path;

        return $this;
    }

    /**
     * @return $this
     */
    public function resetTessDataDirPath(): self
    {
        $this->tessDataDirPath = null;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        $stdout = $this->execute(['--version']);

        if (!$versionLine = @explode(PHP_EOL, $stdout)[0]) {
            throw new ParseException("Couldn't extract the version line");
        }

        return $versionLine;
    }

    /**
     * @param array $arguments
     * @return string
     */
    private function execute(array $arguments): string
    {
        $process = new Process($this->prepareArguments($arguments), timeout: $this->processTimeout);
        $process->run();

        if (!$process->isSuccessful() || $process->getErrorOutput()) {
            throw UnsuccessfulExecutionException::newFromProcess($process);
        }

        if (empty(preg_replace("/\s+/", "", $process->getOutput()))) {
            throw new EmptyResultException($process);
        }

        return $process->getOutput();
    }

    /**
     * @param array $arguments
     * @return array
     */
    private function prepareArguments(array $arguments): array
    {
        $baseArguments = [$this->binaryPath];

        if ($this->tessDataDirPath) {
            $baseArguments[] = "--tessdata-dir";
            $baseArguments[] = $this->tessDataDirPath;
        }

        return array_merge($baseArguments, $arguments);
    }

    /**
     * @return array
     */
    public function getSupportedLanguages(): array
    {
        $stdout = $this->execute(["--list-langs"]);

        $stdoutLines = array_filter(explode(PHP_EOL, $stdout));
        array_shift($stdoutLines); // remove the header line

        return $stdoutLines;
    }

    /**
     * @param mixed $imageSource
     * @param array $langs
     * @param PSM|int|null $psm Page segmentation mode
     * @param OEM|int|null $oem OCR engine mode
     * @param int|null $dpi
     * @param string|null $wordsFilePath
     * @param string|null $patternsFilePath
     * @param bool $outputAsPDF
     * @param ConfigBag|null $config
     * @return string
     * @throws Exception
     */
    public function recognize(
        mixed     $imageSource,
        array     $langs = [],
        PSM|int   $psm = null,
        OEM|int   $oem = null,
        int       $dpi = null,
        string    $wordsFilePath = null,
        string    $patternsFilePath = null,
        bool      $outputAsPDF = false,
        ConfigBag $config = null
    ): string
    {
        if (extension_loaded("imagick")) {
            if ($imageSource instanceof Image && $imageSource->getCore() instanceof \Imagick) {
                $imageSource = $imageSource->getCore()->getImageBlob();
            } else if ($imageSource instanceof \Imagick) {
                $imageSource = $imageSource->getImageBlob();
            }
        }

        $image = ImageManagerStatic::make($imageSource);

        if (is_string($imageSource) && file_exists($imageSource)) {
            $imagePath = $imageSource;
            $isImagePathTemporary = false;
        } else {
            $imagePath = tempnam(sys_get_temp_dir(), "TessImg");
            $image->save($imagePath, null, "png");
            $isImagePathTemporary = true;
        }

        try {
            return $this->doRecognize($imagePath, $langs, $psm, $oem, $dpi, $wordsFilePath, $patternsFilePath, $outputAsPDF, $config);
        } finally {
            if ($isImagePathTemporary) {
                unlink($imagePath);
            }
        }
    }

    /**
     * @param string $imagePath
     * @param array $langs
     * @param PSM|int|null $psm Page segmentation mode
     * @param OEM|int|null $oem OCR engine mode
     * @param int|null $dpi
     * @param string|null $wordsFilePath
     * @param string|null $patternsFilePath
     * @param bool $outputAsPDF
     * @param ConfigBag|null $config
     * @return string
     */
    public function doRecognize(
        string       $imagePath,
        array        $langs,
        PSM|int|null $psm,
        OEM|int|null $oem,
        ?int         $dpi,
        ?string      $wordsFilePath,
        ?string      $patternsFilePath,
        bool         $outputAsPDF,
        ?ConfigBag   $config
    ): string
    {
        $arguments = [$imagePath, "stdout"];

        if ($langs) {
            $arguments[] = "-l";
            $arguments[] = implode("+", $langs);
        }

        if (isset($psm)) {
            $psm = $psm instanceof PSM ? $psm : PSM::from($psm);

            $arguments[] = "--psm";
            $arguments[] = $psm->value;
        }

        if (isset($oem)) {
            $oem = $oem instanceof OEM ? $oem : OEM::from($oem);

            $arguments[] = "--oem";
            $arguments[] = $oem->value;
        }

        if ($dpi) {
            $arguments[] = "--dpi";
            $arguments[] = $dpi;
        }

        if ($wordsFilePath) {
            if (!file_exists($wordsFilePath)) {
                throw new InvalidArgumentException(
                    "The provided words-file ( $wordsFilePath ) does not exist"
                );
            }

            $arguments[] = "--user-words";
            $arguments[] = $wordsFilePath;
        }

        if ($patternsFilePath) {
            if (!file_exists($patternsFilePath)) {
                throw new InvalidArgumentException(
                    "The provided patterns-file ( $patternsFilePath ) does not exist"
                );
            }

            $arguments[] = "--user-patterns";
            $arguments[] = $patternsFilePath;
        }

        if ($outputAsPDF) {
            $arguments[] = "pdf";
        }

        if ($config) {
            $arguments = array_merge($arguments, $config->buildToArguments());
        }

        return $this->execute($arguments);
    }
}
