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

use nova\framework\cache\Cache;
use nova\framework\core\Context;
use SessionHandlerInterface;

/**
 * 自定义会话处理器
 *
 * 实现PHP的SessionHandlerInterface接口，将会话数据存储到缓存系统中
 * 支持会话数据的自动过期和自动续期功能
 *
 * @package nova\plugin\cookie
 */
class SessionHandler implements SessionHandlerInterface
{
    /** @var Cache 缓存实例，用于存储会话数据 */
    private Cache $cache;

    /** @var int 会话最大生存时间（秒），默认30天 */
    private int $maxLifetime;

    /**
     * 构造函数
     *
     * @param int|null $cacheTime 会话缓存时间（秒），如果为null则使用默认30天
     */
    public function __construct($cacheTime)
    {
        $this->cache = Context::instance()->cache;
        $this->maxLifetime = $cacheTime ?? 2592000; // 30天 = 2592000秒
    }

    /**
     * 关闭会话
     *
     * 在会话结束时调用，这里直接返回true表示成功关闭
     *
     * @return bool 始终返回true
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * 垃圾回收
     *
     * 清理过期的会话数据，调用缓存系统的垃圾回收机制
     *
     * @param  int       $max_lifetime 最大生存时间（秒）
     * @return int|false 清理的会话数量，这里返回0
     */
    public function gc(int $max_lifetime): int|false
    {
        $this->cache->gc("session/");
        return 0;
    }

    /**
     * 打开会话
     *
     * 在会话开始时调用，这里直接返回true表示成功打开
     *
     * @param  string $path 会话保存路径（在此实现中未使用）
     * @param  string $name 会话名称（在此实现中未使用）
     * @return bool   始终返回true
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * 读取会话数据
     *
     * 从缓存中读取指定会话ID的数据，如果数据即将过期会自动续期
     *
     * @param  string       $id 会话ID
     * @return string|false 会话数据，如果不存在则返回空字符串
     */
    public function read(string $id): string|false
    {
        $result = $this->cache->get("session/$id");
        if ($result === null) {
            return '';
        }

        // 如果会话即将过期（剩余时间小于7天），自动延长有效期
        $remainingTime = $this->cache->getTtl("session/$id");
        if ($remainingTime > 0 && $remainingTime < 86400 * 7) { // 如果剩余时间小于7天
            $this->cache->set("session/$id", $result, $this->maxLifetime);
        }

        return $result;
    }

    /**
     * 写入会话数据
     *
     * 将会话数据写入缓存系统，使用配置的最大生存时间
     *
     * @param  string $id   会话ID
     * @param  string $data 会话数据
     * @return bool   始终返回true表示写入成功
     */
    public function write(string $id, string $data): bool
    {
        $lifetime = $this->maxLifetime;
        $this->cache->set("session/$id", $data, $lifetime);
        return true;
    }

    /**
     * 销毁会话
     *
     * 从缓存中删除指定会话ID的数据
     *
     * @param  string $id 会话ID
     * @return bool   始终返回true表示销毁成功
     */
    public function destroy(string $id): bool
    {
        $this->cache->delete("session/$id");
        return true;
    }
}
