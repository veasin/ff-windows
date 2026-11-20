<?php
declare(strict_types=1);
namespace ff\windows\dialog;

use function ff\windows\internal\powershell;

/**
 * 显示一个确定消息框，返回 true 表示正常显示并关闭。
 */
function message(string $text, string $title = '提示'): bool{
	return box($text, $title, 'OK', 'Information') !== null;
}
/**
 * 显示是/否确认框，用户点「是」返回 true，点「否」返回 false。
 */
function confirm(string $text, string $title = '确认'): bool{
	return box($text, $title, 'YesNo', 'Question') === 'Yes';
}
/**
 * 显示文本输入框，返回用户输入；取消或失败返回 null。
 */
function popup(string $text = '', string $title = '', string $default = ''): ?string{
	$t = str_replace("'", "''", $text);
	$ti = str_replace("'", "''", $title);
	$d = str_replace("'", "''", $default);
	$script = "Add-Type -AssemblyName Microsoft.VisualBasic; " . "[Console]::Out.Write([Microsoft.VisualBasic.Interaction]::InputBox('{$t}', '{$ti}', '{$d}'))";
	$r = powershell($script);
	return $r['code'] !== 0 ? null : $r['out'];
}
/**
 * 显示 MessageBox 并返回按钮结果字符串（OK/Yes/No/...），失败返回 null。
 * @internal
 */
function box(string $text, string $title, string $buttons, string $icon): ?string{
	$t = str_replace("'", "''", $text);
	$ti = str_replace("'", "''", $title);
	$script = "Add-Type -AssemblyName System.Windows.Forms; " . "[Console]::Out.Write([System.Windows.Forms.MessageBox]::Show('{$t}', '{$ti}', '{$buttons}', '{$icon}').ToString())";
	$r = powershell($script);
	$v = $r['out'];
	return $v === '' ? null : $v;
}
