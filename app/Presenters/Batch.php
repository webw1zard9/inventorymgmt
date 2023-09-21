<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 8/12/18
 * Time: 17:02
 */

namespace App\Presenters;

class Batch extends Presenters
{
    public function branded_name()
    {
//        return ($this->entity->description ? $this->entity->description." (".$this->entity->name.")" : $this->entity->name);

        $name = $this->non_branded_name();

        if (empty($this->entity->brand)) {
            return $name;
        }

        return $this->entity->brand->name.' - '.$name;
    }

    public function non_branded_name()
    {
        return $this->entity->parent_batch_name??$this->entity->name.($this->entity->type?" (".$this->entity->type.")":"");
    }

}
