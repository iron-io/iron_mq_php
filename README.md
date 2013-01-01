PHP language binding for IronMQ

[IronMQ](http://www.iron.io/products/mq) is an elastic message queue for managing data and event flow within cloud applications and between systems.

[See How It Works](http://www.iron.io/products/mq/how)

# Getting Started

## Get credentials

To start using iron_mq_php, you need to sign up and get an oauth token.

1. Go to http://iron.io/ and sign up.
2. Get an Oauth Token at http://hud.iron.io/tokens

## Install iron_mq_php

There are two ways to use iron_mq_php:

#### Using precompiled phar archive:

Copy `iron_mq.phar` to target directory and include it:

```php
<?php
require_once "phar://iron_mq.phar";
```

Please note, [phar](http://php.net/manual/en/book.phar.php) extension available by default only from php 5.3.0
For php 5.2 you should install phar manually or use second option.

#### Using classes directly

1. Copy `IronMQ.class.php` to target directory
2. Grab `IronCore.class.php` [there](https://github.com/iron-io/iron_core_php) and copy to target directory
3. Include both of them:

```php
<?php
require_once "IronCore.class.php"
require_once "IronMQ.class.php"
```

## Configure
Three ways to configure IronMQ:

* Passing array with options:

```php
<?php
$ironmq = new IronMQ(array(
    'token' => 'XXXXXXXXX',
    'project_id' => 'XXXXXXXXX'
));
```
* Passing ini file name which stores your configuration options. Rename sample_config.ini to config.ini and include your Iron.io credentials (`token` and `project_id`):

```php
<?php
$ironmq = new IronMQ('config.ini');
```

* Automatic config search - pass zero arguments to constructor and library will try to find config file in following locations:

    * `iron.ini` in current directory
    * `iron.json` in current directory
    * `IRON_MQ_TOKEN`, `IRON_MQ_PROJECT_ID` and other environment variables
    * `IRON_TOKEN`, `IRON_PROJECT_ID` and other environment variables
    * `.iron.ini` in user's home directory
    * `.iron.json` in user's home directory


## The Basics

### **Push** a message on the queue:

```php
<?php
$ironmq->postMessage("test_queue", "Hello world");
```

More complex example:

```php
<?php
$ironmq->postMessage("test_queue", "Test Message", array(
    'timeout' => 120, # Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
    'delay' => 5, # The item will not be available on the queue until this many seconds have passed. Defaults to 0.
    'expires_in' => 2*24*3600 # How long, in seconds, to keep the item on the queue before it is deleted.
));
```

Post multiple messages in one API call:

```php
<?php
$ironmq->postMessages("test_queue", array("Message 1", "Message 2"), array(
    'timeout' => 120
));
```

### **Pop** a message off the queue:
```php
<?php
$ironmq->getMessage("test_queue");
```
When you pop/get a message from the queue, it will NOT be deleted.
It will eventually go back onto the queue after a timeout if you don't delete it (default timeout is 60 seconds).
### **Delete** a message from the queue:
```php
<?php
$ironmq->deleteMessage("test_queue", $message_id);
```
Delete a message from the queue when you're done with it.


# Troubleshooting

### http error: 0

If you see  `Uncaught exception 'Http_Exception' with message 'http error: 0 | '`
it most likely caused by misconfigured cURL https sertificates.
There are two ways to fix this error:

1. Disable SSL sertificate verification - add this line after IronMQ initialization: `$ironmq->ssl_verifypeer = false;`
2. Switch to http protocol - add this to configuration options: `protocol = http` and `port = 80`

# Updating notes

* 1.3.0 - changed argument list in methods `postMessage` and `postMessages`. Please revise code that uses these methods.



# Full Documentation

You can find more documentation here:

* http://iron.io
* http://dev.iron.io
