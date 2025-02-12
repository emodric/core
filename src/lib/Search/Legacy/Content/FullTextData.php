<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Represents full text data of FullTextValue(s) for a Content object.
 */
class FullTextData extends ValueObject
{
    /**
     * Content object Id.
     *
     * @var int
     */
    public $id;

    /**
     * Content object content type Id.
     *
     * @var int
     */
    public $contentTypeId;

    /**
     * Content object section Id.
     *
     * @var int
     */
    public $sectionId;

    /**
     * Content object publication timestamp.
     *
     * @var int
     */
    public $published;

    /**
     * List of FullTextValue objects corresponding to content object fields (per translation).
     *
     * @var \Ibexa\Core\Search\Legacy\Content\FullTextValue[]
     */
    public $values;
}

class_alias(FullTextData::class, 'eZ\Publish\Core\Search\Legacy\Content\FullTextData');
