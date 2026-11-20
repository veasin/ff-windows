<?php
declare(strict_types=1);
namespace ff\windows\net;

use InvalidArgumentException;
use function ff\windows\internal\cmd;

/**
 * 查询监听指定 TCP 端口的 PID 列表。
 */
function listening(int $port): array{
	if($port < 1 || $port > 65535) throw new InvalidArgumentException("无效端口: {$port}");
	$r = cmd('netstat -ano -p tcp | findstr ":' . $port . ' " | findstr LISTENING');
	$pids = [];
	foreach(explode("\n", $r['out']) as $line){
		if(preg_match('/\s+LISTENING\s+(\d+)\s*$/', rtrim($line), $m)) $pids[] = (int)$m[1];
	}
	return array_values(array_unique($pids));
}
/**
 * 查询监听指定端口的第一个 PID，无监听返回 null。
 */
function pid(int $port): ?int{
	return listening($port)[0] ?? null;
}
