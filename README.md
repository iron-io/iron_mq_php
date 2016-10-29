IronMQ v4 PHP Client Library
-------------

[IronMQ](https://www.iron.io/platform/ironmq/) is an elastic message queue for managing data and event flow within cloud applications and between systems.

This library uses IronMQ API v3.

## Branches

**If you're using laravel and see `"Class IronMQ not found"` error set `iron_mq` version to `1.*` and install/update dependencies**

* `1.*` - Laravel 4.0/4.1/4.2/5.0 compatible, PHP 5.2 compatible version. No namespaces. Using IronMQv2 servers (deprecated).
* `2.*` - Laravel 5.1/5.2 compatible, PSR-4 compatible version. With namespaces. Using IronMQv2 servers (deprecated).
* `3.*` - Laravel 4.0/4.1/4.2/5.0 compatible, PHP 5.2 compatible version. IronMQv3.
* `4.*` - (recommended) Laravel 5.1/5.2 compatible, PSR-4 compatible version. With namespaces. IronMQv3. Current default.
* `master` branch - same as `4.*`

## Update notes

* 1.3.0 - changed argument list in methods `postMessage` and `postMessages`. Please revise code that uses these methods.
* 1.4.5 - added `getMessagePushStatuses` and `deleteMessagePushStatus` methods.
* 2.0.0 - version 2.0 introduced some backward incompatible changes. IronMQ client finally PSR-4 compatible and using namespaces & other php 5.3 stuff. If you're migrating from previous (1.x) version, please carefully check how iron_mq / iron_core classes loaded.
If you need some 1.x features like `.phar` archives, use latest 1.x stable version: https://github.com/iron-io/iron_mq_php/releases/tag/1.5.3



## Getting Started

### Get credentials

To start using iron_mq_php, you need to sign up and get an oauth token.

1. Go to http://iron.io/ and sign up.
2. Get an Oauth Token at http://hud.iron.io/tokens

--

### Install iron_mq_php

There are two ways to use iron_mq_php:

##### Using composer

Create `composer.json` file in project directory:

```json
{
    "require": {
        "iron-io/iron_mq": "2.*"
    }
}
```

Do `composer install` (install it if needed: https://getcomposer.org/download/)

And use it:

```php
require __DIR__ . '/vendor/autoload.php';

$ironmq = new \IronMQ\IronMQ();
```


##### Using classes directly (strongly not recommended)

1. Copy classes from `src` to target directory
2. Grab IronCore classes [there](https://github.com/iron-io/iron_core_php) and copy to target directory
3. Include them all.

```php
require 'src/HttpException.php';
require 'src/IronCore.php';
require 'src/IronMQ.php';
require 'src/IronMQException.php';
require 'src/IronMQMessage.php';
require 'src/JsonException.php';

$ironmq = new \IronMQ\IronMQ();
```

--

### Configure

Three ways to configure IronMQ:

* Passing array with options:

```php
<?php
$ironmq = new \IronMQ\IronMQ(array(
    "token" => 'XXXXXXXXX',
    "project_id" => 'XXXXXXXXX'
));
```
* Passing ini file name which stores your configuration options. Rename sample_config.ini to config.ini and include your Iron.io credentials (`token` and `project_id`):

```php
<?php
$ironmq = new \IronMQ\IronMQ('config.json');
```

* Automatic [config](http://dev.iron.io/mq/3/reference/configuration/) search -
pass zero arguments to constructor and library will try to find config file in following locations:

    * `iron.ini` in current directory
    * `iron.json` in current directory
    * `IRON_MQ_TOKEN`, `IRON_MQ_PROJECT_ID` and other environment variables
    * `IRON_TOKEN`, `IRON_PROJECT_ID` and other environment variables
    * `.iron.ini` in user's home directory
    * `.iron.json` in user's home directory

--

### Keystone Authentication

#### Via Configuration File

Add `keystone` section to your iron.json file:

```javascript
{
  "project_id": "57a7b7b35e8e331d45000001",
  "keystone": {
    "server": "http://your.keystone.host/v2.0/",
    "tenant": "some-group",
    "username": "name",
    "password": "password"
  }
}
```

#### In Code

```php
$keystone = array(
    "server" => "http://your.keystone.host/v2.0/",
    "tenant" => "some-gorup",
    "username" => "name",
    "password" => "password"
);
$ironmq = new \IronMQ\IronMQ(array(
    "project_id" => '57a7b7b35e8e331d45000001',
    "keystone" => $keystone
));
```

## The Basics

### Post a Message to the Queue

```php
<?php
$ironmq->postMessage($queue_name, "Hello world");
```

More complex example:

```php
<?php
$ironmq->postMessage($queue_name, "Test Message", array(
    "timeout" => 120, # Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
    "delay" => 5, # The item will not be available on the queue until this many seconds have passed. Defaults to 0.
    "expires_in" => 2*24*3600 # How long, in seconds, to keep the item on the queue before it is deleted.
));
```

Post multiple messages in one API call:

```php
<?php
$ironmq->postMessages($queue_name, array("Message 1", "Message 2"), array(
    "timeout" => 120
));
```

--

### Reserve a Message

```php
<?php
$ironmq->reserveMessage($queue_name);
```

When you pop/get a message from the queue, it will NOT be deleted.
It will eventually go back onto the queue after a timeout if you don't delete it (default timeout is 60 seconds).

Reserve multiple messages in one API call:

```php
<?php
$ironmq->reserveMessages($queue_name, 3);
```

Reservation Id is needed for operations like delete, touch or release a message. It could be obtained from
message model after reserving it:

```php
<?php
$message = $ironmq->reserveMessage($queue_name);
$reservation_id = $message->reservation_id;
```

--

### Delete a Message from the Queue

```php
<?php
$ironmq->deleteMessage($queue_name, $message_id, $reservation_id);
```

If message isn't reserved, you don't need to provide reservation id

```php
<?php
$ironmq->deleteMessage($queue_name, $message_id);
```
Delete a message from the queue when you're done with it.

Delete multiple messages in one API call:

```php
<?php
$ironmq->deleteMessages($queue_name, array("xxxxxxxxx", "xxxxxxxxx"));
```
Delete multiple messages specified by messages id array.

It's also possible to delete array of message objects:

```php
<?php
$messages = $ironmq->reserveMessages($queue_name, 3);
$ironmq->deleteMessage($queue_name, $messages);
```
--


## Troubleshooting

### http error: 0

If you see  `Uncaught exception 'Http_Exception' with message 'http error: 0 | '`
it most likely caused by misconfigured cURL https sertificates.
There are two ways to fix this error:

1. Disable SSL sertificate verification - add this line after IronMQ initialization: `$ironmq->ssl_verifypeer = false;`
2. Switch to http protocol - add this to configuration options: `protocol = http` and `port = 80`
3. Fix the error! Recommended solution: download actual certificates - [cacert.pem](http://curl.haxx.se/docs/caextract.html) and add them to `php.ini`:

```
[PHP]

curl.cainfo = "path\to\cacert.pem"
```


--

### Updating notes

* 1.3.0 - changed argument list in methods `postMessage` and `postMessages`. Please revise code that uses these methods.
* 1.4.5 - added `getMessagePushStatuses` and `deleteMessagePushStatus` methods.

--


## Queues

### IronMQ Client

`IronMQ` is based on `IronCore` and provides easy access to the whole IronMQ API.

```php
<?php
$ironmq = new \IronMQ\IronMQ(array(
    "token" => 'XXXXXXXXX',
    "project_id" => 'XXXXXXXXX'
));
```

--

### List Queues

This code will return first 30 queues sorted by name.

```php
<?php
$queues = $ironmq->getQueues();
```

**Optional parameters:**

* `per_page`: number of elements in response, default is 30.
* `previous`: this is the last queue on the previous page, it will start from the next one. If queue with specified name doesn’t exist result will contain first per_page queues that lexicographically greater than previous

Assume you have queues named "a", "b", "c", "d", "e". The following code will list "c", "d" and
"e" queues:

```php
<?php
$queues = $ironmq->getQueues('b', 3);
```

--

### Retrieve Queue Information

```php
<?php
$qinfo = $ironmq->getQueue($queue_name);
```

--

### Delete a Message Queue

```php
<?php
$response = $ironmq->deleteQueue($queue_name);
```

--

### Post Messages to a Queue

**Single message:**

```php
<?php
$ironmq->postMessage($queue_name, "Test Message", array(
    'delay' => 2,
    'expires_in' => 2*24*3600 # 2 days
));
```

**Multiple messages:**

```php
<?php
$ironmq->postMessages($queue_name, array("Lorem", "Ipsum"), array(
    "delay" => 2,
    "expires_in" => 2*24*3600 # 2 days
));
```

**Optional parameters (3rd, `array` of key-value pairs):**

* `delay`: The item will not be available on the queue until this many seconds have passed.
Default is 0 seconds. Maximum is 604,800 seconds (7 days).

* `expires_in`: How long in seconds to keep the item on the queue before it is deleted.
Default is 604,800 seconds (7 days). Maximum is 2,592,000 seconds (30 days).

* ~~`timeout`~~: **Deprecated**. Can no longer set timeout when posting a message, only when reserving one.

--

### Get Messages from a Queue

**Single message:**

```php
<?php
$message = $ironmq->reserveMessage($queue_name, $timeout);
```

**Multiple messages:**

```php
<?php
$message = $ironmq->reserveMessages($queue_name, $count, $timeout, $wait);
```

**Optional parameters:**

* `$count`: The maximum number of messages to get. Default is 1. Maximum is 100.

* `$timeout`: After timeout (in seconds), item will be placed back onto queue.
You must delete the message from the queue to ensure it does not go back onto the queue.
If not set, value from POST is used. Default is 60 seconds. Minimum is 30 seconds.
Maximum is 86,400 seconds (24 hours).

* `$wait`: Time to long poll for messages, in seconds. Max is 30 seconds. Default 0.


--

### Touch a Message on a Queue

Touching a reserved message returns new reservation with specified or default timeout.

```php
<?php
$ironmq->touchMessage($queue_name, $message_id, $reservation_id, $timeout);
```

--

### Release Message

```php
<?php
$ironmq->releaseMessage($queue_name, $message_id, $reservation_id, $delay);
```

**Parameters:**

* `$delay`: The item will not be available on the queue until this many seconds have passed.
Default is 0 seconds. Maximum is 604,800 seconds (7 days).

--

### Delete a Message from a Queue

```php
<?php
$ironmq->deleteMessage($queue_name, $message_id, $reservation_id);
```

--

### Peek Messages from a Queue

Peeking at a queue returns the next messages on the queue, but it does not reserve them.

**Single message:**

```php
<?php
$message = $ironmq->peekMessage($queue_name);
```

**Multiple messages:**

```php
<?php
$messages = $ironmq->peekMessages($queue_name, $count);
```

--

### Clear a Queue

```php
<?php
$ironmq->clearQueue($queue_name);
```

--

### Add alerts to a queue. This is for Pull Queue only.

```php
<?php
$first_alert = array(
        'type' => 'fixed',
        'direction' => 'desc',
        'trigger' => 1001,
        'snooze' => 10,
        'queue' => 'test_alert_queue');
$second_alert = array(
        'type' => 'fixed',
        'direction' => 'asc',
        'trigger' => 1000,
        'snooze' => 5,
        'queue' => 'test_alert_queue',);

$res = $ironmq->addAlerts("test_alert_queue", array($first_alert, $second_alert));
```

### Replace current queue alerts with a given list of alerts. This is for Pull Queue only.

```php
<?php
$res = $ironmq->updateAlerts("test_alert_queue", array($first_alert, $second_alert));
```

### Remove alerts from a queue. This is for Pull Queue only.

```php
<?php
$ironmq->deleteAlerts("test_alert_queue");
```

--


## Push Queues

IronMQ push queues allow you to setup a queue that will push to an endpoint, rather than having to poll the endpoint.
[Here's the announcement for an overview](http://blog.iron.io/2013/01/ironmq-push-queues-reliable-message.html).

### Create a Queue

```php
<?php
$params = array(
    "message_timeout" => 120,
    "message_expiration" => 24 * 3600,
    "push" => array(
        "subscribers" => array(
            array("url" => "http://your.first.cool.endpoint.com/push", "name" => "first"),
            array("url" => "http://your.second.cool.endpoint.com/push", "name" => "second")
        ),
        "retries" => 4,
        "retries_delay" => 30,
        "error_queue" => "error_queue_name"
    )
);

$ironmq->createQueue($queue_name, $params);
```


**Options:**

* `type`: String or symbol. Queue type. `:pull`, `:multicast`, `:unicast`. Field required and static.
* `message_timeout`: Integer. Number of seconds before message back to queue if it will not be deleted or touched.
* `message_expiration`: Integer. Number of seconds between message post to queue and before message will be expired.

**The following parameters are all related to Push Queues:**

* `push: subscribers`: An array of subscriber hashes containing a `name` and a `url` required fields,
and optional `headers` hash. `headers`'s keys are names and values are means of HTTP headers.
This set of subscribers will replace the existing subscribers.
To add or remove subscribers, see the add subscribers endpoint or the remove subscribers endpoint.
See below for example json.
* `push: retries`: How many times to retry on failure. Default is 3. Maximum is 100.
* `push: retries_delay`: Delay between each retry in seconds. Default is 60.
* `push: error_queue`: String. Queue name to post push errors to.

--

### Update Queue Information

Same as create queue. A push queue couldn't be changed into a pull queue, so vice versa too.

### Add/Remove Subscribers on a Queue

Add subscribers to Push Queue:

```php
<?php

$ironmq->addSubscriber($queue_name, array(
       "url" => "http://cool.remote.endpoint.com/push",
       "name" => "subscriber_name",
       "headers" => array(
          "Content-Type" => "application/json"
       )
   )
);

$ironmq->addSubscribers($queue_name, array(
        array(
            "url" => "http://first.remote.endpoint.com/push",
            "name" => "first"),
        array(
            "url" => "http://second.remote.endpoint.com/push",
            "name" => "second")
    )
);
```

--

### Replace Subscribers on a Queue

Sets list of subscribers to a queue. Older subscribers will be removed.

```php
<?php

$ironmq->replaceSubscriber($queue_name, array(
       "url" => "http://cool.remote.endpoint.com/push",
       "name" => "subscriber_name"
   )
);

$ironmq->addSubscribers($queue_name, array(
        array(
            "url" => "http://first.remote.endpoint.com/push",
            "name" => "first"),
        array(
            "url" => "http://second.remote.endpoint.com/push",
            "name" => "second")
    )
);
```

### Remove Subscribers from a Queue

Remove subscriber from a queue. This is for Push Queues only.

```php
<?php

$ironmq->removeSubscriber($queue_name, array(
       "name" => "subscriber_name"
   )
);

$ironmq->removeSubscribers($queue_name, array(
        array("name" => "first"),
        array("name" => "second")
    )
);
```

### Get Message Push Status

```php
<?php
$response = $ironmq->postMessage('push me!');

$message_id = $response["ids"][0];
$statuses = $ironmq->getMessagePushStatuses($queue_name, $message_id);
```

Returns an array of subscribers with status.

--

### Acknowledge, That Push Message Is Processed

This method could be used to acknowledgement process of push messages.
See [IronMQ v3 documentation](http://dev.iron.io/mq/3/reference/push_queues/#long_running_processes__aka_202s)
on long-processing for further information.

```php
<?php
$ironmq->deletePushMessage($queue_name, $message_id, $reservation_id, $subscriber_name);
```

--


## Further Links

* [IronMQ Overview](http://dev.iron.io/mq/3/)
* [IronMQ REST/HTTP API](http://dev.iron.io/mq/3/reference/api/)
* [Push Queues](http://dev.iron.io/mq/3/reference/push_queues/)
* [Other Client Libraries](http://dev.iron.io/mq/3/libraries/)
* [Live Chat, Support & Fun](http://get.iron.io/chat)

-------------
© 2011 - 2013 Iron.io Inc. All Rights Reserved.
