<?php
$phar = new Phar(__DIR__ . '/build/statsite-sink-zabbix.phar', 0, 'statsite-sink-zabbix.phar');
$phar->buildFromDirectory(dirname(__FILE__), '/\.php$/');
$phar->setStub($phar->createDefaultStub(__DIR__ . 'zabbix.php'));
