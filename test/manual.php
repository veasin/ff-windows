<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use function ff\windows\notify;
use function ff\windows\dialog\{message, confirm, popup};
use function ff\windows\folder\{special, open};
use function ff\windows\process\{launch, start, title, kill};
use function ff\windows\fs\recycle;
use function ff\windows\shortcut\onDesktop;
use function ff\windows\clipboard\{set, get};

/**
 * 手工测试项：会弹窗、开窗口或污染系统状态，不参与自动测试。
 *   php test/manual.php          列出全部项目
 *   php test/manual.php notify   执行指定项目
 */
$cases = [
	'notify' => [
		'desc' => '系统托盘气泡通知，阻塞约 3 秒',
		'run' => fn() => notify('ff-windows 手工测试', '托盘通知测试'),
	],
	'dialog.message' => [
		'desc' => '弹出确定消息框，需点击「确定」',
		'run' => fn() => message('ff-windows 消息框测试'),
	],
	'dialog.confirm' => [
		'desc' => '弹出是/否确认框，需点击按钮（返回 true/false）',
		'run' => fn() => confirm('ff-windows 确认框测试'),
	],
	'dialog.popup' => [
		'desc' => '弹出文本输入框，输入后返回字符串，取消返回 null',
		'run' => fn() => popup('ff-windows 输入框测试', '手工测试', '默认值'),
	],
	'folder.open' => [
		'desc' => '打开资源管理器并定位到桌面',
		'run' => fn() => open(special('desktop')),
	],
	'process.launch' => [
		'desc' => '用系统默认程序打开网页（浏览器窗口）',
		'run' => fn() => launch('https://www.example.com'),
	],
	'process.start' => [
		'desc' => '新窗口启动 cmd /c pause，标题 ffw_manual_start；配合 process.kill 清理',
		'run' => function(){
			$ok = start('cmd /c pause', 'ffw_manual_start');
			return ['start' => $ok, 'pids' => title('ffw_manual_start')];
		},
	],
	'process.kill' => [
		'desc' => '结束标题为 ffw_manual_start 的窗口进程（配合 process.start）',
		'run' => function(){
			$pids = title('ffw_manual_start');
			return $pids === [] ? '无匹配进程' : kill($pids);
		},
	],
	'fs.recycle' => [
		'desc' => '在临时目录建文件并移入回收站（会污染回收站）',
		'run' => function(){
			$file = sys_get_temp_dir() . '/ffw_recycle_' . bin2hex(random_bytes(4)) . '.txt';
			file_put_contents($file, 'ff-windows recycle test');
			return ['file' => $file, 'recycled' => recycle($file)];
		},
	],
	'shortcut.onDesktop' => [
		'desc' => '在桌面创建 ffw_manual_test.lnk（需手动删除）',
		'run' => fn() => onDesktop('ffw_manual_test', 'C:\Windows\System32\notepad.exe'),
	],
	'clipboard.set' => [
		'desc' => '写入并读回剪贴板文本（会覆盖当前剪贴板内容）',
		'run' => function(){
			$text = 'ff-windows manual test ' . date('H:i:s');
			$ok = set($text);
			return ['set' => $ok, 'get' => get()];
		},
	],
];

$name = $argv[1] ?? 'list';
if($name === 'list' || $name === '--help' || $name === '-h'){
	echo "手工测试项（不自动执行）：\n";
	foreach($cases as $key => $case) printf("  %-22s %s\n", $key, $case['desc']);
	echo "\n用法：php test/manual.php <项目名>\n";
	exit(0);
}
if(!isset($cases[$name])){
	fwrite(STDERR, "未知项目: {$name}\n运行 php test/manual.php 查看清单\n");
	exit(1);
}
printf("执行 [%s] %s\n", $name, $cases[$name]['desc']);
$result = $cases[$name]['run']();
echo $result === null ? "null\n" : var_export($result, true) . PHP_EOL;
