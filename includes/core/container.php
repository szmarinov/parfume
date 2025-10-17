<?php
/**
 * Dependency Injection Container
 * 
 * Simple DI Container for managing dependencies and services
 * 
 * @package Parfume_Reviews
 * @subpackage Core
 * @since 2.0.0
 */

namespace ParfumeReviews\Core;

/**
 * Container Class
 * 
 * Manages service instances and dependencies
 */
class Container {
    
    /**
     * Container instances
     * 
     * @var array
     */
    private $instances = [];
    
    /**
     * Container bindings (factories)
     * 
     * @var array
     */
    private $bindings = [];
    
    /**
     * Singleton instances (shared)
     * 
     * @var array
     */
    private $singletons = [];
    
    /**
     * Aliases for services
     * 
     * @var array
     */
    private $aliases = [];
    
    /**
     * Set a service instance
     * 
     * @param string $key Service key/name
     * @param mixed $value Service instance or value
     * @return void
     */
    public function set($key, $value) {
        $this->instances[$key] = $value;
    }
    
    /**
     * Get a service instance
     * 
     * @param string $key Service key/name
     * @return mixed Service instance or null
     * @throws \Exception If service not found
     */
    public function get($key) {
        // Check if it's an alias
        if (isset($this->aliases[$key])) {
            $key = $this->aliases[$key];
        }
        
        // Return existing instance
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }
        
        // Return singleton instance
        if (isset($this->singletons[$key])) {
            return $this->singletons[$key];
        }
        
        // Try to build from binding
        if (isset($this->bindings[$key])) {
            return $this->build($key);
        }
        
        throw new \Exception("Service '{$key}' not found in container");
    }
    
    /**
     * Check if container has a service
     * 
     * @param string $key Service key/name
     * @return bool
     */
    public function has($key) {
        // Check alias first
        if (isset($this->aliases[$key])) {
            $key = $this->aliases[$key];
        }
        
        return isset($this->instances[$key]) || 
               isset($this->bindings[$key]) || 
               isset($this->singletons[$key]);
    }
    
    /**
     * Bind a factory/closure to the container
     * 
     * @param string $key Service key/name
     * @param callable $factory Factory function
     * @return void
     */
    public function bind($key, callable $factory) {
        $this->bindings[$key] = $factory;
    }
    
    /**
     * Bind a singleton (shared instance)
     * 
     * @param string $key Service key/name
     * @param callable $factory Factory function
     * @return void
     */
    public function singleton($key, callable $factory) {
        $this->bindings[$key] = $factory;
        $this->singletons[$key] = null; // Mark as singleton
    }
    
    /**
     * Register an alias
     * 
     * @param string $alias Alias name
     * @param string $key Original service key
     * @return void
     */
    public function alias($alias, $key) {
        $this->aliases[$alias] = $key;
    }
    
    /**
     * Build a service from binding
     * 
     * @param string $key Service key/name
     * @return mixed Service instance
     */
    private function build($key) {
        if (!isset($this->bindings[$key])) {
            throw new \Exception("No binding found for '{$key}'");
        }
        
        $factory = $this->bindings[$key];
        $instance = $factory($this);
        
        // If it's a singleton, store it
        if (array_key_exists($key, $this->singletons)) {
            $this->singletons[$key] = $instance;
        }
        
        return $instance;
    }
    
    /**
     * Make a new instance (not cached)
     * 
     * @param string $class Class name
     * @param array $params Constructor parameters
     * @return mixed New instance
     */
    public function make($class, $params = []) {
        if (!class_exists($class)) {
            throw new \Exception("Class '{$class}' does not exist");
        }
        
        // Simple reflection-based instantiation
        $reflection = new \ReflectionClass($class);
        
        if (empty($params)) {
            return $reflection->newInstance();
        }
        
        return $reflection->newInstanceArgs($params);
    }
    
    /**
     * Resolve a class with automatic dependency injection
     * 
     * @param string $class Class name
     * @return mixed Resolved instance
     */
    public function resolve($class) {
        if (!class_exists($class)) {
            throw new \Exception("Class '{$class}' does not exist");
        }
        
        $reflection = new \ReflectionClass($class);
        
        // Get constructor
        $constructor = $reflection->getConstructor();
        
        // If no constructor, just instantiate
        if (null === $constructor) {
            return new $class;
        }
        
        // Get constructor parameters
        $parameters = $constructor->getParameters();
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            
            // Skip if no type hint
            if (null === $type || $type->isBuiltin()) {
                // Try to get default value
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception(
                        "Cannot resolve parameter '{$parameter->getName()}' for class '{$class}'"
                    );
                }
                continue;
            }
            
            // Get type name
            $typeName = $type->getName();
            
            // Try to resolve from container
            if ($this->has($typeName)) {
                $dependencies[] = $this->get($typeName);
            } else {
                // Try to auto-resolve
                $dependencies[] = $this->resolve($typeName);
            }
        }
        
        return $reflection->newInstanceArgs($dependencies);
    }
    
    /**
     * Call a method with dependency injection
     * 
     * @param callable|array $callback Callback function or [object, method]
     * @param array $params Additional parameters
     * @return mixed Method result
     */
    public function call($callback, $params = []) {
        if (is_array($callback)) {
            list($object, $method) = $callback;
            $reflection = new \ReflectionMethod($object, $method);
        } else {
            $reflection = new \ReflectionFunction($callback);
        }
        
        $parameters = $reflection->getParameters();
        $dependencies = [];
        
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            
            // Check if parameter provided
            if (array_key_exists($name, $params)) {
                $dependencies[] = $params[$name];
                continue;
            }
            
            $type = $parameter->getType();
            
            // Skip if no type hint
            if (null === $type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception(
                        "Cannot resolve parameter '{$name}'"
                    );
                }
                continue;
            }
            
            // Get type name
            $typeName = $type->getName();
            
            // Try to resolve from container
            if ($this->has($typeName)) {
                $dependencies[] = $this->get($typeName);
            } else {
                $dependencies[] = $this->resolve($typeName);
            }
        }
        
        return $reflection->invokeArgs(
            is_array($callback) ? $callback[0] : null,
            $dependencies
        );
    }
    
    /**
     * Remove a service from container
     * 
     * @param string $key Service key/name
     * @return void
     */
    public function remove($key) {
        unset($this->instances[$key]);
        unset($this->bindings[$key]);
        unset($this->singletons[$key]);
    }
    
    /**
     * Clear all services
     * 
     * @return void
     */
    public function clear() {
        $this->instances = [];
        $this->bindings = [];
        $this->singletons = [];
        $this->aliases = [];
    }
    
    /**
     * Get all registered service keys
     * 
     * @return array
     */
    public function keys() {
        return array_unique(array_merge(
            array_keys($this->instances),
            array_keys($this->bindings),
            array_keys($this->singletons)
        ));
    }
    
    /**
     * Debug: Get container information
     * 
     * @return array
     */
    public function debug() {
        return [
            'instances' => array_keys($this->instances),
            'bindings' => array_keys($this->bindings),
            'singletons' => array_keys($this->singletons),
            'aliases' => $this->aliases,
            'total' => count($this->keys())
        ];
    }
}