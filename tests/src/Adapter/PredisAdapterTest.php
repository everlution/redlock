<?php

use Symfony\Component\Yaml\Yaml;
use Everlution\Redlock\Adapter\PredisAdapter;

/**
 * Integration test with Predis.
 */
class PredisAdapterTest extends AdapterTest
{
    public function newAdapter($valid = true)
    {
        $config = Yaml::parse(file_get_contents(CONFIG_YAML));

        if ($valid) {
            $config = $config['adapter']['predis']['valid_redis'];
        } else {
            $config = $config['adapter']['predis']['invalid_redis'];
        }

        $adapter = new PredisAdapter(
            new \Predis\Client(array(
                'host'    => $config['host'],
                'port'    => $config['port'],
                'timeout' => $config['timeout'],
                'async'   => $config['async'],
            ),
            array('profile' => '2.8')
        ));

        if ($valid) {
            foreach ($adapter->keys('*') as $key) {
                $adapter->del($key);
            }
        }

        return $adapter;
    }
}
