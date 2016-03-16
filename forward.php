#!/usr/bin/env php
<?php
/**
 * Usage: php forward.php host port [namespace]
 */
require __DIR__ . '/vendor/autoload.php';

if (empty($argv[1]) || empty($argv[2])) {
    echo 'Destination host and port are not provided' . PHP_EOL;
    exit(1);
}

$host = $argv[1];
$port = $argv[2];
$namespace = empty($argv[3]) ? '' : $argv[3];

msg("forward started, destination: {$host}:{$port}, namespace: {$namespace}");

$stdin = file_get_contents('php://stdin');
//$stdin = 'counts.foo|11.000000|1458126040';

$stdinLines = array_filter(explode(PHP_EOL, trim($stdin)));
if ($stdinLines) {
    msg("metrics to send:" . count($stdinLines));
    $connection = new \Domnikl\Statsd\Connection\TcpSocket($host, $port);
    $statsd = new \Domnikl\Statsd\Client($connection, $namespace);
    $statsd->startBatch();

    foreach ($stdinLines as $line) {
        list($typeKey, $value, $timestamp) = explode('|', $line);
        list($type, $key) = explode('.', $typeKey, 2);

        switch ($type) {
            case 'gauges':
                $value = (float)$value;
                $statsd->gauge($key, $value);
                msg("gauge {$key}={$value}" . PHP_EOL);
                break;
            case 'counts':
                $value = (float)$value;
                $statsd->count($key, $value);
                msg("count {$key}={$value}");
                break;
            default:
                //for now we support only gauges and counters
        }
    }

    $statsd->endBatch();

    msg("process finished successfully");
} else {
    msg("nothing to send");
}

exit(0);

function msg($msg)
{
    echo date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL;
}