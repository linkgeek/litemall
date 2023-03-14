<?php

namespace Tests;

use App\Models\User\User;
use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $token;

    /** @var User $user */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth();
    }

    public function auth($user = null)
    {
        if (!is_null($user)) {
            $this->user = $user;
        } else {
            if (is_null($this->user)) {
                $this->user = User::factory()->create();
            }
        }

        return $this->token = \Auth::login($this->user);
    }

    public function getAuthHeader()
    {
        $response = $this->post('wx/auth/login', ['username' => '刘明', 'password' => '123456']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $this->token = $token;
        return ['Authorization' => 'Bearer' . $token];
    }

    public function assertLitemallApiGet($url, $ignore = [])
    {
        return $this->assertLitemallApi($url, 'get', [], $ignore);
    }

    public function assertLitemallApiPost($url, $data = [], $ignore = [])
    {
        return $this->assertLitemallApi($url, 'post', $data, $ignore);
    }

    public function assertLitemallApi($url, $method = 'get', $data = [], $ignore = [])
    {
        $client = new Client();
        if ($method == 'get') {
            $response1 = $this->get($url, $this->getAuthHeader());
//            $response2 = $client->get('http://122.112.215.32:8080/' . $url,
//                [
//                    'headers' => ['X-Litemall-Token' => $this->token],
//                ]);
        } else {
            $response1 = $this->post($url, $data, $this->getAuthHeader());

//            $response2 = $client->clie('http://122.112.215.32:8080/' . $url,
//                [
//                    'headers' => ['X-Litemall-Token' => $this->token],
//                    'json'    => $data,
//                ]);
        }
        $content1 = $response1->getContent();
        echo "litemall => ".json_encode(json_decode($content1), JSON_UNESCAPED_UNICODE) . PHP_EOL;

        $content1 = json_decode($content1, true);

//        $content2 = $response2->getBody()->getContents();
//        echo "mcshop => ".json_encode(json_decode($content2), JSON_UNESCAPED_UNICODE) . PHP_EOL;

//        $content2 = json_decode($content2, true);
        foreach ($ignore as $key) {
            unset($content1[$key]);
//            unset($content2[$key]);
        }
        $content1 = [
            'errno' => $content1['errno']
        ];
        $content2 = [
            'errno' => 0
        ];
        $this->assertEquals($content2, $content1);
    }
}
