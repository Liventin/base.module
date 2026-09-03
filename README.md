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
remove (optional)
```
"extra": {
  "service-remove": [
    "liventin/base.module.handlers"
  ]
},
```
The package stays in `require` (composer will still download it), but `post-install.php` removes the files the package had previously deployed into the module (including `service_locator` files, when the package was in `service-redirect`).

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
<a href="https://github.com/Liventin/base.module.iblock">Iblocks</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.orm">Bitrix Orm</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.assets.timeline.images">CRM Timeline Images</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.hlblocks">Hl Blocks</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.local.apps">Local Apps</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.logger.service">PSR-3 Logger</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.smartprocess">SmartProcess</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.intranet.departments">Intranet Departments</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.rest.router">Bitrix Rest Router for event</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.result.modifier.injection">Result Modifier Injection</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.userfield.enumeration">User Fields Enumeration</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields">Migration User Fields</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.hlblock">Migration Hl Block</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.iblock">Migration Bitrix Iblock</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.crmstatus">Migration Crm Status</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.crmcategory">Migration Crm Category</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.smart.process">Migration Smart Process</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.smart.process.workspace">Migration Smart Process Workspace</a>
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
<a href="https://github.com/Liventin/base.module.options.provider.checkbox">Checkbox</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.options.provider.selectbox">SelectBox</a>
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

<table>
<tr>
<th>Migration User Fields Providers</th>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields.provider.string">String</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields.provider.datetime">DateTime</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields.provider.enumeration">Enumeration</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields.provider.hlblock">Hl Block</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields.provider.iblock.element">Iblock Element</a>
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
        return 'Main';
    }

    public static function getSort(): int
    {
        return 100;
    }
}
```
