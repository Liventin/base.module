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
