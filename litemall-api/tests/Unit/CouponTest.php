<?php

namespace Tests\Unit;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Services\Promotion\CouponServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use DatabaseTransactions;

    // 优惠券已领取
    public function testReceiveLimit()
    {
        $this->expectExceptionObject(new BusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券已领取'));
        CouponServices::getInstance()->receive(12, 2);
    }

    // 领取优惠券-成功
    public function testReceive()
    {
        // 新建优惠券，并领取优惠券，查询是否领取成功
        $id = Coupon::query()->insertGetId([
            'name' => '活动优惠券1',
            'desc' => '活动优惠券1',
            'tag' => '满500减200',
            'total' => 0,
            'discount' => 20,
            'min' => 50,
            'limit' => 1,
            'time_type' => 0,
            'days' => 1
        ]);
        $ret = CouponServices::getInstance()->receive(12, $id);
        $this->assertTrue($ret);
        $ret = CouponUser::query()->where('user_id', 12)->where('coupon_id', $id)->first();
        $this->assertNotEmpty($ret);
    }
}
