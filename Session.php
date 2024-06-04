<?php
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
        session_set_save_handler(new SessionHandler($this->cache), true);
        session_set_cookie_params($cacheTime, '/',null,true,true);
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

    public function destroy(): void
    {
        session_destroy();
    }
    function __destruct()
    {
        session_write_close();
    }
}