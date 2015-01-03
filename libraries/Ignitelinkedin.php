<?php


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
    private $CI;
    public function __construct($params)
    {
        //initialize linked in api parameters from config filw or user supplied
        $args = func_get_args();
        if (count($args) > 0) {
            $keys = array_keys($params);
            foreach ($key as $key) {
                $this->set($key, $params[$key]);

            }
        } else {
            //no parameters were specified, check the default configuration file
            $this->config->load('ignitelinkedin', false, true);
            $this->set('api_key', $this->config->item('linkedin_api_key'));
            $this->set('api_key', $this->config->item('linkedin_api_secret'));

            if ($this->config->item('linkedin_redirect_uri')) {
                $this->set('redirect_uri', $this->config->item('redirect_uri'));
            }

        }

        $this->CI = &get_instance();
        //check to see if a redirect uri was specified and generate one from context if not
        if (!isset($this->redirect_uri)) {
            $this->CI->load->helper('url');
            $this->set('redirect_uri', current_url());
        }
    }
    public function getUser()
    {
         if (isset($this->access_token)) {
            //get a user with the existing access token
        } else if ($this->access_token = $this->getSessionValue('access_token')) {
            //get a user with an access token that was just retrieved
        } else if(!isset($this->access_token)) {
            $data ['success'] = 0;
            $data ['error_message'] = "No valid access token found";
            return $data;
        } 
    }
    public function beginAuthentication()
    {
        //was there an error authenticating the user
        if ($error = $this->input->get('error')) {
            $result['success'] = 0;
            $result['error_message'] = $error;
            return $result;
        } else
            if ($code = $this->input->get('code')) {
                //did we get a code back
                if ($this->getSessionValue('state') == $this->get('state')) {
                    $this->getAccessToken($code);
                } else {
                    //possible CSRF atack
                    $result['success'] = 0;
                    $result['error_message'] = 'State does not match';
                    return $result;
                }
            }
    }
    private function getAccessTokenFromCode($code)
    {
        $result = $this->exchangeCode($code);
        //set access token and other parameters in session
        $this->setSessionValue('access_token', $result->access_token);
        $this->setSessionValue('expires_in', $result->expires_in);
        
    }
    private function set($property, $value)
    {
        if (property_exists(_CLASS_, $key)) {
            $this->$property = $key;
        }
    }

    private function get($property, $value)
    {
        if (property_exists(_CLASS_, $key)) {
            return $this->$property;
        }
        return null;
    }

    public function authenticateAndGetUser()
    {

    }

    private function getSessionValue($key)
    {
        $data = $this->CI->session->userdata('ignitelinkedin');
        $data = json_decode($data);

        if (isset($data[$key])) {
            return $data[$key];
        }
        return false;
    }

    private function setSessionValue($key, $value)
    {
        $data = $this->CI->session->userdata('ignitelinkedin');
        $data = json_decode($data);
        $data[$key] = $value;
        $this->CI->session->set_userdata('ignitelinkedin', json_encode($data));
    }

    private function initializeSession()
    {
        //load the session file if it is not already loaded
        $this->CI->load('session');
        $data = array();
        $this->CI->session->set_userdata('ignitelinkedin', json_encode($data));
    }

    private function exchangeCode($code)
    {
        $params = array(
            'grant_type' => 'authorization_code',
            'client_id' => $this->api_key,
            'client_secret' => $this->api_secret,
            'code' => $code,
            'redirect_uri' => $this->redirect_uri,
            );

        $url = 'https://www.linkedin.com/uas/oauth2/accessToken?' . http_build_query($params);

        // Tell streams to make a POST request
        $context = stream_context_create(array('http' => array('method' => 'POST', )));

        // Retrieve access token information
        $response = file_get_contents($url, false, $context);
        return json_decode($response);
    }
}
