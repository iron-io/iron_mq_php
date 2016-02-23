<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://dev.iron.io/
 * @license BSD License
 * @copyright Feel free to copy, steal, take credit for, or whatever you feel like doing with this code. ;)
 */

/**
 * For more information about push queues and subscribers, visit http://dev.iron.io/mq/reference/push_queues/
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IronMQ\IronMQ;

$ironMQ = new IronMQ();
//$ironMQ->debug_enabled = true;
$ironMQ->ssl_verifypeer = false;

// Generating a random queue so we don't collide with any existing queues. Feel free to use a proper name
$queueName = md5(rand() . time());
// We add a 'type' => 'multicast' to turn this into a push queue, and attach a single subscriber
$queueOptions = array(
    'message_expiration' => 3600,
    'type'               => 'multicast',
    'push'               => array(
        'subscribers' => array(
            array(
                'url' => 'http://domain0.com/endpoint', 'name' => 'Sub 0'),
        ),
    ),
);
$ironMQ->createQueue($queueName, $queueOptions);

// Add a single subscriber
$singleAddResponse = $ironMQ->addSubscriber($queueName, array('url' => 'http://domain.com/endpoint', 'name' => 'Sub 1'));
var_dump($singleAddResponse);
$subscribers = array(
    array('url' => 'http://domain2.com/endpoint', 'name' => 'Sub 4'),
    array('url' => 'http://domain3.com/endpoint', 'name' => 'Sub 3'),
);
$multiAddResponse = $ironMQ->addSubscribers($queueName, $subscribers);
var_dump($multiAddResponse);

// Replace the list of subscribers. You can also replace multiple with IronMQ::replaceSubscribers()
// THIS DOES NOT UPDATE, BUT REPLACE THE ENTIRE LIST. To update a URL, you will need to remove and re-add the subscriber
$updateSingleResponse = $ironMQ->replaceSubscriber($queueName, array('url' => 'http://newdomain.com/endpoint', 'name' => 'Sub 1'));

// Remove a subscriber
$removeResponse = $ironMQ->removeSubscriber($queueName, array('name' => 'Sub 3'));

// Clean up our tests
$ironMQ->deleteQueue($queueName);
