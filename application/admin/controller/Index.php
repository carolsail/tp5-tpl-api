<?php
namespace app\admin\controller;

use app\admin\BaseController;

class Index extends BaseController
{
	// 如果$noNeedLogin为空表示所有接口都需要登录才能请求
    // 如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    // 如果接口已经设置无需登录,那也就无需鉴权了
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['index', 'validateDemo', 'loginDemo', 'registerDemo', 'uploadFileDemo'];  //无须携带token访问
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = '*';

    public function initialize()
    {
        parent::initialize();
        \think\facade\Hook::add('upload_after', function($params) {
            echo 'upload after ...';
        });
    }

    /**
     * 响应多语言
     * api/index/index
     */
    public function index()
    {
        $lang = __('Please login first');
        $this->success('success', ['name'=>'test', 'age'=>10, 'lang'=>$lang]);
    }

    /**
     * 路由实例
     * hello/:name
     */
    public function hello($name = 'ThinkPHP5')
    {
        return 'hello,' . $name;
    }

    /**
     * 验证器实例
     * api/index/validateDemo?type=array
     */
    public function validateDemo($type='array'){
    	$data = [
    		'name' => 'test',
    		'age'  => 150,
    		'email' => '123.com'
    	];
        if($type == 'array'){
            $rule = [
                'name'  => 'require|max:25',
                'age'   => 'number|between:1,120',
                'email' => 'email'
            ];
            $msg = [
                'name.require' => '姓名必填',
                'name.max' => '姓名最长25',
                'age.number' => '年龄格式不对',
                'age.between' => '年龄1~120间',
                'email.email' => '邮箱格式有误'
            ];
            $this->validateFailException();
            $this->validate($data, $rule, $msg, true);
        }else{
            $this->validateFailException();
            $this->validate($data, 'app\admin\validate\Test'); 
        }
    }

    /**
     * 会员登陆实例
     * api/index/loginDemo
     */
    public function loginDemo(){
        $data = $this->request->request();
        if($this->auth->login($data['account'], $data['password'])) {
            halt($this->auth->getUserinfo());
        }else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 会员注册实例
     */
    public function registerDemo(){
        [$username, $password, $email, $mobile] = ['test', '123456', 'test1@gmail.com', '13000000000'];
        if($this->auth->register($username, $password, $email, $mobile)){
            halt($this->auth->getUserinfo());
        }else{
            $this->error($this->auth->getError());
        }
    }

    /**
     * 会员注销实例
     */
    public function logoutDemo()
    {
        if($this->auth->logout()){
            $this->success('Logout successful');
            // 清空了token这里做重定向
        }else{
            $this->error('Logout error');
        }
    }

    /**
     * 会员修改密码实例
     */
    public function changePasswordDemo(){
        if($this->auth->changePassword('123456', '', true)){
            $this->success('Change in successful');
            // 同时清空了token这里做重定向处理
        }else{
            $this->error($this->auth->getError());
        }
    }

    /**
     * 检测Token是否过期
     */
    public function checkTokenDemo()
    {
        $token = $this->auth->getToken();
        $tokenInfo = \app\common\library\Token::get($token);
        halt($tokenInfo);
    }

    /**
     * 刷新Token
     * 传递秒数可控制刷新token过期延长时间
     */
    public function refreshTokenDemo()
    {
        $token = $this->auth->getToken();
        $tokenInfo = \app\common\library\Token::refresh($token, 3600);
        halt($tokenInfo);
    }

    /**
     * 删除Token
     */
    public function deleteTokenDemo(){
        $token = $this->auth->getToken();
        if(\app\common\library\Token::delete($token)){
            halt('删除Token成功');
        }
        halt('删除Token失败');
    }

    /**
     * 上传文件
     */
    public function uploadFileDemo(){
        $upload = \app\common\library\Upload::instance();
        if($info = $upload->start('file')){
            halt($info);
        }else{
            halt($upload->getError());
        }
    }


}
