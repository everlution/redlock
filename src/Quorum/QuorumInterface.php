<?php

namespace Everlution\Redlock\Quorum;

interface QuorumInterface
{
    /**
     * isApproved.
     *
     * @param integer $count The total number of members
     * @return boolean
     */
    public function isApproved($count);

    /**
     * getQuorum.
     *
     * @return integer The minimum number of members necessary for approval
     */
    public function getQuorum();
}
