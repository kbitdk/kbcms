<?
/*
KB CMS
Licensed under GPLv2 or later.
*/

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
			
			if(file_exists('../lib/ckeditor/ckeditor.js')) {
				$basepath = json_encode('http'.(isset($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'/../../lib/ckeditor/');
				
				$editor = '<textarea name="editor" rows="8" cols="60">'.$pageContent.'</textarea>';
				
				if(file_exists('../lib/kcfinder/config.php')) {
					$_SESSION['KCFINDER'] = array(
						'disabled'	=> false,
						'uploadURL'	=> '../../upload'
					);
					$editor .= <<<EOF
<script type="text/javascript">
window.CKEditorConfig = {
	filebrowserBrowseUrl:	'../lib/kcfinder/browse.php?type=files',
	filebrowserImageBrowseUrl:	'../lib/kcfinder/browse.php?type=images',
	filebrowserFlashBrowseUrl:	'../lib/kcfinder/browse.php?type=flash',
	filebrowserUploadUrl:	'../lib/kcfinder/upload.php?type=files',
	filebrowserImageUploadUrl:	'../lib/kcfinder/upload.php?type=images',
	filebrowserFlashUploadUrl:	'../lib/kcfinder/upload.php?type=flash'
};
</script>
EOF;
				}
				
				$editor .= '<script type="text/javascript">window.CKEDITOR_BASEPATH='.$basepath.';</script>'.<<<EOF
<script type="text/javascript">//<![CDATA[
$.getScript('../lib/ckeditor/ckeditor.js', function() {
	var CKEditorConfig = window.CKEditorConfig||{};
	CKEDITOR.replace('editor',$.extend(CKEditorConfig,{
		toolbarCanCollapse:	false,
		toolbar:	'KBCMS',
		toolbar_KBCMS:	[
				['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink'],
				['NumberedList','BulletedList','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','RemoveFormat','Image','-','Source'],
				['Styles','Format','Font','FontSize']
		]
	}));
});
//]]></script>
EOF;
			}else{
				$editor = '<textarea name="editor" id="editor" style="width:730px; height:300px;">'.$pageContent.'</textarea>';
				/*$editor .= <<<EOF
<script type="text/javascript">
// Instantiate and configure YUI Loader:
$('#editor').parents('form').addClass('yui-skin-sam');
$.getScript('https://ajax.googleapis.com/ajax/libs/yui/2.8/build/yuiloader/yuiloader-min.js', function() {
	    var loader = new YAHOO.util.YUILoader({ 
        base: "https://ajax.googleapis.com/ajax/libs/yui/2.8/build/", 
        require: ["button","containercore","dom","element","event","menu","simpleeditor"], 
        loadOptional: false, 
        combine: false, 
        filter: "MIN", 
        allowRollup: true, 
        onSuccess: function() { 
            //Setup some private variables
    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event;

        //The SimpleEditor config
        var myConfig = {
            height: '300px',
            width: '600px',
            dompath: true,
            focusAtStart: true
        };

    //Now let's load the SimpleEditor..
    var myEditor = new YAHOO.widget.SimpleEditor('editor', myConfig);
    myEditor.render();
        } 
    }); 
 
// Load the files using the insert() method. 
loader.insert();
}); //<form onsubmit="$('#editor').text(myEditor.saveHTML()); return formHandler(this);" class="yui-skin-sam">
</script>
EOF;*/
			}
			
			$content = <<<EOF
<h1>Edit page</h1>
<a href="#pages">Back to pages</a><br/><br/>

<form onsubmit="return formHandler(this);">
<input type="hidden" name="a" value="adminPageEditChange"/>
<input type="hidden" name="pageUrl" value="$pageUrl"/>
Title: <input type="text" name="pageTitle" value="$pageTitle"/><br/><br/>
$editor
<span class="validationResponse"></span><br/>
<input type="submit" value="Submit"/>
</form>

EOF;
			break;
		case 'main':
			$content = '<h1>Main page</h1>You\'re logged in to KB CMS.<br/><br/><a href="#" onclick="return ajax({a:\'logout\'})">Log out</a>';
			break;
		case 'pages':
			$pages = $cfg['pages'];
			$content = '<h1>Pages</h1><a href="#" onclick="return pageAdd();">Add page</a><br/><br/>';
			
			$content .= '<table class="pagetable">';
			for($i=0, $size=count($pages); $i<$size && $page=$pages[$i]; $i++) {
				$content .= '<tr><td style="padding-right:5px;"><a href="#pageEdit?'.KBTB::attr_encode($page['page']).'">'.KBTB::html_encode($page['title']).'</a></td><td> '.
					'<a href="#" onclick="return unsupported();" class="arrow'.($i>0 && $i<$size-1?'':' hidden').'">▼</a> '.
					'<a href="#" onclick="return unsupported();" class="arrow'.($i>1?'':' hidden').'">▲</a>'.
					($page['page']!='index'?' <a href="#" class="arrow" onclick=\'if(confirm("Are you sure you want to delete this page?")) ajax({a:"pageDelete",page:"'.KBTB::attr_encode($page['page']).'"}); return false;\'>X</a>':'&nbsp;').'</td></tr>';
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
		case 'modules':
			$content = '<h1>Modules</h1>';
			
			if(!is_dir('../settings') || !($files = scandir('../settings')) || count($files = array_filter($files,function($val) { return is_file('../settings/'.$val) && preg_match('/^module_([a-zA-Z0-9]+)\.php$/', $val); }))==0)
				$content .= 'There are currently no modules installed.';
			else while($entry = $files) {
				KBTB::req(preg_match('/^module_([a-zA-Z0-9]+)\.php$/', $entry, $entryRegex));
				require_once('../settings/'.$entry);
				$modname = $entryRegex[1];
				$content .= '<a href=".#modules_settings?'.$modname.'">'.KBTB::html_encode(constant('module\\'.$modname.'\\name')).'<br/>';
			}
			break;
		case 'modules_settings':
			KBTB::req(preg_match('/^[a-zA-Z0-9]+$/',$qs));
			
			require_once('../settings/module_'.$qs.'.php');
			
			$content = '<h1>Module settings ('.KBTB::html_encode(constant('module\\'.$qs.'\\name')).')</h1>'.constant('module\\'.$qs.'\\settings');
			break;
		case 'files':
			if(!is_dir('../settings/files') || !($files = scandir('../settings/files')) || count($files = array_filter($files,function($val) { return is_file('../settings/files/'.$val); }))==0)
				$filelist = 'There are currently no files uploaded.';
			else $filelist =
				'<table class="pagetable">'.
				implode('',array_map(function($val) { return '<tr><td><a href="#fileEdit?'.$val.'">'.$val.'</td>'.
					'<td><a href="#" onclick=\'if(confirm("Are you sure you want to delete this file?")) ajax({a:"fileDelete",file:"'.KBTB::attr_encode($val).'"}); return false;\'>X</a></td></tr>'; },$files)).
				'</table>';
			
			$content =
				'<h1>Files</h1>'.
				'<a href="#" onclick="return fileUpload();">Upload file</a><br/>'.
				'<a href="#" onclick="return fileEditNew();">Edit new file</a><br/><br/>'.
				$filelist
			;
			break;
		case 'fileEdit':
			$content = '<h1>Edit file</h1><a href="#files">Back to files</a><br/><br/>';
			
			$filename = $qs;
			KBTB::req(KBTB::valid('regex',$filename,'/^[a-z0-9][a-z0-9_.]{0,98}$/i') && is_file('../settings/files/'.$filename));
			
			KBTB::req($file = file_get_contents('../settings/files/'.$filename));
			
			if(preg_match('/[\x00-\x08\x0E-\x1F\x7F]/',$file)) $content .= 'Editing binary files is not supported, yet.';
			else {
				$file = KBTB::html_encode($file);
				
				$content .= <<<EOF
<form onsubmit="return formHandler(this);">
<input type="hidden" name="a" value="adminFileEditChange"/>
<input type="hidden" name="filename" value="$filename"/>
<div style="height:350px;"><div id="editorLoading">Loading text editor...</div><div style="width:730px; height:350px; overflow:hidden; visibility:hidden;" id="editor">$file</div></div>
<span class="validationResponse"></span><br/>
<input type="submit" value="Submit"/>
</form>
<script>
$.getScript('http://ajaxorg.github.com/ace/build/textarea/src/ace.js', function() {
	var ace = window.__ace_shadowed__;
	var editor = ace.edit("editor");
	$.getScript('http://ajaxorg.github.com/ace/build/textarea/src/theme-clouds_midnight.js', function() {
		editor.setTheme("ace/theme/clouds_midnight");
		editor.getSession().setTabSize(3);
		editor.getSession().setUseSoftTabs(false);
		$('#editorLoading').hide();
		$('#editor').css('visibility','visible');
		
		var ext = new RegExp('\\.([a-zA-Z0-9]+)$').exec($('input[name=filename]').val());
		if((ext instanceof Array) && ext.length>1) ext = ext[1];
		var mode = {
			php:	'php',
			css:	'css',
			xml:	'xml',
			js:	'javascript'
		}[ext];
		
		if(mode!==undefined) {
			$.getScript('http://ajaxorg.github.com/ace/build/textarea/src/mode-'+mode+'.js', function() {
				var modeNew = ace.require("ace/mode/"+mode).Mode;
				editor.getSession().setMode(new modeNew());
			});
		}
	});
});
</script>
<br style="clear:both;"/>
asdasd
EOF;
/*<script>
(function inject() {
	var baseUrl="http://ajaxorg.github.com/ace/build/textarea/src/";
	function load(path, module, callback) {
		path = baseUrl + path;
		if (!load.scripts[path]) {
			load.scripts[path] = {
				loaded: false,
				callbacks: [ callback ]
			};
			var head = document.getElementsByTagName('head')[0];
			var s = document.createElement('script');
			function c() {
				if (window.__ace_shadowed__ && window.__ace_shadowed__.define.modules[module]) {
					load.scripts[path].loaded = true;
					load.scripts[path].callbacks.forEach(function(callback) { callback(); });
				} else {
					setTimeout(c, 50);
				}
			};
			s.src = path;
			head.appendChild(s);
			c();
		} else if (load.scripts[path].loaded) { callback(); } else { load.scripts[path].callbacks.push(callback); }
	};
	load.scripts = {};
	window.__ace_shadowed_load__ = load;
	load('ace.js', 'text!ace/css/editor.css', function() {
		var ace = window.__ace_shadowed__;
		var Event = ace.require('pilot/event');
		ace.transformTextarea($('#editor').get(0));
	});
})()
</script>
*/
			}
			break;
		default:
			KBTB::req(false, 'Invalid input (page: "'.$url.'")');
			break;
	}
	return json_encode(array('page', $urlOrg, $content,
		'<a href=".#main">Main page</a><a href=".#pages">Pages</a><a href=".#modules">Modules</a><a href=".#design">Design</a><a href=".#files">Files</a><a href=".#settings">Settings</a><a href="#about">About KB CMS</a>'
	));
}

function pageUpdate($page,$cfg) {
	$handlees = array($page['content'],$cfg['design']);
	
	$file = '../settings/tplHandler.php';
	if(is_file($file) && is_readable($file)) require_once($file);
	
	for($i=0; $i<2; $i++) {
		$pageResult = array();
		while(count($split=explode('{%',$handlees[$i],2)) == 2) {
			array_push($pageResult,$split[0]);
			KBTB::req(count($split=explode('%}',$split[1],2)) == 2);
			KBTB::req(count($cmd=json_decode('['.$split[0].']',true))>0,$split[0]);
			
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
			if(!KBTB::valid('strlen',$_POST['editor'],0,4000)) $fieldErrs['editor'] = 'Invalid input.';
			/*else {
				$doc = new DOMDocument();
				$doc->loadHTML('<html><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/><body>'.$_POST['editor'].'</body></html>');
			}*/
			
			// Send validation err's back
			if(count($fieldErrs)>0) die(json_encode(array('fieldErrs',$fieldErrs)));
			
			/*$xp = new DOMXPath($doc);
			$contentList = $xp->query('/html/body/node()');
			$contentCode = array();
			foreach($contentList as $item) $contentCode[] = $doc->saveXML($item);*/
			
			$cfg['pages'][$pageNo]['title'] = $_POST['pageTitle'];
			$cfg['pages'][$pageNo]['content'] = $_POST['editor'];
			
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
				'content' =>	'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>'
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
			unset($_SESSION['KCFINDER']);
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
		case 'fileEditNew':
			$fieldErrs = array();
			
			if(!KBTB::valid('strlen',$_POST['filename'],0,100)) $fieldErrs['filename'] = 'Invalid input.';
			elseif(substr($_POST['filename'],-5)=='.html') $fieldErrs['filename'] = 'HTML files are not allowed through this interface. Use the pages section.';
			elseif(!KBTB::valid('regex',$_POST['filename'],'/^[a-z0-9][a-z0-9.]*$/i')) $fieldErrs['filename'] = 'Invalid input.';
			
			// Send validation err's back
			if(count($fieldErrs)>0) die(json_encode(array('fieldErrs',$fieldErrs)));
			
			echo(json_encode(array('unsupported')));
			break;
		case 'fileUpload':
			$filename = $_FILES['file']['name'];
			
			if(!KBTB::valid('strlen',$filename,0,100)) echo(json_encode(array('err','Invalid filename.')));
			elseif(substr($filename,-5)=='.html') echo(json_encode(array('err','HTML files are not allowed through this interface. Use the pages section.')));
			elseif(!KBTB::valid('regex',$filename,'/^[a-z0-9][a-z0-9_.]{0,98}$/i')) echo(json_encode(array('err','Invalid filename.')));
			elseif(file_exists('../settings/files/'.$filename) || file_exists('../'.$filename)) echo(json_encode(array('err','File already exists.')));
			else {
				
				if(!is_dir('../settings/files')) KBTB::req(@mkdir('../settings/files', 0777, true));
				
				KBTB::req(move_uploaded_file($_FILES['file']['tmp_name'], $settingspath = '../settings/files/'.$filename));
				copy($settingspath,'../'.$filename);
				
				echo(json_encode(array('reload')));
			}
			break;
		case 'fileDelete':
			$filename = $_POST['file'];
			KBTB::req(KBTB::valid('regex',$filename,'/^[a-z0-9][a-z0-9_.]{0,98}$/i') && is_file('../settings/files/'.$filename));
			
			KBTB::req(unlink('../settings/files/'.$filename));
			KBTB::req(unlink('../'.$filename));
			
			echo(json_encode(array('reload')));
			break;
		default:
			if(preg_match('/^module_([a-zA-Z0-9]+)_([a-zA-Z0-9]+)$/',$_POST['a'],$matches)) {
				require_once('../settings/module_'.$matches[1].'.php');
				echo(call_user_func('module\\'.$matches[1].'\\ajax_'.$matches[2],$_POST));
			} else KBTB::req(false,'Invalid input (a: "'.$_POST['a'].'").');
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
	}else {
		$design = <<<EOF
<!DOCTYPE html>
<html>
<head>
	<title>KB CMS - Suit yourself</title>
</head>
<body>
	<h1>{%"content","title"%}</h1>
	{%"content","main"%}
</body>
</html>
EOF;
		return array( // Default config
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
					'content' =>	'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>'
				)
			),
			'design' =>	$design
		);
	}
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
					$valid = $valid && KBTB::valid('regex',$var,$regex);
					break;
				case 'regex':
					$valid = $valid && preg_match($var2, $var);
					break;
				default:
					KBTB::req(false,'Invalid internal input (type).');
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
