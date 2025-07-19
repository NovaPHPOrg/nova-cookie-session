<?php

declare(strict_types=1);

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace nova\plugin\cookie;

use nova\framework\core\Text;

/**
 * Cookie操作类
 *
 * 提供Cookie的设置、获取、删除和续期功能
 * 使用单例模式确保全局只有一个Cookie实例
 *
 * @package nova\plugin\cookie
 * @author ankio
 * @since 2020/11/19
 *
 * @example
 * // 获取Cookie实例
 * $cookie = Cookie::getInstance();
 *
 * // 设置Cookie
 * $cookie->set('user_id', 123);
 * $cookie->set('user_info', ['name' => 'John', 'age' => 25]);
 *
 * // 获取Cookie
 * $userId = $cookie->get('user_id', 0);
 * $userInfo = $cookie->get('user_info', []);
 *
 * // 删除Cookie
 * $cookie->delete('user_id');
 *
 * // Cookie续期
 * $cookie->addTime(10); // 续期10分钟
 */
class Cookie
{
    /**
     * Cookie实例（单例模式）
     *
     * @var Cookie|null
     */
    private static ?Cookie $instance = null;

    /**
     * Cookie过期时间（秒）
     * 0表示会话Cookie，浏览器关闭后失效
     * 正数表示从当前时间开始的秒数
     *
     * @var int
     */
    private int $expire = 0;

    /**
     * Cookie路径
     * 默认为'/'，表示在整个域名下有效
     * 可以设置为特定路径，如'/admin'，只在admin目录下有效
     *
     * @var string
     */
    private string $path = '/';

    /**
     * Cookie域名
     * 空字符串表示当前域名
     * 可以设置为特定域名，如'.example.com'
     *
     * @var string
     */
    private string $domain = '';

    /**
     * 是否只在HTTPS协议下设置Cookie
     * true表示只在HTTPS连接时设置Cookie
     * false表示在HTTP和HTTPS连接时都可以设置Cookie
     *
     * @var bool
     */
    private bool $secure = false;

    /**
     * 是否只允许HTTP协议访问Cookie
     * true表示JavaScript无法访问Cookie，提高安全性
     * false表示JavaScript可以访问Cookie
     *
     * @var bool
     */
    private bool $httponly = true;

    /**
     * 获取Cookie实例（单例模式）
     *
     * 如果实例不存在则创建新实例，如果已存在则返回现有实例
     * 可以通过参数配置Cookie的基本属性
     *
     * @param  int    $expire   过期时间（秒），0表示会话Cookie
     * @param  string $path     Cookie路径，默认为空字符串（使用默认路径'/'）
     * @param  string $domain   Cookie域名，默认为空字符串（使用当前域名）
     * @param  bool   $secure   是否只在HTTPS下设置，默认为false
     * @param  bool   $httponly 是否只允许HTTP访问，默认为true
     * @return Cookie Cookie实例
     *
     * @example
     * // 获取默认配置的Cookie实例
     * $cookie = Cookie::getInstance();
     *
     * // 获取自定义配置的Cookie实例
     * $cookie = Cookie::getInstance(3600, '/admin', '.example.com', true, true);
     */
    public static function getInstance(int $expire = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = true): Cookie
    {
        if (is_null(self::$instance)) {
            self::$instance = new Cookie();
        }
        return self::$instance->setOptions($expire, $path, $domain, $secure, $httponly);
    }

    /**
     * 设置Cookie选项
     *
     * 私有方法，用于配置Cookie的基本属性
     *
     * @param  int    $expire   过期时间（秒）
     * @param  string $path     Cookie路径
     * @param  string $domain   Cookie域名
     * @param  bool   $secure   是否只在HTTPS下设置
     * @param  bool   $httponly 是否只允许HTTP访问
     * @return Cookie 当前实例，支持链式调用
     */
    private function setOptions(int $expire = 0, string $path = "", string $domain = "", bool $secure = false, bool $httponly = true): Cookie
    {
        $this->expire = $expire;
        $this->path = $path;
        $this->domain = $domain;
        $this->secure = $secure;
        $this->httponly = $httponly;
        return $this;
    }

    /**
     * 设置Cookie
     *
     * 将数据存储到Cookie中，支持字符串、数字、数组和对象
     * 数组和对象会自动转换为JSON字符串存储
     *
     * @param  string $name  Cookie名称
     * @param  mixed  $value Cookie值，支持字符串、数字、数组、对象
     * @return void
     *
     * @example
     * $cookie = Cookie::getInstance();
     *
     * // 设置简单值
     * $cookie->set('user_id', 123);
     * $cookie->set('username', 'john_doe');
     *
     * // 设置数组
     * $cookie->set('user_info', ['name' => 'John', 'age' => 25]);
     *
     * // 设置对象（会被转换为JSON）
     * $user = new stdClass();
     * $user->name = 'John';
     * $cookie->set('user_object', $user);
     */
    public function set(string $name, $value): void
    {
        // 如果值是数组或对象，转换为JSON字符串
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        // 设置Cookie
        setcookie($name, $value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
    }

    /**
     * 获取Cookie值
     *
     * 从Cookie中获取指定名称的值，支持类型转换
     * 如果Cookie不存在，返回默认值
     *
     * @param  string     $name    Cookie名称
     * @param  mixed|null $default 默认值，当Cookie不存在时返回此值
     * @return mixed      Cookie值或默认值
     *
     * @example
     * $cookie = Cookie::getInstance();
     *
     * // 获取简单值
     * $userId = $cookie->get('user_id', 0);
     * $username = $cookie->get('username', '');
     *
     * // 获取数组（需要手动解析JSON）
     * $userInfoJson = $cookie->get('user_info', '{}');
     * $userInfo = json_decode($userInfoJson, true);
     *
     * // 使用默认值
     * $theme = $cookie->get('theme', 'light');
     */
    public function get(string $name, mixed $default = null): mixed
    {
        // 检查Cookie是否存在
        if (!isset($_COOKIE[$name])) {
            return $default;
        }

        // 使用Text::parseType进行类型转换
        return Text::parseType($default, $_COOKIE[$name]);
    }

    /**
     * 删除Cookie
     *
     * 通过设置过期时间为过去的时间来删除Cookie
     * 如果Cookie不存在，则不执行任何操作
     *
     * @param  string $name Cookie名称
     * @return void
     *
     * @example
     * $cookie = Cookie::getInstance();
     *
     * // 删除单个Cookie
     * $cookie->delete('user_id');
     *
     * // 删除多个Cookie
     * $cookie->delete('user_id');
     * $cookie->delete('username');
     * $cookie->delete('user_info');
     */
    public function delete(string $name): void
    {
        // 检查Cookie是否存在
        if (!isset($_COOKIE[$name])) {
            return;
        }

        // 获取Cookie值（用于设置删除时的值）
        $value = $_COOKIE[$name];

        // 通过设置过期时间为过去的时间来删除Cookie
        setcookie(
            $name,
            '',           // 空值
            time() - 1,   // 过期时间为1秒前
            $this->path,
            $this->domain,
            $this->secure,
            $this->httponly
        );

        // 清理变量
        unset($value);
    }

    /**
     * Cookie续期
     *
     * 为所有现有的Cookie延长过期时间
     * 常用于保持用户登录状态
     *
     * @param  int  $time 续期时间（分钟），默认为5分钟
     * @return void
     *
     * @example
     * $cookie = Cookie::getInstance();
     *
     * // 续期5分钟（默认）
     * $cookie->addTime();
     *
     * // 续期30分钟
     * $cookie->addTime(30);
     *
     * // 续期1小时
     * $cookie->addTime(60);
     */
    public function addTime(int $time = 5): void
    {
        // 遍历所有Cookie
        foreach ($_COOKIE as $name => $value) {
            // 重新设置Cookie，延长过期时间
            setcookie(
                $name,
                $value,
                time() + $time * 60,  // 当前时间 + 续期时间（转换为秒）
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly
            );
        }
    }
}
