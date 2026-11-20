<?php
declare(strict_types=1);
namespace ff\windows\internal;

/**
 * 创建 COM 对象。扩展缺失或 ProgID 不可用时记录日志并返回 null（优雅降级）。
 * @internal
 */
function com(string $progid): ?object{
	if(!class_exists('COM')){
		error_log("ff-windows: 缺少 ext-com_dotnet，无法创建 COM 对象 {$progid}");
		return null;
	}
	try{
		return new \COM($progid);
	}catch(\Throwable $e){
		error_log("ff-windows: 无法创建 COM 对象 {$progid}: " . $e->getMessage());
		return null;
	}
}
/**
 * 执行传统 cmd 命令，返回 ['out' => string, 'err' => string, 'code' => int]。
 * @internal
 */
function cmd(string $command): array{
	return exec('cmd /c ' . $command);
}
/**
 * 执行 PowerShell 5.1 脚本，返回 ['out' => string, 'err' => string, 'code' => int]。
 * 脚本以 UTF-16LE 编码后通过 -EncodedCommand 传入，避免引号与中文问题；
 * 同时把输出编码设为 UTF-8，便于 PHP 直接读取。
 * @internal
 */
function powershell(string $script, ?string $stdin = null): array{
	$script = '[Console]::OutputEncoding=[System.Text.Encoding]::UTF8;' . $script;
	$encoded = base64_encode((string)iconv('UTF-8', 'UTF-16LE', $script));
	$command = 'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -EncodedCommand ' . $encoded;
	return exec($command, $stdin);
}
/**
 * 底层进程执行，返回 ['out' => string, 'err' => string, 'code' => int]。
 * @internal
 */
function exec(string $command, ?string $stdin = null): array{
	$desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
	$proc = proc_open($command, $desc, $pipes);
	if(!is_resource($proc)) return ['out' => '', 'err' => '无法启动进程: ' . $command, 'code' => -1];
	if($stdin !== null) fwrite($pipes[0], $stdin);
	fclose($pipes[0]);
	$out = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$err = stream_get_contents($pipes[2]);
	fclose($pipes[2]);
	$code = proc_close($proc);
	return ['out' => $out, 'err' => $err, 'code' => $code];
}
