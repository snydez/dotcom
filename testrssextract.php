<?php
    $url = "http://rss.nytimes.com/services/xml/rss/nyt/Sports.xml"; // xmld.xml contains above data
    
	$url = 'https://photoinpicture.wordpress.com/?feed=rss2';
	$url = 'https://jurnal.snydez.com/feed/rss2/';
	$url='http://snydez.blogspot.com/feeds/posts/default?alt=rss';
	
    $feeds = file_get_contents($url);
    $rss = simplexml_load_string($feeds);
	
	   

    foreach($rss->channel->item as $entry) {
        $image = '';
        $image = 'N/A';
        echo "<p>yyyy</p>";
        // foreach ($entry->children('content', true) as $k => $v) {
        foreach ($entry->children('figure', true) as $k => $v) {
			
			echo "<p>xxx</p>";
            $attributes = $v->attributes();

            if (count($attributes) == 0) {
                continue;
            } else {
                $image = $attributes->url;
            }
        $content_data = (string)$entry->children("media", true)->description;
        }


        $items[] = [
            'link' => $entry->link,
            'title' => $entry->title,
            'image' => $image,
            'Desc' =>$entry->description,

        ];

    }

    //print_r($items);

       $i=0; 
foreach ($items as $item) {
 if ($i < 3) {

  printf('<img src="%s">', $item['image']);
  printf('<a href="%s">%s</a>', $item['link'], $item['title']); printf('<p>%s</p>', $item['Desc']);
   $i++; 

  
  } 
  
  

}

  function countdim($array)
{
    if (is_array(reset($array)))
    {
        $return = countdim(reset($array)) + 1;
    }

    else
    {
        $return = 1;
    }

    return $return;
}

    ?>
