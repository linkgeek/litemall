<?php

namespace App\Services\User;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Inputs\AddressInput;
use App\Models\User\Address;
use App\Services\BaseServices;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AddressServices extends BaseServices
{
    public function getDefaultAddress($userId)
    {
        return Address::query()->where('user_id', $userId)
            ->where('is_default', 1)->first();
    }

    /**
     * 获取地址或者返回默认地址
     * @param $userId
     * @param null $addressId
     * @return Address|Builder|Model|object|null
     * @throws BusinessException
     */
    public function getAddressOrDefault($userId, $addressId = null)
    {
        // 获取地址
        if (empty($addressId)) {
            $address = AddressServices::getInstance()->getDefaultAddress($userId);
        } else {
            $address = AddressServices::getInstance()->getAddress($userId, $addressId);
            if (empty($address)) {
                $this->throwBadArgumentValue();
            }
        }

        return $address;
    }

    /**
     * 获取地址列表
     * @param int $userId
     * @return \App\Models\User\Address[]|Collection
     */
    public function getAddressListByUserId(int $userId)
    {
        return Address::query()->where('user_id', $userId)->get();
    }

    /**
     * 获取用户地址
     * @param $userId
     * @param $addressId
     * @return \App\Models\User\Address|Model|null
     */
    public function getAddress($userId, int $addressId)
    {
        return Address::query()->where('user_id', $userId)->where('id', $addressId)->first();
    }

    /**
     * 删除地址
     * @param $userId
     * @param $addressId
     * @return bool|null
     * @throws BusinessException
     */
    public function delete($userId, $addressId)
    {
        $address = $this->getAddress($userId, $addressId);
        if (empty($address)) {
            $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }

        return $address->delete();
    }

    /**
     * 新增/修改地址
     * @param $userId
     * @param AddressInput $input
     * @return Address|Model|null
     */
    public function saveAddress($userId, AddressInput $input)
    {
        if (!is_null($input->id)) {
            $address = AddressServices::getInstance()->getAddress($userId, $input->id);
        } else {
            $address = Address::new();
            $address->user_id = $userId;
        }

        if ($input->isDefault) {
            $this->resetDefault($userId);
        }

        $address->address_detail = $input->addressDetail;
        $address->area_code = $input->areaCode;
        $address->city = $input->city;
        $address->county = $input->county;
        $address->is_default = $input->isDefault;
        $address->name = $input->name;
        $address->postal_code = $input->postalCode;
        $address->province = $input->province;
        $address->tel = $input->tel;
        $address->save();
        return $address;
    }

    /**
     * @param $userId
     * @return bool|int
     */
    public function resetDefault($userId)
    {
        return Address::query()->where('user_id', $userId)
            ->where('is_default', 1)
            ->update(['is_default' => 0]);
    }
}
