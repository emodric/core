<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Bookmark\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Bookmark\Handler as BookmarkHandlerInterface;

class BookmarkHandler extends AbstractHandler implements BookmarkHandlerInterface
{
    private const BOOKMARK_IDENTIFIER = 'bookmark';
    private const LOCATION_IDENTIFIER = 'location';
    private const USER_IDENTIFIER = 'user';
    private const LOCATION_PATH_IDENTIFIER = 'location_path';

    /**
     * {@inheritdoc}
     */
    public function create(CreateStruct $createStruct): Bookmark
    {
        $this->logger->logCall(__METHOD__, [
            'createStruct' => $createStruct,
        ]);

        return $this->persistenceHandler->bookmarkHandler()->create($createStruct);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $bookmarkId): void
    {
        $this->logger->logCall(__METHOD__, [
            'id' => $bookmarkId,
        ]);

        $this->persistenceHandler->bookmarkHandler()->delete($bookmarkId);

        $this->cache->invalidateTags([
            $this->cacheIdentifierGenerator->generateTag(self::BOOKMARK_IDENTIFIER, [$bookmarkId]),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function loadByUserIdAndLocationId(int $userId, array $locationIds): array
    {
        return $this->getMultipleCacheItems(
            $locationIds,
            $this->cacheIdentifierGenerator->generateKey(self::BOOKMARK_IDENTIFIER, [$userId], true) . '-',
            function (array $missingIds) use ($userId) {
                $this->logger->logCall(__CLASS__ . '::loadByUserIdAndLocationId', [
                    'userId' => $userId,
                    'locationIds' => $missingIds,
                ]);

                return $this->persistenceHandler->bookmarkHandler()->loadByUserIdAndLocationId($userId, $missingIds);
            },
            function (Bookmark $bookmark) {
                $tags = [
                    $this->cacheIdentifierGenerator->generateTag(self::BOOKMARK_IDENTIFIER, [$bookmark->id]),
                    $this->cacheIdentifierGenerator->generateTag(self::LOCATION_IDENTIFIER, [$bookmark->locationId]),
                    $this->cacheIdentifierGenerator->generateTag(self::USER_IDENTIFIER, [$bookmark->userId]),
                ];

                $location = $this->persistenceHandler->locationHandler()->load($bookmark->locationId);
                $pathIds = $this->locationPathConverter->convertToPathIds($location->pathString);
                foreach ($pathIds as $pathId) {
                    $tags[] = $this->cacheIdentifierGenerator->generateTag(self::LOCATION_PATH_IDENTIFIER, [$pathId]);
                }

                return $tags;
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserBookmarks(int $userId, int $offset = 0, int $limit = -1): array
    {
        $this->logger->logCall(__METHOD__, [
            'userId' => $userId,
            'offset' => $offset,
            'limit' => $limit,
        ]);

        return $this->persistenceHandler->bookmarkHandler()->loadUserBookmarks($userId, $offset, $limit);
    }

    /**
     * {@inheritdoc}
     */
    public function countUserBookmarks(int $userId): int
    {
        $this->logger->logCall(__METHOD__, [
            'userId' => $userId,
        ]);

        return $this->persistenceHandler->bookmarkHandler()->countUserBookmarks($userId);
    }

    /**
     * {@inheritdoc}
     */
    public function locationSwapped(int $location1Id, int $location2Id): void
    {
        $this->logger->logCall(__METHOD__, [
            'location1Id' => $location1Id,
            'location2Id' => $location2Id,
        ]);

        // Cache clearing is already done by locationHandler
        $this->persistenceHandler->bookmarkHandler()->locationSwapped($location1Id, $location2Id);
    }
}

class_alias(BookmarkHandler::class, 'eZ\Publish\Core\Persistence\Cache\BookmarkHandler');
