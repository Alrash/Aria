<?php
/**
 * Author: Alrash
 * Date: 2017/02/15 13:02
 * Description: 记住登录状态
 * 使用cookie和aes-256-*算法
 * 注意：
 * usePassword***是对用户id和原始密码的对称加密，容易被破解
 */

namespace Aria\helpers;

use Aria\Aria;
use Aria\base\Component;
use Aria\base\ParamException;
use Aria\security\Security;

/**
 * 提供记住/保持登录N天
 * Class KeepSignIn
 * @package Aria\helpers
 */
class KeepSignIn extends Component{
    /**
     * 与userPasswordDecrypt对应使用
     * 在cookie中保存uid和password（原明码）实现
     * 本方法是提供uid与password对称加密，与键值md5检验码码
     *
     * 设置内容
     * auto_login        1
     * login_uid         aes-256-cbc对称（base64再编码）
     * login_uid_chkMD5  cookie中login_uid对应值，加盐做md5（为什么和自己做的不一样，Security::md5仅取中间值做md5）
     * login_data        密码，aes-256-gcm对称（base64再编码），设置文件config.php提供iv, aad
     * login_data_chkMd5 cookie中login_data对应值，加盐做md5
     * login_tag         aes-256-gcm算法中的tag信息，aes-256-cfb对称（base64再编码）
     * login_tag_chkMd5  cookie中login_tag对应值，加盐做md5
     *
     * @param array $info       需要信息，必须使用['uid' => int, 'password' => string]的形式
     * @param int $time         存储时间
     * @param string $path      页面路径
     * @throws ParamException   参数$info检测错误，抛出
     */
    public static function userPasswordEncrypt(array $info, int $time = self::week, string $path = '/') {
        //检测$info参数形式
        if (array_diff(['uid', 'password'], array_keys($info)) !== []){
            throw new ParamException(__METHOD__ . ' parameter $info needs key uid and password!');
        }
        $security = new Security();
        $cookie = Aria::$app->cookie;

        //对称加密信息
        $security->setKeyWithSalt($cookie->config['auth_key'], $cookie->config['auth_key_salt']);
        $security->setIV($cookie->config['auth_iv']);
        $security->setAad($cookie->config['additional_auth_data']);
        $uid = $security->cipherEncrypt($info['uid'], 'aes-256-cfb');
        $password = $security->aes256gcm($info['password']);
        $tag = $security->cipherEncrypt($security->getTag(), 'aes-256-cbc');

        //设置cookie信息
        $cookie->set('auto_login', 1, $time, $path);
        $cookie->set('login_uid', base64_encode($uid), $time, $path);
        $cookie->set('login_uid_chkMD5', Security::md5WithSalt(base64_encode($uid), $cookie->config['login_salt']), $time, $path);
        $cookie->set('login_data', base64_encode($password), $time, $path);
        $cookie->set('login_data_chkMD5', Security::md5WithSalt(base64_encode($password), $cookie->config['login_salt']), $time, $path);
        $cookie->set('login_tag', base64_encode($tag), $time, $path);
        $cookie->set('login_tag_chkMD5', Security::md5WithSalt(base64_encode($tag), $cookie->config['login_salt']), $time, $path);
    }

    /**
     * 提供使用userPasswordEncrypt方法的解码方式，获取uid与password
     *
     * @return array 解码正确   ['uid' => int, 'password' => string]
     * @return bool  解码错误   false   （cookie中缺失某些值，或值被改变，与校验值对不上）
     */
    public static function userPasswordDecrypt(){
        //需要的键值映射，额。。写反了，不要在意
        $keyMap = [
            'uid' => 'login_uid',
            'uid_chk' => 'login_uid_chkMD5',
            'password' => 'login_data',
            'password_chk' => 'login_data_chkMD5',
            'tag' => 'login_tag',
            'tag_chk' => 'login_tag_chkMD5',
        ];
        $cookie  = Aria::$app->cookie;
        foreach ($keyMap as $key => $cookie_key) {
            $cookie_value = $cookie->getByName($cookie_key);
            if (isset($cookie_value)) {
                $value[$key] = $cookie_value;
            }else {
                return false;
            }
        }

        //检测设置值是否未变动
        if (Security::md5WithSalt($value['uid'], $cookie->config['login_salt']) === $value['uid_chk']
            && Security::md5WithSalt($value['password'], $cookie->config['login_salt']) === $value['password_chk']
            && Security::md5WithSalt($value['tag'], $cookie->config['login_salt']) === $value['tag_chk']) {
            //进行解密
            $security = new Security();
            $security->setKeyWithSalt($cookie->config['auth_key'], $cookie->config['auth_key_salt']);
            $security->setIV($cookie->config['auth_iv']);
            $security->setAad($cookie->config['additional_auth_data']);
            $tag = $security->decrypt(base64_decode($value['tag']), 'aes-256-cbc');
            $security->setTag($tag);
            $uid = $security->decrypt(base64_decode($value['uid']), 'aes-256-cfb');
            $password = $security->decrypt(base64_decode($value['password']), 'aes-256-gcm');

            return ['uid' => $uid, 'password' => $password];
        }
        return false;
    }
}