# ff-windows

Windows 平台能力库，ff 生态的纯函数扩展。封装文件夹、文件系统、网络、进程、快捷方式、剪贴板、通知、对话框、系统信息与注册表读取等 Windows 特有交互。

## 设计约定

- **纯函数**：无状态、无类，函数按命名空间分模块，全部以 `ff\windows\` 为前缀。
- **平台**：仅支持 Windows 10+。库名即平台声明，不在运行时做平台检查。
- **PowerShell**：内部固定使用 `powershell.exe`（PS 5.1），不兼容 PS7/`pwsh`。脚本以 UTF-16LE 编码经 `-EncodedCommand` 传入，输出统一为 UTF-8。
- **cmd**：netstat/taskkill/attrib/reg/where 等传统命令走 `cmd.exe`。
- **COM 可选**：`ext-com_dotnet` 通过 composer `suggest` 声明。扩展缺失时，依赖 COM 的函数（shortcut 系列）优雅降级返回 `false`/`null`，并记录 `error_log`，不会致命报错。

## 失败语义

- 查询类：取不到返回 `null`。
- 动作类：失败返回 `false`。
- 参数非法：抛 `InvalidArgumentException`。

## 模块与 API

| 命名空间 | 函数 |
| --- | --- |
| `ff\windows\net` | `listening` `pid` |
| `ff\windows\process` | `kill` `start` `launch` `run` `title` |
| `ff\windows\fs` | `attr` `recycle` `which` `size` |
| `ff\windows\folder` | `special` `icon` `iconOf` `open` |
| `ff\windows\shortcut` | `make` `onDesktop` `read` |
| `ff\windows\clipboard` | `set` `get` |
| `ff\windows` | `notify` |
| `ff\windows\dialog` | `message` `confirm` `popup` |
| `ff\windows\system` | `version` `arch` `dpi` |
| `ff\windows\registry` | `get` |

> PE/图标解析暂未纳入，未来会以窄 API（`ff\windows\icon`）形式对外暴露。

## 用法示例

```php
use function ff\windows\net\{listening, pid};
use function ff\windows\process\{kill, start, title};
use function ff\windows\fs\{attr, recycle, which, size};
use function ff\windows\folder\{special, icon, iconOf, open};
use function ff\windows\shortcut\{onDesktop, read};
use function ff\windows\clipboard\{set, get};
use function ff\windows\{notify};
use function ff\windows\dialog\{confirm};
use function ff\windows\system\{version, arch};

// 文件夹 / 文件系统
echo special('desktop');               // C:\Users\xxx\Desktop
open(special('desktop'));              // 在资源管理器中打开桌面
echo which('php');                     // ...\php.exe
print_r(size('C:\\games'));            // ['dirs' => N, 'files' => N, 'bytes' => N]

// 网络：查 8080 端口占用并结束
$pids = listening(8080);
if ($pids !== []) kill($pids[0]);

// 进程：在带标题的新窗口启动服务，按标题查窗口 PID
start('php -S 127.0.0.1:8080', 'PHP Server', 'C:\\games');
$windowPids = title('PHP Server');

// 文件夹图标
icon('C:\\games\\demo', 'C:\\icons\\demo.ico');
echo iconOf('C:\\games\\demo');        // C:\icons\demo.ico,0

// 快捷方式
onDesktop('我的游戏', 'C:\\games\\demo\\run.exe', icon: 'C:\\icons\\demo.ico');
$info = read(special('desktop') . '\\我的游戏.lnk');

// 剪贴板
set('hello');
echo get();                            // hello

// 通知 / 对话框
notify('完成', '任务已处理');
if (confirm('确认删除？')) { /* ... */ }

// 系统 / 注册表
echo version();                        // 10.0.xxxxx
echo arch();                           // x64
echo \ff\windows\registry\get('HKCU\\Software\\MyApp', 'Path');
```

## 安装

```bash
composer require veasin/ff-windows
```

需要 COM 能力（快捷方式读写）时，确保 PHP 启用 `extension=com_dotnet`。

## 测试

```bash
php test/auto.php              # 自动测试：参数校验、查询类、临时目录隔离
php test/manual.php            # 列出全部手工测试项（不执行）
php test/manual.php notify     # 执行指定手工项
```

自动测试基于 `ff\test()`，只覆盖安全可测部分；弹窗、开窗口、污染系统状态的项目放在 `test/manual.php`，需按项目名显式执行。
