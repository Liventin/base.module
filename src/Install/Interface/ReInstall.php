<?php

namespace Base\Module\Install\Interface;

interface ReInstall
{
    public function getReInstallSort(): int;

    public function reInstall(): void;
}