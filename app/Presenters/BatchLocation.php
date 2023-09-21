<?php

namespace App\Presenters;

class BatchLocation extends Presenters
{
    public function branded_name()
    {
//        return ($this->entity->description ? $this->entity->description." (".$this->entity->name.")" : $this->entity->name);

        $name = $this->non_branded_name();

        if (empty($this->entity->batch->brand)) {
            return $name;
        }

        return $this->entity->batch->brand->name.' - '.$name;
    }

    public function non_branded_name()
    {
        return $this->entity->name.($this->entity->batch->type?" (".$this->entity->batch->type.")":"");
    }
}