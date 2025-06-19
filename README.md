install | update
```
{
  "name": "provider/modul.name",
  "description": "bitrix module",
  "require": {
    "liventin/base.module": "^1.0.0"
  },
  "require-dev": {
    "roave/security-advisories": "dev-latest"
  },
  "scripts": {
    "post-update-cmd": [
      "/usr/bin/php vendor/liventin/base.module/scripts/post-install.php"
    ],
    "post-install-cmd": "@post-update-cmd"
  }
}
```
redirect (optional)
```
"extra": {
  "service-redirect": {
    "liventin/base.module": "module.name",
  }
},
```

<table>
<tr>
<th>additional packages</th>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.handlers">Bitrix Handlers Service For Base Events</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.handlers.smartprocess.orm">Bitrix Handlers Service For SmartProcess Orm Events</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields">Migration User Fields</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.crmstatus">Migration Crm Status</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.smart.process">Migration Smart Process</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.smartprocess">SmartProcess</a>
</td>
</tr>
</table>

<table>
<tr>
<th>Module Options Providers</th>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.options.provider.note">Note</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.options.provider.separator">Separator</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.options.provider.text">Text</a>
</td>
</tr>
</table>

PhpStorm Option Tab Live template
```php
<?php

namespace ${MODULE_PROVIDER_CAMMAL_CASE}\\${MODULE_CODE_CAMMAL_CASE}\Options;

use ${MODULE_PROVIDER_CAMMAL_CASE}\\${MODULE_CODE_CAMMAL_CASE}\Service\Options\Tab;

class TabMain implements Tab
{

    public static function getId(): string
    {
        return 'main';
    }

    public static function getName(): string
    {
        return 'Основные';
    }

    public static function getSort(): int
    {
        return 100;
    }
}
```
