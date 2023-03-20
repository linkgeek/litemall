<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Enums\Constant;
use App\Services\Goods\GoodsServices;
use App\Services\TopicServices;
use App\Services\CollectServices;

/**
 * 专题
 * Class BrandController
 * @package App\Http\Controllers\Wx
 */
class TopicController extends WxController
{
    protected $only = [];

    /**
     * 获取详情
     * @return \Illuminate\Http\JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function detail()
    {
        $id = $this->verifyId('id', 0);
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $topic = TopicServices::getInstance()->getTopicById($id);
        $goodIds = !empty($topic['goods']) ? json_decode($topic['goods']) : [];
        $goods = [];
        if (!empty($goodIds)) {
            $columns = ['id', 'pic_url', 'name', 'retail_price'];
            $goods = GoodsServices::getInstance()->getGoodsListByIds($goodIds, $columns);
        }

        // 用户收藏
        $userHasCollect = 0;
        $userId = $this->userId();
		if ($userId) {
            $userHasCollect = CollectServices::getInstance()->count($id,Constant::COLLECT_TYPE_TOPIC, $userId);
		}

        return $this->success(compact('topic', 'goods', 'userHasCollect'));
    }

    public function related()
    {
        $id = $this->verifyId('id', 0);
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

    }
}
