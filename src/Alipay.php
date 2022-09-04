<?php
namespace Applet\Pay;
class Alipay{
	private $appid;
	private $rsaPrivateKey;//请填写开发者私钥去头去尾去回车，一行字符串
	private $alipayrsaPublicKey;//请填写支付宝公钥，一行字符串
	private $screct_key;//解密敏感信息的AES密钥 例如手机号
	private $notify_url;
	private $debug=false;
	private $gatewayUrl='https://openapi.alipay.com/gateway.do';
	public static function init($config){
		if(!isset($config['appid'])||empty($config['appid'])){
			throw new \Exception('not empty appid');
		}
		$class=new self();
		if($config['debug']){
			$class->gatewayUrl='https://openapi.alipaydev.com/gateway.do';
		}
		$class->appid=$config['appid'];
		$class->rsaPrivateKey=$config['rsaPrivateKey'];
		$class->alipayrsaPublicKey=$config['alipayrsaPublicKey'];
		$class->screct_key=$config['screct_key'];
		$class->notify_url=$config['notify_url'];
		return $class;
	}
	/**
	 * 获取用户信息
	 * @return array
	 */
	public function getOpenid($authCode){
		$config=[
			'app_id'    =>$this->appid,
			'method'    =>'alipay.system.oauth.token',
			'format'    =>'JSON',
			'charset'   =>'utf8',
			'sign_type' =>'RSA2',
			'timestamp' =>date('Y-m-d H:i:s'),
			'version'   =>'1.0',
			'grant_type'=>'authorization_code',
			'code'      =>$authCode,
		];
		$config["sign"]=$this->generateSign($config,$config['sign_type']);
		$result=$this->curlPost($this->gatewayUrl,$config);
		$result=iconv('GBK','UTF-8',$result);
		$result=json_decode($result,true);
		$share=[
			//公共参数
			'app_id'    =>$this->appid,
			'method'    =>'alipay.user.info.share',
			'format'    =>'JSON',
			'charset'   =>'utf8',
			'sign_type' =>'RSA2',
			'timestamp' =>date('Y-m-d H:i:s'),
			'version'   =>'1.0',
			'auth_token'=>$result['alipay_system_oauth_token_response']['access_token'],
		];
		$share["sign"]=$this->generateSign($share,$share['sign_type']);
		$result2=$this->curlPost($this->gatewayUrl,$share);
		$result2=iconv('GBK','UTF-8',$result2);
		return json_decode($result2,true);
	}
	/**
	 * 预下单
	 * @param $options 下单数组
	 */
	public function createOrder($options=[]){
		$config=[
			//公共参数
			'app_id'     =>$this->appid,
			'method'     =>'alipay.trade.create',//接口名称
			'format'     =>'JSON',
			'charset'    =>'UTF-8',
			'sign_type'  =>'RSA2',
			'timestamp'  =>date('Y-m-d H:i:s'),
			'version'    =>'1.0',
			'notify_url' =>$this->notify_url,
			'biz_content'=>json_encode($options),
		];
		$config["sign"]=$this->generateSign($config,$config['sign_type']);
		$result=$this->curlPost($this->gatewayUrl,$config);
		$result=iconv('GBK','UTF-8',$result);
		return json_decode($result,true);
	}
	/**
	 * 申请退款
	 * https://opendocs.alipay.com/open/02ekfk
	 */
	public function applyOrderRefund($options){
		$config=[
			//公共参数
			'app_id'     =>$this->appid,
			'method'     =>'alipay.trade.refund',//接口名称
			'format'     =>'JSON',
			'charset'    =>'UTF-8',
			'sign_type'  =>'RSA2',
			'timestamp'  =>date('Y-m-d H:i:s'),
			'version'    =>'1.0',
			'biz_content'=>json_encode($options),
		];
		$config["sign"]=$this->generateSign($config,$config['sign_type']);
		$result=$this->curlPost($this->gatewayUrl,$config);
		$result=iconv('GBK','UTF-8',$result);
		return json_decode($result,true);
	}
	/**
	 * 订单查询
	 * https://opendocs.alipay.com/open/02ekfh?scene=23
	 */
	public function findOrder($options){
		$config=[
			//公共参数
			'app_id'     =>$this->appid,
			'method'     =>'alipay.trade.refund',//接口名称
			'format'     =>'JSON',
			'charset'    =>'UTF-8',
			'sign_type'  =>'RSA2',
			'timestamp'  =>date('Y-m-d H:i:s'),
			'version'    =>'1.0',
			'biz_content'=>json_encode($options),
		];
		$config["sign"]=$this->generateSign($config,$config['sign_type']);
		$result=$this->curlPost($this->gatewayUrl,$config);
		$result=iconv('GBK','UTF-8',$result);
		return json_decode($result,true);
	}
	/**
	 * 发送模板消息
	 */
	public function sendMessage($options){
		$config=[
			//公共参数
			'app_id'     =>$this->appid,
			'method'     =>'alipay.open.app.mini.templatemessage.send',//接口名称
			'format'     =>'JSON',
			'charset'    =>'UTF-8',
			'sign_type'  =>'RSA2',
			'timestamp'  =>date('Y-m-d H:i:s'),
			'version'    =>'1.0',
			'biz_content'=>json_encode($options),
		];
		$config["sign"]=$this->generateSign($config,$config['sign_type']);
		$result=$this->curlPost($this->gatewayUrl,$config);
		$result=iconv('GBK','UTF-8',$result);
		return json_decode($result,true);
	}
	/**
	 * 解密手机号
	 * @param string $encryptedData 前端传递的encryptedData
	 */
	public function decryptPhone($encryptedData){
		$result=decryptData($encryptedData);
		return json_decode($result,true);
	}
	/**
	 * 异步回调
	 * @param  $order 回调数据
	 * @return bool true   验签通过|false 验签不通过
	 */
	public function verify($data){
		$sign=$data['sign'];
		unset($data['sign_type']);
		unset($data['sign']);
		$re=$this->verifysign($this->getSignContent($data),$sign,"RSA2");
		if($re){
			if($data['trade_status']=='TRADE_SUCCESS'||$data['trade_status']=='TRADE_FINISHED'){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	protected function verifysign($data,$sign,$signType='RSA'){
		$pubKey=$this->alipayrsaPublicKey;
		$res="-----BEGIN PUBLIC KEY-----\n".wordwrap($pubKey,64,"\n",true)."\n-----END PUBLIC KEY-----";
		//调用openssl内置方法验签，返回bool值
		if("RSA2"==$signType){
			$result=(bool)openssl_verify($data,base64_decode($sign),$res,OPENSSL_ALGO_SHA256);
		}else{
			$result=(bool)openssl_verify($data,base64_decode($sign),$res);
		}
		return $result;
	}
	/**
	 * 商户请求参数的签名串签名
	 * @param $params
	 * @param string $signType
	 * @return string|void
	 */
	public function generateSign($params,$signType="RSA"){
		return $this->sign($this->getSignContent($params),$signType);
	}
	protected function sign($data,$signType="RSA"){
		$priKey=$this->rsaPrivateKey;
		$res="-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey,64,"\n",true)."\n-----END RSA PRIVATE KEY-----";
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
		if("RSA2"==$signType){
			openssl_sign($data,$sign,$res,version_compare(PHP_VERSION,'5.4.0','<')?SHA256:OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($data,$sign,$res);
		}
		$sign=base64_encode($sign);
		return $sign;
	}
	/**
	 * 转换成目标字符集
	 * @param $params
	 * @return string
	 */
	public function getSignContent($params){
		ksort($params);
		$stringToBeSigned="";
		$i=0;
		foreach($params as $k=>$v){
			if(false===$this->checkEmpty($v)&&"@"!=substr($v,0,1)){
				$v=$this->characet($v,'utf8');
				if($i==0){
					$stringToBeSigned.="$k"."="."$v";
				}else{
					$stringToBeSigned.="&"."$k"."="."$v";
				}
				$i++;
			}
		}
		unset ($k,$v);
		return $stringToBeSigned;
	}
	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	protected function checkEmpty($value){
		if(!isset($value)){
			return true;
		}
		if($value===null){
			return true;
		}
		if(trim($value)===""){
			return true;
		}
		return false;
	}
	function characet($data,$targetCharset){
		if(!empty($data)){
			if(strcasecmp('utf8',$targetCharset)!=0){
				$data=mb_convert_encoding($data,$targetCharset,'utf8');
			}
		}
		return $data;
	}
	/**
	 * 解密
	 * @param $encryptedData
	 * @return false|string
	 */
	public function decryptData($encryptedData){
		$aesKey=base64_decode($this->screct_key);
		$iv=0;
		$aesIV=base64_decode($iv);
		$aesCipher=base64_decode($encryptedData);
		$result=openssl_decrypt($aesCipher,"AES-128-CBC",$aesKey,1,$aesIV);
		return $result;
	}
	public function curlPost($url='',$postData='',$options=[]){
		if(is_array($postData)){
			$postData=http_build_query($postData);
		}
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
		curl_setopt($ch,CURLOPT_TIMEOUT,30); //设置cURL允许执行的最长秒数
		if(!empty($options)){
			curl_setopt_array($ch,$options);
		}
		//https请求 不验证证书和host
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		$data=curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}
