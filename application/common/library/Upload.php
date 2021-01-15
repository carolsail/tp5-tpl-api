<?php
namespace app\common\library;

use think\facade\Env;
use libs\Random;

class Upload 
{
	// 实例
	protected static $instance = null;

	/**
	 * 错误信息
	 */
	protected $_error;

	/**
     * 单例模式 
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * @param $key 文件名
     */
	public function start($key = 'file'){
        $file = request()->file($key);

        // 文件名称与提交文件名不匹配
		if (empty($file)) {
            $this->setError('No file upload or server upload limit exceeded');
            return false;
        }

        // 判断是否已经存在附件
        $sha1 = $file->hash();

        // 读取配置
        $upload = config('upload.');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';
        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);

        // 禁止上传PHP和HTML文件
        if (in_array($fileInfo['type'], ['text/x-php', 'text/html']) || in_array($suffix, ['php', 'html', 'htm'])) {
        	$this->setError('Uploaded file format is limited');
            return false;
        }

        // 验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            $this->setError('Uploaded file format is limited');
        	return false;
        }

		// 验证是否为图片文件
        $imagewidth = $imageheight = 0;
        if (in_array($fileInfo['type'], ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
            $imgInfo = getimagesize($fileInfo['tmp_name']);
            if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                $this->setError('Uploaded file is not a valid image');
                return false;
            }
            $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
            $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
        }

        // 文件名及存储位置
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);
        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);

        $info = $file->validate(['size' => $size])->move(Env::get('root_path') . '/public' . $uploadDir, $fileName);

        // 上传成功否
        if($info) { 
        	$params = [
        		'user_id'      => '',
        		'filesize'     => $fileInfo['size'],
                'image_width'  => $imagewidth,
                'image_height' => $imageheight,
                'image_type'   => $suffix,
                'image_frames' => 0,
                'mimetype'     => $fileInfo['type'],
                'url'          => $uploadDir . $info->getSaveName(),
                'upload_time'  => time(),
                'storage'      => 'local',
                'sha1'         => $sha1
        	];
        	\think\facade\Hook::listen("upload_after", $params);
        	return $params;
        } else {
        	$this->setError($file->getError());
        	return false;
        }
	}

	/**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? __($this->_error) : '';
    }
}