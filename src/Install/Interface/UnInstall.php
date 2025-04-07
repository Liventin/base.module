<?php

namespace Base\Module\Install\Interface;

interface UnInstall
{
    public function getUnInstallSort(): int;

    public function unInstall(bool $saveData): void;
}