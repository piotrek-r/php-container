<?php

declare(strict_types=1);

namespace PiotrekR\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Throwable;

final class Container implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     * @param array<string, callable(ContainerInterface $container): mixed> $factories
     * @param array<string, string> $aliases
     */
    public function __construct(
        private array $services = [],
        private array $factories = [],
        private array $aliases = [],
    ) {
    }

    /**
     * @template T
     * @param class-string<T>|string $id
     * @return T|mixed
     */
    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        if (!array_key_exists($id, $this->factories)) {
            if (array_key_exists($id, $this->aliases)) {
                return $this->services[$id] = $this->get($this->aliases[$id]);
            }
            return $this->services[$id] = $this->autoResolve($id);
        }

        $factory = $this->factories[$id];

        try {
            $service = $factory($this);
        } catch (Throwable $e) {
            throw new class($e->getMessage(), $e->getCode(), $e)
                extends RuntimeException
                implements ContainerExceptionInterface {
            };
        }

        $this->services[$id] = $service;
        return $service;
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services) || array_key_exists($id, $this->factories);
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    private function autoResolve(string $className): mixed
    {
        if (!class_exists($className)) {
            throw new class(sprintf('Class "%s" not found', $className))
                extends RuntimeException
                implements ContainerExceptionInterface {
            };
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new class(sprintf('Class "%s" is not instantiable', $className))
                extends RuntimeException
                implements ContainerExceptionInterface {
            };
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                throw new class(sprintf('Cannot resolve parameter "%s" of "%s"', $parameter->getName(), $className))
                    extends RuntimeException
                    implements ContainerExceptionInterface {
                };
            }

            $dependencies[] = $this->get($type->getName());
        }

        try {
            return $reflection->newInstance(...$dependencies);
        } catch (ReflectionException $e) {
            throw new class($e->getMessage(), $e->getCode(), $e)
                extends RuntimeException
                implements ContainerExceptionInterface {
            };
        }
    }

    /**
     * @template T
     * @param class-string $id
     * @param T $service
     */
    public function setService(string $id, mixed $service): void
    {
        $this->services[$id] = $service;
    }

    /**
     * @param class-string $id
     * @param callable(ContainerInterface $container): mixed $factory
     */
    public function setFactory(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * @param class-string $alias
     * @param class-string $id
     */
    public function setAlias(string $alias, string $id): void
    {
        $this->aliases[$alias] = $id;
    }
}
