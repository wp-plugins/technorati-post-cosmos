a) get an API key from Technorati. Go to
http://technorati.com/developers/apikey.html and get one.

b) Open the plugin and adjust the values.

c) add this (or similar):

<?php technorati_links_entry($before="<li>", $after="</li>", $print="true" ?>

where you want the cosmos for the post to appear - it should be a post, of
course.

d) done.
