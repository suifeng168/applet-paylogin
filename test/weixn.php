<?php
use Applet\Pay\Factory as Applet;
//注意 引入命名空间 如果不引入就需要把下面Applet::getInstance 改为 \Applet\Pay\Factory::getInstance
/**
 * 全局配置参数
 */
$Config=[
	'appid'     =>'',
	'secret'    =>'',
	'mch_id'    =>'',//商户号
	'mch_key'   =>'',//微信支付密钥 V2
	'notify_url'=>'',//异步通知地址
	'cert_pem'  =>'',
	'key_pem'   =>''
];
/**
 * 获取access_token
 */
$result=Applet::getInstance('Weixin')->init($Config)->getToken();
/**
 * 获取用户openid
 * 需要从小程序传递过 code
 */
$code='123';
$result=Applet::getInstance('Weixin')->init($Config)->getOpenid($code);
if($result['errcode']!=0){
	die('登录失败，错误码：'.$result['errcode']);
}
//$result 就是我们需要的 $result['openid']
print_r($result);
exit;
/**
 * 绑定手机号
 * 需要从小程序获取 code iv encrypted_data
 */
$code='';
$iv='';
$encrypted_data='';
$result=Applet::getInstance('Weixin')->init($Config)->getOpenid($code);
if($result['errcode']!=0){
	die('登录失败，错误码：'.$result['errcode']);
}
$result=Applet::getInstance('Weixin')->init($Config)->decryptPhone($result['session_key'],$iv,$encrypted_data);
//$result['phoneNumber'] //手机号
print_r($result);
exit;
/**
 * 支付
 */
$options=[
	'out_trade_no'    =>'',// 订单号
	'total_fee'       =>'',// 订单金额，**单位：分**
	'body'            =>'',// 订单描述
	'spbill_create_ip'=>'',//ip 去掉这项
	'openid'          =>'',// 支付人的 openID
	'notify_url'      =>'',// 定义通知URL
];
$result=Applet::getInstance('Weixin')->init($Config)->createOrder($options);
if($result['return_code']=='SUCCESS'){
	//$result['payment'];//支付参数
	print_r($result);
	exit;
}
/**
 * 异步回调
 */
$result=Applet::getInstance('Weixin')->init($Config)->verify();
if($result['result_code']=='SUCCESS'&&$result['return_code']=='SUCCESS'){
	$result['out_trade_no']; //网站订单号
	$result['transaction_id']; //流水号
	$result['openid']; //支付账户
	return 'success'; //必须输出一个 success
}
/**
 * 申请退款
 */
$options=[
	'out_trade_no' =>'123',//平台订单号
	'total_fee'    =>0.01, //订单金额
	'out_refund_no'=>time(),//自定义退款单号
	'refund_fee'   =>0.01,//退款金额 不能大于订单金额
];
$result=Applet::getInstance('Weixin')->init($Config)->applyOrderRefund($options);
/**
 * 订单查询
 */
$result=Applet::getInstance('Weixin')->init($Config)->findOrder('订单号');
/**
 * 发送模板消息
 * @param $token 接口调用凭证access_token
 * @param $template_id 所需下发的订阅模板id
 * @param $touser 接收者（用户）的 openid
 * @param string $url 点击模板卡片后的跳转页面
 * @param $send_data 模板内容 数组
 * @return bool|string
 */
$token=''; //接口调用凭证access_token
$template_id=''; //所需下发的订阅模板id
$touser=''; //接收者（用户）的 openid
$url='/page/index/index'; //点击模板卡片后的跳转页面
//模板内容 数组
$send_data=[
	'thing4' =>'参数1',
	'phrase5'=>'参数2'
];
$result=Applet::getInstance('Weixin')->init($Config)->sendMessage($token,$template_id,$touser,$url,$send_data);