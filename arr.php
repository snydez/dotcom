<?php


 $items[] = '';
 
 
 $items[] = ['link' => 'httpx',
              'image' => 'imagex',
              'desc' => 'descx',
              ];
              
 $items[] = ['link' => 'yhttp',
              'image' => 'ymaing',
              'desc' => 'ydse',
              ];
              
  echo  'count items' . count($items);
  
  echo '<p>items[1]'  . $items[1] ;
  
  echo '<p>items[1][1]'  . $items[1][1] ;

echo '<p>items[1]->link'  . $items[1]->link ;

echo '<p>items[1][link]'  . $items[1]['link'] ;
    
echo '<p>items[2][link]'  . $items[2]['link'];

echo '<P>$items[1]->link; ' . $items[1]->link; 
echo '<P>$item[1]->link; ' . $item[1]->link; 

  
echo '<p>items->link'  . $items->link ;
   
  foreach($items as $tt) {
	  
	  
//	echo '<P>'. print_r($tt) . '</P>';  
	echo '<P>' . $tt['image'] .'</P>';  
  } 
  
  echo '<P>sizeof items ' . sizeof($items);
  
  
  
  
  foreach($items as $item) {
	
	echo '<P>sizeof item ' . sizeof($item);
	
	  foreach($item as $att) {
			echo '<P>att ' . $att; 
	  }
	  
  }
         

?>
