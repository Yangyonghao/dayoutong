<?php
    /**
     * Created by PhpStorm.
     * User: YHx
     * Date: 2017/8/2
     * Time: 16:29
     */
namespace app\admin\validate;

use think\Validate;

class CompBasicValidate extends Validate
{
    protected $rule = [
        'post_title' => 'require',
    ];
    protected $message = [
        'post_title.require' => '文章标题不能为空',
    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];
}
?>