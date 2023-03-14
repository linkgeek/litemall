<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Enums\Constant;
use App\Exceptions\BusinessException;
use App\Inputs\GoodsListInput;
use App\Services\CollectServices;
use App\Services\CommentServices;
use App\Services\Goods\BrandServices;
use App\Services\Goods\CatalogServices;
use App\Services\Goods\GoodsServices;
use App\Services\SearchHistoryServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoodsController extends WxController
{
    protected $only = [];

    /**
     * 统计商品总数
     * @param Request $request
     * @return JsonResponse
     */
    public function count(Request $request)
    {
        $count = GoodsServices::getInstance()->countGoodsOnSale();
        return $this->success($count);
    }

    /**
     * 根据分类获取商品列表数据
     * @return JsonResponse
     * @throws BusinessException
     */
    public function category()
    {
        $id = $this->verifyId('id');
        $cur = CatalogServices::getInstance()->getCategory($id);
        if (empty($cur)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $parent = null;
        $children = null;
        if ($cur->pid == 0) {
            $parent = $cur;
            $children = CatalogServices::getInstance()->getL2ListByPid($cur->id);
            $cur = $children->first() ?? $cur;
        } else {
            $parent = CatalogServices::getInstance()->getL1ById($cur->pid);
            $children = CatalogServices::getInstance()->getL2ListByPid($cur->pid);
        }

        return $this->success([
            'currentCategory' => $cur,
            'brotherCategory' => $children,
            'parentCategory' => $parent,
        ]);
    }

    /**
     * 获取商品列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function list()
    {
        $input = GoodsListInput::new('add');

        // 登录用户保存搜索记录
        if ($this->isLogin() && !empty($keyword)) {
            SearchHistoryServices::getInstance()->save($this->userId(), $keyword, Constant::SEARCH_HISTORY_FROM_WX);
        }

        $columns = ['id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'];
        $goodsList = GoodsServices::getInstance()->listGoods($input, $columns);
        $goodsList = $this->paginate($goodsList);
        $goodsList['filterCategoryList'] = GoodsServices::getInstance()->listL2Category($input);
        return $this->success($goodsList);
    }

    /**
     * 获取商品详情
     * @return JsonResponse
     * @throws BusinessException
     */
    public function detail()
    {
        $id = $this->verifyId('id');
        $info = GoodsServices::getInstance()->getGoods($id);
        if (empty($info)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        // 商品属性
        $attr = GoodsServices::getInstance()->getGoodsAttribute($id);
        // 商品规格
        $specification = GoodsServices::getInstance()->getGoodsSpecification($id);

        $product = GoodsServices::getInstance()->getGoodsProduct($id);
        $issue = GoodsServices::getInstance()->getGoodsIssue();

        $brand = $info->brand_id ? BrandServices::getInstance()->getBrand($info->brand_id) : (object)[];

        // 评价
        $comment = CommentServices::getInstance()->getCommentWithUserInfo($id);

        $userHasCollect = 0;
        if ($this->isLogin()) {
            // 如果登录的话展示收藏夹
            $userHasCollect = CollectServices::getInstance()->countByGoodsId($this->userId(), $id);
            GoodsServices::getInstance()->saveFootprint($this->userId(), $id);
        }

        // todo 团购信息
        // todo 系统配置

        return $this->success([
            'info' => $info,
            'userHasCollect' => $userHasCollect,
            'issue' => $issue,
            'comment' => $comment,
            'specificationList' => $specification,
            'productList' => $product,
            'attribute' => $attr,
            'brand' => $brand,
            'groupon' => [],
            'share' => false,
            'shareImage' => $info->share_url,
        ]);
    }
}
