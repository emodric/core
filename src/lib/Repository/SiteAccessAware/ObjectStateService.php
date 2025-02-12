<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Repository\SiteAccessAware;

use Ibexa\Contracts\Core\Repository\LanguageResolver;
use Ibexa\Contracts\Core\Repository\ObjectStateService as ObjectStateServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateUpdateStruct;

/**
 * SiteAccess aware implementation of ObjectStateService injecting languages where needed.
 */
class ObjectStateService implements ObjectStateServiceInterface
{
    /** @var \Ibexa\Contracts\Core\Repository\ObjectStateService */
    protected $service;

    /** @var \Ibexa\Contracts\Core\Repository\LanguageResolver */
    protected $languageResolver;

    /**
     * Construct service object from aggregated service and LanguageResolver.
     *
     * @param \Ibexa\Contracts\Core\Repository\ObjectStateService $service
     * @param \Ibexa\Contracts\Core\Repository\LanguageResolver $languageResolver
     */
    public function __construct(
        ObjectStateServiceInterface $service,
        LanguageResolver $languageResolver
    ) {
        $this->service = $service;
        $this->languageResolver = $languageResolver;
    }

    public function createObjectStateGroup(ObjectStateGroupCreateStruct $objectStateGroupCreateStruct): ObjectStateGroup
    {
        return $this->service->createObjectStateGroup($objectStateGroupCreateStruct);
    }

    public function loadObjectStateGroup(int $objectStateGroupId, array $prioritizedLanguages = null): ObjectStateGroup
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadObjectStateGroup($objectStateGroupId, $prioritizedLanguages);
    }

    public function loadObjectStateGroupByIdentifier(
        string $objectStateGroupIdentifier,
        array $prioritizedLanguages = null
    ): ObjectStateGroup {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadObjectStateGroupByIdentifier($objectStateGroupIdentifier, $prioritizedLanguages);
    }

    public function loadObjectStateGroups(int $offset = 0, int $limit = -1, array $prioritizedLanguages = null): iterable
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadObjectStateGroups($offset, $limit, $prioritizedLanguages);
    }

    public function loadObjectStates(ObjectStateGroup $objectStateGroup, array $prioritizedLanguages = null): iterable
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadObjectStates($objectStateGroup, $prioritizedLanguages);
    }

    public function updateObjectStateGroup(ObjectStateGroup $objectStateGroup, ObjectStateGroupUpdateStruct $objectStateGroupUpdateStruct): ObjectStateGroup
    {
        return $this->service->updateObjectStateGroup($objectStateGroup, $objectStateGroupUpdateStruct);
    }

    public function deleteObjectStateGroup(ObjectStateGroup $objectStateGroup): void
    {
        $this->service->deleteObjectStateGroup($objectStateGroup);
    }

    public function createObjectState(ObjectStateGroup $objectStateGroup, ObjectStateCreateStruct $objectStateCreateStruct): ObjectState
    {
        return $this->service->createObjectState($objectStateGroup, $objectStateCreateStruct);
    }

    public function loadObjectState(int $stateId, array $prioritizedLanguages = null): ObjectState
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadObjectState($stateId, $prioritizedLanguages);
    }

    public function loadObjectStateByIdentifier(
        ObjectStateGroup $objectStateGroup,
        string $objectStateIdentifier,
        array $prioritizedLanguages = null
    ): ObjectState {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadObjectStateByIdentifier(
            $objectStateGroup,
            $objectStateIdentifier,
            $prioritizedLanguages
        );
    }

    public function updateObjectState(ObjectState $objectState, ObjectStateUpdateStruct $objectStateUpdateStruct): ObjectState
    {
        return $this->service->updateObjectState($objectState, $objectStateUpdateStruct);
    }

    public function setPriorityOfObjectState(ObjectState $objectState, int $priority): void
    {
        $this->service->setPriorityOfObjectState($objectState, $priority);
    }

    public function deleteObjectState(ObjectState $objectState): void
    {
        $this->service->deleteObjectState($objectState);
    }

    public function setContentState(ContentInfo $contentInfo, ObjectStateGroup $objectStateGroup, ObjectState $objectState): void
    {
        $this->service->setContentState($contentInfo, $objectStateGroup, $objectState);
    }

    public function getContentState(ContentInfo $contentInfo, ObjectStateGroup $objectStateGroup): ObjectState
    {
        return $this->service->getContentState($contentInfo, $objectStateGroup);
    }

    public function getContentCount(ObjectState $objectState): int
    {
        return $this->service->getContentCount($objectState);
    }

    public function newObjectStateGroupCreateStruct(string $identifier): ObjectStateGroupCreateStruct
    {
        return $this->service->newObjectStateGroupCreateStruct($identifier);
    }

    public function newObjectStateGroupUpdateStruct(): ObjectStateGroupUpdateStruct
    {
        return $this->service->newObjectStateGroupUpdateStruct();
    }

    public function newObjectStateCreateStruct(string $identifier): ObjectStateCreateStruct
    {
        return $this->service->newObjectStateCreateStruct($identifier);
    }

    public function newObjectStateUpdateStruct(): ObjectStateUpdateStruct
    {
        return $this->service->newObjectStateUpdateStruct();
    }
}

class_alias(ObjectStateService::class, 'eZ\Publish\Core\Repository\SiteAccessAware\ObjectStateService');
