let wspay_active = 0;

$(document).ready(function(){

  $("body").on("click", ".reservation", function(e){
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    var res = all_reservations[id];
    open_res_info(res);
  });

  $("body").on("click", ".guest", function(e){
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    open_guest_info(id);
  });

  $("body").on("click", "#res_info_export", function(e){
    $("#one_res_export_form").submit();
  });

  $("body").on("change", "#res_info_guest_status", function(){
    let val = $("#res_info_guest_status").val();
    let bg_color = "#306bad"; // Default color
    if(val == "waiting_arrival"){
      bg_color =  "#f0535a";
    }
    else if(val == "arrived"){
      bg_color = "#c6a43d";
    }
    else if(val == "arrived_and_paid"){
      bg_color =  "#0DC85E";
    }
    else if(val == "left"){
      bg_color = "#282828";
    }
    $("#select2-res_info_guest_status-container").closest(".select2-selection.select2-selection--single").css("background-color", bg_color);
    $("#select2-res_info_guest_status-container").closest(".select2-selection.select2-selection--single").css("border-bottom", "0px");
    if(disable_calls)
      return;
    id = $("#res_info_invoice").attr("data-value");
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/reservationGuestStatus',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id,
        guest_status: val
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Izmjena sačuvana`, sve.data.id); // Add changelog
        get_calendar();
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });

  $("body").on("click", ".res_info_cancel", click_to_hide);

  $("body").on("click", "#res_info_note_show", function(){
    $("#res_info_note_short").hide();
    $("#res_info_note_show").hide();
    $("#res_info_note_full").show();
  });
  $("body").on("click", "#res_info_other_guests", function(){
    $(".res_info_guest > div").css("display", "");
    $("#res_info_other_guests").hide();
  });

  $("body").on("click", ".res_info_linked_res", function(){
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    if(all_reservations[id] == undefined){
      $.ajax({
        url: api_link + 'data/reservation',
        method: 'POST',
        data: {
                key: main_key,
                account: account_name,
                lcode: main_lcode,
                reservation_code: id
              },
        success: function(rezultat){
          var sve = check_json(rezultat);
          if(sve.status !== "ok") {
            add_change_error(sve.status);
            return;
          }
          all_reservations[id] = sve.reservation;
          open_res_info(all_reservations[id]);
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
      });
    }
    else {
      open_res_info(all_reservations[id]);
    }
  });

  $("body").on("click", ".display_cc", function(e){
    var id = e.target.id.split("_");
    id = id[id.length - 1];
    var cc_pass = $("#cc_pass").val();
    $(".cc_info").after(`<div class='cc_loader'><div class='cc_loader_icon'></div></div>`);
    $(".display_cc").addClass("button_loader");
    $.ajax({
      url: api_link + 'data/showCC',
      method: 'POST',
      data: {
              key: main_key,
              lcode: main_lcode,
              account: account_name,
              rcode: id,
              cc_pass: cc_pass
            },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        $(".cc_loader").remove();
        $(".cc_data").remove();
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        sve = sve.data;
        if(sve.cc_number == undefined){
          $(".cc_info").after(`<div class='cc_data'> Pogrešna šifra.</div>`);
            return;
        }
        $("#one_res_export_cc").val(JSON.stringify(sve));
        $(".cc_info").after(`
          <div class='cc_data'>
            <div class='cc_data_label'>
              Broj:
            </div>
            <div class='cc_data_val'>
              ${sve.cc_number}
            </div>
          </div>
          <div class='cc_data'>
            <div class='cc_data_label'>
            CVV:
            </div>
            <div class='cc_data_val'>
              ${sve.cc_cvv}
            </div>
          </div>
          <div class='cc_data'>
            <div class='cc_data_label'>
            Vlasnik:
            </div>
            <div class='cc_data_val'>
              ${sve.cc_owner}
            </div>
          </div>
          <div class='cc_data'>
            <div class='cc_data_label'>
            Važi do:
            </div>
            <div class='cc_data_val'>
              ${sve.cc_expiring}
            </div>
          </div>`);
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        $(".cc_loader").remove();
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });

  // Noshow and Invalid cc
  $("body").on("click", "#res_info_noshow", function(){
    let id = $(this).attr("data-value");
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/noshow',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        add_change_settings();
        click_to_hide();
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  $("body").on("click", "#res_info_invalidcc", function(){
    let id = $(this).attr("data-value");
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/invalidcc',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        add_change_settings();
        click_to_hide();
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
});

function open_res_info(res){
  click_to_hide();
  // Basics

  var dfrom = iso_to_eur(res.date_arrival);
  var dto = iso_to_eur(res.date_departure);
  var guests = parseInt(res.men) + parseInt(res.children);
  var nights = parseInt(res.nights);
  var night_price = res.reservation_price / nights;
  var rooms = res.room_data;
  var rooms_html = "";
  for(var i=0;i<rooms.length;i++)
  {
    let room_numbers = [];
    for(let j=0;j<rooms[i].room_numbers.length;j++){
      let parent_room = rooms_map[rooms[i].parent_id];
      if(parent_room == undefined)
        parent_room = rooms_map[rooms[i].id];
      room_numbers.push(parent_room.room_numbers[rooms[i].room_numbers[j]]);
    }
    rooms_html = rooms_html + `<div class='res_info_room'>${rooms[i].count}x ${rooms[i].name} (${room_numbers.join(", ")})</div>`;
  }
  var services_html = "";
  for(var i=0;i<res.services.length;i++)
  {
    services_html = services_html + `<div class='res_info_room'>${res.services[i].amount}x ${res.services[i].name}</div>`;
  }
  if(res.services.length){
    services_html = `
    <div class='res_info_item'>
      <div class='res_info_tooltip_container'>
        <img class='res_info_icon' src='img/price_tag_white.svg'>
        <div class='res_info_tooltip bigger'> Dodatne usluge </div>
      </div>
      <div class='res_info_text'> ${services_html} </div>
    </div>`;
  }
  // Header
  if(channels_map[res.id_woodoo] !== undefined)
    var channel_html = `<div class='res_info_channel' title='${channels_map[res.id_woodoo].name}'> <img src='${channels_map[res.id_woodoo].logo}'> </div>`;
  else
    var channel_html = `<div class='res_info_channel' title='Direktna rezervacija'> <img src='https://admin.otasync.me/img/ota/youbook.png'> </div>`;
  var linked_reservation_html = "";
  if(res.status == 1)
  {
    var status_html = `<div class='res_info_confirmed'> Potvrđena </div>`;
    if(res.modified_reservation != "")
    {
      linked_reservation_html = `
      <div class='res_info_linked_res' id='res_info_linked_res_${res.modified_reservation}'>
        Stara rezervacija ${res.modified_reservation}
      </div>`;
    }
  }
  else if(res.status == 5 && res.was_modified == 1)
  {
    var status_html = `<div class='res_info_modified'> Izmjenjena </div>`;
    if(res.new_reservation_code != "")
    {
      linked_reservation_html = `
      <div class='res_info_linked_res' id='res_info_linked_res_${res.new_reservation_code}'>
        Nova rezervacija ${res.new_reservation_code}
      </div>`;
    }
  }
  else if(res.status == 5){
    var status_html = `<div class='res_info_canceled'> Otkazana </div>`;
    if(res.modified_reservation != "")
    {
      linked_reservation_html = `
      <div class='res_info_linked_res' id='res_info_linked_res_${res.modified_reservation}'>
        Stara rezervacija ${res.modified_reservation}
      </div>`;
    }
  }
  var note_html = "";
  if(res.note != "")
  {
    if(res.note.length > 200)
    {
      note_html = `<div class='res_info_note'><div id='res_info_note_full'> ${res.note} </div> <div id='res_info_note_short'> ${res.note.substring(0,180)}... </div>  </div> <div id='res_info_note_show'> PRIKAŽI SVE </div>`;
    }
    else {
      note_html = `<div class='res_info_note'> ${res.note} </div>`;
    }
  }

  // Guest

  var country_html = "";
  let guest_html = `<div> ${res.customer_name} ${res.customer_surname} </div>`;
  if(res.guests.length > 0){
      if(res.guests[0].country_of_residence !== "--")
        country_html = `<img class='country_flag' title='${iso_countries[res.guests[0].country_of_residence]}' src='https://www.countryflags.io/${res.guests[0].country_of_residence}/shiny/16.png'>`;

      let guest_html = `<div onclick='open_guest_info(${res.guests[0].id})'> ${res.guests[0].name} ${res.guests[0].surname} ${country_html} </div>`;

      for(let i=1;i<res.guests.length;i++){
        guest_html+= `<div class='res_info_gray' onclick='open_guest_info(${res.guests[i].id})' style='display:none'> ${res.guests[i].name} ${res.guests[i].surname} </div>`;
      }
      if(res.guests.length > 1){
        guest_html += `<div id='res_info_other_guests' class='res_info_gray'> PRIKAŽI SVE </div>`;
      }
  }
  for(let i=0;i<res.guests.length;i++){
    guests_map[res.guests[i].id] = res.guests[i];
  }

  var email_html = "";
  var phone_html = "";
  if(res.customer_mail !== "")
    email_html = `
    <div class='res_info_item'>
      <div class='res_info_tooltip_container'>
        <img class='res_info_icon' src='img/email_white.svg'>
        <div class='res_info_tooltip'> Email </div>
      </div>
      <div class='res_info_text'> ${res.customer_mail} </div>
    </div>`;
  if(res.customer_phone !== "")
    phone_html = `
    <div class='res_info_item'>
      <div class='res_info_tooltip_container'>
        <img class='res_info_icon' src='img/phone_white.svg'>
        <div class='res_info_tooltip'> Telefon </div>
      </div>
      <div class='res_info_text'> ${res.customer_phone} </div>
    </div>`;

  var link_html = "";
  var booking_html = "";
  if(channels_map[res.id_woodoo] !== undefined)
  {
    if(channels_map[res.id_woodoo].ctype == 43) //AirBnB
    {
      var link_url =  `https://www.airbnb.com/z/q/${res.channel_reservation_code}`;
      link_html =
      `
      <div class='info_section'>
        <div class='info_center'>
          <a href='${link_url}' target='_blank' class='res_info_link'> Pregled rezervacije na sajtu kanala </a>
        </div>
      </div>
      `
      ;
    }
    else if(channels_map[res.id_woodoo].ctype == 1) //Expedia
    {
      var link_url = `https://apps.expediapartnercentral.com/lodging/reservations/reservationDetails.html?htid=${channels_map[res.id_woodoo].hotel_id}&reservationIds=${res.channel_reservation_code}`;
      link_html =
      `
      <div class='info_section'>
        <div class='info_center'>
          <a href='${link_url}' target='_blank' class='res_info_link'> Pregled rezervacije na sajtu kanala </a>
        </div>
      </div>
      `
      ;
    }
    else if(channels_map[res.id_woodoo].ctype == 2) //Booking
    {
      var link_url = `https://admin.booking.com/hotel/hoteladmin/extranet_ng/manage/booking.html?res_id=${res.channel_reservation_code}&hotel_id=${channels_map[res.id_woodoo].hotel_id}`;
      link_html =
      `
      <div class='info_section'>
        <div class='info_center'>
          <a href='${link_url}' target='_blank' class='res_info_link'> Pregled rezervacije na sajtu kanala  </a>
        </div>
      </div>
      `
      ;
      booking_html = `
      <div class='booking_flex'>
        <button class='update_button no_show' id='no_show_${res.reservation_code}'>No show</button>
        <button class='update_button invalid_cc' id='invalid_cc_${res.reservation_code}'>Nevalidna kartica</button>
      </div>
      `;
    }
  }

  var cc_html = "";
  if(res.cc_info == "1")
  {
    if(wspay_active == 1)
    {
      cc_html =
      `
      <div class='info_center cc_info'>
        <input type='password' id='cc_pass' class='form_input' placeholder='Šifra za karticu'>
        <input type='number' id='cc_payment_amount' class='number_input' placeholder='IZNOS'>
        <button class='update_button make_cc_payment' id='cc_make_payment_${res.reservation_code}'>NAPLATI</button>
      </div>
      `;
    }
    else {
      cc_html =
      `
      <div class='flex_around cc_info'>
        <input type='password' id='cc_pass' class='text_input' placeholder='Šifra za karticu'>
        <button class='confirm_button display_cc' id='display_cc_${res.reservation_code}'>PRIKAŽI</button>
      </div>
      `;
    }

  }
  var buttons_html = "";
  var export_html = `
  <form action="https://export.otasync.me/reservation/" class='export_form' target="_blank" method="POST" enctype='multipart/form-data' id='one_res_export_form'>
    <input type="text" name="key" value="${main_key}">
    <input type="text" name="account" value="${account_name}">
    <input type="text" name="lcode" value="${main_lcode}">
    <input type="text" name="id" value="${res.reservation_code}">
    <textarea name="cc_data" id='one_res_export_cc'></textarea>
    <input type="submit" value="Submit" id='res_export_submit'>
  </form>
  `;
  $("#click_to_hide").show();
  $("#click_to_hide").css("background-color", "rgba(0,0,0,0.3)");

  let guest_status_html = "";
  if(res.status == 1)
  {
    guest_status_html = `<select class='basic_select' id='res_info_guest_status'>
      <option value='waiting_arrival'> Čeka se dolazak </option>
      <option value='arrived'> Gost došao </option>
      <option value='arrived_and_paid'> Gost došao i platio </option>
      <option value='left'> Gost se odjavio </option>
    </select>`;
  }

  $("body").append(`
    <div class='info' id='res_info'>
      <div class='flex_between' id='res_info_header'>
        ${channel_html}
        <div class='res_info_guest'>
          ${guest_html}
        </div>
        <div class='res_info_id res_info_gray'>
          <b>ID: </b> ${res.reservation_code}
        </div>
        ${status_html}
        ${guest_status_html}
        ${linked_reservation_html}
        <img class='res_info_cancel'>
      </div>
      <div class='flex_around'>
        <div class='res_info_section'>
          <div class='res_info_item'>
          <div class='res_info_tooltip_container'>
            <img class='res_info_icon' src='img/date_time_white.svg'>
            <div class='res_info_tooltip big'> Datum/Vreme </div>
          </div>
            <div class='res_info_text'> ${iso_to_eur(res.date_received)} <span class='res_info_gray'> ${res.time_received} </span> </div>
          </div>
          <div class='res_info_item'>
            <div class='res_info_tooltip_container'>
              <img class='res_info_icon' src='img/calendar_white.svg'>
              <div class='res_info_tooltip bigger'> Period rezervacije </div>
            </div>
            <div class='res_info_text'> ${dfrom} - ${dto} </div>
          </div>
          <div class='flex_start'>
            <div class='res_info_item'>
              <div class='res_info_tooltip_container'>
                <img class='res_info_icon' src='img/night_white.svg'>
                <div class='res_info_tooltip big'> Broj noćenja </div>
              </div>
              <div class='res_info_text'> ${nights} </div>
            </div>
            <div class='res_info_item'>
              <div class='res_info_tooltip_container'>
                <img class='res_info_icon' src='img/price_tag_white.svg'>
                <div class='res_info_tooltip bigger'> Cijena po noćenju </div>
              </div>
              <div class='res_info_text'> ${night_price.toFixed(2)} EUR p/n </div>
            </div>
          </div>
          <div class='flex_start'>
            <div class='res_info_item'>
              <div class='res_info_tooltip_container'>
                <img class='res_info_icon' src='img/people_white.svg'>
                <div class='res_info_tooltip'> Broj gostiju </div>
              </div>
              <div class='res_info_text'> ${guests} </div>
            </div>
            <div class='res_info_item'>
              <div class='res_info_tooltip_container'>
                <img class='res_info_icon' src='img/person_white.svg'>
                <div class='res_info_tooltip big'> Broj odraslih </div>
              </div>
              <div class='res_info_text'> ${res.men} </div>
            </div>
            <div class='res_info_item'>
              <div class='res_info_tooltip_container'>
                <img class='res_info_icon' src='img/baby_white.svg'>
                <div class='res_info_tooltip'> Broj dece </div>
              </div>
              <div class='res_info_text'> ${res.children} </div>
            </div>
          </div>
          ${email_html}
          ${phone_html}
        </div>
        <div class='res_info_section'>
          <div class='res_info_item'>
            <div class='res_info_tooltip_container'>
              <img class='res_info_icon' src='img/bed_white.svg'>
              <div class='res_info_tooltip biggest'> Rezervisane jedinice </div>
            </div>
            <div class='res_info_text'> ${rooms_html} </div>
          </div>
          ${services_html}
          <div class='res_info_item'>
            <div class='res_info_tooltip_container'>
              <img class='res_info_icon' src='img/check_white.svg'>
              <div class='res_info_tooltip big'> Ukupna cijena </div>
            </div>
            <div class='res_info_text'> ${parseFloat(res.total_price).toFixed(2)} EUR </div>
          </div>
          ${note_html}
          ${cc_html}
        </div>
      </div>
      ${export_html}
      <div class='res_info_buttons'>
        <button class='confirm_button' id='res_info_noshow' data-value='${res.reservation_code}'> No Show </button>
        <button class='confirm_button' id='res_info_invalidcc' data-value='${res.reservation_code}'> Nevalidna kartica </button>
        <div class='left_break'> </div>
        <div class='res_info_tooltip_container res_info_delete' id='res_info_delete_${res.reservation_code}' data-value='${res.reservation_code}'>
          <img class='res_info_button' src='img/info_trash.svg'>
          <div class='res_info_tooltip'> Obriši </div>
        </div>
        <div class='res_info_tooltip_container'>
          <img class='res_info_button' id='res_info_export' src='img/export_white.svg'>
          <div class='res_info_tooltip'> Štampa </div>
        </div>
        <div class='res_info_tooltip_container'>
          <img class='res_info_button' id='res_info_invoice' data-value='${res.reservation_code}' src='img/invoice_white.svg'>
          <div class='res_info_tooltip'> Račun </div>
        </div>
        <button class='confirm_button' id='res_info_edit'> Izmjeni </button>
      </div>
    </div>
    `);
  var scroll = $("html").scrollTop() > $("body").scrollTop() ? $("html").scrollTop() : $("body").scrollTop();
  $(".info").css("top", scroll+"px");

  $("#res_info_guest_status").select2({
      minimumResultsForSearch: Infinity,
      width: "element"
  });
  disable_calls = true;
  $("#res_info_guest_status").val(res.guest_status).change();
  disable_calls = false;

  if(cmp_dates(res.date_departure, today) < 0){
    $("#res_info .select2").css("opacity", "0.5");
    $("#res_info .select2").css("pointer-events", "none");
  }
  if(res.id_woodoo != "-1" && res.id_woodoo != "" && res.id_woodoo != "-2")
    $(".res_info_delete").remove();
}

function open_guest_info(guest_id)
{
  click_to_hide();
  let guest = guests_map[guest_id];
  var country_html = "";
  if(guest.country_of_residence !== "--")
    country_html = `<img class='country_flag' title='${iso_countries[guest.country_of_residence]}' src='https://www.countryflags.io/${guest.country_of_residence}/shiny/16.png'>`;
  var note_html = "";
  if(guest.note != "")
  {
    if(guest.note.length > 200)
    {
      note_html = `<div class='res_info_note'><div id='res_info_note_full'> ${guest.note} </div> <div id='res_info_note_short'> ${res.note.substring(0,180)}... </div>  </div> <div id='res_info_note_show'> PRIKAŽI SVE </div>`;
    }
    else {
      note_html = `<div class='res_info_note'> ${guest.note} </div>`;
    }
  }

  $("#click_to_hide").show();
  $("#click_to_hide").css("background-color", "rgba(0,0,0,0.3)");
  $("body").append(`
    <div class='info' id='guest_info'>
      <div class='flex_center'>
        <div class='res_info_guest'>
          ${guest.name} ${guest.surname} ${country_html}
        </div>
      </div>
      <div class='flex_around'>
        <div class='res_info_section'>
          <div class='res_info_item'>
            <img class='res_info_icon' src='img/email_white.svg'>
            <div class='res_info_text'> ${guest.email} </div>
          </div>
          <div class='res_info_item'>
            <img class='res_info_icon' src='img/phone_white.svg'>
            <div class='res_info_text'> ${guest.phone} </div>
          </div>
        </div>
        <div class='res_info_section'>
          <div class='res_info_item'>
            <img class='res_info_icon' src='img/bed_white.svg'>
            <div class='res_info_text'> ${guest.total_arrivals} dolazaka</div>
          </div>
          <div class='res_info_item'>
            <img class='res_info_icon' src='img/night_white.svg'>
            <div class='res_info_text'> ${guest.total_nights} noći </div>
          </div>
          <div class='res_info_item'>
            <img class='res_info_icon' src='img/price_tag_white.svg'>
            <div class='res_info_text'> ${guest.total_paid} EUR </div>
          </div>
          ${note_html}
        </div>
      </div>
      <div id='guest_info_reservations_list'>
        ${loader_html()}
      </div>
      <div class='res_info_buttons'>
        <button class='confirm_button' id='guest_info_register'> Prijavi boravak </button>
        <button class='confirm_button' data-value='${guest.id}' id='guest_info_edit'> Izmjeni </button>
      </div>
    </div>
    `);
  var scroll = $("html").scrollTop() > $("body").scrollTop() ? $("html").scrollTop() : $("body").scrollTop();
  $(".info").css("top", scroll+"px");

  $.ajax({
    url: api_link + 'data/guestReservations',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            id: guest.id
          },
    success: function(rezultat){
      $("#guest_info_reservations_list").empty();
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }

      // Save data nad display
      for(let i=0;i<sve.reservations.length;i++){
        all_reservations[sve.reservations[i].reservation_code] = sve.reservations[i];
        $("#guest_info_reservations_list").append(compact_res_html("guest_info_reservations_list", sve.reservations[i]));
      }
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });



};
