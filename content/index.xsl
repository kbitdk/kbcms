<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" version="1.0" encoding="UTF-8" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" indent="no"/>
	
	<xsl:template match="/page">
		<html>
		<head>
		<title><xsl:value-of select="title"/></title>
		<link rel="shortcut icon" href="/favicon.ico" type="image/vnd.microsoft.icon" />
		<link rel="stylesheet" type="text/css" href="/index.css"/>
		<script src="http://www.google.com/jsapi"></script><script type="text/javascript"> 
			google.load('jquery', '1.3');
		</script><script type="text/javascript" src="/index.js"></script> 
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
			<div id="content">
				<b class="r1"></b><b class="r2"></b><b class="r3"></b><b class="r4"></b>
				<div id="innercontent">
					<xsl:apply-templates select="content/*|content/text()"/>
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
