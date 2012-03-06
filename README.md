PHP language binding for IronMQ

[IronMQ](http://www.iron.io/products/mq) is an elastic message queue for managing data and event flow within cloud applications and between systems.

[See How It Works](http://www.iron.io/products/mq/how)

# Getting Started

## Get credentials

To start using iron_mq_php, you need to sign up and get an oauth token.

1. Go to http://iron.io/ and sign up.
2. Get an Oauth Token at http://hud.iron.io/tokens

## Install iron_mq_php
Just copy `IronMQ.class.php` and include it in your script:

```php
<?php
require_once "IronMQ.class.php"
```
## Configure
Two ways to configure IronWorker:

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

## The Basics

### **Push** a message on the queue:

```php
<?php
$ironmq->postMessage("test_queue", "Hello world");
```

More complex example:

```php
<?php
$ironmq->postMessage("test_queue", array(
    "body" => "Test Message",
    "timeout" => 120, # Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
    'delay' => 5, # The item will not be available on the queue until this many seconds have passed. Defaults to 0.
    'expires_in' => 2*24*3600 # How long, in seconds, to keep the item on the queue before it is deleted.
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


# Full Documentation

You can find more documentation here:

* http://iron.io
* http://dev.iron.io
