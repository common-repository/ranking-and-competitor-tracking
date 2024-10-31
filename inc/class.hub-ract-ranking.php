<?php
/**
 * Project: Hub5050 Ranking and Competitor Tracking (class.hub-ract-ranking.php)
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

class hub_ract_ranking extends hub_ract_audit {
	var $engine = array();
	var $total_to_search = 20;
	var $searchURL = '';
	var $phrase = '';
	var $continue_test = true; //if this becomes false stop recording data
	var $arrURL = array(); //array of all found URLs
	var $region = 'all';
	var $retsites = 5; //number of top ranking sites to return
	private $urlExcludes = array('google','dmoz','wikipedia','bing','bloomberg','reuters','nasdaq','ft.com','slideshare','linkedin',
		'facebook','twitter', 'prnewswire','yellowpages','buzzfile','kompass','globenewswire','crunchbase','sec.gov','theglobeandmail',
		'glassdoor.com');
	public $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:53.0) Gecko/20100101 Firefox/53.0';
	private $socialSites = array('slideshare.net','linkedin.com','facebook.com','twitter.com','twitter.com', 'youtube.com','instagram.com','plus.google.com');
	private $allowedEngines = array('google'); //'google','bing'
	private $allowedRegions = array();
	var $url_list = array();
	var $tmp = array();

    /**
     * Constructor for Metrics
     */
    function __construct($debug = false){
    	parent::__construct(null,$debug);
        $this->error = '';
        $this->allowedRegions = array_keys(hub_ract_base::$se_regions);
    }

    /**
     * Set the parameters for the search engine selected
     * @param string $engine - google or bing
     * @param string $region - region to search
     * @param boolean $sandbox - testing environment
     * @return boolean
     */
    function setEngine($engine, $region, $sandbox=false){
    	$outcome = true;
    	$this->setRegion($region);
    	if (strtolower($engine) == 'google') {
		    $ext = isset( hub_ract_base::$se_regions[ $this->region ] ) ? hub_ract_base::$se_regions[ $this->region ]['google'] : 'com';
		    if ( $sandbox ) {
			    //test url so that Google is not accessed directly
			    $this->engine['url'] = 'http://repixa.com/google.php?q=*1*&num=*2*&start=*3*';
		    } else {
			    //$this->engine['url'] = 'https://www.google.'.$ext.'/search?hl=en&num=*2*&q=*1*&oq=*1*';
			    $this->engine['url'] = 'https://www.google.' . $ext . '/search?hl=en&num=*2*&q=*1*&start=*3*';
		    }
		    $this->engine['regex'] = '|<a href=\"\/url\?q=(.*)&amp;sa=.*>.*<div class=\".*\">(.*)<\/div>.*<div class=\".*\">(.*)<\/div>|siU';
		    $this->engine['matchurl']  = 1;
		    $this->engine['matchtxt']  = 2;
		    $this->engine['failtest']  = 'CaptchaRedirect'; //Phrase to find if test is to stop
		    $this->engine['pagehits']  = $this->total_to_search <= 50 ? round( $this->total_to_search / 10, 0 ) * 10 : 50;
		    $this->engine['useragent'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1';
		    $this->engine['referrer']  = 'http://www.google.com/';
    	} elseif (strtolower($engine) == 'bing'){
    		$loc = isset(hub_ract_base::$se_regions[$this->region])? '&cc='.hub_ract_base::$se_regions[$this->region]['bing']: '';
    		if ($sandbox){
    			//test url so that Bing is not accessed directly
    			$this->engine['url'] = 'http://repixa.com/bing.php?q=*1*&go=Submit&qs=n&pq=*1*&count=*2*&first=*4*'.$loc.'&FORM=PERE';
    		} else {
    			//$this->engine['url'] = 'http://www.bing.com/search?q=*1*'.$loc.'&filt=all&first=*4*&count=*2*';
    			$this->engine['url'] = 'https://www.bing.com/search?q=*1*&go=Submit&qs=n&pq=*1*&count=*2*&first=*4*'.$loc.'&FORM=PERE';
    		}
//    		$this->engine['regex'] = '|<li class="b_algo">.*<h2>.*<a href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>|siU';
//		    $this->engine['matchurl'] = 2;
//		    $this->engine['matchtxt'] = 3;
		    $this->engine['regex'] = '|<li class=\"b_algo\">.*<h2><a.*href=\"(.*)\".*>(.*)<\/a>|siU';
    		$this->engine['failtest'] = false;
    		$this->engine['pagehits'] = $this->total_to_search<=50? round($this->total_to_search/10,0)*10: 50;
    		$this->engine['matchurl'] = 1;
    		$this->engine['matchtxt'] = 2;
    		//$this->engine['useragent'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:15.0) Gecko/20100101 Firefox/15.0.1';
    		//$this->engine['referrer'] = 'http://www.bing.com/';
    	} else {
    		$outcome = false;
    	}
	    //creator_update_log_file('DEBUG', array( 'SEARCH ENGINE' =>strtoupper($engine)),'append',true);
	    $this->debug['SEARCH ENGINE'] = $engine;
    	return $outcome;
    }
    
    /**
     * Set the search query - key phrase
     */
    function setQueryPhrase($phrase, $removestop=false, $stripslashes=true) {
    	$querycleaner = array(" "=>"+","\""=>"%22","'"=>"%22");
    	if (strlen($phrase)){
    		$phrase = $stripslashes? stripslashes($phrase): $phrase;
    		$phrase = strtr(strtolower($phrase),$querycleaner);
    		$this->phrase = $phrase; //set the phrase attribute
    	}
	    //creator_update_log_file('DEBUG', array( 'PHRASE' =>$phrase),'append',true);
	    $this->debug['PHRASE'] = $phrase;
    	//return $this->phrase;
    }
    
    /**
     * Set the region for the search
     */
    function setRegion($region) {
    	$this->region = in_array($region, $this->allowedRegions)? $region: 'us';
	    //creator_update_log_file('DEBUG', array( 'REGION' =>strtoupper($this->region)),'append',true);
	    $this->debug['REGION'] = strtoupper($this->region);
    	//return $this->region;
    }
    
    /**
     * Set the number of records to include in the search query
     * @param integer $total - records to search between 10 and 50
     */
    function setSearchTotal($total){
    	if(is_numeric($total) && $total>=10 && $total<=50){
    		$this->total_to_search = round($total/10,0)*10;;
    	} else {
    		$this->total_to_search = 20;
    	}
	    //creator_update_log_file('DEBUG', array( 'SEARCH TOTAL' =>$this->total_to_search),'append',true);
	    $this->debug['SEARCH TOTAL'] = $this->total_to_search;
    }
    
    // --------------------------------- SERP Methods ---------------------------------
    
    /**
     * Create the URL from the by substituting the necessary parts
     * @param string $url - search engine url template
     * @param string $phrase - string to search for
     * @param integer $hits - number of results on the page
     * @param integer $cnt - start point (used by Google, Bing and Yahoo)
     * @param integer $page - start page (used by Ask)
     * @return mixed|boolean - url if successful else false
     */
    function constructURL($url,$phrase,$hits,$cnt,$page) {
    	if (strlen($url)>4){
    		//$url = str_ireplace("*1*",urlencode($phrase),$url); //NOTE urlencode added on 02/05/17 - causes problems with URL
    		$url = str_ireplace("*1*",$phrase,$url);
    		$url = str_ireplace("*2*",$hits,$url); //number of results to fetch
    		$url = str_ireplace("*3*",$cnt,$url); //Google - search item start value - 1
    		$url = str_ireplace("*4*",$cnt+1,$url); //Bing and Yahoo - search item start value
    		$url = str_ireplace("*5*",$page,$url); //Ask - page number
		    //creator_update_log_file('DEBUG', array( 'SEARCH URL' =>$url),'append',true);
		    $this->debug['SEARCH URL'] = $url;
    		return $url;
    	} else {
    		return false;
    	}
    }
    
    /**
     * GET THE SEARCH RESULTS AS HTML from the SERP page defined by searchURL and check if it is valid
     *
     * @param boolean $strip - strip all tags from the html if set to true
     * @return boolean
     */
    function checkSERPContent($strip=false){
    	$outcome = false;
    	if (isset($this->searchURL) && strlen($this->searchURL)){
    		$params = array('cookiefile'=>'','useragent'=>$this->userAgent,'followlocation'=>true, 'header'=>false, 'returntransfer'=>true, 'ssl_verifypeer'=>0);
    		//$params = array('header'=>false, 'encoding'=>'','connecttimeout'=>15,'timeout'=>60,'cookiefile'=>'./tmp/cookie.txt');
    		$pageFetched = $this->get_page_content($this->searchURL,$params,false);
		    $this->debug['PAGE FETCH OUTCOME'] = $pageFetched? 'SUCCESS': 'FAIL';
		    $this->debug['HTML LENGTH 1'] = $this->htmlLength;
    		$this->getHTMLBodyOnly();
		    if ($this->htmlLength>32){
			    //$this->html = strtolower($this->html);
			    $this->html = $strip? strip_tags($this->html): $this->html;
			    if (stristr($this->html, $this->engine['failtest'])){
				    $this->continue_test = false; //stop recording data
				    $this->error .= " | ERROR: Failtest string detected - test stopped";
			    } else {
				    $outcome = true;
			    }
		    } else {
			    $this->continue_test = false;
			    $this->error .= " | ERROR: Blank content retrieved";
		    }
    	}
        if ($outcome){
		    $this->debug['HTML LENGTH'] = strlen($this->html);
//	        creator_debug_log('DEBUG AGENT', htmlentities($this->userAgent));
//	        creator_debug_log('DEBUG LENGTH', strlen($this->html));
//	        creator_debug_log('DEBUG HTML', substr($this->html,1000,200));
        } else {
		    //creator_update_log_file('DEBUG', array( 'HTML LENGTH' =>'Failed to retrieve HTML'),'append',true);
		    $this->debug['HTML LENGTH'] = 'Failed to retrieve HTML';
        }
    	return $outcome;
    }

    /**
     * Find the ranking results based on matching the reglar expression templates in the html
     * @param string $engine search engine name
     * @return boolean - found or not
     */
    function getRXMatches($domain, $engine, $retsites=null){
    	$outcome = false;
    	$matches = array();
    	$checkpos = 0; $i = 0;
    	$this->retsites = (isset($retsites) && $retsites>0)? (int)$retsites: $this->retsites;
    	$found = false; //ensures only the first instance (highest ranking) position is recorded
    	$rnk = 50; //creates an arbitrary rank scale for competitors based on the number and position of occurrences
    	if ($this->isValidURL($domain) && ($urldata=$this->parseURL($domain))!==false){
    		$domainX = $urldata['domain'];
    		$this->debug['RX LOOKUP DOMAIN'] = $domainX;
    		if (preg_match_all($this->engine['regex'], $this->html, $matches, PREG_SET_ORDER)) {
    			$uid = $this->engine['matchurl']; //url - match position in matches (just for shorthand)
    			$tid = $this->engine['matchtxt']; //text - description match position in matches (just for shorthand)
    			$this->results['rank'] = 0; //start with a no-ranking result
//			    $this->debug['MATCHES'] = $matches;
    			foreach($matches as $match) {
    				$i++; //iteration counter
				    if ( filter_var($match[$uid], FILTER_VALIDATE_URL) ) {
					    $checkpos++;
					    $rnk = $rnk/2;
					    $pos = strpos($match[$uid],'&')? strpos($match[$uid],'&'): 256;
					    $match[$uid] = trim(substr($match[$uid],0, $pos));
					    $match[$tid] = trim(preg_replace('/[\t\n\r\s]+/', ' ', strip_tags($match[$tid])));
					    $this->debug['RX MATCHES '.$checkpos] = $match[$uid];
					    $this->debug['RX TEXT '.$checkpos] = $match[$tid];
					    if(stristr($match[$uid],$domainX) && !$found) {
						    $found= true;
						    $this->results['rank'] = $checkpos; //page rank found in set
						    $this->debug['RX FOUND POSITION '.$checkpos] = ($this->domainX . ' ----------> ' . $checkpos);
					    }
					    if ($this->retsites > 0) {
//    						if ($engine=="google"){
//    							//extract the url from the Google URL
//							    $pattern = "|(.*)&amp;(.*)|siU";
//    							preg_match($pattern, $match[$uid], $mxx);
//    							$ix = substr($mxx[1],0,4)=="http"? $mxx[1]: "http://".$mxx[1];
//    							$this->url_list[$checkpos] = $ix;
//    							//$parts = parse_url(str_replace('&amp;','&',$ix));
//    							//$ix= $parts['scheme']."://".$parts['host'];
//    						} else {
//    							//$ix = $match[$uid];
//    							$ix = substr($match[$uid],0,4)=="http"? $match[$uid]: "http://".$match[$uid];
//    							$this->url_list[$checkpos] = $ix;
//    						}
						    $ix = substr($match[$uid],0,4)=="http"? $match[$uid]: "http://".$match[$uid];
						    $this->url_list[$checkpos] = $ix;
						    $this->results['contenders'][$ix] = is_null($this->results['contenders'][$ix]['rnk'])? (int) $checkpos: $this->results['contenders'][$ix];
//						    $this->results['contenders'][$ix] = array(
//						    	'fom' => is_null($this->results['contenders'][$ix]['fom'])? $rnk: $this->results['contenders'][$ix]['fom']+$rnk,
//						    	'txt' => preg_replace('/\s+/S', " ", trim(strip_tags($match[$tid]))),
//						    	'rnk' => is_null($this->results['contenders'][$ix]['rnk'])? (int) $checkpos: $this->results['contenders'][$ix]['rnk']);
						    foreach ( $this->socialSites as $social_site ) {
							    if (stristr($ix,$social_site)){
								    $this->results['social'][$ix] = (int) $checkpos;
							    }
						    }
					    }
				    } else {
					    $this->debug['URL '.$i.' FAIL'] = $match[$uid];
				    }
    			}
    			$outcome = true;
    			if (is_array($this->results['contenders']) && count($this->results['contenders'])>$this->retsites) {
    				array_splice($this->results['contenders'], $this->retsites);
    			}
    		} else {
    			$this->error .= ' | ERROR: RX error or matches not found';
    			$this->debug['RX MATCHES'] = 'Matches not found';
    		}
    	} else {
    		$this->error .= ' | ERROR: domain specified incorrectly';
    	}
		return $outcome;
    }
    
    /**
     * Find the ranking position of each of the competitors from the data in url_list
     * @param array $competitors - this must contain the domain and not the url
     */
	function checkCompetitorRanks($competitors){
		foreach ($competitors as $k => $competitor) {
			$this->results['rivals'][$competitor] = 0;
		}
		if (is_array($competitors) && count($competitors) && count($this->url_list)){
			foreach ($this->url_list as $rnk => $url) {
				foreach ($competitors as $k => $competitor) {
					if ($this->results['rivals'][$competitor] == 0){
						$parsed_url = $this->parseURL(strtolower($competitor));
						$host = $parsed_url['host'];
						//check and update if found
						if (stristr($url,$host)){
							$this->results['rivals'][$competitor] = $rnk;
						}
					}
				}
			}
		}
	}
    
    /**
     * Select the phrase from Phrases that has the longest time since the last update 
     * or select the first phrase that has not yet been updated
     * 
     * @param array $phrases - phrases from which to select
     * @param array $results - list of phrases with results retrieved from the SEOData option
     *
     * @return text selected phrase
     */
    function selectPhraseX($phrases, $results){
    	$found = false;
    	$kwd = '';
    	$last_time = time();
    	foreach($phrases as $k => $phrase ){
    		if (strlen($phrase)){
			    if ( !is_array( $results) || count( $results) == 0){
				    //first run use / accept the first phrase
				    $kwd = $phrase;
				    $found = true;
			    } else {
				    if (!$found){
					    if  (! isset( $results[$phrase])){
						    //new term just use it
						    $kwd = $phrase;
						    $found = true;
					    } else {
						    //existing term check time and select the term
						    if ( $results[$phrase]['timestamp'] < $last_time){
							    //existing term, find oldest by updating $last_time and comparing
							    $last_time = $results[$phrase]['timestamp'];
							    $kwd       = $phrase;
						    }
					    }
				    }
			    }
		    }
    	}
    	return (strlen($kwd)? $kwd: false);
    }

    
    // ---------------------------------------- Factory Functions ----------------------------------------
    
    /**
     * Get the ranking and competitor sites for a particular domain and phrase
     * @param string $domain search domain to rank
     * @param string $phrase ranking phrase search for on the search engine
     * @param number $phraseMax max number of words in a phrase (default 4)
     * @param boolean $removestop remove stop words from the word array (default true)
     * @return boolean
     */
    function getSERPData($domain, $engine, $region, $phrase, $competitors=array(), $ret_sites=10, $sandbox=false) {
    	$outcome = false;
    	$domain = (substr($domain,0,4)=="http")? $domain: "http://".$domain;
    	if ($this->isValidURL($domain)) {
    		if (in_array($engine, $this->allowedEngines) && in_array($region, $this->allowedRegions)){
			    //creator_update_log_file('DEBUG', array( 'ENGINE' =>$engine),'append',true);
			    $this->debug['SERP ENGINE'] = $engine;
    			$this->setSite($domain,'',false); //set the site but do not get the page content
    			$this->setEngine($engine, $region, $sandbox); //set the engine and all the parameters for the engine regex
    			$this->setSearchTotal(50);
    			$this->setQueryPhrase($phrase);
    			if (strlen($this->phrase)){
    				$this->searchURL = $this->constructURL($this->engine['url'],$this->phrase,$this->engine['pagehits'],0,1);
    				if ($this->checkSERPContent() && $this->continue_test){
    					//valid html retrieved from the engine
    					error_reporting(E_ALL ^ E_NOTICE);
					    //$this->debug['HTML'] = htmlentities($this->html); //The actual HTML *************************************************
					    //find matching occurrences of the domain in the search results
					    $ret_sites = (isset($ret_sites) && $ret_sites>0 && $ret_sites<=50)? $ret_sites: 10; //number of top contenders to return
    					$this->getRXMatches($domain,$engine,$ret_sites); //domain is parsed in this function
					    //creator_update_log_file('DEBUG', array( 'RANK' =>$this->results['rank']),'append',true);
					    $this->debug['SERP RANK'] = $this->results['rank'];
    					if (is_array($competitors) && count($competitors)){
    						$this->checkCompetitorRanks($competitors);
    					}
	    			} else {
	    				$this->error .= ' | ERROR: rank check stopped';
	    			}
    			} else {
    				$this->error .= ' | ERROR: key phrase specified incorrectly';
    			}
    		} else {
    			$this->error .= ' | ERROR: search engine or region incorrectly specified';
    		}
    	} else {
    		$this->error .= ' | ERROR: invalid domain provided';
    	}
    	if (strlen($this->error)) {
		    $this->debug['SERP ERROR'] = $this->error;
    	}
    }
    
} //end of class

