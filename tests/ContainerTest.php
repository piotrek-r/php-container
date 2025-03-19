<?php

declare(strict_types=1);

namespace PiotrekR\Container\Tests;

use PHPUnit\Framework\TestCase;
use PiotrekR\Container\Container;
use Psr\Container\ContainerExceptionInterface;
use stdClass;

final class ContainerTest extends TestCase
{
    public function testGetService(): void
    {
        $service = new stdClass();
        $container = new Container(services: [
            'service' => $service,
        ]);
        self::assertSame($service, $container->get('service'));
    }

    public function testGetFactory(): void
    {
        $service = new stdClass();
        $container = new Container(factories: [
            'service' => static fn() => $service,
        ]);
        self::assertSame($service, $container->get('service'));
    }

    public function testGetAlias(): void
    {
        $service = new stdClass();
        $container = new Container(
            services: ['service' => $service],
            aliases: ['alias' => 'service'],
        );
        self::assertSame($service, $container->get('alias'));
        self::assertTrue($container->get('service') === $container->get('alias'));
    }

    public function testHasService(): void
    {
        $service = new stdClass();
        $container = new Container(['service' => $service]);
        self::assertTrue($container->has('service'));
        self::assertFalse($container->has('nonexistent'));
    }

    public function testAutoResolve(): void
    {
        $container = new Container();
        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    public function testAutoResolveWithDependencies(): void
    {
        $container = new Container();
        $container->setService(stdClass::class, new stdClass());
        $service = $container->get(ClassWithDependency::class);
        self::assertInstanceOf(ClassWithDependency::class, $service);
        self::assertInstanceOf(stdClass::class, $service->dependency);
    }

    public function testGetThrowsExceptionForUnknownService(): void
    {
        self::expectException(ContainerExceptionInterface::class);
        $container = new Container();
        $container->get('unknown');
    }

    public function testSetService(): void
    {
        $service = new stdClass();
        $container = new Container();
        $container->setService('service', $service);
        self::assertSame($service, $container->get('service'));
    }

    public function testSetFactory(): void
    {
        $service = new stdClass();
        $container = new Container();
        $container->setFactory('service', static fn() => $service);
        self::assertSame($service, $container->get('service'));
    }

    public function testSetAlias(): void
    {
        $service = new stdClass();
        $container = new Container();
        $container->setService('service', $service);
        $container->setAlias('alias', 'service');
        self::assertSame($service, $container->get('alias'));
    }
}
