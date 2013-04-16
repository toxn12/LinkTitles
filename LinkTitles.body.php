<?php

require dirname(__FILE__) . "/LinkTitles.body.MachineAC.php";
require dirname(__FILE__) . "/LinkTitles.body.Ngram.php";

/*
 *      \file LinkTitles.body.php
 *      
 *      Copyright 2012 Daniel Kraus <krada@gmx.net>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */

  if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
        }

        function dump($var) {
                        error_log(print_r($var, TRUE), 3, 'php://stderr');
        };

        class LinkTitles {
                /// Setup function, hooks the extension's functions to MediaWiki events.
                public static function setup() {
                        global $wgLinkTitlesParseOnEdit;
                        global $wgLinkTitlesParseOnRender;
                        global $wgHooks;
                        if ( $wgLinkTitlesParseOnEdit ) {
                                $wgHooks['ArticleSave'][] = 'LinkTitles::onArticleSave';
                        };
                        if ( $wgLinkTitlesParseOnRender ) { 
                                $wgHooks['ArticleAfterFetchContent'][] = 'LinkTitles::onArticleAfterFetchContent';
                        };
                }

                /// This function is hooked to the ArticleSave event.
                /// It will be called whenever a page is about to be 
                /// saved.
                public static function onArticleSave( &$article, &$user, &$text, &$summary,
                                $minor, $watchthis, $sectionanchor, &$flags, &$status ) {
                        // To prevent time-consuming parsing of the page whenever
                        // it is edited and saved, we only parse it if the flag
                        // 'minor edits' is not set.
                        return $minor or self::parseContent( $article, $text );
                }

                /// Called when an ArticleAfterFetchContent event occurs; this requires the
                /// $wgLinkTitlesParseOnRender option to be set to 'true'
                public static function onArticleAfterFetchContent( &$article, &$content ) {
                        // The ArticleAfterFetchContent event is triggered whenever page content
                        // is retrieved from the database, i.e. also for editing etc.
                        // Therefore we access the global $action variabl to only parse the 
                        // content when the page is viewed.

                        $actionName = self::getAction();
                        if ( in_array( $actionName, array('view', 'render', 'purge') ) ) {
                                self::parseContent( $article, $content );
                        };
                        return true;
                }

                /// This function performs the actual parsing of the content.
                static function parseContent( &$article, &$text ) {
                        // Configuration variables need to be defined here as globals.
                        global $wgLinkTitlesPreferShortTitles;
                        global $wgLinkTitlesMinimumTitleLength;
                        global $wgLinkTitlesParseHeadings;
                        global $wgLinkTitlesBlackList;
                        global $wgLinkTitlesParseIgnorePagePatterns;

                        // To prevent adding self-references, we now
                        // extract the current page's title.
                        $myTitle = $article->getTitle()->getText();
                        ( $wgLinkTitlesPreferShortTitles ) ? $sort_order = '' : $sort_order = 'DESC';

                        // Build a regular expression that will capture existing wiki links ("[[...]]"),
                        // wiki headings ("= ... =", "== ... ==" etc.),  
                        // urls ("http://example.com", "[http://example.com]", "[http://example.com Description]",
                        // and email addresses ("mail@example.com").
                        // Since there is a user option to skip headings, we make this part of the expression
                        // optional. Note that in order to use preg_split(), it is important to have only one
                        // capturing subpattern (which precludes the use of conditional subpatterns).
                        ( $wgLinkTitlesParseHeadings ) ? $delimiter = '' : $delimiter = '=+.+?=+|';
                        $urlPattern = '[a-z]+?\:\/\/(?:\S+\.)+\S+(?:\/.*)?';
                        $delimiter = '/(' . $delimiter . '\[\[.*?\]\]|\[' . 
                                $urlPattern . '\s.+?\]|'. $urlPattern . '(?=\s|$)|(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b)|{{.*?}})/i';

                        $black_list = str_replace( '_', ' ',
                                '("' . implode( '", "',$wgLinkTitlesBlackList ) . '")' );
                        //dump( $black_list );

                        // Build an SQL query and fetch all page titles ordered
                        // by length from shortest to longest.
                        // Only titles from 'normal' pages (namespace uid = 0)
                        // are returned.
                        $dbr = wfGetDB( DB_SLAVE );
                        $res = $dbr->select( 
                                'page', 
                                'page_title', 
                                array( 
                                        'page_namespace = 0', 
                                        'CHAR_LENGTH(page_title) >= ' . $wgLinkTitlesMinimumTitleLength,
                                        'page_title NOT IN ' . $black_list,
                                ), 
                                __METHOD__, 
                                array( 'ORDER BY' => 'CHAR_LENGTH(page_title) ' . $sort_order )
                        );

            foreach($wgLinkTitlesParseIgnorePagePatterns as $IgnorePattern){
                if(preg_match($IgnorePattern, $myTitle)){
                    return true;
                }
            }

            $byte_len = strlen(bin2hex("$text")) / 2;
            if($byte_len > 50000){
                $className = 'LinkMachineAC';
            }else{
                $className = 'LinkNgram';
            }

            $titleList = array();
            foreach( $res as $row){
                $titleList[] = str_replace('_', ' ', $row->page_title);
            }
   
            $text = $className::linkText($text, $titleList, $myTitle);
                        return true;
                }
                static function getAction(){
                       global $wgRequest;
                       $actionName = $wgRequest->getText( 'action' );
                       if ($actionName == '') {
                          return 'view';
                       }else{
                                return $actionName;
                       }
                }
        }

        // vim: ts=2:sw=2:noet
