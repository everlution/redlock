<?php

use Everlution\Redlock\Model\Lock;
use Everlution\Redlock\KeyGenerator\DefaultKeyGenerator;

class DefaultKeyGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $generator = new DefaultKeyGenerator();

        $tests = array(
            array(
                'name'     => 'resourceA',
                'type'     => 'EX',
                'token'    => 'askdjbasdbasdd233d2dsds',
                'expected' => 'resourceA:EX:askdjbasdbasdd233d2dsds',
            ),
            array(
                'name'     => '',
                'type'     => 'EX',
                'token'    => 'askdjbasdbasdd233d2dsds',
                'expected' => ':EX:askdjbasdbasdd233d2dsds',
            ),
            array(
                'name'     => 'resourceA',
                'type'     => '',
                'token'    => 'askdjbasdbasdd233d2dsds',
                'expected' => 'resourceA::askdjbasdbasdd233d2dsds',
            ),
            array(
                'name'     => 'resourceA',
                'type'     => 'EX',
                'token'    => '',
                'expected' => 'resourceA:EX:',
            ),
            array(
                'name'     => '',
                'type'     => '',
                'token'    => '',
                'expected' => '::',
            ),
        );

        foreach ($tests as $test) {
            $lock = new Lock();
            $lock
                ->setResourceName($test['name'])
                ->setType($test['type'])
                ->setToken($test['token'])
            ;

            $this->assertEquals($test['expected'], $generator->generate($lock));
        }
    }
}
