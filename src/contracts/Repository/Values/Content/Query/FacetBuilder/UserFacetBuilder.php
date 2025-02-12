<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\FacetBuilder;

/**
 * Build a user facet.
 *
 * If provided the search service returns a UserFacet for the given type.
 *
 * @deprecated since eZ Platform 3.2.0, to be removed in Ibexa 4.0.0.
 */
class UserFacetBuilder extends FacetBuilder
{
    /**
     * Owner user.
     */
    public const OWNER = 'owner';

    /**
     * Owner user group.
     */
    public const GROUP = 'group';

    /**
     * Modifier.
     */
    public const MODIFIER = 'modifier';

    /**
     * The type of the user facet.
     *
     * @var string
     */
    public $type = self::OWNER;
}

class_alias(UserFacetBuilder::class, 'eZ\Publish\API\Repository\Values\Content\Query\FacetBuilder\UserFacetBuilder');
