<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 17:21
 */

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

abstract class Filters
{
    /**
     * @var Request
     */
    protected $request;

    protected $builder;

    protected $default_filters = [];

    protected $filters = [];

    protected $cache_key;

    /**
     * BatchFilters constructor.
     *
     * @param  Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($builder)
    {
        $this->builder = $builder;

        $this->getFilters()
            ->filter(function ($value, $filter) {
                return method_exists($this, $filter);
            })
            ->each(function ($value, $filter) {
                $this->$filter($value);
            });

        return $this->builder;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
//        dump(Auth::user()->id);
        if (! $this->request->filters) {
            $this->request->merge(['filters'=> (Cache::has($this->cache_key) ? Cache::get($this->cache_key) : $this->default_filters)]);
        } else {
            $this->setCache();
        }

        $filters = collect($this->request->filters)->filter(function ($val) {
            return  ! is_null($val);
        });

        if ($filters->isEmpty()) {
            $this->request->merge(['filters' => $this->default_filters]);
            $filters = $this->default_filters;
        }

        return collect($filters);
    }

    public function resetFilters()
    {
        return Cache::forget($this->cache_key);
    }

    public function restrict_by_user()
    {
        return $this->builder->where('user_id', '=', Auth::user()->id);
    }

    public function setFilters($data)
    {
        $this->resetFilters();
        $this->default_filters = $data;
    }

    public function setCache()
    {
        if ($this->cache_key) {
            Cache::forever($this->cache_key, $this->request->filters);
        }
    }
}
