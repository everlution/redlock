<?php

use Symfony\Component\Yaml\Yaml;
use Everlution\Redlock\Adapter\PredisAdapter;

/**
 * Integration test with Predis.
 */
class PredisAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testIsConnected()
    {
        $this->assertTrue($this->newAdapter()->isConnected());

        $this->assertFalse($this->newAdapter(false)->isConnected());
    }

    public function testSet()
    {
        $adapter = $this->newAdapter();

        $this->assertTrue($adapter->set('resourceA', 'valueA'));
        $this->assertTrue($adapter->set('resourceB', 'valueB'));
        $this->assertTrue($adapter->set('resourceA', 'valueA'));

        $this->assertTrue($adapter->set('', ''));
        $this->assertTrue($adapter->set(null, ''));
        $this->assertTrue($adapter->set('', null));
        $this->assertTrue($adapter->set(null, null));
    }

    public function testDel()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resource1', 'value1');

        $this->assertTrue($adapter->del('resource1'));
        $this->assertFalse($adapter->del('resource2'));
        $this->assertFalse($adapter->del(''));
        $this->assertFalse($adapter->del(null));
    }

    public function testGet()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resourceA', 'valueA');
        $adapter->set('resourceB', 'valueB');
        $adapter->set('resourceA', 'valueA');
        $adapter->set('', '');
        $adapter->set(null, '');
        $adapter->set('', null);
        $adapter->set(null, null);

        $this->assertEquals('valueA', $adapter->get('resourceA'));
        $this->assertEquals('valueB', $adapter->get('resourceB'));
        $this->assertEquals('', $adapter->get(''));
        $this->assertEquals(null, $adapter->get(null));
    }

    public function testSetTTL()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resourceA', 'valueA');

        $adapter->setTTL('resourceA', 3600);

        // key does not exist
        $this->assertFalse($adapter->setTTL('resourceB', null));
    }

    /**
     * @expectedException Everlution\Redlock\Exception\Adapter\InvalidTtlException
     */
    public function testSetTTLException1()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resourceA', 'valueA');
        $adapter->setTTL('resourceA', 3600);

        $adapter->setTTL('resourceA', null);
    }

    /**
     * @expectedException Everlution\Redlock\Exception\Adapter\InvalidTtlException
     */
    public function testSetTTLException2()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resourceA', 'valueA');
        $adapter->setTTL('resourceA', 'one');
    }

    /**
     * @expectedException Everlution\Redlock\Exception\Adapter\InvalidTtlException
     */
    public function testSetTTLException3()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resourceA', 'valueA');
        $adapter->setTTL('resourceA', '1a');
    }

    public function testGetTTL()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resourceA', 'valueA');
        $adapter->set('resourceB', 'valueB');
        $adapter->set('resourceC', 'valueC');

        $this->assertEquals(-1, $adapter->getTTL('resourceA'));

        $adapter->setTTL('resourceA', 3600);
        $this->assertEquals(3600, $adapter->getTTL('resourceA'));

        $adapter->setTTL('resourceA', -1);
        $this->assertEquals(-1, $adapter->getTTL('resourceA'));
    }

    public function testExists()
    {
        $adapter = $this->newAdapter();

        $adapter->set('resource1', 'value1');

        $this->assertTrue($adapter->exists('resource1'));
        $this->assertFalse($adapter->exists('resource2'));

        $this->assertFalse($adapter->exists(''));
        $adapter->set('', '');
        $this->assertTrue($adapter->exists(''));

        $adapter->del('');
        $this->assertFalse($adapter->exists(''));
        $adapter->set(null, '');
        $this->assertTrue($adapter->exists(''));
        $this->assertTrue($adapter->exists(null));
    }

    public function testKeys()
    {
        $adapter = $this->newAdapter();

        for ($i = 0; $i < 50; $i++) {
            $adapter->set('resource'.$i, 'value'.$i);
        }

        $this->assertCount(1, $adapter->keys('resource1'));

        $this->assertCount(0, $adapter->keys('resource'));

        $this->assertCount(50, $adapter->keys('resource*'));

        $this->assertCount(11, $adapter->keys('resource1*'));
    }

    private function newAdapter($valid = true)
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
            )
        ));

        if ($valid) {
            foreach ($adapter->keys('*') as $key) {
                $adapter->del($key);
            }
        }

        return $adapter;
    }
}
