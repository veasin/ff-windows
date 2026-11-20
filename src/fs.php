<?php
declare(strict_types=1);
namespace ff\windows\fs;

use InvalidArgumentException;
use function ff\windows\internal\{cmd, powershell};

/**
 * 设置文件/文件夹属性（如 "+s +h" / "-h"），成功返回 true。
 * 路径不存在抛 InvalidArgumentException。
 */
function attr(string $path, string $attrs): bool{
	if(!file_exists($path)) throw new InvalidArgumentException("路径不存在: {$path}");
	$r = cmd('attrib ' . $attrs . ' "' . $path . '"');
	return $r['code'] === 0;
}
/**
 * 把文件或目录移入回收站，成功返回 true。路径不存在抛 InvalidArgumentException。
 */
function recycle(string $path): bool{
	if(!file_exists($path)) throw new InvalidArgumentException("路径不存在: {$path}");
	$abs = realpath($path) ?: $path;
	$verb = is_dir($abs) ? 'DeleteDirectory' : 'DeleteFile';
	$quoted = str_replace("'", "''", $abs);
	$script = "Add-Type -AssemblyName Microsoft.VisualBasic; " . "[Microsoft.VisualBasic.FileIO.FileSystem]::{$verb}('{$quoted}', 'OnlyErrorDialogs', 'SendToRecycleBin')";
	$r = powershell($script);
	return $r['code'] === 0;
}
/**
 * 在 PATH 中查找可执行文件，返回第一个匹配路径，找不到返回 null。
 */
function which(string $name): ?string{
	$r = cmd('where ' . escapeshellarg($name));
	$lines = array_values(array_filter(array_map('trim', explode("\n", $r['out'])), fn($l) => $l !== ''));
	return $lines === [] ? null : $lines[0];
}
/**
 * 统计目录下子目录数、文件数与文件总字节数，包含隐藏/系统文件，跳过无权限目录。
 * 返回 ['dirs' => int, 'files' => int, 'bytes' => int]，$dir 不是有效目录抛 InvalidArgumentException。
 */
function size(string $dir): array{
	if(!is_dir($dir)) throw new InvalidArgumentException("不是有效目录: {$dir}");
	$abs = realpath($dir) ?: $dir;
	$quoted = str_replace("'", "''", $abs);
	$script = "\$items = Get-ChildItem -LiteralPath '{$quoted}' -Recurse -Force -ErrorAction SilentlyContinue; \$d = 0; \$f = 0; \$b = 0; foreach(\$x in \$items){ if(\$x.PSIsContainer){ \$d++ } else { \$f++; \$b += \$x.Length } }; [Console]::Out.Write(\"\$d|\$f|\$b\")";
	$r = powershell($script);
	$parts = explode('|', trim($r['out']));
	return count($parts) === 3 ? ['dirs' => (int)$parts[0], 'files' => (int)$parts[1], 'bytes' => (int)$parts[2]] : ['dirs' => 0, 'files' => 0, 'bytes' => 0];
}
