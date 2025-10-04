<?php
/**
 * Hook Loader
 * 
 * Centralized hook management system for actions and filters
 * 
 * @package Parfume_Reviews
 * @subpackage Core
 * @since 2.0.0
 */

namespace Parfume_Reviews\Core;

/**
 * Loader Class
 * 
 * Registers and manages WordPress hooks (actions and filters)
 */
class Loader {
    
    /**
     * Array of actions registered with WordPress
     * 
     * @var array
     */
    private $actions = [];
    
    /**
     * Array of filters registered with WordPress
     * 
     * @var array
     */
    private $filters = [];
    
    /**
     * Array of shortcodes registered with WordPress
     * 
     * @var array
     */
    private $shortcodes = [];
    
    /**
     * Add an action hook
     * 
     * @param string $hook Action hook name
     * @param object $component Component instance
     * @param string $callback Method name to call
     * @param int $priority Hook priority (default: 10)
     * @param int $accepted_args Number of arguments (default: 1)
     * @return void
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add a filter hook
     * 
     * @param string $hook Filter hook name
     * @param object $component Component instance
     * @param string $callback Method name to call
     * @param int $priority Hook priority (default: 10)
     * @param int $accepted_args Number of arguments (default: 1)
     * @return void
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Register a shortcode
     * 
     * @param string $tag Shortcode tag
     * @param object $component Component instance
     * @param string $callback Method name to call
     * @return void
     */
    public function add_shortcode($tag, $component, $callback) {
        $this->shortcodes[] = [
            'tag' => $tag,
            'component' => $component,
            'callback' => $callback
        ];
    }
    
    /**
     * Add hook to internal registry
     * 
     * @param array $hooks Current hooks array
     * @param string $hook Hook name
     * @param object $component Component instance
     * @param string $callback Method name
     * @param int $priority Hook priority
     * @param int $accepted_args Number of arguments
     * @return array Updated hooks array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        ];
        
        return $hooks;
    }
    
    /**
     * Register all hooks with WordPress
     * 
     * @return void
     */
    public function run() {
        // Register actions
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // Register filters
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                [$hook['component'], $hook['callback']],
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // Register shortcodes
        foreach ($this->shortcodes as $shortcode) {
            add_shortcode(
                $shortcode['tag'],
                [$shortcode['component'], $shortcode['callback']]
            );
        }
    }
    
    /**
     * Get all registered actions
     * 
     * @return array
     */
    public function get_actions() {
        return $this->actions;
    }
    
    /**
     * Get all registered filters
     * 
     * @return array
     */
    public function get_filters() {
        return $this->filters;
    }
    
    /**
     * Get all registered shortcodes
     * 
     * @return array
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }
    
    /**
     * Remove an action hook
     * 
     * @param string $hook Action hook name
     * @param object $component Component instance
     * @param string $callback Method name
     * @return void
     */
    public function remove_action($hook, $component, $callback) {
        $this->actions = $this->remove($this->actions, $hook, $component, $callback);
    }
    
    /**
     * Remove a filter hook
     * 
     * @param string $hook Filter hook name
     * @param object $component Component instance
     * @param string $callback Method name
     * @return void
     */
    public function remove_filter($hook, $component, $callback) {
        $this->filters = $this->remove($this->filters, $hook, $component, $callback);
    }
    
    /**
     * Remove hook from internal registry
     * 
     * @param array $hooks Current hooks array
     * @param string $hook Hook name
     * @param object $component Component instance
     * @param string $callback Method name
     * @return array Updated hooks array
     */
    private function remove($hooks, $hook, $component, $callback) {
        return array_filter($hooks, function($item) use ($hook, $component, $callback) {
            return !($item['hook'] === $hook && 
                    $item['component'] === $component && 
                    $item['callback'] === $callback);
        });
    }
    
    /**
     * Clear all registered hooks
     * 
     * @return void
     */
    public function clear() {
        $this->actions = [];
        $this->filters = [];
        $this->shortcodes = [];
    }
    
    /**
     * Count total registered hooks
     * 
     * @return int
     */
    public function count() {
        return count($this->actions) + count($this->filters) + count($this->shortcodes);
    }
    
    /**
     * Get hooks by type
     * 
     * @param string $type Hook type ('action', 'filter', or 'shortcode')
     * @return array
     */
    public function get_hooks_by_type($type) {
        switch ($type) {
            case 'action':
                return $this->actions;
            case 'filter':
                return $this->filters;
            case 'shortcode':
                return $this->shortcodes;
            default:
                return [];
        }
    }
    
    /**
     * Get hooks by hook name
     * 
     * @param string $hook_name Hook name to search for
     * @return array
     */
    public function get_hooks_by_name($hook_name) {
        $results = [];
        
        // Search in actions
        foreach ($this->actions as $hook) {
            if ($hook['hook'] === $hook_name) {
                $results[] = array_merge($hook, ['type' => 'action']);
            }
        }
        
        // Search in filters
        foreach ($this->filters as $hook) {
            if ($hook['hook'] === $hook_name) {
                $results[] = array_merge($hook, ['type' => 'filter']);
            }
        }
        
        return $results;
    }
    
    /**
     * Debug: Get loader information
     * 
     * @return array
     */
    public function debug() {
        return [
            'actions' => count($this->actions),
            'filters' => count($this->filters),
            'shortcodes' => count($this->shortcodes),
            'total' => $this->count(),
            'action_hooks' => array_unique(array_column($this->actions, 'hook')),
            'filter_hooks' => array_unique(array_column($this->filters, 'hook')),
            'shortcode_tags' => array_column($this->shortcodes, 'tag')
        ];
    }
}