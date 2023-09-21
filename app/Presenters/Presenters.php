<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 8/12/18
 * Time: 17:15
 */

namespace App\Presenters;

class Presenters
{
    protected $entity;

    /**
     * User constructor.
     *
     * @param $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function __get($property)
    {
        if (method_exists($this, $property)) {
            return $this->{$property}();
        }

        return $this->entity->{$property};
    }
}
