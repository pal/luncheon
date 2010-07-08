<?php
/*
Plugin Name: Luncheon
Plugin URI: http://palbrattberg.com/luncheon/
Description: Manage menues for your restaurant in WordPress!
Version: 1.2
Author: P&aring;l Brattberg
Author URI: http://palbrattberg.com
*/
?>
<?php
/*  Copyright 2008  P&aring;l Brattberg  (email : brattberg@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

date_default_timezone_set('Europe/Stockholm');
setlocale(LC_ALL, 'sv_SE');

// Currently used options
// luncheon_db_version - Version of DB-schema

// Version of database structure
$luncheon_db_version = "1.0";
$luncheon_db_date_format = "Y-m-d";

function luncheon_getImageURL() {
  return get_bloginfo('url') .'/wp-content/plugins/luncheon-plugin/';
}
function luncheon_printImageURL() {
  echo luncheon_getImageURL();
}
function luncheon_format_date($myDate) {
  return ucwords(strftime("%A", $myDate)) . 
        "en den " . strftime("%d", $myDate) . " " . 
        ucwords(strftime("%B", $myDate)) . " " . 
        strftime("%Y", $myDate);
}

function luncheon_dbtblname_meals() {
  global $wpdb;
  return $wpdb->prefix . "luncheon_meals";
}

function luncheon_dbtblname_offerings() {
  global $wpdb;
  return $wpdb->prefix . "luncheon_offerings";
}


// create/update all custom database tables
function luncheon_db_install () {
  luncheon_db_install_meals();
  luncheon_db_install_offerings();
  update_option("luncheon_db_version", $luncheon_db_version);
}

// create/update db table 1/2
function luncheon_db_install_meals() {
  global $wpdb,$luncheon_db_version;
  $table_name = luncheon_dbtblname_meals();
  
  // Check if table present and correct version
  $table_present = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);
  $table_current_version = (get_option("luncheon_db_version") == $luncheon_db_version);
  
  // if not, make sure to make it so
  if (!$table_present || !$table_current_version) {
    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      description VARCHAR(255) NOT NULL DEFAULT '',
      UNIQUE KEY id (id)
    );";
  
    //  Create or Update the Table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // If this was first creation, add some default data
    /*if (!$table_present) {
      $insert = "INSERT INTO " . $table_name . " (description) " .
                "VALUES ('" . $wpdb->escape("Stekt pangasius med remoulads&aring;s") . "')";
      $results = $wpdb->query( $insert );
      
      $insert = "INSERT INTO " . $table_name . " (description) " .
                "VALUES ('" . $wpdb->escape("Korv stroganoff med ris") . "')";
      $results = $wpdb->query( $insert );
      
      $insert = "INSERT INTO " . $table_name . " (description) " .
                "VALUES ('" . $wpdb->escape("Pannbiff med l&ouml;ksky") . "')";
      $results = $wpdb->query( $insert );
    }*/
  }
}

// create/update db table 2/2
function luncheon_db_install_offerings() {
  global $wpdb,$luncheon_db_version;
  $table_name = luncheon_dbtblname_offerings();
  
  // Check if table present and correct version
  $table_present = ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name);
  $table_current_version = (get_option("luncheon_db_version") == $luncheon_db_version);
  
  // if not, make sure to make it so
  if (!$table_present || !$table_current_version) {
    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      meal_id int(11) NOT NULL default '0',
      menu_date date NOT NULL default '0000-00-00',
      UNIQUE KEY id (id)
    );";
  
    //  Create or Update the Table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // If this was first creation, add some default data
    /*if (!$table_present) {
      $insert = "INSERT INTO " . $table_name . " (meal_id) VALUES (1)";
    }*/
  }
}

// This function will be used in our templates to show the intro
function luncheon_print_todays_menu(){
  echo luncheon_get_todays_menu();
}

// This function will be used in our templates to show the intro
function luncheon_get_todays_menu(){
  $meals = luncheon_get_meal_and_id_by_date(time());
  $rv = "";
  foreach ($meals as $value) {
    $rv .= "- ".$value['description']."<br/><br/>";
  }
  return $rv;
}

function luncheon_get_meal_and_id_by_date($chosenDate){
  global $wpdb, $luncheon_db_date_format;
  $sql = "SELECT meals.id, meals.description, offerings.id AS offering_id FROM " . luncheon_dbtblname_offerings() . 
    " AS offerings, " . luncheon_dbtblname_meals() . 
    " AS meals WHERE (offerings.menu_date = '" . date($luncheon_db_date_format, $chosenDate) . "') AND (offerings.meal_id = meals.id)";
  $items = $wpdb->get_results($sql, ARRAY_A);
  if (is_null($items)){
    return array();
  }
  return $items;
}

// Return hashed array of all meals for the specified week and year
function luncheon_get_meals_for_year_and_week($selectedYear, $selectedWeek){
  global $wpdb, $luncheon_db_date_format;
  
  //since we add from week 1 and weeks are zero-based in php, we subtract with 1
  $weekTerm = $selectedWeek - 1;
  $startdate = strtotime("Monday +$weekTerm week $selectedYear", strtotime("1 January $selectedYear")); 
  $weekdays = array();
  for ($day_index = 0; $day_index < 7; $day_index++)
  {
    $current_date = strtotime("+$day_index days", $startdate);
    $day_data['date'] = $current_date;
    $day_data['dbdate'] = date($luncheon_db_date_format,$current_date);
    $day_data['header'] = luncheon_format_date($current_date);
    $day_data['meals'] = luncheon_get_meal_and_id_by_date($day_data['date']);
    
    $weekdays[$day_index] = $day_data;
  }
  return $weekdays;
}

// setup our admin page 
function luncheon_admin_index(){
    global $luncheon_db_date_format;
    
    // setup variables
    $selectedWeek = date("W");
    $selectedYear = date("Y");
    if(isset($_GET['lu_week'])){
      $selectedWeek = trim($_GET['lu_week']);
    }
    if(isset($_GET['lu_year'])){
      $selectedYear = trim($_GET['lu_year']);
    }
    
    $weekSelector = '<select  id="lu_week" class="luncheon_h2">';
    for ($i = 1; $i<54; $i++){
      $weekSelector .= '<option' . ($selectedWeek==$i?" selected":"") . '>'.$i.'</option>';
    }
    $weekSelector .= "</select>\n";
    
    $yearSelector = '<select  id="lu_year" class="luncheon_h2">';
    for ($i = date("Y")-2; $i<date("Y")+2; $i++){
      $yearSelector .= '<option' . ($selectedYear==$i?" selected":"") . '>'.$i.'</option>';
    }
    $yearSelector .= "</select>\n";
    ?>
  <form action="#" onsubmit="do_add_meal();return false;">
  <div class="wrap"><h2>Meny f&ouml;r vecka <?php echo $weekSelector ?>, <?php echo $yearSelector ?></h2>
  H&auml;r styr du vilka r&auml;tter som erbjuds som dagens lunch.
  
  <table>
  <?php
    $weekdays = luncheon_get_meals_for_year_and_week($selectedYear, $selectedWeek);
    echo '<tr>';
    foreach ($weekdays as $day_index => $day_data)
    {
      if ($day_index != 0 && $day_index % 3 == 0){
        echo '</tr><tr>';
      }
      echo '<td valign="top"><div class="luncheon_day"><h3>' . $day_data['header'] . '</h3><div id="day_' . $day_data['dbdate'] . '" class="luncheon">';
      //$todays_meals = luncheon_get_meal_and_id_by_date($day_data['date']);
      foreach($day_data['meals'] as $meal)
      {
        echo '<div id="luncheon_m_'.$meal['offering_id'].'">'.$meal['description'] . 
          ' <a onclick="delete_meal(\''.$meal['offering_id'].'\', \'' . $meal['description'] . 
          '\');"><img src="' . luncheon_getImageURL() . 
          'x-red.gif" alt="Remove this item" /></a><br/></div>'."\n";
      }
      echo '</div><br/><a onclick="add_meal(\'' . $day_data['dbdate'] . '\');">' . 'L&auml;gg till matr&auml;tt ' . 
        '<img src="' .luncheon_getImageURL() . 
        'plus-green.gif" alt="Add another item"/></a><br/></div>'."\n\n";
    }
    echo '</tr>';
    
  ?>
  </table>
  
  <div id="luncheon_choose_meal">
    <div style="text-align: right;"><img alt="Close window" src="<?php luncheon_printImageURL(); ?>x-red.gif" onclick="javacript:cancel_meal_add();"/></div>
    <h3>L&auml;gg till matr&auml;tt</h3><div id="luncheon_choose_meal_date">xxx</div><br/><br/>
    
    Ange matr&auml;tt:<br/>
    <input type="text" autocomplete="off" onblur="luncheon_fill();" onkeyup="luncheon_lookup(this.value);" id="inputString" value="" size="30"/>
		<br/>
    <div style="display: none;" id="luncheon_suggestions" class="luncheon_suggestionsBox">
      <img alt="upArrow" style="position: relative; top: -12px; left: 30px;" src="<?php luncheon_printImageURL(); ?>upArrow.png"/>
        <div id="luncheon_autoSuggestionsList" class="luncheon_suggestionList"> </div>
      </div>
      <input type="button" onclick="do_add_meal();" value="Spara"/>
		</form>
    </div>
    
  <a href="/veckans_meny/?menu_year=<?php echo $selectedYear ?>&menu_week=<?php echo $selectedWeek ?>" target="_blank"><img src="<?php luncheon_printImageURL(); ?>printer.png"/> Skriv ut denna veckas meny</a><br/>
  
  For support visit <a href="http://palbrattberg.com/luncheon/">http://palbrattberg.com/luncheon/</a></div><?php 
}




// Add menu items to Wordpress Admin
function luncheon_setup_adminmenu() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('luncheon_ajax_js', luncheon_getImageURL() . 'luncheon_ajax_js.php', array('jquery'));
    add_menu_page('Manage Menues with Luncheon', 'Luncheon', 8, __FILE__, 'luncheon_admin_index');
    //add_submenu_page('options-general.php', 'Manage Menues with Luncheon', 'Luncheon', 10, __FILE__, 'luncheon_adminpage');
}

// Add custom css to admin
function luncheon_css() {
    echo '<style type="text/css">
      .luncheon > a, .luncheon > a > img {text-decoration: none; }
      .luncheon > a:hover, .luncheon > a:hover > img { text-decoration: underline; }
      .luncheon_day { margin: 0px 5px 5px; padding: 10px; min-width: 280px; min-height: 135px;}
      .luncheon_day > h3 { margin-top: 5px; margin-bottom: 5px; }
      #luncheon_choose_meal {visibility: hidden; text-align: center; border: 2px solid; position: absolute; 
        top: 50%;left: 50%; margin-top: -125px;  margin-left: -125px; width: 250px; height: 250px; background-color: white; padding: 5px; }
      #luncheon_choose_meal > h3 {margin-top: 7px; margin-bottom: 5px;  }
      
      .luncheon_suggestionsBox {
        position: relative;
        left: 30px;
        margin: 10px 0px 0px 0px;
        width: 200px;
        background-color: #212427;
        -moz-border-radius: 7px;
        -webkit-border-radius: 7px;
        border: 2px solid #000;	
        color: #fff;
      }
      
      .luncheon_suggestionList {
        margin: 0px;
        padding: 0px;
      }
      
      .luncheon_suggestionList li {
        margin: 0px 0px 3px 0px;
        padding: 3px;
        cursor: pointer;
      }
      
      .luncheon_suggestionList li:hover {
        background-color: #659CD8;
      }
      .luncheon_h2{
        color:#333333;
        background-color: white;
        font-family:Georgia,"Times New Roman",Times,serif;
        font-size:32px;
      }
    </style>';
}
add_action('admin_menu', 'luncheon_setup_adminmenu');
add_action('admin_head', 'luncheon_css');
 

// Make sure do proper install on activation
register_activation_hook(__FILE__, 'luncheon_db_install');


// Send requests for AJAX-backend through normal WP mechanisms
// thx to http://willnorris.com/2009/06/wordpress-plugin-pet-peeve-2-direct-calls-to-plugin-files
function luncheon_parse_request($wp) {
	// only process requests with "luncheon=ajax-handler"
	if (array_key_exists('luncheon', $wp->query_vars) && $wp->query_vars['luncheon'] == 'ajax-handler') {
	
		// ajaxian parameter must be present fo us to do anything at all
		if(isset($_POST['action'])){
		  $action = $_POST['action'];
		  global $wpdb;
		  
		  if ('add_meal' == $action) {
		    $mealDate = $wpdb->escape(trim(html_entity_decode($_POST['day'])));
		    $mealName = $wpdb->escape(trim(html_entity_decode($_POST['meal'])));
		    
		    // check if this meal is already present
		    $mealId = $wpdb->get_var("SELECT id FROM ". luncheon_dbtblname_meals() . " WHERE description='".$mealName."'");
		    if ('' == $mealId){
		      // did not find the meal, add it first
		      $wpdb->query("INSERT INTO ". luncheon_dbtblname_meals() . " (description) VALUES ('$mealName')");
		      $mealId = $wpdb->insert_id;
		    }
		    
		    // insert offering
		    $wpdb->query("INSERT INTO ". luncheon_dbtblname_offerings() . " (meal_id, menu_date) VALUES ('$mealId', '$mealDate')");
		    
		    $offerId = $wpdb->insert_id;
		    echo '<div id="luncheon_m_'.$offerId.'">'. $mealName . 
		          ' <a href="#" onclick="delete_meal(\''.$offerId.'\', \'' . $mealName . 
		          '\');"><img src="' . luncheon_getImageURL() . 
		          'x-red.gif" alt="Remove this item" /></a><br/></div>'."\n";
		    
		  } else if ('find_meal' == $action) {
		    $searchTerm = $wpdb->escape($_POST['term']);
		    
		    $meals = $wpdb->get_results("SELECT id, description
		      FROM ". luncheon_dbtblname_meals() . " 
		      WHERE description LIKE '$searchTerm%' 
		      ORDER BY description LIMIT 10");
		    
		    foreach ($meals as $meal) {
		      echo '<li onClick="luncheon_fill(\''.$meal->description.'\');">'.$meal->description.'</li>';
		    }
		  } else if ('delete_meal_offer' == $action) {
		    global $wpdb;
		    $offerId = $wpdb->escape($_POST['id']);
		    $wpdb->query("DELETE FROM ". luncheon_dbtblname_offerings() . " WHERE id = $offerId");
		    echo "OK";
		  } else {
		    echo 'Unknown action: ' . $action;
		  }
		} else {
		  wp_die('Luncheon: No action parameter specified!');
		}
		die();
	}
}
add_action('parse_request', 'luncheon_parse_request');

function luncheon_query_vars($vars) {
    $vars[] = 'luncheon';
    return $vars;
}
add_filter('query_vars', 'luncheon_query_vars');


?>