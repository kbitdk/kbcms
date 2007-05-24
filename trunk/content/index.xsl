<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" version="1.0" encoding="UTF-8" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" indent="no"/>
	
	<xsl:template match="/page">
		<html>
		<head>
		<title><xsl:value-of select="title"/></title>
		<link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon" />
		<style type="text/css">
			html {
				background:			#fff;
			}
			#adminpanel {
				position:			absolute;
				top:					10px;
				left:					600px;
				z-index:          10;
				background:       #c0c0c0;
				padding:          10px;
			}
			#logo {
				position:			absolute;
				top:					30px;
				left:					20px;
			}
			#motto {
				position:			relative;
				top:					-15px;
				left:					5px;
			}
			#menu {
				position:			absolute;
				top:					120px;
				left:					20px;
				min-width:			140px;
			}
			b.r1, b.r2, b.r3, b.r4 {
				display:				block;
				height:				1px;
				overflow:			hidden;
				background:			#b3c9ff;
			}
			b.r1{margin: 0 5px}
			b.r2{margin: 0 3px}
			b.r3{margin: 0 2px}
			b.r4{margin: 0 1px; height: 2px}
			#menu a {
				display:				block;
				padding:				5px 10px;
				background:			#b3c9ff;
				color:				#000;
				text-decoration:	none;
			}
			#menu a:hover {
				background:			#c3d9ff;
			}
			#content {
				position:			absolute;
				top:					120px;
				left:					180px;
				right:				100px;
				margin-right:		100px;
				margin-bottom:		10px;
				padding-bottom:	20px;
			}
			#content b.r1, #content b.r2, #content b.r3, #content b.r4 {
				background:			#c3d9ff;
			}
			#innercontent {
				background:			#c3d9ff;
				padding:				5px 15px 10px 15px;
				min-width:			400px;
				min-height:			300px;
			}
			h1 {
				margin-top:			0px;
			}
			ul {
				padding:				0px;
				margin:				8px;
			}
			li {
				margin-left:		15px;
			}
			a {
				color:				#888;
			}
			a:hover {
				color:				#fff;
			}
		</style>
		</head>
		<body>
			<div id="logo">
				<img src="Logo" alt="{title}"/>
				<span id="motto"><xsl:value-of select="motto"/></span>
				</div>
			<div id="menu">
				<b class="r1"></b><b class="r2"></b><b class="r3"></b><b class="r4"></b>
				<a href="/{page/loc}"><xsl:value-of select="page/title"/></a>
				<xsl:for-each select="page/page">
					<a href="/{title}"><xsl:value-of select="title"/></a>
				</xsl:for-each>
				<b class="r4"></b><b class="r3"></b><b class="r2"></b><b class="r1"></b>
			</div>
			<div id="content" onclick='unsupported("The content can be edited through the (by default) content/*.xml files.");'>
				<b class="r1"></b><b class="r2"></b><b class="r3"></b><b class="r4"></b>
				<div id="innercontent">
					<xsl:apply-templates select="content"/>
				</div>
				<b class="r4"></b><b class="r3"></b><b class="r2"></b><b class="r1"></b>
			</div>
		</body>
		</html>
	</xsl:template>
	
	<!--
		Show XML verbatim
		- Originally from the GPL'd Text Encoding Initiative Consortium project at http://tei.sf.net/
	-->
	<xsl:param name="indentchar" select="'&#160;&#160;&#160;'"/>
	<xsl:template match="text()" mode="verbatim">
		<xsl:param name="indent" select="''"/>
		<xsl:value-of select="normalize-space(.)"/>
	</xsl:template>
	<xsl:template match="*" mode="verbatim">
		<xsl:param name="indent" select="''"/>
		<xsl:value-of select="$indent"/>
		<xsl:text>&lt;</xsl:text>
		<xsl:if test="namespace-uri(.)='http://www.w3.org/1999/XSL/Transform'">xsl:</xsl:if>
		<xsl:value-of select="local-name()"/>
		<xsl:for-each select="@*">
			<xsl:text>&#10; </xsl:text>
			<xsl:value-of select="normalize-space(name(.))"/>
			<xsl:text>="</xsl:text>
			<xsl:value-of select="."/>
			<xsl:text>"</xsl:text>
		</xsl:for-each>
		<xsl:if test="not(*|text())">/</xsl:if>
		<xsl:text>&gt;</xsl:text>
		<xsl:if test="*"><xsl:text>&#10;</xsl:text></xsl:if>
		
		<xsl:if test="*|text()">
			<xsl:apply-templates select="*|text()" mode="verbatim">
				<xsl:with-param name="indent" select="concat($indentchar, $indent)"/>
			</xsl:apply-templates>
			<xsl:if test="*"><xsl:value-of select="$indent"/></xsl:if>
			<xsl:text>&lt;/</xsl:text>
			<xsl:if test="namespace-uri(.)='http://www.w3.org/1999/XSL/Transform'">xsl:</xsl:if>
			<xsl:value-of select="local-name(.)"/>
			<xsl:text>&gt;&#10;</xsl:text>
		</xsl:if>
	</xsl:template>
	
	<xsl:template match="content | @* | node()">
		<xsl:copy>
			<xsl:apply-templates select="@* | node()"/>
		</xsl:copy>
	</xsl:template>
	
	<xsl:template match="verbatim">
		<pre>
			<xsl:apply-templates select="@* | node()" mode="verbatim"/>
		</pre>
	</xsl:template>
	
	<xsl:template match="content/KBEmployeeList">
		<xsl:apply-templates/>
	</xsl:template>
	<xsl:template match="content/KBEmployeeList/employee">
		<div style="background:#bbb; width:200px; padding:3px; border: 1px solid #000;"><xsl:value-of select="name"/></div>
		<table>
			<tr><td>Department:</td><td><xsl:value-of select="department"/></td></tr>
			<tr><td>Extension:</td><td><xsl:value-of select="extension"/></td></tr>
		</table>
		<br/>
	</xsl:template>
	
</xsl:stylesheet>
