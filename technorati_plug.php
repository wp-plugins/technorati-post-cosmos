<?php

/*
Plugin Name: Technorati Tag
Plugin URI: http://www.jluster.org
Description: This plugin implements some technorati functions you might find useful.
Author: Jonas M Luster
Version: 1.0
Author URI: http://www.jluster.org
*/ 

require_once(dirname(__FILE__).'../../../' .'wp-config.php');
require_once(dirname(__FILE__).'/technorati/xmlParser.php');

/* Config Section */

$API_KEY = "NONE";
$CACHE_PATH = "/usr/u/www/blog/tcache"; /* must be accessible and writable to the webserver */
$CACHE_TIMEOUT = 6 * 60 * 60;           /* six hours, split out for easy editing :) */
                                        /* you want to set this to a healthy value, remember: 500 queries a day! */
$TAG_LIMIT = 10;                        /* how many tagged posts to retrieve for the tags query */

/* You should be fine with not touching anything below this line */

function _add_technorati_post_links($id) {
   /* Get and set the current time. No need to do this every time */
   $update_me = false;
   add_option('jl_technorati_last_updated', '0');
   
   if (get_option('jl_technorati_last_updated') <= (time() - $CACHE_TIMEOUT)) {
      $update_me = true;
      update_option('jl_technorati_last_updated', time());
   }
   
   if ($update_me) {
   /*   $url = get_permalink($id);
      $curl = "http://api.technorati.com/cosmos?format=xml&url=$url&key=$API_KEY";
      $my_xml = technorati_file_contents($curl, 'r', $CACHE_TIMEOUT, $CACHE_PATH, 0);
      $p = new XMLParser;
      $p->definens('TECHNORATI');
      $p->setXmlData($my_xml);
       $p->buildXmlTree();
       $struct = $p->getXmlTree();
       
       if ($struct[0]['children'][0]['children'][0]['children'][0]['tag'] == "TECHNORATI:ERROR") {
          if ($print) {
             $output .= "$before<b>Encountered an error, please notify site admin or wait a bit...</b>$after";
          }
          else {
             return 0;
          }
       } 
       else {
          $i = 0;
          foreach ( $struct[0]['children'][0]['children'] as $pkey => $pvalue ) {
             if ($pvalue['tag'] == "TECHNORATI:ITEM") { $inbound[$i++] = technorati_parse_item($pvalue['children']); }
          }      
      }
      if ($comments) {
         foreach ($comments as $comment) {
            
         }
      }
   */
   }
}

function technorati_tags_entry($before="<li>", $after="</li>", $between="<br />", $show_blog=true, $show_excerpt=false, $print="true") {
   global $API_KEY, $CACHE_TIMEOUT, $CACHE_PATH;

   $cats = get_the_category();

   foreach ($cats as $cat) {
      $tagname = $cat->cat_name;
      $curl = "http://api.technorati.com/tag?format=xml&tag=$tagname&key=$API_KEY&limit=$TAG_LIMIT";
      $my_xml = technorati_file_contents($curl, 'r', $CACHE_TIMEOUT, $CACHE_PATH, 0);
      $p = new XMLParser;
      $p->definens('TECHNORATI');
      $p->setXmlData($my_xml);
      $p->buildXmlTree();
      $struct = $p->getXmlTree();
      $i = 0;
      foreach ($struct[0]['children'][0]['children'] as $pkey => $pvalue) {
         if ($pvalue['tag'] == "TECHNORATI:ITEM") { $inbound[$i++] = technorati_parse_item($pvalue['children']); }
      }
      foreach ($inbound as $key => $value) {
         $plink = $value['itempermalink'] ? $value['itempermalink'] : $value['itemurl'];
         $excerpt = $show_excerpt ? $between . $value['excerpt'] : '';
         $name = $show_blog ? "(".$value['name'].")" : '';
         $title = $value['itemtitle'];
         
         if ($plink != $linkname) {
            $linkname = $plink;
            print "$before<a href='".$plink."'>$title</a> $name$excerpt$after";
         }
      }
   }   

}

function technorati_links_entry($before="<li>", $after="</li>", $print="true") {
   global $API_KEY, $CACHE_TIMEOUT, $CACHE_PATH;

   $url = get_permalink();
   $curl = "http://api.technorati.com/cosmos?format=xml&url=$url&key=$API_KEY";
   $my_xml = technorati_file_contents($curl, 'r', $CACHE_TIMEOUT, $CACHE_PATH, 0);

   $p = new XMLParser;
   $p->definens('TECHNORATI');

   $p->setXmlData($my_xml);
   $p->buildXmlTree();
   $struct = $p->getXmlTree();
   
   if ($struct[0]['children'][0]['children'][0]['children'][0]['tag'] == "TECHNORATI:ERROR") {
      if ($print) {
         $output .= "$before<b>Encountered an error, please notify site admin or wait a bit...</b>$after";
      }
      else {
         return 0;
      }
   } 
   else {
      $i = 0;
      foreach ( $struct[0]['children'][0]['children'] as $pkey => $pvalue ) {
         if ($pvalue['tag'] == "TECHNORATI:ITEM") { $inbound[$i++] = technorati_parse_item($pvalue['children']); }
      }      
      
      if (!$inbound) {
         if ($print) {
            print "<b>None, yet!</b>";
            return 0;
         }
         else {
            return 0;
         }
      }
      
      if ($print) {
         foreach ($inbound as $key => $value) {
            $plink = $value['permalink'] ? $value['permalink'] : $value['itemurl'];
            // $output .= $before ."<b><a href="'.$plink.'">'.$value['name'].'</a> ($value['created'])</b><br />$value['excerpt']$after";
            $output .= $before ."<b><a href='".$plink."'>". $value['name'] ."</a> (". $value['created'] .")</b><br />". $value['excerpt'] . $after;
         }
      print $output;
      }
      else {
         return $inbound;
      }
   }
}

/* parser routines */

function technorati_get_value_from_cosmos($struct, $gvalue="NAME") {
  foreach ( $struct as $key => $value ) {
    if ($value['tag'] == "TECHNORATI:$gvalue") { return $value['children'][0]; }
  }
}

function technorati_parse_item($structure) {
  $geturl = 0;
  foreach( $structure as $key => $value ) {
    switch ( $value['tag'] ) {
      case "TECHNORATI:NEARESTPERMALINK":
        $returnval['permalink'] = $value['children'][0];
        break;
      case "TECHNORATI:EXCERPT":
        $returnval['excerpt'] = $value['children'][0];
        break;
      case "TECHNORATI:LINKCREATED":
        $returnval['created'] = $value['children'][0];
        break;
      case "TECHNORATI:LINKURL":
          $returnval['url'] = $value['children'][0];
          break;
      case "TECHNORATI:PERMALINK":
         $returnval['itempermalink'] = $value['children'][0];
         break;
      case "TECHNORATI:TITLE":
         $returnval['itemtitle'] = $value['children'][0];
         break;
      case "TECHNORATI:WEBLOG":
          $returnval['name'] = technorati_get_value_from_cosmos($value['children'], "NAME");
          $returnval['itemurl'] = technorati_get_value_from_cosmos($value['children'], "URL");
          break;
    }
  }
  return $returnval;
}

function technorati_parse_outbound($structure) {
  foreach( $structure as $key => $value ) {
    switch ( $value['tag'] ) {
      case "TECHNORATI:URL":
          $returnval['url'] = $value['children'][0];
          break;
      case "TECHNORATI:NAME":
          $returnval['name'] = $value['children'][0];
    }
  }
  return $returnval;
}

function technorati_get_undefweblog($structure) {
  # Technorati's XML structure is wild. We dive deep until we find the WEBLOG tag, which then translates into
  # a following list of "real" data.
  foreach ( $structure as $key => $value ) {
    if ($value['tag'] == "TECHNORATI:WEBLOG") {
      return $value['children'];
    }
  }
}

function technorati_parse_undefweblog($structure) {
  # This one's a doozy as well. Just traverse the list and populate an array with stuff we care about.
  if (!$structure) { return; }
  foreach ( $structure as $tkey => $kval ) {
    switch ($kval['tag']) {
      case "TECHNORATI:URL":
        $bloginfo['url'] = $kval['children'][0];
        break;
      case "TECHNORATI:RANK":
        $bloginfo['rank'] = $kval['children'][0];
        break;
      case "TECHNORATI:INBOUNDLINKS":
        $bloginfo['inboundlinks'] = $kval['children'][0];
        break;
      case "TECHNORATI:INBOUNDBLOGS":
        $bloginfo['inboundblogs'] = $kval['children'][0];
        break;
      case "TECHNORATI:LASTUPDATE":
        $bloginfo['lastupdate'] = $kval['children'][0];
        break;
    }
  }
  return $bloginfo;
}

/* FS routines */

function technorati_file_contents($file, $file_mode, $cache_to, $cache_pa, $debug = false) {
	clearstatcache();
	$cache_filename = $cache_pa . "/" . urlencode($file) .".cached";
	if (( @file_exists($cache_filename) && (( @filemtime($cache_filename) + $cache_to) > ( time())))) 
	{ /* TODO: stats code */ } 
	else {
		$f = fopen($file,"r"); 
		$f2 = fopen($cache_filename,"w+"); 
		while ($r = fread($f,8192)) { fwrite($f2,$r); }
		fclose($f2);
		fclose($f);
	}
	return file_get_contents ($cache_filename);
}

?>
