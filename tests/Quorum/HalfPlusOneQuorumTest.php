<?php

use Everlution\Redlock\Quorum\HalfPlusOneQuorum;

class HalfPlusOneQuorumTest extends \PHPUnit_Framework_TestCase
{
    public function testQuorum()
    {
        $cases = array(
            array(
                'total'  => 0,
                'quorum' => 0,
            ),
            array(
                'total'  => 1,
                'quorum' => 1,
            ),
            array(
                'total'  => 2,
                'quorum' => 2,
            ),
            array(
                'total'  => 3,
                'quorum' => 2,
            ),
            array(
                'total'  => 4,
                'quorum' => 3,
            ),
            array(
                'total'  => 5,
                'quorum' => 3,
            ),
            array(
                'total'  => 6,
                'quorum' => 4,
            ),
            array(
                'total'  => 7,
                'quorum' => 4,
            ),
            array(
                'total'  => 8,
                'quorum' => 5,
            ),
            array(
                'total'  => 9,
                'quorum' => 5,
            ),
            array(
                'total'  => 10,
                'quorum' => 6,
            ),
            array(
                'total'  => 50,
                'quorum' => 26,
            ),
        );

        foreach ($cases as $case) {
            $quorum = new HalfPlusOneQuorum();
            $quorum->setTotal($case['total']);

            $this->assertEquals($case['total'], $quorum->getTotal());
            $this->assertEquals($case['quorum'], $quorum->getQuorum());
        }
    }
}
