<?php
use Applet\Pay\Factory as Applet;
//注意 引入命名空间 如果不引入就需要把下面Applet::getInstance 改为 \Applet\Pay\Factory::getInstance
/**
 * 全局配置参数
 */
$Config=[
	'appid'             =>'',
	'debug'             =>true,//沙盒开关 关闭设置为 false
	'screct_key'        =>'',//解密手机号的 AES密钥
	'notify_url'        =>'',//异步通知
	'rsaPrivateKey'     =>'',//请填写开发者私钥去头去尾去回车，一行字符串
	'alipayrsaPublicKey'=>'',//请填写支付宝公钥，一行字符串
];
/**
 * 获取用户openid
 * 需要从小程序传递过 code
 */
$code='123';
$result=Applet::getInstance('Alipay')->init($Config)->getOpenid($code);
if(!isset($result['alipay_user_info_share_response']['code'])){
	die('登录失败，错误码：'.$result['error_response']['code']);
}
$baseInfo=$result['alipay_user_info_share_response'];
//$baseInfo 就是我们需要的 $baseInfo['user_id']
print_r($baseInfo);
exit;
/**
 * 绑定手机号
 * 需要从小程序获取 code
 */
$code='';
$result=Applet::getInstance('Alipay')->init($Config)->decryptPhone($code);
if(isset($result['mobile'])){
	//$result['mobile'] //手机号
	print_r($result);
	exit;
}else{
	die('获取手机号失败');
}
/**
 * 支付
 */
$options=[
	'out_trade_no'=>1,// 订单号
	'total_amount'=>2,// 订单金额，**单位：元**
	'buyer_id'    =>3,//支付人的openid
	'subject'     =>4,// 订单描述
	'notify_url'  =>'',// 定义通知URL 不设置调用总配置
];
$result=Applet::getInstance('Alipay')->init($Config)->createOrder($options);
if(!isset($result['alipay_trade_create_response']['code'])){
	die('支付失败，错误码：'.$result['error_response']['code']);
}else{
	//成功执行
}
/**
 * 异步回调
 */
$result=Applet::getInstance('Alipay')->init($Config)->verify($_POST);
if($result['trade_status']=='TRADE_SUCCESS'||$result['trade_status']=='TRADE_FINISHED'){
	return 'success';//必须
}
/**
 * 申请退款
 */
$options=[
	'out_trade_no' =>'123',//平台订单号
	'refund_reason'=>'退款', //退款说明
	'refund_amount'=>0.01,//退款金额 不能大于订单金额
];
$result=Applet::getInstance('Alipay')->init($Config)->applyOrderRefund($options);
/**
 * 订单查询
 */
$options=[
	'out_trade_no'=>'123',//平台订单号
];
$result=Applet::getInstance('Alipay')->init($Config)->findOrder($options);
/**
 * 发送模板消息
 */
$options=[
	'to_user_id'      =>'参数1',//接收模板消息的用户 user_id
	'user_template_id'=>'参数2',//消息模板ID
	'page'            =>'',//小程序的跳转页面
	//模板参数
	'data'            =>[
		'thing4' =>'参数1',
		'phrase5'=>'参数2'
	]
];
$result=Applet::getInstance('Alipay')->init($Config)->sendMessage($options);