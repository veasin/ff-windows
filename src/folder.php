<?php
declare(strict_types=1);
namespace ff\windows\folder;

use InvalidArgumentException;
use function ff\windows\fs\attr;
use function ff\windows\internal\powershell;

/**
 * 获取系统特殊文件夹路径，未知名称抛 InvalidArgumentException，取不到返回 null。
 * 支持：desktop/documents/pictures/music/videos/downloads/appdata/localappdata/
 * temp/home/programfiles/programfilesx86/windows/system32/startmenu/startup。
 */
function special(string $name): ?string{
	$map = [
		'desktop' => '[Environment]::GetFolderPath("Desktop")',
		'documents' => '[Environment]::GetFolderPath("MyDocuments")',
		'pictures' => '[Environment]::GetFolderPath("MyPictures")',
		'music' => '[Environment]::GetFolderPath("MyMusic")',
		'videos' => '[Environment]::GetFolderPath("MyVideos")',
		'downloads' => 'Join-Path $env:USERPROFILE "Downloads"',
		'appdata' => '$env:APPDATA',
		'localappdata' => '$env:LOCALAPPDATA',
		'temp' => '$env:TEMP',
		'home' => '$env:USERPROFILE',
		'programfiles' => '$env:ProgramFiles',
		'programfilesx86' => '${env:ProgramFiles(x86)}',
		'windows' => '$env:WINDIR',
		'system32' => 'Join-Path $env:SystemRoot "System32"',
		'startmenu' => '[Environment]::GetFolderPath("StartMenu")',
		'startup' => '[Environment]::GetFolderPath("Startup")',
	];
	if(!isset($map[$name])) throw new InvalidArgumentException("未知的文件夹类型: {$name}");
	$r = powershell($map[$name]);
	$path = trim($r['out']);
	return $path === '' ? null : $path;
}
/**
 * 给文件夹设置自定义图标（写 desktop.ini 并加系统/隐藏属性），成功返回 true。
 * $index 为图标资源索引，默认 0。
 */
function icon(string $dir, string $file, int $index = 0): bool{
	if(!is_dir($dir)) throw new InvalidArgumentException("不是有效目录: {$dir}");
	$ini = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'desktop.ini';
	$absIco = realpath($file) ?: $file;
	$content = "[.ShellClassInfo]\r\nIconResource={$absIco},{$index}\r\n";
	return !(file_put_contents($ini, $content) === false) && attr($ini, '+s +h') && attr($dir, '+s');
}
/**
 * 读取文件夹自定义图标资源字符串（形如 "C:\icons\a.ico,0"），未设置返回 null。
 */
function iconOf(string $dir): ?string{
	$ini = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'desktop.ini';
	if(!is_file($ini)) return null;
	$content = file_get_contents($ini);
	return $content === false || !preg_match('/^IconResource=(.+)$/mi', $content, $m) ? null : trim($m[1]);
}
/**
 * 在资源管理器中打开目录；$dir 为 null 时直接打开资源管理器，成功返回 true。
 */
function open(?string $dir = null): bool{
	if($dir === null) $r = powershell("Start-Process explorer.exe");
	else{
		$abs = realpath($dir) ?: $dir;
		$quoted = str_replace("'", "''", $abs);
		$r = powershell("Start-Process explorer.exe -ArgumentList '{$quoted}'");
	}
	return $r['code'] === 0;
}
