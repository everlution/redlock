<?php

use Symfony\Component\Yaml\Yaml;
use Everlution\Redlock\Adapter\PredisAdapter;

/**
 * Integration test with Predis.
 */
class PredisAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Everlution\Redlock\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var \Everlution\Redlock\Adapter\AdapterInterface
     */
    protected $invalidAdapter;

    public function setUp()
    {
        parent::setUp();
        $config = Yaml::parse(file_get_contents(CONFIG_YAML));
        $this->adapter = new PredisAdapter(
            new \Predis\Client(array(
                'host'    => $config['adapter']['predis']['valid_redis']['host'],
                'port'    => $config['adapter']['predis']['valid_redis']['port'],
                'timeout' => $config['adapter']['predis']['valid_redis']['timeout'],
                'async'   => $config['adapter']['predis']['valid_redis']['async'],
            )
        ));

        $this->invalidAdapter = new PredisAdapter(
            new \Predis\Client(array(
                'host'    => $config['adapter']['predis']['invalid_redis']['host'],
                'port'    => $config['adapter']['predis']['invalid_redis']['port'],
                'timeout' => $config['adapter']['predis']['invalid_redis']['timeout'],
                'async'   => $config['adapter']['predis']['invalid_redis']['async'],
            ))
        );
    }

    public function clearAll()
    {
        foreach ($this->adapter->keys('*') as $key) {
            $this->adapter->del($key);
        }
    }

    public function testAdapter()
    {
        $this->clearAll();

        $keys = $this
            ->adapter
            ->keys('*')
        ;
        $this->assertInternalType('array', $keys);
        $this->assertCount(0, $keys);

        $result = $this
            ->adapter
            ->set('resourceA', 'valueA')
        ;

        $keys = $this
            ->adapter
            ->keys('*')
        ;

        $resourceA = $this
            ->adapter
            ->get('resourceA')
        ;

        $this->assertEquals('valueA', $resourceA);

        $keys = $this
            ->adapter
            ->keys('*')
        ;
        $this->assertInternalType('array', $keys);
        $this->assertCount(1, $keys);
        $this->assertContains('resourceA', $keys);
    }

    public function testInvalidServer()
    {
        $this->assertFalse($this->invalidAdapter->isConnected());
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

        for ($i = 0; $i < 50; $i++) {
            $this
                ->adapter
                ->set('resource'.$i, 'value'.$i)
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

    public function testDel()
    {
        $this->clearAll();

        $this
            ->adapter
            ->set('resource1', 'value1')
        ;

        $this->assertTrue($this->adapter->del('resource1'));
        $this->assertFalse($this->adapter->del('resource2'));
    }

    public function testExists()
    {
        $this->clearAll();

        $this
            ->adapter
            ->set('resource1', 'value1')
        ;

        $this->assertTrue($this->adapter->exists('resource1'));
        $this->assertFalse($this->adapter->exists('resource2'));
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->clearAll();
    }
}
