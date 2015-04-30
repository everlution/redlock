<?php

namespace Everlution\Redlock\Quorum;

interface QuorumInterface
{
    /**
     * setTotal.
     *
     * The total number.
     *
     * @param type $total
     */
    public function setTotal($total);

    /**
     * getTotal.
     *
     * @return int
     */
    public function getTotal();

    /**
     * isApproved.
     *
     * @param int $count The total number of members
     *
     * @return bool
     */
    public function isApproved($count);

    /**
     * getQuorum.
     *
     * @return int The minimum number of members necessary for approval
     */
    public function getQuorum();
}
