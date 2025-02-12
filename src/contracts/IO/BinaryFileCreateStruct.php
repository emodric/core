<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Contracts\Core\IO;

/**
 * Create struct for BinaryFile objects.
 */
class BinaryFileCreateStruct
{
    /**
     * File size, in bytes.
     *
     * @var int
     */
    public $size;

    /**
     * File modification time.
     *
     * @var \DateTime
     */
    public $mtime;

    /**
     * The file's mime type
     * If not provided, will be auto-detected by the IOService
     * Example: text/xml.
     *
     * @var string
     */
    public $mimeType;

    /**
     * Unique identifier for this file
     * Ex: images/media/images/ibexa-logo/209-1-eng-GB/Ibexa-Logo.gif,
     *     or original/application/2b042138835bb5f48beb9c9df6e86de4.pdf.
     *
     * @var mixed
     */
    public $id;

    /** @var resource */
    private $inputStream;

    /**
     * Returns the file's input resource.
     *
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    /**
     * Sets the file's input resource.
     *
     * @param resource $inputStream
     */
    public function setInputStream($inputStream)
    {
        $this->inputStream = $inputStream;
    }
}

class_alias(BinaryFileCreateStruct::class, 'eZ\Publish\SPI\IO\BinaryFileCreateStruct');
