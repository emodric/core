<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Bundle\Core\EventListener;

use Ibexa\Bundle\Core\EventListener\SiteAccessListener;
use Ibexa\Bundle\Core\Routing\DefaultRouter;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SiteAccessListenerTest extends TestCase
{
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $container;

    /** @var \Ibexa\Bundle\Core\Routing\DefaultRouter|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var \Ibexa\Core\MVC\Symfony\Routing\Generator\UrlAliasGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $generator;

    /** @var \Ibexa\Bundle\Core\EventListener\SiteAccessListener */
    private $listener;

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $defaultSiteaccess;

    protected function setUp(): void
    {
        parent::setUp();
        $this->defaultSiteaccess = new SiteAccess('default');
        $this->container = $this->createMock(ContainerInterface::class);
        $this->router = $this->createMock(DefaultRouter::class);
        $this->generator = $this->createMock(UrlAliasGenerator::class);
        $this->listener = new SiteAccessListener($this->defaultSiteaccess);
    }

    public function testGetSubscribedEvents()
    {
        $this->assertSame(
            [
                MVCEvents::SITEACCESS => ['onSiteAccessMatch', 255],
            ],
            $this->listener->getSubscribedEvents()
        );
    }

    public function siteAccessMatchProvider()
    {
        return [
            ['/foo/bar', '/foo/bar', '', []],
            ['/my_siteaccess/foo/bar', '/foo/bar', '', []],
            ['/foo/bar/(some)/thing', '/foo/bar', '/(some)/thing', ['some' => 'thing']],
            ['/foo/bar/(some)/thing/(other)', '/foo/bar', '/(some)/thing/(other)', ['some' => 'thing', 'other' => '']],
            ['/foo/bar/(some)/thing/orphan', '/foo/bar', '/(some)/thing/orphan', ['some' => 'thing/orphan']],
            ['/foo/bar/(some)/thing//orphan', '/foo/bar', '/(some)/thing//orphan', ['some' => 'thing/orphan']],
            ['/foo/bar/(some)/thing/orphan/(something)/else', '/foo/bar', '/(some)/thing/orphan/(something)/else', ['some' => 'thing/orphan', 'something' => 'else']],
            ['/foo/bar/(some)/thing/orphan/(something)/else/(other)', '/foo/bar', '/(some)/thing/orphan/(something)/else/(other)', ['some' => 'thing/orphan', 'something' => 'else', 'other' => '']],
            ['/foo/bar/(some)/thing/orphan/(other)', '/foo/bar', '/(some)/thing/orphan/(other)', ['some' => 'thing/orphan', 'other' => '']],
            ['/my_siteaccess/foo/bar/(some)/thing', '/foo/bar', '/(some)/thing', ['some' => 'thing']],
            ['/foo/bar/(some)/thing/(toto_titi)/tata_tutu', '/foo/bar', '/(some)/thing/(toto_titi)/tata_tutu', ['some' => 'thing', 'toto_titi' => 'tata_tutu']],
            ['/foo/%E8%B5%A4/%28some%29/thing', '/foo/赤', '/(some)/thing', ['some' => 'thing']],
        ];
    }

    /**
     * @dataProvider siteAccessMatchProvider
     */
    public function testOnSiteAccessMatchMasterRequest(
        $uri,
        $expectedSemanticPathinfo,
        $expectedVPString,
        array $expectedVPArray
    ) {
        $uri = rawurldecode($uri);
        $semanticPathinfoPos = strpos($uri, $expectedSemanticPathinfo);
        if ($semanticPathinfoPos !== 0) {
            $semanticPathinfo = substr($uri, $semanticPathinfoPos);
            $matcher = $this->createMock(SiteAccess\URILexer::class);
            $matcher
                ->expects($this->once())
                ->method('analyseURI')
                ->with($uri)
                ->will($this->returnValue($semanticPathinfo));
        } else {
            $matcher = $this->createMock(SiteAccess\Matcher::class);
        }

        $siteAccess = new SiteAccess('test', 'test', $matcher);
        $request = Request::create($uri);
        $event = new PostSiteAccessMatchEvent($siteAccess, $request, HttpKernelInterface::MASTER_REQUEST);

        $this->listener->onSiteAccessMatch($event);
        $this->assertSame($expectedSemanticPathinfo, $request->attributes->get('semanticPathinfo'));
        $this->assertSame($expectedVPArray, $request->attributes->get('viewParameters'));
        $this->assertSame($expectedVPString, $request->attributes->get('viewParametersString'));
        $this->assertSame($this->defaultSiteaccess->name, $siteAccess->name);
        $this->assertSame($this->defaultSiteaccess->matchingType, $siteAccess->matchingType);
        $this->assertSame($this->defaultSiteaccess->matcher, $siteAccess->matcher);
    }

    /**
     * @dataProvider siteAccessMatchProvider
     */
    public function testOnSiteAccessMatchSubRequest($uri, $semanticPathinfo, $vpString, $expectedViewParameters)
    {
        $siteAccess = new SiteAccess('test', 'test', $this->createMock(SiteAccess\Matcher::class));
        $request = Request::create($uri);
        $request->attributes->set('semanticPathinfo', $semanticPathinfo);
        if (!empty($vpString)) {
            $request->attributes->set('viewParametersString', $vpString);
        }
        $event = new PostSiteAccessMatchEvent($siteAccess, $request, HttpKernelInterface::SUB_REQUEST);

        $this->listener->onSiteAccessMatch($event);
        $this->assertSame($semanticPathinfo, $request->attributes->get('semanticPathinfo'));
        $this->assertSame($expectedViewParameters, $request->attributes->get('viewParameters'));
        $this->assertSame($vpString, $request->attributes->get('viewParametersString'));
        $this->assertSame($this->defaultSiteaccess->name, $siteAccess->name);
        $this->assertSame($this->defaultSiteaccess->matchingType, $siteAccess->matchingType);
        $this->assertSame($this->defaultSiteaccess->matcher, $siteAccess->matcher);
    }
}

class_alias(SiteAccessListenerTest::class, 'eZ\Bundle\EzPublishCoreBundle\Tests\EventListener\SiteAccessListenerTest');
