let reservations_page = 1;
let total_reservations_pages = 1;

$(document).ready(function(){

// Requests
function year_month_change() { // Setting filter dates by month / year
  reservations_page = 1;
  let year = $("#reservations_year").val();
  let month = $("#reservations_month").val();
  if(month == "" && year == ""){
    $("#reservations_filter_dfrom").datepicker().data('datepicker').clear();
    $("#reservations_filter_dto").datepicker().data('datepicker').clear();
  }
  else {
    let dfrom;
    let dto;
    if(month == ""){ // If month is empty get full year
      dfrom = `${year}-01-01`;
      dto = `${year}-12-31`;
    }
    else {
      year = year == "" ? (new Date()).getFullYear() : year; // If year is empty use current
      dfrom = `${year}-${month}-01`;
      dto = `${year}-${month}-31`;
    }
    $("#reservations_filter_dfrom").datepicker().data('datepicker').selectDate(new Date(dfrom));
    $("#reservations_filter_dto").datepicker().data('datepicker').selectDate(new Date(dto));
  }
  get_reservations();
}
$("#reservations_year").change(year_month_change);
$("#reservations_month").change(year_month_change);
$("#reservations_order_type").click(function(){
  if($(this).hasClass("asc")){
    $(this).removeClass("asc");
    $(this).addClass("desc");
    $(this).attr("data-value", "desc");
  }
  else {
    $(this).removeClass("desc");
    $(this).addClass("asc");
    $(this).attr("data-value", "asc");
  }
  reservations_page = 1;
  get_reservations();
});
$("#reservations_order_by").change(function(){
  reservations_page = 1;
  get_reservations();
});
// Filter
$("#reservations_filter_clear").click(function(){
  $("#reservations_filter_by").val("date_received").change();
  $("#reservations_filter_dfrom").datepicker().data('datepicker').clear();
  $("#reservations_filter_dto").datepicker().data('datepicker').clear();
  set_checkbox("reservations_filter_arrivals", 0);
  set_checkbox("reservations_filter_departures", 0);
  $("#reservations_filter_status").val("").change();
  $("#reservations_filter_rooms").val([]).change();
  $('#reservations_filter_price').jRange('updateRange', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#reservations_filter_nights').jRange('updateRange', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#reservations_filter_price').jRange('setValue', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $('#reservations_filter_nights').jRange('setValue', 0 + ',' + Number.MAX_SAFE_INTEGER);
  $("#reservations_filter_channels").val([]).change();
  $("#reservations_filter_countries").val([]).change();
  hide_modals();
});

$("#reservations_list").on("click", ".delete", function(e){ // Show dialog and delete
  e.stopPropagation();
  let row_id = $(this).closest(".reservation")[0].id;
  let id = row_id.split("_");
  id = id[id.length - 1];
  let reservation = all_reservations[id];
  let confirm_text = `Da li želite da otkažete ${reservation.reservation_code}`;
  if(reservation.status == 5)
    confirm_text = `Da li želite da obrišete ${reservation.reservation_code}`;
  if(confirm(confirm_text)){
    $.ajax({
      type: 'POST',
      url: api_link + 'delete/reservation',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id,
        status: reservation.status
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        let change_text = `Otkazana rezervacija ${reservation.reservation_code}`;
        if(reservation.status == 5)
          change_text = `Obrisana rezervacija ${reservation.reservation_code}`;
        add_change(change_text, sve.data.id); // Add changelog
        reservations_page = 1;
        get_reservations(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  }
});

$("body").on("click", ".res_info_delete", function(e){ // Show dialog and delete
  e.stopPropagation();
  let row_id = $(this)[0].id;
  let id = row_id.split("_");
  id = id[id.length - 1];
  let reservation = all_reservations[id];
  let confirm_text = `Da li želite da otkažete ${reservation.reservation_code}`;
  if(reservation.status == 5)
    confirm_text = `Da li želite da obrišete ${reservation.reservation_code}`;
  click_to_hide();
  if(confirm(confirm_text)){
    $.ajax({
      type: 'POST',
      url: api_link + 'delete/reservation',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id,
        status: reservation.status
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        let change_text = `Otkazana rezervacija ${reservation.reservation_code}`;
        if(reservation.status == 5)
          change_text = `Obrisana rezervacija ${reservation.reservation_code}`;
        add_change(change_text, sve.data.id); // Add changelog
        reservations_page = 1;
        get_reservations(); // Refresh data
        get_calendar();
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  }
});



});

function get_reservations()
{
  // Clear list
  if(reservations_page == 1)
    $("#reservations_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  else
    $("#reservations_list").append("<div class='loader'><div class='loader_icon'></div></div>");
  // Parameters
  let prices = $("#reservations_filter_price").val().split(",");
  let min_price = prices[0];
  let max_price = prices[1];
  let nights = $("#reservations_filter_nights").val().split(",");
  let min_nights = nights[0];
  let max_nights = nights[1];
  $.ajax({
    url: api_link + 'data/reservations',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            filter_by: $("#reservations_filter_by").val(),
            dfrom: date_to_iso($("#reservations_filter_dfrom").datepicker().data('datepicker').selectedDates[0]),
            dto: date_to_iso($("#reservations_filter_dto").datepicker().data('datepicker').selectedDates[0]),
            arrivals: $("#reservations_filter_arrivals").attr("data-value"),
            departures: $("#reservations_filter_departures").attr("data-value"),
            status: $("#reservations_filter_status").val(),
            rooms: JSON.stringify($("#reservations_filter_rooms").val()),
            min_price: min_price,
            max_price: max_price,
            min_nights: min_nights,
            max_nights: max_nights,
            channels: JSON.stringify($("#reservations_filter_channels").val()),
            countries: JSON.stringify($("#reservations_filter_countries").val()),
            order_type: $("#reservations_order_type").attr("data-value"),
            order_by: $("#reservations_order_by").val(),
            page: reservations_page
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      display_reservations(sve);
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
};

function display_reservations(data){ // Check if destructuring works
  let reservations_list = data.reservations;
  let min_price = 0;
  let max_price = parseFloat(data.max_price) + 1;
  let min_nights = 0;
  let max_nights = parseFloat(data.max_nights) + 1;
  console.log(min_price, max_price);
  reservations_page = parseInt(data.page);
  total_reservations_pages = parseInt(data.total_pages_number);
  // Clear list
  if(reservations_page == 1)
    $("#reservations_list").empty();
  else
    $("#reservations_list").find(".loader").remove();
  // Set min / max values
  $('#reservations_filter_price').jRange('updateRange', min_price + ',' + max_price);
  let vals = $('#reservations_filter_price').val().split(",");
  vals[0] = min_price > vals[0] ? min_price : vals[0];
  vals[1] = max_price < vals[1] ? max_price : vals[1];
  vals = vals.join(",");
  $('#reservations_filter_price').jRange('setValue', vals);
  $('#reservations_filter_nights').jRange('updateRange', min_nights + ',' + max_nights);
  vals = $('#reservations_filter_nights').val().split(",");
  vals[0] = min_nights > vals[0] ? min_nights : vals[0];
  vals[1] = max_nights < vals[1] ? max_nights : vals[1];
  vals = vals.join(",");
  $('#reservations_filter_nights').jRange('setValue', vals);
  // Display data
  for(let i=0;i<reservations_list.length;i++){
    all_reservations[reservations_list[i].reservation_code] = reservations_list[i];
    $("#reservations_list").append(res_html("reservations_list", reservations_list[i]));
  }
  if(reservations_list.length == 0 && reservations_page == 1)
    $("#reservations_list").append(empty_html("Nema rezervacija"));
  else if(reservations_page == 1)
    $("#reservations_list").prepend(`
    <div class="list_names">
      <div class='res_channel'> Kanal </div>
      <div class='res_received'> Datum </div>
      <div class='res_guest'> Gost </div>
      <div class='res_period'> Period </div>
      <div class='res_price'> Iznos </div>
      <div class='res_rooms'> Jedinice </div>
      <div class='res_note'> Napomena </div>
      <div class='res_actions'> Akcije </div>
    </div>`);
}

function res_html(loc, res){
  var id = res.reservation_code;
  var date_received = iso_to_eur(res.date_received);
  var time_received = res.time_received.substring(0,5);
  var customer_name = res.customer_name + " " + res.customer_surname;
  var country_img = res.customer_country == "--" ? "" : `<img class='country_flag' src='https://www.countryflags.io/${res.customer_country}/shiny/16.png'>`;
  var adults = parseInt(res.men);
  var children = parseInt(res.children);
  var guests = adults + children;
  var period = iso_to_eur(res.date_arrival).substring(0,5) + " - " + iso_to_eur(res.date_departure).substring(0,5);
  var nights = res.nights;
  var price = res.total_price;
  var price_per_night = (res.reservation_price / res.nights).toFixed(2);
  var rooms = res.rooms;
  let room_text = "";
  let room_data = res.room_data;
  if(room_data.length == 1){
    let room_count = room_data[0].count == 1 ? "" : ` (${room_data[0].count})`;
    room_text = `${room_data[0].name}${room_count}`;
  }
  else {
    room_text = [];
    for(let i=0;i<room_data.length;i++){ // Same as one room, but add shortname of each room
      room_text.push(`${room_data[i].shortname} (${room_data[i].count})`);
    }
    room_text = room_text.join(", ");
  }
  let channel_name = res.channel_name;
  let channel_logo = res.channel_logo;
  var note = res.note;
  if(note.length > 70)
    note = note.substring(0,70) + "...";
  var status = `<div class='res_confirmed'> Potvrđena </div>`;
  if(res.status == 5 && res.was_modified == 0)
    status = `<div class='res_canceled'> Otkazana </div>`;
  else if(res.status == 5 && res.was_modified == 1)
    status = `<div class='res_modified'> Izmenjena </div>`;
  let adults_info = adults > 0 ? `<div title="${adults} odraslih" class='res_guests_info'>${adults} <img class='res_guests_icon adults'></div>` : "";
  let children_info = children > 0 ? `<div title="${children} dece" class='res_guests_info'>${children} <img class='res_guests_icon children'></div>` : "";
  let guests_info = `<div class='res_guests_info'><div title="${guests} gostiju" class='res_guests_info'>${guests} <img class='res_guests_icon total'></div>${adults_info} ${children_info}</div>`;
  let res_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmeni'> </div>`;
  let res_invoice = `<div class='list_action'><img class='list_action_icon invoice' title='Račun'> </div>`;
  let res_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
  if(res.id_woodoo != "-1" && res.id_woodoo != "" && res.id_woodoo != "-2")
    res_delete = "";
  var ret_val = `
    <div class="list_row reservation" id='${loc}_${id}'>
      <div class='res_channel' title='${channel_name}'>
        <img src='${channel_logo}'>
      </div>
      <div class='res_received'>
        <div class='res_date_received'> ${date_received} </div>
        <div class='res_time_received'> ${time_received} </div>
      </div>
      <div class='res_guest'>
        <div class='res_name'>${customer_name} ${country_img}</div>
        <div class='res_guest_info'>${guests_info}</div>
      </div>
      <div class='res_period'>
        <div class='res_dates'> ${period}</div>
        <div class='res_nights'>${nights} noći</div>
      </div>
      <div class='res_price'>
       <div class='res_total'>${price} ${currency}</div>
       <div class='res_avg'>${price_per_night} ${currency} p/n</div>
      </div>
      <div class='res_rooms'>
        ${room_text}
        ${status}
      </div>
      <div class='res_note'>
        ${note}
      </div>
      <div class='res_actions'>
        ${res_edit} ${res_invoice} ${res_delete}
      </div>
    </div>`;
  return ret_val;
}

function compact_res_html(loc, res){
  var id = res.reservation_code;
  var date_received = iso_to_eur(res.date_received);
  var time_received = res.time_received.substring(0,5);
  var customer_name = res.customer_name + " " + res.customer_surname;
  var period = iso_to_eur(res.date_arrival).substring(0,5) + " - " + iso_to_eur(res.date_departure).substring(0,5);
  var nights = res.nights;
  var price = res.total_price;
  var price_per_night = (res.reservation_price / res.nights).toFixed(2);
  let room_text = "";
  let room_data = res.room_data;
  if(room_data.length == 1){
    let room_count = room_data[0].count == 1 ? "" : ` (${room_data[0].count})`;
    room_text = `${room_data[0].name}${room_count}`;
  }
  else {
    room_text = [];
    for(let i=0;i<room_data.length;i++){ // Same as one room, but add shortname of each room
      room_text.push(`${room_data[i].shortname} (${room_data[i].count})`);
    }
    room_text = room_text.join(", ");
  }
  let channel_name = res.channel_name;
  let channel_logo = res.channel_logo;
  var ret_val = `
    <div class="list_row reservation" id='${loc}_${id}'>
      <div class='compact_res_channel' title='${channel_name}'>
        <img src='${channel_logo}'>
      </div>
      <div class='compact_res_info'>
        <div class='compact_res_name'>${customer_name}</div>
        <div class='compact_res_received'>${date_received}, ${time_received}</div>
      </div>
      <div class='compact_res_period'>
        <div class='compact_res_dates'> ${period}</div>
        <div class='compact_res_nights'>${nights} noći</div>
      </div>
      <div class='compact_res_price'>
       <div class='compact_res_total'>${price} ${currency}</div>
       <div class='compact_res_avg'>${price_per_night} ${currency} p/n</div>
      </div>
      <div class='compact_res_rooms'>
        ${room_text}
      </div>
    </div>`;
  return ret_val;
}
