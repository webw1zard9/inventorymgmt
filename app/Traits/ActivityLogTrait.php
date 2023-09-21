<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 6/28/22
 * Time: 11:12
 */

namespace App\Traits;

use Spatie\Activitylog\Models\Activity;

trait ActivityLogTrait
{
    public function activity_logs()
    {
        return $this->morphMany(Activity::class, 'subject');
    }
}
