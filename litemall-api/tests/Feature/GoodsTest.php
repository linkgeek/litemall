<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class GoodsTest extends TestCase
{
    use DatabaseTransactions;

    // 统计商品总数
    public function testCount()
    {
        $response = $this->assertLitemallApiGet('wx/goods/count', ['data']);
    }

    // 根据分类获取商品列表数据
    public function testCategory()
    {
        $response = $this->assertLitemallApiGet('wx/goods/category?id=1008009', ['data']);
        $response = $this->assertLitemallApiGet('wx/goods/category?id=1005000', ['data']);
    }

    // 获得商品列表(因为线上接口有其它测试数据导致数据库内容不一致，所以不对比 data 里的数据
    public function testList()
    {
        $this->assertLitemallApiGet('wx/goods/list', ['data']);
        $this->assertLitemallApiGet('wx/goods/list?categoryId=1008009', ['data']);
        $this->assertLitemallApiGet('wx/goods/list?brandId=1001000', ['data']);
        $this->assertLitemallApiGet('wx/goods/list?keyword=四件套', ['data']);
        $this->assertLitemallApiGet('wx/goods/list?isNew=1', ['data']);
        $this->assertLitemallApiGet('wx/goods/list?isHot=1', ['data']);
        $this->assertLitemallApiGet('wx/goods/list?page=2&limit=5', ['data']);
    }

    public function testDetail()
    {
        $this->assertLitemallApiGet('wx/goods/detail?id=1006002');
    }
}
