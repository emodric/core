<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a field of a content object.
 *
 * @property-read mixed $id an internal id of the field
 * @property-read string $fieldDefIdentifier the field definition identifier
 * @property-read mixed $value the value of the field
 * @property-read string $languageCode the language code of the field
 * @property-read string $fieldTypeIdentifier field type identifier
 */
class Field extends ValueObject
{
    /**
     * The field id.
     *
     * @todo may be not needed
     *
     * @var mixed
     */
    protected $id;

    /**
     * The field definition identifier.
     *
     * @var string
     */
    protected $fieldDefIdentifier;

    /**
     * A field type value or a value type which can be converted by the corresponding field type.
     *
     * @var mixed
     */
    protected $value;

    /**
     * the language code.
     *
     * @var string
     */
    protected $languageCode;

    /**
     * Field type identifier.
     *
     * @var string
     */
    protected $fieldTypeIdentifier;
}

class_alias(Field::class, 'eZ\Publish\API\Repository\Values\Content\Field');
