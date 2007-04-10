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
	function xslParse($xsl) {
		$xslt = new xsltProcessor;
		$xslt->importStyleSheet(DOMDocument::load($xsl));
		// TODO: put the resulting xml in the object for further usage
		return $xslt->transformToXML($this->asDOM());
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
	//private $xml;
	
	function getStdCfg() {
		return new KBXML(null,"<?xml version='1.0' encoding='UTF-8'?> 
		<config><contentpath>content</contentpath></config>");
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
		$xml = new KBXML($this->dir."index.xml");
		if($xml->error) return;
		
		// TODO: define a precedence for the url's
		// TODO: validation of the xml file and the following xml and xsl file
		// TODO: make a proper rule for symbols in urls
		
		KBTB::req(ereg("^[a-zA-Z._/-]*$",$url),"Error on line ".__LINE__.": Invalid URL.");
		if($url) {
			if($url == "config") { // Admin interface
				// TODO: the content of this page should be configurable (in index.xml?)
				// TODO: consider to use something else than request_uri (the url should be configurable, in config.xml?)
				$login = "<form action='".$_SERVER['REQUEST_URI']."'><table>
				<tr><td>Username: </td><td><input type='text'/></td></tr>
				<tr><td>Password: </td><td><input type='text'/></td></tr>
				<tr><td><input type='submit' value='Login'/></td><td></td></tr>
				</table></form>
				";
				$xml->set("/page/content",$login);
			} elseif($url == "sitemap.xml") { // Sitemap
				$sitemap = "<?xml version='1.0' encoding='UTF-8'?>";
				$sitemap .= "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";
				$sitemap .= $this->getSitemap($xml);
				$sitemap .= "</urlset>";
				
				$this->contents = $sitemap;
				$this->contenttype = "text/html";
				$this->type = "page";
				return;
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
		$this->contents = $xml->xslParse($this->dir.'index.xsl');
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