a) get an API key from Technorati. Go to
http://technorati.com/developers/apikey.html and get one.

b) Open the plugin and adjust the values.

c) add this (or similar):

<?php technorati_links_entry($before="<li>", $after="</li>", $print="true" ?>

where you want the cosmos for the post to appear - it should be a post, of
course.

add this one to your single-post sidebar:

<?php technorati_tags_entry($before="<li>", $after="</li>", $between="<br />",
  $show_blog=true; $show_excerpt=false, $print="true"); ?>

the options should be fairly self explanatory. If not, see:

http://www.jluster.org/archives/86/new-version-of-technorati-plugin-released/

lastly, add this to the sidebars you want it in:

<?php technorati_weblog_stats($before="<li>", $after="</li>"); ?>

to show your basic stats.

d) done.
