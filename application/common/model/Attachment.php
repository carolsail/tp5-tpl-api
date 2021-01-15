<?php

namespace app\api\model;

use think\Model;

class Attachment extends Model
{
    // 定义字段类型
    protected $type = [];

    public function setUploadTimeAttr($value)
    {
        return is_numeric($value) ? $value : strtotime($value);
    }

    protected static function init()
    {
        // 如果已经上传该资源，则不再记录
        self::beforeInsert(function ($model) {
            if (self::where('url', '=', $model['url'])->find()) {
                return false;
            }
        });
    }
}
