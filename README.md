# Ignitelinkedin
linked in API library for codeigniter


#Installation 
Copy the curl and ignitelinkedin.php files to the libraries folder of your 
codeigniter project. Also make sure curl is enabled and installed on your server.

#Usage
Using ignitelinkedin in one of your controllers is as simple as doing this

      $this->load->library('ignitelinkedin');
      $result = $this->ignitelinkedin->validateAuthentication();
      if($result['success']) {
            $user = $this->ignitelinkedin->getUser();

            //or get access token for the user and store in db
            $accessToken = $this->ignitelinkedin->getAccessToken();
        } else {
           redirect( $this->ignitelinkedin->generateLoginUrl());
        }
