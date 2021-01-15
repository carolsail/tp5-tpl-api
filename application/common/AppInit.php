<?php
namespace app\common;

use think\Request;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Env;
use think\facade\Lang;

class AppInit
{
	public function moduleInit(Request $request)
    {
        // 设置mbstring字符编码
        mb_internal_encoding("UTF-8");

        $moduleName = strtolower($request->module());
        $appName = strtolower(Config::get('app.app_name')) ?: 'api';
        $langKey = 'lang_' . $appName . '_' . $moduleName;
        if($request->get('lang')){
            Cookie::set($langKey, $request->get('lang'));
        }else{
            if(!Cookie::has($langKey)) Cookie::set($langKey, Config::get('lang.' . $moduleName));
        }
		// 加载模块公共语言包(zh-cn, zh-hk, en-us...)
        Lang::load(Env::get('app_path') . $moduleName . DIRECTORY_SEPARATOR. 'lang' . DIRECTORY_SEPARATOR . Cookie::get($langKey) . '.php');
    }
}