<?php
declare(strict_types=1);
namespace ff\windows\shortcut;

use function ff\windows\internal\{com};
use function ff\windows\folder\special;

/**
 * 创建 .lnk 快捷方式，成功返回 true。COM 不可用或创建失败返回 false。
 */
function make(string $target,
	string $lnk,
	?string $args = null,
	?string $icon = null,
	?string $workdir = null,
	?string $desc = null
): bool{
	$shell = com('WScript.Shell');
	if($shell === null) return false;
	try{
		$sc = $shell->CreateShortcut($lnk);
		$sc->TargetPath = $target;
		if($args !== null) $sc->Arguments = $args;
		if($icon !== null) $sc->IconLocation = $icon;
		if($workdir !== null) $sc->WorkingDirectory = $workdir;
		if($desc !== null) $sc->Description = $desc;
		$sc->Save();
		return true;
	}catch(\Throwable){
		return false;
	}
}
/**
 * 在桌面创建名为 $name 的快捷方式（自动补 .lnk），成功返回 true。
 */
function onDesktop(string $name,
	string $target,
	?string $args = null,
	?string $icon = null,
	?string $workdir = null,
	?string $desc = null
): bool{
	$dir = special('desktop');
	if($dir === null) return false;
	$lnk = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $name . '.lnk';
	return make($target, $lnk, $args, $icon, $workdir, $desc);
}
/**
 * 读取快捷方式信息，返回 target/args/icon/workdir/desc；文件不存在或 COM 不可用时返回 null。
 */
function read(string $lnk): ?array{
	if(!is_file($lnk)) return null;
	$shell = com('WScript.Shell');
	if($shell === null) return null;
	try{
		$sc = $shell->CreateShortcut($lnk);
		return [
			'target' => $sc->TargetPath,
			'args' => $sc->Arguments,
			'icon' => $sc->IconLocation,
			'workdir' => $sc->WorkingDirectory,
			'desc' => $sc->Description,
		];
	}catch(\Throwable){
		return null;
	}
}
