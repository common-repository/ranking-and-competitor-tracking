<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (class.ranking-and-competitor-tracking.php)
 * Copyright: (C) 2011 Clinton
 * Developer:  Clinton [CreatorSEO]
 * Created on 10 March 2018
 *
 * Description: Metrics class for Hub5050 Ranking and Competitor Tracking (ract)
 *
 * This program is the property of the developer and is not intended for distribution. However, if the
 * program is distributed for ANY reason whatsoever, this distribution is WITHOUT ANY WARRANTEE or even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

class hub_ract_audit extends hub_ract_base{
	public $debugMe = false; //debug to debug_file.txt in the root folder of the site
	public $debug = array(); //storage for debug data
    protected $useProxies = false;
    protected $proxyList = array();
    protected $useUserAgents = true;
    protected $uaList = array();
    protected $userIP = null;
    protected $site = null;
    public $html = '';
	public $htmlLength = 0;
    public $body = '';
    public $maintext = '';
    protected $wordarr = array();
    protected $keyphrases = array();
    protected $siteSet = false;
    
    /**
     * Constructor for Metrics
     * Checks the url provided for a scheme and attempts to set the site
     */
    function __construct($url=null,$debug=false){
        parent::__construct();
        $this->debugMe = ( isset($debug) && $debug)? true: false;
	    $this->setUserAgents();
        if ($url && strlen($url)){
            $url = (substr($url,0,4)=="http")? $url: "http://".$url;
            if ($this->setSite($url)){
                $this->siteSet = true;
            } else {
                $this->siteSet = false;
                $this->error .= " |"."Could not set the site";
            }
        }
    }
    
    /**
     * Set the site URL and domain and then recover the contents of the site
     * @param string $url the url for the site
     * @param string $ref referer
     * @param boolean $getcontent - fetch page content (default true)
     * @return boolean
     */
    function setSite($url, $ref='', $getcontent=true){
        $this->error = '';
        if ($this->isValidURL($url) && ($this->urldata=$this->parseURL($url))!==false){
        	$this->site = $url;
            $this->body = '';
            $this->maintext = '';
            $this->data = array();
            $this->wordarr = array();
            $this->keyphrases = array();
            //$urldata is set in the parseURL() function above 
            $this->schemeX = $this->urldata['scheme'];
            $this->hostX = $this->urldata['host'];
            $this->domainX = $this->urldata['domain'];
            $this->subdomainX = $this->urldata['subdomain'];
            $this->extX = $this->urldata['extension'];
            if ($this->debugMe) {
	            $this->debug['SITE INFO'] = array(
		            'SCHEME'=>$this->schemeX,
		            'HOST'=>$this->hostX,
		            'DOMAIN'=>$this->domainX,
		            'SUB-DOMAIN'=>$this->subdomainX,
		            'EXTENSION'=>$this->extX
	            );
            }
            //Return the header only
            //$params = array('cookiefile'=>'','useragent'=>$this->uaList[0],'followlocation'=>true, 'header'=>true, 'nobody'=>true, 'returntransfer'=>true, 'ssl_verifypeer'=>0);
            //Return the page source only
            $params = array('cookiefile'=>'','useragent'=>$this->uaList[0],'followlocation'=>true, 'header'=>false, 'returntransfer'=>true, 'ssl_verifypeer'=>0);
            if (strlen($ref)) $params['referer'] = $ref;
            return ($getcontent? $this->get_page_content( $url, $params ): true);
        } else {
            $this->error .= " |URL not valid (".$url.")";
            return false;
        }
    }
    
    /**
     * Complete cURL management - read the site using curl with various parameter settings pre-set or user set
     * @param string $url (required) the url to scrape
     * @param array $p (parameters) contains the parameters needed for the cURL (empty array for defaults)
     * @param boolean $ret_html - if true then return the html otherwise (default) set the html for the url
     * @return boolean - success or failure
     */
    function get_page_content($url,$p=array(),$ret_html=false){
    	$outcome = false;
	    $urldata = array();
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $url);
    	$val = isset($p['returntransfer'])? ($p['returntransfer']? true: false): true;
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, $val); //show data as text (1) or web page (0)
    	$val = isset($p['header'])? ($p['header']? true: false): true;
    	curl_setopt($ch, CURLOPT_HEADER, $val); //true to include the header (default = false)
    	if ((isset($this->schemeX) && $this->schemeX == 'https') || isset($p['ssl_verifypeer'])){
    		$val = (isset($p['ssl_verifypeer']) && $p['ssl_verifypeer'])? true: false;
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $val); //false to stop cURL from verifying the peer's certificate
    	}
    	if ($this->useProxies && creator_count($this->proxyList)){
    		$i = rand(0,creator_count($this->proxyList)-1);
    		$this->proxy = preg_replace('/[^S0-9:.]/i', '', $this->proxyList[$i]);
    		//$this->proxy = trim($this->proxyList[$i]);
    		//phpAlert('Proxy: '.$this->proxy);
    		if (substr($this->proxy,0,1)=="S"){
    			$this->proxy = substr($this->proxy,1,22);
    			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);  // comment this to test without proxy
			    //curl_setopt($ch, CURLOPT_PROXYPORT, '8080');
    			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
    			//curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'USERNAME:PASSWORD');
    		} else {
    			$this->proxy = $this->proxy;
    		}
    	}
    	$val = (isset($p['nobody'])? ($p['nobody']? true: false): false);
    	curl_setopt($ch, CURLOPT_NOBODY, $val); //true = remove body
    	$val = (isset($p['encoding'])? (strlen($p['encoding'])? $p['encoding']: ''): '');
    	curl_setopt($ch, CURLOPT_ENCODING, ""); //blank to handle all encodings
    	$val = (isset($p['followlocation'])? ($p['followlocation']? true: false): true);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $val); //true = follow page redirects
    	$val = (isset($p['maxredirs'])? intval($p['maxredirs']): 2);
    	curl_setopt($ch, CURLOPT_MAXREDIRS, $val); //stop follow after $val redirects
    	$val = (isset($p['autoreferer'])? ($p['autoreferer']? true: false): true);
    	curl_setopt($ch, CURLOPT_AUTOREFERER, $val); //set referer on redirect
    	$val = (isset($p['timeout']) || defined('CURL_TIMEOUT'))? (isset($p['timeout'])? intval($p['timeout']): intval(CURL_TIMEOUT)): 120;
    	curl_setopt($ch, CURLOPT_TIMEOUT, $val); //Timeout on response in seconds
    	$val = (isset($p['connecttimeout'])? intval($p['connecttimeout']): 20);
    	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $val); //Timeout on connection in seconds
    	if (isset($p['verbose'])){
    		$val = $p['verbose']? true: false;
    		curl_setopt($ch, CURLOPT_VERBOSE, $val); // Minimize logs - set to true for debugging
    	}
    	if (isset($p['cookiefile']) && strlen($p['cookiefile'])>3){
    		$val = $p['cookiefile'];
    		curl_setopt($ch, CURLOPT_COOKIEJAR, $val); //Cookie management.
    		curl_setopt($ch, CURLOPT_COOKIEFILE, $val);
    	}
    	if ($this->useUserAgents && creator_count($this->uaList)){
    		$i = rand(0, creator_count($this->uaList) - 1);
    		$this->userAgent = $this->uaList[$i];
    		curl_setopt($ch, CURLOPT_REFERER, $this->userAgent); //Set the user agent name
    	} elseif (isset($p['useragent']) && strlen($p['useragent'])>3){
    		$val = $p['useragent'];
    		curl_setopt($ch, CURLOPT_USERAGENT, $val); //Set the user agent name
    	}
    	if (isset($p['referer']) && strlen($p['referer'])>3){
    		if ($p['referer']=='auto'){
			   if ($this->isValidURL($url) && ($urldata=$this->parseURL($url))!==false){
				    $val = $urldata['scheme'].'://'.$urldata['host'];
				    curl_setopt($ch, CURLOPT_REFERER, $val); //Set the referer name
			    }
		    } else {
			    $val = $p['referer'];
			    curl_setopt($ch, CURLOPT_REFERER, $val); //Set the referer name
		    }
    	}
    	//Retrieve the html from the site specified by the url
    	$html = curl_exec($ch);
    	//echo "<p>==-".htmlentities($html)."==</p>";
	    //creator_debug_log('HTML', $html); //*********************************************************************
    	$this->httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); //Get the response code
    	$this->curlErr = curl_error($ch)." [".curl_errno($ch)."]";
    	curl_close($ch);
    	
    	if (isset($this->httpCode) && ($this->httpCode>=200 && $this->httpCode<300)){
		    $this->htmlLength = strlen($html);
    		if ($ret_html){
    			$outcome = $html;
    		} else {
    			$this->html = $html;
    			$outcome = true;
    		}
    		unset ($html);
    	} else {
    		if ($ret_html){
    			$outcome = "FAIL";
    		} else {
    			$this->html = "FAIL";
    		}
    		$this->error .= " |"."Error: ".$this->curlErr." HTTP Code: ".$this->httpCode." (".$url.")";
    	}
    	return $outcome;
    }

	/**
	 * Strip out any information from the search engine returned page that is not needed like the head section, script and styles
	 * @return bool
	 */
    function getHTMLBodyOnly(){
    	$outcome = false;
	    if (isset($this->html)){
//		    $pattern = "<body.*>(.*?)<\/body>"; //including nested tags
//		    preg_match_all("|".$pattern."|simU", $this->html, $data, PREG_PATTERN_ORDER);
//		    $this->html = $data[1][0];
		    //$this->html = substr($this->html,1,3000);
		    $this->html = preg_replace('|<head>(.*?)</head>|is', '**HEAD**', $this->html);
		    $this->html = preg_replace('|<script(.*?)>(.*?)</script>|is', '**SCRIPT**', $this->html);
		    $this->html = preg_replace('|<style(.*?)>(.*?)</style>|is', '**STYLE**', $this->html);
		    //$outcome = true;
	    }
	    return $outcome;
    }

    // --------------------------------- Extraction methods ---------------------------------

    /**
     * Check the URL for the presence of keywords where keywords is a simple array or a string of keywords
     * Returns thew number of keywords found in the URL
     */
    function checkURLWords($url, $keywords){
    	if (!is_array($keywords)){
    		$checkarray = array_unique(explode(' ',$this->cleanupText($keywords,true)));
    	} else {
    		$checkarray = array_unique($keywords);
    	}
    	$arr = array();
    	foreach ($checkarray as $key=>$findme){
    		if (stripos($url, $findme) !== false) {
    			$arr[] = $findme;
    		}
    	}
    	$this->data['url_kwd'] = implode(' ',$arr);
    	if (DICAP_GETMETAWORDS && count($arr)) $this->getFrequencyData($arr,'url_kwd');
    	return count($arr);
    }
    
	/**
     * Get the a links for the site and determine whether these are internal or external links
     * $inc true get the link titles,
     * $rel true gets the relative attibute for the link is supplied
     * The link type is identified as internal (int), external (ext), media (med) or resource (res)
     * NOTE - the body must be set using setBodyText() before this function will work
     */
    function get_a_links($inc=true,$rel=false){
    	$pagelinks = array();
    	$resource = array('.css','.js');
    	$media= array('.aac','.avi','.bmp','.flv','.gif','.jpeg','.ico','.jpg','.mov','.mp3','.mp4','.mpeg','.mpg','.ogg','.pdf','.png','.ram','.swf','.wav','.webm','.wmv');
    	$i=0;
    	//$rx = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
    	$rx = '<a\s[^>]*href=([\"\']??)([^\" >]*?)\\1[^>]*>(.*)<\/a>';
    	if (isset($this->site)){
    		if (strlen($this->body)){
    			if(preg_match_all("|".$rx."|siU", $this->body, $matches, PREG_SET_ORDER)) {
    				foreach($matches as $match) {
    					# $match[0] = full string
    					# $match[1] = match text
    					# $match[2] = link address
    					# $match[3] = link text
    					//convert relative links to absolute links against the parent
    					$add = $this->rel2abs($match[2],$this->urldata['base']);
    					if ($this->isValidURL($add)){
    						//$typ = stristr($add,$this->hostX)? 'int': 'ext';
    						$typ = stristr($add,$this->hostX)? ($add!=str_ireplace($media,"XX",$add)? 'med': ($add!=str_ireplace($resource,"XX",$add)? 'res': 'int')): 'ext';
    						$pagelinks[$typ]['url'][$i] = $add;
    						if ($inc && strlen($match[3])>0){
    							//get link titles
    							//$pagelinks[][$typ]['txt']= strip_tags((strlen($match[3])>40)? substr($match[3],0,40)."...": $match[3]);
    							$pagelinks[$typ]['txt'][$i] = $match[3];
    						}
    						if($rel){
    							//Check for rel tag
    							$pagelinks[$typ]['rel'][$i] = $this->get_rel_tag($match[0]);
    						}
    						$i++;
    					}
    				}
    			}
    			//$this->arrayDump($matches);
    			//abc_add_debug($matches,true); //-----------------------DEBUG-----------------------
    			$pagelinks['outgoing'] = $i;
    			$this->data['links'] = $pagelinks;
    			if (DICAP_GETMETAWORDS && creator_count($this->data['links']['int']['txt'])) $this->getFrequencyData($this->data['links']['int']['txt'],'links_int');
    			if (DICAP_GETMETAWORDS && creator_count($this->data['links']['ext']['txt'])) $this->getFrequencyData($this->data['links']['ext']['txt'],'links_ext');
    			return $i;
    		} else {
    			$this->error .= " |"."Error: Body text not set";
    			return false;
    		}
    	} else {
    		$this->error .= " |"."Error: Site not set";
    		return false;
    	}
    }
 
    /**
     * get the number of words in the body text
     */
    function get_body_words(){
    	return ($this->data['body']['count']? $this->data['body']['count']: 0);
    }
    
    /**
     * Get the document type and dtd definitions
     */
    function get_doctype($detail=true){
    	$rx0 = '|<!DOCTYPE\s(.*?)>|is';
    	if(preg_match_all($rx0, $this->html, $matches, PREG_SET_ORDER)){
    		if ($detail){
    			$this->data['doctype']=$matches[0][1];
    		} else {
    			$rx1 = '|[\"\'](.*)[\"\']|iU';
    			$this->data['doctype']=preg_match($rx1, $matches[0][1], $matches1)? $matches1[1]: $matches[0][1];
    		}
    		$rx2 = $detail? '|<html\s(.*?)>|is': '|<html.*?xmlns.*?[\"\'](.*?)[\"\'].*?>|is';
    		if(preg_match_all($rx2, $this->html, $matches2, PREG_SET_ORDER)){
    			$this->data['dtd']=$matches2[0][1];
    			return true;
    		}
    		return false;
    	} else {
    		return false;
    	}
    }
    
    /**
     * get the validation results as errors and warnings
     */
    function get_dtd_validation(){
    	$test = $this->validateDTD($this->site);
    	if ($test){
    		$this->data['validation']=$test;
    		return true;
    	} else {
    		return false;
    	}
    }

    /**
     * Retrieve all email addresses in the input string ($str)
     * $allowrpt determines whether email addresses can be repeated in the returned array
     */
    function get_emails ($str,$allowrpt=false) {
    	$emails = array();
    	$rx = "|\b[a-zA-Z0-9]+[a-zA-Z0-9\._-]*@[a-zA-Z0-9_-]+[a-zA-Z0-9\._-]+\b|";
    	//$rx =  "|\b[-\w.]+@[A-z0-9][-A-z0-9]+\.+[A-z]{2,4}\b|";
    	//$rx = "|\b\w+\@\w+[\.\w+]+\b|";
    	preg_match_all($rx, $str, $output);
    	foreach($output[0] as $email)
    		if ($allowrpt || !in_array(strtolower($email),$emails)) array_push ($emails, strtolower($email));
    		if (count ($emails) >= 1)
    			return $emails;
    		else
    			return false;
    }
    
    /**
     * Retrieve the number of externals stylesheets used
     */
    function get_external_css(){
    	//$rx = '|<link.*?rel\s*?=\s*?[\"\']stylesheet[\"\'].*?href=[\"\'](.*)\.css.*>"|i';
    	$rx = '|<link.*?stylesheet.*?href=[\"\']?(.*\.css).*?>|i';
    	if(preg_match_all($rx, $this->html, $matches, PREG_SET_ORDER)){
    		//$this->arrayDump($matches);
    		foreach ($matches as $key=>$match){
    			$this->data['xcss'][]=$match[1];
    		}
    		return count($matches);
    	} else {
    		return false;
    	}
    }
   
    /**
     * Retrieve all header tags
     */
    function get_header($tag,$entities=false){
    	$tag = strtolower($tag);
    	if ($tag=='h1' || $tag=='h2' || $tag=='h3' || $tag=='h4' || $tag=='h5' || $tag=='h6'){
    		$rx = '|<'.$tag.'.*>(.*)</'.$tag.'>|ismU';
    		//$rx = '|<'.$tag.'.*>(\w.*)</'.$tag.'>|isxmU';
    		if(preg_match_all($rx, $this->html, $matches, PREG_SET_ORDER)){
    			foreach($matches as $M){
    				if (strlen($M[1])>0){
    					$data[] = $entities? htmlentities($M[1]): $M[1];
    					//echo "<li>".htmlentities($M[1])."</li>";
    				}
    			}
    			//$this->arrayDump($matches);
    			$this->data[$tag] = $data;
    			if (DICAP_GETMETAWORDS && count($this->data[$tag])) $this->getFrequencyData($this->data[$tag],$tag);
    			//$this->phpAlert("tag = ".$tag." count = ".count($data));
    			return count($data);
    		} else {
    			//$this->phpAlert("tag = ".$tag." -- ** None **");
    			//echo "<h5>** None **</h5>";
    			return false;
    		}
    	} else {
    		return false;
    	}
    }

    /**
     * get all the image tags
     */
    function get_image_tags(){
    	$rx = '|<img[^>](.*?)>|sim';
    	$i=0;
    	if (preg_match_all($rx,$this->body,$matches,PREG_SET_ORDER)) {
    		foreach($matches as $M){
    			//echo "<p>-->".$M[1]."<--</p>";
    			$rx1 = '|src\s*=\s*(["\'])([^\1>]*?)\1|sim';
    			if (preg_match($rx1,$M[1],$src)){
    				$data['src'][$i] = $src[2];
    				//echo "<p>src-->".$src[2]."<--</p>";
    			}
    			$rx2 = '|alt\s*=\s*(["\'])([^\1>]*?)\1|sim';
    			if (preg_match($rx2,$M[1],$alt) && strlen($alt[2])>0){
    				$data['alt'][$i] = $alt[2];
    				//echo "<p>alt-->".$alt[2]."<--</p>";
    			}
    			$i++;
    		}
    		$this->data['image'] = $data;
    		if (DICAP_GETMETAWORDS && creator_count($this->data['image']['alt'])) $this->getFrequencyData($this->data['image']['alt'],'image');
    		return creator_count($data['src']);
    	} else {
    		//echo "<h5>** None **</h5>";
    		return false;
    	}
    }
    
    /**
     * get the proportion of page words links in the links
     */
    function get_link_proportion(){
    	if (isset($this->data['links'])){
    		$this->data['stat']['pagewords'] = $this->data['body']['count'];
    		$this->data['stat']['outgoing'] = $this->data['links']['outgoing'];
    		$tmparr = is_array($this->data['links']['ext']['txt'])&&$this->data['links']['int']['txt']?
    		array_merge($this->data['links']['ext']['txt'], $this->data['links']['int']['txt']):
    		(is_array($this->data['links']['ext']['txt'])? $this->data['links']['ext']['txt']:
    				(is_array($this->data['links']['ext']['txt'])? $this->data['links']['int']['txt']: array()));
    		//print_r($tmparr); echo "<hr />";
    		$this->data['stat']['linkwords'] = $this->countWords(implode(' ',$tmparr),true,true);
    		if ($this->data['body']['count']>$this->data['stat']['linkwords']){
    			$this->data['stat']['proportion'] = round(($this->data['stat']['linkwords']/$this->data['body']['count']*100),1);
    		} else {
    			$this->data['stat']['proportion'] = 0;
    		}
    		return $this->data['stat']['proportion'];
    	} else {
    		$this->error .= " |"."get_a_links needs to run before this function";
    		return false;
    	}
    }
    
    /**
     * get all the page meta tags and categorise these
     */
    function get_metatags(){
    	$meta = array();
    	$rx1 = '|<meta[^>]*?name\s*=\s*(["\'])([^\1>]*?)\1[^>]*?content\s*=\s*(["\'])([^\3>]*?)\3|sim';
    	$rx2 = '|<meta[^>]*?content\s*=\s*(["\'])([^\1>]*?)\1[^>]*?name\s*=\s*(["\'])([^\3>]*?)\3|sim';
    	preg_match_all($rx1,$this->html,$matches,PREG_SET_ORDER);
    	foreach($matches as $M){
    		//print_r($M); echo "<br /><br />";
    		$meta[strtolower($M[2])] =$M[4];
    	}
    	preg_match_all($rx2,$this->html,$matches,PREG_SET_ORDER);
    	foreach($matches as $M){
    		$meta[strtolower($M[2])] =$M[4];
    	}
    	$this->data['meta'] = $meta;
    	if (DICAP_GETMETAWORDS && creator_count($this->data['meta']['description'])) $this->getFrequencyData($this->data['meta']['description'],'description');
    	if (DICAP_GETMETAWORDS && creator_count($this->data['meta']['keywords'])) $this->getFrequencyData($this->data['meta']['keywords'],'keywords');
    	return count($meta);
    }
    
    /**
     * count the number of paragraphs on the page and get the cleaned paragraph text.
     */
    function get_p_count(){
    	return ($this->data['body']['p']? $this->data['body']['p']: 0);
    }
    
    /**
     * count the number of paragraphs on the page and get the cleaned paragraph text.
     */
    function get_paragraphs(){
    	$alltext = "";
    	$rx = '|<p.*>(\w.*)</p>|ismU';
    	$bodytxt = strlen($this->body)? $this->body: $this->html;
    	if(preg_match_all($rx, $bodytxt, $matches, PREG_SET_ORDER)){
    		foreach ($matches as $key=>$paragraph){
    			$px = trim($paragraph[1]);
    			$px = str_replace('</li>','.</li>',$px);
    			$alltext .= strlen($px)>25? ($px.(substr($px,-1,1)=='.'? ' ': '. ')): "";
    		}
    		$this->maintext = $this->cleanupText($alltext,false,true,' ',true);
    		return count($matches);
    	} else {
    		return false;
    	}
    }
    
    /**
     * get rel (relevance) attributes in the site link
     */
    function get_rel_tag($str){
    	//$rx = '|(rel=)(".*") href=(".*")|im';
    	$rx = '|rel=.([a-zA-Z0-9\s]{1,})|is';
    	if(preg_match($rx, $str, $matches)){
    		$data =$matches[1];
    		//echo "<h5>".$data."</h5>";
    		return $data;
    	} else {
    		//echo "<h5>** None **</h5>";
    		return false;
    	}
    }
    
    /**
     * get the inline styles and return the count
     */
    function get_styles(){
    	$rx = '|style\s?=\s?(["\'])([^\1>]*?)\1.*?>|is';
    	if(preg_match_all($rx, $this->html, $matches, PREG_SET_ORDER)){
    		//$this->arrayDump($matches);
    		foreach ($matches as $key=>$match){
    			$this->data['icss'][]=$match[2];
    		}
    		return count($matches);
    	} else {
    		return false;
    	}
    }
    
    /**
     * Get the title tag for the page
     */
    function get_title(){
    	//$rx = '|<head.*(<title>(.*?)<\/title>).*?</head>|sim';
    	$rx = '|<title>(.*?)<\/title>|sim';
    	if(preg_match($rx, $this->html, $matches)){
    		//$data = $matches[2];
    		$this->data['title'] = $matches[1];
    		if (DICAP_GETMETAWORDS && creator_count($this->data['title'])) $this->getFrequencyData($this->data['title'],'title');
    		return strlen($this->data['title']);
    	} else {
    		return false;
    	}
    }

    /**
     * Extract the body text from $html
     * @param string $findwords - get word tables from the body
     * @param boolean $decode - decode any html entities (default = true)
     * @return boolean
     */
    function setBodyText($findwords=false, $decode=true){
    	if (isset($this->html)){
    		$pattern = "<body.*>(.*?)<\/body>"; //including nested tags
    		preg_match_all("|".$pattern."|simU", $this->html, $data, PREG_PATTERN_ORDER);
    		if (strlen($data[1][0])){
    			$this->body = $decode? html_entity_decode($data[1][0]): $data[1][0];
    			if ($findwords) {
    				$this->wordarr['body'] = $this->createFrequencyArray($data[1][0],true,true);
    				$this->data['body']['count'] = array_sum($this->wordarr['body']);
    				$this->data['body']['p'] = $this->get_paragraphs();
    			}
    			return true;
    		} else {
    			$this->data['body']['count'] = 0;
    			return false;
    		}
    	} else {
    		return false;
    	}
    }

    // ---------------------------------------- Key Phrase Assessment ----------------------------------------
    
    /**
     * Iterate through the phrases in the text from 2 words up to $phrasemax to count the number of matching phrases.
     * Also add the key phrases to the FOM keyphrase table with the weighting multiplied by the number of words in the phrase.
     * Note that phrases with stop words in the start and end of the phrase are ignored if a stoplist is sent or
     * if trimstop is false (trimstop should always be true).
     * @param string $text source text (up to 500 words)
     * @param integer $phrasemax maximum number of words in each phrase (default 3)
     * @param string $trimstop trim stopwords from the start and end of every phrase
     * @param string $cleanall clean the text before analysing
     * @param string $weight weighting to apply to the phrase
     * @return array of phrases
     */
    function countMatchedPhrases($txt,$phrasemax=3,$trimstop=true,$cleanall=true,$weight=1){
    	$phrasearr = array();
    	$maxwords = 1000;
    	$trimstop = (creator_count($this->stopWords)==0)? false: $trimstop;
    	$removestop=false; //remove stop words at the start (also removes stop words from phrases)
    	$phrasemax = (intval($phrasemax)==0 || $phrasemax>5)? 5: $phrasemax;
    	if ($cleanall){
    		$txt = $this->cleanupText($txt,$removestop,true,' ',false,true,(5*$maxwords));
    	}
    	//convert to an array
    	$arr = explode(' ',$txt,$maxwords);
    	$num = count($arr);
    	$phrasemax = $phrasemax<$num? $phrasemax: $num;
    	for ($i = 2; $i<=$phrasemax; $i++) {
    		//box-car step through the text
    		for ($j = 0; $j<=($num-$i); $j++) {
    			//check that the first word is not a stop word - skip processing if it is
    			if (!$trimstop || !in_array($arr[$j],$this->stopWords)){
    				$phrase = '';
    				//build the phrase
    				for ($k = 0; $k<$i; $k++) {
    					//create the phrase with $i words
    					$phrase = $phrase.(strlen($phrase)? ' ': '').$arr[($j+$k)];
    				}
    				//$phrase = preg_replace('/\s+/', ' ',$phrase);
    				//check that the last word is not a stop word - skip including if it is
    				if (!$trimstop || !in_array($arr[($j+$i-1)],$this->stopWords)){
    					if (isset($phrasearr[$phrase])){
    						$phrasearr[$phrase]++;
    					} else {
    						$phrasearr[$phrase] = 1;
    					}
    					if (isset($this->keyphrases[$phrase])){
    						$this->keyphrases[$phrase] += $i*$weight;
    					} else {
    						$this->keyphrases[$phrase] = $i*$weight;
    					}
    				}
    			}
    		}
    	}
    	arsort($phrasearr);
    	return $phrasearr;
    }

    /**
     * Create word frequency arrays for the primary keyword tags and sections specified by $arr
     */
    function getFrequencyData($source,$tag){
    	$txt = (is_array($source))? implode(' ',$source): $source;
    	$this->wordarr[$tag] = $this->createFrequencyArray($txt);
    }
    
    /**
     * Sort and splice the keyphrases array
     * @param number $num the number of elements to retain. All if 0 (default).
     */
    function updatePhraseArray($num=0){
    	if (creator_count($this->keyphrases)){
    		arsort($this->keyphrases);
    		if ($num>0){
    			array_splice($this->keyphrases,$num);
    		}
    	}
    }

    // ---------------------------------------- External Services ----------------------------------------
    
    /**
     * The W3C resource for testing the DTD of the site is used to assess whether there are errors
     * and warnings in the DTD for the site
     */
    function validateDTD($site){
    	$rx1 = '|<td colspan="2" class="invalid">[^<](.*?)\sErrors,\s[^0-9]*?(.*?)\swarn|ism'; //invalid
    	$rx2 = '|<td colspan="2" class="invalid">[^<](.*?)\sError(.*?)<|ism'; //invalid
    	$rx3 = '|(Passed)|ism'; //valid
    	$rx4 = '|<td colspan="2" class="invalid">[^<](.*?)\sWarning(.*?)<|ism';
    	$url = "http://validator.w3.org/check?uri=".$site."&charset=%28detect+automatically%29&doctype=Inline&group=0";
    	$params = array('cookiefile'=>'','useragent'=>$this->uaList[0],'followlocation'=>1, 'returntransfer'=>1);
    	$html = $this->get_page_content($url,$params,true);
    	//echo htmlentities($html);
    	if(preg_match($rx1, $html, $matches)){
    		$data['errors'] = $matches[1];
    		$data['warnings'] = $matches[2];
    		//echo "<p>".print_r($matches)."</p>";
    		return $data;
    	} elseif(preg_match($rx2, $html, $matches)){
    		$data['errors'] = $matches[1];
    		$data['warnings'] = 0;
    		//echo "<p>".print_r($matches)."</p>";
    		return $data;
    	} elseif(preg_match($rx3, $html, $matches)){
    		$data['errors'] = 0;
    		$data['warnings'] = 0;
    		//echo "<p>".print_r($matches)."</p>";
    		return $data;
    	} elseif(preg_match($rx4, $html, $matches)){
    		$data['errors'] = 0;
    		$data['warnings'] = $matches[1];
    		//echo "<p>".print_r($matches)."</p>";
    		return $data;
    	} else {
    		return false;
    	}
    }
    
    // --------------------------------- Proxy methods ---------------------------------
    
    /**
     * Set or read a list of proxies. These proxies could eb a text file that comes from ScrapeBox
     * Types CURLPROXY_HTTP (default), CURLPROXY_SOCKS4, CURLPROXY_SOCKS5, CURLPROXY_SOCKS4A or CURLPROXY_SOCKS5_HOSTNAME.
     */
    function setProxies(){
    	$tmpfile = './inc/proxies.txt';
    	if (file_exists($tmpfile)){
    		$ipList = trim(file_get_contents($tmpfile));
    		//$out .= "<hr /><p>".$ipList."</p>";
    		//$this->proxyList = explode("\n",$ipList);
    		$this->proxyList = preg_split("'/\r\n|\n|\r/'", $ipList);
    		//echo "<hr /><h1>Proxies</h1><p>".print_r($this->proxyList,true)."</p>";
    	} else {
    		$this->proxyList = array(
    			0=>'91.106.42.22:80',
    			//17=>'',
    			//99=>''
    		);
    	}
    }
    
    function setUserAgents(){
    	$this->uaList = array(
    		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1",
		    "Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3",
		    "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36 OPR/42.0.2393.94",
		    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.170 Safari/537.36 OPR/53.0.2907.99",
		    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36",
		    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36",
		    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:65.0) Gecko/20100101 Firefox/65.0",
		    "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:64.0) Gecko/20100101 Firefox/64.0",
		    "Mozilla/5.0 (Windows NT 10.0; rv:63.0) Gecko/20100101 Firefox/63.0",
		    "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-en) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4",
    		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393",
		    "Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko",
		    "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko",
		    "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)",
		    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.110 Safari/537.36",
    		//99=>''
    	);
    }
    
    /**
     * Set up proxies and/or user agents for cURL
     * @param boolean $useProxies use proxies
     * @param boolean $useAgents use user agents
     */
    function useProxyAgents($useProxies=false,$useAgents=false){
    	$this->useProxies = $useProxies;
    	$this->useUserAgents = $useAgents;
    	if ($useProxies){
    		$this->setProxies();
    	} else {
    		$this->proxyList = array();
    	}
    	if ($useAgents){
    		$this->setUserAgents();
    	} else {
    		$this->uaList = array();
    	}
    }
    
    // ---------------------------------------- Factory Functions ----------------------------------------
    
    /**
     * Extract all the relevant site data from the page. Note that the site must be set before running this function.
     * @param array $keywords words to look for in the url (default empty)
     * @param number $phraseMax max number of words in a phrase (default 4)
     * @param boolean $removestop remove stop words from the word array (default true)
     * @return boolean
     */
    function getAllPageStats($keywords='',$phraseMax=4,$removestop=true){
    	$analysis = array('headings'=>1, 'validate'=>0, 'rationalise'=>1);
    	$stats = array();
    	$maxrex = 25;
    	if (isset($this->html)){
    		$base['Scheme']=$this->schemeX;
    		$base['Host']=$this->hostX;
    		$base['Extension']=$this->extX;
    		$base['Document type found']=$this->get_doctype();
    		if (strlen($keywords)){
    			$keywords = preg_replace('/\s+/', ' ',str_replace(array(',',';','.')," ", $keywords));
    			$chkurl = (strlen($this->subdomainX)? $this->subdomainX: '').(strlen($this->domainX)? $this->domainX: '');
    			$base['Keywords in URL']=$this->checkURLWords($chkurl, $keywords);
    		}
    		$base['Title length']=$this->get_title();
    		$base['Title found']=$base['Title length']? 1: 0;
    		$base['META data found']=$this->get_metatags();
    		
    		if($base['Description found']=strlen($this->data['meta']['description'])){
    			$base['Description length'] = $base['Description found'];
    			$tmp = $this->createFrequencyArray($this->data['meta']['description']);
    			$base['Description word count'] = array_sum($tmp);
    		} else {
    			$base['Description word count'] = '--';
    		}
    		if($base['Keywords found']=strlen($this->data['meta']['keywords'])>0){
    			$tmp = $this->createFrequencyArray($this->data['meta']['keywords']);
    			$base['Keyword count'] = array_sum($tmp);
    		} else {
    			$base['Keyword count'] = '--';
    		}
    		$base['External styles used']=$this->get_external_css();
    		if ($this->setBodyText(true)){
    			//body text found
    			//$this->arrayDump($this->pgdata['body']['words']);
    			$base['Words on the page']=$this->get_body_words();
    			$base['Paragraphs on page']=$this->get_p_count();
    		}
    		if($analysis['headings']){
    			$base['h1 header count']=$this->get_header('h1');
    			$base['h2 header count']=$this->get_header('h2');
    			$base['h3 header count']=$this->get_header('h3');
    			$base['h4 header count']=$this->get_header('h4');
    			$base['h5 header count']=$this->get_header('h5');
    			$base['h6 header count']=$this->get_header('h6');
    		}
    		$base['Internal styles used']=$this->get_styles();
    		$base['Images on page']=$this->get_image_tags();
    		$base['Missing alt tags']=creator_count($this->data['image']['src'])-count($this->data['image']['alt']);
    		$base['Links on page']=$this->get_a_links();
    		$base['External page links']=count($this->data['links']['ext']['url']);
    		$base['Internal page links']=count($this->data['links']['int']['url']);
    		$base['Percentage words in links']= $this->get_link_proportion();
    		if($analysis['validate']){
    			$base['Valid DTD found'] = $this->get_dtd_validation();
    			if ($base['Valid DTD found']){
    				$base['Number of errors'] = $this->data['validation']['errors'];
    				$base['Number of warnings'] = $this->data['validation']['warnings'];
    			} else {
    				$base['Number of errors'] = 999;
    				$base['Number of warnings'] = 999;
    			}
    		}
    		//get the phrase frequency arrays
    		$stats['phrases']['title'] = $this->countMatchedPhrases($this->data['title'],$phraseMax,$removestop,true,3);
    		$stats['phrases']['desc'] = $this->countMatchedPhrases($this->data['meta']['description'],$phraseMax,$removestop,true,2);
    		$tempArr = $this->countMatchedPhrases($this->maintext,$phraseMax,$removestop,true,1);
    		if($analysis['rationalise']){
    			foreach ($tempArr as $phrase => $cnt) {
    				if ($cnt>1){
    					$stats['phrases']['body'][$phrase] = $cnt;
    				} elseif(isset($stats['phrases']['title'][$phrase]) || isset($stats['phrases']['desc'][$phrase])){
    					$stats['phrases']['body'][$phrase] = $cnt;
    				}
    			}
    		} else {
    			$stats['phrases']['body'] = $tempArr;
    		}
    		unset($tempArr);
    		
    		$this->updatePhraseArray($maxrex); //sort the key phrase array and restrict to the top maxrex
    		$stats['keyphrases'] = $this->keyphrases;
    		$stats['words'] = $this->wordarr;
    		$this->arraySortAndUpdate($stats['words']['body'],$maxrex); //sort the body phrase array and restrict to the top maxrex
    		$this->arraySortAndUpdate($stats['phrases']['body'],$maxrex); //sort the body word array and restrict to the top maxrex
    		$stats['base'] = $base;
    		$stats['raw'] = count($this->data)? $this->data: array();
    		return $stats;
    	} else {
    		return false;
    	}
    }
    
    
} //end of class

