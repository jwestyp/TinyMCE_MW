<?php
/*
TinyMCE_MW.php - MediaWiki extension - version 0.5.4
        by Joseph P. Socoloski III
        If you already have pages written in Mediawiki wikitext, this extension enables
        Moxiecode's TinyMCE and does not break Mediawiki wikitext. Also, TinyMCE_MW 
        has a new 'msword' configuration theme.  msword follows the MS Office 2003
        toolbar layout. Call TinyMCE's simple, advanced and a built-in msword theme from 
        LocalSettings.php.  TinyMCE_MW was built and tested on Mediawiki-1.10.0.  
        Successfully tested CategoryTree extension for compatibility with new tags.
                -Improved custom tag parsing for repeat and categorytree; added sql2wiki
 
        References:     http://meta.wikimedia.org/wiki/Image:Wiki-refcard.png
                                http://meta.wikimedia.org/wiki/Cheatsheet
                                http://meta.wikimedia.org/wiki/Help:HTML_in_wikitext
                                http://wiki.moxiecode.com/index.php/TinyMCE:Index
                                http://www.mediawiki.org/wiki/Extension:CategoryTree
                                http://www.mediawiki.org/wiki/Manual:Parameters_to_index.php
 
        NOTE:To change the default font and size for TinyMCE, add these two lines to your
        theme's editor_content.css body{} section:
        font-family: Arial;
        font-size: 14px;
        NOTE:To decrease the space between lines after a carriage return place this line 
        to your theme's editor_content.css:
        p {margin: 0; padding: 0;}           
BUGS:   - Does not support Wikitext Bullet list
                - Does not support Wikitext Numbered list
                - Does not support Wikitext Redirect to another article
                - Does not support Wikitext Tables
TODO:   - Enable Ajax usage
This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.
This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.
You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

Modified by Toshiya TSURU <turutosiya@gmail.com> for Mediawiki 1.18.1

*/
 
 
if( !defined( 'MEDIAWIKI' ) ) {
  die();
}
 
$wgExtensionCredits['other'][] = array(
 
        "name" => "TinyMCE MW extension",
        "author" => "Joseph P. Socoloski III",
        "version" => "0.5.4",
        "url" => "http://www.mediawiki.org/wiki/Extension:TinyMCE_MW",
        "description" => "Easily implement Moxiecode's TinyMCE into MediaWiki"
 );
 
# REGISTER HOOKS
$wgHooks['ParserBeforeStrip'][]                 = 'wfTinymceParserCut';
$wgHooks['ParserAfterTidy'][]                   = 'wfTinymceParserPaste';
$wgHooks['ArticleAfterFetchContent'][]                   = 'wfCheckBeforeEdit';
$wgHooks['EditPage::showEditForm:initial'][]    = 'wfTinymceAddScript';
 
##Process the raw wikidb code before any internal processing is applied
function wfTinymceParserCut ($q, $text) {
 
        global $wgTitle;
        global $wgTempText, $wgUseTinymce;
 
        $ns_allowed = true;
        $ns = $wgTitle->getNamespace();
 
        #if (in_array($ns, $wgexcludedNamespaces)) $ns_allowed = false;
        $wgTempText = $text;#get text
        #$text = "";
        
        return true;
}
 
##Process the wgTempText code (wikitext and html) and reformat it into html friendly $text
function wfTinymceParserPaste ($q, $text) {
 
        global $wgOut, $wgTitle, $wgParser;
        global $wgTempText, $wgTinymceToken, $wgUseTinymce;
 
        $List = array();
 
        $ns_allowed = true;
        $ns = $wgTitle->getNamespace();
 
        // @HACK this enables the upload script and other special pages
        if ($ns == -1) {
                return true;
        }
 
        # TinyMCE can NOT be enabled for any pages that have data tags
        if ($ns_allowed and $wgUseTinymce) {
 
                $tinymcetext = $wgTempText; 
 
                ## EXTENSION TAGS | ADD HERE ##
                         #Custom tags may ONLY be entered in the regular editor NOT the HTML Source editor
                         #Allow_inputbox_tags
                         while (preg_match("|&lt;inputbox&gt;(.*?)&lt;/inputbox&gt;|is", $tinymcetext, $a)) {
                                        $r = preg_replace("| |i", "", $a[0]);#erase all the whitespace
                                 $r = preg_replace("|</p><p>|i", "<br/>", $a[0]);#sometimes </p><p> instead of br
                                 $r = preg_replace("|<br.*?\>|i", "\n", $r);
                                        $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);#htmlentities()
                         }
                         #Allow_math_tags
                         while (preg_match("|&lt;imath&gt;(.*?)&lt;/math&gt;|is", $tinymcetext, $a)) {
                                        $r = preg_replace("| |i", "", $a[0]);#erase all the whitespace
                                 $r = preg_replace("|</p><p>|i", "<br/>", $a[0]);#sometimes </p><p> instead of br
                                 $r = preg_replace("|<br.*?\>|i", "\n", $r);
                                        $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);#htmlentities()
                         }
                                #Allow_repeat_tags
                         while (preg_match("|&lt;repeat.*?&gt;(.*?)&lt;/repeat&gt;|is", $tinymcetext, $a)) {
                                        $r = preg_replace("| |i", "", $a[0]);#erase all the whitespace
                                 $r = preg_replace("|</p><p>|i", "<br/>", $a[0]);#sometimes </p><p> instead of br
                                  #<repeat table="Service_Center_Table" sort="sc_num"></repeat>
                                 $r = preg_replace("|<br.*?\>|i", "\n", $r);
                                        $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);#htmlentities()
                         }
                                #Allow_categorytree_tags
                         while (preg_match("|&lt;categorytree.*?&gt;(.*?)&lt;/categorytree&gt;|is", $tinymcetext, $a)) {
                                        $r = preg_replace("| |i", "", $a[0]);#erase all the whitespace
                                 $r = preg_replace("|</p><p>|i", "<br/>", $a[0]);#sometimes </p><p> instead of br
                                 $r = preg_replace("|<br.*?\>|i", "\n", $r);
                                        $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);#htmlentities()
                         }
                                #Allow_includeonlyx_tags
                         while (preg_match("|&lt;includeonly&gt;(.*?)&lt;/includeonly&gt;|is", $tinymcetext, $a)) {
                                        $r = preg_replace("| |i", "", $a[0]);#erase all the whitespace
                                 $r = preg_replace("|</p><p>|i", "<br/>", $a[0]);#sometimes </p><p> instead of br
                                 $r = preg_replace("|<br.*?\>|i", "\n", $r);
                                        $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);#htmlentities()
                         }
                                #Allow_repeat_tags
                         while (preg_match("|&lt;sql2wiki.*?&gt;(.*?)&lt;/sql2wiki&gt;|is", $tinymcetext, $a)) {
                                        $r = preg_replace("| |i", "", $a[0]);#erase all the whitespace
                                 $r = preg_replace("|</p><p>|i", "<br/>", $a[0]);#sometimes </p><p> instead of br
                                  #<repeat table="Service_Center_Table" sort="sc_num"></repeat>
                                 $r = preg_replace("|<br.*?\>|i", "\n", $r);
                                        $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);#htmlentities()
                         }
                ## EXTENSION TAGS END ##
                
                #Allow_a_tags
                        $i = 0;
                        $ta = md5("aopen");
                        while (preg_match("|(<a.*?\>)|i", $tinymcetext, $a)) {
                                $j = $ta."_".md5($i);
                                $List[$j]["content"] = $a[0];
                                $List[$j]["index"] = $j;
                                $tinymcetext = str_replace($a[0], $j, $tinymcetext);
                                $i++;
                        }
                        $i = 0;
                        $ta = md5("aclose");
                        while (preg_match("|(</a>)|i", $tinymcetext, $a)) {
                                $j = $ta."_".md5($i);
                                $List[$j]["content"] = $a[0];
                                $List[$j]["index"] = $j;
                                $tinymcetext = str_replace($a[0], $j, $tinymcetext);
                                $i++;
                        }
 
                #Allow_img_tags
                        $i = 0;
                        $timg = md5("img");
                        while (preg_match("|(<img[^>]*?/>)|i", $tinymcetext, $a)) {
                                $j = $timg."_".md5($i);
                                $List[$j]["content"] = $a[0];
                                $List[$j]["index"] = $j;
                                $tinymcetext = str_replace($a[0], $j, $tinymcetext);
                                $i++;
                        }
 
                ## MEDIAWIKI WIKITEXT HANDLING ##
                #'''''bold and italic'''''
                        while (preg_match("|'''''.*?'''''|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("'''''", "", $value);
                                $r = preg_replace("|'''''.*?'''''|is", "<i><strong>".$value."</strong></i>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                #'''bold'''
                        while (preg_match("|'''.*?'''|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("'''", "", $value);
                                $r = preg_replace("|'''.*?'''|is", "<strong>".$value."</strong>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                #''italic''
                        while (preg_match("|''.*?''|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("''", "", $value);
                                $r = preg_replace("|''.*?''|is", "<i>".$value."</i>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
 
                #=====level 4=====
                        while (preg_match("|=====.*?=====|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("=====", "", $value);
                                $r = preg_replace("|=====.*?=====|is", "<h5>".$value."</h5>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                #====level 3====
                        while (preg_match("|====.*?====|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("====", "", $value);
                                $r = preg_replace("|====.*?====|is", "<h4>".$value."</h4>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                #===level 2===
                        while (preg_match("|===.*?===|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("===", "", $value);
                                $r = preg_replace("|===.*?===|is", "<h3>".$value."</h3>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                #==heading==
                        while (preg_match("|==.*?==|is", $tinymcetext, $a)) {
                                $value = implode(",", $a);
                                $value = str_replace("==", "", $value);
                                $r = preg_replace("|==.*?==|is", "<h2>".$value."</h2>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                #==heading 1==
                ## Does not support <h1> tags because "|=.*?=|is" grabs too much
                #---- horizontal line
                        while (preg_match("|----|is", $tinymcetext, $a)) {
                                $r = preg_replace("|----|is", "<hr>", $a[0]);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                ## MEDIAWIKI WIKITEXT HANDLING END ##
                
                $tagList = array("pre", "math", "gallery", "nowiki", "html", "syntax");
                foreach($tagList as $tag) {
                        while (preg_match("|&lt;($tag.*?)&gt;(.*?)&lt;/$tag&gt;|is", $tinymcetext, $a)) {  
                                $r = preg_replace("|<br.*?\>|i", "", $a[0]);
                                $r = preg_replace("|&nbsp;|i", " ", $r);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                }
 
                foreach($q->mTagHooks as $tag => $func) {
                        while (preg_match("|&lt;($tag.*?)&gt;(.*?)&lt;/$tag&gt;|is", $tinymcetext, $a)) {  
                                $r = preg_replace("|<br.*?\>|i", "", $a[0]);
                                $r = preg_replace("|&nbsp;|i", " ", $r);
                                $tinymcetext = str_replace($a[0], html_entity_decode($r), $tinymcetext);
                        }
                }
                $state = new StripState("");
                $x =& $state;
                // $tinymcetext = $q->strip($tinymcetext, $x);
                // http://svn.wikimedia.org/svnroot/mediawiki/branches/REL1_17/phase3/includes/parser/Parser.php
				$tinymcetext = $q->replaceVariables($tinymcetext, $x);
				
                # optional remove <p></p> 
                #$tinymcetext = preg_replace('/<p[^>]*>/','',$tinymcetext);//Remove the start <p> or <p attr="">
#                $tinymcetext = preg_replace('/<\/p>/', '<br />', $tinymcetext); // Replace the end
                         #$tinymcetext = preg_replace('/<\/p>/', '\n', $tinymcetext); // Replace the end
                         #$tinymcetext = preg_replace("|<br/>|i", "\n", $tinymcetext);
                $tinymcetext = preg_replace("/<\/?tbody>/i","", $tinymcetext);
                $tinymcetext = preg_replace("/$wgTinymceToken/i","", $tinymcetext); 
                $tinymcetext = Sanitizer::removeHTMLtags( $tinymcetext, array( &$q, 'attributeStripCallback' ) );
                $tinymcetext = $q->replaceVariables($tinymcetext);
         //$fcktext = $q->doDoubleUnderscore( $fcktext ); //var $fcktext is not set
           $tinymcetext = $q->doDoubleUnderscore( $tinymcetext ); // should be this
                $tinymcetext = $q->replaceInternalLinks( $tinymcetext );
                $tinymcetext = $q->replaceExternalLinks( $tinymcetext );
                $tinymcetext = str_replace($q->mUniqPrefix."NOPARSE", "", $tinymcetext);
                $tinymcetext = $q->doMagicLinks( $tinymcetext );
                $tinymcetext = $q->formatHeadings( $tinymcetext, true );
                $q->replaceLinkHolders( $tinymcetext );
				
				
                // $tinymcetext = $q->unstripNoWiki( $tinymcetext, $state );
                // http://svn.wikimedia.org/svnroot/mediawiki/branches/REL1_17/phase3/includes/parser/Parser.php
				// $this->mStripState->unstrip( $tinymcetext, $state );
                $tinymcetext = $state->unstripGeneral( $tinymcetext );
 
                foreach($List as $item) {
                        $tinymcetext = str_replace($item["index"], $item["content"], $tinymcetext);
                        $i++;
                }
 
                $text = $tinymcetext;
        }
        return true;
}
 
 
function wfTinymceAddScript ($q) { 
 
        global $wgOut, $wgTitle, $wgScriptPath, $wgMyWikiURL; 
        global $wgTempText, $wgTinymceDir, $wgTinymceTheme, $wgExt_valid_elements, $wgUseTinymce;
 
        $wgTinymceDir = "TinyMCE_MW";
        $ns_allowed = true;
        $ns = $wgTitle->getNamespace();
 
        if ($ns_allowed && $wgUseTinymce)
        {
                # following from http://rorlach.de/mediawiki/index.php/Toggle_TinyMCE
                 $wgOut->addScript("<script language=\"javascript\" type=\"text/javascript\">
var tinyMCEmode = true;
function toggleEditorMode(wpTextbox1,eleToggleLink) {
    try {
        if(tinyMCEmode) {
            tinyMCE.execCommand(\"mceRemoveControl\", false, wpTextbox1);
            tinyMCEmode = false;
            if(eleToggleLink)
                eleToggleLink.innerHTML    =    \"Enable Advanced Editor\";
        } else {
            tinyMCE.execCommand(\"mceAddControl\", false, wpTextbox1);
            tinyMCEmode = true;
            if(eleToggleLink)
                eleToggleLink.innerHTML    =    \"Disable Advanced Editor\";
        }
    } catch(e) {
        //error handling
    }
}
</script>");
 
        if (($wgTinymceTheme == "simple")){
                $wgOut->addScript( "<script language=\"javascript\" type=\"text/javascript\" src=\"$wgScriptPath/extensions/$wgTinymceDir/jscripts/tiny_mce/tiny_mce.js\"></script><script language=\"javascript\" type=\"text/javascript\">tinyMCE.init({
        mode : \"textareas\",
        theme : \"simple\",
        convert_newlines_to_brs : false,
        apply_source_formatting : true,
        relative_urls : false,
        remove_script_host : true,
                document_base_url : \"$wgMyWikiURL\",
        extended_valid_elements : \"$wgExt_valid_elements\"
});</script>" );
} elseif(($wgTinymceTheme == "advanced")) {
        $wgOut->addScript( "<script language=\"javascript\" type=\"text/javascript\" src=\"$wgScriptPath/extensions/$wgTinymceDir/jscripts/tiny_mce/tiny_mce.js\"></script><script language=\"javascript\" type=\"text/javascript\">tinyMCE.init({
        mode : \"textareas\",
        theme : \"advanced\",
        convert_newlines_to_brs : false,
        apply_source_formatting : true,
        relative_urls : false,
        remove_script_host : true,
                document_base_url : \"$wgMyWikiURL\",
        extended_valid_elements : \"$wgExt_valid_elements\"
});</script>" );
}elseif(($wgTinymceTheme == "msword")) {
        $wgOut->addScript( "<script language=\"javascript\" type=\"text/javascript\" src=\"$wgScriptPath/extensions/$wgTinymceDir/jscripts/tiny_mce/tiny_mce.js\"></script><script language=\"javascript\" type=\"text/javascript\">tinyMCE.init({
		mode : \"textareas\",
		theme : \"advanced\",
		height : \"600\",
		theme_advanced_blockformats : \"p,address,pre,div,h2,h3,h4,h5,h6,blockquote,dt,dd,code,samp\",
		convert_newlines_to_brs : false,
		apply_source_formatting : true,
		relative_urls : false,
		remove_script_host : true,
        document_base_url : \"$wgMyWikiURL\",
		extended_valid_elements : \"$wgExt_valid_elements\",
		custom_elements : \"$wgExt_valid_elements\",
		plugins : \"style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras\",
		theme_advanced_buttons1 : \"styleselect,newdocument,save,|,print,|,iespell,|cut,copy,paste,pastetext,pasteword,|,undo,redo,|,link,unlink,image,hr,anchor,|,search,replace,|,tablecontrols,|,help\",
        theme_advanced_buttons2 : \"formatselect,fontselect,fontsizeselect,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,bullist,numlist,|,outdent,indent,cleanup,backcolor,forecolor,removeformat\",
		theme_advanced_buttons3 : \"insertdate,inserttime,|,sub,sup,|,charmap,emotions,media,|,ltr,rtl,|,fullscreen,|,code\",
		theme_advanced_layout_manager : \"SimpleLayout\",
        theme_advanced_toolbar_location : \"top\",
        theme_advanced_toolbar_align : \"left\"
});</script>");
}else{$wgOut->addScript("**TINYMCE NOT ENABLED: FIX wgTinymceTheme**<script language=\"javascript\" type=\"text/javascript\"></script>" );}
                #Since editing add the button
         $wgOut->addHTML("<p align=\"right\"><a id=\"toggleLink\" href=\"#\" title=\"toggle TinyMCE\" onclick=\"toggleEditorMode('wpTextbox1',this);\">Hide Editor</a></p>");
}
else{$wgOut->addScript("<script language=\"javascript\" type=\"text/javascript\"></script>" ); $wgUseTinymce = true; }
        return true;
}
 
# Check existing article for any tags we don't want TinyMCE parsing...
function wfCheckBeforeEdit ($q, $text) { 
        global $wgUseTinymce;
 
        if (preg_match("|&lt;(data.*?)&gt;(.*?)&lt;/data&gt;|is", $text, $a)) {
                $wgUseTinymce = false;
        }
        elseif(preg_match("|<(data.*?)>(.*?)</data>|is", $text, $a)) {
                $wgUseTinymce = false;}
        else{$wgUseTinymce = true;}
        return true;
}
 
?>