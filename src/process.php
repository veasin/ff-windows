<?php
declare(strict_types=1);
namespace ff\windows\process;

use InvalidArgumentException;
use function ff\windows\internal\{cmd, powershell};

/**
 * 按 PID 强制结束进程，成功返回 true。PID 列表为空抛 InvalidArgumentException。
 */
function kill(int|array $pid): bool{
	$ids = is_array($pid) ? array_values($pid) : [$pid];
	if($ids === []){
		throw new InvalidArgumentException('PID 列表不能为空');
	}
	$parts = array_map(fn(int $p) => '/PID ' . $p, $ids);
	$r = cmd('taskkill ' . implode(' ', $parts) . ' /F');
	return $r['code'] === 0;
}
/**
 * 在新窗口中启动命令（忠实封装 cmd start），成功返回 true。
 * $title 为 start 的窗口标题，$dir 对应 /D 指定工作目录。
 * 用 popen 而非 cmd()：start 的子进程继承管道，exec() 读管道会永久阻塞。
 */
function start(string $command, ?string $title = null, ?string $dir = null): bool{
	$head = 'start ';
	if($title !== null) $head .= '"' . str_replace('"', '', $title) . '" ';
	if($dir !== null) $head .= '/D "' . str_replace('"', '', $dir) . '" ';
	$h = popen($head . $command, 'r');
	if($h === false) return false;
	return pclose($h) === 0;
}
/**
 * 用系统默认程序启动文件或 URL，成功返回 true。
 */
function launch(string $path): bool{
	$abs = realpath($path) ?: $path;
	$quoted = str_replace("'", "''", $abs);
	$r = powershell("Start-Process '{$quoted}'");
	return $r['code'] === 0;
}
/**
 * 同步执行命令并返回 stdout，失败返回 null。
 */
function run(string $command): ?string{
	$r = cmd($command);
	return $r['code'] === 0 ? $r['out'] : null;
}
/**
 * 查询窗口标题精确匹配 $title 的进程 PID 列表。
 */
function title(string $title): array{
	$quoted = str_replace("'", "''", $title);
	$script = "Get-Process | Where-Object { \$_.MainWindowTitle -eq '{$quoted}' } | ForEach-Object { \$_.Id }";
	$r = powershell($script);
	$lines = array_values(array_filter(array_map('trim', explode("\n", $r['out'])), fn($l) => $l !== ''));
	return array_map('intval', $lines);
}
