<?php
	
/*
This function find the images ("img" tags) in the html content and resize the images
and/or add a link ("a" tag) to a larger version of the image.
Don't forget to change the value of the $space_api_key variable with your Space api key.
*/
function mspaces_images($htm, $adlink = true, $resize = true){
	preg_match_all('/<img[^>]+>/i', $htm, $result);
	$dax = [];
	$space_api_key = 'my_space_key_with_41_chars';

	foreach($result[0] as $im){
		$pts = [];
		preg_match_all('/(width|height|src|alt)=("[^"]*")/i', $im, $pts);
		$atr = [];
		foreach($pts[1] as $k => $n) $atr[$n] = trim($pts[2][$k], '"');
		if(!isset($atr['width']) || !isset($atr['height'])) continue;
		
		if(preg_match("/(http|https):\/\/(.*?)$/i", $atr['src'], $ur) < 1) continue;
		$ur = explode("/", $ur[2]);
		if(
			count($ur) == 4
			&& substr_count($ur[0], ".") == 2
			&& substr($ur[0], 0, 2) == "sp"
			&& strstr($ur[0], '.') == ".mgniers.com"
		){
			$p = [
				'server' => $ur[0],
				'space_name' => $ur[1],
				'id' => $ur[2],
				'uri' => $ur[3],
				'png' => strtolower(substr($ur[3], -4)) == '.png' ? true : false
			];
			
			if($resize){
				$url = 'https://'.$p['server'].'/'.$p['space_name'].'/'.$p['id'].'/resize/'.$p['uri'];
				foreach(['limited', 'fixed'] as $type){
					$pm = ['type' => $type, 'quality' => ($p['png'] ? 1 : 80)];
					switch($type){
						case 'limited':
							$pm = array_merge($pm, ['width' => 1200, 'height' => 750]);
						
						break;
						case 'fixed':
							if(isset($atr['width'])) $pm['width'] = $atr['width'];
							if(isset($atr['height'])) $pm['height'] = $atr['height'];
							$pm['replace'] = true;			
						
						break;
					}
		
					$options = [
					    'http' => [
					        'header' => "Content-Type: application/json\r\n"."x-key: ".$space_api_key."\r\n",
					        'method' => 'POST',
					        'user_agent' => 'free_uapi-1.0',
					        'ignore_errors' => true,
					        'content' => json_encode($pm)
					    ],
					    'ssl' => ['verify_peer' => false]
					];
					$context  = stream_context_create($options);
					file_get_contents($url, false, $context);
				}
			}
		}
		
		if($adlink && !in_array($im, $dax)){
			$htm = str_replace($im, '<a href="//'.$p['server'].'/'.$p['space_name'].'/'.$p['id'].'/zoom/'.$p['uri'].'" target="_blank" data-lightbox="pic-set"'.((isset($atr['alt']) && !empty($atr['alt']))?' data-title="'.$atr['alt'].'"':'').'>'.$im.'</a>', $htm);
			$dax[] = $im;
		}
	}	
	return $htm;	
}	