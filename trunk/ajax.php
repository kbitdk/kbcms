<?

// Functions
function page($urlOrg,$cfg) {
	if(!isset($_SESSION['user'])) return json_encode(array('redirect','.'));
	if(($qPos=strpos($urlOrg,'?'))!==false) {
		$qs = substr($urlOrg,$qPos+1);
		$url = substr($urlOrg,0,$qPos);
	} else $url = $urlOrg;
	switch($url) {
		case 'design':
			$design = KBTB::html_encode($cfg['design']);
			$content = <<<EOF
<h1>Design</h1>
<form onsubmit="return formHandler(this);">
	<input type="hidden" name="a" value="designChange"/>
	<textarea name="design" style="width:700px; height:350px;">$design</textarea>
	<input type="submit" value="Submit"/>
</form>
EOF;
			break;
		case 'pageEdit':
			foreach($cfg['pages'] as $i=>$pageCurr) if($qs==$pageCurr['page']) $page = $pageCurr;
			KBTB::req($page!==null);
			
			$pageUrl = KBTB::attr_encode($page['page']);
			$pageTitle = KBTB::attr_encode($page['title']);
			$pageContent = KBTB::html_encode($page['content']);
			$content = <<<EOF
<h1>Edit page</h1>
<a href="#pages">Back to pages</a><br/><br/>

<form onsubmit="$('#editor').text(myEditor.saveHTML()); return formHandler(this);" class="yui-skin-sam">
<input type="hidden" name="a" value="adminPageEditChange"/>
<input type="hidden" name="pageUrl" value="$pageUrl"/>
Title: <input type="text" name="pageTitle" value="$pageTitle"/><br/><br/>
<textarea name="content" id="editor">$pageContent</textarea>
<span class="validationResponse"></span><br/>
<input type="submit" value="Submit"/>
</form>

<script type="text/javascript"> 
// Instantiate and configure YUI Loader:
$.getScript('https://ajax.googleapis.com/ajax/libs/yui/2.8.1/build/yuiloader/yuiloader-min.js', function() {
	var loader = new YAHOO.util.YUILoader({ 
		base: "https://ajax.googleapis.com/ajax/libs/yui/2.8.1/build/", 
		require: ["container","dom","editor","element","event"], 
		//require: ["animation","button","container","dom","editor","element","event","menu"], 
		loadOptional: false, 
		combine: false, 
		filter: "MIN", 
		allowRollup: true, 
		onSuccess: function() { 
			var Dom = YAHOO.util.Dom,
				Event = YAHOO.util.Event;
			
			var myConfig = {
				width: '700px',
				height: '350px',
				dompath: true,
				focusAtStart: true,
				markup: 'xhtml',
				html:	'<html><head><title>{TITLE}</title>'+
					'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>'+
					'<base href="..">'+
					'<style>{CSS}</style>'+
					'<style>{HIDDEN_CSS}</style>'+
					'<style>{EXTRA_CSS}</style>'+
					'</head>'+
					'<body onload="document.body._rteLoaded=true;">{CONTENT}</body></html>'
			};
			
			myEditor = new YAHOO.widget.Editor('editor', myConfig);
			myEditor._defaultToolbar.buttonType = 'advanced';
			myEditor.render();
		} 
	}); 
	
	// Load the files using the insert() method. 
	loader.insert(); 
});
</script>

EOF;
			break;
		case 'main':
			$content = '<h1>Main page</h1>You\'re logged in to KB CMS.<br/><br/><a href="#" onclick="return ajax({a:\'logout\'})">Log out</a>';
			break;
		case 'pages':
			$pages = $cfg['pages'];
			$content = '<h1>Pages</h1><a href="#" onclick="return pageAdd();">Add page</a><br/><br/>';
			
			$content .= '<table class="pagetable">';
			foreach($pages as $page) {
				$content .= '<tr><td><a href="#pageEdit?'.KBTB::attr_encode($page['page']).'">'.KBTB::html_encode($page['title']).'</a></td><td>'.
					($page['page']!='index'?' <a href="#" onclick=\'if(confirm("Are you sure you want to delete this page?")) ajax({a:"pageDelete",page:"'.KBTB::attr_encode($page['page']).'"}); return false;\'>Delete</a>':'&nbsp;').'</td></tr>';
			}
			$content .= '</table>';
			break;
		case 'about':
			$doc = simplexml_load_file('about.html')->xpath('/html/body/div[@id="content"]/*');
			$content = '';
			foreach($doc as $node) $content .= $node->asXML();
			break;
		case 'settings':
			$content = '<h1>Settings</h1><h2>Change password</h2>'.
				'<form class="labelsWide" onsubmit="return formHandler(this);"><input type="hidden" name="a" value="passChange"/>'.
				'<label>Old password:</label><input type="password" name="passOld"/><br/>'.
				'<label>New password:</label><input type="password" name="pass"/><br/>'.
				'<label>Repeat new password:</label><input type="password" name="pass2"/><br/><br/><input type="submit" value="Submit"/></form>';
			break;
		default:
			KBTB::req(false, 'Invalid input (page: "'.$url.'")');
			break;
	}
	return json_encode(array('page',$urlOrg,$content,'<a href=".#main">Main page</a><a href=".#pages">Pages</a><a href=".#design">Design</a><a href=".#settings">Settings</a><a href="#about">About KB CMS</a>'));
}

function pageUpdate($page,$cfg) {
	$handlees = array($page['content'],$cfg['design']);
	
	$file = '../settings/tplHandler.php';
	if(is_file($file) && is_readable($file)) require($file);
	
	for($i=0; $i<2; $i++) {
		$pageResult = array();
		while(count($split=explode('{%',$handlees[$i],2)) == 2) {
			array_push($pageResult,$split[0]);
			KBTB::req(count($split=explode('%}',$split[1],2)) == 2);
			KBTB::req(count($cmd=json_decode('['.$split[0].']',true))>0,$page['page'].', '.$split[0]);
			
			$tplResult = function_exists('tplHandler') ? tplHandler($cmd) : false ;
			
			if($tplResult!==false) {
				$pageResult[] = $tplResult;
			}else{
				switch($cmd[0]) {
					case 'content':
						KBTB::req(count($cmd)==2);
						switch($cmd[1]) {
							case 'main':
								$pageResult[] = $handlees[0];
								break;
							case 'title':
								$pageResult[] = $page['title'];
								break;
							case 'menu':
								foreach($cfg['pages'] as $mi) $pageResult[] = '<a href="'.($mi['page']=='index'?'.':$mi['page'].'.html').'"'.($mi['page']==$page['page']?' class="active"':'').'>'.KBTB::html_encode($mi['title']).'</a> ';
								break;
							default:
								KBTB::req(false,'Invalid content type.');
								break;
						}
						break;
					default:
						KBTB::req(false,'Unsupported template command.');
						break;
				}
			}
			$handlees[$i] = $split[1];
		}
		$handlees[$i] = implode($pageResult).$handlees[$i];
	}
	
	KBTB::req(file_put_contents('../'.$page['page'].'.html',$handlees[1])>0);
}

function main() {
	if(!isset($_SESSION)) session_start();
	$cfg = cfgGet();
	
	switch($_POST['a']) {
		case 'designChange':
			if(!isset($_SESSION['user'])) return json_encode(array('redirect','.'));
			
			$fieldErrs = array();
			if(!KBTB::valid('strlen',$_POST['design'],0,4000)) $fieldErrs['design'] = 'Invalid input.';
			
			// Send validation err's back
			if(count($fieldErrs)>0) die(json_encode(array('fieldErrs',$fieldErrs)));
			
			$cfg['design'] = $_POST['design'];
			
			if(!cfgSet($cfg)) echo(json_encode(array('err','Error: Couldn\'t save settings. Check if application has necessary directory permissions.')));
			else {
				foreach($cfg['pages'] as $page) pageUpdate($page,$cfg);
				echo(json_encode(array('msg','Design was updated.')));
			}
			break;
		case 'adminPageEditChange':
			if(!isset($_SESSION['user'])) return json_encode(array('redirect','.'));
			
			foreach($cfg['pages'] as $i=>$page) if($_POST['pageUrl']==$page['page']) $pageNo = $i;
			KBTB::req($pageNo!==null);
			
			$fieldErrs = array();
			if(!KBTB::valid('strlen',$_POST['pageTitle'],0,100)) $fieldErrs['pageTitle'] = 'Invalid input.';
			if(!KBTB::valid('strlen',$_POST['content'],0,4000)) $fieldErrs['content'] = 'Invalid input.';
			else {
				$content = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><content>'.$_POST['content'].'</content>');
				if($content===false) $fieldErrs['content'] = 'Invalid input (invalid XML).';
			}
			
			// Send validation err's back
			if(count($fieldErrs)>0) die(json_encode(array('fieldErrs',$fieldErrs)));
			
			$cfg['pages'][$pageNo]['title'] = $_POST['pageTitle'];
			$cfg['pages'][$pageNo]['content'] = $_POST['content'];
			
			if(!cfgSet($cfg)) echo(json_encode(array('err','Error: Couldn\'t save settings. Check if application has necessary directory permissions.')));
			else {
				pageUpdate($cfg['pages'][$pageNo],$cfg);
				echo(page('pages',$cfg));
			}
			break;
		case 'pageDelete':
			if(!isset($_SESSION['user'])) return json_encode(array('redirect','.'));
			
			KBTB::req($_POST['page']!='index');
			foreach($cfg['pages'] as $i=>$page) if($_POST['page']==$page['page']) unset($cfg['pages'][$i]);
			
			if(!cfgSet($cfg)) echo(json_encode(array('err','Error: Couldn\'t save settings. Check if application has necessary directory permissions.')));
			else echo(page('pages',$cfg));
			break;
		case 'pageAdd':
			if(!isset($_SESSION['user'])) return json_encode(array('redirect','.'));
			$fieldErrs = array();
			
			if(!KBTB::valid('strlen',$_POST['title'],0,100)) $fieldErrs['title'] = 'Invalid input.';
			else {
				$pageurl = preg_replace('/[^a-zA-Z0-9]/i','_',$_POST['title']);
				foreach($cfg['pages'] as $page) if($pageurl==$page['page']) $fieldErrs['title'] = 'Invalid input.';
			}
			
			// Send validation err's back
			if(count($fieldErrs)>0) die(json_encode(array('fieldErrs',$fieldErrs)));
			
			$cfg['pages'][] = array(
				'page' =>	$pageurl,
				'title' =>	$_POST['title'],
				'content' =>	'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
			);
			
			if(!cfgSet($cfg)) echo(json_encode(array('err','Error: Couldn\'t save settings. Check if application has necessary directory permissions.')));
			else echo(page('pages',$cfg));
			break;
		case 'passChange':
			if(!isset($_SESSION['user'])) return json_encode(array('redirect','.'));
			$fieldErrs = array();
			
			if(!KBTB::valid('strlen',$_POST['pass'],4,50)) $fieldErrs['pass'] = 'Invalid input.';
			if($_POST['pass']!=$_POST['pass2']) $fieldErrs['pass2'] = 'Password fields not matching.';
			
			$login = false;
			$oldpass = $cfg['users'][0]['pass'];
			foreach($cfg['users'] as $key=>$user) if($login===false && $user['user']==$_SESSION['user'] && crypt($_POST['passOld'],$user['pass'])==$user['pass']) $login = $key;
			if($login===false) $fieldErrs['passOld'] = 'Invalid input.';
			
			// Send validation err's back
			if(count($fieldErrs)>0) die(json_encode(array('fieldErrs',$fieldErrs)));
			
			$cfg['users'][$login]['pass'] = crypt($_POST['pass']);
			
			if(!cfgSet($cfg)) echo(json_encode(array('err','Error: Couldn\'t save settings. Check if application has necessary directory permissions.')));
			else echo(json_encode(array('callbackCustom','passChanged')));
			break;
		case 'logout':
			unset($_SESSION['user']);
			echo(json_encode(array('redirect','.')));
			break;
		case 'checklogin':
			if(isset($_SESSION['user'])) echo(page('main',$cfg));
			else echo(json_encode(array('callbackCustom','loginFocus')));
			break;
		case 'login':
			$login = false;
			foreach($cfg['users'] as $user) if(!$login && $user['user']==$_POST['user'] && crypt($_POST['pass'],$user['pass'])==$user['pass']) $login=$user['user'];
			
			if($login===false) echo(json_encode(array('fieldErrs',array('user'=>'Incorrect username and/or password.','pass'=>'Incorrect username and/or password.'))));
			else {
				$_SESSION['user'] = $user['user'];
				echo(page('main',$cfg));
			}
			break;
		case 'page':
			echo(page($_POST['p'],$cfg));
			break;
		default:
			KBTB::req(false,'Invalid input (a: "'.$_POST['a'].'").');
			break;
	}
	die();
}

function cfgSet($cfg) {
	if(!is_dir('../settings')) if(!@mkdir('../settings')) return false;
	
	KBTB::req(file_put_contents('../settings/.htaccess',"Options -Indexes\nRewriteEngine on\nRewriteRule ^.*$ - [F]")>0,'Couldn\'t save settings file. Check directory permissions.');
	KBTB::req(file_put_contents('../settings/cfg.json',json_encode($cfg))>0);
	
	return true;
}

function cfgGet() {
	$file = '../settings/cfg.json';
	if(is_file($file) && is_readable($file)) {
		return json_decode(file_get_contents($file),true);
	}else return array( // Default config
		'users' =>	array(
			array(
				'user' =>	'admin',
				'pass' =>	crypt('changeme')
			)
		),
		'pages' =>	array(
			array(
				'page' =>	'index',
				'title' =>	'Main page',
				'content' =>	'Lorem ipsum dolor sit amet, consectetur adipiscing elit.'
			)
		),
		'design' =>	'<!DOCTYPE html>'+
			'<html>'+
			'<head>'+
			'	<title>KB CMS - Suit yourself</title>'+
			'</head>'+
			'<body>'+
			'	<h1>{%"content","title"%}</h1>'+
			'	{%"content","main"%}'+
			'</body>'+
			'</html>'
	);
}


// Classes
class KBTB { // Toolbox
	function valid($types,$var,$var2=null,$var3=null) {
		$valid = true;
		foreach(explode(',',$types) as $type) {
			switch($type) {
				case 'int':
					$valid = $valid && $var!==true && (string)$var==(string)(int)$var;
					break;
				case '>':
					$valid = $valid && $var>$var2;
					break;
				case '<':
					$valid = $valid && $var<$var2;
					break;
				case '><':
					$valid = $valid && $var>$var2 && $var<$var3;
					break;
				case 'usbi': //mysql unsigned bigint
					$valid = $valid && KBTB::valid('int',$var) && $var>=0 && $var<10000000000000000000;
					break;
				case 'strlen':
					$valid = $valid && strlen($var)>$var2 && strlen($var)<$var3;
					break;
				case 'email': // valid e-mail, and less than var2 in length if set
					$valid = $valid && (is_null($var2) || strlen($var)<$var2) && (strlen($var) > 5 && preg_match('/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i', $var) !== false);
					break;
				case 'IP':
					$valid = $valid && preg_match('/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/', $var);
					break;
				case 'url':
					$regex = '/^((https?|ftp)\:\/\/)?'; // SCHEME 
					$regex .= '([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?'; // User and Pass 
					$regex .= '([a-z0-9-.]*)\.([a-z]{2,3})'; // Host or IP 
					$regex .= '(\:[0-9]{2,5})?'; // Port 
					$regex .= '(\/([a-z0-9+\$_-]\.?)+)*\/?'; // Path 
					$regex .= '(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?'; // GET Query 
					$regex .= '(#[a-z_.-][a-z0-9+\$_.-]*)?$/i'; // Anchor 
					$valid = $valid && preg_match($regex, $var);
					break;
			}
		}
		return $valid;
	}
	function req($value,$errMsg = false) {
		if(!$value) {
			while(ob_get_clean());
			throw new Exception($errMsg ? $errMsg : 'Unknown error');
		}else return $value;
	}
	function debug($value, $continue = false) {
		header('Content-type: text/plain');
		while(ob_get_level()>0) ob_end_clean();
		var_dump($value);
		if(!$continue) die();
	}
	function html_encode($var) {
		return htmlentities($var, ENT_QUOTES, 'UTF-8');
	}
	function attr_encode($var) {
		return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
	}
	function inpath($path) {
		return ereg('^'.addslashes(realpath('.').DIRECTORY_SEPARATOR),realpath($path));
	}
}



// Init
main();



?>
