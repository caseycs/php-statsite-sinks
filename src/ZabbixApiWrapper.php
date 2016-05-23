<?php
namespace StatsiteSinks;

use ZabbixApi\ZabbixApi;

class ZabbixApiWrapper
{
    /**
     * @var ZabbixApi
     */
    private $zabbix;

    /**
     * @param string $url
     * @param string $username
     * @param string $password
     */
    public function __construct($url, $username, $password)
    {
        $this->zabbix = new ZabbixApi($url, $username, $password);
    }

    /**
     * @param string $host
     * @param string $itemKey
     * @throws \Exception
     * @throws \ZabbixApi\Exception
     * @throws \Exception
     */
    public function ensureZabbixItem($host, $itemKey)
    {
        $params = ['filter' => ['host' => [$host]]];
        $hosts = $this->zabbix->hostGet($params);

        if (count($hosts) !== 1) {
            throw new \Exception('Zabbix host not found');
        }

        /** @var \stdClass $hostDetails */
        $hostDetails = $this->zabbix->hostGet($params)[0];

        $params = [
            'name' => $itemKey,
            'key_' => $itemKey,
            'hostid' => $hostDetails->hostid,
            'type' => 2,
            'value_type' => 0,
            'interfaceid' => '1',
            'applications' => [
            ],
            'delay' => 30
        ];

        try {
            $itemsId = $this->zabbix->itemCreate($params);
        } catch (\ZabbixApi\Exception $e) {
            // it's fine then item already exists
            if (0 !== strpos($e->getMessage(), 'API error -32602: Item with key')) {
                throw $e;
            }
        }

        if (!$itemsId) {
            throw new \RuntimeException('Zabbix item creation failed');
        }
    }
}