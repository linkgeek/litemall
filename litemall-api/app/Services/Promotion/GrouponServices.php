<?php

namespace App\Services\Promotion;

use App\CodeResponse;
use App\Enums\GrouponEnums;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Groupon;
use App\Models\Promotion\GrouponRules;
use App\Services\BaseServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\AbstractFont;
use Intervention\Image\Facades\Image;
use PhpParser\Builder;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GrouponServices extends BaseServices
{
    public function getGrouponRules(PageInput $page, $columns = ['*'])
    {
        return GrouponRules::whereStatus(GrouponEnums::RULE_STATUS_ON)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

    public function getGrouponRulesById($id, $columns = ['*'])
    {
        return GrouponRules::query()->find($id, $columns);
    }

    /**
     * 获取参团人数
     * @param int $openGrouponId 开团团购活动id
     * @return int
     */
    public function countGrouponJoin($openGrouponId)
    {
        return Groupon::query()->whereGrouponId($openGrouponId)
            ->where('status', '!=', GrouponEnums::STATUS_NONE)
            ->count(['id']);
    }

    /**
     * 用户是否参与或开启某个团购
     * @param $userId
     * @param $grouponId
     * @return bool
     */
    public function isOpenOrJoin($userId, $grouponId)
    {
        return Groupon::query()->whereUserId($userId)
            ->where(function (Builder $builder) use ($grouponId) {
                return $builder->where('groupon_id', $grouponId)
                    ->orWhere('id', $grouponId);
            })->where('status', '!=', GrouponEnums::STATUS_NONE)->exists();
    }

    /**
     * 校验用户是否可以开启或参与某个团购活动
     * @param $userId
     * @param $ruleId
     * @param $linkId
     * @return void
     * @throws BusinessException
     */
    public function checkGrouponValid($userId, $ruleId, $linkId = null)
    {
        if ($ruleId == null || $ruleId <= 0) {
            return;
        }

        $rule = $this->getGrouponRulesById($ruleId);
        if (is_null($rule)) {
            $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }

        // 团购活动已过期
        if ($rule->status == GrouponEnums::RULE_STATUS_DOWN_EXPIRE) {
            $this->throwBusinessException(CodeResponse::GROUPON_EXPIRED);
        }

        // 团购活动已下线
        if ($rule->status == GrouponEnums::RULE_STATUS_DOWN_ADMIN) {
            $this->throwBusinessException(CodeResponse::GROUPON_OFFLINE);
        }

        // 开团
        if ($linkId == null || $linkId <= 0) {
            return;
        }

        // 参购人数已满
        if ($this->countGrouponJoin($linkId) >= ($rule->discount_member - 1)) {
            $this->throwBusinessException(CodeResponse::GROUPON_FULL);
        }

        // 判断是否已开团/参团
        if ($this->isOpenOrJoin($userId, $linkId)) {
            $this->throwBusinessException(CodeResponse::GROUPON_JOIN);
        }

        return;
    }

    public function getGroupon($id, $columns = ['*'])
    {
        return Groupon::query()->find($id, $columns);
    }

    /**
     * 生成团购记录
     * @param $userId
     * @param $orderId
     * @param $ruleId
     * @param null $linkId
     * @return int|null
     */
    public function openOrJoinGroupon($userId, $orderId, $ruleId, $linkId = null)
    {
        // 卫语句
        if ($ruleId == null || $ruleId <= 0) {
            return $linkId;
        }

        $groupon = Groupon::new();
        $groupon->order_id = $orderId;
        $groupon->user_id = $userId;
        $groupon->status = GrouponEnums::STATUS_NONE;
        $groupon->rules_id = $ruleId;

        // 开团
        if ($linkId == null || $linkId <= 0) {
            $groupon->creator_user_id = $userId;
            $groupon->creator_user_time = Carbon::now()->toDateTimeString();
            $groupon->groupon_id = 0;
            $groupon->save();
            return $groupon->id;
        }

        // 参团
        $openGroupon = $this->getGroupon($linkId);
        $groupon->creator_user_id = $openGroupon->creator_user_id;
        $groupon->groupon_id = $linkId;
        $groupon->share_url = $openGroupon->share_url;
        $groupon->save();

        return $linkId;
    }

    /**
     * 根据订单号获取团购信息
     * @param $orderId
     * @return Groupon|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function GetGrouponByOrderId($orderId)
    {
        return Groupon::whereOrderId($orderId)->first();
    }

    /**
     * 支付成功，更新团购活动状态
     * @param $orderId
     * @return void
     * @throws BusinessException
     */
    public function payGrouponOrder($orderId)
    {
        $groupon = $this->GetGrouponByOrderId($orderId);
        if (is_null($groupon)) {
            return;
        }

        // 团购规则
        $rule = $this->getGrouponRulesById($groupon->rules_id);
        if ($groupon->groupon_id == 0) {
            $groupon->share_url = $this->createGrouponShareImage($rule);
        }

        $groupon->status = GrouponEnums::STATUS_ON;
        $isSuccess = $groupon->save();
        if (!$isSuccess) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }

        if ($groupon->groupon_id == 0) {
            return;
        }

        $joinCount = $this->countGrouponJoin($groupon->groupon_id);
        if ($joinCount < $rule->discount_member - 1) {
            return;
        }

        $row = Groupon::query()->where(function (Builder $builder) use ($groupon) {
            return $builder->where('groupon_id', $groupon->groupon_id)
                ->orWhere('id', $groupon->groupon_id);
        })->update(['status' => GrouponEnums::STATUS_SUCCEED]);
        if ($row == 0) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }

        return;
    }

    /**
     * 创建团购分享图片
     *
     * 1. 获取链接，创建二维码
     * 2. 合成图片
     * 3. 保存图片，返回图片地址
     * @param GrouponRules $rules
     * @return string
     */
    public function createGrouponShareImage(GrouponRules $rules)
    {
        $shareUrl = route('home.redirectShareUrl', ['type' => 'groupon', 'id' => $rules->goods_id]);
        // 创建二维码
        $qrCode = QrCode::format('png')->margin(1)->size(290)->generate($shareUrl);
        //return $qrCode;

        // 合成图片
        $goodsImage = Image::make($rules->pic_url)->resize(660, 660);
        $image = Image::make(resource_path('image/back_groupon.png'))
            ->insert($qrCode, 'top-left', 458, 773)
            ->insert($goodsImage, 'top-left', 71, 69)
            ->text($rules->goods_name, 67, 867, function (AbstractFont $font) {
                $font->color([167, 136, 69]);
                $font->size(28);
                $font->file(resource_path('ttf/msyh.ttf'));
            });
        //return $image->encode();

        // 保存图片
        $filePath = 'groupon/' . Carbon::now()->toDateString() . '/' . Str::random() . '.png';
        Storage::disk('public')->put($filePath, $image->encode());
        return Storage::url($filePath);
    }

    public function getGrouponOrderInOrderIds($orderIds)
    {
        return Groupon::query()->whereIn('order_id', $orderIds)->pluck('order_id')->toArray();
    }
}
