<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 用来将 数据中 http开头的url 转为 https
class XiuGaiHttpHttps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'XiuGaiHttpHttps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '修改cdn图片地址 http 改为https';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    /**
     * 历史数据中证件类型为0的会员信息 现在将默认证件类型修改为身份证
     * 历史数据中证件类型为0但有证件号的会员信息， 现在将默认证件类型修改为其它
     * @return void
     */
    public function handle()
    {
        $sql1 = "SELECT * from litemall_goods";
        $data1 = array_map('get_object_vars', DB::select($sql1));
        foreach ($data1 as $key => $val) {
            $picUrl = 'https' . ltrim($val['pic_url'], 'http');
//            $iconUrl = 'https' . ltrim($val['icon_url'], 'http');
//            $updateSql1 = "UPDATE litemall_goods SET pic_url = '{$picUrl}', update_time = '2018-02-01 00:00:00' WHERE id = {$val['id']}";
            $updateSql1 = "UPDATE litemall_goods SET pic_url = '{$picUrl}'";

            DB::update($updateSql1);
        }
//        $sql2 = "SELECT * from litemall_category where 1=1 limit 1";
//        $data2 = array_map('get_object_vars', DB::select($sql2));
//        dd($data1, $data2);
        /*
        for ($i = $fkCount; $i > 0; $i--) {
            $db = $dbPrefix . $i;
            echo '分库' . $db . PHP_EOL;
            echo 'inn_orderguests 表修复身份证类型' . PHP_EOL;
            $updateSql1 = "UPDATE {$db}.inn_orderguests SET idtype = 1, updateon = '" . timeNow() . "' WHERE `idtype` = 0 and  (idnum = '' OR ISNULL(idnum))";
            DB::update($updateSql1);
            $updateSql2 = "UPDATE {$db}.inn_orderguests SET idtype = 5, updateon = '" . timeNow() . "' WHERE `idtype` = 0 and  idnum != ''";
            DB::update($updateSql2);

            echo 'inn_guests 表修复身份证类型' . PHP_EOL;
            $updateSql3 = "UPDATE {$db}.inn_guests SET idtype = 1, updateon = '" . timeNow() . "' WHERE `idtype` = 0 and  (idnum = '' OR ISNULL(idnum))";
            DB::update($updateSql3);
            $updateSql4 = "UPDATE {$db}.inn_guests SET idtype = 5, updateon = '" . timeNow() . "' WHERE `idtype` = 0 and  idnum != ''";
            DB::update($updateSql4);
            echo '-----------------'.PHP_EOL;

        }
        */
    }
}
