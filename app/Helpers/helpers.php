<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/13/17
 * Time: 13:04
 */

use App\Batch;
use App\Brand;
use App\Fund;
use App\License;
use App\Location;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Money\Money;
use Money\Currency;

if (! function_exists('get_grams')) {
    function get_grams($uom)
    {
        return 1;
    }
}

if (! function_exists('display_fulfillment_status')) {
    function display_fulfillment_status($order_detail)
    {
        if (is_null($order_detail->units_fulfilled)) {
            return '';
        }

        if ($order_detail->units_fulfilled == 0) {
            return '-danger';
        } elseif ($order_detail->units == $order_detail->units_fulfilled) {
            return '-success';
        } elseif ($order_detail->units != $order_detail->units_fulfilled) {
            return '-warning';
        }
    }
}


if (! function_exists('display_category_price_ranges')) {
    function display_category_price_ranges($min, $max)
    {
        $str = "";

        if($min == 0 || is_null($min)) $str = "Under ";
        $str .= ($min > 0 ? display_currency($min)  : null);

        if($min > 0 && !is_null($max)) $str .= " - ";
        $str .= ($max > 0 ? display_currency($max) : " +");

        return $str;
    }

}

if (! function_exists('date_presets')) {
    function date_presets()
    {
        return collect([
            'Custom' => [
                'from' => null,
                'to' => null,
            ],
            'Today' => [
                'from' => Carbon::now()->format('Y-m-d'),
                'to' => Carbon::now()->format('Y-m-d'),
            ],
            'Yesterday' => [
                'from' => Carbon::now()->subDays(1)->format('Y-m-d'),
                'to' => Carbon::now()->subDays(1)->format('Y-m-d'),
            ],
            'This Week' => [
                'from' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'to' => Carbon::now()->startOfWeek()->addDays(6)->format('Y-m-d'),
            ],
            'This Month' => [
                'from' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'to' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            ],
            'This Quarter' => [
                'from' => Carbon::now()->firstOfQuarter()->format('Y-m-d'),
                'to' => Carbon::now()->endOfQuarter()->format('Y-m-d'),
            ],
            'Last Week' => [
                'from' => Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'to' => Carbon::now()->subWeek()->startOfWeek()->addDays(6)->format('Y-m-d'),
            ],
            'Last Month' => [
                'from' =>Carbon::now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
                'to' => Carbon::now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d'),
            ],
            'Last Quarter' => [
                'from' => Carbon::now()->subQuarter()->firstOfQuarter()->format('Y-m-d'),
                'to' => Carbon::now()->subQuarter()->endOfQuarter()->format('Y-m-d'),
            ],
        ]);
    }
}

if (! function_exists('convert_to_cents')) {
    function convert_to_cents($amount = 0, $return_money_obj = false, $absolute = false)
    {
        // this function is required bc Scottlaurent\Accounting\ package casts dollar transactions to (int) and in
        // some instances the returned value is incorrect. (int)(0.29 * 100) = 28 !!
        if($return_money_obj) {
            $amt = ($absolute ? abs($amount) : $amount);
            return new Money(round($amt * 100), new Currency('USD'));
        } else {
            return (($absolute ? abs($amount) : $amount) * 100);
        }

    }
}

if (! function_exists('display_currency')) {
    function display_currency($amount = 0, $dec = 2, $sign = 1, $k_seperator = ',')
    {
        if(is_null($amount)) return 0;
        $amt = number_format($amount, $dec, '.', $k_seperator);

        return ($sign ? '$' : '').$amt;
    }
}

if (! function_exists('display_currency_no_sign')) {
    function display_currency_no_sign($amount = 0, $dec = 2)
    {
        return display_currency($amount, $dec, 0, '');
    }
}

if (! function_exists('display_status')) {
    function display_status($status)
    {
        switch ($status) {
            case 'basket-pending':
//                return '<a href="'.route('baskets.show', ['id'=>$model->id]).'">Basket</a>';
                return 'Basket Pending';
                break;
            default:
                return ucwords($status);
        }
    }
}

if (! function_exists('discount_class')) {
    function discount_class($pct)
    {
        switch (true) {
            case $pct >= 75:
                return 'danger';
            break;
            case $pct < 75 && $pct >= 50:
                return 'warning';
            break;
            default:
                return 'success';
        }
    }
}

if (! function_exists('clean_field_label')) {
    function clean_field_label($field)
    {
        return ucwords(str_replace('_', ' ', $field));
    }
}

if (! function_exists('status_class')) {
    function status_class($status)
    {
        switch ($status) {
            case 'pending':
            case 'ready for delivery':
            case 'updated':
                return 'info';
            case 'inventory':
            case 'Inventory':
            case 'Passed':
                return 'primary';
            case 'Lab':
            case 'in-transit':
            case 'In-Testing':
            case 'hold':
            case 'updated on order':
            case 'item updated':
            case 'item allocated':
            case 'allocated':
            case 'closed':
            case 'applied':
                return 'warning';
            case 'sold':
            case 'Failed':
            case 'voided':
            case 'removed from order':
            case 'item deleted':
            case 'deleted':
            case 'sold out':
            case 'return':
            case 'allocation rejected':
            case 'line discount rejected':
            case 'discount rejected':
            case 'items returned':
            case 'transaction deleted':
            case 'refund':
                return 'danger';
            case 'open':
            case 'delivered':
            case 'added to order':
            case 'added to po':
            case 'item added':
            case 'added':
            case 'back in stock':
            case 'created':
            case 'additional purchase':
            case 'allocation approved':
            case 'line discount approved':
            case 'discount approved':
            case 'payment':
            case 'paid':
                return 'success';
            case 'destroyed':
                return 'pink';
            default:
                return 'default';
        }

        return ucwords($status);
    }
}

if (! function_exists('sold_class')) {
    function sold_class($status)
    {
        if ($status == 'sold') {
            return 'ion-checkmark-circled text-success';
        } else {
            return 'ion-close-circled text-danger';
        }
    }
}

if (! function_exists('display_date_range_sales_rep')) {
    function display_date_range_sales_rep($from, $to)
    {
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);

        $showYear = (!$from->isCurrentYear() || !$to->isCurrentYear()) ? ", y" : "";
        $showToMonth = (!$to->isSameMonth($from)?"M ":"");

        $str = [];
        $str[] = $from->format('M jS'.$showYear);
        $str[] = $to->format($showToMonth.'jS'.$showYear);

        return collect($str)->join(" - ");
    }
}

if (! function_exists('display_roles')) {
    function display_roles($user)
    {
        return display_list($user->roles);
    }
}

if (! function_exists('display_locations')) {
    function display_locations($user)
    {
        return display_list($user->locations);
    }
}

if (! function_exists('display_list')) {
    function display_list($list, $col = 'name')
    {
        $html_list = '<ul>';
        foreach ($list->pluck($col) as $val) {
            $html_list .= "<li>{$val}</li>";
        }
        $html_list .= '</ul>';

        return $html_list;
    }
}

if (! function_exists('display_filters')) {
    function display_filters($filter, $value, $dataset = null)
    {
        $label = ucwords($filter);
        $v = (is_array($value) ? ucwords(implode(', ', $value)) : ucwords($value));

        switch ($filter) {
            case 'status':
//                dump();
//                if(is_null($dataset)) break;
////                dd($dataset);
//                $v = $dataset
//                    ->groupBy('status')
//                    ->map(function($coll, $key) {
//                        return ucwords($key);
//                    })
//                    ->implode(', ');
                break;
            case 'in_stock':
                $label = 'In Stock';
                break;
            case 'testing_status':
                $label = 'Testing Status';
                break;
            case 'date_preset':
                $label = 'Date Preset';
//                $v = Carbon::createFromFormat('m-Y', $value)->format('F, Y');
                break;
            case 'from_date':
            case 'to_date':
                $label = ($filter == 'from_date' ? 'From Date' : 'To Date');
                $v = Carbon::parse($value)->format('m/d/Y');
                break;
            case 'vendor':
            case 'customer':
                $user = User::find($value);
                $v = $user->name;
                break;
            case 'sales_rep':
                $label = 'Sales Rep';
                $user = User::find($value);
                $v = ($user ? $user->name : 'None');
                break;
            case 'brand_id':
                $label = 'Brand';
                $brand = Brand::find($value);
                $v = ($brand ? $brand->name : '');
                break;
            case 'sale_type':
                $label = 'Sale Type';
                break;
            case 'batch_id':
                $v = $value;
                $label = 'Batch/Unique/Pkg ID';
                break;
            case 'fund_id':
                $fund = Fund::find($value);
                $v = ($fund ? $fund->name : 'None');
                $label = 'Funding';
                break;
            case 'license_id':
                $license = License::find($value);
                $v = ($license ? $license->legal_business_name.' - '.$license->number : 'None');
                $label = 'License';
                break;
            case 'location_id':
                $location = Location::find($value);
                $v = ($location ? $location->name : 'Nest');
                $label = 'Location';
                break;
            case 'ref_number':
                $label = 'SO#';
                break;
            case 'date_type':
                $label = 'Date Type';
                if ($v == 'Txn_date') {
                    $v = 'Ordered At';
                } else {
                    $v = 'Delivered At';
                }
                break;
            case 'not_available_inventory':
                $label = 'Inventory';
                $v = 'Show Not Available Inventory';
                break;
            case 'available_inventory':
                $label = 'Inventory';
                $v = 'Show Available Inventory';
                break;
            case 'pending_inventory':
                $label = 'Inventory';
                $v = 'Show Pending Inventory';
                break;
            case 'sku':
                $label = 'SKU';
                break;
            case 'uom':
                $label = 'UOM';
                break;
            case 'non_inventory':
                $label = 'Non-inventory Item';
                $v = 'Yes';
                break;
        }

        return '<strong>'.$label.': </strong>'.$v;
    }
}

if (! function_exists('clean_string')) {
    function clean_string($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
}

if (! function_exists('clean_string_strict')) {
    function clean_string_strict($string)
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

        return preg_replace('/[^ \w]+/', '', $string); // Removes special chars.
    }
}

if (! function_exists('badge_color')) {
    function badge_color($number)
    {
        switch (true) {
            case $number >= 60:
                return 'danger';
                break;
            case $number >= 30:
                return 'warning';
                break;
            case $number >= 15:
                return 'info';
                break;
            default:
                return 'success';
                break;
        }
    }
}

if (! function_exists('display_inventory')) {
    function display_inventory($batch, $field = 'inventory', $display_lb = false)
    {
        $batches = null;
        if ($batch instanceof Collection) {
            $batches = $batch;
            $field = ($batch->first()->wt_based ? 'wt_grams' : $field);
            $count = floatval($batch->sum($field));
            $batch = $batch->first();
        } else {
            $field = ($batch->wt_based ? 'wt_grams' : $field);
            $count = floatval($batch->{$field});
        }
//        dump($count);
//        dump($batch);
        if (empty($batch->{$field})) {
            $count = 0;
        }
        //dd($count);

        $uom = $batch->uom;
        $min = 0;

        $str = "<span class='".($count <= $min ? 'text-danger' : '')."'>";

        $str .= $count;
        $str .= ' <small>'.$uom.'</small>';

        $str .= '</span>';

        return $str;
    }
}

if (! function_exists('is_round')) {
    function is_round($value)
    {
        return is_numeric($value) && intval($value) == $value;
    }
}

