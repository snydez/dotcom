<?php 
// --------------------------------------------------------------------

$feed_url = 'https://jurnal.snydez.com/feed/rss2/';	
$feed_url='http://robothijau.blogspot.com/feeds/posts/default?feed=rss';
$feed_url = 'https://queryfeed.net/instagram?q=snydez';
//$feed_$url='http://robothijau.blogspot.com/feeds/posts/default?feed=rss';
$xml_data = simplexml_load_file($feed_url);



// -------------------------------------------------------------------- 

$i=0;
foreach($xml_data->channel->item as $ritem) { 
 
//foreach($xml_data->entry as $ritem) { 

// -------------------------------------- 

$e_title       = (string)$ritem->title; 
//$e_link        = (string)$ritem->link;
//$e_url = $ritem->children("link",true);
//$e_link = (string)$ritem->link->alternate;

foreach($ritem->enclosure as $linxx) {
	
		if($linxx->attributes() == 'url') {
			$e_link = $linxx->attributes()->url;
			break;
		}
	}
 
$e_pubDate     = (string)$ritem->pubDate; 
$e_pubDate = (string)$ritem->published;
$e_description = (string)$ritem->description; 
$e_guid        = (string)$ritem->enclosure;
$x = $ritem->category->attributes()->term;
$e_guid = $x; 



$e_content     = $ritem->children("content", true);
$e_encoded     = (string)$e_content->encoded; 
$e_encoded = (string)htmlspecialchars_decode($ritem->content);

$n = ($i+1);

// -------------------------------------- 

print '<p> ---------- '. $n .' ---------- </p>'."\n";

print "\n"; 
print '<div class="entry" style="margin:0 auto; padding:4px; text-align:left;">'."\n"; 
print '<p> Title: '. $e_title .'</p>'."\n"; 
print '<p> Link:  '. $e_link .'</p>'."\n"; 
print '<p> Date:  '. $e_pubDate .'</p>'."\n"; 
print '<p> Desc:  '. $e_description .'</p>'."\n"; 
print '<p> Guid:  '. $e_guid .'</p>'."\n"; 
print '<p> Content: </p>'."\n"; 
print '<p style="background:#DEDEDE">'. $e_encoded .'</p>'."\n"; 
print '</div>'."\n"; 


// -------------------------------------- 

print '<br />'."\n"; 
print '<br />'."\n";

$i++; 
} 

// -------------------------------------------------------------------- 
?>
