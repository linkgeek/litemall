<?php

namespace App\Services\Goods;

use App\Inputs\GoodsListInput;
use App\Models\Goods\Footprint;
use App\Models\Goods\Goods;
use App\Models\Goods\GoodsAttribute;
use App\Models\Goods\GoodsProduct;
use App\Models\Goods\GoodsSpecification;
use App\Models\Goods\Issue;
use App\Services\BaseServices;
use App\Services\SystemServices;
use Illuminate\Database\Eloquent\Builder;

class GoodsServices extends BaseServices
{
    /**
     * 批量获取商品信息
     * @param array $goodsIds
     * @return Goods[]|Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getGoodsListByIds(array $goodsIds, $columns = ['*'])
    {
        if (empty($goodsIds)) {
            return collect();
        }

        return Goods::query()->whereIn('id', $goodsIds)->get($columns);
    }

    public function getGoods(int $goodsId)
    {
        return Goods::query()->find($goodsId);
    }

    public function getGoodsAttribute(int $goodsId)
    {
        return GoodsAttribute::query()->where('goods_id', $goodsId)->get();
    }

    public function getGoodsSpecification(int $goodsId)
    {
        $spec = GoodsSpecification::query()->where('goods_id', $goodsId)
            ->get()->groupBy('specification');
        return $spec->map(function ($v, $k) {
            return ['name' => $k, 'valueList' => $v];
        })->values();
    }

    public function getGoodsProduct(int $goodsId)
    {
        return GoodsProduct::query()->where('goods_id', $goodsId)->get();
    }

    public function getGoodsProductById(int $id)
    {
        return GoodsProduct::query()->find($id);
    }

    public function getGoodsProductsByIds(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }
        return GoodsProduct::query()->whereIn('id', $ids)->get();
    }

    public function getGoodsIssue(int $page = 1, int $limit = 4)
    {
        return Issue::query()->forPage($page, $limit)->get();
    }

    public function saveFootprint($userId, $goodsId)
    {
        $footprint = new Footprint();
        $footprint->fill(['user_id', $userId, 'goods_id' => $goodsId]);
        return $footprint->save();
    }

    /**
     * 获取在售商品数量
     * @return int
     */
    public function countGoodsOnSale()
    {
        return Goods::query()->where('is_on_sale', 1)->count('id');
    }

    /**
     * 获取商品列表
     * @param GoodsListInput $input
     * @param $columns
     * @return mixed
     */
    public function listGoods(GoodsListInput $input, $columns)
    {
        $query = $this->getQueryByGoodsFilter($input);
        if (!empty($input->categoryId)) {
            $query = $query->where('category_id', $input->categoryId);
        }

        return $query->orderBy($input->sort, $input->order)
            ->paginate($input->limit, $columns, 'page', $input->page);
    }

    /**
     * 获取商品二级类目
     * @param GoodsListInput $input
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function listL2Category(GoodsListInput $input)
    {
        $query = $this->getQueryByGoodsFilter($input);
        $categoryIds = $query->select(['category_id'])->pluck('category_id')->unique()->toArray();
        return CatalogServices::getInstance()->getL2ListByIds($categoryIds);
    }

    /**
     * 查询条件
     * @param GoodsListInput $input
     * @return Goods|Builder
     */
    private function getQueryByGoodsFilter(GoodsListInput $input)
    {
        $query = Goods::query()->where('is_on_sale', 1);
        if (!empty($input->brandId)) {
            $query = $query->where('brand_id', $input->brandId);
        }

        if (!is_null($input->isNew)) {
            $query = $query->where('is_new', $input->isNew);
        }

        if (!is_null($input->isHot)) {
            $query = $query->where('is_hot', $input->isHot);
        }

        if (!empty($input->keyword)) {
            $query = $query->where(function (Builder $query) use ($input) {
                $query->where('keywords', 'like', "%{$input->keyword}%")
                    ->orWhere('name', 'like', "%{$input->keyword}%");
            });
        }

        return $query;
    }

    /**
     * 乐观锁减库存
     * @param $productId
     * @param $num
     * @return int
     */
    public function reduceStock($productId, $num)
    {
        return GoodsProduct::query()->where('id', $productId)->where('number', '>=', $num)
            ->decrement('number', $num);
    }

    public function addStock($productId, $num)
    {
        $product = $this->getGoodsProductById($productId);
        $product->number = $product->number + $num;
        return $product->cas();
    }

    /**
     * @throws \App\Exceptions\BusinessException
     */
    public function queryByNew()
    {
        $columns = ['id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'];
        $input = GoodsListInput::new('add');
        $input->isNew = true;
        $input->limit = SystemServices::getInstance()->getNewLimit();
        $goodsList = $this->listGoods($input, $columns);
        $goodsList = $goodsList->toArray();
        return $goodsList['data'] ?? [];
    }

    public function queryByHot()
    {
        $columns = ['id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'];
        $input = GoodsListInput::new('add');
        $input->isHot = true;
        $input->limit = SystemServices::getInstance()->getHotLimit();
        $goodsList = $this->listGoods($input, $columns);
        $goodsList = $goodsList->toArray();
        return $goodsList['data'] ?? [];
    }
}
