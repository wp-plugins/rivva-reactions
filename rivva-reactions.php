<?php
/*
Plugin Name: Rivva Reactions
Plugin URI:  http://bueltge.de/rivva-reaction-wordpress-plugin/1029/
Description: This plugin was not supportet or developed; Rivva is down.
Version:     0.5.0
Author:      Frank B&uuml;ltge
Author URI:  http://bueltge.de/
Last Change: 27.02.2011
/*

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
The license is also available at http://www.gnu.org/copyleft/gpl.html
*/

//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( !class_exists('RivvaReactions') ) {
	class RivvaReactions {
		
		/**
		 * constructor
		 */
		function RivvaReactions() {
			if ( is_admin() ) {
				add_action( 'after_plugin_row_' . plugin_basename(__FILE__), array(&$this, 'update_notice') );
				delete_option( 'RivvaReactions' );
			}
		}
		
		function update_notice() {
			echo '<tr class="plugin-update-tr"><td class="plugin-update" colspan="3"><div class="update-message">' . __('This plugin was not supportet or developed; Rivva is down. Please see also the <a target="_blank" href="http://rivva.de">rivva.de</a>.') . '</div></td></tr>';
		}
		
	} // end class
	
	$RivvaReactions = new RivvaReactions();
}
?>
