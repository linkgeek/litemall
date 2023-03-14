<?php

namespace App;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

trait VerifyRequestInput
{
    /**
     * id 类型
     * @param $key
     * @param $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyId($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer|digits_between:1,20');
    }

    /**
     * 字符串类型
     * @param $key
     * @param $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyString($key, $default = null)
    {
        return $this->verifyData($key, $default, 'string');
    }

    /**
     * 布尔类型
     * @param $key
     * @param $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyBoolean($key, $default = null)
    {
        return $this->verifyData($key, $default, 'boolean');
    }

    /**
     * 整型
     * @param $key
     * @param $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyInteger($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer');
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyPositiveInteger($key, $default = null)
    {
        return $this->verifyData($key, $default, 'integer|min:1');
    }

    /**
     * 枚举类型
     * @param $key
     * @param $default
     * @param $enum
     * @return mixed
     * @throws BusinessException
     */
    public function verifyEnum($key, $default = null, $enum = [])
    {
        return $this->verifyData($key, $default, Rule::in($enum));
    }

    /**
     * @param $key
     * @param  null  $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyArrayNotEmpty($key, $default = null)
    {
        return $this->verifyData($key, $default, 'array|min:1');
    }

    /**
     * 手机号验证
     * @param $key
     * @param  null  $default
     * @return mixed
     * @throws BusinessException
     */
    public function verifyMobile($key, $default = null)
    {
        return $this->verifyData($key, $default, 'regex:/^1[0-9]{10}$/', CodeResponse::AUTH_INVALID_MOBILE);
    }

    /**
     * @param $key
     * @param $default
     * @param $rule
     * @param array $codeResponse
     * @return mixed
     * @throws BusinessException
     */
    public function verifyData($key, $default, $rule, $codeResponse = CodeResponse::PARAM_VALUE_ILLEGAL)
    {
        $value = request()->input($key, $default);
        $validator = Validator::make([$key => $value], [$key => $rule]);

        if (is_null($default) && is_null($value)) {
            return $value;
        }
        if ($validator->fails()) {
            throw new BusinessException($codeResponse);
        }

        return $value;
    }
}
