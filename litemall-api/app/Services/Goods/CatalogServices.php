<?php

namespace App\Services\Goods;

use App\Models\Goods\Category;
use App\Services\BaseServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CatalogServices extends BaseServices
{
    /**
     * 获取商品一级类目列表
     * @return Builder[]|Collection
     */
    public function getL1List()
    {
        return Category::query()->where('level', '=', 'L1')->get();
    }

    /**
     * 根据一级类目的pid获取商品二级类目列表
     * @param  int  $pId
     * @return Builder[]|Collection
     */
    public function getL2ListByPid(int $pId)
    {
        return Category::query()->where('level', 'L2')->where('pid', $pId)->get();
    }

    /**
     * 根据ID获取一级类目
     * @param  int  $id
     * @return Builder|Model|object|null
     */
    public function getL1ById(int $id)
    {
        return Category::query()->where('level', 'L1')->where('id', $id)->first();
    }

    /**
     * 获取
     * @param  int  $id
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    public function getCategory(int $id)
    {
        return Category::query()->find($id);
    }

    /**
     * @param  array  $ids
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    public function getL2ListByIds(array $ids)
    {
        if (empty($ids)) {
            // 返回空集合
            return collect([]);
        }
        return Category::query()->whereIn('id', $ids)->get();
    }

}
