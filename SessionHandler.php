<?php
namespace nova\plugin\cookie;
use nova\framework\cache\Cache;
use SessionHandlerInterface;

class SessionHandler implements SessionHandlerInterface
{
    private Cache $cache;
    private int $maxLifetime;

    public function __construct(&$cache, int $maxLifetime = 2592000)
    {
        $this->cache = $cache;
        if ($maxLifetime == 0) {
            $maxLifetime = 2592000;
        }
        $this->maxLifetime = $maxLifetime; // 默认会话有效期为30天
    }

    public function close(): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return true;
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
        if ($remainingTime < 86400 * 7) { // 如果剩余时间小于1天
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
    function __destruct()
    {
        session_write_close();
    }
}
