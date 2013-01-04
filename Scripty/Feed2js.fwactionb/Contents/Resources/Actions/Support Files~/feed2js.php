<?php
/*  Feed2JS : RSS feed to JavaScript src file

  CONCATENATED ALL LIBS INTO THIS FILE
  Walter Lee Davis 2013 January 3

	VERSION 2.3 (2011 jun 9)
	
	ABOUT
	This PHP script will take an RSS feed as a value of src="...."
	and return a JavaScript file that can be linked 
	remotely from any other web page. Output includes
	site title, link, and description as well as item site, link, and
	description with these outouts contolled by extra parameters.
	
	Developed by Alan Levine initially released 13.may.2004
	http://cogdogblog.com/
	
	PRIMARY SITE:
	http://feed2js.org/
	 
	CODE:
	http://code.google.com/p/feed2js/
     
	Feed2JS makes use of the Magpie RSS parser from
	 http://magpierss.sourceforge.net/
	
   ------------- small print ---------------------------------------
	GNU General Public License 
	Copyright (C) 2004-2010 Alan Levine
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details
	http://www.gnu.org/licenses/gpl.html
	------------- small print ---------------------------------------

*/

// ERROR CHECKING FOR NO SOURCE -------------------------------

$script_msg = '';
$src = (isset($_GET['src'])) ? $_GET['src'] : '';

// trap for missing src param for the feed, use a dummy one so it gets displayed.
if (!$src or strpos($src, 'http://')!=0) $src=  'http://example.com/index.html';# . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . '/nosource.php';

// test for malicious use of script tags
if (strpos($src, '<script>')) {
	$src = preg_replace("/(\<script)(.*?)(script>)/si", "SCRIPT DELETED", "$src");
	die("Warning! Attempt to inject javascript detected. Aborted and tracking log updated.");
}

// MAGPIE  SETUP ----------------------------------------------------
// access configuration settings
#require_once('feed2js_config.php');
/* Feed2JS : RSS feed to JavaScript Configuration include

	Use this include to establish server specific paths
	and other common functions used by the feed2js.php
	
	See main script for all the gory details or the Google Code site
	http://code.google.com/p/feed2js/
	
	created 10.sep.2004
*/


// MAGPIE SETUP ----------------------------------------------------
// Define path to Magpie files and load library
// The easiest setup is to put the 4 Magpie include
// files in the same directory:
// define('MAGPIE_DIR', './')

// Otherwise, provide a full valid file path to the directory
// where magpie sites

define('MAGPIE_DIR',  './magpie/');

// access magpie libraries
#require_once(MAGPIE_DIR.'rss_fetch.inc');
/*
 * Project:     MagpieRSS: a simple RSS integration tool
 * File:        rss_fetch.inc, a simple functional interface
                to fetching and parsing RSS files, via the
                function fetch_rss()
 * Author:      Kellan Elliott-McCrea <kellan@protest.net>
 * License:     GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * magpierss-general@lists.sourceforge.net
 *
 */
 
// Setup MAGPIE_DIR for use on hosts that don't include
// the current path in include_path.
// with thanks to rajiv and smarty
if (!defined('DIR_SEP')) {
    define('DIR_SEP', DIRECTORY_SEPARATOR);
}

if (!defined('MAGPIE_DIR')) {
    define('MAGPIE_DIR', dirname(__FILE__) . DIR_SEP);
}

#require_once( MAGPIE_DIR . 'rss_parse.inc' );
/**
* Project:     MagpieRSS: a simple RSS integration tool
* File:        rss_parse.inc  - parse an RSS or Atom feed
*               return as a simple object.
*
* Handles RSS 0.9x, RSS 2.0, RSS 1.0, and Atom 0.3
*
* The lastest version of MagpieRSS can be obtained from:
* http://magpierss.sourceforge.net
*
* For questions, help, comments, discussion, etc., please join the
* Magpie mailing list:
* magpierss-general@lists.sourceforge.net
*
* @author           Kellan Elliott-McCrea <kellan@protest.net>
* @version          0.7a
* @license          GPL
*
*/

define('RSS', 'RSS');
define('ATOM', 'Atom');

#require_once (MAGPIE_DIR . 'rss_utils.inc');
/*
 * Project:     MagpieRSS: a simple RSS integration tool
 * File:        rss_utils.inc, utility methods for working with RSS
 * Author:      Kellan Elliott-McCrea <kellan@protest.net>
 * Version:     0.51
 * License:     GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * magpierss-general@lists.sourceforge.net
 */


/*======================================================================*\
    Function: parse_w3cdtf
    Purpose:  parse a W3CDTF date into unix epoch

    NOTE: http://www.w3.org/TR/NOTE-datetime
\*======================================================================*/

function parse_w3cdtf ( $date_str ) {
    
    # regex to match wc3dtf
    $pat = "/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(:(\d{2}))?(?:([-+])(\d{2}):?(\d{2})|(Z))?/";
    
    if ( preg_match( $pat, $date_str, $match ) ) {
        list( $year, $month, $day, $hours, $minutes, $seconds) = 
            array( $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
        
        # calc epoch for current date assuming GMT
        $epoch = gmmktime( $hours, $minutes, $seconds, $month, $day, $year);
        
        $offset = 0;
        if ( $match[10] == 'Z' ) {
            # zulu time, aka GMT
        }
        else {
            list( $tz_mod, $tz_hour, $tz_min ) =
                array( $match[8], $match[9], $match[10]);
            
            # zero out the variables
            if ( ! $tz_hour ) { $tz_hour = 0; }
            if ( ! $tz_min ) { $tz_min = 0; }
        
            $offset_secs = (($tz_hour*60)+$tz_min)*60;
            
            # is timezone ahead of GMT?  then subtract offset
            #
            if ( $tz_mod == '+' ) {
                $offset_secs = $offset_secs * -1;
            }
            
            $offset = $offset_secs; 
        }
        $epoch = $epoch + $offset;
        return $epoch;
    }
    else {
        return -1;
    }
}
#end rss_utils.inc
/**
* Hybrid parser, and object, takes RSS as a string and returns a simple object.
*
* see: rss_fetch.inc for a simpler interface with integrated caching support
*
*/
class MagpieRSS {
    var $parser;
    
    var $current_item   = array();  // item currently being parsed
    var $items          = array();  // collection of parsed items
    var $channel        = array();  // hash of channel fields
    var $textinput      = array();
    var $image          = array();
    var $feed_type;
    var $feed_version;
    var $encoding       = '';       // output encoding of parsed rss
    
    var $_source_encoding = '';     // only set if we have to parse xml prolog
    
    var $ERROR = "";
    var $WARNING = "";
    
    // define some constants
    
    var $_CONTENT_CONSTRUCTS = array('content', 'summary', 'info', 'title', 'tagline', 'copyright');
    var $_KNOWN_ENCODINGS    = array('UTF-8', 'US-ASCII', 'ISO-8859-1');

    // parser variables, useless if you're not a parser, treat as private
    var $stack              = array(); // parser stack
    var $inchannel          = false;
    var $initem             = false;
    var $incontent          = false; // if in Atom <content mode="xml"> field 
    var $intextinput        = false;
    var $inimage            = false;
    var $current_namespace  = false;
    

    /**
     *  Set up XML parser, parse source, and return populated RSS object..
     *   
     *  @param string $source           string containing the RSS to be parsed
     *
     *  NOTE:  Probably a good idea to leave the encoding options alone unless
     *         you know what you're doing as PHP's character set support is
     *         a little weird.
     *
     *  NOTE:  A lot of this is unnecessary but harmless with PHP5 
     *
     *
     *  @param string $output_encoding  output the parsed RSS in this character 
     *                                  set defaults to ISO-8859-1 as this is PHP's
     *                                  default.
     *
     *                                  NOTE: might be changed to UTF-8 in future
     *                                  versions.
     *                               
     *  @param string $input_encoding   the character set of the incoming RSS source. 
     *                                  Leave blank and Magpie will try to figure it
     *                                  out.
     *                                  
     *                                   
     *  @param bool   $detect_encoding  if false Magpie won't attempt to detect
     *                                  source encoding. (caveat emptor)
     *
     */
    function MagpieRSS ($source, $output_encoding='ISO-8859-1', 
                        $input_encoding=null, $detect_encoding=true) 
    {   
        # if PHP xml isn't compiled in, die
        #
        if (!function_exists('xml_parser_create')) {
            $this->error( "Failed to load PHP's XML Extension. " . 
                          "http://www.php.net/manual/en/ref.xml.php",
                           E_USER_ERROR );
        }
        
        list($parser, $source) = $this->create_parser($source, 
                $output_encoding, $input_encoding, $detect_encoding);
        
        
        if (!is_resource($parser)) {
            $this->error( "Failed to create an instance of PHP's XML parser. " .
                          "http://www.php.net/manual/en/ref.xml.php",
                          E_USER_ERROR );
        }

        
        $this->parser = $parser;
        
        # pass in parser, and a reference to this object
        # setup handlers
        #
        xml_set_object( $this->parser, $this );
        xml_set_element_handler($this->parser, 
                'feed_start_element', 'feed_end_element' );
                        
        xml_set_character_data_handler( $this->parser, 'feed_cdata' ); 
    
        $status = xml_parse( $this->parser, $source );
        
        if (! $status ) {
            $errorcode = xml_get_error_code( $this->parser );
            if ( $errorcode != XML_ERROR_NONE ) {
                $xml_error = xml_error_string( $errorcode );
                $error_line = xml_get_current_line_number($this->parser);
                $error_col = xml_get_current_column_number($this->parser);
                $errormsg = "$xml_error at line $error_line, column $error_col";

                $this->error( $errormsg );
            }
        }
        
        xml_parser_free( $this->parser );

        $this->normalize();
    }
    
    function feed_start_element($p, $element, &$attrs) {
        $el = $element = strtolower($element);
        $attrs = array_change_key_case($attrs, CASE_LOWER);
        
        // check for a namespace, and split if found
        $ns = false;
        if ( strpos( $element, ':' ) ) {
            list($ns, $el) = split( ':', $element, 2); 
        }
        if ( $ns and $ns != 'rdf' ) {
            $this->current_namespace = $ns;
        }
            
        # if feed type isn't set, then this is first element of feed
        # identify feed from root element
        #
        if (!isset($this->feed_type) ) {
            if ( $el == 'rdf' ) {
                $this->feed_type = RSS;
                $this->feed_version = '1.0';
            }
            elseif ( $el == 'rss' ) {
                $this->feed_type = RSS;
                $this->feed_version = $attrs['version'];
            }
            elseif ( $el == 'feed' ) {
                $this->feed_type = ATOM;
                $this->feed_version = $attrs['version'];
                $this->inchannel = true;
            }
            return;
        }
    
        if ( $el == 'channel' ) 
        {
            $this->inchannel = true;
        }
        elseif ($el == 'item' or $el == 'entry' ) 
        {
            $this->initem = true;
            if ( isset($attrs['rdf:about']) ) {
                $this->current_item['about'] = $attrs['rdf:about']; 
            }
        }
        
        // if we're in the default namespace of an RSS feed,
        //  record textinput or image fields
        elseif ( 
            $this->feed_type == RSS and 
            $this->current_namespace == '' and 
            $el == 'textinput' ) 
        {
            $this->intextinput = true;
        }
        
        elseif (
            $this->feed_type == RSS and 
            $this->current_namespace == '' and 
            $el == 'image' ) 
        {
            $this->inimage = true;
        }

// ----- additional code to handle RSS enclosures -- added by alan levine 
         elseif (
            $this->feed_type == RSS and
            $el == 'enclosure' )
        {
            $this->current_item[$el][] = $attrs;
            $this->incontent = $el;
        }
 
 
 
        # handle atom content constructs
        elseif ( $this->feed_type == ATOM and in_array($el, $this->_CONTENT_CONSTRUCTS) )
        {
            // avoid clashing w/ RSS mod_content
            if ($el == 'content' ) {
                $el = 'atom_content';
            }
            
            $this->incontent = $el;
            
            
        }
        
        // if inside an Atom content construct (e.g. content or summary) field treat tags as text
        elseif ($this->feed_type == ATOM and $this->incontent ) 
        {
            // if tags are inlined, then flatten
            $attrs_str = join(' ', 
                    array_map('map_attrs', 
                    array_keys($attrs), 
                    array_values($attrs) ) );
            
            $this->append_content( "<$element $attrs_str>"  );
                    
            array_unshift( $this->stack, $el );
        }
        
        // Atom support many links per containging element.
        // Magpie treats link elements of type rel='alternate'
        // as being equivalent to RSS's simple link element.
        //
        elseif ($this->feed_type == ATOM and $el == 'link' ) 
        {
            if ( isset($attrs['rel']) and $attrs['rel'] == 'alternate' ) 
            {
                $link_el = 'link';
            }
            else {
                $link_el = 'link_' . $attrs['rel'];
            }
            
            $this->append($link_el, $attrs['href']);
        }
        // set stack[0] to current element
        else {
            array_unshift($this->stack, $el);
        }
    }
    

    
    function feed_cdata ($p, $text) {
        if ($this->feed_type == ATOM and $this->incontent) 
        {
            $this->append_content( $text );
        }
        else {
            $current_el = join('_', array_reverse($this->stack));
            $this->append($current_el, $text);
        }
    }
    
    function feed_end_element ($p, $el) {
        $el = strtolower($el);
        
        if ( $el == 'item' or $el == 'entry' ) 
        {
            $this->items[] = $this->current_item;
            $this->current_item = array();
            $this->initem = false;
        }
        elseif ($this->feed_type == RSS and $this->current_namespace == '' and $el == 'textinput' ) 
        {
            $this->intextinput = false;
        }
        elseif ($this->feed_type == RSS and $this->current_namespace == '' and $el == 'image' ) 
        {
            $this->inimage = false;
        }
        elseif ($this->feed_type == ATOM and in_array($el, $this->_CONTENT_CONSTRUCTS) )
        {   
            $this->incontent = false;
        }
        elseif ($el == 'channel' or $el == 'feed' ) 
        {
            $this->inchannel = false;
        }
        elseif ($this->feed_type == ATOM and $this->incontent  ) {
            // balance tags properly
            // note:  i don't think this is actually neccessary
            if ( $this->stack[0] == $el ) 
            {
                $this->append_content("</$el>");
            }
            else {
                $this->append_content("<$el />");
            }

            array_shift( $this->stack );
        }
        else {
            array_shift( $this->stack );
        }
        
        $this->current_namespace = false;
    }
    
    function concat (&$str1, $str2="") {
        if (!isset($str1) ) {
            $str1="";
        }
        $str1 .= $str2;
    }
    
    
    
    function append_content($text) {
        if ( $this->initem ) {
            $this->concat( $this->current_item[ $this->incontent ], $text );
        }
        elseif ( $this->inchannel ) {
            $this->concat( $this->channel[ $this->incontent ], $text );
        }
    }
    
    // smart append - field and namespace aware
    function append($el, $text) {
        if (!$el) {
            return;
        }
        if ( $this->current_namespace ) 
        {
            if ( $this->initem ) {
                $this->concat(
                    $this->current_item[ $this->current_namespace ][ $el ], $text);
            }
            elseif ($this->inchannel) {
                $this->concat(
                    $this->channel[ $this->current_namespace][ $el ], $text );
            }
            elseif ($this->intextinput) {
                $this->concat(
                    $this->textinput[ $this->current_namespace][ $el ], $text );
            }
            elseif ($this->inimage) {
                $this->concat(
                    $this->image[ $this->current_namespace ][ $el ], $text );
            }
        }
        else {
            if ( $this->initem ) {
                $this->concat(
                    $this->current_item[ $el ], $text);
            }
            elseif ($this->intextinput) {
                $this->concat(
                    $this->textinput[ $el ], $text );
            }
            elseif ($this->inimage) {
                $this->concat(
                    $this->image[ $el ], $text );
            }
            elseif ($this->inchannel) {
                $this->concat(
                    $this->channel[ $el ], $text );
            }
            
        }
    }
    
    function normalize () {
        // if atom populate rss fields
        if ( $this->is_atom() ) {
            $this->channel['description'] = $this->channel['tagline'];
            for ( $i = 0; $i < count($this->items); $i++) {
                $item = $this->items[$i];
                if ( isset($item['summary']) )
                    $item['description'] = $item['summary'];
                if ( isset($item['atom_content']))
                    $item['content']['encoded'] = $item['atom_content'];
                
                $atom_date = (isset($item['issued']) ) ? $item['issued'] : $item['modified'];
                if ( $atom_date ) {
                    $epoch = @parse_w3cdtf($atom_date);
                    if ($epoch and $epoch > 0) {
                        $item['date_timestamp'] = $epoch;
                    }
                }
                
                $this->items[$i] = $item;
            }       
        }
        elseif ( $this->is_rss() ) {
            $this->channel['tagline'] = $this->channel['description'];
            for ( $i = 0; $i < count($this->items); $i++) {
                $item = $this->items[$i];
                if ( isset($item['description']))
                    $item['summary'] = $item['description'];
                if ( isset($item['content']['encoded'] ) )
                    $item['atom_content'] = $item['content']['encoded'];
                
                if ( $this->is_rss() == '1.0' and isset($item['dc']['date']) ) {
                    $epoch = @parse_w3cdtf($item['dc']['date']);
                    if ($epoch and $epoch > 0) {
                        $item['date_timestamp'] = $epoch;
                    }
                }
                elseif ( isset($item['pubdate']) ) {
                    $epoch = @strtotime($item['pubdate']);
                    if ($epoch > 0) {
                        $item['date_timestamp'] = $epoch;
                    }
                }
                
                $this->items[$i] = $item;
            }
        }
    }
    
    
    function is_rss () {
        if ( $this->feed_type == RSS ) {
            return $this->feed_version; 
        }
        else {
            return false;
        }
    }
    
    function is_atom() {
        if ( $this->feed_type == ATOM ) {
            return $this->feed_version;
        }
        else {
            return false;
        }
    }

    /**
    * return XML parser, and possibly re-encoded source
    *
    */
    function create_parser($source, $out_enc, $in_enc, $detect) {
        if ( substr(phpversion(),0,1) == 5) {
            $parser = $this->php5_create_parser($in_enc, $detect);
        }
        else {
            list($parser, $source) = $this->php4_create_parser($source, $in_enc, $detect);
        }
        if ($out_enc) {
            $this->encoding = $out_enc;
            xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $out_enc);
        }
        
        return array($parser, $source);
    }
    
    /**
    * Instantiate an XML parser under PHP5
    *
    * PHP5 will do a fine job of detecting input encoding
    * if passed an empty string as the encoding. 
    *
    * All hail libxml2!
    *
    */
    function php5_create_parser($in_enc, $detect) {
        // by default php5 does a fine job of detecting input encodings
        if(!$detect && $in_enc) {
            return xml_parser_create($in_enc);
        }
        else {
            return xml_parser_create('');
        }
    }
    
    /**
    * Instaniate an XML parser under PHP4
    *
    * Unfortunately PHP4's support for character encodings
    * and especially XML and character encodings sucks.  As
    * long as the documents you parse only contain characters
    * from the ISO-8859-1 character set (a superset of ASCII,
    * and a subset of UTF-8) you're fine.  However once you
    * step out of that comfy little world things get mad, bad,
    * and dangerous to know.
    *
    * The following code is based on SJM's work with FoF
    * @see http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    *
    */
    function php4_create_parser($source, $in_enc, $detect) {
        if ( !$detect ) {
            return array(xml_parser_create($in_enc), $source);
        }
        
        if (!$in_enc) {
            if (preg_match('/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m', $source, $m)) {
                $in_enc = strtoupper($m[1]);
                $this->source_encoding = $in_enc;
            }
            else {
                $in_enc = 'UTF-8';
            }
        }
        
        if ($this->known_encoding($in_enc)) {
            return array(xml_parser_create($in_enc), $source);
        }
        
        // the dectected encoding is not one of the simple encodings PHP knows
        
        // attempt to use the iconv extension to
        // cast the XML to a known encoding
        // @see http://php.net/iconv
       
        if (function_exists('iconv'))  {
            $encoded_source = iconv($in_enc,'UTF-8', $source);
            if ($encoded_source) {
                return array(xml_parser_create('UTF-8'), $encoded_source);
            }
        }
        
        // iconv didn't work, try mb_convert_encoding
        // @see http://php.net/mbstring
        if(function_exists('mb_convert_encoding')) {
            $encoded_source = mb_convert_encoding($source, 'UTF-8', $in_enc );
            if ($encoded_source) {
                return array(xml_parser_create('UTF-8'), $encoded_source);
            }
        }
        
        // else 
        $this->error("Feed is in an unsupported character encoding. ($in_enc) " .
                     "You may see strange artifacts, and mangled characters.",
                     E_USER_NOTICE);
            
        return array(xml_parser_create(), $source);
    }
    
    function known_encoding($enc) {
        $enc = strtoupper($enc);
        if ( in_array($enc, $this->_KNOWN_ENCODINGS) ) {
            return $enc;
        }
        else {
            return false;
        }
    }

    function error ($errormsg, $lvl=E_USER_WARNING) {
        // append PHP's error message if track_errors enabled
        if ( isset($php_errormsg) ) { 
            $errormsg .= " ($php_errormsg)";
        }
        if ( MAGPIE_DEBUG ) {
            trigger_error( $errormsg, $lvl);        
        }
        else {
            error_log( $errormsg, 0);
        }
        
        $notices = E_USER_NOTICE|E_NOTICE;
        if ( $lvl&$notices ) {
            $this->WARNING = $errormsg;
        } else {
            $this->ERROR = $errormsg;
        }
    }
    
    
} // end class RSS

function map_attrs($k, $v) {
    return "$k=\"$v\"";
}

// patch to support medieval versions of PHP4.1.x, 
// courtesy, Ryan Currie, ryan@digibliss.com

if (!function_exists('array_change_key_case')) {
	define("CASE_UPPER",1);
	define("CASE_LOWER",0);


	function array_change_key_case($array,$case=CASE_LOWER) {
       if ($case=CASE_LOWER) $cmd=strtolower;
       elseif ($case=CASE_UPPER) $cmd=strtoupper;
       foreach($array as $key=>$value) {
               $output[$cmd($key)]=$value;
       }
       return $output;
	}

}
#end rss_parse.inc
#require_once( MAGPIE_DIR . 'rss_cache.inc' );
/*
 * Project:     MagpieRSS: a simple RSS integration tool
 * File:        rss_cache.inc, a simple, rolling(no GC), cache 
 *              for RSS objects, keyed on URL.
 * Author:      Kellan Elliott-McCrea <kellan@protest.net>
 * Version:     0.51
 * License:     GPL
 *
 * The lastest version of MagpieRSS can be obtained from:
 * http://magpierss.sourceforge.net
 *
 * For questions, help, comments, discussion, etc., please join the
 * Magpie mailing list:
 * http://lists.sourceforge.net/lists/listinfo/magpierss-general
 *
 */

class RSSCache {
    var $BASE_CACHE = './cache';    // where the cache files are stored
    var $MAX_AGE    = 3600;         // when are files stale, default one hour
    var $ERROR      = "";           // accumulate error messages
    
    function RSSCache ($base='', $age='') {
        if ( $base ) {
            $this->BASE_CACHE = $base;
        }
        if ( $age ) {
            $this->MAX_AGE = $age;
        }
        
        // attempt to make the cache directory
        if ( ! file_exists( $this->BASE_CACHE ) ) {
            $status = @mkdir( $this->BASE_CACHE, 0755 );
            
            // if make failed 
            if ( ! $status ) {
                $this->error(
                    "Cache couldn't make dir '" . $this->BASE_CACHE . "'."
                );
            }
        }
    }
    
/*=======================================================================*\
    Function:   set
    Purpose:    add an item to the cache, keyed on url
    Input:      url from wich the rss file was fetched
    Output:     true on sucess  
\*=======================================================================*/
    function set ($url, $rss) {
        $this->ERROR = "";
        $cache_file = $this->file_name( $url );
        $fp = @fopen( $cache_file, 'w' );
        
        if ( ! $fp ) {
            $this->error(
                "Cache unable to open file for writing: $cache_file"
            );
            return 0;
        }
        
        
        $data = $this->serialize( $rss );
        fwrite( $fp, $data );
        fclose( $fp );
        
        return $cache_file;
    }
    
/*=======================================================================*\
    Function:   get
    Purpose:    fetch an item from the cache
    Input:      url from wich the rss file was fetched
    Output:     cached object on HIT, false on MISS 
\*=======================================================================*/ 
    function get ($url) {
        $this->ERROR = "";
        $cache_file = $this->file_name( $url );
        
        if ( ! file_exists( $cache_file ) ) {
            $this->debug( 
                "Cache doesn't contain: $url (cache file: $cache_file)"
            );
            return 0;
        }
        
        $fp = @fopen($cache_file, 'r');
        if ( ! $fp ) {
            $this->error(
                "Failed to open cache file for reading: $cache_file"
            );
            return 0;
        }
        
        if ($filesize = filesize($cache_file) ) {
        	$data = fread( $fp, filesize($cache_file) );
        	$rss = $this->unserialize( $data );
        
        	return $rss;
    	}
    	
    	return 0;
    }

/*=======================================================================*\
    Function:   check_cache
    Purpose:    check a url for membership in the cache
                and whether the object is older then MAX_AGE (ie. STALE)
    Input:      url from wich the rss file was fetched
    Output:     cached object on HIT, false on MISS 
\*=======================================================================*/     
    function check_cache ( $url ) {
        $this->ERROR = "";
        $filename = $this->file_name( $url );
        
        if ( file_exists( $filename ) ) {
            // find how long ago the file was added to the cache
            // and whether that is longer then MAX_AGE
            $mtime = filemtime( $filename );
            $age = time() - $mtime;
            if ( $this->MAX_AGE > $age ) {
                // object exists and is current
                return 'HIT';
            }
            else {
                // object exists but is old
                return 'STALE';
            }
        }
        else {
            // object does not exist
            return 'MISS';
        }
    }

	function cache_age( $cache_key ) {
		$filename = $this->file_name( $url );
		if ( file_exists( $filename ) ) {
			$mtime = filemtime( $filename );
            $age = time() - $mtime;
			return $age;
		}
		else {
			return -1;	
		}
	}
	
/*=======================================================================*\
    Function:   serialize
\*=======================================================================*/     
    function serialize ( $rss ) {
        return serialize( $rss );
    }

/*=======================================================================*\
    Function:   unserialize
\*=======================================================================*/     
    function unserialize ( $data ) {
        return unserialize( $data );
    }
    
/*=======================================================================*\
    Function:   file_name
    Purpose:    map url to location in cache
    Input:      url from wich the rss file was fetched
    Output:     a file name
\*=======================================================================*/     
    function file_name ($url) {
        $filename = md5( $url );
        return join( DIRECTORY_SEPARATOR, array( $this->BASE_CACHE, $filename ) );
    }

/*=======================================================================*\
    Function:   error
    Purpose:    register error
\*=======================================================================*/         
    function error ($errormsg, $lvl=E_USER_WARNING) {
        // append PHP's error message if track_errors enabled
        if ( isset($php_errormsg) ) { 
            $errormsg .= " ($php_errormsg)";
        }
        $this->ERROR = $errormsg;
        if ( MAGPIE_DEBUG ) {
            trigger_error( $errormsg, $lvl);
        }
        else {
            error_log( $errormsg, 0);
        }
    }
    
    function debug ($debugmsg, $lvl=E_USER_NOTICE) {
        if ( MAGPIE_DEBUG ) {
            $this->error("MagpieRSS [debug] $debugmsg", $lvl);
        }
    }

}
#end rss_cache.inc

// for including 3rd party libraries
define('MAGPIE_EXTLIB', MAGPIE_DIR . 'extlib' . DIR_SEP);
#require_once( MAGPIE_EXTLIB . 'Snoopy.class.inc');
/*************************************************

Snoopy - the PHP net client
Author: Monte Ohrt <monte@ispi.net>
Copyright (c): 1999-2000 ispi, all rights reserved
Version: 1.0

 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

You may contact the author of Snoopy by e-mail at:
monte@ispi.net

Or, write to:
Monte Ohrt
CTO, ispi
237 S. 70th suite 220
Lincoln, NE 68510

The latest version of Snoopy can be obtained from:
http://snoopy.sourceforge.com

*************************************************/

class Snoopy
{
	/**** Public variables ****/
	
	/* user definable vars */

	var $host			=	"www.php.net";		// host name we are connecting to
	var $port			=	80;					// port we are connecting to
	var $proxy_host		=	"";					// proxy host to use
	var $proxy_port		=	"";					// proxy port to use
	var $agent			=	"Snoopy v1.0";		// agent we masquerade as
	var	$referer		=	"";					// referer info to pass
	var $cookies		=	array();			// array of cookies to pass
												// $cookies["username"]="joe";
	var	$rawheaders		=	array();			// array of raw headers to send
												// $rawheaders["Content-type"]="text/html";

	var $maxredirs		=	5;					// http redirection depth maximum. 0 = disallow
	var $lastredirectaddr	=	"";				// contains address of last redirected address
	var	$offsiteok		=	true;				// allows redirection off-site
	var $maxframes		=	0;					// frame content depth maximum. 0 = disallow
	var $expandlinks	=	true;				// expand links to fully qualified URLs.
												// this only applies to fetchlinks()
												// or submitlinks()
	var $passcookies	=	true;				// pass set cookies back through redirects
												// NOTE: this currently does not respect
												// dates, domains or paths.
	
	var	$user			=	"";					// user for http authentication
	var	$pass			=	"";					// password for http authentication
	
	// http accept types
	var $accept			=	"image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, */*";
	
	var $results		=	"";					// where the content is put
		
	var $error			=	"";					// error messages sent here
	var	$response_code	=	"";					// response code returned from server
	var	$headers		=	array();			// headers returned from server sent here
	var	$maxlength		=	500000;				// max return data length (body)
	var $read_timeout	=	0;					// timeout on read operations, in seconds
												// supported only since PHP 4 Beta 4
												// set to 0 to disallow timeouts
	var $timed_out		=	false;				// if a read operation timed out
	var	$status			=	0;					// http request status
	
	var	$curl_path		=	"/usr/bin/curl";
												// Snoopy will use cURL for fetching
												// SSL content if a full system path to
												// the cURL binary is supplied here.
												// set to false if you do not have
												// cURL installed. See http://curl.haxx.se
												// for details on installing cURL.
												// Snoopy does *not* use the cURL
												// library functions built into php,
												// as these functions are not stable
												// as of this Snoopy release.
	
	// send Accept-encoding: gzip?
	var $use_gzip		= true;	
	
	/**** Private variables ****/	
	
	var	$_maxlinelen	=	4096;				// max line length (headers)
	
	var $_httpmethod	=	"GET";				// default http request method
	var $_httpversion	=	"HTTP/1.0";			// default http request version
	var $_submit_method	=	"POST";				// default submit method
	var $_submit_type	=	"application/x-www-form-urlencoded";	// default submit type
	var $_mime_boundary	=   "";					// MIME boundary for multipart/form-data submit type
	var $_redirectaddr	=	false;				// will be set if page fetched is a redirect
	var $_redirectdepth	=	0;					// increments on an http redirect
	var $_frameurls		= 	array();			// frame src urls
	var $_framedepth	=	0;					// increments on frame depth
	
	var $_isproxy		=	false;				// set if using a proxy server
	var $_fp_timeout	=	30;					// timeout for socket connection

/*======================================================================*\
	Function:	fetch
	Purpose:	fetch the contents of a web page
				(and possibly other protocols in the
				future like ftp, nntp, gopher, etc.)
	Input:		$URI	the location of the page to fetch
	Output:		$this->results	the output text from the fetch
\*======================================================================*/

	function fetch($URI)
	{
	
		//preg_match("|^([^:]+)://([^:/]+)(:[\d]+)*(.*)|",$URI,$URI_PARTS);
		$URI_PARTS = parse_url($URI);
		if (!empty($URI_PARTS["user"]))
			$this->user = $URI_PARTS["user"];
		if (!empty($URI_PARTS["pass"]))
			$this->pass = $URI_PARTS["pass"];
				
		switch($URI_PARTS["scheme"])
		{
			case "http":
				$this->host = $URI_PARTS["host"];
				if(!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if($this->_connect($fp))
				{
					if($this->_isproxy)
					{
						// using proxy, send entire URI
						$this->_httprequest($URI,$fp,$URI,$this->_httpmethod);
					}
					else
					{
						$path = $URI_PARTS["path"].(isset($URI_PARTS["query"]) ? "?".$URI_PARTS["query"] : "");
						// no proxy, send only the path
						$this->_httprequest($path, $fp, $URI, $this->_httpmethod);
					}
					
					$this->_disconnect($fp);

					if($this->_redirectaddr)
					{
						/* url was redirected, check if we've hit the max depth */
						if($this->maxredirs > $this->_redirectdepth)
						{
							// only follow redirect if it's on this site, or offsiteok is true
							if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
							{
								/* follow the redirect */
								$this->_redirectdepth++;
								$this->lastredirectaddr=$this->_redirectaddr;
								$this->fetch($this->_redirectaddr);
							}
						}
					}

					if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
					{
						$frameurls = $this->_frameurls;
						$this->_frameurls = array();
						
						while(list(,$frameurl) = each($frameurls))
						{
							if($this->_framedepth < $this->maxframes)
							{
								$this->fetch($frameurl);
								$this->_framedepth++;
							}
							else
								break;
						}
					}					
				}
				else
				{
					return false;
				}
				return true;					
				break;
			case "https":
				if(!$this->curl_path || (!is_executable($this->curl_path))) {
					$this->error = "Bad curl ($this->curl_path), can't fetch HTTPS \n";
					return false;
				}
				$this->host = $URI_PARTS["host"];
				if(!empty($URI_PARTS["port"]))
					$this->port = $URI_PARTS["port"];
				if($this->_isproxy)
				{
					// using proxy, send entire URI
					$this->_httpsrequest($URI,$URI,$this->_httpmethod);
				}
				else
				{
					$path = $URI_PARTS["path"].($URI_PARTS["query"] ? "?".$URI_PARTS["query"] : "");
					// no proxy, send only the path
					$this->_httpsrequest($path, $URI, $this->_httpmethod);
				}

				if($this->_redirectaddr)
				{
					/* url was redirected, check if we've hit the max depth */
					if($this->maxredirs > $this->_redirectdepth)
					{
						// only follow redirect if it's on this site, or offsiteok is true
						if(preg_match("|^http://".preg_quote($this->host)."|i",$this->_redirectaddr) || $this->offsiteok)
						{
							/* follow the redirect */
							$this->_redirectdepth++;
							$this->lastredirectaddr=$this->_redirectaddr;
							$this->fetch($this->_redirectaddr);
						}
					}
				}

				if($this->_framedepth < $this->maxframes && count($this->_frameurls) > 0)
				{
					$frameurls = $this->_frameurls;
					$this->_frameurls = array();

					while(list(,$frameurl) = each($frameurls))
					{
						if($this->_framedepth < $this->maxframes)
						{
							$this->fetch($frameurl);
							$this->_framedepth++;
						}
						else
							break;
					}
				}					
				return true;					
				break;
			default:
				// not a valid protocol
				$this->error	=	'Invalid protocol "'.$URI_PARTS["scheme"].'"\n';
				return false;
				break;
		}		
		return true;
	}



/*======================================================================*\
	Private functions
\*======================================================================*/
	
	
/*======================================================================*\
	Function:	_striplinks
	Purpose:	strip the hyperlinks from an html document
	Input:		$document	document to strip.
	Output:		$match		an array of the links
\*======================================================================*/

	function _striplinks($document)
	{	
		preg_match_all("'<\s*a\s+.*href\s*=\s*			# find <a href=
						([\"\'])?					# find single or double quote
						(?(1) (.*?)\\1 | ([^\s\>]+))		# if quote found, match up to next matching
													# quote, otherwise match up to next space
						'isx",$document,$links);
						

		// catenate the non-empty matches from the conditional subpattern

		while(list($key,$val) = each($links[2]))
		{
			if(!empty($val))
				$match[] = $val;
		}				
		
		while(list($key,$val) = each($links[3]))
		{
			if(!empty($val))
				$match[] = $val;
		}		
		
		// return the links
		return $match;
	}

/*======================================================================*\
	Function:	_stripform
	Purpose:	strip the form elements from an html document
	Input:		$document	document to strip.
	Output:		$match		an array of the links
\*======================================================================*/

	function _stripform($document)
	{	
		preg_match_all("'<\/?(FORM|INPUT|SELECT|TEXTAREA|(OPTION))[^<>]*>(?(2)(.*(?=<\/?(option|select)[^<>]*>[\r\n]*)|(?=[\r\n]*))|(?=[\r\n]*))'Usi",$document,$elements);
		
		// catenate the matches
		$match = implode("\r\n",$elements[0]);
				
		// return the links
		return $match;
	}

	
	
/*======================================================================*\
	Function:	_striptext
	Purpose:	strip the text from an html document
	Input:		$document	document to strip.
	Output:		$text		the resulting text
\*======================================================================*/

	function _striptext($document)
	{
		
		// I didn't use preg eval (//e) since that is only available in PHP 4.0.
		// so, list your entities one by one here. I included some of the
		// more common ones.
								
		$search = array("'<script[^>]*?>.*?</script>'si",	// strip out javascript
						"'<[\/\!]*?[^<>]*?>'si",			// strip out html tags
						"'([\r\n])[\s]+'",					// strip out white space
						"'&(quote|#34);'i",					// replace html entities
						"'&(amp|#38);'i",
						"'&(lt|#60);'i",
						"'&(gt|#62);'i",
						"'&(nbsp|#160);'i",
						"'&(iexcl|#161);'i",
						"'&(cent|#162);'i",
						"'&(pound|#163);'i",
						"'&(copy|#169);'i"
						);				
		$replace = array(	"",
							"",
							"\\1",
							"\"",
							"&",
							"<",
							">",
							" ",
							chr(161),
							chr(162),
							chr(163),
							chr(169));
					
		$text = preg_replace($search,$replace,$document);
								
		return $text;
	}

/*======================================================================*\
	Function:	_expandlinks
	Purpose:	expand each link into a fully qualified URL
	Input:		$links			the links to qualify
				$URI			the full URI to get the base from
	Output:		$expandedLinks	the expanded links
\*======================================================================*/

	function _expandlinks($links,$URI)
	{
		
		preg_match("/^[^\?]+/",$URI,$match);

		$match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|","",$match[0]);
				
		$search = array( 	"|^http://".preg_quote($this->host)."|i",
							"|^(?!http://)(\/)?(?!mailto:)|i",
							"|/\./|",
							"|/[^\/]+/\.\./|"
						);
						
		$replace = array(	"",
							$match."/",
							"/",
							"/"
						);			
				
		$expandedLinks = preg_replace($search,$replace,$links);

		return $expandedLinks;
	}

/*======================================================================*\
	Function:	_httprequest
	Purpose:	go get the http data from the server
	Input:		$url		the url to fetch
				$fp			the current open file pointer
				$URI		the full URI
				$body		body contents to send if any (POST)
	Output:		
\*======================================================================*/
	
	function _httprequest($url,$fp,$URI,$http_method,$content_type="",$body="")
	{
		if($this->passcookies && $this->_redirectaddr)
			$this->setcookies();
			
		$URI_PARTS = parse_url($URI);
		if(empty($url))
			$url = "/";
		$headers = $http_method." ".$url." ".$this->_httpversion."\r\n";		
		if(!empty($this->agent))
			$headers .= "User-Agent: ".$this->agent."\r\n";
		if(!empty($this->host) && !isset($this->rawheaders['Host']))
			$headers .= "Host: ".$this->host."\r\n";
		if(!empty($this->accept))
			$headers .= "Accept: ".$this->accept."\r\n";
		
		if($this->use_gzip) {
			// make sure PHP was built with --with-zlib
			// and we can handle gzipp'ed data
			if ( function_exists(gzinflate) ) {
			   $headers .= "Accept-encoding: gzip\r\n";
			}
			else {
			   trigger_error(
			   	"use_gzip is on, but PHP was built without zlib support.".
				"  Requesting file(s) without gzip encoding.", 
				E_USER_NOTICE);
			}
		}
		
		if(!empty($this->referer))
			$headers .= "Referer: ".$this->referer."\r\n";
		if(!empty($this->cookies))
		{			
			if(!is_array($this->cookies))
				$this->cookies = (array)$this->cookies;
	
			reset($this->cookies);
			if ( count($this->cookies) > 0 ) {
				$cookie_headers .= 'Cookie: ';
				foreach ( $this->cookies as $cookieKey => $cookieVal ) {
				$cookie_headers .= $cookieKey."=".urlencode($cookieVal)."; ";
				}
				$headers .= substr($cookie_headers,0,-2) . "\r\n";
			} 
		}
		if(!empty($this->rawheaders))
		{
			if(!is_array($this->rawheaders))
				$this->rawheaders = (array)$this->rawheaders;
			while(list($headerKey,$headerVal) = each($this->rawheaders))
				$headers .= $headerKey.": ".$headerVal."\r\n";
		}
		if(!empty($content_type)) {
			$headers .= "Content-type: $content_type";
			if ($content_type == "multipart/form-data")
				$headers .= "; boundary=".$this->_mime_boundary;
			$headers .= "\r\n";
		}
		if(!empty($body))	
			$headers .= "Content-length: ".strlen($body)."\r\n";
		if(!empty($this->user) || !empty($this->pass))	
			$headers .= "Authorization: BASIC ".base64_encode($this->user.":".$this->pass)."\r\n";

		$headers .= "\r\n";
		
		// set the read timeout if needed
		if ($this->read_timeout > 0)
			socket_set_timeout($fp, $this->read_timeout);
		$this->timed_out = false;
		
		fwrite($fp,$headers.$body,strlen($headers.$body));
		
		$this->_redirectaddr = false;
		unset($this->headers);
		
		// content was returned gzip encoded?
		$is_gzipped = false;
						
		while($currentHeader = fgets($fp,$this->_maxlinelen))
		{
			if ($this->read_timeout > 0 && $this->_check_timeout($fp))
			{
				$this->status=-100;
				return false;
			}
				
		//	if($currentHeader == "\r\n")
			if(preg_match("/^\r?\n$/", $currentHeader) )
			      break;
						
			// if a header begins with Location: or URI:, set the redirect
			if(preg_match("/^(Location:|URI:)/i",$currentHeader))
			{
				// get URL portion of the redirect
				preg_match("/^(Location:|URI:)\s+(.*)/",chop($currentHeader),$matches);
				// look for :// in the Location header to see if hostname is included
				if(!preg_match("|\:\/\/|",$matches[2]))
				{
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
					// eliminate double slash
					if(!preg_match("|^/|",$matches[2]))
							$this->_redirectaddr .= "/".$matches[2];
					else
							$this->_redirectaddr .= $matches[2];
				}
				else
					$this->_redirectaddr = $matches[2];
			}
		
			if(preg_match("|^HTTP/|",$currentHeader))
			{
                if(preg_match("|^HTTP/[^\s]*\s(.*?)\s|",$currentHeader, $status))
				{
					$this->status= $status[1];
                }				
				$this->response_code = $currentHeader;
			}
			
			if (preg_match("/Content-Encoding: gzip/", $currentHeader) ) {
				$is_gzipped = true;
			}
			
			$this->headers[] = $currentHeader;
		}

		# $results = fread($fp, $this->maxlength);
		$results = "";
		while ( $data = fread($fp, $this->maxlength) ) {
		    $results .= $data;
		    if (
		        strlen($results) > $this->maxlength ) {
		        break;
		    }
		}
		
		// gunzip
		if ( $is_gzipped ) {
			// per http://www.php.net/manual/en/function.gzencode.php
			$results = substr($results, 10);
			$results = gzinflate($results);
		}
		
		if ($this->read_timeout > 0 && $this->_check_timeout($fp))
		{
			$this->status=-100;
			return false;
		}
		
		// check if there is a a redirect meta tag
		
		if(preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]+URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))
		{
			$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
		}

		// have we hit our frame depth and is there frame src to fetch?
		if(($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
		{
			$this->results[] = $results;
			for($x=0; $x<count($match[1]); $x++)
				$this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);
		}
		// have we already fetched framed content?
		elseif(is_array($this->results))
			$this->results[] = $results;
		// no framed content
		else
			$this->results = $results;
		
		return true;
	}

/*======================================================================*\
	Function:	_httpsrequest
	Purpose:	go get the https data from the server using curl
	Input:		$url		the url to fetch
				$URI		the full URI
				$body		body contents to send if any (POST)
	Output:		
\*======================================================================*/
	
	function _httpsrequest($url,$URI,$http_method,$content_type="",$body="")
	{
		if($this->passcookies && $this->_redirectaddr)
			$this->setcookies();

		$headers = array();		
					
		$URI_PARTS = parse_url($URI);
		if(empty($url))
			$url = "/";
		// GET ... header not needed for curl
		//$headers[] = $http_method." ".$url." ".$this->_httpversion;		
		if(!empty($this->agent))
			$headers[] = "User-Agent: ".$this->agent;
		if(!empty($this->host))
			$headers[] = "Host: ".$this->host;
		if(!empty($this->accept))
			$headers[] = "Accept: ".$this->accept;
		if(!empty($this->referer))
			$headers[] = "Referer: ".$this->referer;
		if(!empty($this->cookies))
		{			
			if(!is_array($this->cookies))
				$this->cookies = (array)$this->cookies;
	
			reset($this->cookies);
			if ( count($this->cookies) > 0 ) {
				$cookie_str = 'Cookie: ';
				foreach ( $this->cookies as $cookieKey => $cookieVal ) {
				$cookie_str .= $cookieKey."=".urlencode($cookieVal)."; ";
				}
				$headers[] = substr($cookie_str,0,-2);
			}
		}
		if(!empty($this->rawheaders))
		{
			if(!is_array($this->rawheaders))
				$this->rawheaders = (array)$this->rawheaders;
			while(list($headerKey,$headerVal) = each($this->rawheaders))
				$headers[] = $headerKey.": ".$headerVal;
		}
		if(!empty($content_type)) {
			if ($content_type == "multipart/form-data")
				$headers[] = "Content-type: $content_type; boundary=".$this->_mime_boundary;
			else
				$headers[] = "Content-type: $content_type";
		}
		if(!empty($body))	
			$headers[] = "Content-length: ".strlen($body);
		if(!empty($this->user) || !empty($this->pass))	
			$headers[] = "Authorization: BASIC ".base64_encode($this->user.":".$this->pass);
			
		for($curr_header = 0; $curr_header < count($headers); $curr_header++) {
			$cmdline_params .= " -H \"".$headers[$curr_header]."\"";
		}
			  	                         
		if(!empty($body))
			$cmdline_params .= " -d \"$body\"";
		
		if($this->read_timeout > 0)
			$cmdline_params .= " -m ".$this->read_timeout;
		
		$headerfile = uniqid(time());
		
		# accept self-signed certs
		$cmdline_params .= " -k"; 
		exec($this->curl_path." -D \"/tmp/$headerfile\"".escapeshellcmd($cmdline_params)." ".escapeshellcmd($URI),$results,$return);
		
		if($return)
		{
			$this->error = "Error: cURL could not retrieve the document, error $return.";
			return false;
		}
			
			
		$results = implode("\r\n",$results);
		
		$result_headers = file("/tmp/$headerfile");
						
		$this->_redirectaddr = false;
		unset($this->headers);
						
		for($currentHeader = 0; $currentHeader < count($result_headers); $currentHeader++)
		{
			
			// if a header begins with Location: or URI:, set the redirect
			if(preg_match("/^(Location: |URI: )/i",$result_headers[$currentHeader]))
			{
				// get URL portion of the redirect
				preg_match("/^(Location: |URI:)(.*)/",chop($result_headers[$currentHeader]),$matches);
				// look for :// in the Location header to see if hostname is included
				if(!preg_match("|\:\/\/|",$matches[2]))
				{
					// no host in the path, so prepend
					$this->_redirectaddr = $URI_PARTS["scheme"]."://".$this->host.":".$this->port;
					// eliminate double slash
					if(!preg_match("|^/|",$matches[2]))
							$this->_redirectaddr .= "/".$matches[2];
					else
							$this->_redirectaddr .= $matches[2];
				}
				else
					$this->_redirectaddr = $matches[2];
			}
		
			if(preg_match("|^HTTP/|",$result_headers[$currentHeader]))
			{
			    $this->response_code = $result_headers[$currentHeader];
			    if(preg_match("|^HTTP/[^\s]*\s(.*?)\s|",$this->response_code, $match))
			    {
				$this->status= $match[1];
                	    }
			}
			$this->headers[] = $result_headers[$currentHeader];
		}

		// check if there is a a redirect meta tag
		
		if(preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]+URL[\s]*=[\s]*([^\"\']*?)[\"\']?>'i",$results,$match))
		{
			$this->_redirectaddr = $this->_expandlinks($match[1],$URI);	
		}

		// have we hit our frame depth and is there frame src to fetch?
		if(($this->_framedepth < $this->maxframes) && preg_match_all("'<frame\s+.*src[\s]*=[\'\"]?([^\'\"\>]+)'i",$results,$match))
		{
			$this->results[] = $results;
			for($x=0; $x<count($match[1]); $x++)
				$this->_frameurls[] = $this->_expandlinks($match[1][$x],$URI_PARTS["scheme"]."://".$this->host);
		}
		// have we already fetched framed content?
		elseif(is_array($this->results))
			$this->results[] = $results;
		// no framed content
		else
			$this->results = $results;

		unlink("/tmp/$headerfile");
		
		return true;
	}

/*======================================================================*\
	Function:	setcookies()
	Purpose:	set cookies for a redirection
\*======================================================================*/
	
	function setcookies()
	{
		for($x=0; $x<count($this->headers); $x++)
		{
		if(preg_match("/^set-cookie:[\s]+([^=]+)=([^;]+)/i", $this->headers[$x],$match))
			$this->cookies[$match[1]] = $match[2];
		}
	}

	
/*======================================================================*\
	Function:	_check_timeout
	Purpose:	checks whether timeout has occurred
	Input:		$fp	file pointer
\*======================================================================*/

	function _check_timeout($fp)
	{
		if ($this->read_timeout > 0) {
			$fp_status = socket_get_status($fp);
			if ($fp_status["timed_out"]) {
				$this->timed_out = true;
				return true;
			}
		}
		return false;
	}

/*======================================================================*\
	Function:	_connect
	Purpose:	make a socket connection
	Input:		$fp	file pointer
\*======================================================================*/
	
	function _connect(&$fp)
	{
		if(!empty($this->proxy_host) && !empty($this->proxy_port))
			{
				$this->_isproxy = true;
				$host = $this->proxy_host;
				$port = $this->proxy_port;
			}
		else
		{
			$host = $this->host;
			$port = $this->port;
		}
	
		$this->status = 0;
		
		if($fp = fsockopen(
					$host,
					$port,
					$errno,
					$errstr,
					$this->_fp_timeout
					))
		{
			// socket connection succeeded

			return true;
		}
		else
		{
			// socket connection failed
			$this->status = $errno;
			switch($errno)
			{
				case -3:
					$this->error="socket creation failed (-3)";
				case -4:
					$this->error="dns lookup failure (-4)";
				case -5:
					$this->error="connection refused or timed out (-5)";
				default:
					$this->error="connection failed (".$errno.")";
			}
			return false;
		}
	}
/*======================================================================*\
	Function:	_disconnect
	Purpose:	disconnect a socket connection
	Input:		$fp	file pointer
\*======================================================================*/
	
	function _disconnect($fp)
	{
		return(fclose($fp));
	}

	
/*======================================================================*\
	Function:	_prepare_post_body
	Purpose:	Prepare post body according to encoding type
	Input:		$formvars  - form variables
				$formfiles - form upload files
	Output:		post body
\*======================================================================*/
	
	function _prepare_post_body($formvars, $formfiles)
	{
		settype($formvars, "array");
		settype($formfiles, "array");

		if (count($formvars) == 0 && count($formfiles) == 0)
			return;
		
		switch ($this->_submit_type) {
			case "application/x-www-form-urlencoded":
				reset($formvars);
				while(list($key,$val) = each($formvars)) {
					if (is_array($val) || is_object($val)) {
						while (list($cur_key, $cur_val) = each($val)) {
							$postdata .= urlencode($key)."[]=".urlencode($cur_val)."&";
						}
					} else
						$postdata .= urlencode($key)."=".urlencode($val)."&";
				}
				break;

			case "multipart/form-data":
				$this->_mime_boundary = "Snoopy".md5(uniqid(microtime()));
				
				reset($formvars);
				while(list($key,$val) = each($formvars)) {
					if (is_array($val) || is_object($val)) {
						while (list($cur_key, $cur_val) = each($val)) {
							$postdata .= "--".$this->_mime_boundary."\r\n";
							$postdata .= "Content-Disposition: form-data; name=\"$key\[\]\"\r\n\r\n";
							$postdata .= "$cur_val\r\n";
						}
					} else {
						$postdata .= "--".$this->_mime_boundary."\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
						$postdata .= "$val\r\n";
					}
				}
				
				reset($formfiles);
				while (list($field_name, $file_names) = each($formfiles)) {
					settype($file_names, "array");
					while (list(, $file_name) = each($file_names)) {
						if (!is_readable($file_name)) continue;

						$fp = fopen($file_name, "r");
						$file_content = fread($fp, filesize($file_name));
						fclose($fp);
						$base_name = basename($file_name);

						$postdata .= "--".$this->_mime_boundary."\r\n";
						$postdata .= "Content-Disposition: form-data; name=\"$field_name\"; filename=\"$base_name\"\r\n\r\n";
						$postdata .= "$file_content\r\n";
					}
				}
				$postdata .= "--".$this->_mime_boundary."--\r\n";
				break;
		}

		return $postdata;
	}
}
#end Snoopy.class.inc

/* 
 * CONSTANTS - redefine these in your script to change the
 * behaviour of fetch_rss() currently, most options effect the cache
 *
 * MAGPIE_CACHE_ON - Should Magpie cache parsed RSS objects? 
 * For me a built in cache was essential to creating a "PHP-like" 
 * feel to Magpie, see rss_cache.inc for rationale
 *
 *
 * MAGPIE_CACHE_DIR - Where should Magpie cache parsed RSS objects?
 * This should be a location that the webserver can write to.   If this 
 * directory does not already exist Mapie will try to be smart and create 
 * it.  This will often fail for permissions reasons.
 *
 *
 * MAGPIE_CACHE_AGE - How long to store cached RSS objects? In seconds.
 *
 *
 * MAGPIE_CACHE_FRESH_ONLY - If remote fetch fails, throw error
 * instead of returning stale object?
 *
 * MAGPIE_DEBUG - Display debugging notices?
 *
*/


/*=======================================================================*\
    Function: fetch_rss: 
    Purpose:  return RSS object for the give url
              maintain the cache
    Input:    url of RSS file
    Output:   parsed RSS object (see rss_parse.inc)

    NOTES ON CACHEING:  
    If caching is on (MAGPIE_CACHE_ON) fetch_rss will first check the cache.
    
    NOTES ON RETRIEVING REMOTE FILES:
    If conditional gets are on (MAGPIE_CONDITIONAL_GET_ON) fetch_rss will
    return a cached object, and touch the cache object upon recieving a
    304.
    
    NOTES ON FAILED REQUESTS:
    If there is an HTTP error while fetching an RSS object, the cached
    version will be return, if it exists (and if MAGPIE_CACHE_FRESH_ONLY is off)
\*=======================================================================*/

define('MAGPIE_VERSION', '0.72');

$MAGPIE_ERROR = "";

function fetch_rss ($url) {
    // initialize constants
    init();
    
    if ( !isset($url) ) {
        error("fetch_rss called without a url");
        return false;
    }
    
    // if cache is disabled
    if ( !MAGPIE_CACHE_ON ) {
        // fetch file, and parse it
        $resp = _fetch_remote_file( $url );
        if ( is_success( $resp->status ) ) {
            return _response_to_rss( $resp );
        }
        else {
            error("Failed to fetch $url and cache is off");
            return false;
        }
    } 
    // else cache is ON
    else {
        // Flow
        // 1. check cache
        // 2. if there is a hit, make sure its fresh
        // 3. if cached obj fails freshness check, fetch remote
        // 4. if remote fails, return stale object, or error
        
        $cache = new RSSCache( MAGPIE_CACHE_DIR, MAGPIE_CACHE_AGE );
        
        if (MAGPIE_DEBUG and $cache->ERROR) {
            debug($cache->ERROR, E_USER_WARNING);
        }
        
        
        $cache_status    = 0;       // response of check_cache
        $request_headers = array(); // HTTP headers to send with fetch
        $rss             = 0;       // parsed RSS object
        $errormsg        = 0;       // errors, if any
        
        // store parsed XML by desired output encoding
        // as character munging happens at parse time
        $cache_key       = $url . MAGPIE_OUTPUT_ENCODING;
        
        if (!$cache->ERROR) {
            // return cache HIT, MISS, or STALE
            $cache_status = $cache->check_cache( $cache_key);
        }
                
        // if object cached, and cache is fresh, return cached obj
        if ( $cache_status == 'HIT' ) {
            $rss = $cache->get( $cache_key );
            if ( isset($rss) and $rss ) {
                // should be cache age
                $rss->from_cache = 1;
                if ( MAGPIE_DEBUG > 1) {
                    debug("MagpieRSS: Cache HIT", E_USER_NOTICE);
                }
                return $rss;
            }
        }
        
        // else attempt a conditional get
        
        // setup headers
        if ( $cache_status == 'STALE' ) {
            $rss = $cache->get( $cache_key );
            if ( $rss and $rss->etag and $rss->last_modified ) {
                $request_headers['If-None-Match'] = $rss->etag;
                $request_headers['If-Last-Modified'] = $rss->last_modified;
            }
        }
        
        $resp = _fetch_remote_file( $url, $request_headers );
        
        if (isset($resp) and $resp) {
          if ($resp->status == '304' ) {
                // we have the most current copy
                if ( MAGPIE_DEBUG > 1) {
                    debug("Got 304 for $url");
                }
                // reset cache on 304 (at minutillo insistent prodding)
                $cache->set($cache_key, $rss);
                return $rss;
            }
            elseif ( is_success( $resp->status ) ) {
                $rss = _response_to_rss( $resp );
                if ( $rss ) {
                    if (MAGPIE_DEBUG > 1) {
                        debug("Fetch successful");
                    }
                    // add object to cache
                    $cache->set( $cache_key, $rss );
                    return $rss;
                }
            }
            else {
                $errormsg = "Failed to fetch $url ";
                if ( $resp->status == '-100' ) {
                    $errormsg .= "(Request timed out after " . MAGPIE_FETCH_TIME_OUT . " seconds)";
                }
                elseif ( $resp->error ) {
                    # compensate for Snoopy's annoying habbit to tacking
                    # on '\n'
                    $http_error = substr($resp->error, 0, -2); 
                    $errormsg .= "(HTTP Error: $http_error)";
                }
                else {
                    $errormsg .=  "(HTTP Response: " . $resp->response_code .')';
                }
            }
        }
        else {
            $errormsg = "Unable to retrieve RSS file for unknown reasons.";
        }
        
        // else fetch failed
        
        // attempt to return cached object
        if ($rss) {
            if ( MAGPIE_DEBUG ) {
                debug("Returning STALE object for $url");
            }
            return $rss;
        }
        
        // else we totally failed
        error( $errormsg ); 
        
        return false;
        
    } // end if ( !MAGPIE_CACHE_ON ) {
} // end fetch_rss()

/*=======================================================================*\
    Function:   error
    Purpose:    set MAGPIE_ERROR, and trigger error
\*=======================================================================*/

function error ($errormsg, $lvl=E_USER_WARNING) {
        global $MAGPIE_ERROR;
        
        // append PHP's error message if track_errors enabled
        if ( isset($php_errormsg) ) { 
            $errormsg .= " ($php_errormsg)";
        }
        if ( $errormsg ) {
            $errormsg = "MagpieRSS: $errormsg";
            $MAGPIE_ERROR = $errormsg;
            trigger_error( $errormsg, $lvl);                
        }
}

function debug ($debugmsg, $lvl=E_USER_NOTICE) {
    trigger_error("MagpieRSS [debug] $debugmsg", $lvl);
}
            
/*=======================================================================*\
    Function:   magpie_error
    Purpose:    accessor for the magpie error variable
\*=======================================================================*/
function magpie_error ($errormsg="") {
    global $MAGPIE_ERROR;
    
    if ( isset($errormsg) and $errormsg ) { 
        $MAGPIE_ERROR = $errormsg;
    }
    
    return $MAGPIE_ERROR;   
}

/*=======================================================================*\
    Function:   _fetch_remote_file
    Purpose:    retrieve an arbitrary remote file
    Input:      url of the remote file
                headers to send along with the request (optional)
    Output:     an HTTP response object (see Snoopy.class.inc)  
\*=======================================================================*/
function _fetch_remote_file ($url, $headers = "" ) {
    // Snoopy is an HTTP client in PHP
    $client = new Snoopy();
    $client->agent = MAGPIE_USER_AGENT;
    $client->read_timeout = MAGPIE_FETCH_TIME_OUT;
    $client->use_gzip = MAGPIE_USE_GZIP;
    if (is_array($headers) ) {
        $client->rawheaders = $headers;
    }
    
    @$client->fetch($url);
    return $client;

}

/*=======================================================================*\
    Function:   _response_to_rss
    Purpose:    parse an HTTP response object into an RSS object
    Input:      an HTTP response object (see Snoopy)
    Output:     parsed RSS object (see rss_parse)
\*=======================================================================*/
function _response_to_rss ($resp) {
    $rss = new MagpieRSS( $resp->results, MAGPIE_OUTPUT_ENCODING, MAGPIE_INPUT_ENCODING, MAGPIE_DETECT_ENCODING );
    
    // if RSS parsed successfully       
    if ( $rss and !$rss->ERROR) {
        
        // find Etag, and Last-Modified
        foreach($resp->headers as $h) {
            // 2003-03-02 - Nicola Asuni (www.tecnick.com) - fixed bug "Undefined offset: 1"
            if (strpos($h, ": ")) {
                list($field, $val) = explode(": ", $h, 2);
            }
            else {
                $field = $h;
                $val = "";
            }
            
            if ( $field == 'ETag' ) {
                $rss->etag = $val;
            }
            
            if ( $field == 'Last-Modified' ) {
                $rss->last_modified = $val;
            }
        }
        
        return $rss;    
    } // else construct error message
    else {
        $errormsg = "Failed to parse RSS file.";
        
        if ($rss) {
            $errormsg .= " (" . $rss->ERROR . ")";
        }
        error($errormsg);
        
        return false;
    } // end if ($rss and !$rss->error)
}

/*=======================================================================*\
    Function:   init
    Purpose:    setup constants with default values
                check for user overrides
\*=======================================================================*/
function init () {
    if ( defined('MAGPIE_INITALIZED') ) {
        return;
    }
    else {
        define('MAGPIE_INITALIZED', true);
    }
    
    if ( !defined('MAGPIE_CACHE_ON') ) {
        define('MAGPIE_CACHE_ON', true);
    }

    if ( !defined('MAGPIE_CACHE_DIR') ) {
        define('MAGPIE_CACHE_DIR', './cache');
    }

    if ( !defined('MAGPIE_CACHE_AGE') ) {
        define('MAGPIE_CACHE_AGE', 60*60); // one hour
    }

    if ( !defined('MAGPIE_CACHE_FRESH_ONLY') ) {
        define('MAGPIE_CACHE_FRESH_ONLY', false);
    }

    if ( !defined('MAGPIE_OUTPUT_ENCODING') ) {
        define('MAGPIE_OUTPUT_ENCODING', 'ISO-8859-1');
    }
    
    if ( !defined('MAGPIE_INPUT_ENCODING') ) {
        define('MAGPIE_INPUT_ENCODING', null);
    }
    
    if ( !defined('MAGPIE_DETECT_ENCODING') ) {
        define('MAGPIE_DETECT_ENCODING', true);
    }
    
    if ( !defined('MAGPIE_DEBUG') ) {
        define('MAGPIE_DEBUG', 0);
    }
    
    if ( !defined('MAGPIE_USER_AGENT') ) {
        $ua = 'MagpieRSS/'. MAGPIE_VERSION . ' (+http://magpierss.sf.net';
        
        if ( MAGPIE_CACHE_ON ) {
            $ua = $ua . ')';
        }
        else {
            $ua = $ua . '; No cache)';
        }
        
        define('MAGPIE_USER_AGENT', $ua);
    }
    
    if ( !defined('MAGPIE_FETCH_TIME_OUT') ) {
        define('MAGPIE_FETCH_TIME_OUT', 5); // 5 second timeout
    }
    
    // use gzip encoding to fetch rss files if supported?
    if ( !defined('MAGPIE_USE_GZIP') ) {
        define('MAGPIE_USE_GZIP', true);    
    }
}

// NOTE: the following code should really be in Snoopy, or at least
// somewhere other then rss_fetch!

/*=======================================================================*\
    HTTP STATUS CODE PREDICATES
    These functions attempt to classify an HTTP status code
    based on RFC 2616 and RFC 2518.
    
    All of them take an HTTP status code as input, and return true or false

    All this code is adapted from LWP's HTTP::Status.
\*=======================================================================*/


/*=======================================================================*\
    Function:   is_info
    Purpose:    return true if Informational status code
\*=======================================================================*/
function is_info ($sc) { 
    return $sc >= 100 && $sc < 200; 
}

/*=======================================================================*\
    Function:   is_success
    Purpose:    return true if Successful status code
\*=======================================================================*/
function is_success ($sc) { 
    return $sc >= 200 && $sc < 300; 
}

/*=======================================================================*\
    Function:   is_redirect
    Purpose:    return true if Redirection status code
\*=======================================================================*/
function is_redirect ($sc) { 
    return $sc >= 300 && $sc < 400; 
}

/*=======================================================================*\
    Function:   is_error
    Purpose:    return true if Error status code
\*=======================================================================*/
function is_error ($sc) { 
    return $sc >= 400 && $sc < 600; 
}

/*=======================================================================*\
    Function:   is_client_error
    Purpose:    return true if Error status code, and its a client error
\*=======================================================================*/
function is_client_error ($sc) { 
    return $sc >= 400 && $sc < 500; 
}

/*=======================================================================*\
    Function:   is_client_error
    Purpose:    return true if Error status code, and its a server error
\*=======================================================================*/
function is_server_error ($sc) { 
    return $sc >= 500 && $sc < 600; 
}
#end of rss_fetch.inc

#require_once(MAGPIE_DIR.'rss_utils.inc');

// value of 2 optionally show lots of debugging info but breaks JavaScript
// This should be set to 0 unless debugging
define('MAGPIE_DEBUG', 0);

// Define cache age in seconds.
define('MAGPIE_CACHE_AGE', 60*60);

// OTHER SETTIINGS ----------------------------------------------
// Output spec for item date string if used
// see http://www.php.net/manual/en/function.date.php
$date_format = "F d, Y h:i:s a";


// server time zone offset from GMT
// If this line generates errors (common on Windoze servers,
//   then figure out your time zone offset from GMT and enter
//   manually, e.g. $tz_offset = -7;

$tz_offset = gmmktime(0,0,0,1,1,1970) - mktime(0,0,0,1,1,1970);

// ERROR Handling ------------------------------------------------

// Report all errors except E_NOTICE
// This is the default value set in php.ini for Apache but often not Windows
// We recommend changing the value to 0 once your scripts are working
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL^ E_NOTICE);


// Restrict RSS url to domain
// Example: www.example.org => allows www.example.org and mywww.example.org
// Example: .example.org => allows www.example.org and other.example.org

// remove the comment here to activate url restriction
//$restrict_url = ".example.org";

// comment out this line to activate url restriction
unset($restrict_url);


// Utility to remove return characters from strings that might
// pollute JavaScript commands. While we are at it, substitute 
// valid single quotes as well and get rid of any escaped quote
// characters
function strip_returns ($text, $linefeed=" ") {
	$subquotes = trim( preg_replace( '/\s+/', ' ', $text ) );
	return preg_replace("(\r\n|\n|\r)", $linefeed, $subquotes);
}
#end of config

//  check for utf encoding type
$utf = (isset($_GET['utf'])) ? $_GET['utf'] : 'n';

if ($utf == 'y') {
	#define('MAGPIE_CACHE_DIR', MAGPIE_DIR . 'cache_utf8/');
	// chacrater encoding
	define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');
	

} else {
	#define('MAGPIE_CACHE_DIR', MAGPIE_DIR . 'cache/');
	define('MAGPIE_OUTPUT_ENCODING', 'ISO-8859-1');
}

define('MAGPIE_CACHE_DIR', dirname(__FILE__) . DIR_SEP . '_cache');
// GET VARIABLES ---------------------------------------------
// retrieve values from posted variables

// flag to show channel info
$chan = (isset($_GET['chan'])) ? $_GET['chan'] : 'n';

// variable to limit number of displayed items; default = 0 (show all, 100 is a safe bet to list a big list of feeds)

$num = (isset($_GET['num'])) ? $_GET['num'] : 0;
if ($num==0) $num = 100;

// indicator to show item description,  0 = no; 1=all; n>1 = characters to display
// values of -1 indicate to displa item without the title as a link
// (default=0)
$desc = (isset($_GET['desc'])) ? $_GET['desc'] : 0;

// flag to show author of items, values: no/yes (default=no)
$auth = (isset($_GET['au'])) ? 'y' : 'n';

// flag to show date of items, values: no/yes (default=no)
$date = (isset($_GET['date'])) ? $_GET['date'] : 'n';

// time zone offset for making local time, 
// e.g. +7, =-10.5; 'feed' = print the time string in the RSS w/o conversion
$tz = (isset($_GET['tz'])) ? $_GET['tz'] : 'feed';


// flag to open target window in new window; n = same window, y = new window,
// other = targeted window, 'popup' = call JavaScript function popupfeed() to display
// in new window (default is n)

$targ = (isset($_GET['targ'])) ? $_GET['targ'] : 'n';
if ($targ == 'n') {
	$target_window = ' target="_self"';
} elseif ($targ == 'y' ) {
	$target_window = ' target="_blank"';
} elseif ($targ == 'popup') {
	$target_window = ' onClick="popupfeed(this.href);return false"';
} else {
	$target_window = ' target="' . $targ . '"';
}

// flag to show feed as full html output rather than JavaScript, used for alternative
// views for JavaScript-less users. 
//     y = display html only for non js browsers (NO LONGER USED)
//     n = default (JavaScript view)
//     a = display javascript output but allow HTML 
//     p  = display text only items but convert linefeeds to BR tags

// default setting for no conversion of linebreaks
$html = (isset($_GET['html'])) ? $_GET['html'] : 'n';

$br = ' ';
if ($html == 'a') {
	$desc = 1;
} elseif ($html == 'p') {
	$br = '<br />';
}

// optional parameter to use different class for the CSS container
$rss_box_id = (isset($_GET['css'])) ? '-' . $_GET['css'] : '';

// optional parameter to use different class for the CSS container
$play_podcast = (isset($_GET['pc'])) ? $_GET['pc'] : 'n';


// PARSE FEED and GENERATE OUTPUT -------------------------------
// This is where it all happens!


// check if site has a setting to restrict to a url
if (isset($restrict_url)) {
	$src_host = substr($src, 7);
	$src_pos = strpos($src_host,"/");
	if ($src_pos) {
		$src_host = substr($src_host,0, $src_pos);
	}
}
if (isset($restrict_url) && substr($src_host, strlen($src_host)-strlen($restrict_url)) != $restrict_url) {
	$str.= "<div class=\"rss-box" . $rss_box_id .
		"\"><p class=\"rss-item\"><em>Error:</em> on feed <strong>" .
		$src . "</strong>. " .
		"Feeds are allowed only from URLs from the site http://*" .
		$restrict_url . "</p></div>";
		
} else {


	$rss = @fetch_rss( $src );
	
	// begin javascript output string for channel info
	$str= "<div class=\"rss-box" . $rss_box_id . "\">";
	
	
	// no feed found by magpie, return error statement
	if  (!$rss) {
		$str.= "<p class=\"rss-item\">$script_msg<em>Error:</em> Feed failed! Causes may be (1) No data  found for RSS feed $src; (2) There are no items are available for this feed; (3) The RSS feed does not validate.<br /><br /> Please verify that the URL <a href=\"$src\">$src</a> works first in your browser and that the feed passes a <a href=\"http://feedvalidator.org/check.cgi?url=" . urlencode($src) . "\">validator test</a>.</p></div>";
	
	
	} else {
	
	
		// Create CONNECTION CONFIRM
		// create output string for local javascript variable to let 
		// browser know that the server has been contacted
		$feedcheck_str = "";
	
		// we have a feed, so let's process
		if ($chan == 'y') {
		
			// output channel title and description	
			$str.= "<p class=\"rss-title\"><a class=\"rss-title\" href=\"" . trim($rss->channel['link']) . '"' . $target_window . ">" . addslashes(strip_returns($rss->channel['title'])) . "</a><br /><span class=\"rss-item\">" . addslashes(strip_returns(strip_tags($rss->channel['description']))) . "</span></p>";
		
		} elseif ($chan == 'title') {
			// output title only
			$str.= "<p class=\"rss-title\"><a class=\"rss-title\" href=\"" . trim($rss->channel['link']) . '"' . $target_window . ">" . addslashes(strip_returns($rss->channel['title'])) . "</a></p>";
		
		}	
		
		// begin item listing
		$str.= "<ul class=\"rss-items\">";
			
		// Walk the items and process each one
		$all_items = array_slice($rss->items, 0, $num);
		
		foreach ( $all_items as $item ) {
			
			// set defaults thanks RPFK
			if (!isset($item['summary'])) $item['summary'] = ''; 
			$more_link = '';
			
			// create output for item author
			
			
			$author_str = '';
			if ($auth == 'y') {
				if (isset($item['dc']['creator'])) {
					$author_str = ' <span class="rss-item-auth">(' . addslashes(strip_tags($item['dc']['creator'])) . ')</span>';
				
				} else {
					if (isset($item['author_name'])) {
						$author_str = ' <span class="rss-item-auth">(' . addslashes(strip_tags($item['author_name'])) . ')</span>';	
					}
				}
			
			}
			
			
			
			if ($item['link']) {
				// link url
				$my_url = addslashes($item['link']);
			} elseif  ($item['guid']) {
				//  feeds lacking item -> link
				$my_url = ($item['guid']);
			}
			
			
			if ($desc < 0) {
				$str.= "<li class=\"rss-item\">";
				
			} elseif ($item['title']) {
				// format item title
				$my_title = addslashes(strip_returns($item['title']));
				
							
	
				// write the title strng
				$str.= "<li class=\"rss-item\"><a class=\"rss-item\" href=\"" . trim($my_url) . "\"" . $target_window . '>' . $my_title . '</a>' .  $author_str . "<br />";
	
	
			} else {
				// if no title, build a link to tag on the description
				$str.= "<li class=\"rss-item\">";
				$more_link = " <a class=\"rss-item\" href=\"" . trim($my_url) . '"' . $target_window . ">&laquo;details&raquo;</a>";
			}
		
			// print out date if option indicated
	
			if ($date == 'y') {
						
				if ($tz == 'feed') {
				//   echo the date/time stamp reported in the feed
	
					if ($item['pubdate'] != '') {
						// RSS 2.0 is already formatted, so just use it
						$pretty_date = $item['pubdate'];
					} elseif ($item['published'] != "") {
						// ATOM 1.0 format, remove the "T" and "Z" and the time zone offset
						$pretty_date = str_replace("T", " ", $item['published']);
						$pretty_date= str_replace("Z", " ", $pretty_date);
		
					} elseif ($item['issued'] != "") {
						// ATOM 0.3 format, remove the "T" and "Z" and the time zone offset
						$pretty_date = str_replace("T", " ", $item['issued']);
						$pretty_date= str_replace("Z", " ", $pretty_date);
					} elseif ( $item['dc']['date'] != "") {
						// RSS 1.0, remove the "T" and the time zone offset
						$pretty_date = str_replace("T", " ", $item['dc']['date']);
						$pretty_date = substr($pretty_date, 0,-6);
					} else {
					
						// no time/date stamp, 
						$pretty_date =  'n/a';
					}
	
				} else {
					// convert to local time via conversion to GMT + offset
					
					// adjust local server time to GMT and then adjust time according to user
					// entered offset.
					
					// let's see what kind of timestamps we can pull...
					if ($item['date_timestamp'] != "") {
						$ts = $item['date_timestamp'];
					}  elseif ($item['published'] != "") {
						$ts = strtotime($item['published']);
					} elseif ($item['issued'] != "") {
						$ts = strtotime($item['issued']);
					} elseif ( $item['dc']['date'] != "") {
						$ts = strtotime($item['dc']['date']);
					} else {
						$ts = time();
					}
					
					$pretty_date = date($date_format, $ts - $tz_offset + $tz * 3600);
				
				}
		
				$str.= "<span class=\"rss-date\">$pretty_date</span><br />"; 
			}
	
			// link to podcast media if availavle
			
			if ($play_podcast == 'y' and is_array($item['enclosure'])) {
				$str.= "<div class=\"pod-play-box\">";
				for ($i = 0; $i < count($item['enclosure']); $i++) {
				
					// display only if enclosure is a valid URL
					//if (strpos($item['enclosure'][$i]['url'], 'http://')!=0) {
						$str.= "<a class=\"pod-play\" href=\"" . trim($item['enclosure'][$i]['url']) . "\" title=\"Play Now\" target=\"_blank\"><em>Play</em> <span> " .  substr(trim($item['enclosure'][$i]['url']), -3)  . "</span></a> ";
					//}
				
				}
				
				$str.= "</div>";
			
			}
	
		
			// output description of item if desired
			if ($desc) {
			
				if ($item['atom_content']) {
					// Atom content - note that wordpress.com feeds return bad data here "A"
					// so revert to description if this is the case.
					$my_blurb = ($item['atom_content'] == "A") ? $item['description'] :   html_entity_decode ( $item['atom_content'], ENT_NOQUOTES, MAGPIE_OUTPUT_ENCODING);
				
				} else if ($item['content']) {
				
					
					
					// Atom/encocded content support (thanks David Carter-Tod)
				
					$my_blurb =   html_entity_decode ( $item['content'], ENT_NOQUOTES, MAGPIE_OUTPUT_ENCODING);
				
					
				} else {   
					$my_blurb = $item['summary'];
				}
				
				// strip html
				if ($html != 'a') $my_blurb = strip_tags($my_blurb);
				
				// trim descriptions
				if ($desc > 1) {
				
					// display specified substring numbers of chars;
					//   html is stripped to prevent cut off tags
					//   make sure we dont chop UTF-8 characters
					
					
					if ($utf == 'y') {
						$my_blurb = mb_substr($my_blurb, 0, $desc, 'UTF-8') . '...';
					} else {
						$my_blurb = substr($my_blurb, 0, $desc) . '...';
					}
	
				}
		
			
				$str.= "" . addslashes(strip_returns($my_blurb, $br)) . ""; 
				
			}
				
			$str.= "$more_link</li>";	
		}
	
	
		$str .= "</ul></div>";
	} // end restrict_url
}

// Render as JavaScript
// START OUTPUT
// headers to tell browser this is a JS file
if ($rss) header("Content-type: application/x-javascript"); 

// Spit out the results as the series of JS statements
echo $feedcheck_str . $str;


?>
