<?php
declare(strict_types=1);
namespace ff\windows\registry;

use InvalidArgumentException;
use function ff\windows\internal\{cmd};

/**
 * 获取注册表键值。
 * 传 $name 时返回该值（REG_DWORD 转 int，其余为 string），不存在返回 null；
 * 不传 $name 时返回该键下所有「名称 => 值」的关联数组，键不存在返回 null。
 * $path 支持 HKCU/HKLM/HKCR/HKU/HKCC 缩写或完整名；子键必须用反斜杠 \ 分隔，
 * 正斜杠 / 会被 reg.exe 当作选项开关导致查询失败并返回 null。
 */
function get(string $path, ?string $name = null): mixed{
	if(trim($path) === '') throw new InvalidArgumentException('注册表路径不能为空');
	$aliases = [
		'HKCU' => 'HKEY_CURRENT_USER',
		'HKLM' => 'HKEY_LOCAL_MACHINE',
		'HKCR' => 'HKEY_CLASSES_ROOT',
		'HKU' => 'HKEY_USERS',
		'HKCC' => 'HKEY_CURRENT_CONFIG',
	];
	$parts = explode('\\', $path, 2);
	$head = strtoupper(array_shift($parts));
	$head = $aliases[$head] ?? $head;
	$normalized = $head . ($parts === [] ? '' : '\\' . $parts[0]);
	$command = 'reg query "' . $normalized . '"';
	if($name !== null) $command .= ' /v "' . $name . '"';
	$r = cmd($command);
	if($r['code'] !== 0) return null;
	$values = [];
	foreach(explode("\n", $r['out']) as $line){
		if(preg_match('/^\s+(.+?)\s+(REG_\w+)\s+(.*)$/', rtrim($line, "\r"), $m)){
			$values[trim($m[1])] = $m[2] === 'REG_DWORD' ? hexdec(trim($m[3])) : trim($m[3]);
		}
	}
	return $name !== null ? $values[$name] ?? null : $values;
}
