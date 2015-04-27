# Redlock

**WARNING** this package is still on development.

An implementation of the [Redlock] [1] algorithm.

It is worth it to take a look at the wikipedia page for [distributed locking] [2].

## Requirements

* PHP >= 5.4
* Redis

## Adapters

This library is provided with two different adapters:

* [Predis] [3]
* [PHP Redis extension] [4]

## Contributors

* [Michael Caldera] [5]


[1]: http://redis.io/topics/distlock
[2]: http://en.wikipedia.org/wiki/Distributed_lock_manager
[3]: https://github.com/nrk/predis
[4]: https://github.com/phpredis/phpredis
[5]: https://github.com/michcald
