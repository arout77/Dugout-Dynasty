<?php

namespace Core;

use ReflectionClass;
use ReflectionParameter;

class Container
{
    /**
     * Holds the registered bindings (recipes for how to build objects).
     * @var array
     */
    protected array $bindings = [];

    /**
     * Binds a class or interface to the container.
     *
     * @param string $abstract The class/interface name to bind.
     * @param callable|string|null $concrete The concrete implementation or a closure.
     */
    public function bind( string $abstract, callable | string | null $concrete = null ): void
    {
        if ( is_null( $concrete ) ) {
            $concrete = $abstract;
        }
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Checks if a binding exists in the container.
     *
     * @param string $abstract The class/interface name.
     * @return bool
     */
    public function has( string $abstract ): bool
    {
        return isset( $this->bindings[$abstract] );
    }

    /**
     * Resolves a class from the container, automatically injecting dependencies.
     *
     * @param string $abstract The class name to resolve.
     * @return mixed
     * @throws \Exception
     */
    public function resolve( string $abstract ): mixed
    {
        // If we have a specific recipe (a closure) for this class, use it.
        if ( isset( $this->bindings[$abstract] ) && is_callable( $this->bindings[$abstract] ) ) {
            return call_user_func( $this->bindings[$abstract], $this );
        }

        // Otherwise, try to autowire it using PHP's Reflection API.
        $reflector = new ReflectionClass( $abstract );

        if ( !$reflector->isInstantiable() ) {
            throw new \Exception( "Class {$abstract} is not instantiable." );
        }

        $constructor = $reflector->getConstructor();

        if ( is_null( $constructor ) ) {
            // If there's no constructor, we can just create a new instance.
            return new $abstract();
        }

        // Get the constructor's parameters.
        $parameters   = $constructor->getParameters();
        $dependencies = $this->resolveDependencies( $parameters );

        // Create a new instance of the class with the resolved dependencies.
        return $reflector->newInstanceArgs( $dependencies );
    }

    /**
     * Resolves the dependencies for a given set of reflection parameters.
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     */
    protected function resolveDependencies( array $parameters ): array
    {
        $dependencies = [];
        foreach ( $parameters as $parameter ) {
            $dependencyType = $parameter->getType();
            if ( $dependencyType && !$dependencyType->isBuiltin() ) {
                // It's a class, so we recursively resolve it from the container.
                $dependencies[] = $this->resolve( $dependencyType->getName() );
            }
        }
        return $dependencies;
    }
}
