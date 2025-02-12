<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class represents a location in the repository.
 *
 * @property-read \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo calls getContentInfo()
 * @property-read int $contentId calls getContentInfo()->id
 * @property-read int $id the id of the location
 * @property-read int $priority Position of the Location among its siblings when sorted using priority
 * @property-read bool $hidden Indicates that the Location entity is hidden (explicitly or hidden by content).
 * @property-read bool $invisible Indicates that the Location is not visible, being either marked as hidden itself, or implicitly hidden by its Content or an ancestor Location
 * @property-read bool $explicitlyHidden Indicates that the Location entity has been explicitly marked as hidden.
 * @property-read string $remoteId a global unique id of the content object
 * @property-read int $parentLocationId the id of the parent location
 * @property-read string $pathString the path to this location e.g. /1/2/4/23 where 23 is current id.
 * @property-read array $path Same as $pathString but as array, e.g. [ 1, 2, 4, 23 ]
 * @property-read int $depth Depth location has in the location tree
 * @property-read int $sortField Specifies which property the child locations should be sorted on. Valid values are found at {@link Location::SORT_FIELD_*}
 * @property-read int $sortOrder Specifies whether the sort order should be ascending or descending. Valid values are {@link Location::SORT_ORDER_*}
 */
abstract class Location extends ValueObject
{
    // @todo Rename these to better fit current naming, also reuse these in Persistence or copy the change over.
    public const SORT_FIELD_PATH = 1;
    public const SORT_FIELD_PUBLISHED = 2;
    public const SORT_FIELD_MODIFIED = 3;
    public const SORT_FIELD_SECTION = 4;
    public const SORT_FIELD_DEPTH = 5;
    public const SORT_FIELD_CLASS_IDENTIFIER = 6;
    public const SORT_FIELD_CLASS_NAME = 7;
    public const SORT_FIELD_PRIORITY = 8;
    public const SORT_FIELD_NAME = 9;

    /**
     * @deprecated
     */
    public const SORT_FIELD_MODIFIED_SUBNODE = 10;

    public const SORT_FIELD_NODE_ID = 11;
    public const SORT_FIELD_CONTENTOBJECT_ID = 12;

    public const SORT_ORDER_DESC = 0;
    public const SORT_ORDER_ASC = 1;

    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;

    /**
     * Map for Location sort fields to their respective SortClauses.
     *
     * Those not here (class name/identifier and modified subnode) are
     * missing/deprecated and will most likely be removed in the future.
     */
    public const SORT_FIELD_MAP = [
        self::SORT_FIELD_PATH => SortClause\Location\Path::class,
        self::SORT_FIELD_PUBLISHED => SortClause\DatePublished::class,
        self::SORT_FIELD_MODIFIED => SortClause\DateModified::class,
        self::SORT_FIELD_SECTION => SortClause\SectionIdentifier::class,
        self::SORT_FIELD_DEPTH => SortClause\Location\Depth::class,
        //self::SORT_FIELD_CLASS_IDENTIFIER => false,
        //self::SORT_FIELD_CLASS_NAME => false,
        self::SORT_FIELD_PRIORITY => SortClause\Location\Priority::class,
        self::SORT_FIELD_NAME => SortClause\ContentName::class,
        //self::SORT_FIELD_MODIFIED_SUBNODE => false,
        self::SORT_FIELD_NODE_ID => SortClause\Location\Id::class,
        self::SORT_FIELD_CONTENTOBJECT_ID => SortClause\ContentId::class,
    ];

    /**
     * Map for Location sort order to their respective Query SORT constants.
     */
    public const SORT_ORDER_MAP = [
        self::SORT_ORDER_DESC => Query::SORT_DESC,
        self::SORT_ORDER_ASC => Query::SORT_ASC,
    ];

    /**
     * Location ID.
     *
     * @var int Location ID.
     */
    protected $id;

    /**
     * the status of the location.
     *
     * a location gets the status DRAFT on newly created content which is not published. When content is published the
     * location gets the status STATUS_PUBLISHED
     *
     * @var int
     */
    public $status = self::STATUS_PUBLISHED;

    /**
     * Location priority.
     *
     * Position of the Location among its siblings when sorted using priority
     * sort order.
     *
     * @var int
     */
    protected $priority;

    /**
     * Indicates that the Location entity is hidden (explicitly or hidden by content).
     *
     * @var bool
     */
    protected $hidden;

    /**
     * Indicates that the Location is not visible, being either marked as hidden itself,
     * or implicitly hidden by its Content or an ancestor Location.
     *
     * @var bool
     */
    protected $invisible;

    /**
     * Indicates that the Location entity has been explicitly marked as hidden.
     *
     * @var bool
     */
    protected $explicitlyHidden;

    /**
     * Remote ID.
     *
     * A universally unique identifier.
     *
     * @var string
     */
    protected $remoteId;

    /**
     * Parent ID.
     *
     * @var int Location ID.
     */
    protected $parentLocationId;

    /**
     * The materialized path of the location entry, eg: /1/2/.
     *
     * @var string
     */
    protected $pathString;

    /**
     * Depth location has in the location tree.
     *
     * @var int
     */
    protected $depth;

    /**
     * Specifies which property the child locations should be sorted on.
     *
     * Valid values are found at {@link Location::SORT_FIELD_*}
     *
     * @var int
     */
    protected $sortField;

    /**
     * Specifies whether the sort order should be ascending or descending.
     *
     * Valid values are {@link Location::SORT_ORDER_*}
     *
     * @var int
     */
    protected $sortOrder;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content */
    protected $content;

    /**
     * Returns the content info of the content object of this location.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     */
    abstract public function getContentInfo(): ContentInfo;

    /**
     * Return the parent location of of this location.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location|null
     */
    abstract public function getParentLocation(): ?Location;

    /**
     * Returns true if current location is a draft.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * Get SortClause objects built from Locations's sort options.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException If sort field has a deprecated/unsupported value which does not have a Sort Clause.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[]
     */
    public function getSortClauses(): array
    {
        $map = self::SORT_FIELD_MAP;
        if (!isset($map[$this->sortField])) {
            throw new NotImplementedException(
                "Sort Clause not implemented for Location sort Field {$this->sortField}"
            );
        }

        $sortClause = new $map[$this->sortField]();
        $sortClause->direction = self::SORT_ORDER_MAP[$this->sortOrder];

        return [$sortClause];
    }
}

class_alias(Location::class, 'eZ\Publish\API\Repository\Values\Content\Location');
