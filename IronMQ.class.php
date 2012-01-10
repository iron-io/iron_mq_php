<?php

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
        if(!empty($this->timeout) || $this->timeout == 0) {# 0 is considered empty, but we want people to be able to set a timeout of 0
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
        if($expires_in > $this->max_expires_in) {
            throw new InvalidArgumentException("Expires In can't be greater than ".$this->max_expires_in.".");
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

    private $required_config_fields = array('token','protocol','host','port','api_version');

    private $url;
    private $token;
    private $api_version;
    private $version;
    private $project_id;

    /**
     * @param string|array $config_file_or_options
     *        Array of options or name of config file.
     * Fields in options array or in config:
     * Required:
     * - token
     * - protocol
     * - host
     * - port
     * - api_version
     * Optional:
     * - default_project_id
     */
    function __construct($config_file_or_options){
        $config = $this->getConfigData($config_file_or_options);
        $token              = $config['token'];
        $protocol           = $config['protocol'];
        $host               = $config['host'];
        $port               = $config['port'];
        $api_version        = $config['api_version'];
        $default_project_id = empty($config['default_project_id'])?'':$config['default_project_id'];

        $this->url          = "$protocol://$host:$port/$api_version/";
        $this->token        = $token;
        $this->api_version  = $api_version;
        $this->version      = $api_version;
        $this->project_id   = $default_project_id;
    }

    public function setProjectId($project_id) {
        if (!empty($project_id)){
          $this->project_id = $project_id;
        }
        if (empty($this->project_id)){
            throw new InvalidArgumentException("Please set project_id");
        }
    }

    public function getProjects(){
        $this->setJsonHeaders();
        $projects = json_decode($this->apiCall(self::GET, 'projects'));
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $projects->projects;
    }

    public function getProjectDetails($project_id = ''){
        $this->setProjectId($project_id);
        $this->setJsonHeaders();
        $url =  "projects/{$this->project_id}";
        $response = json_decode($this->apiCall(self::GET, $url));
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $response;
    }

    public function postProject($name){
        $request = array(
            'name' => $name
        );

        $this->setCommonHeaders();
        $res = $this->apiCall(self::POST, 'projects', $request);
        $responce = json_decode($res);
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $responce->id;
    }

    public function deleteProject($project_id){
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}";
        return $this->apiCall(self::DELETE, $url);
    }

    public function getQueues($project_id = '', $page = 0){
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/queues";
        $params = array();
        if($page > 0) {
            $params['page'] = $page;
        }
        $this->setJsonHeaders();
        $queues = json_decode($this->apiCall(self::GET, $url, $params));
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $queues;
    }

    public function getQueue($project_id = '', $queue_name) {
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/queues/{$queue_name}";
        $this->setJsonHeaders();
        $queue = json_decode($this->apiCall(self::GET, $url));
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $queue;
    }

    public function postMessage($project_id = '', $queue_name, $message) {
        $msg = new IronMQ_Message($message);
        $req = array(
            "messages" => array($msg->asArray())
        );
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        $response = json_decode($res);
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $response;
    }

    public function postMessages($project_id = '', $queue_name, $messages) {
        $req = array(
            "messages" => array()
        );
        foreach($messages as $message) {
            $msg = new IronMQ_Message($message);
            array_push($req['messages'], $msg->asArray());
        }
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        $response = json_decode($res);
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $response;
    }

    public function getMessages($project_id = '', $queue_name, $count=1) {
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $params = array();
        if($count > 1) {
            $params['count'] = $count;
        }
        $this->setJsonHeaders();
        $response = $this->apiCall(self::GET, $url, $params);
        $messages = json_decode($response);
        $json_error = json_last_error();
        if($json_error != JSON_ERROR_NONE) {
            throw new JSON_Exception($json_error);
        }
        return $messages;
    }

    public function getMessage($project_id = '', $queue_name) {
        return $this->getMessages($project_id, $queue_name, 1);
    }

    public function deleteMessage($project_id = '', $queue_name, $message_id) {
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages/{$message_id}";
        return $this->apiCall(self::DELETE, $url);
    }

    public function deleteTask($project_id, $task_id){
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $this->headers['Accept'] = "text/plain";
        unset($this->headers['Content-Type']);
        $url = "projects/{$this->project_id}/tasks/$task_id";
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
}
