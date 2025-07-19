<?php

declare(strict_types=1);

namespace nova\plugin\cookie;

use nova\framework\core\ConfigObject;

/**
 * 会话配置类
 *
 * 继承自 ConfigObject，用于管理会话相关的配置参数
 * 包括会话超时时间和会话名称等配置项
 *
 * @package nova\plugin\cookie
 * @since 1.0.0
 */
class SessionConfig extends ConfigObject
{
    /**
     * 会话超时时间（秒）
     *
     * 设置为 0 表示会话永不过期
     * 大于 0 的值表示会话在指定秒数后过期
     *
     * @var int
     * @default 0
     */
    public int $time = 0;

    /**
     * 会话名称
     *
     * 用于在 Cookie 中标识会话的唯一名称
     * 建议使用有意义的名称以便于调试和管理
     *
     * @var string
     * @default "NovaSession"
     */
    public string $session_name = "NovaSession";
}
