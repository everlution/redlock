<?php

use Everlution\Redlock\Manager\LockManager;
use Everlution\Redlock\Manager\LockTypeManager;
use Everlution\Redlock\Quorum\HalfPlusOneQuorum;
use Everlution\Redlock\KeyGenerator\DefaultKeyGenerator;
use Everlution\Redlock\Adapter\PredisAdapter;

$manager = new LockManager(
    new HalfPlusOneQuorum(),
    new DefaultKeyGenerator(),
    new LockTypeManager(),
    60, // 60 secs default lock validity time
    3,  // retries
    10  // max delay before retry
);

$manager
    ->addAdapter(
        new PredisAdapter(
            new \Predis\Client(array(
                'host'      => '127.0.0.1',
                'port'      => 6379,
                'timeout'   => 0,
                'async'     => false,
            ))
        )
    )
    ->addAdapter(
        new PredisAdapter(
            new \Predis\Client(array(
                'host'      => '127.0.0.1',
                'port'      => 6380,
                'timeout'   => 0,
                'async'     => false,
            ))
        )
    )
    ->addAdapter(
        new PredisAdapter(
            new \Predis\Client(array(
                'host'      => '127.0.0.1',
                'port'      => 6381,
                'timeout'   => 0,
                'async'     => false,
            ))
        )
    )
;

$resourceName = 'printer';

$nlLock = new Lock($resourceName, LockType::NULL, $token1);
$cwLock = new Lock($resourceName, LockType::CONCURRENT_WRITE, $token1);
$exLock = new Lock($resourceName, LockType::EXCLUSIVE, $token2);
$crLock = new Lock($resourceName, LockType::CONCURRENT_READ, $token1);

// current locks: []

$manager->acquireLock($nlLock); // true

// current locks: [NL]

$manager->acquireLock($cwLock); // true

// current locks: [NL, CW]

$manager->acquireLock($exLock); // false

// current locks: [NL, CW]

$manager->acquireLock($crLock); // true

// current locks: [NL, CW, CR]

$manager->releaseLock($cwLock);

// current locks: [NL, CR]

$manager->acquireLock($exLock); // false

// current locks: [NL, CR]

$manager->releaseLock($crLock);

// current locks: [NL]

$manager->acquireLock($exLock); // true

// current locks: [NL, EX]

$manager->releaseLock($nlLock);

// current locks: [EX]
