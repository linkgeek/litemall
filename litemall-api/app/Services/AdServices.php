<?php

namespace App\Services;

use App\Models\Ad;

class AdServices extends BaseServices
{
    public function queryFront()
    {
        return Ad::query()->where('position', 1)->where('enabled', 1)->get();
    }
}
