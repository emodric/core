<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Repository\IdManager;

use Ibexa\Tests\Integration\Core\Repository\IdManager;

/**
 * ID manager for the basic PHP usage of the Public API.
 */
class Php extends IdManager
{
    /**
     * Generates a repository specific ID.
     *
     * Generates a repository specific ID for an object of $type from the
     * database ID $rawId.
     *
     * @param string $type
     * @param mixed $rawId
     *
     * @return mixed
     */
    public function generateId($type, $rawId)
    {
        return $rawId;
    }

    /**
     * Parses the given $id for $type into its raw form.
     *
     * Takes a repository specific $id of $type and returns the raw database ID
     * for the object.
     *
     * @param string $type
     * @param mixed $id
     *
     * @return mixed
     */
    public function parseId($type, $id)
    {
        return $id;
    }
}

class_alias(Php::class, 'eZ\Publish\API\Repository\Tests\IdManager\Php');
