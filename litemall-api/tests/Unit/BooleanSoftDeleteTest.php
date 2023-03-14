<?php

namespace Tests\Unit;

use App\Models\Goods\Goods;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BooleanSoftDeleteTest extends TestCase
{
    use DatabaseTransactions;

    private $goodsId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->goodsId = Goods::query()->insertGetId([
            'goods_sn' => 'test',
            'name' => '母亲节礼物-舒适安睡组合',
            'category_id' => '1008008',
            'brand_id' => '1001020',
            'gallery' => '["http://yanxuan.nosdn.127.net/355efbcc32981aa3b7869ca07ee47dac.jpg", "http://yanxuan.nosdn.127.net/43e283df216881037b70d8b34f8846d3.jpg", "http://yanxuan.nosdn.127.net/12e41d7e5dabaf9150a8bb45c41cf422.jpg", "http://yanxuan.nosdn.127.net/5c1d28e86ccb89980e6054a49571cdec.jpg"]',
            'keywords' => '',
            'brief' => '安心舒适是最好的礼物',
            'is_on_sale' => 1,
            'sort_order' => 1,
            'pic_url' => 'https://yanxuan.nosdn.127.net/1f67b1970ee20fd572b7202da0ff705d.png',
            'share_url' => '',
            'is_new' => 1,
            'is_hot' => 0,
            'unit' => '件',
            'counter_price' => '2618.00',
            'retail_price' => '2598.00',
            'detail' => '',
        ]);
    }

    public function testSoftDeleteByModel()
    {
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $goods->delete();
        $this->assertTrue($goods->deleted);
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $this->assertNull($goods);

        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        $goods->restore();
        $this->assertFalse($goods->deleted);
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $this->assertEquals($this->goodsId, $goods->id ?? 0);
    }

    public function testSoftDeleteByBuilder()
    {
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $this->assertEquals($this->goodsId, $goods->id) ?? 0;

        // 软删除数据
        Goods::withoutTrashed()->whereId($this->goodsId)->first();
        $this->assertEquals($this->goodsId, $goods->id) ?? 0;

        $ret = Goods::query()->whereId($this->goodsId)->delete();
        $this->assertEquals(1, $ret);

        // 查询数据
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $this->assertNull($goods);

        // 获取到已删除的数据
        $goods = Goods::withTrashed()->whereId($this->goodsId)->first();
        $this->assertEquals($this->goodsId, $goods->id ?? 0);

        // 获取到已删除的数据
        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        $this->assertEquals($this->goodsId, $goods->id ?? 0);

        // 恢复已删除数据
        $ret = Goods::withTrashed()->whereId($this->goodsId)->restore();
        $this->assertEquals(1, $ret);

        // 获取到已删除的数据
        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        $this->assertNull($goods);

        // 正常查询
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $this->assertEquals($this->goodsId, $goods->id) ?? 0;

        // 硬删除
        $ret = Goods::query()->whereId($this->goodsId)->forceDelete();
        $this->assertEquals(1, $ret);

        // 正常查询
        $goods = Goods::query()->whereId($this->goodsId)->first();
        $this->assertNull($goods);

        // 获取到已删除的数据
        $goods = Goods::onlyTrashed()->whereId($this->goodsId)->first();
        $this->assertNull($goods);
    }
}
