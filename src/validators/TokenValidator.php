<?php

/**
 * A lightweight and powerful Restful interface framework based on Yii2
 * PHP version 5.6 or newer
 * 
 * @category Restful_Api_Validator
 * @package  Qinqw\Yii\Rest\validators
 * @author   Kevin <qinqiwei@hotmail.com>
 * @license  Apache 2.0
 * @link     https://github.com/qinqw/yii-rest
 */

namespace Qinqw\Yii\Rest\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use yii\web\HttpException;
use yii\web\Request;
use Qinqw\Yii\Rest\security\Authorization;

/**
 * Token Validator
 * 
 * @category Restful_Api_Validator
 * @package  Qinqw\Yii\Rest\validators
 * @author   Kevin <qinqiwei@hotmail.com>
 * @date     2017-06-06
 * @license  Apache 2.0
 * @link     https://github.com/qinqw/yii-rest
 */
class TokenValidator
{
    private $_is_valid_token = false;
    public static $globalParams = null;
    public $attributes = [
        'token',
        'app-key',
        'timestamp'
    ];

    /**
     * Validate
     *
     * @return mixed 
     */
    public function validate()
    {
        //return true;
        // 此处实现请求的合法性校验，true代表请求合法
        // 校验参数
        if ($this->_is_valid_token == false) {
            return true;
        }

        $auth = new Authorization();
        $token = self::$globalParams['token'];
        $app_key = self::$globalParams['app-key'];
        if ($auth->validateToken($token)) {
            return true;
        } else {
            throw new HttpException(401, "Token is expired", 401);
        }
    }

    /**
     * Load
     * 
     * @return mixed 
     */
    public function load()
    {
        // 只有在Yii参数定义中显式的声明 enable_sign 为 true，才不启用签名验证。
        if (isset(Yii::$app->params['enable_token'])) {
            if (Yii::$app->params['enable_token'] == true) {
                $this->_is_valid_token = true;
            }
        }

        if ($this->_is_valid_token == true) {
            /**
             * 优先检查存储在cookie中的参数；
             * 如果cookie中的信息符合要求，则忽略header中的参数；如果cookie中的参数不符合要求，进一步检查header中的参数
             */
            $is_missed_cookie = false;
            $cookieParams = $_COOKIE;
            foreach ($this->attributes as $attribute) {
                if (!array_key_exists($attribute, $cookieParams)) {
                    $is_missed_cookie = true;
                    break;
                } else {
                    self::$globalParams[$attribute] = $cookieParams[$attribute];
                }
            }

            if ($is_missed_cookie == true) {
                $headParams = Yii::$app->request->getHeaders()->toArray();
                foreach ($this->attributes as $attribute) {
                    if (!isset($headParams[$attribute]) || (!is_array($headParams[$attribute])) || empty($headParams[$attribute])) {
                        throw new HttpException(400, "Missing " . $attribute . " in the header", 400);
                    } else {
                        self::$globalParams[$attribute] = array_shift($headParams[$attribute]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * GetParams
     *
     * @return mixed 
     */
    public function getParams()
    {
        return self::$globalParams;
    }
}
