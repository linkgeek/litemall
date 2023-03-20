<?php

namespace App\Http\Controllers\Wx;

use App\Exceptions\BusinessException;
use App\Services\Goods\CatalogServices;
use Illuminate\Http\RedirectResponse;
use App\Services\AdServices;
use App\Services\Goods\BrandServices;
use App\Services\Goods\GoodsServices;
use App\Services\Promotion\CouponServices;
use App\Services\TopicServices;
use App\Models\User\User;


class HomeController extends WxController
{
    protected $only = [];

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws BusinessException
     */
    public function index()
    {
        $redis = redis();
        $indexCache = $redis->get('index');
        if ($indexCache) {
            $result = json_decode($indexCache);
            return $this->success($result);
        }

        $banner = AdServices::getInstance()->queryFront();
        $brandList = BrandServices::getInstance()->getFront();
        $newGoodsList = GoodsServices::getInstance()->queryByNew();
        $hotGoodsList = GoodsServices::getInstance()->queryByHot();
        $couponList = CouponServices::getInstance()->queryByNew($this->userId());
        $channel = CatalogServices::getInstance()->getL1List();
        $topicList = TopicServices::getInstance()->queryFront();

        $result = [
            'banner' => $banner,
            'channel' => $channel,
            'couponList' => $couponList,
            'newGoodsList' => $newGoodsList,
            'hotGoodsList' => $hotGoodsList,
            'brandList' => $brandList,
            'topicList' => $topicList,
            'grouponList' => [],
            'floorGoodsList' => [],
        ];
        $redis->set('index', json_encode($result), 600);
        return $this->success($result);
    }

    /**
     * 分享链接调整
     * @return RedirectResponse
     * @throws BusinessException
     */
    public function redirectShareUrl()
    {
        $type = $this->verifyString('type', 'groupon');
        $id = $this->verifyId('id');

        if ($type == 'groupon') {
            return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
        }

        if ($type == 'goods') {
            return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
        }

        return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
    }

    public function test()
    {
        $user = User::factory()->create();
        dd($user);

        //$user = UserServices::getInstance()->getUserById(1);
        dd($user->toArray());
    }
}
