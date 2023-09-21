<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/7/17
 * Time: 16:11
 */

namespace App\Repositories\Contracts;

interface OrderRepositoryInterface
{
    public function all();

    public function create($data);

    public function find($id);
}
