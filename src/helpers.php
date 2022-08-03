<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

if (!function_exists('rel')) {
    /**
     * Get eloquent relationships
     *
     * @return object
     */
    function rel(Model $model)
    {
        // Get public methods declared without parameters and non inherited
        $instance = $model->replicate();
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
            $methodName = $method->getName();
            try {
                $methodReturn = $instance->$methodName();
            } catch (\Throwable $th) {
                $relations['WARNING! wrong model methods'][$methodName] = $th->getMessage();
            }
            if ($methodReturn instanceof Relation) {
                $type = (new \ReflectionClass($methodReturn))->getShortName();
                $class = get_class($methodReturn->getRelated());
                $relations[lcfirst($type)][$methodName] = $class;
            }
        }
        \DB::rollBack();

        return (object) $relations;
    }
}
