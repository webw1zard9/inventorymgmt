<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $locations;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

        view()->share('warnings', collect());
        view()->share('title', $this->construct_title() ?: 'Dashboard');
    }

    protected function construct_title()
    {
        $parts = explode('-', Request::segment(1));
        $parts = array_map('ucwords', $parts);

        return implode(' ', $parts);
    }
}
