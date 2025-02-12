<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\Imagine\Filter;

/**
 * Base implementation of FilterInterface, handling options.
 */
abstract class AbstractFilter implements FilterInterface
{
    /** @var array */
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function setOption($optionName, $value)
    {
        $this->options[$optionName] = $value;
    }

    public function getOption($optionName, $defaultValue = null)
    {
        return isset($this->options[$optionName]) ? $this->options[$optionName] : $defaultValue;
    }

    public function hasOption($optionName)
    {
        return isset($this->options[$optionName]);
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }
}

class_alias(AbstractFilter::class, 'eZ\Bundle\EzPublishCoreBundle\Imagine\Filter\AbstractFilter');
