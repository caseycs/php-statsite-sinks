Statsite sinks written on PHP
=============================

Here are two sinks (they call backends this way) for https://github.com/armon/statsite.

First one to forward data to another statsd:

```
php /home/vagrant/src/php-statsite-sinks/forward.php localhost 8126
```

Second one - to push data to Zabbix:


```
php zabbix.php /usr/bin/zabbix_sender localhost 10051 web1 namespace
```