<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/20/17
 * Time: 17:51
 */

namespace App;

use App\Scopes\ReturnOrderScope;

class ReturnPurchaseOrder extends Order
{
    protected $table = 'orders';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ReturnOrderScope);
    }

    public function set_order_id()
    {
        $this->ref_number = $this->new_ref_number('RPO');
        $this->save();

        return $this;
    }

}
