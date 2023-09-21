<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/20/17
 * Time: 17:49
 */

namespace App\Repositories\Contracts;

interface SaleOrderRepositoryInterface
{
    public function all();

    public function create($data);

    public function find($id);
}
