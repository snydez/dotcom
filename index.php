<?php
// require_once './PHP-Feed.php';


function curlsimplexml_load_file($feed) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $feed);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);
	curl_close($ch);

	return $output;
}

function curlhttp_get_contents($url)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_TIMEOUT, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  if(FALSE === ($retval = curl_exec($ch))) {
    error_log(curl_error($ch));
  } else {
    return $retval;
  }
}


function readfeed($url,$type) 
	{
		
		
		$rssstring = curlhttp_get_contents($url);
		
		if(!$rss = simplexml_load_string($rssstring)) error_log("Error load : " . $url);
		//$rss = Feed::loadAtom($url);
		
		
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
			case 'tumblr':
				$xml = tumblr($rss);
				break;
			case 'other':
				$xml = rssother($rss);
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
            'title' => strip_tags(substr($entry->title,0,125)),
            'img' => $entry->enclosure->attributes()->url,
            'desc' => strip_tags($entry->description),

        ];
        
	}
	
	
	return $items;
		
}
	

function tumblr($rss) 
	{
	
    	$items = [];
		
	foreach($rss->channel->item as $item) 
		{
			
			
			//$img = catch_that_image(htmlspecialchars($item->description));

			$img = catch_that_image((string)htmlspecialchars_decode($item->description));
			
			$items[] = [
				'link' => htmlspecialchars($item->link),
				'title' => htmlspecialchars($item->title),
				'img' => $img,
				'desc' => htmlspecialchars(strip_tags($item->description)),

			];
			
			
		}
	return $items;
}


function wp_selfhosting($rss) 
	{
	
    $items = [];

    foreach($rss->channel->item as $entry) {
        $image = '';
        $image = 'https://picsum.photos/500/400?blur=1';
        
        $e_content     = $entry->children("content", true);
		$e_encoded     = (string)$e_content->encoded;
		if(!empty($e_encoded)) {
			$image = catch_that_image($e_encoded);
        }
        


        $items[] = [
            'link' => $entry->link,
            'title' => $entry->title,
            'img' => $image,
            'desc' => strip_tags($entry->description),

        ];

    }

	   

	return $items;
}


function blogger_feed($rss) 
	{
		
	$items[] = '';
	
	
	
	foreach($rss->entry as $ritem) 
		{


		$img = catch_that_image((string)htmlspecialchars_decode($ritem->content));

		foreach($ritem->link as $linxx) {

			if($linxx->attributes()->rel == 'alternate') {
				$e_link = $linxx->attributes()->href;
				break;
			}
		}
		
		
		$items[] = [
            'link' => htmlspecialchars($e_link),
            'title' => htmlspecialchars($ritem->title),
            'img' => $img,
            'desc' => strip_tags(htmlspecialchars_decode($ritem->content)),

        ];
		
	}
	
	
	return $items;	
}


function wpcom($rss) 
	{
	
    $items = [];

    foreach($rss->channel->item as $entry) {
        $image = '';
        $image = 'https://picsum.photos/500/400?blur=1';
        foreach ($entry->children('media', true) as $k => $v) {
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
            'img' => $image,
            'desc' => strip_tags($content_data),

        ];

    }

		  
	return $items;
}



function rssother($rss)
	{
		
	$items = [];
		
	foreach($rss->channel->item as $item) 
		{
			
			
			$img = catch_that_image(htmlspecialchars($item->description));
			
			$items[] = [
				'link' => htmlspecialchars($item->link),
				'title' => htmlspecialchars($item->title),
				'img' => $img,
				'desc' => htmlspecialchars(strip_tags($item->description)),

			];
			
			
		}
	return $items;
}


	
	


function parsetemplate($template, $items, $nstart=1, $nend=99) 
	{
		
		$html = '';
		$x=0;
				
		if($nend==99) { $nend = sizeof($items); }
		
		for($x=$nstart; $x<$nend; $x++) {
			
						
				$html .= str_replace('{url_post}',  $items[$x]['link'] , $template);
				$html = str_replace('{title_post}', $items[$x]['title'] , $html);
				$html = str_replace('{desc}', $items[$x]['desc'], $html);
				$html = str_replace('{img_src}', $items[$x]['img'] , $html); 
				$html = str_replace('http://','https://', $html);
		
		
			
		}
		
		

return $html;	
	
}


function getTemplate() 
	{
		$file = './template.html';
		if (file_exists($file)) {
			return file_get_contents($file);
		} else {
			return ' {slick_slider} ';
		}
	}

function getContent($clear)
	{

	// Thanks to https://davidwalsh.name/php-cache-function for cache idea

	$file = "./feed-cache.txt";
	$current_time = time();
	$expire_time = 5 * 60;
	$file_time = filemtime($file);
	
	if (file_exists($file) && ($current_time - $expire_time < $file_time)  && $clear!=1 )
		{
		return file_get_contents($file);
		}
	  else
		{
		$content = getFreshContent();

		file_put_contents($file, $content);
		return $content;
		}
	}

function getFreshContent()
	{
	$template = getTemplate();
	$html = $template;
	


	// digelasin

	$url = 'https://digelasin.blogspot.com/feeds/posts/default?feed=rss';
	$rss = readfeed($url,'blogger');
	
	
	$repeattemplatesmall = '<li> <div class="media wow fadeInDown" style="height: 124px; overflow:hidden"> <a class="media-left" href="{url_post}"><img src = "{img_src}" alt="" ></a> <div class="media-body"> <h4 class="media-heading"><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h4> <div class="comments_box"> {desc} </div> </div> </div> </li>';
	$templatebusiness_cat = parsetemplate($repeattemplatesmall, $rss,1,4);
	$html =  str_replace('{business_cat}', $templatebusiness_cat, $html);
	
	$repeattemplate = '<li>'
                  . '<div class="catgimg2_container"> <a href="{url_post}"><img alt="" src="{img_src}"></a> </div>'
                  . '<h2 class="catg_titile" style="width:100%"><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h2>'
				  . '<p>{desc}</p>'
                  . '</li>';
    $templatebusiness_cat2 = parsetemplate($repeattemplate, $rss, 5,6);
	$html =  str_replace('{fashion_catgnav}', $templatebusiness_cat2, $html);
	
	// ipad2ismine
	
	$url = 'https://ipad2ismine.blogspot.com/feeds/posts/default?feed=rss';
	$rss = readfeed($url, 'blogger');
	
	$templatetech2 = parsetemplate( $repeattemplatesmall, $rss,1,4);
	$html =  str_replace('{tech_2}', $templatetech2, $html);
	
	$templatetech1 = parsetemplate( $repeattemplate, $rss,4,5);
	$html =  str_replace('{tech_1}', $templatetech1, $html);
	
	
	
	// photoinpicture
	
	$url = 'https://photoinpicture.wordpress.com/feed/'; 
	$rss = readfeed($url,'wpcom');
	//$rss = readfeed($url,'atom');
	$repeattemplate = '<div class="single_iteam"><img src="{img_src}" alt="" style="width:560px; height:auto;"><h2><a class="slider_tittle" href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h2></div>';
	$templatekanan_atas = parsetemplate($repeattemplate, $rss);
	$html =  str_replace('{slick_slider}', $templatekanan_atas, $html);


	// flickr
	
	$url = 'https://www.flickr.com/services/feeds/photos_public.gne?id=73953834@N00&lang=en-us&format=rss2';
	
	//'https://www.flickr.com/services/feeds/photos_public.gne?id=73953834@N00&lang=en-us&format=rss';
	
	// https://www.flickr.com/services/feeds/photos_public.gne?id=73953834@N00&lang=en-us&format=atom

	$rss = readfeed($url, 'atom');
	
	// $rss = readfeed($url, 'atom');
	$repeattemplate = '<li style=""><img src="{img_src}" alt="" > <div class="title_caption"  ><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></div>  </li>';
	$templateslick_slider =  parsetemplate($repeattemplate, $rss, 2,6);   
	$html =  str_replace('{kanan_atas}', $templateslick_slider, $html);
	
	//// robothijau
	
	$url = 'https://robothijau.blogspot.com/feeds/posts/default?feed=rss';
	$rss = readfeed($url, 'blogger');
	$repeattemplate = '<li> <div class="catgimg_container" style="max-width: 292px; max-heigth:150px; overflow: hidden;"> <a href="{url_post}" class="catg1_img"><img alt="" src="{img_src}" style="width:292px; height:auto;"></a></div> <h3 class="post_titile"><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h3> </li>';
	$templateslick_slider =  parsetemplate($repeattemplate, $rss,3,5);
	$html =  str_replace('{single_category}', $templateslick_slider, $html);
	
	//// snydezwp
	
	$url = 'https://snydez.wordpress.com/feed/';
	$rss = readfeed($url, 'wpcom');
	
	$templatecatg1 = parsetemplate($repeattemplate, $rss,1,3);
	$html =  str_replace('{catg1_nav_kanan}', $templatecatg1, $html);
	
	// jurnal
	
	$url = 'https://jurnal.snydez.com/feed/';
	//$rss = readfeed($url, 'wp_selfhosting');
	//$rss = readfeed($url, 'atom');
	$rss = readfeed($url, 'wp_selfhosting');
	$repeattemplate = '<div class="single_featured_slide"> <div style="max-width:567px; max-height:330px; overflow:hidden"><a href="{url_post}"><img src="{img_src}" alt="" style="width:567px; height:auto" ></a></div> <h2><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h2> <p>{desc}</p> </div>';
	$templateslick_slider2 = parsetemplate($repeattemplate, $rss);
	$html =  str_replace('{slick_slider2}', $templateslick_slider2, $html);

	//// terkap
	
	$url = 'https://terkap.blogspot.com/feeds/posts/default?feed=rss';
	$rss = readfeed($url, 'blogger');
	$repeattemplatesmall = '<li>'
							. '<div class="media wow fadeInDown" style="height: 130px; overflow:hidden"> <a href="{url_post}" class="media-left"><img alt="" src="{img_src}"> </a>'
							. '<div class="media-body">'
							. '<h4 class="media-heading"><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h4>'
							. '<p>{desc}</p>'
							. '</div> </div>'
							. '</li>';

	$templaterecentpost = parsetemplate($repeattemplatesmall, $rss,1,4);
	$html =  str_replace('{recent_post}', $templaterecentpost, $html);
	
	
		
	//// tumblr
	
	$url = 'https://snydez.tumblr.com/rss';
	$rss = readfeed($url, 'tumblr');  //-- other
	$templaterecentpost = parsetemplate($repeattemplatesmall, $rss,1,4);
	$html =  str_replace('{most_popular}', $templaterecentpost, $html);
	
	
	//// medium
	
	$url = 'https://medium.com/feed/@snydez';
	$rss = readfeed($url, 'atom');

	$templaterecentpost = parsetemplate($repeattemplatesmall, $rss,1,4);
	$html =  str_replace('{recent_comment}', $templaterecentpost, $html);
	
	
	
	///// instagram
// udah berbayar ;(
//	$url = 'https://queryfeed.net/instagram?q=snydez';

// feed no longer working
	$url = 'https://fetchrss.com/rss/5e1fd4008a93f8a55f8b45675e1fd3d48a93f8345d8b4567.atom';
	$url = 'https://rss.app/feeds/OZThAKutmgmxRzUp.xml';
// ---	
	
	$url = 'https://rss.app/feeds/C3tJeuxhkIZ19mBO.xml';
	$rss = readfeed($url, 'wpcom');
	$repeattemplatebawah = '<li>'
					. '<div class="media"> <a class="media-left" href="{url_post}"><img src="{img_src}" alt=""></a>'
                    . '<div class="media-body">'
                    .  '  <h4 class="media-heading"><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h4>'
                    . '</div>'
                    . '</div>'
                    . '</li>';
	$templategamesbawah = parsetemplate($repeattemplatebawah, $rss,1,3);
	$html =  str_replace('{games_bawah}', $templategamesbawah, $html);
	
	$repeattemplateatas = '<li>'
						. '<div class="catgimg2_container"> <a href="{url_post}"><img alt="" src="{img_src}"></a> </div>'
						. '<h2 class="catg_titile"><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h2>'
						. '<div><p>{desc}</p></div>'
						. '</li>';
	$templategamesatas = parsetemplate($repeattemplateatas, $rss,4,5);
	$html =  str_replace('{games_atas}', $templategamesatas, $html);
	
	//// ojapuga
	
	$url = 'https://ojapuga.tumblr.com/rss';
	$rss = readfeed($url, 'tumblr');  // -- other
	
	$templategamesbawah = parsetemplate($repeattemplatebawah, $rss,1,3);
	$html =  str_replace('{fashion_bawah}', $templategamesbawah, $html);
	
	$templategamesatas = parsetemplate($repeattemplateatas, $rss,4,5);
	$html =  str_replace('{fashion_atas}', $templategamesatas, $html);
	
	/* RSS penyedian untuk intagram sudah berbayar

	// street instagram
	
	// no longer working
	$url = 'https://queryfeed.net/instagram?q=zedyns';
	$url = 'https://rss.app/feeds/qVCYOvz5GgfzTnf6.xml';
	///---
	
	$url = 'https://rss.app/feeds/VGFmRHEZ5L6zE5vC.xml';
	$rss = readfeed($url, 'wpcom');
	$repeattemplate = '	<li><a href="{url_post}"><img src="{img_src}" alt=""></a></li>';
	$parseds = parsetemplate($repeattemplate, $rss, 1, 8);
	$html = str_replace('{street_instagram}', $parseds, $html);
		
	*/
	
	// street ini coba diganti dengan stripgenerator
	
	$url = 'http://snydez.stripgenerator.com/rss/';
	
	$rss = readfeed($url, 'tumblr');
	$repeattemplate = '<div class="single_featured_slide"> <div style=" max-height:150px; overflow:hidden"><a href="{url_post}"><img src="{img_src}" alt="" style="width:auto; height:150px" ></a></div> <h2><a href="{url_post}" style="text-transform:capitalize;">{title_post}</a></h2> <p>{desc}</p> </div>';
	$repeattemplate = '	<li><a href="{url_post}"><img src="{img_src}" alt=""></a></li>';
	$templateslick_slider3 = parsetemplate($repeattemplate, $rss);
	$html = str_replace('{stripgenerator}', $templateslick_slider3, $html);
		
	// snydez blogspot
	
	$url = 'https://snydez.blogspot.com/feeds/posts/default?feed=rss';
	$rss = readfeed($url, 'blogger');
	$repeattemplate = ' <li><a href="{url_post}">{title_post}</a></li>';
	$parseds = parsetemplate($repeattemplate, $rss, 1, 7);
	$html =  str_replace('{scrap}', $parseds, $html);
	
	//gravatar
	$gravatar = GravatarAsFavicon(); 
	$html = str_replace('{fave_icon}', $gravatar   , $html);
	
	return $html;

	}

function catch_that_image($desc)
	{
	$first_img = '';
	ob_start();
	ob_end_clean();
	
	$output = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $desc, $matches); 
	$first_img = $matches[1][0];
	if (empty($first_img))
		{ //Defines a default image
		$first_img = "https://picsum.photos/300/150?blur=1&random=" . rand(1,9);
		}

	return $first_img;
	}


function GravatarAsFavicon() {
    $hashedEmail = md5(strtolower(trim('gembel@yahoo.com')));
    return 'https://www.gravatar.com/avatar/' . $hashedEmail . '?s=16';
}


print getContent($_GET['clear']);

?>
 
