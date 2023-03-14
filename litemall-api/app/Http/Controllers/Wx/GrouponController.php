<?php

namespace App\Http\Controllers\Wx;

use App\Inputs\PageInput;
use App\Models\Promotion\GrouponRules;
use App\Services\Goods\GoodsServices;
use App\Services\Promotion\GrouponServices;

/**
 * 团购
 * Class GrouponController
 * @package App\Http\Controllers\Wx
 */
class GrouponController extends WxController
{
    protected $except = ['test'];

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function list()
    {
        $page = PageInput::new();
        $list = GrouponServices::getInstance()->getGrouponRules($page);
        $rules = collect($list->items());
        $goodsIds = $rules->pluck('goods_id')->toArray();
        $goodsList = GoodsServices::getInstance()->getGoodsListByIds($goodsIds)->keyBy('id');
        $voList = $rules->map(function (GrouponRules $rule) use ($goodsList) {
            /** @var  $goods */
            $goods = $goodsList->get($rule->goods_id);
            return [
                'id' => $goods->id,
                'name' => $goods->name,
                'brief' => $goods->brief,
                'picUrl' => $goods->pic_url,
                'counterPrice' => $goods->counter_price,
                'retailPrice' => $goods->retail_price,
                'grouponPrice' => bcsub($goods->retail_price - $rule->discount, 2),
                'grouponDiscount' => $rule->discount,
                'grouponMember' => $rule->discount_member,
                'expireTime' => $rule->expire_time,
            ];
        });
        $list = $this->paginate($list, $voList);
        return $this->success($list);
    }

    public function test()
    {
        $rules = GrouponServices::getInstance()->getGrouponRulesById(1);
        $resp = GrouponServices::getInstance()->createGrouponShareImage($rules);
        //return response()->make($resp)->header('Content-Type', 'image/png');
        return $resp;
    }
}
