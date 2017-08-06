<?php
    /**
     * Created by PhpStorm.
     * User: YHx
     * Date: 2017/8/2
     * Time: 16:29
     */
namespace app\admin\validate;

use think\Validate;

class CompAdministrationValidate extends Validate
{
    protected $rule = [
        'comp_id'=>'gt:0',
        'legal_disputes' => 'require|integer',//企业民事法律纠纷次数
        'civil_law' => 'require|integer',//股东、法人、高管民事法律纠纷次数
        'criminal_law' => 'require|integer',//股东、法人、高管刑事法律纠纷次数

    ];
    protected $message = [
        'comp_id.gt' => '请选择企业名称',
        'legal_disputes.integer' => '请输入数字',
        'legal_disputes.require' => '请输入企业民事法律纠纷次数',
        'civil_law.integer' => '请输入数字',
        'civil_law.require' => '请输入股东、法人、高管民事法律纠纷次数',
        'criminal_law.integer' => '请输入数字',
        'criminal_law.require' => '请输入股东、法人、高管刑事法律纠纷次数',

    ];

    protected $scene = [
//        'add'  => ['user_login,user_pass,user_email'],
//        'edit' => ['user_login,user_email'],
    ];

}
?>