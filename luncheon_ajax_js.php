function luncheon_choose_date(){
  var loc = (location+"");
  var re = new RegExp("&lu_week=.*");
  if (loc!='' && re.test(loc)){
    loc = loc.replace(re,'');
  }
  location = loc + "&lu_week=" + jQuery("#lu_week").val() + "&lu_year=" + jQuery("#lu_year").val();
}

jQuery(function(){
  jQuery("#lu_week").change(function(){
    luncheon_choose_date();
  })
  jQuery("#lu_year").change(function(){
    luncheon_choose_date();
  })
})

var LUNCHEON_LOADING_HTML = "<br/><br/>Loading content...<br/><br/><img src='blue_loader.gif' alt='Loading content...' />";
var LUNCHEON_AJAX_URL = "../index.php?luncheon=ajax-handler"; // TODO Make more robust

function cancel_meal_add() {
  jQuery("#luncheon_choose_meal").css('visibility', 'hidden');
  jQuery('#inputString').val('');
}

function add_meal(mealDate) {
  jQuery("#luncheon_choose_meal_date").html(mealDate);
  jQuery('#inputString').focus();
  jQuery("#luncheon_choose_meal").css('visibility', 'visible');
}

function do_add_meal() {
  var date = jQuery("#luncheon_choose_meal_date").html();
  var meal = jQuery('#inputString').val();
  //alert('saving meal '+meal+' for date: '+date);
  
  jQuery("#luncheon_choose_meal").css('visibility', 'hidden');
  jQuery('#inputString').val('');
  
  jQuery.ajax({
    type: "POST", url: LUNCHEON_AJAX_URL,
    data: "action=add_meal&day=" + date+"&meal="+meal,
    success: function(msg) {
      jQuery("#day_"+date).html(jQuery("#day_"+date).html()+msg);
    }
  });
}

function delete_meal(mealOfferId, mealText) {
  if (confirm("Vill du plocka bort \"" + mealText + "\" fr\u00E5n menyn?")){
    
    jQuery.ajax({
      type: "POST", url: LUNCHEON_AJAX_URL,
      data: "action=delete_meal_offer&id=" + mealOfferId,
      success: function(msg) {
        if ('OK' == msg){ 
          jQuery('#luncheon_m_'+mealOfferId).hide();
        } else {
          alert('Problem med databasen: ' + msg);
        }
      }
    });
  }
}

function luncheon_lookup(inputString) {
  if(inputString.length == 0) {
    // Hide the suggestion box.
    jQuery('#luncheon_suggestions').hide();
  } else {
    jQuery.ajax({
      type: "POST", url: LUNCHEON_AJAX_URL,
      data: "action=find_meal&term=" + inputString,
      success: function(msg) {
        if (msg.length > 0){ 
          jQuery('#luncheon_suggestions').show();
          jQuery('#luncheon_autoSuggestionsList').html(msg);
        } else {
          jQuery('#luncheon_suggestions').hide();
        }
      }
    });
  }
} // lookup

function luncheon_fill(thisValue) {
  jQuery('#inputString').val(thisValue);
  setTimeout("jQuery('#luncheon_suggestions').hide();", 200);
}
