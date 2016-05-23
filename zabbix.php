<?php
/**
 * Usage: php zabbix.php executable zabbixHost zabbixPort metricsHostname zabbixApiUrl metricsNamespace
 */
require __DIR__ . '/vendor/autoload.php';

if (empty($argv[1]) || empty($argv[2]) || empty($argv[3]) || empty($argv[4]) || empty($argv[5]) || empty($argv[6])) {
    echo 'Required arguments not provided' . PHP_EOL;
    echo 'Example: php statsite-sink-zabbix.phar /usr/bin/zabbix_sender zabbixHost 10051 metricsHostname http://user:password@zabbixhost/api metricsNamespace' . PHP_EOL;
    exit(1);
}

$executable = $argv[1];
$host = $argv[2];
$port = $argv[3];
$metricsHostname = $argv[4];
$zabbixApiUrl = $argv[5];
$namespace = $argv[6];
$debugMode = in_array('--debug', $argv, true);

//start
msg("Push to zabbix started, destination: {$executable} {$host}:{$port}, metrics hostname: {$metricsHostname}, " .
    "namespace: {$namespace}, API endpoint: {$zabbixApiUrl}");

//normalize API url
//$zabbixApiUrl = 'http://admin:zabbix:192.168.99.100:32779/zabbix/api_jsonrpc.php';
$zabbixApiUrlParts = parse_url($zabbixApiUrl);

$zabbixApiWrapper = new \StatsiteSinks\ZabbixApiWrapper(
    combineUrlWithoutAuth($zabbixApiUrlParts),
    $zabbixApiUrlParts['user'],
    $zabbixApiUrlParts['pass']
);

if ($debugMode) {
    $stdin = 'counts.test' . (int)rand(1,100) . '|22.000000|1458126040';
} else {
    $stdin = file_get_contents('php://stdin');
}

$stdinLines = array_filter(explode(PHP_EOL, trim($stdin)));
if ($stdinLines) {
    msg("Metrics to send:" . count($stdinLines));

    $zabbixFileContent = '';

    foreach ($stdinLines as $line) {
        list($typeKey, $value, $timestamp) = explode('|', $line);
        list($type, $key) = explode('.', $typeKey, 2);

        switch ($type) {
            case 'gauges':
            case 'counts':
                $zabbixKey = "{$namespace}.{$key}.{$type}";

                $zabbixApiWrapper->ensureZabbixItem($metricsHostname, $zabbixKey);

                $value = (float)$value;
                $line = "{$metricsHostname} {$zabbixKey} {$value}";
                $zabbixFileContent .= $line . PHP_EOL;
                msg($line);
                break;
            default:
                //for now we support only gauges and counters
        }
    }

    $zabbixFilePath = sys_get_temp_dir() . '/zabbix.txt';
    file_put_contents($zabbixFilePath, $zabbixFileContent);

    msg('Metrics to send: ' . str_replace(PHP_EOL, ';', $zabbixFileContent));

    $cmd = "{$executable} -vv --zabbix-server {$host} --port {$port} --input-file {$zabbixFilePath}";
    msg("Executing: {$cmd}");

    exec($cmd);

    $pipes = array();
    $proc = proc_open($cmd, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    $stdout = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    $stderr = trim(stream_get_contents($pipes[2]));
    fclose($pipes[2]);
    proc_close($proc);

    msg("stdout: {$stdout}");
    msg("stderr: {$stderr}");

    msg("Process finished successfully");
} else {
    msg("No metrics to send");
}

exit(0);

function msg($msg)
{
    echo date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL;
}

function combineUrlWithoutAuth(array $parsedUrl)
{
    $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
    $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
    $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
    $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
    $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
    return "$scheme$host$port$path$query$fragment";
}