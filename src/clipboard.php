<?php
declare(strict_types=1);
namespace ff\windows\clipboard;

use function ff\windows\internal\powershell;

/**
 * 设置剪贴板文本内容，成功返回 true。
 */
function set(string $text): bool{
	$r = powershell('$v = [Console]::In.ReadToEnd(); Set-Clipboard -Value $v', $text);
	return $r['code'] === 0;
}
/**
 * 读取剪贴板文本内容，失败返回 null。
 */
function get(): ?string{
	$r = powershell('[Console]::Out.Write((Get-Clipboard -Raw))');
	return $r['code'] !== 0 ? null : $r['out'];
}
