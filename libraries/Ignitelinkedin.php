<?php
use \Curl\Curl;
/**
 * Ignitelinkedin Codeigniter wrapper library for the linkedin PHP api
 * 
 * @package Ignitelinkedin
 * @author Sola Adekunle
 * @copyright 2015
 * @version 1.0
 * @access public
 */
class Ignitelinkedin
{

    private $api_key = null;
    private $api_secret = null;
    private $redirect_uri = null;
    private $access_token = null;
    private $scope; //default scope is full profile
    private $CI;
    private $fields;
    /**
     * Ignitelinkedin::__construct()
     * 
     * @return
     */
    public function __construct()
    {
        $this->CI = &get_instance();
        $args = func_get_args();
        $params = $args[0];
        //initialize linked in api parameters from config filw or user supplied
        foreach ($params as $property => $value) {
            $this->set($property, $value);
        }
        //check to see if a redirect uri was specified and generate one from context if not
        if (!isset($this->redirect_uri)) {
            $this->CI->load->helper('url');
            $this->set('redirect_uri', current_url());
        }
        if (!isset($this->scope)) {
            $this->set('scope', 'r_fullprofile');
        }
        if (!isset($this->fields)) {
            $this->fields = 'interests,skills';
        }
        $this->curl = new Curl();
        $this->initializeSession();
    }
    /**
     * Ignitelinkedin::getUser()
     * 
     * @return
     */
    public function getUser()
    {
        if (isset($this->access_token)) {
            return $this->getUserFromAccessToken();

        } else {
            $data['success'] = 0;
            $data['error_message'] = "No valid access token found";
            return $data;
        }
    }

    /**
     * Ignitelinkedin::isAuthenticated()
     * 
     * @return
     */
    public function isAuthenticated()
    {
        return isset($this->access_token);
    }
    /**
     * Ignitelinkedin::validateAuthentication()
     * 
     * @return
     */
    public function validateAuthentication()
    {
        //was there an error authenticating the user
        if ($error = $this->CI->input->get('error')) {
            $result['success'] = 0;
            $result['error_message'] = $error;
            return $result;
        } else
            if ($code = $this->CI->input->get('code')) {
                //did we get a code back
                if ($this->getSessionValue('state') == $this->get('state')) {
                    $this->getAccessTokenFromCode($code);
                    if($this->isAuthenticated()) {
                        echo "we are authenticated";
                    $result['success'] = 1;
                    return $result;
                    }
                } else {
                    //possible CSRF atack
                    $result['success'] = 0;
                    $result['error_message'] = 'State does not match';
                    return $result;
                }
            }

        $result['success'] = 0;
        $result['error_message'] ="No code detected";
        return $result;
    }

    /**
     * Ignitelinkedin::generateLoginUrl()
     * 
     * @return
     */
    public function generateLoginUrl()
    {
        $params = array(
            'response_type' => 'code',
            'client_id' => $this->api_key,
            'scope' => $this->scope,
            'state' => uniqid('', true), // unique long string
            'redirect_uri' => $this->redirect_uri,
            );

        // Authentication request
        $url = 'https://www.linkedin.com/uas/oauth2/authorization?';
        $this->setSessionValue('state', $params['state']);
        return $url;

    }
    /**
     * Ignitelinkedin::getAccessTokenFromCode()
     * 
     * @param mixed $code
     * @return
     */
    private function getAccessTokenFromCode($code)
    {
        $result = $this->exchangeCode($code);
        var_dump($result);
        //set access token and other parameters in session
        $this->setSessionValue('access_token', $result->access_token);
        $this->setSessionValue('expires_in', $result->expires_in);
        $this->set('access_token', $result->access_token);

    }
    /**
     * Ignitelinkedin::set()
     * 
     * @param mixed $property
     * @param mixed $value
     * @return
     */
    private function set($property, $value)
    {
        if (property_exists(__class__, $property)) {
            $this->$property = $value;
        }
    }

    /**
     * Ignitelinkedin::get()
     * 
     * @param mixed $property
     * @return
     */
    private function get($property)
    {
        if (property_exists(__class__, $property)) {
            return $this->$property;
        }
        return null;
    }

    /**
     * Ignitelinkedin::authenticateAndGetUser()
     * 
     * @return
     */
    public function authenticateAndGetUser()
    {
        if (isset($this->access_token)) {
            $user = $this->getUserFromAccessToken();
            return $user;
        } else {
              $this->validateAuthentication();
        }
    }

    /**
     * Ignitelinkedin::getSessionValue()
     * 
     * @param mixed $key
     * @return
     */
    private function getSessionValue($key)
    {
        $data = $this->CI->session->userdata('ignitelinkedin');
        $data = json_decode($data, true);

        if (isset($data[$key])) {
            return $data[$key];
        }
        return false;
    }

    /**
     * Ignitelinkedin::setSessionValue()
     * 
     * @param mixed $key
     * @param mixed $value
     * @return
     */
    private function setSessionValue($key, $value)
    {
        $data = $this->CI->session->userdata('ignitelinkedin');
        $data = json_decode($data, TRUE);
        $data[$key] = $value;
        $this->CI->session->set_userdata('ignitelinkedin', json_encode($data));
    }

    /**
     * Ignitelinkedin::initializeSession()
     * 
     * @return
     */
    private function initializeSession()
    {
        //load the session file if it is not already loaded
        $this->CI->load->library('session');
   
        $data = array();
        $this->CI->session->set_userdata('ignitelinkedin', json_encode($data));
    }

    /**
     * Ignitelinkedin::exchangeCode()
     * 
     * @param mixed $code
     * @return
     */
    private function exchangeCode($code)
    {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->api_key,
            'client_secret' => $this->api_secret,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            );

        $url = 'https://www.linkedin.com/uas/oauth2/accessToken?';
        $this->curl= new Curl();
        $this->curl->post($url, $params);

        // Retrieve access token information
       if($this->curl->error()) {
            throw new Exception ($this->curl->error_code . ' ' . $this->curl->error_message);
        } else {
           return json_decode($this->curl->response);
        }
    }

    /**
     * Ignitelinkedin::getUserFromAccessToken()
     * 
     * @return
     */
    private function getUserFromAccessToken()
    {
        //use curl library and make a call on behalf of user to the api using the new
        //access token
        $this->curl = new Curl ();
        $this->curl->setHeader('Authorization','Bearer ' . $this->access_token);
        $this->curl->setHeader('x-li-format', 'json' );
        // Need to use HTTPS
        $url = 'https://api.linkedin.com' . $this->prepareUrlFields();
        $this->curl->get($url);
        if($this->curl->error()) {
            throw new Exception ($this->curl->error_code . ' ' . $this->curl->error_message);
        } else {
           return json_decode($this->curl->response);
        }
        
    }

    /**
     * Ignitelinkedin::prepareUrlFields()
     * 
     * @return
     */
    private function prepareUrlFields()
    {
        $url = '/v1/people/~:('. $this->fields. ')';
        return trim($url);
    }
}
