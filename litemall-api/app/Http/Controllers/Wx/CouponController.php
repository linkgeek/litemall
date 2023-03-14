<?php

namespace App\Http\Controllers\Wx;

use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Services\Promotion\CouponServices;
use Illuminate\Http\JsonResponse;

/**
 * 优惠券
 * Class CouponController
 * @package App\Http\Controllers\Wx
 */
class CouponController extends WxController
{
    protected $except = ['list'];

    /**
     * 获取优惠券列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function list()
    {
        $page = PageInput::new();
        $columns = ['id', 'name', 'desc', 'tag', 'discount', 'min', 'days', 'start_time', 'end_time'];
        $list = CouponServices::getInstance()->list($page, $columns);
        return $this->successPaginate($list);
    }

    /**
     * 用户优惠券列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function myList()
    {
        $status = $this->verifyInteger('status');
        // 获取分页参数
        $page = PageInput::new();
        $list = CouponServices::getInstance()->myList($this->userId(), $status, $page);
        $couponUserList = collect($list->items());
        $couponIds = $couponUserList->pluck('coupon_id')->toArray();
        // $couponIds = [1, 2];
        $coupons = CouponServices::getInstance()->getCoupons($couponIds)->keyBy('id');
        $myList = $couponUserList->map(function (CouponUser $item) use ($coupons) {
            $coupons = $coupons->get($item->coupon_id);
            return [
                'id' => $item->id,
                'cid' => $coupons->id,
                'name' => $coupons->name,
                'desc' => $coupons->desc,
                'tag' => $coupons->tag,
                'min' => $coupons->min,
                'discount' => $coupons->discount,
                'startTime' => $item->start_time,
                'endTime' => $item->end_time,
                'available' => false
            ];
        });
        $list = $this->paginate($list, $myList);

        return $this->success($list);
    }

    /**
     * 领取优惠券
     * @return JsonResponse
     * @throws BusinessException
     */
    public function receive()
    {
        $couponId = $this->verifyId('couponId', 0);
        CouponServices::getInstance()->receive($this->userId(), $couponId);
        return $this->success();
    }
}
