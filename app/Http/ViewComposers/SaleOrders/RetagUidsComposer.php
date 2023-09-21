<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\SaleOrders;

use App\Batch;
use Illuminate\View\View;

class RetagUidsComposer
{
    public function compose(View $view)
    {
        $new_uid_tags = collect();
        $existing_uids = collect();
        $warnings = collect();

        $batches_need_retag = $view->saleOrder->batchesThatRequireRetag;

        $number_tags = 0;
        $view->saleOrder->order_details->where('cog', 1)->each(function ($order_detail) use (&$number_tags, $batches_need_retag) {
            if ($batches_need_retag->has($order_detail->batch->id)) {
                $number_tags++;
            }
        });

        if (request()->has('start_tag_id') && $number_tags) {
            for ($i = 0; $i < $number_tags; $i++) {
                $tag_id = config('inventorymgmt.metrc_tag')[2].str_pad((int) request()->get('start_tag_id') + $i, 9, 0, STR_PAD_LEFT);

                $new_uid_tags->push($tag_id);
            }

            $existing_uids = Batch::whereIn('ref_number', $new_uid_tags)->get()->pluck('ref_number');

//            dump($new_uid_tags);
//            dd($existing_uids);
        }

        if ($existing_uids->count()) {
            $warnings->push('There are UID\'s that are already in use.');
        }

        $view->with('warnings', $warnings)
            ->with('batches_need_retag', $batches_need_retag)
            ->with('new_uid_tags', $new_uid_tags)
            ->with('existing_uids', $existing_uids);
    }
}
