<?php
namespace Applet\Pay;
class Factory{
	public static $instance=[
		'Toutiao'=>'\Applet\Pay\Toutiao',
		'Weixin' =>'\Applet\Pay\Weixin',
		'Alipay'=>'\Applet\Pay\Alipay',
	];
	public static function getInstance($ClassName){
		static $class;
		if(isset($class[$ClassName])){
			return $class[$ClassName];
		}
		return $class[$ClassName]=new self::$instance[$ClassName]();
	}
}
