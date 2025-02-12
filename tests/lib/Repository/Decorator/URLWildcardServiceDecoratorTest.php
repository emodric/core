<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\Decorator\URLWildcardServiceDecorator;
use Ibexa\Contracts\Core\Repository\URLWildcardService;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class URLWildcardServiceDecoratorTest extends TestCase
{
    protected function createDecorator(MockObject $service): URLWildcardService
    {
        return new class($service) extends URLWildcardServiceDecorator {
        };
    }

    protected function createServiceMock(): MockObject
    {
        return $this->createMock(URLWildcardService::class);
    }

    public function testCreateDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            'source_url_value',
            'destination_url_value',
            true,
        ];

        $serviceMock->expects($this->once())->method('create')->with(...$parameters);

        $decoratedService->create(...$parameters);
    }

    public function testRemoveDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(URLWildcard::class)];

        $serviceMock->expects($this->once())->method('remove')->with(...$parameters);

        $decoratedService->remove(...$parameters);
    }

    public function testLoadDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [1];

        $serviceMock->expects($this->once())->method('load')->with(...$parameters);

        $decoratedService->load(...$parameters);
    }

    public function testLoadAllDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            10,
            100,
        ];

        $serviceMock->expects($this->once())->method('loadAll')->with(...$parameters);

        $decoratedService->loadAll(...$parameters);
    }

    public function testTranslateDecorator()
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = ['ibexa.co'];

        $serviceMock->expects($this->once())->method('translate')->with(...$parameters);

        $decoratedService->translate(...$parameters);
    }
}

class_alias(URLWildcardServiceDecoratorTest::class, 'eZ\Publish\SPI\Repository\Tests\Decorator\URLWildcardServiceDecoratorTest');
