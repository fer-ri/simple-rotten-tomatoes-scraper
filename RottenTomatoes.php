<?php

class RottenTomatoes
{
	private $baseUrl	= 'http://www.rottentomatoes.com/search/?search=';

	public function search($title)
	{
		$url	= $this->baseUrl . urlencode($title);

		$open	= $this->_curl($url);

		if ($open['error']) {
			throw new Exception($open['error']);
		}

		// search all matching result
		if ( ! preg_match_all('/<a class="unstyled articleLink" target="_top" data-pageheader="" href="(.*?)">/msi', 
			$open['content'], 
			$matches)
		) {
			throw new Exception('No match result');
		}

		// open first matches only
		$open	= $this->_curl('http://www.rottentomatoes.com/' . $matches[1][0]);

		if ($open['error']) {
			throw new Exception($open['error']);
		}

		$title	= $this->_match('/<title>(.*?)<\/title>/msi', $open['content'], 1);
		$title	= str_replace(' - Rotten Tomatoes', '', $title);
		$title	= trim($title);

		$description	= $this->_match('/<p id="movieSynopsis" class="movie_synopsis" style="clear:both">(.*?)<a href="javascript/msi', $open['content']);
		$description	= strip_tags($description);
		$description	= trim($description);

		return [
			'title'			=> $title,
			'description'	=> $description,
		];
	}

	public function setBaseUrl($url)
	{
		$this->baseUrl	= $url;
	}

	/**
	 * Wrapper for easy cURLing
	 *
	 * @author Viliam Kopecký
	 *
	 * @param string HTTP method (GET|POST|PUT|DELETE)
	 * @param string URI
	 * @param mixed content for POST and PUT methods
	 * @param array headers
	 * @param array curl options
	 * @return array of 'headers', 'content', 'error'
	 */
	private function _curl($uri, $method = 'GET', $data = null, $curl_headers = [], $curl_options = []) 
	{
		// defaults
		$default_curl_options = [
			CURLOPT_SSL_VERIFYPEER	=> false,
			CURLOPT_HEADER 			=> true,
			CURLOPT_RETURNTRANSFER 	=> true,
			CURLOPT_TIMEOUT 		=> 10,
		];

		$default_headers = [];

		// validate input
		$method = strtoupper(trim($method));
		$allowed_methods = ['GET', 'POST', 'PUT', 'DELETE'];

		if ( ! in_array($method, $allowed_methods))
			throw new Exception("'$method' is not valid cURL HTTP method.");

		if ( ! empty($data) && !is_string($data))
			throw new Exception("Invalid data for cURL request '$method $uri'");

		// init
		$curl	= curl_init($uri);

		// apply default options
		curl_setopt_array($curl, $default_curl_options);

		// apply method specific options
		switch($method) {
			case 'GET':
				break;
			case 'POST':
				if ( ! is_string($data))
					throw new Exception("Invalid data for cURL request '$method $uri'");
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case 'PUT':
				if ( ! is_string($data))
					throw new Exception("Invalid data for cURL request '$method $uri'");
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
			case 'DELETE':
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
				break;
		}

		// apply user options
		curl_setopt_array($curl, $curl_options);

		// add headers
		curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge($default_headers, $curl_headers));

		// parse result
		$raw			= rtrim(curl_exec($curl));
		$lines 			= explode("\r\n", $raw);
		$headers		= [];
		$content		= '';
		$write_content	= false;

		if (count($lines) > 3) {
			foreach($lines as $h) {
				if ($h == '') {
					$write_content = true;
				} else {
					if ($write_content) {
						$content .= $h."\n";
					} else {
						$headers[] = $h;
					}
				}
			}
		}

		$error	= curl_error($curl);

		curl_close($curl);

		// return
		return [
			'raw'		=> $raw,
			'headers' 	=> $headers,
			'content' 	=> $content,
			'error' 	=> $error
		];
	}

	private function _match($regex, $str, $i = 0)
	{
		if (preg_match($regex, $str, $match) == 1) {
			return $match[$i];
		}

		return false;
	}
}