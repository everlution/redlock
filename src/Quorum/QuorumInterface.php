<?php

namespace Everlution\Redlock\Quorum;

interface QuorumInterface
{
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
