<?php

namespace Tests\Feature;

use App\Services\User\UserServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    // 设置事务不提交，避免脏数据
    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testRegisterError()
    {
        $mobile = '18625696715';
        $response = $this->post('wx/auth/register', [
            'username' => 'tanfan',
            'password' => '123456',
            'mobile' => $mobile,
            'code' => '123',
        ]);
        $response->assertJson([
            'errno' => 703,
            'errmsg' => '验证码错误',
        ]);
    }

    public function testRegister()
    {
        $mobile = '18625696778';
        $code = UserServices::getInstance()->setCaptcha($mobile);
        $response = $this->post('wx/auth/register', [
            'username' => 'tanfan2',
            'password' => '123456',
            'mobile' => $mobile,
            'code' => $code,
        ]);
        $response->assertStatus(200);
        $ret = $response->getOriginalContent();
        $this->assertEquals(0, $ret['errno']);
        $this->assertNotEmpty($ret['data']);
    }

    public function testRegCaptcha()
    {
        $response = $this->post('wx/auth/regCaptcha', ['mobile' => '18625696716']);
        $response->assertJson(['errno' => 0, 'errmsg' => "成功"]);
        $response = $this->post('wx/auth/regCaptcha', ['mobile' => '18625696716']);
        $response->assertJson(['errno' => 702, 'errmsg' => "验证码未超时1分钟，不能发送"]);
    }

    public function testLogin()
    {
        $response = $this->post('wx/auth/login', ['username' => '刘明', 'password' => '123456']);
        $response->assertJson([
            "errno" => 0,
            "errmsg" => "成功",
            "data" => [
                "userInfo" => [
                    "nickName" => "刘明",
                    "avatarUrl" => "https://tvax4.sinaimg.cn/crop.0.0.512.512.180/008brvsDly8gokfghmue7j30e80e8gmi.jpg?KID=imgbed,tva&Expires=1666352925&ssig=nUMIaJAxod",
                ],
            ],
        ]);
        echo $response->getOriginalContent()['data']['token'] ?? '';
        $this->assertNotEmpty($response->getOriginalContent()['data']['token'] ?? '');
    }

    public function testInfo()
    {
        $response = $this->post('wx/auth/login', ['username' => '刘明', 'password' => '123456']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $response2 = $this->get('wx/auth/info', ['Authorization' => 'Bearer' . $token]);
        $user = UserServices::getInstance()->getByUserName("刘明");
        $response2->assertJson([
            'data' => [
                'nickName' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile,
            ],
        ]); // 只验证一个返回字段
    }

    /**
     * 退出登录
     * @return void
     */
    public function testLogOut()
    {
        $response = $this->post('wx/auth/login', ['username' => '刘明', 'password' => '123456']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $response2 = $this->get('wx/auth/info', ['Authorization' => 'Bearer' . $token]);
        $user = UserServices::getInstance()->getByUserName("刘明");
        $response2->assertJson([
            'data' => [
                'nickName' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile,
            ],
        ]); // 只验证一个返回字段
        $response3 = $this->post('wx/auth/logout', [], ['Authorization' => 'Bearer' . $token]);
        $response3->assertJson(['errno' => 0]);

        $response4 = $this->get('wx/auth/info', ['Authorization' => 'Bearer' . $token]);
        $response4->assertJson(['errno' => 501]);
    }

    // 测试密码重置接口
    public function testRest()
    {
        $mobile = '18621165035';
        $code = UserServices::getInstance()->setCaptcha($mobile);
        $response = $this->post('wx/auth/reset', ['mobile' => $mobile, 'password' => '654321', 'code' => $code]);
        $response->assertJson(['errno' => 0]);
        $user = UserServices::getInstance()->getByMobile($mobile);
        $isPass = Hash::check('654321', $user->password);
        $this->assertTrue($isPass);
    }

    // 测试用户资料修改接口
    public function testProfile()
    {
        $response = $this->post('wx/auth/login', ['username' => '刘明', 'password' => '123456']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $response1 = $this->post('wx/auth/profile',
            [
                'avatar' => '',
                'gender' => 1,
                'nickname' => '刘明1',
            ], [
                'Authorization' => 'Bearer' . $token,
            ]);
        $response1->assertJson(['errno' => 0]);
        $user = UserServices::getInstance()->getByUserName('刘明');

//        $this->assertEquals('', $user->avatar);
        $this->assertEquals(1, $user->gender);
        $this->assertEquals('刘明1', $user->nickname);
    }
}
