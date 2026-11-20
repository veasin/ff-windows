<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use function ff\test;
use function ff\windows\fs\{attr, recycle, size, which};
use function ff\windows\folder\{icon, iconOf, special};
use function ff\windows\net\{listening, pid};
use function ff\windows\process\{kill, run, title};
use function ff\windows\system\{arch, dpi, version};
use function ff\windows\shortcut\{make, read};
use function ff\windows\registry\get;

// 临时目录在注册阶段准备，用例执行完后统一清理（test() 是延迟执行）
$root = sys_get_temp_dir() . '/ffw_auto_' . bin2hex(random_bytes(4));
$sizeDir = $root . '/size';
$iconDir = $root . '/icon';
$attrDir = $root . '/attr';
mkdir($sizeDir, 0777, true);
mkdir($iconDir);
mkdir($attrDir);
file_put_contents($sizeDir . '/normal.txt', '12345');
file_put_contents($sizeDir . '/.hidden.txt', '1234567890');
mkdir($sizeDir . '/sub');
file_put_contents($attrDir . '/test.txt', 'x');
$missing = $root . '/no_such_path';

// ---------- 参数校验 ----------
test('net\listening 端口 0 抛异常', fn() => listening(0), InvalidArgumentException::class);
test('net\listening 端口 65536 抛异常', fn() => listening(65536), InvalidArgumentException::class);
test('fs\attr 路径不存在抛异常', fn() => attr($missing, '+h'), InvalidArgumentException::class);
test('fs\recycle 路径不存在抛异常', fn() => recycle($missing), InvalidArgumentException::class);
test('fs\size 非目录抛异常', fn() => size($missing), InvalidArgumentException::class);
test('folder\icon 非目录抛异常', fn() => icon($missing, 'x.ico'), InvalidArgumentException::class);
test('folder\special 未知名称抛异常', fn() => special('ffw_no_such_folder'), InvalidArgumentException::class);
test('registry\get 空路径抛异常', fn() => get(''), InvalidArgumentException::class);
test('process\kill 空 PID 列表抛异常', fn() => kill([]), InvalidArgumentException::class);

// ---------- 查询类 ----------
test('fs\which php 返回路径', fn() => which('php'), fn($v) => is_string($v) && $v !== '');
test('fs\which 不存在返回 null', fn() => which('ffw_no_such_exe_xyz'), null);
test('process\run 成功返回 stdout', fn() => trim((string)run('echo ffw_run_ok')), 'ffw_run_ok');
test('process\run 失败返回 null', fn() => run('exit /b 1'), null);
test('process\title 无匹配返回空数组', fn() => title('ffw_no_such_window_' . bin2hex(random_bytes(4))), []);
test('net\listening 返回数组', fn() => listening(1), fn($v) => is_array($v));
test('net\pid 返回 int 或 null', fn() => pid(1), fn($v) => $v === null || is_int($v));
test('folder\special windows 是目录', fn() => special('windows'), fn($v) => is_string($v) && is_dir($v));
test('folder\special temp 是目录', fn() => special('temp'), fn($v) => is_string($v) && is_dir($v));
test('system\version 版本格式', fn() => version(), fn($v) => is_string($v) && preg_match('/^\d+\.\d+/', $v) === 1);
test('system\arch 合法架构', fn() => arch(), fn($v) => in_array($v, ['x86', 'x64', 'arm64'], true));
test('system\dpi 正数或 null', fn() => dpi(), fn($v) => $v === null || (is_int($v) && $v > 0));
test('registry\get 已存在的字符串值', fn() => get('HKLM\SOFTWARE\Microsoft\Windows NT\CurrentVersion', 'ProductName'), fn($v) => is_string($v) && $v !== '');
test('registry\get 不存在的键返回 null', fn() => get('HKCU\ffw_no_such_key_xyz'), null);
test('registry\get 正斜杠路径返回 null', fn() => get('HKCU/Environment'), null);

// ---------- 临时目录隔离（有副作用但可清理） ----------
test('fs\size 空目录统计', fn() => size($iconDir), ['dirs' => 0, 'files' => 0, 'bytes' => 0]);
test('fs\size 含隐藏文件统计', fn() => size($sizeDir), ['dirs' => 1, 'files' => 2, 'bytes' => 15]);
test('fs\attr 加隐藏属性', fn() => attr($attrDir . '/test.txt', '+h'), true);
test('fs\attr 去隐藏属性', fn() => attr($attrDir . '/test.txt', '-h'), true);
test('folder\icon 写 desktop.ini', fn() => icon($iconDir, 'C:\Windows\System32\shell32.dll'), true);
test('folder\iconOf 读回图标资源', fn() => iconOf($iconDir), fn($v) => is_string($v) && str_contains($v, 'shell32.dll,0'));
test('folder\iconOf 未设置返回 null', fn() => iconOf($attrDir), null);

if(class_exists('COM')){
	test('shortcut\make 创建快捷方式', fn() => make('C:\Windows\System32\notepad.exe', $root . '/test.lnk'), true);
	test('shortcut\read 读回目标', fn() => read($root . '/test.lnk'), fn($v) => is_array($v) && ($v['target'] ?? null) === 'C:\Windows\System32\notepad.exe');
	test('shortcut\read 不存在返回 null', fn() => read($missing), null);
}

$result = test();

// ---------- 清理 ----------
$cleanup = function(string $dir) use (&$cleanup): void{
	if(!is_dir($dir)) return;
	$ini = $dir . '/desktop.ini';
	if(is_file($ini)){
		@attr($ini, '-s -h');
		@unlink($ini);
	}
	@attr($dir, '-s -h');
	foreach(@scandir($dir) ?: [] as $entry){
		if($entry === '.' || $entry === '..') continue;
		$path = $dir . '/' . $entry;
		is_dir($path) ? $cleanup($path) : @unlink($path);
	}
	@rmdir($dir);
};
$cleanup($root);

// ---------- 输出 ----------
printf("total=%d passed=%d failed=%d\n", $result['total'], $result['passed'], count($result['failed'] ?? []));
foreach($result['failed'] ?? [] as $f){
	$actual = $f['actual'] instanceof Throwable
		? get_class($f['actual']) . ': ' . $f['actual']->getMessage()
		: var_export($f['actual'], true);
	printf("FAIL [%s]\n  expected: %s\n  actual:   %s\n", $f['label'], var_export($f['expected'], true), $actual);
}
exit(isset($result['failed']) ? 1 : 0);
