
$(document).ready(function(){

  // Load starting lcode
  main_lcode = findGetParameter("lcode");

  // Session login
  $("#login_window").css("display", "none");
  main_key = getCookie("main_key");
//  $("#login_window").css("display", "flex");
  if(main_key != "")
    get_login_session();
  else
    $("#login_window").css("display", "flex");


  // Handle login
  $("#login_confirm").click(function(){
    get_login();
  });
  $('#login_username').keypress(function(e){
    if(e.which == 13)
      $('#login_confirm').click();
  });
  $('#login_password').keypress(function(e){
    if(e.which == 13)
      $('#login_confirm').click();
  });

  $("#show_password").click(function(){
    if($(this).attr("data-value") == 1)
    {
      $(this).attr("data-value", 0)
      $(this).attr("src", "img/eye_closed.png")
      $("#login_password").attr("type", "password");
    }
    else {
      $(this).attr("data-value", 1);
      $(this).attr("src", "img/eye_opened.png")
      $("#login_password").attr("type", "text");
    }
  });


  // Property change
  $("#property_select").change(function(){
    main_lcode = $(this).val();
    get_basics();
    });

});


function get_login(){
  $("#login_confirm").addClass("button_loader"); // This will be removed by get basics
  $.ajax({
    url: api_link + 'account/login/',
    method: 'POST',
    data: {
      username: $("#login_username").val(),
      password: $("#login_password").val(),
      remember: $("#login_remember").attr("data-value")
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        $(".button_loader").removeClass("button_loader");
        $("#login_error").text("Pogrešan username ili password.");
        return;
      }
      // Save data
      main_key = sve.key;
      account_name = sve.account;
      user_name = sve.client_name;
      account_access = {};
      account_access.reservations = sve.reservations;
      account_access.guests = sve.guests;
      account_access.invoices = sve.invoices;
      account_access.prices = sve.prices;
      account_access.restrictions = sve.restrictions;
      account_access.avail = sve.avail;
      account_access.rooms = sve.rooms;
      account_access.channels = sve.channels;
      account_access.statistics = sve.statistics;
      account_access.changelog = sve.changelog;
      account_access.articles = sve.articles;
      account_access.wspay = sve.wspay;

      var properties = sve.properties;
      for(var i=0;i<properties.length;i++){
        properties_list.push(properties[i].lcode);
        properties_map[properties[i].lcode] = properties[i];
      }
      if(properties_map[main_lcode] == undefined){
        main_lcode = properties_list[0];
      }

      // Save session
      if($("#login_remember").attr("data-value") == 1){
        setCookie("main_key", main_key, 365);
      }
      else {
        setCookie("main_key", main_key);
      }

      display_user_info(sve); // This will call get_basics for property, because of property select2
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function get_login_session(){
  $.ajax({
    url: api_link + 'account/loginSession',
    method: 'POST',
    data: {
      key: main_key
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        $(".button_loader").removeClass("button_loader");
        $("#login_error").text("Sesija istekla.");
        return;
      }
      // Save data
      main_key = sve.key;
      account_name = sve.account;
      user_name = sve.client_name;
      account_access = {};
      account_access.reservations = sve.reservations;
      account_access.guests = sve.guests;
      account_access.invoices = sve.invoices;
      account_access.prices = sve.prices;
      account_access.restrictions = sve.restrictions;
      account_access.avail = sve.avail;
      account_access.rooms = sve.rooms;
      account_access.channels = sve.channels;
      account_access.statistics = sve.statistics;
      account_access.changelog = sve.changelog;
      account_access.articles = sve.articles;
      account_access.wspay = sve.wspay;

      var properties = sve.properties;
      for(var i=0;i<properties.length;i++){
        properties_list.push(properties[i].lcode);
        properties_map[properties[i].lcode] = properties[i];
      }
      if(properties_map[main_lcode] == undefined){
        main_lcode = properties_list[0];
      }
      display_user_info(sve); // This will call get_basics for property, because of property select2
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function display_user_info(data){
  disable_calls = true;

  // Account settings
  $("#account_undo_timer").val(data.undo_timer).change();
  $("#account_email").val(data.email);
  set_checkbox("account_notify_overbooking", data.notify_overbooking);
  set_checkbox("account_notify_reservations", data.notify_new_reservations);
  $("#account_invoice_delivery").val(data.invoice_delivery).change();
  $("#account_invoice_due").val(data.invoice_due).change();
  $("#account_invoice_margin").val(data.invoice_margin).change();
  set_checkbox("account_invoice_header", data.invoice_header);

  // Properties
  if(account_name == "IM043" || account_name == "ME001"){
    for(let i=0;i<properties_list.length;i++){
      $("#property_select").append(`<option value='${properties_list[i]}'> ${properties_map[properties_list[i]].name} - ${properties_map[properties_list[i]].account} </option>'`);
      $("#form_user_properties").append(create_checkbox('form_user_property_' + properties_map[properties_list[i]].lcode, 0, properties_map[properties_list[i]].name));
    }
  }
  else {
    for(let i=0;i<properties_list.length;i++){
      $("#property_select").append(`<option value='${properties_list[i]}'> ${properties_map[properties_list[i]].name} </option>'`);
      $("#form_user_properties").append(create_checkbox('form_user_property_' + properties_map[properties_list[i]].lcode, 0, properties_map[properties_list[i]].name));
    }
  }
  $("#property_select").val(main_lcode).change();

  // Initial dates
  $("#calendar_date").datepicker().data('datepicker').selectDate(new Date());
  $("#dFromOccu").datepicker().data('datepicker').selectDate(new Date());

  let stat_dfrom = relative_date(today, -365);
  $("#stat_dfrom").datepicker().data('datepicker').selectDate(new Date(stat_dfrom));
  $("#stat_dto").datepicker().data('datepicker').selectDate(new Date());
  $("#stat_dto").datepicker().data('datepicker').hide();
  get_statistics();

  // Rest Periods
  let dfrom1 = today;
  let dto1 = relative_date(dfrom1, 365);
  let dfrom2 = relative_date(today, 366);
  let dto2 = relative_date(dfrom2, 365);
  $("#rest_details_period").append(`<option value=1>${iso_to_eur(dfrom1)} - ${iso_to_eur(dto1)} </option>`)
  $("#rest_details_period").append(`<option value=2>${iso_to_eur(dfrom2)} - ${iso_to_eur(dto2)} </option>`)

  disable_calls = false;

  // Countries selects
  let country_list = Object.entries(iso_countries);
  for(let i=1;i<country_list.length;i++){
    $(".country_select").append(`<option value='${country_list[i][0]}'>${country_list[i][1]}</option>`);
  }

  // Articles
  if(account_access.articles < 3){
    $("#form_invoice_add_article").closest(".flex_center").remove();
    $("#settings_nav_articles").remove();
  }
  else {
    $.ajax({
    url: api_link + 'data/allArticles',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      for(var i=0;i<sve.all_articles.length;i++){
        all_articles_name_map[sve.all_articles[i].description] = sve.all_articles[i];
        all_articles_map[sve.all_articles[i].id] = sve.all_articles[i];
        $("#form_invoice_add_article").append(`<option value=${sve.all_articles[i].id}> ${sve.all_articles[i].description} </option>`)
      }
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
  }
}


function get_basics(){
  $.ajax({
    url: api_link + 'data/basics/',
    method: 'POST',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode
    },
    success: function(rezultat){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      // Get data
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      // Switch screens
      $("#login").remove();
      $("#app").show();
      // Fix account name for master accounts
      account_name = properties_map[main_lcode].account;

      // Clear data
      rooms_list = [];
      real_rooms_list = [];
      rooms_map = {};
      channels_list = [];
      channels_map = {};
      pricing_plans_list = [];
      pricing_plans_map = {};
      restriction_plans_list = [];
      restriction_plans_map = {};
      extras_list = [];
      extras_map = {};
      policies_list = [];
      policies_map = {};
      // Save data
      var rooms = sve.rooms;
      for(var i=0;i<rooms.length;i++){
        rooms_list.push(rooms[i].id);
        rooms_map[rooms[i].id] = rooms[i];
        if(rooms_map[rooms[i].id].parent_room == "0")
          real_rooms_list.push(rooms[i].id);
      }
      var channels = sve.channels;
      for(var i=0;i<channels.length;i++){
        channels_list.push(channels[i].id);
        channels_map[channels[i].id] = channels[i];
      }
      var pricing_plans = sve.pricing_plans;
      for(var i=0;i<pricing_plans.length;i++){
        pricing_plans_list.push(pricing_plans[i].id);
        pricing_plans_map[pricing_plans[i].id] = pricing_plans[i];
      }
      var restriction_plans = sve.restriction_plans;
      for(var i=0;i<restriction_plans.length;i++){
        restriction_plans_list.push(restriction_plans[i].id);
        restriction_plans_map[restriction_plans[i].id] = restriction_plans[i];
      }
      var extras = sve.extras;
      for(var i=0;i<extras.length;i++){
        extras_list.push(extras[i].id);
        extras_map[extras[i].id] = extras[i];
      }
      var policies = sve.policies;
      for(var i=0;i<policies.length;i++){
        policies_list.push(policies[i].id);
        policies_map[policies[i].id] = policies[i];
      }
      display_property_info(sve);
      // Show screen
      let old_hash = window.location.hash == "" ? "home" : window.location.hash;
      window.location.hash = "";
      window.location.hash = old_hash;
    },
    error: function(xhr, textStatus, errorThrown){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}


function display_property_info(data){
  disable_calls = true;

  // Property data
  let property = properties_map[main_lcode];
  if(property.logo == ""){
    $("#property_logo_container").hide();
    $("#property_logo").show();
  }
  else {
    $("#property_logo").hide();
    $("#property_logo_container img").attr("src", property.logo);
    $("#property_logo_container").show();
  }
  $("#property_name").val(property.name);
  $("#property_pib").val(property.pib);
  $("#property_mb").val(property.mb);
  $("#property_address").val(property.address);
  $("#property_bank_account").val(property.bank_account);
  $("#property_iban").val(property.iban);
  $("#property_swift").val(property.swift);
  $("#default_price").val(property.default_price).change();
  set_checkbox("property_pdv_included", property.pdv_included);
  set_checkbox("property_rooms_tax_included", property.rooms_tax_included);
  if(property.rooms_tax_included == 1){
    $("#property_rooms_tax_containter").show();
  }
  else {
    $("#property_rooms_tax_containter").hide();
  }
  $("#property_rooms_tax").val(property.rooms_tax);
  set_checkbox("property_notify_guests", property.notify_guests);

  $("#wubook_ids").text(property.account + " - " + property.lcode);

  for(let i=0;i<property.planned_earnings.length;i++){
    $("#stats_plan_" + i).val(property.planned_earnings[i]);
  }

  // Emails
  let guest_emails = data.guest_emails;
  $("#guest_emails_res_type").val(guest_emails.res_type).change();
  $("#guest_emails_received_active").val(guest_emails.received_active).change();
  $("#guest_emails_received_subject").val(guest_emails.received_subject);
  $("#guest_emails_received_edit").html(guest_emails.received_text);
  $("#guest_emails_before_active").val(guest_emails.before_active).change();
  $("#guest_emails_before_subject").val(guest_emails.before_subject);
  $("#guest_emails_before_edit").html(guest_emails.before_text);
  $("#guest_emails_after_active").val(guest_emails.after_active).change();
  $("#guest_emails_after_subject").val(guest_emails.after_subject);
  $("#guest_emails_after_edit").html(guest_emails.after_text);

  let client_emails = data.client_emails;
  set_checkbox("client_emails_active", client_emails.active);
  $("#client_emails_emails").val(client_emails.emails);
  set_checkbox("client_emails_arrivals", client_emails.arrivals);
  set_checkbox("client_emails_departures", client_emails.departures);
  set_checkbox("client_emails_stay", client_emails.stay);
  $("#client_emails_time").val(client_emails.hour).change();
  set_checkbox("client_emails_tomorrow", client_emails.tomorrow);

  $("#new_invoice").click();

  // Lists

  display_rooms();

  display_channels();

  display_pricing_plans();

  disable_calls = true;

  display_restriction_plans();

  display_extras();

  display_policies();

  // Calendar filters
  let filters = property.custom_calendar;
  $("#cal_filter_type").val(filters.type).change();
  set_checkbox("cal_filter_avail", filters.avail);
  set_checkbox("cal_filter_price", filters.price);
  set_checkbox("cal_filter_min", filters.min);
  set_checkbox("cal_filter_margin", filters.margin);
  set_checkbox("cal_filter_room_name", filters.room_name);
  set_checkbox("cal_filter_room_type", filters.room_type);
  set_checkbox("cal_filter_room_status", filters.room_status);
  $(`[id^='cal_filter_days'] .radio_value`).removeClass("checked");
  $("#cal_filter_days_"+filters.days).find(".radio_value").addClass("checked");
  $(`#cal_filter_days`).val(filters.days);
  $("#cal_filter_rooms").val(-1).change();
  for(let i=0;i<filters.room_types.length;i++){
    let room_id = filters.room_types[i];
    let shortname = rooms_map[room_id].shortname;
    if($("#cal_filter_room_" + room_id).length == 0){
      $("#cal_filter_rooms_types_list").append(`
        <div class='cal_filter_room' id='cal_filter_room_${room_id}' data-value='${room_id}'>
          <img class='list_action_icon delete'> ${shortname}
        </div>`);
    }
  }
  $("#cal_filter_rooms_single").val(-1).change();
  $("#cal_filter_rooms_types_list").empty();
  $("#cal_filter_rooms_single_list").empty();
  for(let i=0;i<filters.single_rooms.length;i++){
    let room_id = filters.single_rooms[i].split("_")[0];
    let room = rooms_map[room_id];
    let shortname = room.shortname;
    let room_number_id = filters.single_rooms[i].split("_")[1];
    let room_number = room.room_numbers[room_number_id];
    if($(`#cal_filter_room_${room_id}_${room_number_id}`).length == 0){
      $("#cal_filter_rooms_single_list").append(`
        <div class='cal_filter_room' id='cal_filter_room_${room_id}_${room_number_id}' data-value='${room_id}_${room_number_id}'>
          <img class='list_action_icon delete'> ${room_number} (${shortname})
        </div>`);
    }
  }

  disable_calls = false;

  showEngineData();

  $("#engine_link").remove();
  $("#tab_engine").prepend(`<a id='engine_link' href='https://engine.otasync.me/?${main_lcode}' target='_blank'> Pregled engine-a </a>`)

  $("#settings_articles_id").val(property.articles_id);
  $("#settings_articles_course").val(property.currency_course);

  $('#reservations_filter_price').jRange('updateRange', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#reservations_filter_nights').jRange('updateRange', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#reservations_filter_price').jRange('setValue', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#reservations_filter_nights').jRange('setValue', 0 + ',' + Number.MAX_SAFE_INTEGER);

  $('#guests_filter_price').jRange('updateRange', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#guests_filter_nights').jRange('updateRange', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#guests_filter_price').jRange('setValue', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#guests_filter_nights').jRange('setValue', 0 + ',' + Number.MAX_SAFE_INTEGER);

  let res = findGetParameter("id");
  if(res != null){
    $.ajax({
      type: 'POST',
      url: api_link + 'data/reservation',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        reservation_code: res
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        all_reservations[sve.reservation.reservation_code] = sve.reservation;
        open_res_info(sve.reservation);
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  }
}
