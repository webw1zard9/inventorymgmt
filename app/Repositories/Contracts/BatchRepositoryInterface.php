<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/24/17
 * Time: 18:06
 */

namespace App\Repositories\Contracts;

interface BatchRepositoryInterface
{
    public function all();

    public function find($id);

    public function findByRefNumber($refNumber, $with);
}
