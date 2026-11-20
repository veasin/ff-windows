<?php
declare(strict_types=1);
namespace ff\windows\system;

use function ff\windows\internal\powershell;

/**
 * 获取 Windows 版本号（如 "10.0.19045"），失败返回 null。
 */
function version(): ?string{
	$r = powershell('[Console]::Out.Write([Environment]::OSVersion.Version.ToString())');
	$v = trim($r['out']);
	return $v === '' ? null : $v;
}
/**
 * 获取 CPU 架构：x86/x64/arm64，无法识别返回 null。
 */
function arch(): ?string{
	$r = powershell('[Console]::Out.Write($env:PROCESSOR_ARCHITECTURE)');
	return match (strtoupper(trim($r['out']))) {
		'X86' => 'x86',
		'AMD64' => 'x64',
		'ARM64' => 'arm64',
		default => null,
	};
}
/**
 * 获取主显示器水平 DPI，失败返回 null。
 */
function dpi(): ?int{
	$script = 'Add-Type -AssemblyName System.Drawing; ' . '$g=[System.Drawing.Graphics]::FromHwnd([IntPtr]::Zero); ' . '[Console]::Out.Write($g.DpiX); $g.Dispose()';
	$r = powershell($script);
	$v = trim($r['out']);
	return ctype_digit($v) ? (int)$v : null;
}
