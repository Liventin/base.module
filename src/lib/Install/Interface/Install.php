<?php

namespace Base\Module\Install\Interface;

interface Install
{
    public function getInstallSort(): int;

    public function install(): void;
}