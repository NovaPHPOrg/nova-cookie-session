<?php

declare(strict_types=1);

namespace nova\plugin\cookie;

use nova\framework\core\ConfigObject;

class SessionConfig extends ConfigObject
{
    public int  $time = 0;

    public string $session_name = "NovaSession";
}
