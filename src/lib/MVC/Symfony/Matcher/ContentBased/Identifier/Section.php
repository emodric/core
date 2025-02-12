<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Matcher\ContentBased\Identifier;

use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\MVC\Symfony\Matcher\ContentBased\MultipleValued;
use Ibexa\Core\MVC\Symfony\View\ContentValueView;
use Ibexa\Core\MVC\Symfony\View\View;

class Section extends MultipleValued
{
    /**
     * Checks if a Location object matches.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     *
     * @return bool
     */
    public function matchLocation(Location $location)
    {
        $section = $this->repository->sudo(
            static function (Repository $repository) use ($location) {
                return $repository->getSectionService()->loadSection(
                    $location->getContentInfo()->sectionId
                );
            }
        );

        return isset($this->values[$section->identifier]);
    }

    /**
     * Checks if a ContentInfo object matches.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     *
     * @return bool
     */
    public function matchContentInfo(ContentInfo $contentInfo)
    {
        $section = $this->repository->sudo(
            static function (Repository $repository) use ($contentInfo) {
                return $repository->getSectionService()->loadSection(
                    $contentInfo->sectionId
                );
            }
        );

        return isset($this->values[$section->identifier]);
    }

    public function match(View $view)
    {
        if (!$view instanceof ContentValueView) {
            return false;
        }

        $contentInfo = $view->getContent()->contentInfo;
        $section = $this->repository->sudo(
            static function (Repository $repository) use ($contentInfo) {
                return $repository->getSectionService()->loadSection(
                    $contentInfo->sectionId
                );
            }
        );

        return isset($this->values[$section->identifier]);
    }
}

class_alias(Section::class, 'eZ\Publish\Core\MVC\Symfony\Matcher\ContentBased\Identifier\Section');
