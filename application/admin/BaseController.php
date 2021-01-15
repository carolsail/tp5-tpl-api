<?php
namespace app\admin;

use think\Loader;
use app\common\Controller;
use app\common\library\Auth;

class BaseController extends Controller
{

    /**
     * 权限Auth
     * @var Auth 
     */
    protected $auth = null;

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 初始化操作
     * @access protected
     */
    protected function initialize()
    {
        // 移除HTML标签
        $this->request->filter('strip_tags');
        $this->auth = Auth::instance();

        // 是否携带token请求
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\facade\Cookie::get('token')));

        // 设置当前请求的URI：控制器/方法 (利用loader, 字母大写会转为小写加下划线)
        $controllerName = Loader::parseName($this->request->controller());
        $actionName = strtolower($this->request->action());
        $path = str_replace('.', '/', $controllerName) . '/' . $actionName;
        $this->auth->setRequestUri($path);

        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin))
        {
            // 初始化
            $this->auth->init($token);
            // 检测是否登录
            if (!$this->auth->isLogin())
            {
                $this->error(__('Please login first'), null, 401);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight))
            {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path))
                {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        }
        else
        {
            // 如果有传递token才验证是否登录状态
            if ($token)
            {
                $this->auth->init($token);
            }
        }
    }
}