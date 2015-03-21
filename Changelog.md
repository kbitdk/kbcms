# Unreleased changes #
Changes described here are in the repository and should be included in the next release
  * Changed keyboard shortcut for fullscreen in the ACE editor to be F9 instead of F10, to avoid conflict with Unity
<a href='Hidden comment: 
* No new unreleased features, yet.
'></a>

# 0.2.4 #
  * Added the ability to package site to a tgz file
  * Fixed issue with checking permissions when saving configuration

# 0.2.3 #
  * Fixed an issue where the YUI code view didn't retain any changes when submitting
  * Made the login session variable unique for the folder KB CMS is in, so you can have multiple CMS'es on one site with separate users
    * This means that an upgrade will require you to log in again after the upgrade
  * Fixed an issue where the about page wouldn't show properly while logged in
  * Fixed an issue where shortcuts for the ACE editor were in some cases applied when the editor wasn't there and in some cases multiple times

# 0.2.1 #
  * Compatible with PHP 5.2 and possibly earlier PHP versions
    * 0.2.0 used anonymous functions and the nowdoc syntax, which were introduced to PHP in version 5.3
  * Uses Yahoo's YUI 2 Rich Text Editor as a fallback if CKEditor is not found ([issue 1](https://code.google.com/p/kbcms/issues/detail?id=1))
  * KB CMS can now update itself

# 0.2 #
  * Rewrite to overcome some limitations in version 0.1.
  * Content and design is now editable directly in the admin interface, instead of using XML/XSL
  * Files can be edited with a code editor, which is based on [ACE](http://ace.ajax.org/), but with things like syntax highlighting and keyboard shortcuts set by default.
  * The fully customizable URL's and automatically generated sitemaps are removed for this version, but they might return in a later version.
  * The CKEditor WYSIWYG editor will be used if it exists in lib/ckeditor

# 0.1 #
  * First release
  * Licensed under the GPL version 2 or later
    * Use it for as many sites as you like and customize it all you want
    * Note: The logo and the name are not to be used with derivative works)
  * Content and design separation with XML and XSL files
  * [\*Fully\* customizable URL's](CustomURLs.md)
    * SEO (Search Engine Optimization) URL's are now trivial
    * Paths can be named exactly as they were on the website you're replacing
  * Automatically generates a google-compatible sitemap