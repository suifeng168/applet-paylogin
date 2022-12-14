### 注意事项
微信支付使用APIv2规则 所以需要配置V2接口的密匙
不会用就看 test 目录下的示例
本项目只要是为自己服务 其他人用请自行看文档摸索
支付宝是订阅消息 不是订单消息等
# 安装说明

    composer require suifeng/applet-paylogin
# 功能支持
| 第三方    | token | openid | 支付  | 回调  | 退款  | 订单查询 | 解密手机号 |订阅消息 |
|:------:|:-----:|:------:|:---:|:---:|:---:|:----:|:-----:|:-----:|
| 微信小程序  | ✓     | ✓      | ✓   | ✓   | ✓   | ✓    | ✓     |✓     |
| 支付宝小程序 | x    | ✓      | ✓   | ✓   | ✓   | ✓    | ✓     |✓     |
| 字节小程序  | ✓     | ✓      | ✓   | ✓   | ✓   | ✓    | ✓     |✓     |

# 字节小程序

### Config

| 参数名字       | 类型     | 必须  | 说明              |
| ---------- | ------ | --- | --------------- |
| salt       | string | 是   | 担保交易的 SALT      |
| app_id     | int    | 是   | 小程序的 APP_ID     |
| secret     | string | 是   | 小程序的 APP_SECRET |
| debug     | string | 是   | 沙盒调试开关 使用为 true 不使用为false |
| notify_url | string | 是   | 支付回调 url        |

### token

```php
    $data= \Applet\Pay\Factory::getInstance('Toutiao')->init($config)->getToken();
    
```
### openid

```php
    $code="";//小程序传递过来的
    $data= \Applet\Pay\Factory::getInstance('Toutiao')->init($config)->getOpenid($code);
```
### 预下单
```php
    $options=[
    'out_order_no'=>1,
    'total_amount'=>'2'...
    ];
    appid,valid_time 不需要传递 其他必填都必须传递 
    $pay= \Applet\Pay\Factory::getInstance('Toutiao')->init($config)->createOrder($options);
```  
文档地址：https://microapp.bytedance.com/docs/zh-CN/mini-app/develop/server/ecpay/APIlist/pay-list/pay
### 解密手机号

```php
    $data= \Applet\Pay\Factory::getInstance('Toutiao')->init($config)->decryptPhone($session_key, $iv, $encryptedData);
```

### 字节订单查询

```php
    $Toutiao = \Applet\Pay\Factory::getInstance('Toutiao')->init($config)->findOrder("订单号");
   
```
文档地址：https://microapp.bytedance.com/docs/zh-CN/mini-app/develop/server/ecpay/APIlist/pay-list/query
### 字节退款

```php
    $order = [
            'out_order_no' => '',
            'out_refund_no' => time(),
            'reason' => '说明 都看文档吧',
            'refund_amount' => 1, //退款金额
        ];
    $data= \Applet\Pay\Factory::getInstance('Toutiao')->init($config)->applyOrderRefund($order);
    
```
文档地址：https://microapp.bytedance.com/docs/zh-CN/mini-app/develop/server/ecpay/APIlist/refund-list/refund
### 异步通知
```php
   $result=\Applet\Pay\Factory::getInstance('Toutiao')->init($Config)->verify();
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
    
    
```
文档地址：https://microapp.bytedance.com/docs/zh-CN/mini-app/develop/server/ecpay/APIlist/pay-list/callback  
这里如果有退款 记得要判断下 支付回调和退款回调
### 订阅消息
```php
$token=''; //接口调用凭证access_token
$tpl_id=''; //所需下发的订阅模板id
$open_id=''; //接收者（用户）的 openid
$page='/page/index/index'; //点击模板卡片后的跳转页面
//模板内容 数组
$send_data=[
	'thing4' =>'参数1',
	'phrase5'=>'参数2'
];
$result=Applet::getInstance('Toutiao')->init($Config)->sendMessage($token,$tpl_id,$open_id,$send_data,$page);  
    
```
# 微信小程序

### Config

| 参数名字       | 类型     | 必须  | 说明          |
| ---------- | ------ | --- | ----------- |
| appid      | int    | 是   | 小程序 appid   |
| secret     | int    | 是   | 小程序 secret  |
| mch_id     | string | 是   | 商户 mch_id   |
| mch_key    | string | 是   | 商户 mch_key  |
| notify_url | string | 是   | 异步地址        |
| cert_pem   | string | 是   | cert_pem 证书 |
| key_pem    | string | 是   | key_pem 证书  |

### token

```php
    $data= \Applet\Pay\Factory::getInstance('Weixin')->init($config)->getToken();
    
```
### openid

```php
    $code="";
    $data= \Applet\Pay\Factory::getInstance('Weixin')->init($config)->getOpenid($code);
    
```

### 预下单
```php
    $options=[]; 
    $pay= \Applet\Pay\Factory::getInstance('Weixin')->init($config)->set($options);
   
```
文档地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_1
### 微信解密手机号

```php
    $data= \Applet\Pay\Factory::getInstance('Weixin')->init($config)->decryptPhone($session_key, $iv, $encryptedData);
   
```

### 微信订单查询

```php
    $payName='Weixin';//设置驱动
    $Baidu = \Applet\Pay\Factory::getInstance('Weixin')->init($config);
    $data = $Baidu->findOrder("订单号");
     
```
文档地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_2
### 微信退款

```php
    $order = [
             'out_trade_no' => '123',
            'total_fee' => 0.01,
            'out_refund_no' => time(),
            'refund_fee' => 0.01,
        ];
    $data= \Applet\Pay\Factory::getInstance('Weixin')->init($config)->applyOrderRefund($order);
  
```
文档地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_4
### 异步通知
```php
   $result=\Applet\Pay\Factory::getInstance('Weixin')->init($Config)->verify();
 if($result['result_code']=='SUCCESS'&&$result['return_code']=='SUCCESS'){
	$result['out_trade_no']; //网站订单号
	$result['transaction_id']; //流水号
	$result['openid']; //支付账户
	return 'success'; //必须输出一个 success
}
    
    
```
文档地址：https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=9_7&index=8
### 订阅消息
```php
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
    
```
# 支付宝小程序

### Config

| 参数名字       | 类型     | 必须  | 说明                     |
| ---------- | ------ | --- | ---------------------- |
| appid     | int    | 是   | 小程序 appid              |
| screct_key | int    | 是   | 解密手机号的 AES密钥             |
| notify_url | string | 是   | 异步回调地址                   |
| debug | string | 是   | 沙箱调试开关 使用沙箱设置为ture 不使用设置为false |
| rsaPrivateKey      | int    | 是   | 请填写开发者私钥去头去尾去回车，一行字符串                     |
| alipayrsaPublicKey| int    | 是   | 请填写支付宝公钥，一行字符串                    |

### openid

```php
    $code="";
    $data= \Applet\Pay\Factory::getInstance('Alipay')->init($config)->getOpenid($code);
```


### 支付宝解密手机号

```php
    $code='';
    $data= \Applet\Pay\Factory::getInstance('Alipay')->init($config)->decryptPhone($code);
```
### 预下单
```php
    $options=[
		'out_trade_no'=>1,// 订单号
		'total_amount'=>2,// 订单金额，**单位：元**
		'buyer_id'    =>3,//支付人的 buyer_id
		'subject'     =>4,// 订单描述
	];
    $data= \Applet\Pay\Factory::getInstance('Alipay')->init($config)->createOrder($options);
```
文档地址：https://opendocs.alipay.com/open/02ekfj
### 支付宝订单查询

```php
    $options=[];//这里是数组
    $data = \Applet\Pay\Factory::getInstance('Alipay')->findOrder($options);
     
```
文档地址：https://opendocs.alipay.com/open/02ekfh?scene=common
### 支付宝退款
```php
    $options=[];//这里是数组
    $data= \Applet\Pay\Factory::getInstance('Alipay')->init($config)->applyOrderRefund($options);
```
文档地址：https://opendocs.alipay.com/open/02ekfk

### 异步通知
```php
       
		
   $result=\Applet\Pay\Factory::getInstance('Alipay')->init($Config)->verify($_POST);
    if($result['trade_status']=='TRADE_SUCCESS'||$result['trade_status']=='TRADE_FINISHED'){
		return 'success';//必须输出
    }
    
    
```
文档地址：https://opendocs.alipay.com/open/194/103296
### 订阅消息
```php
       
$options=[
	'to_user_id' =>'参数1',//接收模板消息的用户 user_id
	'user_template_id'=>'参数2',//消息模板ID
	'page'=>'',//小程序的跳转页面
	//模板参数
	'data'=>[
		'thing4' =>'参数1',
		'phrase5'=>'参数2'
	]
];
$result=Applet::getInstance('Alipay')->init($Config)->sendMessage($options);
    
    
```
文档地址：https://opendocs.alipay.com/mini/02cth2

 