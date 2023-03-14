<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use Illuminate\Http\RedirectResponse;
use App\Models\User\User;


class HomeController extends AdminController
{
    protected $only = [];

    /**
     * 分享链接调整
     * @return RedirectResponse
     * @throws BusinessException
     */
    public function redirectShareUrl()
    {
        $type = $this->verifyString('type', 'groupon');
        $id = $this->verifyId('id');

        if ($type == 'groupon') {
            return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
        }

        if ($type == 'goods') {
            return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
        }

        return redirect()->to(env('H5_URL') . '/#/items/detail/' . $id);
    }

    public function test()
    {
        $user = User::factory()->create();
        dd($user);

        //$user = UserServices::getInstance()->getUserById(1);
        dd($user->toArray());
    }
}
