<?php
use Applet\Pay\Factory as Applet;//注意 引入命名空间 如果不引入就需要把下面Applet::getInstance 改为 \Applet\Pay\Factory::getInstance
/**
 * 全局配置参数
 */
$Config=[
	'app_id'    =>'',
	'secret'    =>'',
	'debug'     =>false,//沙盒开关 关闭设置为 false
	'salt'      =>'',//支付salt
	'notify_url'=>''//异步通知
];
/**
 * 获取access_token
 */
$result=Applet::getInstance('Toutiao')->init($Config)->getToken();
/**
 * 获取用户openid
 * 需要从小程序传递过 code
 */
$code='123';
$result=Applet::getInstance('Toutiao')->init($Config)->getOpenid($code);
if($result['err_no']!=0){
	die('登录失败，错误码：'.$result['err_no']);
}
//$result 就是我们需要的 $result['data']['openid']
print_r($result);exit;
/**
 * 绑定手机号
 * 需要从小程序获取 code iv encrypted_data
 */
$code='';
$iv='';
$encrypted_data='';
$result=Applet::getInstance('Toutiao')->init($Config)->getOpenid($code);
if($result['err_no']!=0){
	die('登录失败，错误码：'.$result['err_no']);
}
$result=Applet::getInstance('Toutiao')->init($Config)->decryptPhone($result['data']['session_key'],$iv,$encrypted_data);
//$result['phoneNumber'] //手机号
print_r($result);exit;
/**
 * 支付
 */
$options=[
	'out_order_no'=>'',// 订单号
	'total_amount'=>'',// 订单金额，**单位：分**
	'body'        =>'',// 订单描述
	'subject'     =>'',// 订单描述
	'notify_url'  =>'',// 定义通知URL 去掉这项就用总配置
];
$result=Applet::getInstance('Toutiao')->init($Config)->createOrder($options);
if($result['err_no']==0){
	$result["data"];//支付参数
	print_r($result["data"]);exit;
}
/**
 * 异步回调
 */
$result=Applet::getInstance('Toutiao')->init($Config)->verify();
$msg=$result['msg'];
if($msg['status']=='SUCCESS'){
	$msg['cp_orderno'];; //网站订单号
	$msg['channel_no']; //流水号
	$data=[
		"err_no"  =>0,
		"err_tips"=>"success",
	];
	return json_encode($data); //必须输出一个 json

}
/**
 * 申请退款
 */
$options = [
	'out_order_no' => '',
	'out_refund_no' => time() . 'refund',
	'reason' => '就想退款，咋滴',
	'refund_amount' => 1, //退款金额
];
$result=Applet::getInstance('Toutiao')->init($Config)->applyOrderRefund($options);
/**
 * 订单查询
 */
$result=Applet::getInstance('Toutiao')->init($Config)->findOrder('订单号');