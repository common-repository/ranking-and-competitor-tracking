<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (class.hub-ract-base.php)
 * Copyright: (C) 2011 Clinton
 * Developer:  Clinton [CreatorSEO]
 * Created on 10 March 2018
 *
 * Description: Base class for Hub5050 Ranking and Competitor Tracking (ract)
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */


class hub_ract_base {
    protected $userIP = '0.0.0.0';
    public $error = '';
    protected $curlErr = '';
    protected $httpCode = null;
    protected $stopText = '';
    protected $stopWords = array();
    protected $schemeX = 'http';
    protected $hostX = '';
    protected $extX = '';
    protected $domainX = '';
    protected $subdomainX = '';
    public $urldata = array();
    public $proxy = '';
    public $userAgent = '';
    protected $data = array();
	public static $se_regions = array(
		'al'=>array('country'=>'Albania', 'bing'=>'al', 'google'=>'al'),
		'dz'=>array('country'=>'Algeria', 'bing'=>'dz', 'google'=>'dz'),
		'ar'=>array('country'=>'Argentina', 'bing'=>'ar', 'google'=>'com.ar'),
		'am'=>array('country'=>'Armenia', 'bing'=>'am', 'google'=>'am'),
		'au'=>array('country'=>'Australia', 'bing'=>'au', 'google'=>'com.au'),
		'at'=>array('country'=>'Austria', 'bing'=>'at', 'google'=>'at'),
		'az'=>array('country'=>'Azerbaijan', 'bing'=>'az', 'google'=>'az'),
		'bh'=>array('country'=>'Bahrain', 'bing'=>'bh', 'google'=>'com.bh'),
		'be'=>array('country'=>'Belgium', 'bing'=>'be', 'google'=>'be'),
		'bo'=>array('country'=>'Bolivia', 'bing'=>'bo', 'google'=>'com.bo'),
		'ba'=>array('country'=>'Bosnia and Herzegovina', 'bing'=>'ba', 'google'=>'ba'),
		'br'=>array('country'=>'Brazil', 'bing'=>'br', 'google'=>'com.br'),
		'bg'=>array('country'=>'Bulgaria', 'bing'=>'bg', 'google'=>'bg'),
		'ca'=>array('country'=>'Canada', 'bing'=>'ca', 'google'=>'ca'),
		'cl'=>array('country'=>'Chile', 'bing'=>'cl', 'google'=>'cl'),
		'cn'=>array('country'=>'China', 'bing'=>'cn', 'google'=>'cn'),
		'co'=>array('country'=>'Colombia', 'bing'=>'co', 'google'=>'cn'),
		'cr'=>array('country'=>'Costa Rica', 'bing'=>'cr', 'google'=>'co.cr'),
		'hr'=>array('country'=>'Croatia', 'bing'=>'hr', 'google'=>'hr'),
		'cz'=>array('country'=>'Czech Republic', 'bing'=>'cz', 'google'=>'cz'),
		'dk'=>array('country'=>'Denmark', 'bing'=>'dk', 'google'=>'dk'),
		'do'=>array('country'=>'Dominican Republic', 'bing'=>'do', 'google'=>'com.do'),
		'ec'=>array('country'=>'Ecuador', 'bing'=>'ec', 'google'=>'com.ec'),
		'eg'=>array('country'=>'Egypt', 'bing'=>'eg', 'google'=>'com.eg'),
		'sv'=>array('country'=>'El Salvador', 'bing'=>'sv', 'google'=>'com.sv'),
		'ee'=>array('country'=>'Estonia', 'bing'=>'ee', 'google'=>'ee'),
		'fi'=>array('country'=>'Finland', 'bing'=>'fi', 'google'=>'fi'),
		'mk'=>array('country'=>'Macedonia', 'bing'=>'mk', 'google'=>'mk'),
		'fr'=>array('country'=>'France', 'bing'=>'fr', 'google'=>'fr'),
		'ge'=>array('country'=>'Georgia', 'bing'=>'ge', 'google'=>'ge'),
		'de'=>array('country'=>'Germany', 'bing'=>'de', 'google'=>'de'),
		'gr'=>array('country'=>'Greece', 'bing'=>'gr', 'google'=>'gr'),
		'gt'=>array('country'=>'Guatemala', 'bing'=>'gt', 'google'=>'com.gt'),
		'hn'=>array('country'=>'Honduras', 'bing'=>'hn', 'google'=>'hn'),
		'hk'=>array('country'=>'Hong Kong', 'bing'=>'hk', 'google'=>'com.hk'),
		'hu'=>array('country'=>'Hungary', 'bing'=>'hu', 'google'=>'hu'),
		'is'=>array('country'=>'Iceland', 'bing'=>'is', 'google'=>'is'),
		'in'=>array('country'=>'India', 'bing'=>'in', 'google'=>'co.in'),
		'id'=>array('country'=>'Indonesia', 'bing'=>'id', 'google'=>'co.id'),
		'iq'=>array('country'=>'Iraq', 'bing'=>'iq', 'google'=>'iq'),
		'ie'=>array('country'=>'Ireland', 'bing'=>'ie', 'google'=>'ie'),
		'pk'=>array('country'=>'Pakistan', 'bing'=>'pk', 'google'=>'com.pk'),
		'il'=>array('country'=>'Israel', 'bing'=>'il', 'google'=>'co.il'),
		'it'=>array('country'=>'Italy', 'bing'=>'it', 'google'=>'it'),
		'jp'=>array('country'=>'Japan', 'bing'=>'jp', 'google'=>'co.jp'),
		'jo'=>array('country'=>'Jordan', 'bing'=>'jo', 'google'=>'jo'),
		'ke'=>array('country'=>'Kenya', 'bing'=>'ke', 'google'=>'co.ke'),
		'kr'=>array('country'=>'Korea', 'bing'=>'kr', 'google'=>'co.kr'),
		'kw'=>array('country'=>'Kuwait', 'bing'=>'kw', 'google'=>'com.kw'),
		'lv'=>array('country'=>'Latvia', 'bing'=>'lv', 'google'=>'lv'),
		'lb'=>array('country'=>'Lebanon', 'bing'=>'lb', 'google'=>'com.lb'),
		'ly'=>array('country'=>'Libya', 'bing'=>'ly', 'google'=>'com.ly'),
		'lt'=>array('country'=>'Lithuania', 'bing'=>'lt', 'google'=>'lt'),
		'lu'=>array('country'=>'Luxembourg', 'bing'=>'lu', 'google'=>'lu'),
		'my'=>array('country'=>'Malaysia', 'bing'=>'my', 'google'=>'com.my'),
		'mt'=>array('country'=>'Malta', 'bing'=>'mt', 'google'=>'com.mt'),
		'mx'=>array('country'=>'Mexico', 'bing'=>'mx', 'google'=>'com.mx'),
		'ma'=>array('country'=>'Morocco', 'bing'=>'ma', 'google'=>'co.ma'),
		'nl'=>array('country'=>'Netherlands', 'bing'=>'nl', 'google'=>'nl'),
		'nz'=>array('country'=>'New Zealand', 'bing'=>'nz', 'google'=>'co.nz'),
		'ni'=>array('country'=>'Nicaragua', 'bing'=>'ni', 'google'=>'com.ni'),
		'no'=>array('country'=>'Norway', 'bing'=>'no', 'google'=>'no'),
		'om'=>array('country'=>'Oman', 'bing'=>'om', 'google'=>'com.om'),
		'pa'=>array('country'=>'Panama', 'bing'=>'pa', 'google'=>'com.pa'),
		'py'=>array('country'=>'Paraguay', 'bing'=>'py', 'google'=>'com.py'),
		'pe'=>array('country'=>'Peru', 'bing'=>'pe', 'google'=>'com.pe'),
		'pl'=>array('country'=>'Poland', 'bing'=>'pl', 'google'=>'pl'),
		'pt'=>array('country'=>'Portugal', 'bing'=>'pt', 'google'=>'pt'),
		'pr'=>array('country'=>'Puerto Rico', 'bing'=>'pr', 'google'=>'com.pr'),
		'qa'=>array('country'=>'Qatar', 'bing'=>'qa', 'google'=>'com.qa'),
		'ph'=>array('country'=>'Philippines', 'bing'=>'ph', 'google'=>'com.ph'),
		'ro'=>array('country'=>'Romania', 'bing'=>'ro', 'google'=>'ro'),
		'ru'=>array('country'=>'Russia', 'bing'=>'ru', 'google'=>'ru'),
		'sa'=>array('country'=>'Saudi Arabia', 'bing'=>'sa', 'google'=>'com.sa'),
		'rs'=>array('country'=>'Serbia', 'bing'=>'sp', 'google'=>'rs'),
		'sg'=>array('country'=>'Singapore', 'bing'=>'sg', 'google'=>'com.sg'),
		'sk'=>array('country'=>'Slovakia', 'bing'=>'sk', 'google'=>'sk'),
		'si'=>array('country'=>'Slovenia', 'bing'=>'si', 'google'=>'si'),
		'za'=>array('country'=>'South Africa', 'bing'=>'za', 'google'=>'co.za'),
		'es'=>array('country'=>'Spain', 'bing'=>'es', 'google'=>'es'),
		'se'=>array('country'=>'Sweden', 'bing'=>'se', 'google'=>'se'),
		'ch'=>array('country'=>'Switzerland', 'bing'=>'ch', 'google'=>'ch'),
		'tw'=>array('country'=>'Taiwan', 'bing'=>'tw', 'google'=>'com.tw'),
		'th'=>array('country'=>'Thailand', 'bing'=>'th', 'google'=>'co.th'),
		'tn'=>array('country'=>'Tunisia', 'bing'=>'tn', 'google'=>'tn'),
		'tr'=>array('country'=>'Turkey', 'bing'=>'tr', 'google'=>'com.tr'),
		'ae'=>array('country'=>'United Arab Emirates', 'bing'=>'ae', 'google'=>'ae'),
		'ua'=>array('country'=>'Ukraine', 'bing'=>'ua', 'google'=>'com.ua'),
		'gb'=>array('country'=>'United Kingdom', 'bing'=>'gb', 'google'=>'co.uk'),
		'us'=>array('country'=>'United States', 'bing'=>'us', 'google'=>'com'),
		'vn'=>array('country'=>'Vietnam', 'bing'=>'vn', 'google'=>'com.vn')
	);

    /**
     * Constructor for base
     */
    function __construct(){
    	//$this->userIP = $this->getIPAddr();
    	$tmpfile = plugin_dir_path(HUB_RACT_ROOT).'inc/stopwords.txt';
    	$this->addStopFile($tmpfile);
	    //creator_update_log_file('DEBUG', array('STOP WORDS FILE'=>$this->stopWords),'append',true);
    }
    
    // --------------------------------- Database methods ---------------------------------
    
    
    // --------------------------------- Utility methods ---------------------------------
    
    /**
     * Read a list of stop words separated by spaces
     * @param string $fn file name of file that contains the stop words
     */
    function addStopFile($tmpfile){
    	if(file_exists($tmpfile)){
    		$this->stopText = strtolower(file_get_contents($tmpfile));
    		$this->stopWords = explode(" ",$this->stopText);
    	} else {
    		$this->stopText = '';
    		$this->stopWords = array();
    	}
    }
    
    /**
     * Create an array of values from arr2 that match the keys from arr1. All
     * keys are represented so a missing value in arr2 creates a 0 record with
     * the missing key.
     */
    function arrayKeyMap($arr1,$arr2){
    	$arr = array();
    	foreach (array_keys($arr1) as $k=>$v){
    		//echo "<li>".$k." == ".$v." - ";
    		if (isset($arr2[$v])){
    			$arr[$v] = $arr2[$v];
    			//echo $arr2[$v]."Found</li>";
    		} else {
    			$arr[$v] = 0;
    			//echo "Not found</li>";
    		}
    	}
    	return $arr;
    }
    
    /**
     * Determine the proportional frequencies for each element in array and return a
     * new array with the proportion values against the same keys
     */
    function arrayProportion($arr,$cumulate=true){
    	if (creator_count($arr)>0){
    		$total = array_sum($arr);
    		$cum = 0;
    		foreach ($arr as $k=>$v){
    			$arr1['p'][$k] = $total>0? $v/$total*100: 0;
    			if($cumulate){
    				$cum += $arr1['p'][$k];
    				$arr1['c'][$k] = $cum;
    			}
    		}
    		return $arr1;
    	} else {
    		return false;
    	}
    }

    /**
     * Chops a string off at the last space after the string length max specified by $sze
     * @param $txt - the text to chop
     * @param $sze - the length of the text
     * @param false $incdot - include dots if true
     * @param false $strict - do not break at a space if true
     * @return mixed|string
     */
    function chopText($txt, $sze, $incdot=false, $strict=false) {
    	$len = strlen($txt);
    	if ($len>intval($sze)){
    		$txt = $strict? substr($txt, 0, $sze): (strpos($txt," ",$sze)? substr($txt, 0, strpos($txt," ",$sze)): $txt);
    		$txt .= ($incdot && ($len> strlen($txt)))? "...": "";
    	}
    	return $txt;
    }
    
    /**
     * Clean up the text by removing tags, punctuation, numbers and stop words
     * @param string $txt source text
     * @param boolean $removestop remove stop words
     * @param boolean $setEncoding set the encoding to UTF 8
     * @param string $sep separator used between words
     * @param boolean $keepformat keep the format of the text
     * @param boolean $alphanum allow only alphanumeric
     * @param number $maxtext maximum number of characters to allow
     * @return string cleaned up text string with words separated by a space
     */
    function cleanupText($txt,$removestop,$setEncoding=true,$sep=' ',$keepformat=false,$alphanum=false,$maxtext=3000){
    	//ini_set('pcre.backtrack_limit', 99999999999);
    	$sep = strlen($sep)? $sep: " ";
    	$special = array("&nbsp;","&copy;","&trade;","&euro;");
    	$txt = str_replace(">", "> ", $txt);
    	//echo "<h4>Remove any script and tags</h4>";
    	$txt = $this->strip_all_script($txt,'');
        //echo "<h4>Remove any inline styles and tags</h4>";
        $txt = $this->strip_all_inline_css($txt,'');
    	//echo "<h4>Removing all HTML tags</h4>";
    	$txt = strip_tags($txt);
    	//echo "<h4>Removing special Characters</h4>";
    	$txt = str_replace($special,' ',$txt);
    	//echo "<h4>Replace multiple spaces with single spaces</h4>";
    	$txt = preg_replace('/\s+/', ' ',$txt);
    	if ($alphanum){
    		$txt = preg_replace("/[^A-Za-z0-9 \-]/", '', $txt);
    	}
    	if($setEncoding){
    		//echo "<h4>Decode HTML entities</h4>";
    		$txt = html_entity_decode($txt, ENT_QUOTES, "UTF-8" );
    		//$txt = html_entity_decode($txt);
    	}
    	if (!$keepformat){
    		//echo "<h4>Set to lower case</h4>";
    		$txt = strtolower($txt);
    		//echo "<h4>Strip Punctuation</h4>";
    		$txt = $sep==' '? preg_replace('/\W/', ' ', $txt): $txt;
    		//echo "<h4>Strip Numbers</h4>";
    		$txt = preg_replace('([0-9])','',$txt);
    		//echo "<h4>Strip single letters</h4>";
    		$txt = preg_replace('/\s.\s/',' ',$txt);
    		//echo "<h4>Strip whitespace (or other characters) from the beginning and end of the string</h4>";
    		$txt = trim($txt);
    		//Restrict the maximum length of the string
    		$txt = strlen($txt)>$maxtext? $this->chopText($txt, $maxtext, false): $txt;
    		if ($removestop && creator_count($this->stopWords)){
    			//echo "<h4>Strip Stop Text</h4>";
    			//$txt = str_ireplace($this->stopWords, '--', $txt);
    			$kwTemp = explode(" ",$txt);
    			$kwTemp = array_diff($kwTemp,$this->stopWords);
    			//echo "<h4>Strip Blank Records</h4>";
    			$kwTemp = array_filter($kwTemp);
    			$txt = implode($sep,$kwTemp);
    		}
    	}
    	$txt = preg_replace('/\s+/',' ',$txt);
    	return $txt;
    }
    
    /**
     * Convert a text string to a catergorised (frequency) word array with optional
     * string cleaning prior to conversion.  The array elements have the form:
     *     array[keyword]=frequency
     * Input: string
     * Output: a categorised array sorted from maximum to minimum value
     */
    function convert2CatArray($txt,$removestop=true,$clean=true){
    	if ($clean){
    		$txt = $this->cleanupText($txt,$removestop, false);
    		//echo "<p>".htmlentities($txt)."</p><hr />";
    	}
    	//echo htmlentities($txt); echo "<hr />";
    	$kwTemp = explode(' ',$txt);
    	$kwList = array_count_values($kwTemp);
    	arsort($kwList);
    	return $kwList;
    }
    
    /**
     * count the number of words in a string after cleaning the data
     */
    function countWords($txt,$removestop=true,$cleanall=true){
    	$wordArray = $this->createFrequencyArray($txt,$removestop,$cleanall);
    	$wordCount = array_sum($wordArray);
    	return ($wordCount? $wordCount: false);
    }
    
    /**
     * Create a frequency table of the body words submitted to the function
     * @param string $txt words in a block of text
     * @param boolean $removestop remove stop words
     * @param boolean $cleanall deep cleans the text before starting
     * @param string $sep separator definition
     * @return boolean|array list of keywords if successful else false
     */
    function createFrequencyArray($txt,$removestop=true,$cleanall=true,$sep=" "){
    	if ($cleanall){
    		$txt = $this->cleanupText($txt, $removestop,true, $sep);
    	}
    	$kwTemp = explode($sep,$txt);
    	$kwList = array_count_values($kwTemp);
    	arsort($kwList);
    	return (count($kwList)? $kwList: false);
    }
    
    /**
     * Get the real IP address of a visitor to the site.
     * In this PHP function, first attempt is to get the direct IP address of clients machine, if not available
     * then try for forwarded for IP address using HTTP_X_FORWARDED_FOR. And if this is also not available, then
     * finally get the IP address using REMOTE_ADDR.
     */
	function getIPAddr() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			//check ip from share internet
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			//to check ip is pass from proxy
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
			//to check ip is pass from proxy
			$ip=$_SERVER['HTTP_X_REAL_IP'];
		} else {
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		$ipArr = explode(',', $ip);
		return $ipArr[0];
	}

    /**
     * Validate that a URL is valid include the 'http://''
     */
    function isValidURL($url){
    	if (substr($url,0,4)=='http'){
    		//$rx = "/^[a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.\/%&=\?\-_#]+$/i";
    		$rx = "|^http(s)?://[a-z0-9-_]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i";
    	} else {
    		$rx = "|^[a-z0-9-_]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i";
    	}
    	//if(filter_var($url, FILTER_VALIDATE_URL) === FALSE){
    	return preg_match($rx, $url);
    }
    
    /**
     * Parse and check the URL Sets the following array parameters
     * scheme, host, port, user, pass, path, query, fragment, dirname, basename, filename, extension, domain,
     * domainX, rel address
     * @param string $url of the site
     * @param string $retdata if true then return the parsed URL data otherwise set the $urldata class variable
     * @return array|mixed|boolean
     */
    function parseURL($url,$retdata=true){
    	$url = substr($url,0,4)=='http'? $url: ('http://'.$url); //assume http if not supplied
    	if ($urldata = parse_url(str_replace('&amp;','&',$url))){
    		$path_parts = pathinfo($urldata['host']);
    	    $tmp = explode('.',$urldata['host']); $n = count($tmp);
    	    if ($n>=2){
    	        if ($n==4 || ($n==3 && strlen($tmp[($n-2)])<=3)){
    	            $urldata['domain'] = $tmp[($n-3)].".".$tmp[($n-2)].".".$tmp[($n-1)];
    	            $urldata['tld'] = $tmp[($n-2)].".".$tmp[($n-1)]; //top-level domain
    	            $urldata['root'] = $tmp[($n-3)]; //second-level domain
		            $urldata['subdomain'] = $n==4? $tmp[0]: (($n==3 && strlen($tmp[($n-2)])<=3)? $tmp[0]: '');
    	        } else {
    	            $urldata['domain'] = $tmp[($n-2)].".".$tmp[($n-1)];
    	            $urldata['tld'] = $tmp[($n-1)];
    	            $urldata['root'] = $tmp[($n-2)];
    	            $urldata['subdomain'] = $n==3? $tmp[0]: '';
    	        }
    	    }
    	    $urldata['dirname'] = $path_parts['dirname'];
    	    $urldata['basename'] = $path_parts['basename'];
    	    $urldata['filename'] = $path_parts['filename'];
    	    $urldata['extension'] = $path_parts['extension'];
    	    $urldata['base'] = $urldata['scheme']."://".$urldata['host'];
		    $urldata['relative'] = (isset($urldata['path']) && strlen($urldata['path']))? $urldata['path']: '/';
		    $urldata['relative'] .= (isset($urldata['query']) && strlen($urldata['query']))? '?'.$urldata['query']: '';
    		//Set data
    		if ($retdata){
    			return $urldata;
    		} else {
    		    $this->urldata = $urldata;
    			return true;
    		}
    	} else {
    		//invalid URL
    		return false;
    	}
    }
    
    /**
     * Convert a relative address to an absolute address
     * @param string Relative url address
     * @param string $base address
     * @return string absolute url
     */
    function rel2abs($rel, $base) {
    	$info = array('scheme'=>'', 'host'=>'', 'path'=>'');
    	/* return if already absolute URL */
    	if (parse_url($rel, PHP_URL_SCHEME) != '') return ($rel);
    	/* queries and anchors */
    	if ($rel[0] == '#' || $rel[0] == '?') return ($base . $rel);
    	/* parse base URL and convert to an array with parameters: scheme, host, path, query, port, user, pass, fragment */
	    $info = parse_url($base);
    	/* remove non-directory element from path */
    	$info['path'] = preg_replace('#/[^/]*$#', '', $info['path']);
    	/* destroy path if relative url points to root */
    	if ($rel[0] == '/') $info['path'] = '';
    	/* dirty absolute URL */
    	$abs = '';
    	/* do we have a user in our URL? */
    	if (isset($info['user'])) {
    		$abs .= $info['user'];
    		/* password too? */
    		if (isset($info['pass'])) $abs .= ':' . $info['pass'];
    		$abs .= '@';
    	}
    	$abs .= $info['host'];
    	/* did somebody sneak in a port? */
    	if (isset($info['port'])) $abs .= ':' . $info['port'];
    	$abs .= $info['path'] . '/' . $rel . (isset($info['query']) ? '?' . $info['query'] : '');
    	/* replace '//' or '/./' or '/foo/../' with '/' */
    	$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    	for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) { }
    	/* absolute URL is ready! */
    }
    
    /**
     * Remove carriage return and line feed characters
     */
    function removeCRLF($txt, $replacewith=' '){
    	$txt=stripslashes($txt); // strip out hash marks to fix apostropies and single quote marks
    	//$txt=preg_replace( '|[\r\n]|', $replacewith, trim($txt) );
    	$txt=strtr ($txt,chr(13),$replacewith); // replace carriage return with something
    	$txt=strtr ($txt,chr(10),chr(32)); // replace line feed with space
    	return $txt; // take it back home
    }

    
    /**
     * Sort and splice an array keeping only the top $num records
     * @param array $arr the array to apply the changes to.
     * @param number $num the number of elements to retain. All if 0 (default).
     */
    function arraySortAndUpdate(&$arr,$num=0){
    	if (is_array($arr) && count($arr)>0){
    		arsort($arr);
    		if ($num>0){
    			array_splice($arr,$num);
    		}
    	}
    }
    
    /**
     * Strip all the script between script tags and the tags
     *
     * @param $text - text to modify
     * @param string $new replacement string
     * @return array|string|string[]|null
     */
    function strip_all_script($text,$new=' ** '){
        $search = array ("|<script[^>]*?>.*?</script>|si");
        $replace = array ($new);
        return preg_replace($search, $replace, $text);
    }

    /**
     * Strip all the script between script tags and the tags
     *
     * @param $text - text to modify
     * @param string $new replacement string
     * @return array|string|string[]|null
     */
    function strip_all_inline_css($text, $new=' ** '){
        $search = array ("|<style[^>]*?>.*?</style>|si");
        $replace = array ($new);
        return preg_replace($search, $replace, $text);
    }

	/**
	 * Strip all the html comments from $text
	 *
	 * @param $text - text to modify
	 * @param string $new replacement string
	 * @return array|string|string[]|null
	 */
	function strip_html_comments($text, $new=''){
		$search = array ("|<!--[\s\S]*?-->|si");
		$replace = array ($new);
		return preg_replace($search, $replace, $text);
	}
} //end of class

// ---------- Additional direct call functions ----------

/**
 * Rough and ready bot detector. This could be improved but serves its purpose for now.
 *
 * @param $agent - user agent
 * @param $label - IP address (or some other label) to assign to the bot
 * @return bool
 */
function ract_bot_detected($agent, $label='bot') {
	$outcome = false;
	$botcheck = array('bot', 'crawl', 'slurp', 'spider', 'search', 'google', 'bing', 'yandex', 'baidu', 'yahoo', 'mediapartners');
	if (isset($agent)){
		foreach ( $botcheck as $bot ) {
			if (stristr($agent, $bot)){
				$outcome = $label;
			}
		}
	}
//	$pattern = '/bot|crawl|slurp|spider|search|google|bing|yandex|baidu|yahoo|mediapartners/i';
//	if (isset($agent) && preg_match($pattern, $agent)) {
//		$outcome = $label;
//	}
	return $outcome;
}

/**
 * Social site detector for referring sites. This could be improved but serves its purpose for now.
 *
 * @param $host - host of the referring site
 * @param $label - IP address (or some other label) to assign to the bot
 * @return bool
 */
function ract_soc_detected($host, $label = 'social') {
	$outcome = false;
	$socialSites = array(
		'facebook' => 'Facebook',
		't.co' => 'Twitter',
		'plus.url.google' => 'Google+',
		'plus.google' => 'Google+',
		'pinterest' => 'Pinterest',
		'linkedin' => 'LinkedIn',
		'lnkd.in' => 'LinkedIn',
		'instagram' => 'Instagram',
		'whatsapp' => 'WhatsApp',
		'digg' => 'Digg',
		'reddit' => 'Reddit',
		'stumbleupon' => 'StumbleUpon',
		'del.icio.us' => 'Delicious',
		'livejournal' => 'Livejournal',
		'tumblr' => 'Tumblr',
		'vk.com' => 'VKontakte'
	);
	if (isset($host)){
		foreach ( $socialSites as $ref=>$site ) {
			if (stristr($host, $ref)){
				$outcome = (strlen($label)? $label.'|': '') . $site;
			}
		}
	}
	return $outcome;
}
