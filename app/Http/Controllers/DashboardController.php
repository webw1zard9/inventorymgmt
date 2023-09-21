<?php

namespace App\Http\Controllers;

use App\SaleOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

    public function revenue(Request $request, $time = 'week')
    {

        if ($request->session()->has('dashboard_date_range')) {
            $date_range = $request->session()->get('dashboard_date_range');
        }

        $sales = (new SaleOrder())->{'sales_by_'.$time}($date_range);

        $location_colors = config('inventorymgmt.location_colors');

        $labels = collect();
        $start = Carbon::parse($date_range[0]);
        $start_loop = Carbon::parse($date_range[0]);
        $end = Carbon::parse($date_range[1]);

        switch ($time) {
            case 'day':

                for ($i = 0; $i <= $start->diffInDays($end); $i++) {
                    $current_date = $start_loop->addDays($i == 0 ? 0 : 1);
                    $labels->push($current_date->format('D m/d'));
                }
                break;
            case 'week':
                for ($i = 0; $i <= $start->diffInWeeks($end); $i++) {
                    $current_date = $start_loop->addWeeks($i == 0 ? 0 : 1);
                    if ($current_date->isSunday()) {
                        $current_date->addDay();
                    }
                    $labels->push('Wk '.$current_date->format('W, \'y'));
                }
                break;
            case 'month':
                for ($i = 0; $i <= $start->diffInMonths($end); $i++) {
                    $current_date = $start_loop->addMonths($i == 0 ? 0 : 1);
                    $labels->push($current_date->format('M, y'));
                }
                break;
            case 'quarter':
                $i=0;
                do {
                    $current_date = $start_loop->addQuarters($i == 0 ? 0 : 1); $i++;
                    $labels->push('Q'.$current_date->quarter.'-'.$current_date->format('Y'));
                } while ( ! $current_date->isSameQuarter($end));

                break;
        }

        $datasets = collect();

        $sales->groupBy('location_name')->each(function ($location_data, $location_name) use ($datasets, $location_colors, $time, $labels) {
            $data = collect();
            $labels->each(function ($label) use ($data, $location_data, $time) {
                $location_data_date = $location_data->keyBy($time.'_year');

                if ($location_data_date->has($label)) {
                    $data->push($location_data_date[$label]->total);
                } else {
                    $data->push(0);
                }
            });

            $datasets->push(collect([
                'label' => $location_name,
                'data' => $data,
                'borderColor' => (!empty($location_colors[$location_name])?$location_colors[$location_name]['primary']:"#000"),
                'backgroundColor' => (!empty($location_colors[$location_name])?$location_colors[$location_name]['secondary']:"#000"),
            ]
            ));
        });

        if (Auth::check() && Auth::user()->active_locations->count() > 1) {
            $location_data = $sales->groupBy($time.'_year');
            $data = collect();
            $labels->each(function ($label) use ($data, $location_data) {

//dd($location_data);
                if ($location_data->has($label)) {
                    $data->push($location_data[$label]->sum('total'));
                } else {
                    $data->push(0);
                }
            });

            $datasets->push(collect([
                'label' => 'Total',
                'data' => $data,
                'borderColor' => $location_colors['Nest']['primary'],
                'backgroundColor' => $location_colors['Nest']['secondary'],
            ]
            ));
        }

        //dd($datasets);
//        $view->with('week_data', $week_data);

        $response = collect([
            'labels' => $labels,
            'datasets' => $datasets,
        ]);

        return response()->json($response);
    }
}
