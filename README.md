install | update
```
{
  "name": "provider/modul.name",
  "description": "bitrix module",
  "require": {
    "liventin/base.module": "dev-main"
  }
  ,
  "require-dev": {
    "roave/security-advisories": "dev-latest"
  },
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:liventin/base.module"
    }
  ],
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