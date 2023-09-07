# tesseract-ocr

A PHP wrapper for Tesseract-OCR binary.

Originally inspired from [ddeboer/tesseract](https://github.com/ddeboer/tesseract) with added features + some
Improvements.

## Installation

````
$ composer require ahmedghanem00/tesseract-ocr
````

# Usage

if the tesseract is added to your path, You can just do:

````php
$tesseract = new \ahmedghanem00\TesseractOCR\Tesseract();
````

Otherwise, You can do:

````php
$tesseract = new \ahmedghanem00\TesseractOCR\Tesseract("path/to/binary/location");
# OR
$tesseract->setBinaryPath("path/to/binary/location");
````

To specify the tesseract process timeout:

````php
$tesseract = new \ahmedghanem00\TesseractOCR\Tesseract(processTimeout: 3);
# OR
$tesseract->setProcessTimeout(2.5);
````

To specify a custom tessdata-dir:

````php
$tesseract->setTessDataDirPath("path/to/data/dir")
````

To reset tessdata-dir to default:

````php
$tesseract->resetTessDataDirPath();
````

To get version of the binary:

````php
$version = $tesseract->getVersion();
````

To get all the supported languages:

````php
$languages = $tesseract->getSupportedLanguages();
````

To OCR an Image:

````php
$result = $tesseract->recognize("test.png");
##
$result = $tesseract->recognize("https://example.com/test.png");
````

Thanks to the [Intervention/image](https://github.com/Intervention/image) package. The recognize method can accept
different sources for an image:

    - Path of the image in filesystem.
    - URL of an image (allow_url_fopen must be enabled).
    - Binary image data.
    - Data-URL encoded image data.
    - Base64 encoded image data.
    - PHP resource of type gd
    - Imagick instance
    - Intervention\Image\Image instance
    - SplFileInfo instance (To handle Laravel file uploads via Symfony\Component\HttpFoundation\File\UploadedFile)

To Specify the language(s):

````php
$result = $tesseract->recognize("test.png", langs: ["eng", "ara"]);
````

To specify the Page-Segmentation-Model (PSM):

````php
use ahmedghanem00\TesseractOCR\Enum\PSM;

# using PSM enum
$result = $tesseract->recognize("test.png", psm: PSM::SINGLE_BLOCK);
# OR by using id directly
$result = $tesseract->recognize("test.png", psm: 3);
````

To specify the OCR-Engine-Mode (OEM):

````php
use ahmedghanem00\TesseractOCR\Enum\OEM;

# using OEM enum
$result = $tesseract->recognize("test.png", oem: OEM::LEGACY_WITH_LSTM);
# OR by using id directly
$result = $tesseract->recognize("test.png", oem: 3);
````

To specify the DPI of the input image:

````php
$result = $tesseract->recognize("test.png", dpi: 200);
````

To make the recognize method output the result as a searchable PDF instead of raw text:

````php
$pdfBinaryData = $tesseract->recognize("test.png", outputAsPDF: true);

file_put_contents("result.pdf", $pdfBinaryData)
````

To specify words-file or patterns-file:

````php
$result = $tesseract->recognize("test.png", wordsFilePath: "/path/to/file");
# OR
$result = $tesseract->recognize("test.png", patternsFilePath: "/path/to/file");
````

To set a config parameters:

````php
use ahmedghanem00\TesseractOCR\ConfigBag;

$config = ConfigBag::new()
    ->setParameter("tessedit_char_whitelist", "abcrety")
    ->setParameter("textord_pitch_range", 3);

$result = $tesseract->recognize("test.png", config: $config);
````

You can also run `tesseract --print-parameters` to see the list of available config parameters.

# Licence

Package is licensed under the [MIT License](http://opensource.org/licenses/MIT).
