<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use PHPUnit\Framework\TestCase;

class SiteAccessServiceTest extends TestCase
{
    private const EXISTING_SA_NAME = 'existing_sa';
    private const UNDEFINED_SA_NAME = 'undefined_sa';
    private const SA_GROUP = 'group';

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    /** @var \ArrayIterator */
    private $availableSiteAccesses;

    /** @var array */
    private $configResolverParameters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = $this->createMock(SiteAccessProviderInterface::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->siteAccess = new SiteAccess('current');
        $this->availableSiteAccesses = $this->getAvailableSitAccesses(['current', 'first_sa', 'second_sa', 'default']);
        $this->configResolverParameters = $this->getConfigResolverParameters();
    }

    public function testGetCurrentSiteAccess(): void
    {
        $service = new SiteAccessService(
            $this->createMock(SiteAccessProviderInterface::class),
            $this->createMock(ConfigResolverInterface::class)
        );

        self::assertNull($service->getCurrent());

        $siteAccess = new SiteAccess('default');
        $service->setSiteAccess($siteAccess);
        self::assertSame($siteAccess, $service->getCurrent());

        $service->setSiteAccess(null);
        self::assertNull($service->getCurrent());
    }

    public function testGetSiteAccess(): void
    {
        $staticSiteAccessProvider = new StaticSiteAccessProvider(
            [self::EXISTING_SA_NAME],
            [self::EXISTING_SA_NAME => [self::SA_GROUP]],
        );
        $service = new SiteAccessService(
            $staticSiteAccessProvider,
            $this->createMock(ConfigResolverInterface::class)
        );

        self::assertEquals(
            self::EXISTING_SA_NAME,
            $service->get(self::EXISTING_SA_NAME)->name
        );
    }

    public function testGetSiteAccessThrowsNotFoundException(): void
    {
        $staticSiteAccessProvider = new StaticSiteAccessProvider(
            [self::EXISTING_SA_NAME],
            [self::EXISTING_SA_NAME => [self::SA_GROUP]],
        );
        $service = new SiteAccessService(
            $staticSiteAccessProvider,
            $this->createMock(ConfigResolverInterface::class)
        );

        $this->expectException(NotFoundException::class);
        $service->get(self::UNDEFINED_SA_NAME);
    }

    public function testGetCurrentSiteAccessesRelation(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap($this->configResolverParameters);

        $this->provider
            ->method('getSiteAccesses')
            ->willReturn($this->availableSiteAccesses);

        $this->assertSame(['current', 'first_sa'], $this->getSiteAccessService()->getSiteAccessesRelation());
    }

    public function testGetFirstSiteAccessesRelation(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap($this->configResolverParameters);

        $this->provider
            ->method('getSiteAccesses')
            ->willReturn($this->availableSiteAccesses);

        $this->assertSame(
            ['current', 'first_sa'],
            $this->getSiteAccessService()->getSiteAccessesRelation(new SiteAccess('first_sa'))
        );
    }

    private function getSiteAccessService(): SiteAccessService
    {
        $siteAccessService = new SiteAccessService($this->provider, $this->configResolver);
        $siteAccessService->setSiteAccess($this->siteAccess);

        return $siteAccessService;
    }

    /**
     * @param string[] $siteAccessNames
     */
    private function getAvailableSitAccesses(array $siteAccessNames): ArrayIterator
    {
        $availableSitAccesses = [];
        foreach ($siteAccessNames as $siteAccessName) {
            $availableSitAccesses[] = new SiteAccess($siteAccessName);
        }

        return new ArrayIterator($availableSitAccesses);
    }

    private function getConfigResolverParameters(): array
    {
        return [
            ['repository', 'ibexa.site_access.config', 'current', 'repository_1'],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'current', 1],
            ['repository', 'ibexa.site_access.config', 'first_sa', 'repository_1'],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'first_sa', 1],
            ['repository', 'ibexa.site_access.config', 'second_sa', 'repository_1'],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'second_sa', 2],
            ['repository', 'ibexa.site_access.config', 'default', ''],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'default', 3],
        ];
    }
}

class_alias(SiteAccessServiceTest::class, 'eZ\Publish\Core\MVC\Symfony\SiteAccess\Tests\SiteAccessServiceTest');
