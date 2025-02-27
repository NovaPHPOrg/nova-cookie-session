<?php
/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace nova\plugin\cookie;
use nova\framework\cache\Cache;

/**
 * Class Session
 * @package cleanphp\web
 * Date: 2020/11/29 12:24 上午
 * Author: ankio
 * Description:Session操作类
 */
class Session
{
    private static ?Session $instance = null;
    private static bool $isStart = false;

    private  Cache $cache;
    /**
     * 获取实例
     * @return Session
     */
    public static function getInstance(): Session
    {
        if (is_null(self::$instance)) {
            self::$instance = new Session();

        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->cache = new Cache();
    }

    /**
     * 启动session
     * @param int $cacheTime Session缓存时间，默认会话有效
     * @param string $sessionName
     * @return void
     */
    public function start(int $cacheTime = 0, string $sessionName = 'NovaSession'): void
    {
        if (self::$isStart) {
            return; // 如果已经启动会话，直接返回
        }

        // 设置会话名称
        ini_set("session.name", $sessionName);

        if ($cacheTime !== 0) {
            // 设置会话的最大生存时间和Cookie参数
            ini_set('session.gc_maxlifetime', $cacheTime);
        }
        session_set_save_handler(new SessionHandler($this->cache, $cacheTime), true);
        session_set_cookie_params([
            'lifetime' => $cacheTime, // 会话Cookie将在浏览器关闭时过期
            'path' => '/', // 可在整个域名下使用
            'secure' => false, // 仅通过HTTPS发送
            'httponly' => true, // 不能通过JavaScript访问
            'samesite' => 'Strict', // 防止CSRF攻击
        ]);
        // 启动会话
        session_start();
        self::$isStart = true;
    }


    /**
     * 获取sessionId
     * @return string
     */
    public function id(): string
    {
        return session_id();
    }
    /**
     * 重新生成sessionId,建议在用户登陆等提权的情况下使用，确保会话安全。
     * @return void
     */
    public function regenerateId(): void
    {
        session_regenerate_id();
    }


    /**
     * 设置session
     * @param string $name session名称
     * @param mixed $value
     * @param int $expire 过期时间,单位秒
     */
    public function set(string $name, mixed $value, int $expire = 0): void
    {

        if ($expire != 0) {
            $expire = time() + $expire;
            $_SESSION[$name . "_expire"] = $expire;

        }
        $_SESSION[$name] = serialize($value);

    }


    /**
     * 获取session
     * @param string $name 要获取的session名
     * @param mixed|null $default 默认值
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if (!isset($_SESSION[$name])) {
            return $default;
        }
        $value = $_SESSION[$name];
        if (!isset($_SESSION[$name . "_expire"])) {
            return unserialize($value);
        }
        $expire = $_SESSION[$name . "_expire"];
        if ($expire == 0 || $expire > time()) {
            return unserialize($value);
        } else {
            //超时后销毁变量
            unset($_SESSION[$name]);
            unset($_SESSION[$name . "_expire"]);
        }
        return null;
    }


    /**
     * 删除session
     * @param string $name 要删除的session名称
     */
    public function delete(string $name): void
    {
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
        if (isset($_SESSION[$name . "_expire"])) {
            unset($_SESSION[$name . "_expire"]);
        }
    }

    /**
     * 结束SESSION的写操作，但是不删除会话
     * @return void
     */
    public function close(): void
    {
        session_write_close();
    }

    /**
     * 销毁会话
     * @return void
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }
    function __destruct()
    {
        session_write_close();
    }
}