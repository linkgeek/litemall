<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Models\User\User;
use App\Services\User\UserServices;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * 用户
 * Class AuthController
 * @package App\Http\Controllers\Wx
 */
class UserController extends WxController
{
    protected $only = ['info', 'profile'];

    /**
     * 获取用户信息
     * @return JsonResponse
     */
    public function index()
    {
        $user = $this->user();
        return $this->success([
            'nickName' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => $user->gender,
            'mobile' => $user->mobile,
        ]);
    }

    /**
     * 用户信息修改
     * @param Request $request
     * @return JsonResponse
     */
    public function profile(Request $request)
    {
        $user = $this->user();
        $avatar = $request->input('avatar');
        $gender = $request->input('gender');
        $nickName = $request->input('nickname');
        if (!empty($avatar)) {
            $user->avatar = $avatar;
        }
        if (!empty($gender)) {
            $user->gender = $gender;
        }
        if (!empty($nickName)) {
            $user->nickname = $nickName;
        }
        $ret = $user->save();
        return $this->failOrSuccess($ret, CodeResponse::UPDATED_FAIL);
    }

    /**
     * 登出
     * @return JsonResponse
     */
    public function logout()
    {
        Auth::guard('wx')->logout();
        return $this->success();
    }

    /**
     * 密码重置
     * @param Request $request
     * @return JsonResponse
     * @throws \App\Exceptions\BusinessException
     */
    public function reset(Request $request)
    {
        $password = $request->input('password');
        $mobile = $request->input('mobile');
        $code = $request->input('code');
        if (empty($password) || empty($mobile) || empty($code)) {
            return $this->fail(codeResponse::PARAM_ILLEGAL);
        }

        $isPass = UserServices::getInstance()->checkCaptcha($mobile, $code);
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_UNMATCH);
        }

        $user = UserServices::getInstance()->getByMobile($mobile);
        if (is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_UNREGISTERED);
        }

        $user->password = Hash::make($password);
        $ret = $user->save();
        return $this->failOrSuccess($ret, CodeResponse::UPDATED_FAIL);
    }

    /**
     * 登录
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        $userName = $request->input('username');
        $password = $request->input('password');
        if (empty($userName) || empty($password)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }

        // 验证账号是否存在
        $user = UserServices::getInstance()->getByUserName($userName);
        if (is_null($user)) {
            return $this->fail(CodeResponse::AUTH_INVALID_ACCOUNT);
        }

        // 密码验证
        $isPass = Hash::check($password, $user->getAuthPassword());
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_INVALID_ACCOUNT, '账号/密码有误');
        }

        // 更新登录信息
        $user->last_login_time = now()->toDateTimeString();
        $user->last_login_ip = $request->getClientIp();
        if (!$user->save()) {
            return $this->fail(CodeResponse::UPDATED_FAIL);
        }

        // 获取token
        $token = Auth::guard('wx')->login($user);

        // 组装数据并返回
        return $this->success([
            'token' => $token,
            'userInfo' => [
                'nickName' => $userName,
                'avatarUrl' => $user->avatar,
            ],
        ]);
    }

    /**
     * 用户注册
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        // 获取参数
        $userName = $request->input('username');
        $password = $request->input('password');
        $mobile = (string)$request->input('mobile');
        $code = (string)$request->input('code');

        // 验证参数是否为空
        if (empty($userName) || empty($password) || empty($mobile) || empty($code)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }

        // 验证用户是否存在
        $user = UserServices::getInstance()->getByUserName($userName);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_NAME_REGISTERED);
        }

        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }

        $user = UserServices::getInstance()->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }

        // 验证验证码是否正确
        //UserServices::getInstance()->checkCaptcha($mobile, $code);

        // 写入用户表
        $user = new User();
        $user->username = $userName;
        $user->password = Hash::make($password);
        $user->mobile = $mobile;
        $user->avatar = "https://tvax4.sinaimg.cn/crop.0.0.512.512.180/008brvsDly8gokfghmue7j30e80e8gmi.jpg?KID=imgbed,tva&Expires=1666352925&ssig=nUMIaJAxod";
        $user->nickname = $userName;
        $user->last_login_time = Carbon::now()->toDateTimeString(); // Y-m-d H:i:s
        $user->last_login_ip = $request->getClientIp();
        $result = $user->save();

        // todo 新用户发券
        // 返回用户信息和 todo 返回token

        return $this->success([
            'token' => '',
            'userInfo' => [
                'nickName' => $userName,
                'avatarUrl' => $user->avatar,
            ],
        ]);
    }

    /**
     * 发送注册短信验证码
     * @param Request $request
     * @return null
     * @throws \Exception
     */
    public function regCaptcha(Request $request)
    {
        $mobile = $request->input('mobile');
        // 验证手机号是否合法
        if (empty($mobile)) {
            return $this->fail(CodeResponse::PARAM_ILLEGAL);
        }

        $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'regex:/^1[0-9]{10}$/']);
        if ($validator->fails()) {
            return $this->fail(CodeResponse::AUTH_INVALID_MOBILE);
        }

        // 验证手机号是否已注册
        $user = UserServices::getInstance()->getByMobile($mobile);
        if (!is_null($user)) {
            return $this->fail(CodeResponse::AUTH_MOBILE_REGISTERED);
        }

        // 防刷验证 一分钟内只能请求1次，当天只能请求10次
        $lock = Cache::add('register_captcha_lock_' . $mobile, 1, 60);
        if (!$lock) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY);
        }

        $isPass = UserServices::getInstance()->checkMobileSendCaptchaCount($mobile);
        if (!$isPass) {
            return $this->fail(CodeResponse::AUTH_CAPTCHA_FREQUENCY, '验证码当天发送不能超过10次');
        }

        // 随机生成6位验证码 并 保存手机号和验证码的关系
        $code = UserServices::getInstance()->setCaptcha($mobile);
        // 发送短信
        UserServices::getInstance()->sendCaptchaMsg($mobile, $code);
        return $this->success($code);
    }
}
