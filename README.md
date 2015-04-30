# Redlock

A PHP implementation of the [Redlock] [1] algorithm.

## Requirements

* PHP >= 5.4
* Redis >= 2.8 as using the [SCAN] [6] command

# Installation (using composer)

You can find the library in packagist [here](https://packagist.org/packages/everlution/redlock).


```json
{
  "require": {
    "everlution/redlock": "dev-master"
  }
}
```

# Documentation

[Read the documentation for dev-master](https://github.com/everlution/redlock/wiki)

## Contributors

* [Michael Caldera] [5]


[1]: http://redis.io/topics/distlock
[2]: http://en.wikipedia.org/wiki/Distributed_lock_manager
[3]: https://github.com/nrk/predis
[4]: https://github.com/phpredis/phpredis
[5]: https://github.com/michcald
[6]: http://redis.io/commands/scan
