<?php

use Everlution\Redlock\Adapter\PredisAdapter;

/**
 * Integration test with Predis
 */
class PredisAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Everlution\Redlock\Adapter\AdapterInterface
     */
    protected $adapter;

    const PREFIX = 'test:';

    public function setUp()
    {
        parent::setUp();
        $predis = new \Predis\Client('tcp://127.0.0.1:6379', array('prefix'=> self::PREFIX));
        $this->adapter = new PredisAdapter($predis);
    }

    /**
     * translateKey.
     *
     * Predis does not strip off the prefix
     *
     * @param string $key
     * @return string
     */
    private function translateKey($key)
    {
        return str_replace(self::PREFIX, '', $key);
    }

    public function clearAll()
    {
        foreach ($this->adapter->keys() as $key) {
            $this->adapter->del(
                $this->translateKey($key)
            );
        }
    }

    public function testAdapter()
    {
        $this->clearAll();

        $keys = $this
            ->adapter
            ->keys()
        ;
        $this->assertInternalType('array', $keys);
        $this->assertCount(0, $keys);

        $this
            ->adapter
            ->set('resourceA', 'valueA')
        ;

        $resourceA = $this
            ->adapter
            ->get('resourceA')
        ;

        $this->assertEquals('valueA', $resourceA);

        $keys = $this
            ->adapter
            ->keys()
        ;
        $this->assertInternalType('array', $keys);
        $this->assertCount(1, $keys);
        $this->assertContains(self::PREFIX . 'resourceA', $keys);
    }

    public function testTTL()
    {
        $this->clearAll();

        $this
            ->adapter
            ->set('resourceA', 'valueA')
        ;

        $ttlTest = $this
            ->adapter
            ->getTTL('resourceA')
        ;
        $this->assertEquals(-1, $ttlTest);

        $this
            ->adapter
            ->setTTL('resourceA', 3600)
        ;
        $ttlTest = $this
            ->adapter
            ->getTTL('resourceA')
        ;
        $this->assertEquals(3600, $ttlTest);
    }

    public function testKeys()
    {
        $this->clearAll();

        for ($i=0; $i<50; $i++) {
            $this
                ->adapter
                ->set('resource' . $i, 'value' . $i)
            ;
        }

        $this->assertCount(
            1,
            $this->adapter->keys('resource1')
        );

        $this->assertCount(
            0,
            $this->adapter->keys('resource')
        );

        $this->assertCount(
            50,
            $this->adapter->keys('resource*')
        );

        $this->assertCount(
            11,
            $this->adapter->keys('resource1*')
        );
    }
}
