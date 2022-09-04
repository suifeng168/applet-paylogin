<?php
namespace Applet\Pay;
class Toutiao{
	private $orderParam;
	private $app_id;
	private $secret;
	private $debug=false;
	private $salt;
	private $valid_time;
	private $notify_url;
	private $token;
	private $codeUrl='https://developer.toutiao.com/api/apps/v2/jscode2session'; //code2Session
	private $tokenUrl='https://developer.toutiao.com/api/apps/v2/token';//token
	protected $payUrl='https://developer.toutiao.com/api/apps/ecpay/v1/create_order';//预下单
	protected $query='https://developer.toutiao.com/api/apps/ecpay/v1/query_order';//订单查询
	protected $refundUrl='https://developer.toutiao.com/api/apps/ecpay/v1/create_refund';//订单退款
	private $notifyOrder;
	public static function init($config){
		if(!isset($config['app_id'])||empty($config['app_id'])){
			throw new \Exception('not empty app_id');
		}
		if(!isset($config['secret'])||empty($config['secret'])){
			throw new \Exception('not empty secret');
		}
		$class=new self();
		if($config['debug']){
			$class->codeUrl='https://open-sandbox.douyin.com/api/apps/v2/jscode2session';
			$class->payUrl='https://open-sandbox.douyin.com/api/apps/ecpay/v1/create_order';
			$class->tokenUrl='https://open-sandbox.douyin.com/api/apps/v2/token';
			$class->query='https://open-sandbox.douyin.com/api/apps/ecpay/v1/query_order';
			$class->refundUrl='https://open-sandbox.douyin.com/api/apps/ecpay/v1/create_refund';
		}
		$class->app_id=$config['app_id'];
		$class->secret=$config['secret'];
		$class->token=$config['token'];
		$class->debug=$config['debug'];
		$class->salt=$config['salt'];
		$class->notify_url=$config['notify_url'];
		$class->valid_time=isset($config['valid_time'])?$config['valid_time']:time()+900;
		return $class;
	}
	/**
	 * 获取 openid
	 * @param string $code
	 * @return void
	 */
	public function getOpenid($code){
		$config=[
			'appid' =>$this->app_id,
			'secret'=>$this->secret,
			'code'  =>$code
		];
		$result=json_decode($this->curl_post($this->codeUrl,$config),true);
		return $result;
	}
	/**
	 * 预下单
	 * @param $options 下单数组

	 */
	public function createOrder($options=[]){
		$this->orderParam["alid_time"]=$this->valid_time;
		$this->orderParam["app_id"]=$this->app_id;
		$this->orderParam = array_merge($this->orderParam, $options);
		$data=["sign"=>$this->sign($this->orderParam)]+$this->orderParam;
		$result=json_decode($this->curl_post($this->payUrl,$data),true);
		return $result;
	}
	/**
	 * 支付签名
	 * @param array $map
	 * @return string
	 */
	public function sign(array $map){
		$rList=[];
		foreach($map as $k=>$v){
			if($k=="other_settle_params"||$k=="app_id"||$k=="sign"||$k=="thirdparty_id"){
				continue;
			}
			$value=trim(strval($v));
			$len=strlen($value);
			if($len>1&&substr($value,0,1)=="\""&&substr($value,$len,$len-1)=="\""){
				$value=substr($value,1,$len-1);
			}
			$value=trim($value);
			if($value==""||$value=="null"){
				continue;
			}
			array_push($rList,$value);
		}
		array_push($rList,$this->salt);
		sort($rList,2);
		return md5(implode('&',$rList));
	}
	/**
	 * 获取token
	 */
	public function getToken(){
		$arr=[
			'grant_type'=>'client_credential',
			'appid'     =>$this->app_id,
			'secret'    =>$this->secret,
		];
		$result=json_decode($this->curl_post($this->tokenUrl,$arr),true);
		return $result;
	}

	/**
	 * 异步回调
	 * @param  $order 回调数据
	 * @return bool true   验签通过|false 验签不通过
	 */
	public function verify(){
		$data=file_get_contents("php://input");
		$order=json_decode($data,true);
		$order['msg']=json_decode($order['msg'],true);
		$data=[
			$order['timestamp'],
			$order['nonce'],
			json_encode($order['msg']),
			$this->token,
		];
		sort($data,SORT_STRING);
		$str=implode('',$data);
		if(!strcmp(sha1($str),$order['msg_signature'])){
			return $order;
		}
		return false;
	}
	/**
	 * 申请退款
	 */
	public function applyOrderRefund($order){
		$order['notify_url']=$this->notify_url;
		$order['app_id']=$this->app_id;
		$result=json_decode($this->curl_post($this->refundUrl,['sign'=>$this->sign($order)]+$order),true);
		return $result;
	}
	/**
	 * 订单查询
	 * @param string $out_order_no 订单号
	 * @return array 订单信息
	 */
	public function findOrder($out_order_no){
		if(empty($out_order_no)){
			return false;
		}
		$order=[
			'out_order_no'=>$out_order_no,
			'app_id'      =>$this->app_id,
		];
		$order['sign']=$this->sign($order);
		$result=json_decode($this->curl_post($this->query,$order),true);
		return $result;
	}
	/**
	 * 发送模板消息
	 * @param $token 小程序 access_token
	 * @param $tpl_id 模板的 id
	 * @param $open_id 接收消息目标用户的 open_id
	 * @param array $send_data 模板内容
	 * @param string $page 跳转的页面
	 * @return mixed
	 */
	public function sendMessage($token,$tpl_id,$open_id,$send_data=[],$page=''){
		$template=[
			'access_token'      =>$token,
			'app_id'           =>$this->app_id,
			'tpl_id'             =>$tpl_id,
			'open_id'=>$open_id,
			'data'             =>$send_data,
			'page'             =>$page
		];
		$result=json_decode($this->curl_post('https://developer.toutiao.com/api/apps/subscribe_notification/developer/v1/notify',$template),true);
		return $result;
	}
	/**
	 * 解密手机号
	 * @param string $session_key 前端传递的session_key
	 * @param string $iv 前端传递的iv
	 * @param string $encryptedData 前端传递的encryptedData
	 */
	public function decryptPhone($session_key,$iv,$encryptedData){
		if(strlen($session_key)!=24){
			return false;
		}
		$aesKey=base64_decode($session_key);
		if(strlen($iv)!=24){
			return false;
		}
		$aesIV=base64_decode($iv);
		$aesCipher=base64_decode($encryptedData);
		$result=openssl_decrypt($aesCipher,"AES-128-CBC",$aesKey,1,$aesIV);
		$dataObj=json_decode($result);
		if($dataObj==null){
			return false;
		}
		if($dataObj->watermark->appid!=$this->app_id){
			return false;
		}
		return json_decode($result,true);
	}
	/**
	 * get
	 * @param $url
	 * @return bool|string
	 */
	protected static function curl_get($url){
		$headerArr=["Content-type:application/x-www-form-urlencoded"];
		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headerArr);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
		$output=curl_exec($ch);
		if(!$output){
			throw new \Exception(curl_errno($ch));
		}
		curl_close($ch);
		return $output;
	}
	/**
	 * post
	 * @param $url 网址
	 * @param $data 数组
	 * @return bool|string
	 */
	protected static function curl_post($url,$data){
		$data=json_encode($data);
		$curl=curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($curl,CURLOPT_POST,true);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json','Content-Length:'.strlen($data)]);
		$result=curl_exec($curl);
		if(curl_errno($curl)){
			throw new \Exception('Errno'.curl_errno($curl));
		}
		curl_close($curl);
		return $result;
	}
}
