<?php
/*
Plugin Name: Darkpage Eventer Signup
Plugin URI: none
Description: Simple script to attach a guest list to a post.
Author: darkstar
Version: 1
Author URI: none
*/

$wp_root = '../../..';
if (!function_exists('add_action')) {
	if (file_exists($wp_root.'/wp-load.php')) {
		require_once($wp_root.'/wp-load.php');
	} else {
		require_once($wp_root.'/wp-config.php');
	}
}

$db_create = "";
$db_create .= "CREATE TABLE IF NOT EXISTS `dpe_signups` (";
$db_create .= "`dpe_id` int(11) NOT NULL auto_increment,";
$db_create .= "`dpe_post_id` int(11) NOT NULL default '0',";
$db_create .= "`dpe_user_id` int(11) NOT NULL default '0',";
$db_create .= "`dpe_user_vote` set('1','2','3') NOT NULL default '',";
$db_create .= "PRIMARY KEY  (`dpe_id`)";
$db_create .= ") TYPE=MyISAM AUTO_INCREMENT=1 ;";
$wpdb->query($db_create);



function dpe_get_vote($vote) {
	Switch ($vote) {
		CASE 1:
			return "Bin dabei!";
			break;
			
		CASE 2:
			return "Kann leider nicht!";
			break;
			
		CASE 3:
			return "Kann noch nichts konkretes sagen.";
			break;
	}
}

function dpe_get_voter($user_id) {
	global $wpdb, $post, $current_user;
	$user_name = $wpdb->get_var("SELECT display_name FROM $wpdb->users Where ID='" . $user_id . "' LIMIT 1");
	return $user_name;
}

function dpe_get_list($post_id) {
	global $wpdb, $post, $current_user;
	
	$dpe_list = array();
	
	If ($sql = $wpdb->get_results("Select dpe_user_id, dpe_user_vote From dpe_signups Where dpe_post_id = '" . $post_id . "'")) {
		foreach($sql as $sql) {
			$dpe_list[$sql->dpe_user_id] = $sql->dpe_user_vote;
		}
		return $dpe_list;
	}
	else {
		return false;
	}
	
	
}

function dpe_vote($post_id, $vote) {
	global $wpdb, $post, $current_user;	
	
	$vote_allright = false;
	
	If ($current_user->ID != 0) {
		
		$votes = dpe_get_list($post_id);
		
		If (empty($votes[$current_user->ID])) {
			
			$sql = "Insert Into dpe_signups(dpe_post_id, dpe_user_id, dpe_user_vote) VALUES(" . $post_id . ", " . $current_user->ID . ", " . $vote . ");";
			If ($wpdb->query($sql)) {
				$vote_allright = true;
			}
			else {
				echo "<font color='#FF0000'>02/00 bad error<BR></font>";
			}
		}
		else {
			$sql = "Update dpe_signups SET dpe_user_vote='" . $vote . "' Where dpe_user_id='" . $current_user->ID . "' AND dpe_post_id='" . $post_id . "' LIMIT 1";
			If ($wpdb->query($sql)) {
				$vote_allright = true;
			}
			else {
				echo "<font color='#FF0000'>02/01 bad error<BR></font>";
			}
		}
	}
	
	If ($vote_allright = true) {
		return true;
	}
	else {
		return false;
	}
}

add_shortcode('dpe', 'dpe_short');
function dpe_short($atts) {
	return dpe_main();
}

function dpe_main() {
	global $wpdb, $post, $current_user;

	$dpe_content = "";
	// post id $post->ID;
	// user id $current_user->ID; falls nicht eingeloggt user id = 0

	
	Switch ($_REQUEST["dpe_action"]) {
		CASE "submit_vote":
			#Anmelden!!
			If (empty($_REQUEST["p"])) {
				echo "01/00 bad error<BR>";
			}
			else {
				If (!dpe_vote($_REQUEST["p"], $_REQUEST["vote"])) {
					echo "01/01 bad error<BR>";
				}
			}
			
			break;
	}
	
	$votes = dpe_get_list($post->ID);
	
	
	//Table Header
	$align = "alignleft"; //#% k�nnte man �ndern damit man noch align einstellen k�nnte
		$dpe_content .= "<a name='dpe_eventer'><div class='wp-caption " . $align . "' style='width: 350px'><table width='340' align='center' colspan='0' rowspan='0' cellpadding='0' cellspacing='0' border='0'>";
	
	If ($current_user->ID != 0) {
		
		$dpe_status_change = "";
		If (!empty($votes[$current_user->ID])) {
			#user hat sich angemeldet
			$dpe_content .= "<tr><td colspan='3' align='left'>Dein aktueller Status<BR> [ <b>" . dpe_get_vote($votes[$current_user->ID]) . "</b> ]</td></tr>";
			$dpe_status_change = "Status &auml;ndern:<BR>";
		}
		else {
			#user hat sich noch nicht angemeldet�
			$dpe_content .= "<tr><td colspan='3' align='left'>Jetzt Anmelden!</td></tr>";
		}
		
		$dpe_content .= "<tr><td colspan='3' align='left'>";
		$dpe_content .= $dpe_status_change;
		$dpe_content .= '<form method="post">';
		$dpe_content .= '<input type="hidden" name="dpe_action" value="submit_vote">';
		$dpe_content .= '<input type="hidden" name="p" value="' . $post->ID . '">';
			
		$dpe_content .= '<a href="?p=' . $post->ID . '&dpe_action=submit_vote&vote=1#dpe_eventer">' . dpe_get_vote(1) . '</a><BR>';
		$dpe_content .= '<a href="?p=' . $post->ID . '&dpe_action=submit_vote&vote=2#dpe_eventer">' . dpe_get_vote(2) . '</a><BR>';
		$dpe_content .= '<a href="?p=' . $post->ID . '&dpe_action=submit_vote&vote=3#dpe_eventer">' . dpe_get_vote(3) . '</a><BR>';
		$dpe_content .= '</form>';
		$dpe_content .= "</td></tr>";
		$dpe_content .= "<tr><td colspan='3'>&nbsp;</td></tr>";
	}
	
	//loop header
	$dpe_content .= "<tr><td colspan='3'>&nbsp;</td></tr>";
	$dpe_content .= "<tr><td colspan='3' align='left'>G&auml;steliste: </tr>";
	$dpe_content .= "<tr><td align='left'><font size='1'><b>User</b></font></td><td>  </td><td align='left'><font size='1'><b>Status</b></font></td></tr>";
	$dpe_content .= "<tr><td colspan='3' height='1' bgcolor='#000000'></td></tr>";
	
	If (is_array($votes)) {
		foreach ($votes as $voter_id => $voter_vote) {
			$dpe_content .= "<tr><td align='left'>" . dpe_get_voter($voter_id) . "</td><td>  </td><td align='left'>" . dpe_get_vote($voter_vote) . "</td></tr>";
			$dpe_content .= "<tr><td colspan='3' height='1'></td></tr>";
		}
	}
	else {
		$dpe_content .= "<tr><td colspan='3' align='left'>noch keine Anmeldungen</td></tr>";
	}
	
	//Table End
		$dpe_content .= "</table></div>";
	return $dpe_content;

}

?>