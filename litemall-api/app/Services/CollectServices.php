<?php

namespace App\Services;

use App\Enums\Constant;
use App\Models\Collect;
use App\Services\Exception;

class CollectServices extends BaseServices
{
    public function countByGoodsId($userId, $goodsId)
    {
        return Collect::query()->where('user_id', $userId)
            ->where('value_id', $goodsId)
            ->where('type', Constant::COLLECT_TYPE_GOODS)
            ->count('id');
    }
}
