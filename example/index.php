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

$editor_content = (isset($_POST['editor']) && !empty($_POST['editor'])) ? $_POST['editor'] : "";
$editor_content = !empty($editor_content) ? mspaces_images($editor_content, false, true) : "";
$editor_content_to_view = !empty($editor_content) ? mspaces_images($editor_content, true, false) : "";
	
?>
<!DOCTYPE HTML>
<html dir="ltr" xml:lang="pt-br" lang="pt-br">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>TinyMCE 4.x File Upload Plugin</title>
		<style type="text/css">
			body{
			    font-family: Helvetica, Arial, Tahoma;
			    font-size: 12px;
			    color: #505050;
			    margin: 0;
			}
			form, input, textarea{
				margin: 0;
			}
			input[type="submit"]{
				border: none;
				background: #008fc1;
				color: #fff;
				font-weight: 700;
				height: 40px;
				line-height: 38px;
				font-family: inherit;
				font-size: inherit;
				border-bottom: 2px solid #006481;
			}	
			
			h1{
				margin: 0 0 20px 0;
			}
					
			.mbox{
				width: 900px;
				margin: 20px auto 20px auto;
			}
			
			.bbuttons{
				text-align: center;
				margin-top: 20px;
			}
			
			.edprev{
				border-top: 1px solid #c4c4c4;
				padding-top: 30px;
				margin-top: 30px;
			}
			
			.edprev .both{
				clear: both;
				height: 1px;
			}
			
			.edprev .content{
				font-style: normal;
				line-height: 21px;
				font-size: 14px;				
			}
			
			.edprev .content p{
				margin: 0;
				padding: 0;
			}
		</style>
		<link rel="stylesheet" href="lightbox2/lightbox.min.css" type="text/css">
		<script type="text/javascript" src="jquery-3.3.1.min.js"></script>
		<script type="text/javascript" src="tinymce/tinymce.min.js"></script>
		<script type="text/javascript" src="lightbox2/lightbox.min.js"></script>
		<script type="text/javascript">
			tinymce.init({
				selector: '#editor',
				plugins: [
					'advlist autolink link image lists charmap anchor fileupload preview',
					'searchreplace visualchars code fullscreen media nonbreaking',
					'table contextmenu directionality textcolor paste textcolor colorpicker textpattern'
				],
				toolbar1: 'newdocument preview | cut copy paste | undo redo | searchreplace charmap | bullist numlist | outdent indent | image media table | removeformat code fullscreen',
				toolbar2: 'fontselect fontsizeselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | forecolor backcolor | subscript superscript | link unlink anchor',
				menubar: false,
				toolbar_items_size: 'small',
				statusbar: false,
				relative_urls: false,
				image_advtab: true,
				content_css: ['editor.css']			
			});	
		</script>
	</head>
	<body>
		<div class="mbox">
			<h1>TinyMCE 4.x File Upload Plugin</h1>
			<form name="frm_editor" method="post" action="index.php">
				<div>
					<textarea id="editor" name="editor" style="width: 100%; height: 400px"><?=$editor_content?></textarea>
				</div>
				<div class="bbuttons">
					<input type="submit" value="Submit to view" style="width: 130px">
				</div>
			</form>
			<?php if(!empty($editor_content_to_view)){ ?>
			<div class="edprev">
				<h1>View</h1>
				<div class="content"><?=$editor_content_to_view?></div>
				<div class="both"><!-- --></div>
			</div>
			<?php }?>
		</div>
	</body>
</html>