<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Persistence\Cache;

use Ibexa\Contracts\Core\Persistence\URL\Handler as URLHandlerInterface;
use Ibexa\Contracts\Core\Persistence\URL\URLUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\URL\URLQuery;

class URLHandler extends AbstractHandler implements URLHandlerInterface
{
    private const URL_IDENTIFIER = 'url';
    private const CONTENT_IDENTIFIER = 'content';

    /**
     * {@inheritdoc}
     */
    public function updateUrl($id, URLUpdateStruct $struct)
    {
        $this->logger->logCall(__METHOD__, [
            'url' => $id,
            'struct' => $struct,
        ]);

        $url = $this->persistenceHandler->urlHandler()->updateUrl($id, $struct);

        $this->cache->invalidateTags([
            $this->cacheIdentifierGenerator->generateTag(self::URL_IDENTIFIER, [$id]),
        ]);

        if ($struct->url !== null) {
            $this->cache->invalidateTags(array_map(function ($id) {
                return $this->cacheIdentifierGenerator->generateTag(self::CONTENT_IDENTIFIER, [$id]);
            }, $this->persistenceHandler->urlHandler()->findUsages($id)));
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function find(URLQuery $query)
    {
        $this->logger->logCall(__METHOD__, [
            'query' => $query,
        ]);

        return $this->persistenceHandler->urlHandler()->find($query);
    }

    /**
     * {@inheritdoc}
     */
    public function loadById($id)
    {
        $cacheItem = $this->cache->getItem(
            $this->cacheIdentifierGenerator->generateKey(self::URL_IDENTIFIER, [$id], true)
        );

        $url = $cacheItem->get();
        if ($cacheItem->isHit()) {
            return $url;
        }

        $this->logger->logCall(__METHOD__, ['url' => $id]);
        $url = $this->persistenceHandler->urlHandler()->loadById($id);

        $cacheItem->set($url);
        $cacheItem->tag([
            $this->cacheIdentifierGenerator->generateTag(self::URL_IDENTIFIER, [$id]),
        ]);
        $this->cache->save($cacheItem);

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function loadByUrl($url)
    {
        $this->logger->logCall(__METHOD__, ['url' => $url]);

        return $this->persistenceHandler->urlHandler()->loadByUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function findUsages($id)
    {
        $this->logger->logCall(__METHOD__, ['url' => $id]);

        return $this->persistenceHandler->urlHandler()->findUsages($id);
    }
}

class_alias(URLHandler::class, 'eZ\Publish\Core\Persistence\Cache\URLHandler');
