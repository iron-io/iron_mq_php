<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://docs.iron.io/
 * @version 1.0
 * @package IronMQPHP
 * @copyright Feel free to copy, steal, take credit for, or whatever you feel like doing with this code. ;)
 */

/**
 * The Http_Exception class represents an HTTP response status that is not 200 OK.
 */
class Http_Exception extends Exception{
    const NOT_MODIFIED = 304;
    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const NOT_ALOWED = 405;
    const CONFLICT = 409;
    const PRECONDITION_FAILED = 412;
    const INTERNAL_ERROR = 500;
}

class IronMQ_Exception extends Exception{

}

/**
 * The JSON_Exception class represents an failures of decoding json strings.
 */
class JSON_Exception extends Exception {
    public $error = null;
    public $error_code = JSON_ERROR_NONE;

    function __construct($error_code) {
        $this->error_code = $error_code;
        switch($error_code) {
            case JSON_ERROR_DEPTH:
                $this->error = 'Maximum stack depth exceeded.';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $this->error = "Unexpected control characted found.";
                break;
            case JSON_ERROR_SYNTAX:
                $this->error = "Syntax error, malformed JSON";
                break;
        }
        parent::__construct();
    }

    function __toString() {
        return $this->error;
    }
}


class IronMQ_Message {
    private $body;
    private $timeout;
    private $delay;
    private $expires_in;

    const max_expires_in = 2592000;

    /**
     * Create a new message.
     *
     * @param array|string $message
     *        An array of message properties or a string of the message body.
     * Fields in message array:
     * Required:
     * - body: The message data, as a string.
     * Optional:
     * - timeout: Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
     * - delay: The item will not be available on the queue until this many seconds have passed. Defaults to 0.
     * - expires_in: How long, in seconds, to keep the item on the queue before it is deleted. Defaults to 604800 (7 days). Maximum is 2592000 (30 days).
     */
    function __construct($message) {
        if(is_string($message)) {
            $this->setBody($message);
        } elseif(is_array($message)) {
            $this->setBody($message['body']);
            if(array_key_exists("timeout", $message)) {
                $this->setTimeout($message['timeout']);
            }
            if(array_key_exists("delay", $message)) {
                $this->setDelay($message['delay']);
            }
            if(array_key_exists("expires_in", $message)) {
                $this->setExpiresIn($message['expires_in']);
            }
        }
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        if(empty($body)) {
            throw new InvalidArgumentException("Please specify a body");
        } else {
            $this->body = $body;
        }
    }

    public function getTimeout() {
        if(!empty($this->timeout) || $this->timeout === 0) {# 0 is considered empty, but we want people to be able to set a timeout of 0
            return $this->timeout;
        } else {
            return null;
        }
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    public function getDelay() {
        if(!empty($this->delay) || $this->delay == 0) {# 0 is considered empty, but we want people to be able to set a delay of 0
            return $this->delay;
        } else {
            return null;
        }
    }

    public function setDelay($delay) {
        $this->delay = $delay;
    }

    public function getExpiresIn() {
        return $this->expires_in;
    }

    public function setExpiresIn($expires_in) {
        if($expires_in > self::max_expires_in) {
            throw new InvalidArgumentException("Expires In can't be greater than ".self::max_expires_in.".");
        } else {
            $this->expires_in = $expires_in;
        }
    }

    public function asArray() {
        $array = array();
        $array['body'] = $this->getBody();
        if($this->getTimeout() != null) {
            $array['timeout'] = $this->getTimeout();
        }
        if($this->getDelay() != null) {
            $array['delay'] = $this->getDelay();
        }
        if($this->getExpiresIn() != null) {
            $array['expires_in'] = $this->getExpiresIn();
        }
        return $array;
    }
}

class IronMQ{

    //Header Constants
    const header_user_agent = "IronMQ PHP v0.1";
    const header_accept = "application/json";
    const header_accept_encoding = "gzip, deflate";
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACEPTED = 202;

    const POST   = 'POST';
    const GET    = 'GET';
    const DELETE = 'DELETE';

    public  $debug_enabled = false;

    private $required_config_fields = array('token','project_id');
    private $default_values = array(
        'protocol'    => 'http',
        'host'        => 'mq-aws-us-east-1.iron.io',
        'port'        => '80',
        'api_version' => '1',
    );

    private $url;
    private $token;
    private $api_version;
    private $version;
    private $project_id;

    /**
     * @param string|array $config_file_or_options
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
    function __construct($config_file_or_options){
        $config = $this->getConfigData($config_file_or_options);
        $token              = $config['token'];
        $project_id         = $config['project_id'];

        $protocol           = empty($config['protocol'])   ? $this->default_values['protocol']    : $config['protocol'];
        $host               = empty($config['host'])       ? $this->default_values['host']        : $config['host'];
        $port               = empty($config['port'])       ? $this->default_values['port']        : $config['port'];
        $api_version        = empty($config['api_version'])? $this->default_values['api_version'] : $config['api_version'];

        $this->url          = "$protocol://$host:$port/$api_version/";
        $this->token        = $token;
        $this->api_version  = $api_version;
        $this->version      = $api_version;
        $this->project_id   = $project_id;
    }

    /**
     * Switch active project
     *
     * string @param $project_id Project ID
     * @throws InvalidArgumentException
     */
    public function setProjectId($project_id) {
        if (!empty($project_id)){
          $this->project_id = $project_id;
        }
        if (empty($this->project_id)){
            throw new InvalidArgumentException("Please set project_id");
        }
    }

    public function getQueues($page = 0){
        $url = "projects/{$this->project_id}/queues";
        $params = array();
        if($page > 0) {
            $params['page'] = $page;
        }
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::GET, $url, $params));
    }

    /**
     * Get information about queue.
     * Also returns queue size.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function getQueue($queue_name) {
        $url = "projects/{$this->project_id}/queues/{$queue_name}";
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::GET, $url));
    }

    /**
     * Push a message on the queue
     *
     * Examples:
     * <code>
     * $ironmq->postMessage("test_queue", "Hello world");
     * </code>
     * <code>
     * $ironmq->postMessage("test_queue", array(
     *   "body" => "Test Message"
     *   "timeout" => 120,
     *   'delay' => 2,
     *   'expires_in' => 2*24*3600 # 2 days
     * ));
     * </code>
     *
     * @param string $queue_name Name of the queue.
     * @param array|string $message
     * @return mixed
     */
    public function postMessage($queue_name, $message) {
        $msg = new IronMQ_Message($message);
        $req = array(
            "messages" => array($msg->asArray())
        );
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        return self::json_decode($res);
    }

    /**
     * Push multiple messages on the queue
     *
     * @param string $queue_name Name of the queue.
     * @param array $messages array of messages, each message same as for postMessage() method
     * @return mixed
     */
    public function postMessages($queue_name, $messages) {
        $req = array(
            "messages" => array()
        );
        foreach($messages as $message) {
            $msg = new IronMQ_Message($message);
            array_push($req['messages'], $msg->asArray());
        }
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        return self::json_decode($res);
    }

    public function getMessages($queue_name, $count=1) {
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $params = array();
        if($count > 1) {
            $params['count'] = $count;
        }
        $this->setJsonHeaders();
        $response = $this->apiCall(self::GET, $url, $params);
        $result = self::json_decode($response);
        if(count($result->messages) < 1) {
            return null;
        } else {
            return $result;
        }
    }

    public function getMessage($queue_name) {
        return $this->getMessages($queue_name, 1);
    }

    public function deleteMessage($queue_name, $message_id) {
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages/{$message_id}";
        return $this->apiCall(self::DELETE, $url);
    }

    /* PRIVATE FUNCTIONS */

    private function compiledHeaders(){

        # Set default headers if no headers set.
        if ($this->headers == null){
            $this->setCommonHeaders();
        }

        $headers = array();
        foreach ($this->headers as $k => $v){
            $headers[] = "$k: $v";
        }
        return $headers;
    }

    private function apiCall($type, $url, $params = array()){
        $url = "{$this->url}$url";

        $s = curl_init();
        if (! isset($params['oauth'])) {
          $params['oauth'] = $this->token;
        }
        switch ($type) {
            case self::DELETE:
                $fullUrl = $url . '?' . http_build_query($params);
                $this->debug('apiCall fullUrl', $fullUrl);
                curl_setopt($s, CURLOPT_URL, $fullUrl);
                curl_setopt($s, CURLOPT_CUSTOMREQUEST, self::DELETE);
                break;
            case self::POST:
                $this->debug('apiCall url', $url);
                curl_setopt($s, CURLOPT_URL,  $url);
                curl_setopt($s, CURLOPT_POST, true);
                curl_setopt($s, CURLOPT_POSTFIELDS, json_encode($params));
                break;
            case self::GET:
                $fullUrl = $url . '?' . http_build_query($params);
                $this->debug('apiCall fullUrl', $fullUrl);
                curl_setopt($s, CURLOPT_URL, $fullUrl);
                break;
        }

        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_HTTPHEADER, $this->compiledHeaders());
        $_out = curl_exec($s);
        $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
        curl_close($s);
        switch ($status) {
            case self::HTTP_OK:
            case self::HTTP_CREATED:
            case self::HTTP_ACEPTED:
                $out = $_out;
                break;
            default:
                throw new Http_Exception("http error: {$status} | {$_out}", $status);
        }
        return $out;
    }


    /**
     * @param array|string $config_file_or_options
     * array of options or name of config file
     * @return array
     * @throws InvalidArgumentException
     */
    private function getConfigData($config_file_or_options){
        if (is_string($config_file_or_options)){
            $ini = parse_ini_file($config_file_or_options, true);
            if ($ini === false){
                throw new InvalidArgumentException("Config file $config_file_or_options not found");
            }
            if (empty($ini['iron_mq'])){
                throw new InvalidArgumentException("Config file $config_file_or_options has no section 'iron_mq'");
            }
            $config =  $ini['iron_mq'];
        }elseif(is_array($config_file_or_options)){
            $config = $config_file_or_options;
        }else{
            throw new InvalidArgumentException("Wrong parameter type");
        }
        foreach ($this->required_config_fields as $field){
            if (empty($config[$field])){
                throw new InvalidArgumentException("Required config key missing: '$field'");
            }
        }
        return $config;
    }

    private function setCommonHeaders(){
        $this->headers = array(
            'Authorization'   => "OAuth {$this->token}",
            'User-Agent'      => self::header_user_agent,
            'Content-Type'    => 'application/json',
            'Accept'          => self::header_accept,
            'Accept-Encoding' => self::header_accept_encoding
        );
    }

    private function setJsonHeaders(){
        $this->setCommonHeaders();
    }

    private function setPostHeaders(){
        $this->setCommonHeaders();
        $this->headers['Content-Type'] ='multipart/form-data';
    }

    private function debug($var_name, $variable){
        if ($this->debug_enabled){
            echo "{$var_name}: ".var_export($variable,true)."\n";
        }
    }

    private static function json_decode($response){
        $data = json_decode($response);
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $data;
    }

}
