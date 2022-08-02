<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

if (!function_exists('rel')) {
    /**
     * Get eloquent relationships
     *
     * @return object
     */
    function rel(Model $instance)
    {
        // Get public methods declared without parameters and non inherited
        $class = get_class($instance);
        $allMethods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter(
            $allMethods,
            function ($method) use ($class) {
                return $method->class === $class && !$method->getParameters(); // relationships have no parameters
            }
        );

        \DB::beginTransaction();

        $relations = [];
        foreach ($methods as $method) {
            try {
                $methodName = $method->getName();
                $methodReturn = $instance->$methodName();
                if (!$methodReturn instanceof Relation) {
                    continue;
                }
            } catch (\Throwable $th) {
                continue;
            }

            $type = (new \ReflectionClass($methodReturn))->getShortName();
            $model = get_class($methodReturn->getRelated());
            $relations[lcfirst($type)][$methodName] = $model;
        }

        \DB::rollBack();

        return (object) $relations;
    }
}