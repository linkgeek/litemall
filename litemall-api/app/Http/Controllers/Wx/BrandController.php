<?php

namespace App\Http\Controllers\Wx;


use App\CodeResponse;
use App\Services\Goods\BrandServices;
use Illuminate\Http\Request;

/**
 * 品牌
 * Class BrandController
 * @package App\Http\Controllers\Wx
 */
class BrandController extends WxController
{
    protected $only = [];

    public function list(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'add_time');
        $order = $request->input('order', 'desc');
        $columns = ['id', 'name', 'desc', 'pic_url', 'floor_price'];
        $list = BrandServices::getInstance()->getBrandList($page, $limit, $sort, $order, $columns);
        return $this->successPaginate($list);
    }

    /**
     * 获取详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = $request->input('id', 0);
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $brand = BrandServices::getInstance()->getBrand($id);
        if (is_null($brand)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        return $this->success($brand);
    }
}
