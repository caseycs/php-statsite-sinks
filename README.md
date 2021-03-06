Statsite sinks written on PHP
=============================

Here are two sinks (they call backends this way) for https://github.com/armon/statsite.

First one to forward data to another statsd:

```
php forward.php statsdHost 8126
```

Second one - to push data to Zabbix:


```
php build.php
php build/statsite-sink-zabbix.phar /usr/bin/zabbix_sender zabbixHost 10051 metricsHostname http://user:password@zabbixhost/api metricsNamespace
# for example
php zabbix.php /usr/local/bin/zabbix_sender 192.168.99.100 32769 Zabbix http://admin:zabbix@192.168.99.100:32771/zabbix/api_jsonrpc.php test8
```

## Statsite config samples:

Node

```
[statsite]
port = 8125
udp_port = 8125
log_level = DEBUG
log_facility = local0
flush_interval = 10
timer_eps = 0.01
set_eps = 0.02
stream_cmd = timeout 10 php /home/user/path/forward.php localhost 8126 2>&1 >> forward.php.log
```

Master

```
[statsite]
port = 8126
udp_port = 8126
log_level = DEBUG
log_facility = local0
flush_interval = 60
timer_eps = 0.01
set_eps = 0.02
stream_cmd = timeout 10 php /user/path/zabbix.php /usr/bin/zabbix_sender localhost 10051 web1 namespace 2>&1 >> zabbix.php.log
```