<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use DatabaseTransactions;
    public function testIndex()
    {
        $response = $this->assertLitemallApiGet('wx/catalog/index', ['errmsg', 'data']);
        $response1 = $this->assertLitemallApiGet('wx/catalog/index?id=1005000', ['data']);
        $response2 = $this->assertLitemallApiGet('wx/catalog/index?id=1005001', ['data']);
    }


    public function testCurrent()
    {
//        $response = $this->assertLitemallApiGet('wx/catalog/current', ['errmsg', 'data']);
        $response1 = $this->assertLitemallApiGet('wx/catalog/current?id=1005000', ['data']);
        $response2 = $this->assertLitemallApiGet('wx/catalog/current?id=1005001', ['data']);
    }
}