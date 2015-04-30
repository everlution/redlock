<?php

use Everlution\Redlock\Quorum\HalfPlusOneQuorum;

class HalfPlusOneQuorumTest extends \PHPUnit_Framework_TestCase
{
    public function testIsApproved()
    {
        $quorum = new HalfPlusOneQuorum();

        $cases = array(
            array(
                'total'    => 0,
                'count'    => 0,
                'approved' => true,
            ),
            array(
                'total'    => 1,
                'count'    => 1,
                'approved' => true,
            ),
            array(
                'total'    => 2,
                'count'    => 2,
                'approved' => true,
            ),
            array(
                'total'    => 3,
                'count'    => 2,
                'approved' => true,
            ),
            array(
                'total'    => 4,
                'count'    => 3,
                'approved' => true,
            ),
            array(
                'total'    => 5,
                'count'    => 3,
                'approved' => true,
            ),
            array(
                'total'    => 6,
                'count'    => 4,
                'approved' => true,
            ),
            array(
                'total'    => 7,
                'count'    => 4,
                'approved' => true,
            ),
            array(
                'total'    => 8,
                'count'    => 5,
                'approved' => true,
            ),
            array(
                'total'    => 9,
                'count'    => 5,
                'approved' => true,
            ),
            array(
                'total'    => 10,
                'count'    => 6,
                'approved' => true,
            ),
            array(
                'total'    => 50,
                'count'    => 26,
                'approved' => true,
            ),
            array(
                'total'    => 1,
                'count'    => 0,
                'approved' => false,
            ),
            array(
                'total'    => 3,
                'count'    => 1,
                'approved' => false,
            ),
            array(
                'total'    => 5,
                'count'    => 1,
                'approved' => false,
            ),
            array(
                'total'    => 5,
                'count'    => 2,
                'approved' => false,
            ),
        );

        foreach ($cases as $case) {
            $quorum->setTotal($case['total']);

            if ($case['approved']) {
                $this->assertTrue($quorum->isApproved($case['count']));
            } else {
                $this->assertFalse($quorum->isApproved($case['count']));
            }
        }
    }

    public function testGetQuorum()
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
