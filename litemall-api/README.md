> 遇到的问题：
> 1. 该教程录制者的gitee 仓库无法访问，不清楚是不是因为被gitee设置为了私有仓库。 
> 解决方案：数据库文件大家可以去litemall项目中获取，然后直接导入到你的mysql库中。 路径litemall/litemall-db/sql 中。我已经放到本项目resources/sql中了
> 
> 2. 一开始服务一直无法生效。发现是因为 laradock 中的路径没有设置正确
> 3. readme 文件夹 放有phpstrom 快捷键list


#tymon/jwt-auth

php artisan jwt:secret


#文件软链
php artisan storage:link

phpunit9 phpstorm版本需升级


#提醒
出现跨域问题看下nginx（nginx.htaccess）配置是否遗漏
