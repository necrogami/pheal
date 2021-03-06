<?php
/*
 MIT License
 Copyright (c) 2010 Peter Petermann, Daniel Hoffend

 Permission is hereby granted, free of charge, to any person
 obtaining a copy of this software and associated documentation
 files (the "Software"), to deal in the Software without
 restriction, including without limitation the rights to use,
 copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the
 Software is furnished to do so, subject to the following
 conditions:

 The above copyright notice and this permission notice shall be
 included in all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.
*/

/**
 * Pheal (PHp Eve Api Library), a EAAL Port for PHP
 */
class Pheal_Pheal
{
    /**
     * Version container
     */
    public static $version = "0.0.11";

    /**
     * @var int
     */
    private $userid;

    /**
     * @var string
     */
    private $key;

    /**
     * EVE Api scope to be used (for example: "account", "char","corp"...)
     * @var String 
     */
    public $scope;
    
    /**
     * Result of the last XML request, so application can use the raw xml data
     * @var String 
     */
    public $xml;

    /**
     * creates new Pheal API object
     * @param int $userid the EVE userid
     * @param string $key the EVE apikey
     * @param string $scope scope to use, defaults to account. scope can be changed during usage by modifycation of public attribute "scope"
     */
    public function __construct($userid=null, $key=null, $scope="account")
    {
        $this->userid = $userid;
        $this->key = $key;
        $this->scope = $scope;
    }

    /**
     * Magic __call method, will translate all function calls to object to API requests
     * @param String $name name of the function
     * @param array $arguments an array of arguments
     * @return PhealResult
     */
    public function  __call($name, $arguments)
    {
        if(count($arguments) < 1 || !is_array($arguments[0]))
            $arguments[0] = array();
        $scope = $this->scope;
        return $this->request_xml($scope, $name, $arguments[0]); // we only use the
        //first argument params need to be passed as an array, due to naming

    }

    /**
     * method will ask caching class for valid xml, if non valid available
     * will make API call, and return the appropriate result
     * @todo errorhandling
     * @return PhealResult
     */
    private function request_xml($scope, $name, $opts)
    {
        $opts = array_merge(Pheal_Config::getInstance()->additional_request_parameters, $opts);
        if(!$this->xml = Pheal_Config::getInstance()->cache->load($this->userid,$this->key,$scope,$name,$opts))
        {
            $url = Pheal_Config::getInstance()->api_base . $scope . '/' . $name . ".xml.aspx";
            if($this->userid) $opts['userid'] = $this->userid;
            if($this->key) $opts['apikey'] = $this->key;
            
            try {
                // start measure the response time
                Pheal_Config::getInstance()->log->start();

                // request
                if(Pheal_Config::getInstance()->http_method == "curl" && function_exists('curl_init'))
                    $this->xml = self::request_http_curl($url,$opts);
                else
                    $this->xml = self::request_http_file($url,$opts);

                // stop measure the response time
                Pheal_Config::getInstance()->log->stop();

                // parse
                $element = new SimpleXMLElement($this->xml);

            } catch(Exception $e) {
                // log + throw error
                Pheal_Config::getInstance()->log->errorLog($scope,$name,$opts,$e->getCode() . ': ' . $e->getMessage());
                throw new Pheal_Exception('API Date could not be read / parsed, orginial exception: ' . $e->getMessage());
            }
            Pheal_Config::getInstance()->cache->save($this->userid,$this->key,$scope,$name,$opts,$this->xml);
            
            // archive+save only non-error api calls + logging
            if(!$element->error) {
                Pheal_Config::getInstance()->log->log($scope,$name,$opts);
                Pheal_Config::getInstance()->archive->save($this->userid,$this->key,$scope,$name,$opts,$this->xml);
            } else {
                Pheal_Config::getInstance()->log->errorLog($scope,$name,$opts,$element->error['code'] . ': ' . $element->error);
            }
        } else {
            $element = new SimpleXMLElement($this->xml);
        }
        return new Pheal_Result($element);
    }

    /**
     * method will do the actual http call using curl libary. 
     * you can choose between POST/GET via config.
     * will throw Exception if http request/curl times out or fails
     * @param String $url url beeing requested
     * @param array $opts an array of query paramters
     * @return string raw http response
     */
    public static function request_http_curl($url,$opts)
    {
        // init curl
        $adapter = new Zend_Http_Client_Adapter_Curl();
		$client = new Zend_Http_Client();
		$client->setAdapter($adapter);
		$config = array();
        // custom user agent
        if(($http_user_agent = Pheal_Config::getInstance()->http_user_agent) != false)
            $config['useragent'] = $http_user_agent;
        
        // custom outgoing ip address
        //if(($http_interface_ip = Pheal_Config::getInstance()->http_interface_ip) != false)
        //    curl_setopt($curl, CURLOPT_INTERFACE, $http_interface_ip);
        //    
        // use post for params
        if(count($opts) && Pheal_Config::getInstance()->http_post)
        {
            $client->setParameterPost($opts);
        }
        // else build url parameters
        elseif(count($opts))
        {
            $client->setParameterGet($opts);
        }
        
        if(($http_timeout = Pheal_Config::getInstance()->http_timeout) != false)
            $config['timeout'] = $http_timeout;
        
        $client->setUri($url);
        $client->setConfig($config);
        if(Pheal_Config::getInstance()->http_post)
        {
            $response = $client->request(Zend_Http_Client::POST);
        } else {
            $response = $client->request(Zend_Http_Client::GET);
        }
        
        // call
        $result	= $response->getBody();
        $errno = $response->getStatus();
        $error = $response->getMessage();

        if($response->isError())
            throw new Exception($error, $errno);
        else
            return $result;
    }
    
    /**
     * method will do the actual http call using file()
     * remember: on some installations, file_get_contents(url) might not be available due to
     * restrictions via allow_url_fopen
     * @param String $url url beeing requested
     * @param array $opts an array of query paramters
     * @return string raw http response
     */
    public static function request_http_file($url,$opts)
    {
        $options = array();
        
        // set custom user agent
        if(($http_user_agent = Pheal_Config::getInstance()->http_user_agent) != false)
            $options['http']['user_agent'] = $http_user_agent;
        
        // set custom http timeout
        if(($http_timeout = Pheal_Config::getInstance()->http_timeout) != false)
            $options['http']['timeout'] = $http_timeout;
        
        // use post for params
        if(count($opts) && Pheal_Config::getInstance()->http_post)
        {
            $options['http']['method'] = 'POST';
            $options['http']['content'] = http_build_query($opts);
        }
        // else build url parameters
        elseif(count($opts))
        {
            $url .= "?" . http_build_query($opts);
        }

        // set track errors. needed for $php_errormsg
        $oldTrackErrors = ini_get('track_errors');
        ini_set('track_errors', true);

        // create context with options and request api call
        // suppress the 'warning' message which we'll catch later with $php_errormsg
        if(count($options)) 
        {
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
        } else {
            $result = @file_get_contents($url);
        }

         // throw error
        if($result === false) {
            $message = ($php_errormsg ? $php_errormsg : 'HTTP Request Failed');
            
            // set track_errors back to the old value
            ini_set('track_errors',$oldTrackErrors);

            throw new Exception($message);

        // return result
        } else {
            // set track_errors back to the old value
            ini_set('track_errors',$oldTrackErrors);
            return $result;
        }
    }
}

