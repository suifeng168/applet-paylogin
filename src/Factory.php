<?php
namespace Applet\Pay;
class Factory{
	public static $instance=[
		'Baidu'   =>'\Applet\Pay\Baidu',
		'Toutiao'    =>'\Applet\Pay\Toutiao',
		'Weixin'  =>'\Applet\Pay\Weixin',
		'Kuaishou'=>'\Applet\Pay\Kuaishou',
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
