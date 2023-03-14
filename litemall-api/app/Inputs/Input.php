<?php

namespace App\Inputs;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\VerifyRequestInput;
use Illuminate\Support\Facades\Validator;

class Input
{
    use VerifyRequestInput;

    /**
     * @param $scene
     * @param null|array $data
     * @return $this
     * @throws BusinessException
     */
    public function fill($scene, $data = null)
    {
        if (is_null($data)) {
            $data = request()->input();
        }

        $sceneFields = $this->scene()[$scene] ?? [];
        $rules = $this->rules();
        if (!empty($sceneFields)) {
            $rules = array_filter($rules, function ($key) use ($sceneFields) {
                return in_array($key, $sceneFields);
            }, ARRAY_FILTER_USE_KEY);
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            //throw new BusinessException(CodeResponse::PARAM_VALUE_ILLEGAL);
            $errMsg = json_encode($validator->errors()->getMessages(), JSON_UNESCAPED_UNICODE);
            throw new BusinessException(CodeResponse::PARAM_VALUE_ILLEGAL, $errMsg);
        }

        // 过滤未定义参数
        $map = get_object_vars($this);
        $keys = array_keys($map);

        collect($data)->map(function ($v, $k) use ($keys) {
            if (in_array($k, $keys)) {
                $this->$k = $v;
            }
        });

        return $this;
    }

    public function rules()
    {
        return [];
    }

    public function scene()
    {
        return [];
    }

    /**
     * @param null|array $data
     * @param string $scene
     * @return Input|static
     * @throws BusinessException
     */
    public static function new($scene = '', $data = null)
    {
        return (new static())->fill($scene, $data);
    }
}
