<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Locale;

use Psr\Log\LoggerInterface;

class LocaleConverter implements LocaleConverterInterface
{
    /**
     * Conversion map, indexed by Ibexa locale.
     * See locale.yml.
     *
     * @var array
     */
    private $conversionMap;

    /**
     * Conversion map, indexed by POSIX locale.
     *
     * @var array
     */
    private $reverseConversionMap;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(array $conversionMap, LoggerInterface $logger)
    {
        $this->conversionMap = $conversionMap;
        $this->reverseConversionMap = array_flip($conversionMap);
        $this->logger = $logger;
    }

    /**
     * Converts a locale in Ibexa internal format to POSIX format.
     * Returns null if conversion cannot be made.
     *
     * @param string $ezpLocale
     *
     * @return string|null
     */
    public function convertToPOSIX($ezpLocale)
    {
        if (!isset($this->conversionMap[$ezpLocale])) {
            $this->logger->warning("Could not convert locale '$ezpLocale' to POSIX format. Please check your locale configuration in ezplatform.yml");

            return;
        }

        return $this->conversionMap[$ezpLocale];
    }

    /**
     * Converts a locale in POSIX format to Ibexa internal format.
     * Returns null if conversion cannot be made.
     *
     * @param string $posixLocale
     *
     * @return string|null
     */
    public function convertToEz($posixLocale)
    {
        if (!isset($this->reverseConversionMap[$posixLocale])) {
            $this->logger->warning("Could not convert locale '$posixLocale' to Ibexa format. Please check your locale configuration in ezplatform.yml");

            return;
        }

        return $this->reverseConversionMap[$posixLocale];
    }
}

class_alias(LocaleConverter::class, 'eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverter');
