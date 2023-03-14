<?php

namespace App\Services\User;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Models\User\User;
use App\Notifications\VerificationCode;
use App\Services\BaseServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Leonis\Notifications\EasySms\Channels\EasySmsChannel;
use Overtrue\EasySms\PhoneNumber;

class UserServices extends BaseServices
{
    public function getUserById($id)
    {
        return User::find($id);
    }

    public function getUsers(array $userIds)
    {
        if (empty($userIds)) {
            return collect([]);
        }

        return User::query()->whereIn('id', $userIds)->get();
    }

    /**
     * 根据用户名获取用户
     * @param $userName
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByUserName($userName)
    {
        return User::query()->where('username', $userName)->first();
    }

    /**
     * 根据手机号获取用户
     * @param $mobile
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function getByMobile($mobile)
    {
        return User::query()->where('mobile', $mobile)->first();
    }

    /**
     * 验证手机号发送验证码是否达到限制条数
     * @param string $mobile
     * @return bool
     */
    public function checkMobileSendCaptchaCount(string $mobile)
    {
        $countKey = 'register_captcha_count_' . $mobile;
        if (Cache::has($countKey)) {
            $count = Cache::increment($countKey);
            if ($count > 10) {
                return false;
            }
        } else {
            Cache::put($countKey, 1, Carbon::tomorrow()->diffInSeconds(now()));
        }

        return true;
    }

    /**
     * 发送验证码短信
     * @param string $mobile
     * @param string $code
     * @return void
     */
    public function sendCaptchaMsg(string $mobile, string $code)
    {
        if (app()->environment('testing')) {
            return;
        }

        // 发送短信
        Notification::route(
            EasySmsChannel::class,
            new PhoneNumber($mobile, 86)
        )->notify(new VerificationCode($code));
    }

    /**
     * 验证短信验证码
     * @param string $mobile
     * @param string $code
     * @return bool
     * @throws BusinessException
     */
    public function checkCaptcha(string $mobile, string $code)
    {
        $key = 'register_captcha_' . $mobile;
        $isPass = $code === Cache::get($key);
        if ($isPass) {
            Cache::forget($key);
            return true;
        } else {
            throw new BusinessException(CodeResponse::AUTH_CAPTCHA_UNMATCH);
        }
    }

    /**
     * 设置手机短信验证码
     * @param string $mobile
     * @return string
     * @throws \Exception
     */
    public function setCaptcha(string $mobile)
    {
        // 随机生成6位验证码
        $code = random_int(100000, 999999);
        if (!app()->environment('production')) {
            $code = 111111;
        }

        // 保存手机号和验证码的关系
        $code = strval($code);
        Cache::put('register_captcha_' . $mobile, $code, 600);
        return $code;
    }
}
