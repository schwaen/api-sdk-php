<?php
namespace Aiphilos\Api;

/**
 * Solutions for the default clients
 */
abstract class AbstractClient implements ClientInterface
{
    /** @var string */
    protected $auth_name = null;
    
    /** @var string */
    protected $auth_pass = null;
    
    /** @var string */
    protected $ref_id = null;
    
    /** @var string */
    protected $default_language = null;
    
    /**
     * (non-PHPdoc)
     * @see \Aiphilos\Api\ClientInterface::setAuthCredentials()
     */
    public function setAuthCredentials($name, $pass, $ref_id = null)
    {
        $this->auth_name = $name;
        $this->auth_pass = $pass;
        $this->ref_id = $ref_id;
    }
    
    protected function exec($path='', $language = null, array $options = array())
    {
        $language = !empty($language) ? $language : $this->getDefaultLanguage();
        if (!in_array($language, $this->getLanguages())) {
            throw new \UnexpectedValueException('Unknown or invalid $language: '.$language.'. See getLanguages()');
        }
        $url = Sdk::BASE_URL.Sdk::API_VERSION.'/'.$language.'/'.$path;
        $default_options = array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->auth_name.':'.$this->auth_pass,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array_merge(
                array('Content-Type: application/json'),
                !empty($this->ref_id) ? array('X-AIPHILOS-REF: '.$this->ref_id) : array()
            ),
        );
        $ch = curl_init();
        curl_setopt_array($ch, array_replace($default_options, $options));
        $response = json_decode(curl_exec($ch), true);
        $response_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        //curl_close($ch);
        //handle generell api error
        if ($response === null || isset($response['messagecode']) && $response['messagecode'] !== 0 || $response_http_code < 200 || $response_http_code > 299) {
//var_dump($response);
//var_dump(curl_getinfo($ch));
            $message = !empty($response['message']) ? $response['message'] : 'Unknown';
            $message_code = isset($response['messagecode']) ? $response['messagecode'] : $response_http_code;
            switch ($message_code) {
                case 401: $message = 'Unauthorized'; break;
                case 403: $message = 'Forbidden'; break;
                case 405: $message = 'Method Not Allowed'; break;
            }
            throw new \DomainException($message, $message_code);
        }
        return $response;
    }
    
    /**
     * (non-PHPdoc)
     * @todo switch to API when its available
     * @see \Aiphilos\Api\ClientInterface::getLanguages()
     */
    public function getLanguages()
    {
        return array('de-de');
    }
    
    /**
     * Sets the default language
     *
     * @param string $language
     *
     * @throws \UnexpectedValueException
     */
    public function setDefaultLanguage($language)
    {
        if (!in_array($language, $this->getLanguages())) {
            throw new \UnexpectedValueException('$language is not a valid language. See getLanguages()');
        }
        $this->default_language = $language;
    }
    
    /**
     * @return string
     */
    public function getDefaultLanguage()
    {
        return $this->default_language;
    }
}