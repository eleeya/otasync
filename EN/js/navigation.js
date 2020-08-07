var active_tabs =
{
  home: "home",
  calendar: "calendar",
  reservations: "reservations",
  guests: "guests",
  invoices: "invoices",
  statistics: "statistics",
  reports: "occupancy",
  settings: "general",
  finances: "finances",
  changelog: "changelog"
};

const main_ids =
{
  home: 'home',
  calendar: 'calendar',
  restrictions: 'calendar',
  yield: 'calendar',
  reservations: 'reservations',
  guests: 'guests',
  invoices: 'invoices',
  statistics: 'statistics',
  occupancy: 'reports',
  daily: 'reports',
  housekeeping: 'reports',
  articles: 'settings',
  engine: 'settings',
  general: 'settings',
  emails: 'settings',
  rooms: 'settings',
  plans: 'settings',
  channels: 'settings',
  users: 'settings',
  finances: 'finances',
  changelog: 'changelog'
}

var filter_open = false;
// Filter open is used to trigger hash change when closing filters (reloads data)

function hash_change(){

      hide_modals();

      $("html, body").css("overflow", "");
      $(".form_container").hide();
      // Get tab
      var tab = window.location.hash.substring(1);

      if(tab == "")
        tab = "home";
      // Hide previous state
      $("main").hide();
      $(".tab").hide();
      $(".tab_nav").hide();
      // Show new state
      $("#tab_" + tab).closest("main").show();
      $("#tab_" + tab).show();
      $("#tab_nav_" + tab).closest(".tab_nav").css("display", "flex");
      // Show tab nav selected
      $(".tab_nav.selected").removeClass("selected");
      $("#tab_nav_" + tab).addClass("selected");
      // Show nav selected
      $(".nav_item").removeClass("nav_selected");
      $("#nav_" + main_ids[tab]).addClass("nav_selected");

      if(main_ids[tab] == "settings"){
        $("body").addClass("settings_active");
      }
      else {
        $("body").removeClass("settings_active");
      }

      // Handle each tab
      if(tab == "home"){
        get_news();
        get_events();
      }
      if(tab == "calendar"){
        get_calendar();
      }
      if(tab == "restrictions"){
        $("#rest_work_on").change(); // Calling this instead of get_rest_details to not trigger call when no value is selected
      }
      if(tab == "reservations"){
        reservations_page = 1;
        get_reservations();
      }
      if(tab == "guests"){
        guests_page = 1;
        get_guests();
      }
      if(tab == "invoices"){
        invoices_page = 1;
        get_invoices();
      }
      if(tab == "statistics"){
        get_statistics();
      }
      if(tab == "occupancy"){
        occupancy();
      }
      if(tab == "daily"){
        daily();
      }
      if(tab == "housekeeping"){
        housekeeping();
      }
      if(tab == "engine"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
      }
      if(tab == "articles"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
        show_articles();
      }
      if(tab == "general"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
      }
      if(tab == "emails"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
      }
      if(tab == "plans"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
      }
      if(tab == "rooms"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
      }
      if(tab == "users"){
        $(".settings_nav_button").removeClass("selected");
        $("#settings_nav_" + tab).addClass("selected");
        get_users();
      }
      if(tab == "finances"){
      }
      if(tab == "changelog"){
        changelog_page = 1;
        get_changelog();
      }
}

$(document).ready(function(){

// Triggers tab change
$(window).on('hashchange', hash_change);

// Closes open modals on background click
$("#click_to_hide").click(click_to_hide);


// Main nav
$(".nav_item, .menu_item").click(function(e){

  // Get id of required main
  let main_id = $(this)[0].id.split("_")[1];

  if(main_id == "logout"){
    filter_open = false;
    $.ajax({
      url: api_link + 'account/logout',
      method: 'POST',
      data: {
              key: main_key,
              account: account_name
            },
      success: function(rezultat){
        setCookie("main_key", "", -1);
        window.location.reload();
      },
      error: function(rezultat){
        setCookie("main_key", "", -1);
        window.location.reload();
      }
    });
    return;
  }
  else {
    hide_modals();
    // Get active tab of main
    let tab = active_tabs[main_id];
    // Change hash
    window.location.hash = tab;
  }
});

// Tab nav
$(".tab_nav_button").click(function(){
  let tab = $(this)[0].id.split("_")[2];
  $(this).closest(".tab_nav").find(".tab_nav_button").removeClass("selected");
  $(this).addClass("selected");
  let main_id = main_ids[tab];
  active_tabs[main_id] = tab;
  window.location.hash = tab;
});

// Settings nav
$(".settings_nav_button").click(function(){
  let tab = $(this)[0].id.split("_")[2];
  active_tabs["settings"] = tab;
  window.location.hash = tab;
});


// Modals
$(".modal_container").click(function(){
  if($(this).hasClass("active")){
    hide_modals();
  }
  else {
    $("#hide_modals").show();
    $(this).addClass("active");
    if($(this).has(".filter_modal")) // Flag filter_open
      filter_open = true;
  }
});
$(".modal").click(function(e){
  e.stopPropagation();
});
$("#hide_modals").click(hide_modals);

// Load next page when scrolling to bottom
$(window).scroll(function() {
  if($(window).scrollTop() + $(window).height() == $(document).height()) {
     let tab = window.location.hash.substring(1);
     if(tab == "reservations" && reservations_page < total_reservations_pages){
       reservations_page++;
       get_reservations();
     }
     if(tab == "guests" && guests_page < total_guests_pages){
       guests_page++;
       get_guests();
     }
     if(tab == "invoices" && invoices_page < total_invoices_pages && invoices_page > 0){
       invoices_page++;
       get_invoices();
     }
     if(tab == "changelog" && changelog_page < total_changelog_pages && changelog_page > 0){
       changelog_page++;
       get_changelog();
     }
  }
});

});

function disable_scroll(){
  $("html, body").css("overflow", "hidden");
}
var scroll_lock = function() {
  scroll = $("html").scrollTop() > $("body").scrollTop() ? $("html").scrollTop() : $("body").scrollTop();
  $(".form_container").css("top", scroll+"px");
  $("#dialog_container").css("top", scroll+"px");
  $(".info").css("top", scroll+"px");
  $(".form_container").scrollTop(0);
  $("html, body").css("overflow", "hidden");
}

function hide_modals(){
  $("#hide_modals").hide();
  $(".modal_container.active").removeClass("active");
  $("html, body").css("overflow", "");
  if(filter_open){
    filter_open = false;
    hash_change();
  }
}

var click_to_hide = function(){
  $("#click_to_hide").hide();
  $("#click_to_hide").css("background-color", "");
  $("#user_menu").hide();
  $("#search_list").hide();
  $(".filter_container").hide();
  $(".filter_button").css("z-index", "");
  $("#cal_modal").hide();
  $("#color_modal").hide();
  $("html, body").css("overflow", "");
  $(".info").remove();
};
