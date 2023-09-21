<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\PurchaseOrders;

use App\Batch;
use App\Conversion;
use App\TaxRate;
use Carbon\Carbon;
use Illuminate\View\View;

class ReviewUploadComposer
{
    public function compose(View $view)
    {
        $categories = $view->categories->pluck('id', 'name');
//        $brands = $view->brands->pluck('id', 'name');

        $header_row = collect($view->packages[0]);
        $view->packages->shift(); // remove header row

        $view->can_continue = true;
//        dd($view->packages);

        $view->packages = $view->packages->filter(function ($value) {
            return ! empty($value[2]);
        })->transform(function ($item, $key) use ($categories, $header_row, &$view) {
            $item = $header_row->combine($item)->toArray();

            $item['tax_rate_id'] = $this->tax_rates()[$item['Tax Rate']];

            $tax_rate = TaxRate::find($item['tax_rate_id']);
            $item['tax_rate'] = $tax_rate;

            $tax_rate_quantity = $item['Qty'];
            $unit_tax_amount = 0;
            $item['tax_amount'] = 0;

            if ($tax_rate) {
                $unit_tax_amount = $tax_rate->amount;
                if ($tax_rate->uom != $item['UOM']) {
                    $conv_rate = Conversion::getRate($item['UOM'], $tax_rate->uom);
                    $tax_rate_quantity = ($item['Qty'] * $conv_rate->value);
                    $unit_tax_amount = ($tax_rate->amount * $conv_rate->value);
                }
                $item['tax_amount'] = ($tax_rate_quantity * $item['tax_rate']->amount);
            }
//            dump($tax_rate_quantity);
//            dump($tax_rate->amount);
//            dump($tax_rate);

//            dd($item);
            $batch = Batch::where('ref_number', $item['Package'])->first();

            if (! is_null($batch)) {
                $view->can_continue = false;
            }

            $item['uid_exists'] = is_null($batch) ? 0 : 1;
            $item['Qty'] = floatval(preg_replace('/[^\d.]/', '', $item['Qty']));
            $item['category_id'] = $categories[$item['Category']];
            $item['tax_rate_qty'] = $tax_rate_quantity;
            $item['unit_cost'] = round($item['Pre-Tax Unit Cost'], 2);
            $item['subtotal'] = round($item['Qty'] * ($item['Pre-Tax Unit Cost']), 2);
            $item['total'] = $item['subtotal'] - $item['tax_amount'];
            if (! empty($item['Cultivation Date'])) {
                $item['Cultivation Date'] = Carbon::parse($item['Cultivation Date']);
            }

            return $item;
        });
//
//        dd($view->packages);
    }

    protected function tax_rates()
    {
        return [
            'None' => null,
            'Flower' => 5,
            'Trim' => 6,
        ];
    }
}
