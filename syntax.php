<?php
/**
 * DokuWiki Syntax Plugin Backlinks
 *
 * Shows a list of pages that link back to a given page.
 *
 * Syntax:  {{backlinks>[pagename]}}
 *
 *   [pagename] - a valid wiki pagename
 * 
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Michael Klier <chi@chimeric.de>
 * @author  Mark C. Prins <mprins@users.sf.net>
 */
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('DW_LF')) define('DW_LF',"\n");

require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/parserutils.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_backlinks extends DokuWiki_Syntax_Plugin {
    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()  { return 'substition'; }

    /**
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType() { return 'block'; }

    /**
     * @see Doku_Parser_Mode::getSort()
     */
    function getSort()  { return 304; }
    
    /**
     * Connect pattern to lexer.
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{backlinks>.+?\}\}', $mode, 'plugin_backlinks');
    }

    /**
     * Handler to prepare matched data for the rendering process.
     * @see DokuWiki_Syntax_Plugin::handle()
     */
    function handle($match, $state, $pos, &$handler){

        // Because the page id can be a sidebar id, we
        // can handle the  not here, otherwise it will be cached
        // in the instructions

        $match = substr($match,12,-2); //strip {{backlinks> from start and }} from end
        return (array($match));
    }

    /**
     * Handles the actual output creation.
     * @see DokuWiki_Syntax_Plugin::render()
     */
    function render($mode, &$renderer, $data) {

        global $lang;

        // Take the id of the source
        // It can be a rendering of a sidebar
        global $INFO;
        global $ID;
        $id = $ID;
        // If it's a sidebar, get the original id.
        if ($INFO != null) {
            $id = $INFO['id'];
        }

        if ($data[0] == '.') { // The page id
            $backLinksId =  $id;
        } elseif (strstr($data[0],".:" )) { // Relative Path
            $backLinksId = $data[0];
            resolve_pageid(getNS($id),$backLinksId,$exists);
        } else { // Full Path
            $backLinksId = $data[0];
        }

        if($mode == 'xhtml'){
            $renderer->info['cache'] = false;
            
            @require_once(DOKU_INC.'inc/fulltext.php');
            $backLinks = ft_backlinks($backLinksId);
            
            $renderer->doc .= '<div id="plugin__backlinks">' . DW_LF;

            if(!empty($backLinks)) {

                $renderer->doc .= '<ul class="idx">';

                foreach($backLinks as $backLink){
                    $name = p_get_metadata($backLink,'title');
                    if(empty($name)) $name = $backLink;
                    $renderer->doc .= '<li><div class="li">';
                    $renderer->doc .= html_wikilink(':'.$backLink,$name,'');
                    $renderer->doc .= '</div></li>';
                }

                $renderer->doc .= '</ul>';
            } else {
                $renderer->doc .= "<strong>Plugin BackLinks: " . $lang['nothingfound'] . "</strong>";
            }
            
            $renderer->doc .= '</div>' . DW_LF;

            return true;
        }
        return false;
    }
}
