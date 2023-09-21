<?php

namespace App\Presenters;

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 6/27/17
 * Time: 18:35
 */
class User extends Presenters
{
    /**
     * @return string
     */
    public function phone_number()
    {
        if ($this->entity->phone == '3105551234') {
            return '';
        }

        if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $this->entity->phone, $matches)) {
            $result = $matches[1].'-'.$matches[2].'-'.$matches[3];

            return $result;
        }

        return $this->entity->phone;
    }

    /**
     * @return string
     */
    public function first_name()
    {
        if (! $this->entity->name) {
            return '';
        }
        $nameParts = explode(' ', $this->entity->name);
        array_filter($nameParts);
        if (count($nameParts) > 1) {
            $lastName = array_pop($nameParts);
        } //remove last name

        return trim(implode(' ', $nameParts));
    }

    /**
     * @return string
     */
    public function last_name()
    {
        if (! $this->entity->name) {
            return '';
        }
        $nameParts = explode(' ', $this->entity->name);
        array_filter($nameParts);
        if (count($nameParts) > 1) {
            return trim(array_pop($nameParts));
        } else {
            return '';
        }
    }

    public function name_address()
    {
        return $this->entity->name.'<br>'.$this->address();
    }

    public function address()
    {
        return $this->entity->details['address'].'<br>'.$this->entity->details['address2'];
    }
}
