<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Inputs\AddressInput;
use App\Services\User\AddressServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 地址
 * Class AddressController
 * @package App\Http\Controllers\Wx
 */
class AddressController extends WxController
{
    /**
     * 获取用户地址列表
     * @return JsonResponse
     */
    public function list()
    {
        $list = AddressServices::getInstance()->getAddressListByUserId($this->user()->id);
        return $this->successPaginate($list);
    }

    /**
     * 获取收货地址详情
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function detail(Request $request)
    {
        $id = $this->verifyId('id', 0);
        $detail = AddressServices::getInstance()->getAddress($this->user()->id, $id);
        if (empty($detail)) {
            return $this->badArgumentValue();
        }

        return $this->success($detail);
    }

    /**
     * 保存收货地址
     * @return JsonResponse
     * @throws BusinessException
     */
    public function save()
    {
        $input = AddressInput::new();
        $address = AddressServices::getInstance()->saveAddress($this->userId(), $input);
        return $this->success($address->id);
    }

    /**
     * 删除地址
     * @param Request $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function delete(Request $request)
    {
        $id = $this->verifyId('id', 0);
        AddressServices::getInstance()->delete($this->user()->id, $id);
        return $this->success();
    }
}
