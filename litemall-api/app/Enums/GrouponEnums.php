<?php

namespace App\Enums;

class GrouponEnums
{
    // 团购状态
    const RULE_STATUS_ON = 0;
    const RULE_STATUS_DOWN_EXPIRE = 1;
    const RULE_STATUS_DOWN_ADMIN = 2;

    // 团购活动状态
    const STATUS_NONE = 0;
    const STATUS_ON = 1;
    const STATUS_SUCCEED = 2;
    const STATUS_FAIL = 3;
}
