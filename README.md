Statsite sinks written on PHP
=============================

Here are two sinks (they call backends this way) for https://github.com/armon/statsite.

First one to forward data to another statsd:

```
php forward.php statsdHost 8126
```

Second one - to push data to Zabbix:


```
php zabbix.php /usr/bin/zabbix_sender zabbixHost 10051 metricsHostname metricsNamespace
```