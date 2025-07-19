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

/**
 * Session操作类
 *
 * 提供完整的Session管理功能，包括：
 * - Session的启动、关闭和销毁
 * - Session数据的设置、获取和删除
 * - Session ID的获取和重新生成
 * - 支持自定义过期时间
 * - 使用单例模式确保全局唯一实例
 *
 * @package nova\plugin\cookie
 * @author ankio
 * @since 2020/11/29
 */
class Session
{
    /**
     * Session实例（单例模式）
     *
     * @var Session|null
     */
    private static ?Session $instance = null;

    /**
     * Session配置对象
     *
     * @var SessionConfig
     */
    private SessionConfig $config;

    /**
     * Session是否已启动的标志
     *
     * @var bool
     */
    private bool $session_start = false;

    /**
     * 私有构造函数，防止外部直接实例化
     * 初始化Session配置
     */
    public function __construct()
    {
        $this->config = new SessionConfig();
    }

    /**
     * 获取Session单例实例
     *
     * 使用单例模式确保整个应用中只有一个Session实例
     *
     * @return Session Session实例
     */
    public static function getInstance(): Session
    {
        if (is_null(self::$instance)) {
            self::$instance = new Session();
        }

        return self::$instance;
    }

    /**
     * 检查Session是否已启动
     *
     * @return bool 如果Session已启动返回true，否则返回false
     */
    public function isStarted(): bool
    {
        return $this->session_start;
    }

    /**
     * 启动Session
     *
     * 执行以下操作：
     * 1. 检查Session是否已经启动，如果已启动则直接返回
     * 2. 生成唯一的Session名称（基于ROOT_PATH的MD5值）
     * 3. 设置Session Cookie参数（生命周期、路径、安全设置等）
     * 4. 设置自定义的Session处理器
     * 5. 启动Session并设置启动标志
     *
     * @return void
     */
    public function start(): void
    {
        // 如果Session已经启动，直接返回
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->session_start = true;
            return;
        }

        // 生成唯一的Session名称，基于ROOT_PATH的MD5值
        $sessionName = (substr(md5(ROOT_PATH), 8, 8))."_".($this->config->session_name);

        // 设置会话名称
        session_name($sessionName);

        // 获取缓存时间配置
        $cacheTime = $this->config->time;

        // 设置Session Cookie参数
        session_set_cookie_params([
            'lifetime' => $cacheTime, // 会话Cookie的生命周期
            'path' => '/', // Cookie可在整个域名下使用
            'secure' => false, // 是否仅通过HTTPS发送（开发环境设为false）
            'httponly' => true, // 防止通过JavaScript访问Cookie
        ]);

        // 使用自定义的Session处理器，支持缓存存储
        session_set_save_handler(new SessionHandler($cacheTime), true);

        // 启动会话
        session_start();
        $this->session_start = true;
    }

    /**
     * 检查并启动Session
     *
     * 如果Session未启动，则自动启动Session
     * 这是一个内部方法，用于确保在操作Session数据前Session已经启动
     *
     * @return void
     */
    private function checkAndStart(): void
    {
        if (!$this->isStarted()) {
            $this->start();
        }
    }

    /**
     * 获取当前Session ID
     *
     * @return string 当前Session的ID
     */
    public function id(): string
    {
        $this->checkAndStart();
        return session_id();
    }

    /**
     * 重新生成Session ID
     *
     * 建议在用户登录等提权的情况下使用，确保会话安全。
     * 重新生成ID可以防止会话固定攻击。
     *
     * @return void
     */
    public function regenerateId(): void
    {
        $this->checkAndStart();
        session_regenerate_id();
    }

    /**
     * 设置Session数据
     *
     * 将数据序列化后存储到Session中，支持设置过期时间。
     * 如果指定了过期时间，会在Session中额外存储一个过期时间戳。
     *
     * @param  string $name   Session数据的键名
     * @param  mixed  $value  Session数据的值（会被序列化存储）
     * @param  int    $expire 过期时间，单位秒。0表示使用Session默认过期时间
     * @return void
     */
    public function set(string $name, mixed $value, int $expire = 0): void
    {
        $this->checkAndStart();

        // 如果指定了过期时间，存储过期时间戳
        if ($expire != 0) {
            $expire = time() + $expire;
            $_SESSION[$name . "_expire"] = $expire;
        }

        // 将数据序列化后存储
        $_SESSION[$name] = serialize($value);
    }

    /**
     * 获取Session数据
     *
     * 从Session中获取数据并反序列化。如果数据不存在或已过期，返回默认值。
     * 支持自动过期检查，过期数据会被自动清理。
     *
     * @param  string     $name    要获取的Session数据键名
     * @param  mixed|null $default 当数据不存在或已过期时的默认值
     * @return mixed      返回反序列化后的数据，如果不存在或已过期则返回默认值
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $this->checkAndStart();

        // 检查数据是否存在
        if (!isset($_SESSION[$name])) {
            return $default;
        }

        $value = $_SESSION[$name];

        // 如果没有设置过期时间，直接返回反序列化的数据
        if (!isset($_SESSION[$name . "_expire"])) {
            return unserialize($value);
        }

        // 检查是否过期
        $expire = $_SESSION[$name . "_expire"];
        if ($expire == 0 || $expire > time()) {
            // 未过期，返回反序列化的数据
            return unserialize($value);
        } else {
            // 已过期，清理数据
            unset($_SESSION[$name]);
            unset($_SESSION[$name . "_expire"]);
        }

        return null;
    }

    /**
     * 删除Session数据
     *
     * 删除指定的Session数据及其过期时间设置。
     *
     * @param  string $name 要删除的Session数据键名
     * @return void
     */
    public function delete(string $name): void
    {
        $this->checkAndStart();

        // 删除数据
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }

        // 删除对应的过期时间设置
        if (isset($_SESSION[$name . "_expire"])) {
            unset($_SESSION[$name . "_expire"]);
        }
    }

    /**
     * 结束SESSION的写操作，但不删除会话
     *
     * 调用session_write_close()来保存Session数据并释放写锁，
     * 但不会销毁Session。适用于长时间运行的脚本中临时释放Session锁。
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->isStarted()) {
            $this->session_start = false;
            session_write_close();
        }
    }

    /**
     * 销毁会话
     *
     * 完全销毁当前Session，包括：
     * - 清空所有Session数据
     * - 销毁Session
     * - 重置启动标志
     *
     * @return void
     */
    public function destroy(): void
    {
        if ($this->isStarted()) {
            $this->session_start = false;
            session_unset(); // 清空所有Session变量
            session_destroy(); // 销毁Session
        }
    }

    /**
     * 析构函数
     *
     * 在对象销毁时自动调用，确保Session数据被正确保存。
     * 如果Session已启动，会调用session_write_close()保存数据。
     *
     * @return void
     */
    public function __destruct()
    {
        if ($this->isStarted()) {
            $this->session_start = false;
            session_write_close();
        }
    }
}
