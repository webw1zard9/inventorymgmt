<?php

namespace App\Http\Controllers;

use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        $activity_logs = Activity::with([
            'subject',
            'causer',
        ])
//            ->where('description', '!=', 'updated')
            ->orderBy('created_at', 'desc')->paginate(100);

        return view('activity_log.index', compact('activity_logs'));
    }
}
