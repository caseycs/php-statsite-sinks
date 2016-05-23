<?php
if (is_file('statsite-sink-zabbix.phar')) {
    unlink('statsite-sink-zabbix.phar');
}
$phar = new Phar('statsite-sink-zabbix.phar', 0, 'statsite-sink-zabbix.phar');
// add all files in the project
$phar->buildFromDirectory(dirname(__FILE__));
$phar->setStub($phar->createDefaultStub('zabbix.php', 'zabbix.php'));
