<?php

namespace App\Container;

use Exception;
use ReflectionClass;
use ReflectionParameter;

class Container
{
    private static ?Container $instance = null;
    private array $bindings = [];
    private array $instances = [];

    private function __construct() {}

    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function bind(string $abstract, $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function resolve(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            if ($this->instances[$abstract] === null) {
                $this->instances[$abstract] = $this->build($abstract);
            }
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return $this->build($abstract);
        }

        return $this->build($abstract);
    }

    private function build(string $concrete)
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        $className = $this->bindings[$concrete] ?? $concrete;

        if (!class_exists($className)) {
            throw new Exception("Class {$className} does not exist");
        }

        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if ($constructor === null) {
            return new $className;
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflectionClass->newInstanceArgs($dependencies);
    }

    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter);
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    private function resolveDependency(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if ($type === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new Exception("Cannot resolve dependency {$parameter->getName()}");
        }

        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
            throw new Exception("Cannot resolve built-in type {$typeName} for parameter {$parameter->getName()}");
        }

        return $this->resolve($typeName);
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    public function make(string $abstract, array $parameters = [])
    {
        return $this->resolve($abstract);
    }
}