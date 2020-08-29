<?php

function readfeed($url,$type) 
	{
		$rss = simplexml_load_file($url);
		//print_r($rss);
		// $rss = new SimpleXMLElement($url);
		
		$xml[] = '';
		
		switch($type) {
			case 'blogger':
				$xml = blogger_feed($rss);
			
				break;
			case 'wpcom':
				$xml = wpcom($rss);
				break;
			case 'wp_selfhosting':
				$xml = wp_selfhosting($rss);
				break;
			case 'atom':
				$xml = atom($rss);
				break;	
			case 'other':
				$xml = rssother($rss);
				break;
			case 'flickr':
				$xml = rssflickr($rss);
				break;
		}	

		
		return $xml;
		
	
}

function atom($rss)
	{
		
	$items = [];
	
	foreach($rss->channel->item as $entry) {
		 $items[] = [
            'link' => $entry->link,
            'title' => $entry->title,
            'img' => $entry->enclosure->attributes()->url,
            'desc' => strip_tags($entry->description),

        ];
        
	}
	
	
	return $items;
		
}

function rssflickr($rss) 
	{
	
	$items = [];
	
	// $abc = new SimpleXMLElement($rss);
	
	echo "<P>title " . $rss->channel->item->title;
	echo "<P>description " . $rss->channel->item->description;
	echo "<P>url  " . $rss->channel->item->enclosure->attributes()->url;
	
	foreach($rss as $x) {
		
		echo "<P>cd" . $x[0];
		
	}
	
	
	echo "aaa</p>";
	
	/*
	foreach ($rss->channel->item as $entry) {
			$e_content     = $entry->children("content", true);
			$e_encoded     = (string)$e_content->encoded;
		
			echo "<P>XXX" . $e_encoded;
		}
	
	*/
	}

	
	$url = 'https://www.flickr.com/services/feeds/photos_public.gne?id=73953834@N00&lang=en-us&format=rss2';
	
	// $a = readfeed($url, 'flickr');
	$b = readfeed($url, 'atom');
	print_r($b);

?>
 
