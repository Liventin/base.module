<?php

/**
 * @var CMain $APPLICATION
 * @noinspection DuplicatedCode
 */

use Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid()) {
    return;
}

const LANG_PREFIX = 'BASE_MODULE_';
$moduleId = basename(dirname(__DIR__));

/** @var CAdminException $exception */
$exception = $APPLICATION->GetException();
if (!$exception) {
    $action = 'unknown';
    $hasErrors = false;
} else {
    $action = $exception->GetID();
    $hasErrors = !empty($exception->GetMessages());
}

$titleAction = '';
$titleErrors = '';
$hiddenInputs = '<input type="hidden" name="lang" value="' . LANGUAGE_ID . '">';

switch ($action) {
    case 'install':
        $titleAction = Loc::getMessage('MOD_INST_OK');
        $titleErrors = Loc::getMessage('MOD_INST_ERR');
        break;
    case 'unInstall':
        $titleAction = Loc::getMessage('MOD_UNINST_OK');
        $titleErrors = Loc::getMessage('MOD_UNINST_ERR');
        break;
    case 'reInstall':
        $hiddenInputs .= '<input type="hidden" name="mid" value="' . $moduleId . '">';
        $titleAction = Loc::getMessage(LANG_PREFIX . 'MODULE_REINSTALL_SUCCESS');
        $titleErrors = Loc::getMessage(LANG_PREFIX . 'MODULE_REINSTALL_ERROR');
        break;
    default:
        $hiddenInputs .= '<input type="hidden" name="mid" value="' . $moduleId . '">';
        $titleAction = Loc::getMessage(LANG_PREFIX . 'MODULE_REFERENCE_UPDATED');
        $titleErrors = Loc::getMessage(LANG_PREFIX . 'MODULE_ERROR_REFERENCE_UPDATED');
        break;
}

if ($hasErrors) {
    echo (new CAdminMessage($titleErrors, $exception))->Show();
} elseif ($action === 'preUnInstall') {
    echo renderUninstallForm($moduleId);
} else {
    CAdminMessage::ShowNote($titleAction);
}

echo renderBackForm($hiddenInputs);

/**
 * @param string $moduleId
 * @return string
 */
function renderUninstallForm(string $moduleId): string
{
    global $APPLICATION;
    return '
        <form action="' . $APPLICATION->GetCurPage() . '" method="post">
            ' . bitrix_sessid_post() . '
            <input type="hidden" name="lang" value="' . LANGUAGE_ID . '">
            <input type="hidden" name="id" value="' . $moduleId . '">
            <input type="hidden" name="uninstall" value="Y">
            <input type="hidden" name="step" value="2">
            ' . (new CAdminMessage(Loc::getMessage('MOD_UNINST_WARN')))->Show() . '
            <p>' . Loc::getMessage('MOD_UNINST_SAVE') . '</p>
            <p>
                <input type="checkbox" name="savedata" id="savedata" value="Y" checked>
                <label for="savedata">' . Loc::getMessage('MOD_UNINST_SAVE_TABLES') . '</label>
            </p>
            <input type="submit" name="" value="' . Loc::getMessage('MOD_UNINST_DEL') . '">
        </form>';
}

/**
 * @param string $hiddenInputs
 * @return string
 */
function renderBackForm(string $hiddenInputs): string
{
    global $APPLICATION;
    return '
        <form action="' . $APPLICATION->GetCurPage() . '" method="post">
            ' . $hiddenInputs . '
            <input type="submit" name="" value="' . Loc::getMessage(LANG_PREFIX . 'MODULE_BACK') . '">
        </form>';
}