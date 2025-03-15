<?php

declare(strict_types=1);

namespace nova\plugin\cookie;

use Workerman\Protocols\Http\Session\SessionHandlerInterface as WorkermanSessionHandlerInterface;

class WorkermanSessionHandler implements WorkermanSessionHandlerInterface
{
    private SessionHandler $handler;

    public function __construct()
    {
        $this->handler = new SessionHandler();
    }

    public function close(): bool
    {
        return $this->handler->close();
    }

    public function destroy(string $sessionId): bool
    {
        return $this->handler->destroy($sessionId);
    }

    public function gc(int $maxLifetime): bool
    {
        return $this->handler->gc($maxLifetime);
    }

    public function open(string $savePath, string $name): bool
    {
        return $this->handler->open($savePath, $name);
    }

    public function read(string $sessionId): string|false
    {
        return $this->handler->read($sessionId);
    }

    public function write(string $sessionId, string $sessionData): bool
    {
        return $this->handler->write($sessionId, $sessionData);
    }

    public function updateTimestamp(string $sessionId, string $data = ""): bool
    {
        // 实现更新时间戳的逻辑
        $result = $this->handler->read($sessionId);
        if ($result !== false) {
            return $this->handler->write($sessionId, $result);
        }
        return false;
    }
}
