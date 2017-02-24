<?php
/**
 * Author: Alrash
 * Date: 2017/02/12 20:35
 * Description: 加密、解密类
 *
 * 由于使用aes-256-gcm/ccm，则需要
 *  (1) php7.1以上版本
 *  (2) openssl支持gcm/ccm算法（使用openssl_get_cipher_methods()查看）
 */

namespace Aria\security;

use Aria\base\Object;
use Aria\base\SecurityException;

/**
 * Class Security
 * @package Aria\security
 */
class Security extends Object{
    const raw_data = OPENSSL_RAW_DATA;
    const zero_padding = OPENSSL_ZERO_PADDING;

    private $key = null;
    private $iv = null;
    private $tag = null;
    private $aad = '';
    private $tag_length = 16;

    /**
     * aes-256-gcm对称加密
     * 方法内调用cipherEncrypt方法
     *
     * 注：
     *  需要php7.1以上
     *
     * @param string $data
     * @param int $options
     * @return string
     */
    public function aes256gcm($data, $options = self::zero_padding): string {
        $algo = 'aes-256-gcm';
        return $this->cipherEncrypt($data, $algo, $options);
    }

    /**
     * aes-256-ccm对称加密
     * 方法内调用cipherEncrypt方法
     *
     * 注：
     *  需要php7.1以上
     *
     * @param string $data
     * @param int $options
     * @return string
     */
    public function aes256ccm($data, $options = self::zero_padding): string {
        $algo = 'aes-256-ccm';
        return $this->cipherEncrypt($data, $algo, $options);
    }

    /**
     * 对称加密
     * 方法调用openssl_encrypt内置方法
     *
     * @param string $data          待操作字符串
     * @param string $algo          使用算法，必须是已有算法（openssl_get_cipher_methods()查看）
     * @param int $options          看懂与看不懂的区别
     * @return string
     * @throws SecurityException
     */
    public function cipherEncrypt($data, $algo, $options = 0): string {
        if (!isset($this->key)){
            $this->key = bin2hex(random_bytes(random_int(5, 32)));
        }

        $iv_length = openssl_cipher_iv_length($algo);
        if (!isset($this->iv)) {
            $iv = $this->iv = random_bytes($iv_length);
        }else {
            $iv = $this->adjustIV($algo);
        }

        $mode = explode('-', $algo);
        array_walk($mode,
            function($value, $key) {
                return strtolower($value);
            });

        if (array_intersect($mode, ['gcm', 'ccm']) === []) {
            $cipherText = openssl_encrypt(
                $data,
                $algo,
                $this->key,
                $options,
                $iv
            );
        }else {
            $cipherText = openssl_encrypt(
                $data,
                $algo,
                $this->key,
                $options,
                $iv,
                $this->tag,
                $this->aad,
                $this->tag_length
            );
        }

        if ($cipherText === false) {
            throw new SecurityException(sprintf("OpenSSL %s", openssl_error_string()));
        }

        return $cipherText;
    }

    /**
     * @param $cipherText
     * @param $algo
     * @param int $options
     * @return string
     * @throws SecurityException
     */
    public function decrypt($cipherText, $algo, $options = 0){
        $mode = explode('-', $algo);
        array_walk($mode,
            function($value, $key) {
                return strtolower($value);
            });
        $iv = $this->adjustIV($algo);

        if (array_intersect($mode, ['gcm', 'ccm']) === []) {
            $decrypt = openssl_decrypt(
                $cipherText,
                $algo,
                $this->key,
                $options,
                $iv
            );
        }else {
            $decrypt = openssl_decrypt(
                $cipherText,
                $algo,
                $this->key,
                $options,
                $iv,
                $this->tag,
                $this->aad
            );
        }

        if ($decrypt === false){
            throw new SecurityException(sprintf("OpenSSL %s", openssl_error_string()));
        }

        return $decrypt;
    }

    /**
     * @param $data
     * @param bool $raw_output
     * @return string
     */
    public static function md5(string $data, bool $raw_output = false): string {
        $len = strlen($data);
        return md5(substr($data, floor($len / 4), round($len / 2)), $raw_output);
    }

    /**
     * @param $data
     * @param $salt
     * @param bool $raw_output
     * @return string
     */
    public static function md5WithSalt(string $data, string $salt, bool $raw_output = false): string {
        return self::md5($data . $salt, $raw_output);
    }

    public static function sha1(string $data, bool $raw_output = false): string {
        $len = strlen($data);
        return sha1(substr($data, floor($len / 4), round($len / 2)), $raw_output);
    }

    public static function sha1WithSalt(string $data, string $salt, bool $raw_output = false): string {
        return self::sha1($data . $salt, $raw_output);
    }

    /**
     * @return string
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key) {
        $this->key = $key;
    }

    public function setHashKey(string $key) {
        $this->setKey(self::md5($key));
    }

    public function setKeyWithSalt(string $key, string $salt) {
        $this->setHashKey($key . $salt);
    }

    /**
     * @return null
     */
    public function getIV() {
        return bin2hex($this->iv);
    }

    /**
     * 调整iv的值，使其符合openssl_cipher_iv_length的长度规定
     * @param string $algo
     * @return string
     * */
    private function adjustIV(string $algo): string {
        $iv_length = openssl_cipher_iv_length($algo);
        $len = mb_strlen($this->iv, '8bit');
        $iv = $this->iv;
        if ($len !== $iv_length) {
            if ($len > $iv_length) {
                $iv = mb_substr($iv, 0, $iv_length);
            }else {
                $iv = str_pad($iv, $iv_length, $this->iv);
            }
        }
        return $iv;
    }

    /**
     * @param null $iv
     */
    public function setIV($iv) {
        $this->iv = $iv;
    }

    /**
     * @return null
     */
    public function getTag() {
        return bin2hex($this->tag);
    }

    /**
     * @param string $tag
     */
    public function setTag(string $tag) {
        $this->tag = hex2bin($tag);
    }

    /**
     * @return string
     */
    public function getAad(): string {
        return $this->aad;
    }

    /**
     * @param string $aad
     */
    public function setAad(string $aad) {
        $this->aad = $aad;
    }

    /**
     * @return int
     */
    public function getTagLength(): int {
        return $this->tag_length;
    }

    /**
     * @param int $tag_length
     */
    public function setTagLength(int $tag_length) {
        if ($tag_length > 16 || $tag_length < 4){
            $tag_length = 16;
        }
        $this->tag_length = $tag_length;
    }
}