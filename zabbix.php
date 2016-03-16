#!/usr/bin/env php
<?php
/**
 * Usage: php zabbix.php executable zabbixHost zabbixPort metricsHostname metricsNamespace
 */
if (empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || empty($argv[4]) || empty($argv[5])) {
    echo 'Required arguments not provided' . PHP_EOL;
    exit(1);
}

$executable = $argv[1];
$host = $argv[2];
$port = $argv[3];
$hostname = $argv[4];
$namespace = $argv[5];

msg("push to zabbix started, destination: {$executable} {$host}:{$port}, metrics hostname: {$hostname}, namespace: {$namespace}");

//normalize namespace
if ($namespace) {
    $namespace  = $namespace . '.';
}

$stdin = file_get_contents('php://stdin');
//$stdin = 'counts.foo|11.000000|1458126040';

$stdinLines = array_filter(explode(PHP_EOL, trim($stdin)));
if ($stdinLines) {
    msg("metrics to send:" . count($stdinLines));

    $zabbixFileContent = '';

    foreach ($stdinLines as $line) {
        list($typeKey, $value, $timestamp) = explode('|', $line);
        list($type, $key) = explode('.', $typeKey, 2);

        switch ($type) {
            case 'gauges':
            case 'counts':
                $value = (float)$value;
                $line = "{$hostname} {$namespace}{$type}.{$key} {$value}";
                $zabbixFileContent .= $line . PHP_EOL;
                msg($line);
                break;
            default:
                //for now we support only gauges and counters
        }
    }

    $zabbixFilePath = __DIR__ . '/zabbix.txt';
    file_put_contents($zabbixFilePath, $zabbixFileContent);

    $cmd = "{$executable} -vv --zabbix-server {$host} --port {$port} --input-file {$zabbixFilePath}";
    msg("executing: {$cmd}");
//    exec($cmd);

    $pipes = array();
    $proc = proc_open($cmd, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    proc_close($proc);

    msg("stdout: {$stdout}");
    msg("stderr: {$stderr}");

    msg("process finished successfully");
} else {
    msg("nothing to send");
}

exit(0);

function msg($msg)
{
    echo date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL;
}