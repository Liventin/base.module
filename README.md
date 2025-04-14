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
<a href="https://github.com/Liventin/base.module.handlers">Bitrix Handlers Service</a>
</td>
</tr>
<tr>
<td>
<a href="https://github.com/Liventin/base.module.migration.userfields">Migration User Fields</a>
</td>
</tr>
</table>