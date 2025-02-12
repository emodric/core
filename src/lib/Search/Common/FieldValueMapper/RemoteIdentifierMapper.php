<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Contracts\Core\Search\Field;
use Ibexa\Contracts\Core\Search\FieldType\RemoteIdentifierField;

/**
 * Common remote ID field value mapper.
 *
 * Currently behaves in the same way as StringMapper.
 *
 * @internal for internal use by Search engine field value mapper
 */
class RemoteIdentifierMapper extends StringMapper
{
    public function canMap(Field $field): bool
    {
        return $field->type instanceof RemoteIdentifierField;
    }
}

class_alias(RemoteIdentifierMapper::class, 'eZ\Publish\Core\Search\Common\FieldValueMapper\RemoteIdentifierMapper');
