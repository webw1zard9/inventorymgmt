<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/24/17
 * Time: 18:06
 */

namespace App\Repositories;

use App\Batch;
use App\Repositories\Contracts\BatchRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DbBatchRepository implements BatchRepositoryInterface
{
    public function all($filters = [])
    {
        $batch = Batch::with('purchase_order')->select('batches.*');

        if ($filters) {
            if (isset($filters['status'])) {
                $batch->whereIn('batches.status', array_keys($filters['status']));
            }

            if (isset($filters['category'])) {
                $batch->whereIn('category_id', array_keys($filters['category']));
            }

            if (isset($filters['vendor_id'])) {
                $batch
                    ->join('orders', 'batches.purchase_order_id', '=', 'orders.id')
                    ->where('orders.vendor_id', $filters['vendor_id']);
            }
            if (isset($filters['name'])) {
                $batch->where('name', 'like', '%'.$filters['name'].'%');
            }
        }

        $batch->orderBy('name');

        return $batch->get();
    }

    public function find($id, $with = ['purchase_order', 'category'])
    {
        $builder = Batch::with($with)->where('batches.id', $id);

        if (Auth::user()->hasLocation()) {
            $builder->addSelect([
                'batches.*',
                DB::raw('SUM(batch_location.quantity * batch_location.unit_price)/SUM(batch_location.quantity)/100 as location_unit_price')
            ])
                ->join('batch_location', 'batches.id', '=', 'batch_location.batch_id')
                ->where('batch_location.location_id', '=', Auth::user()->current_location->id)
                ->groupby('batches.id');
        }

        return $builder->first();
    }

    public function findByRefNumber($refNumber, $with = [])
    {
        return Batch::whereRefNumber($refNumber)->with($with)->firstOrFail();
    }
}
