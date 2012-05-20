<?php
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

		/// This function is hooked to the ArticleSave event.
		/// It will be called whenever a page is about to be 
		/// saved.
		public static function onArticleSave( &$article, &$user, &$text, &$summary,
				$minor, $watchthis, $sectionanchor, &$flags, &$status ) {

			// To prevent time-consuming parsing of the page whenever
			// it is edited and saved, we only parse it if the flag
			// 'minor edits' is not set.

			if ( !$minor ) {
				// Build an SQL query and fetch all page titles ordered
				// by length from shortest to longest.
				$dbr = wfGetDB( DB_SLAVE );
				$res = $dbr->select( 
					'page', 
					'page_title', 
					'', 
					__METHOD__, 
					array( 'ORDER BY' => 'length(page_title)' ));

				// Iterate through the page titles
				foreach( $res as $row ) {
					// Page titles are stored in the database with spaces
					// replaced by underscores. Therefore we now convert
					// the underscores back to spaces.
					$title = str_replace('_', ' ', $row->page_title);

					// Now look for every occurrence of $title in the
					// page $text and enclose it in double square brackets,
					// unless it is already enclosed in brackets (directly
					// adjacent or remotely, see http://stackoverflow.com/questions/10672286
					// Regex built with the help from Eugene @ Stackoverflow
					// http://stackoverflow.com/a/10672440/270712
					$text = preg_replace(
						'/(' . $title . ')([^\]]+(\[|$))/i',
						'[[$1]]$2',
						$text );
				};
			};
			return true;
		}
	}
	// vim: ts=2:sw=2:noet