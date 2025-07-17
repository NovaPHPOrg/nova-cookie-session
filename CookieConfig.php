<?php

declare(strict_types=1);

namespace nova\plugin\cookie;

use nova\framework\core\ConfigObject;

class CookieConfig extends ConfigObject
{
    public int  $expire = 0;

    public string $session_name = "NovaSession";
}
