<?

// Main
website();

// Classes
class KBXML {
	protected $xml;
	public $error;
	
	protected function nodeasXML($node) {
		// returns a node as an xml string (returns xml fragment, not valid xml)
		foreach($node->childNodes as $child){
			switch($child->nodeType) {
			case 1: // elementnode
				$attrs = "";
				foreach($child->attributes as $attr) {
					$attrs .= " ".$attr->name."='".$attr->value."'";
				}
				$result .= "<".$child->nodeName."$attrs";
				$value = $child->hasChildNodes()?$this->nodeasXML($child):$child->NodeValue;
				$result .= $value=="" ? "/>" : ">".$value."</".$child->nodeName.">";
				break;
			case 3: // textnode
				$result .= $child->nodeValue;
				break;
			case 4: // cdata
				$result .= $child->nodeValue;
				break;
			}
		}
		return $result;
	}
	
	protected function replacenode($old, $new) {
		// replaces a node with a new one
		
		$old->nodeValue = "";
		
		foreach($new->childNodes as $child) {
			
			switch($child->nodeType) {
			case 1: // elementnode
				$altered = $old->appendchild(new DOMElement($child->nodeName,""));
				foreach($child->attributes as $attr) {
					$altered->appendchild($this->xml->createattribute($attr->name));
					$altered->setattribute($attr->name,$attr->value);
				}
				if($child->hasChildNodes()) {
					foreach($child->childNodes as $subchild) {
						$this->replacenode($altered,$child);
					}
				}
				
				break;
			case 3: // textnode
				$old->appendchild(new DOMText($child->nodeValue));
				break;
			}
		}
	}
	
	// Load an xml file
	function __construct($file,$xml=null) {
		if(!is_null($xml)) {
			// TODO: check validity, perhaps against a DTD
			$this->xml = DomDocument::loadXML($xml);
		} elseif(is_file($file) && is_readable($file)) {
			// TODO: check validity, perhaps against a DTD
			$this->xml = DomDocument::load($file);
		}else{
			$this->error = "File not found";
		}
	}
	
	function get($xpath) {
		// returns xml data as a string from xpath (doesn't include the root of the xpath result)
		// returns false for invalid query
		$xp = new DOMXPath($this->xml);
		if(!$node = $xp->query($xpath)->item(0)) return false;
		return $this->nodeasXML($node); // TODO: check if it might be better to transform it to regular xml like in this comment: http://dk2.php.net/manual/en/function.dom-domxpath-query.php#58729
	}
	function getArr($xpath) {
		// returns xml data as an array of strings from xpath
		// returns false for invalid query
		$xp = new DOMXPath($this->xml);
		if(!$nodes = $xp->query($xpath)) return false;
		foreach($nodes as $node) $output[] = $this->nodeasXML($node);
		return $output; // TODO: check if it might be better to transform it to regular xml like in this comment: http://dk2.php.net/manual/en/function.dom-domxpath-query.php#58729
	}
	
	function replace($xpath,$value) {
		$xp = new DOMXPath($this->xml);
		$nodes = $xp->query($xpath);
		$node = $nodes->item(0)->parentNode;
		$node->removeChild;
		
		$xp = new DOMXPath(DOMDocument::loadXML("<x>".$value."</x>"));
		$pn = $xp->query("/x");
		
		foreach($pn->item(0)->childNodes as $child) {
			switch($child->nodeType) {
			case 1:
				$newnode = new DOMElement($child->nodeName,$child->nodeValue);
				$altered = $node->appendchild($newnode);
				foreach($child->attributes as $attr) {
					$altered->appendchild($this->xml->createattribute($attr->name));
					$altered->setattribute($attr->name,$attr->value);
				}
				break;
			case 3:
				$node->appendchild(new DOMText($child->nodeValue));
				break;
			}
		}
	}
	
	function set($xpath,$value) {
		// sets a node found via an xpath as an input xml string
		
		$xp = new DOMXPath($this->xml);
		$nodes = $xp->query($xpath)->item(0);
		
		$xp = new DOMXPath(DOMDocument::loadXML("<x>".$value."</x>"));
		$pn = $xp->query("/x")->item(0);
		
		$this->replacenode($nodes,$pn);
	}
	
	function asXML() {
		return $this->xml->saveXML();
	}
	
	function asDOM() {
		return $this->xml;
	}
	
	function setDOM($dom) {
		$this->xml = $dom;
	}
	
	// Parse the XML with the XSL argument and put it back in the XML
	function xslParse($xsl) {
		$xslt = new xsltProcessor;
		$xslt->importStyleSheet(DOMDocument::load($xsl));
		$this->setDOM($xslt->transformToDoc($this->asDOM()));
		return $this->asXML();
	}
}

class KBTB { // Toolbox
	function debug($string) {
		header("Content-type: text/plain");
		var_dump($string);
		die();
	}
	function inpath($path) {
		return ereg("^".realpath(".")."/",realpath($path));
	}
	function req($value,$errMsg = false) {
		if(!$value) {
			// TODO: find a correct http error code
			//header("HTTP/1.0 404 Not Found");
			die($errMsg ? $errMsg : "Unknown error");
		}else return $value;
	}
}

class KBContent {
	public $type = "notfound"; // Assume the address is not found unless proven otherwise
	public $contents = "";
	public $contenttype = "";
	private $cfg;
	private $dir;
	
	function getStdCfg() { // Standard configuration
		return new KBXML(null,"<?xml version='1.0' encoding='UTF-8'?> 
		<config>
			<contentpath>content</contentpath>
			<adminpath>admin</adminpath>
			<rootpass>".crypt("changeme","$2")."</rootpass>
		</config>");
	}
	
	function getSitemap($xml,$url=null,$xpath="/page/page") {
		$xpath .= is_null($url) ? "" : "/page/title[.='".substr($url,strrpos($url,"/")+1)."']/..";
		$lastmod = $xml->get($xpath."/lastmod");
		
		// Output the record for the page
		// TODO: Should interpret characters (such as spaces, symbols, etc.)
		$output .= "\n\n<url><loc>"."http://". $_SERVER['SERVER_NAME'].$url."</loc>";
		if($lastmod) $output .= "\n<lastmod>".$lastmod."</lastmod>";
		$output .= "</url>";
		
		// Recursive
		if($xml->get($xpath."/page")) foreach($xml->getArr($xpath."/page/title") as $page)
			$output .= $this->getSitemap($xml,$url."/".$page,$xpath);
		
		return $output;
	}
	
	function __construct($url) {
		// Input parsing
		$this->cfg = new KBXML("config.xml");
		if($this->cfg->error == "File not found") $this->cfg = $this->getStdCfg();
		$this->dir = $this->cfg->get("/config/contentpath")."/";
		// TODO: validate the config file
		$xml = new KBXML($this->dir."index.xml");
		if($xml->error) return;
		
		// TODO: validation of the xml file and the following xml and xsl file
		// TODO: make a proper rule for symbols in urls
		
		KBTB::req(ereg("^[a-zA-Z._/-]*$",$url),"Error on line ".__LINE__.": Invalid URL.");
		if($url) {
			if($url == "sitemap.xml") { // Sitemap
				$sitemap = "<?xml version='1.0' encoding='UTF-8'?>";
				$sitemap .= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";
				$sitemap .= $this->getSitemap($xml);
				$sitemap .= "</urlset>";
				
				$this->contents = $sitemap;
				$this->contenttype = "text/html";
				$this->type = "page";
				return;
			} elseif($url == "favicon.ico") {
				// TODO: check if favicon.ico exists
				KBTB::req(KBTB::inpath($this->dir."favicon.ico"),"Error on line ".__LINE__.": Invalid path.");
				$this->contents = file_get_contents($this->dir."favicon.ico");
				$this->contenttype = "image/x-icon";
				$this->type = "media";
				return;
			} elseif($url == $this->cfg->get("/config/adminpath")) { // Admin interface
				// Check for valid login
				if(!is_null($_POST['user'])) {
					//TODO: create support for users other than root
					if($_POST['user'] == "root" && crypt($_POST['pass'],"$2") == $this->cfg->get("/config/rootpass")) {
						$_SESSION['user'] = $_POST['user'];
						//TODO: redirect to a different page?
						header("Location: http".(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on"?"s":"")."://".$_SERVER['SERVER_NAME']);
					} else {
						$logonErr = "Error: Wrong username and/or password";
					}
				}
				
				// Login page
				// TODO: the content of this page should be configurable (in index.xml, index.xsl or config.xml?)
				// TODO: make sure the pages can't be (re)named the same as a "special URL"
				// TODO: make the username field gain focus on load of the page
				$login = "<h1>Administration</h1>
				".$logonErr."
				<form action='/".$url."' method='post'><table>
				<tr><td>Username: </td><td><input type='text' name='user'/></td></tr>
				<tr><td>Password: </td><td><input type='password' name='pass'/></td></tr>
				<tr><td><input type='submit' value='Login'/></td><td></td></tr>
				</table></form>
				";
				$xml->set("/page/content",$login);
			// Check if $url is a content file or a media file
			} elseif(is_file($file = $this->dir.$xml->get("/page/page/page/title[.='".implode("']/../page/title[.='",explode("/",$url))."']/../loc")) && is_readable($file)) {
				// Requirements
				KBTB::req(KBTB::inpath($file),"Error on line ".__LINE__.": Invalid path.");
				// Get the real contents and put it in the right place
				$xmlpage = new KBXML($file);
				KBTB::req(!$xmlpage->error,"Error on line ".__LINE__.": Invalid XML input.");
				$xml->set("/page/content",$xmlpage->get("/page/content"));
			} elseif(is_file($file = $this->dir.$xml->get("/page/medias/media/title[.='".$url."']/../loc")) && is_readable($file)) {
				// Requirements
				KBTB::req(KBTB::inpath($file),"Error on line ".__LINE__.": Invalid path.");
				// Spit out the media
				// TODO: check for image type (png, jpg, gif, etc.), or maybe the system should require png, is that too locked in, if it converts the format in the admin back-end?
				// TODO: check for image validity
				$this->contents = file_get_contents($file);
				$this->contenttype = "image/png";
				$this->type = "media";
				return;
			} else { // 404
				return;
			}
		}
		
		// Parse the xml file with the xsl stylesheet
		// TODO: check for existance of index.xsl
		$xml->xslParse($this->dir.'index.xsl');
		if(isset($_SESSION['user'])) {
			// TODO: implement the logoff and the rename functions
			// TODO: make a seperate place for javascript functions
			// TODO: make a single-click or perhaps double-click on the content turn it into a WYSIWYG editor
			$adminpanel = "
			<script type='text/javascript'>
			var editable;
			function unsupported(msg) {
				alert('Function not supported yet!\\n\\n'+msg);
			}
			function wysiwyg() {
				if(!editable) {
					document.getElementById('innercontent').innerHTML = '<textarea id='editor' name='content' cols='100' rows='30'>'+document.getElementById('innercontent').innerHTML+'</textarea>';
					tinyMCE.execCommand('mceAddControl', false, 'editor');
					editable = true;
				}
			}
			window.onload = function() {
				document.getElementById('innercontent').onclick = function() {
					wysiwyg();
				};
			};
			</script>
			<div id='adminpanel'>
			<h1>Admin panel</h1>
			<script type='text/javascript' src='tiny_mce/tiny_mce_gzip.js'></script>
			<script type='text/javascript'>
			tinyMCE_GZ.init({
				plugins : 'style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras',
				themes : 'simple,advanced',
				languages : 'en',
				disk_cache : true,
				debug : false
			});
			</script>
			<!-- Needs to be seperate script tags! -->
			<script language='javascript' type='text/javascript'>
			tinyMCE.init({
				mode : 'textareas'
			});
			</script>
					
			<a href=\"javascript:unsupported('The page can be renamed from the content/index.xml file.');\">Rename page</a><br/>
			<a href=\"javascript:unsupported('Logging out can be done by closing the browser to clear the session.');\">Log out</a>
			</div>";
			$xml->set("/html/body",$adminpanel.$xml->get("/html/body"));
		}
		$this->contents = $xml->asXML();
		$this->contenttype = "text/html";
		$this->type = "page";
	}
}

// Functions
function err404() {
	// Shows a 404 page and dies
	header("HTTP/1.0 404 Not Found");
	die("<html><head><title>404 Not Found</title></head>
<body bgcolor=white>
<h1>404 Not Found</h1>

The requested URL ".getenv("REQUEST_URI")." does not exist.

</body></html>"); //TODO: redirect to the real 404 page
}

function website() {
	$content = new KBContent($_GET["url"]);
	switch($content->type) {
	case "page":
	case "media":
		header("Content-type: ".$content->contenttype);
		echo $content->contents;
		break;
	case "notfound":
		err404();
		break;
	}
}


?>
