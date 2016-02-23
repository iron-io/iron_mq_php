<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://dev.iron.io/
 * @version 4.0.1
 * @package IronMQPHP
 * @copyright Feel free to copy, steal, take credit for, or whatever you feel like doing with this code. ;)
 */

namespace IronMQ;

use IronCore\IronCore;

class IronMQ extends IronCore
{

    protected $client_version = '4.0.1';
    protected $client_name    = 'iron_mq_php';
    protected $product_name   = 'iron_mq';
    protected $default_values = array(
        'protocol'    => 'https',
        'host'        => 'mq-aws-us-east-1-1.iron.io',
        'port'        => '443',
        'api_version' => '3',
    );

    const LIST_QUEUES_PER_PAGE = 30;
    const GET_MESSAGE_TIMEOUT  = 60;
    const GET_MESSAGE_WAIT     = 0;

    /**
     * @param string|array $config
     *        Array of options or name of config file.
     * Fields in options array or in config:
     *
     * Required:
     * - token
     * - project_id
     * Optional:
     * - protocol
     * - host
     * - port
     * - api_version
     */
    public function __construct($config = null)
    {
        $this->getConfigData($config);
        $this->url = "{$this->protocol}://{$this->host}:{$this->port}/{$this->api_version}/";
    }

    /**
     * Switch active project
     *
     * @param string $project_id Project ID
     * @throws \InvalidArgumentException
     */
    public function setProjectId($project_id)
    {
        if (!empty($project_id)) {
            $this->project_id = $project_id;
        }
        if (empty($this->project_id)) {
            throw new \InvalidArgumentException("Please set project_id");
        }
    }

    /**
     * Get list of message queues
     *
     * @param int $previous
     *        Zero-indexed page to view
     * @param int $per_page
     *        Number of queues per page
     */
    public function getQueues($previous = null, $per_page = self::LIST_QUEUES_PER_PAGE)
    {
        $url = "projects/{$this->project_id}/queues";
        $params = array();
        if (!is_null($previous)) {
            $params['previous'] = $previous;
        }
        if ($per_page !== self::LIST_QUEUES_PER_PAGE) {
            $params['per_page'] = (int) $per_page;
        }
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::GET, $url, $params))->queues;
    }

    /**
     * Get information about queue.
     * Also returns queue size.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function getQueue($queue_name)
    {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::GET, $url))->queue;
    }

    /**
     * Clear all messages from queue.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function clearQueue($queue_name)
    {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::DELETE, $url));
    }

    /**
     * Push a message on the queue
     *
     * Examples:
     * <code>
     * $ironmq->postMessage("test_queue", "Hello world");
     * </code>
     * <code>
     * $ironmq->postMessage("test_queue", "Test Message", array(
     *   'timeout' => 120,
     *   'delay' => 2,
     *   'expires_in' => 2*24*3600 # 2 days
     * ));
     * </code>
     *
     * @param string $queue_name Name of the queue.
     * @param string $message
     * @param array $properties
     * @return mixed
     */
    public function postMessage($queue_name, $message, $properties = array())
    {
        $msg = new IronMQMessage($message, $properties);
        $req = array(
            "messages" => array($msg->asArray()),
        );
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        $decoded = self::json_decode($res);
        $decoded->id = $decoded->ids[0];
        return $decoded;
    }

    /**
     * Push multiple messages on the queue
     *
     * Example:
     * <code>
     * $ironmq->postMessages("test_queue", array("Lorem", "Ipsum"), array(
     *   'timeout' => 120,
     *   'delay' => 2,
     *   'expires_in' => 2*24*3600 # 2 days
     * ));
     * </code>
     *
     * @param string $queue_name Name of the queue.
     * @param array $messages array of messages, each message same as for postMessage() method
     * @param array $properties array of message properties, applied to each message in $messages
     * @return mixed
     */
    public function postMessages($queue_name, $messages, $properties = array())
    {
        $req = array(
            "messages" => array(),
        );
        foreach ($messages as $message) {
            $msg = new IronMQMessage($message, $properties);
            array_push($req['messages'], $msg->asArray());
        }
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        return self::json_decode($res);
    }

    /**
     * Reserve multiplie messages from queue
     *
     * @param string $queue_name Queue name
     * @param int $count
     * @param int $timeout
     * @return array|null array of messages or null
     * @deprecated Use reserveMessages instead
     */
    public function getMessages($queue_name, $count = 1, $timeout = self::GET_MESSAGE_TIMEOUT, $wait = self::GET_MESSAGE_WAIT)
    {
        return $this->reserveMessages($queue_name, $count, $timeout, $wait);
    }

    /**
     * Reserve multiplie messages from queue
     *
     * @param string $queue_name Queue name
     * @param int $count
     * @param int $timeout
     * @return array|null array of messages or null
     */
    public function reserveMessages($queue_name, $count = 1, $timeout = self::GET_MESSAGE_TIMEOUT, $wait = self::GET_MESSAGE_WAIT)
    {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/reservations";
        $params = array();
        if ($count !== 1) {
            $params['n'] = (int) $count;
        }
        if ($timeout !== self::GET_MESSAGE_TIMEOUT) {
            $params['timeout'] = (int) $timeout;
        }
        if ($wait !== self::GET_MESSAGE_WAIT) {
            $params['wait'] = (int) $wait;
        }
        $this->setJsonHeaders();
        $response = $this->apiCall(self::POST, $url, $params);
        $result = self::json_decode($response);
        if (count($result->messages) < 1) {
            return null;
        } else {
            return $result->messages;
        }
    }

    /**
     * Reserve single message from queue
     *
     * @param string $queue_name Queue name
     * @param int $timeout
     * @return mixed|null single message or null
     * @deprecated Use reserveMessages instead
     */
    public function getMessage($queue_name, $timeout = self::GET_MESSAGE_TIMEOUT, $wait = self::GET_MESSAGE_WAIT)
    {
        return $this->reserveMessage($queue_name, $timeout, $wait);
    }

    /**
     * Reserve single message from queue
     *
     * @param string $queue_name Queue name
     * @param int $timeout
     * @return mixed|null single message or null
     */
    public function reserveMessage($queue_name, $timeout = self::GET_MESSAGE_TIMEOUT, $wait = self::GET_MESSAGE_WAIT)
    {
        $messages = $this->reserveMessages($queue_name, 1, $timeout, $wait);
        if ($messages) {
            return $messages[0];
        } else {
            return null;
        }
    }

    /**
     * Get the message with the given id.
     * @param string $queue_name Queue name
     * @param string $message_id Message ID
     * @return mixed
     */
    public function getMessageById($queue_name, $message_id)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}";
        return self::json_decode($this->apiCall(self::GET, $url))->message;
    }

    /**
     * Delete a Message from a Queue
     * This call will delete the message. Be sure you call this after you’re done with a message,
     * or it will be placed back on the queue.
     *
     * @param $queue_name
     * @param $message_id
     * @return mixed
     */
    public function deleteMessage($queue_name, $message_id, $reservation_id = null)
    {
        $req = array(
            "reservation_id" => $reservation_id,
        );
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}";
        if (is_null($reservation_id)) {
            return $this->apiCall(self::DELETE, $url);
        } else {
            return $this->apiCall(self::DELETE, $url, $req);
        }
    }

    /**
     * Delete Messages from a Queue
     * This call will delete the messages. Be sure you call this after you’re done with a message,
     * or it will be placed back on the queue.
     *
     * @param $queue_name
     * @param $messages
     * @return mixed
     */
    public function deleteMessages($queue_name, $messages)
    {
        $req = array(
            "ids" => array(),
        );
        foreach ($messages as $message) {
            if (is_string($message)) {
                array_push($req['ids'], $message);
            } else if (is_object($message)) {
                array_push($req['ids'], array('id' => $message->id, 'reservation_id' => $message->reservation_id));
            } else if (is_array($message)) {
                array_push($req['ids'], array('id' => $message['id'], 'reservation_id' => $message['reservation_id']));
            }
        }
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $result = $this->apiCall(self::DELETE, $url, $req);
        return self::json_decode($result);
    }

    /**
     * Peek Messages on a Queue
     * Peeking at a queue returns the next messages on the queue, but it does not reserve them.
     *
     * @param string $queue_name
     * @return object|null  message or null if queue is empty
     */
    public function peekMessage($queue_name)
    {
        $messages = $this->peekMessages($queue_name, 1);
        if ($messages == null) {
            return null;
        } else {
            return $messages[0];
        }
    }

    /**
     * Peek Messages on a Queue
     * Peeking at a queue returns the next messages on the queue, but it does not reserve them.
     *
     * @param string $queue_name
     * @param int $count The maximum number of messages to peek. Maximum is 100.
     * @return array|null array of messages or null if queue is empty
     */
    public function peekMessages($queue_name, $count)
    {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $params = array();
        if ($count !== 1) {
            $params['n'] = (int) $count;
        }
        $this->setJsonHeaders();
        $response = self::json_decode($this->apiCall(self::GET, $url, $params));
        return $response->messages;
    }

    /**
     * Touch a Message on a Queue
     * Touching a reserved message extends its timeout by the duration specified when the message was created,
     * which is 60 seconds by default.
     *
     * @param string $queue_name
     * @param string $message_id
     * @param int $timeout
     * @return mixed
     */
    public function touchMessage($queue_name, $message_id, $reservation_id, $timeout)
    {
        $req = array(
            "reservation_id" => $reservation_id,
        );
        if ($timeout !== 0) {
            $req['timeout'] = (int) $timeout;
        }
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/touch";
        return self::json_decode($this->apiCall(self::POST, $url, $req));
    }

    /**
     * Release a Message on a Queue
     * Releasing a reserved message unreserves the message and puts it back on the queue,
     * as if the message had timed out.
     *
     * @param string $queue_name
     * @param string $message_id
     * @param string $reservation_id
     * @param int $delay The item will not be available on the queue until this many seconds have passed.
     *                   Default is 0 seconds. Maximum is 604,800 seconds (7 days).
     * @return mixed
     */
    public function releaseMessage($queue_name, $message_id, $reservation_id, $delay)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $params = array('reservation_id' => $reservation_id);
        if ($delay !== 0) {
            $params['delay'] = (int) $delay;
        }
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/release";
        return self::json_decode($this->apiCall(self::POST, $url, $params));
    }

    /**
     * Add alerts to a queue. This is for Pull Queue only.
     *
     * @param string $queue_name
     * @param array $alerts_hash
     * @return mixed
     */
    public function addAlerts($queue_name, $alerts_hash)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        $options = array(
            'queue' => array(
                'alerts' => $alerts_hash,
            ),
        );
        return self::json_decode($this->apiCall(self::PUT, $url, $options));
    }

    /**
     * Replace current queue alerts with a given list of alerts. This is for Pull Queue only.
     *
     * @param string $queue_name
     * @param array $alerts_hash
     * @return mixed
     */
    public function updateAlerts($queue_name, $alerts_hash)
    {
        return $this->addAlerts($queue_name, $alerts_hash);
    }

    /**
     * Remove alerts from a queue. This is for Pull Queue only.
     *
     * @param string $queue_name
     *
     * @return mixed
     * @deprecated
     */
    public function deleteAlerts($queue_name)
    {
        return $this->addAlerts($queue_name, array());
    }

    /**
     * Remove alert from a queue by its ID. This is for Pull Queue only.
     *
     * @param string $queue_name
     * @param string $alert_id
     * @return mixed
     * @deprecated
     */
    public function deleteAlertById($queue_name, $alert_id)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/alerts/$alert_id";

        return self::json_decode($this->apiCall(self::DELETE, $url));
    }

    /**
     * Delete a Message Queue
     * This call deletes a message queue and all its messages.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function deleteQueue($queue_name)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        return self::json_decode($this->apiCall(self::DELETE, $url));
    }

    /**
     * Updates the queue object
     *
     * @param string $queue_name
     * @param array $options Parameters to change. keys:
     *
     * @return object
     */
    public function updateQueue($queue_name, $options)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        return self::json_decode($this->apiCall(self::PATCH, $url, array('queue' => $options)));
    }

    /**
     * Creates a queue
     *
     * @param string $queue_name
     * @param array $options Parameters to change. keys:
     *
     * @return object
     */
    public function createQueue($queue_name, $options)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        return self::json_decode($this->apiCall(self::PUT, $url, array('queue' => $options)));
    }

    /**
     * @param $queue_name
     * @param $subscribers_hash - Array of subscribers. keys:
     * - "url" Subscriber url
     * - "name" Name of subscriber
     * @return mixed
     */
    public function addSubscribers($queue_name, $subscribers_hash)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/subscribers";
        $options = array(
            'subscribers' => $subscribers_hash,
        );
        return self::json_decode($this->apiCall(self::POST, $url, $options));
    }

    /**
     * Add Subscriber to a Queue
     *
     * Example:
     * <code>
     * $ironmq->addSubscriber("test_queue", array("url" => "http://example.com"));
     * </code>
     *
     * @param string $queue_name
     * @param array $subscriber_hash Subscriber. keys:
     * - "url" Subscriber url
     * - "name" Name of subscriber
     * @return mixed
     */
    public function addSubscriber($queue_name, $subscriber_hash)
    {
        return $this->addSubscribers($queue_name, array($subscriber_hash));
    }

    /**
     * Replace old Subscribers with new ones, Older subscribers will be removed.
     *
     * @param $queue_name
     * @param $subscribers_hash - Array of subscribers. keys:
     * - "url" Subscriber url
     * - "name" Name of subscriber
     * @return mixed
     */
    public function replaceSubscribers($queue_name, $subscribers_hash)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/subscribers";
        $options = array(
            'subscribers' => $subscribers_hash,
        );
        return self::json_decode($this->apiCall(self::PUT, $url, $options));
    }

    /**
     * Replace Subscribers with a new subscriber
     *
     * @param $queue_name
     * @param array $subscriber_hash Subscriber. keys:
     * - "url" Subscriber url
     * - "name" Name of subscriber
     */
    public function replaceSubscriber($queue_name, $subscriber_hash)
    {
        $this->replaceSubscribers($queue_name, array($subscriber_hash));
    }

    /**
     * Remove Subscriber from a Queue
     *
     * @param $queue_name
     * @param array $subscriber_hash Subscriber. keys:
     * - "url" Subscriber url
     * - "name" Name of subscriber
     */
    public function removeSubscriber($queue_name, $subscriber_hash)
    {
        $this->removeSubscribers($queue_name, array($subscriber_hash));
    }

    /**
     * Remove Subscribers from a Queue
     *
     * Example:
     * <code>
     * $ironmq->removeSubscriber("test_queue", array("url" => "http://example.com"));
     * </code>
     *
     * @param string $queue_name
     * @param array $subscriber_hash Subscriber. keys:
     * - "url" Subscriber url
     * @return mixed
     *
     */
    public function removeSubscribers($queue_name, $subscriber_hash)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/subscribers";
        $options = array(
            'subscribers' => $subscriber_hash,
        );
        return self::json_decode($this->apiCall(self::DELETE, $url, $options));
    }

    /**
     * Get Message's Push Statuses (for Push Queues only)
     *
     * Example:
     * <code>
     * statuses = $ironmq->getMessagePushStatuses("test_queue", $message_id)
     * </code>
     *
     * @param string $queue_name
     * @param string $message_id
     * @return array
     */
    public function getMessagePushStatuses($queue_name, $message_id)
    {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/subscribers";
        $response = self::json_decode($this->apiCall(self::GET, $url));
        return $response->subscribers;
    }

    /**
     * Delete Push Message (for Push Queues only)
     *
     * Example:
     * <code>
     * $ironmq->deletePushMessage("test_queue", $message_id, $reservation_id, $subscriber_name)
     * </code>
     *
     * @param string $queue_name
     * @param string $message_id
     * @param string $reservation_id
     * @param string $subscriber_name
     * @return mixed
     */
    public function deletePushMessage($queue_name, $message_id, $reservation_id, $subscriber_name)
    {
        $req = array(
            'reservation_id'  => $reservation_id,
            'subscriber_name' => $subscriber_name,
        );
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}";
        return self::json_decode($this->apiCall(self::DELETE, $url, $req));
    }

    /* PRIVATE FUNCTIONS */

    private function setJsonHeaders()
    {
        $this->setCommonHeaders();
        $token = isset($this->use_keystone) && $this->use_keystone ? $this->getToken() : $this->token;
        $this->headers['Authorization'] = "OAuth {$token}";
    }

    private function setPostHeaders()
    {
        $this->setCommonHeaders();
        $this->headers['Content-Type'] = 'multipart/form-data';
    }
}
