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
use SessionHandlerInterface;

class SessionHandler implements SessionHandlerInterface
{
    private Cache $cache;
    private int $maxLifetime;

    public function __construct($cacheTime)
    {
        $this->cache = new Cache();
        $this->maxLifetime = $cacheTime ?? 2592000;
    }

    public function close(): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $this->cache->deleteKeyStartWith("session/");
        return 0;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $result = $this->cache->get("session/$id");
        if ($result === null) {
            return '';
        }

        // 如果会话即将过期,自动延长有效期
        $remainingTime = $this->cache->getTtl("session/$id");
        if ($remainingTime > 0 && $remainingTime < 86400 * 7) { // 如果剩余时间小于1天
            $this->cache->set("session/$id", $result, $this->maxLifetime);
        }

        return $result;
    }

    public function write(string $id, string $data): bool
    {
        $lifetime = $this->maxLifetime;
        $this->cache->set("session/$id", $data, $lifetime);
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->cache->delete("session/$id");
        return true;
    }
}
