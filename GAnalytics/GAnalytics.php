<?php
/**
 * GAnalytics - Google Analytics API data importer
 *
 * @author Dumitru Glavan
 * @link http://dumitruglavan.com
 * @version 1.0
 *
 * Examples and documentation at: https://github.com/doomhz/GAnalytics
 * Find source on GitHub: https://github.com/doomhz/GAnalytics
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */
class GAnalytics
{
	// Keeps the Auth response key
    protected $_auth = '';

	// GAnalytics account data
    protected $_email = '';
    protected $_password = '';

	// GAnalytics API data request URL
	// Generates easy with this tool:
	// http://code.google.com/apis/analytics/docs/gdata/gdataExplorer.html
    protected $_requestUrl = '';

	/**
	 * Set up your class data
	 *
	 * @param array $config
	 *
	 */
    public function  __construct($config = array())
    {
        foreach ($config as $attr => $value) {
            if (isset($this->{"_$attr"})) {
                $this->{"_$attr"} = $value;
            }
        }
    }

	/**
	 * Authenticate to Google Analytics and get the response Auth key
	 *
	 * @param string $email
	 * @param string $password
	 * @return string - the GA Auth key
	 */
    public function login($email = NULL, $password = NULL)
    {
        $email = $email !== NULL ? $email : $this->_email;
        $password = $password !== NULL ? $password : $this->_password;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.google.com/accounts/ClientLogin");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);

        $data = array(
            'accountType' => 'GOOGLE',
            'Email' => $email,
            'Passwd' => $password,
            'service' => 'analytics',
            'source' => ''
        );

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $this->_auth = '';
        if($info['http_code'] == 200) {
            preg_match('/Auth=(.*)/', $output, $matches);
            if(isset($matches[1])) {
                $this->_auth = $matches[1];
            } else {
                throw new Exception('Login failed with message: ' . $output);
            }
        }

        return $this->_auth;
    }

	/**
	 * If authentication successful - do the GA API call
	 *
	 * @param string $url
	 * @return string - GA Atom Feed data XML
	 */
    public function call($url = NULL)
    {
        $url = $url !== NULL ? $url : $this->_requestUrl;
        if ($this->_auth === '') {
            $this->login();
        }

        $headers = array("Authorization: GoogleLogin auth=$this->_auth");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info['http_code'] != 200) {
            throw new Exception('Request failed with message: ' . $output, $info['http_code']);
        }

        return $output;
    }
}