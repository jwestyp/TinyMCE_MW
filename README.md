TinyMCE_MW
==========

TinyMCE Editor for use with MediaWiki

This is a repository created to continue the work started by Joseph P. Socoloski III in 2007 on the TinyMCE for
MediaWiki on the Extension:TinyMCE_MW page at http://www.mediawiki.org/wiki/Extension:TinyMCE_MW

I am going to do my best to keep it up to date.

The current version of TinyMCE in this repository is 3.3.9.
I currently have it operational on MediaWiki 1.20 master branch dated June 19, 2012. 

I have tested it in Firefox 13.0.1, Chrome 19.0.1084.56, and Safari 5.1.7.

You will need to add the following lines into your LocalSettings.php file:
	require_once("$IP/extensions/TinyMCE_MW/TinyMCE_MW.php");
	$wgUseTinymce = true;#Init needed for clicking on a new article link
	$wgDefaultUserOptions ['showtoolbar'] = 0;  #new users get this default or modify DefaultSetting.php
	$wgTinymceTheme = "msword";                 #"simple", "advanced", "msword", else none
	$wgMyWikiURL = "$server/$wgScriptPath/";
	$wgExt_valid_elements = "data[table|template],repeat[table|sort],categorytree[mode|depth],inputbox[type|bgcolor|width|default|preload|editintro|buttonlabel|searchbuttonlabel|break],big,math,syntax,pre";