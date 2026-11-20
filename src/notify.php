<?php
declare(strict_types=1);
namespace ff\windows;

use function ff\windows\internal\{powershell};

/**
 * 在系统托盘显示一条气泡通知，成功返回 true。
 * $duration 为停留毫秒数，默认 3000。注意本函数会阻塞约 $duration 毫秒。
 */
function notify(string $title, string $message, int $duration = 3000): bool{
	$t = str_replace("'", "''", $title);
	$m = str_replace("'", "''", $message);
	$script = <<<PS
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing
\$n = New-Object System.Windows.Forms.NotifyIcon
\$n.Icon = [System.Drawing.SystemIcons]::Information
\$n.BalloonTipTitle = '{$t}'
\$n.BalloonTipText = '{$m}'
\$n.Visible = \$true
\$n.ShowBalloonTip({$duration})
Start-Sleep -Milliseconds \$duration
\$n.Dispose()
PS;
	$r = powershell($script);
	return $r['code'] === 0;
}
