

$(document).ready(function(){

// Open New
$("#new_reservation").click(function(){
  // Show form
  $("#form_res").show();
  scroll_lock();
  // Clear values
  $("#form_res h1").text("Unos nove rezervacije");
  $("#form_res_id").val("");
  $("#form_res_pid").val(properties_map[main_lcode].default_price);
  $("#form_res_dfrom").datepicker().data('datepicker').clear();
  $("#form_res_dto").datepicker().data('datepicker').clear();
  $('#form_res_dfrom').datepicker().data('datepicker').update('maxDate', "");
  $('#form_res_dfrom').datepicker().data('datepicker').update('minDate', new Date());
  $('#form_res_dto').datepicker().data('datepicker').update('minDate', new Date());
  $("#form_res_room").val(-1).change();
  $("#form_res_room_number").val("x");
  $("#form_res_room option").show();
  $("#form_res_channel").val(-1);
  $("#form_res_commission_text").text("0.00");
  $("#form_res_commission_amount").val(0.00);
  $("#form_res_adults").val(0);
  $("#form_res_children").val(0);
  $("#form_res_night_price").val(0);
  $("#form_res_nights").val("0 noći");
  $("#form_res_amount").val(0);
  $("#form_res_discount_value").val(0);
  $("#form_res_discount_type").val("percent").change();
  $("#form_res_avans_value").val(0);
  $("#form_res_avans_type").val("percent").change();
  $("#form_res_services").empty();
  $("#form_res_remaining_amount").val(0);
  $("#form_res_total_price").val(0);
  $("#form_res_total_paid").val(0);
  $("#form_res_total_remaining").val(0);
  $("#form_res_error").text("");
  // Add first guest
  $("#form_res_guests").empty();
  $("#form_res_guests").append(`
    <div class='flex_around form_res_guest' id='form_res_guest_${+(new Date())}'>
      <input type='hidden' class='form_res_guest_id' value=''>
      <div class='vert_center'>
        <div> Ime </div>
        <input type='text' class='text_input form_res_guest_name'>
      </div>
      <div class='vert_center'>
        <div> Prezime </div>
        <input type='text' class='text_input form_res_guest_surname'>
      </div>
      <div class='vert_center'>
        <div> Email </div>
        <input type='text' class='text_input form_res_guest_email'>
      </div>
      <div class='vert_center'>
        <div> Telefon </div>
        <input type='text' class='text_input form_res_guest_phone'>
      </div>
      <div class='vert_center'>
        <div> &nbsp; </div>
        <div class='flex_center'>
          <div class='list_action'><img class='list_action_icon edit' title='Dodatni podaci'> </div>
          <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>
        </div>
      </div>
    </div>`);

  // Disable inputs until dates and room is selected
  $("#form_res .secondary").css("opacity", "0.5");
  $("#form_res .secondary").css("pointer-events", "none");
  $("#form_res .tertiary").css("opacity", "0.5");
  $("#form_res .tertiary").css("pointer-events", "none");

});

$("#form_res_confirm").click(function(){
  // Loaders
  $("#form_res_confirm, #form_res_cancel").addClass("button_loader");
  // Parameters
  var id = $("#form_res_id").val();
  var date_arrival = date_to_iso($("#form_res_dfrom").datepicker().data('datepicker').selectedDates[0]);
  var date_departure = date_to_iso($("#form_res_dto").datepicker().data('datepicker').selectedDates[0]);
  var rooms = [];
  var room = {};
  room.id = $("#form_res_room").val();
  room.price = $("#form_res_night_price").val();
  room.count = 1;
  room.room_numbers = [];
  let room_number = $("#form_res_room_number").val();
  if(room_number != "x"){
    room.room_numbers.push(room_number);
  }
  rooms.push(room);
  rooms = JSON.stringify(rooms);
  var adults = $("#form_res_adults").val();
  var children = $("#form_res_children").val();
  let channel = $("#form_res_channel").val();
  let guests = [];
  $(".form_res_guest").each(function(){
    let guest = {};
    guest["id"] = $(this).find(".form_res_guest_id")[0].value;
    guest["name"] = $(this).find(".form_res_guest_name")[0].value;
    guest["surname"] = $(this).find(".form_res_guest_surname")[0].value;
    guest["email"] = $(this).find(".form_res_guest_email")[0].value;
    guest["phone"] = $(this).find(".form_res_guest_phone")[0].value;
    guests.push(guest);
  });
  guests = JSON.stringify(guests);
  let discount = {};
  discount["type"] = $("#form_res_discount_type").val();
  discount["value"] = $("#form_res_discount_value").val();
  discount = JSON.stringify(discount);
  let avans = {};
  avans["type"] = $("#form_res_avans_type").val();
  avans["value"] = $("#form_res_avans_value").val();
  avans = JSON.stringify(avans);
  let services = [];
  $(".form_res_service").each(function(){
    let service = {};
    service["name"] = $(this).find(".form_res_service_name")[0].value;
    service["price"] = parseFloat($(this).find(".form_res_service_price")[0].value);
    service["amount"] = parseFloat($(this).find(".form_res_service_amount")[0].value);
    service["tax"] = parseFloat($(this).find(".form_res_service_tax")[0].value);
    services.push(service);
  });
  services = JSON.stringify(services);
  var total_price = $("#form_res_total_price").val();
  let note = $("#form_res_note").val();
  let send_guest_email = properties_map[main_lcode].notify_guests;

  // Call
  let action = id == "" ? 'insert/reservation' : 'edit/reservation';
  $.ajax({
    type: 'POST',
    url: api_link + action,
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: id,
      date_arrival: date_arrival,
      date_departure: date_departure,
      rooms: rooms,
      adults: adults,
      children: children,
      id_woodoo: channel,
      guests: guests,
      discount: discount,
      avans: avans,
      services: services,
      total_price: total_price,
      note: note,
      send_guest_email: send_guest_email
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      if(id == "")
        add_change(`Dodata nova rezervacija`, sve.data.id); // Add changelog
      else
        add_change(`Izmjenjena rezervacija ${id}`, sve.data.id); // Add changelog
      $("#form_res_cancel").click();
      hash_change();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

// Date inputs

$('#form_res_dfrom').datepicker().data('datepicker').update(
  {
    position: "bottom left",
    minDate: new Date(),
    onHide: function(inst, animationCompleted) {
      if(animationCompleted === false)
      {
        open_calendar = "";
        if(inst.selectedDates.length)
        {
          var minDate = new Date(inst.selectedDates[0]);
          $('#form_res_dto').datepicker().data('datepicker').show();
        }
        else {
          var minDate = new Date();
        }
        minDate.setDate(minDate.getDate() + 1);
        $('#form_res_dto').datepicker().data('datepicker').update({
          minDate: minDate
        });
        if($("#form_res_dfrom").val() !== "" && $("#form_res_dto").val() !== "")
        {
          var dolazak_date = $('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0];
          var odlazak_date = $('#form_res_dto').datepicker().data('datepicker').selectedDates[0];
          dolazak_date = date_to_iso(dolazak_date);
          odlazak_date = date_to_iso(odlazak_date);
          $("#form_res_nights").val(`${num_of_nights(dolazak_date, odlazak_date)} noći`);
          get_res_rooms();
        }
        else {
          $("#form_res_room").prop("selectedIndex", 0).change();
          $("#form_res .secondary").css("opacity", "0.5");
          $("#form_res .secondary").css("pointer-events", "none");
        }
      }
    }
  }
);
$('#form_res_dto').datepicker().data('datepicker').update(
  {
    minDate: new Date(),
    onHide: function(inst, animationCompleted) {
      if(animationCompleted === false)
      {
        open_calendar = "";
        if(inst.selectedDates.length)
        {
          var maxDate = new Date(inst.selectedDates[0]);
        }
        else {
          var maxDate = new Date();
        }
        maxDate.setDate(maxDate.getDate() - 1);
        $('#form_res_dfrom').datepicker().data('datepicker').update({
          maxDate: maxDate
        });
        if($("#form_res_dfrom").val() !== "" && $("#form_res_dto").val() !== "")
        {
          var dolazak_date = $('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0];
          var odlazak_date = $('#form_res_dto').datepicker().data('datepicker').selectedDates[0];
          dolazak_date = date_to_iso(dolazak_date);
          odlazak_date = date_to_iso(odlazak_date);
          $("#form_res_nights").val(`${num_of_nights(dolazak_date, odlazak_date)} noći`);
          get_res_rooms();
        }
        else {
          $("#form_res_room").prop("selectedIndex", 0).change();
          $("#form_res .secondary").css("opacity", "0.5");
          $("#form_res .secondary").css("pointer-events", "none");
        }
      }
    }
  }
);



// Room input

$("#form_res_room").change(function(){
  if(disable_calls)
    return;
  let id = $(this).val();
  if(id == -1){
    $("#form_res_room_number").val("x").change();
    $("#form_res .tertiary").css("opacity", "0.5");
    $("#form_res .tertiary").css("pointer-events", "none");
  }
  else {
    $.ajax({
      url: api_link + 'data/resRoomData',
      method: 'POST',
      data: {
              key: main_key,
              account: account_name,
              lcode: main_lcode,
              dfrom: date_to_iso($('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0]),
              dto: date_to_iso($('#form_res_dto').datepicker().data('datepicker').selectedDates[0]),
              room: id,
              pid: $("#form_res_pid").val()
            },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        $("#form_res_room_number .room").remove();
        for(let i=0;i<sve.room_numbers.length;i++)
        {
          $("#form_res_room_number").append(`<option class='room' value='${sve.room_numbers[i].id}'> ${sve.room_numbers[i].name} </option>`);
        }
        $("#form_res .tertiary").css("opacity", "");
        $("#form_res .tertiary").css("pointer-events", "");
        $("#form_res_night_price").val((parseFloat(sve.price)).toFixed(2));
        let res_id = $("#form_res_id").val();
        if(res_id !== ""){
          let res_room_number = all_reservations[res_id].room_numbers[0];
          let res_room = all_reservations[res_id].rooms[0];
          if($(`#form_res_room_number option[value='${res_room_number}']`).length == 0){
              $("#form_res_room_number").append(`<option class='room' value='${res_room_number}'> ${rooms_map[res_room].room_numbers[res_room_number]} </option>`);
          }
          $("#form_res_room_number").val(res_room_number).change();
        }
        else {
          $("#form_res_room_number").prop("selectedIndex", 0).change();
        }
        res_price_update();
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  }
});

// Number inputs

$("#form_res_night_price").on("input", res_price_update);
$("#form_res_discount_value").on("input", res_price_update);
$("#form_res_avans_value").on("input", res_price_update);
$("#form_res_discount_type").change(res_price_update);
$("#form_res_avans_type").change(res_price_update);
$("#form_res_services").on("input", ".number_input", res_price_update);

// Add guest
$("#form_res_new_guest").click(function(){
  $("#form_res_guests").append(`
    <div class='flex_around form_res_guest' id='form_res_guest_${+(new Date())}'>
      <input type='hidden' class='form_res_guest_id' value=''>
      <div class='vert_center'>
        <div> Ime </div>
        <input type='text' class='text_input form_res_guest_name'>
      </div>
      <div class='vert_center'>
        <div> Prezime </div>
        <input type='text' class='text_input form_res_guest_surname'>
      </div>
      <div class='vert_center'>
        <div> Email </div>
        <input type='text' class='text_input form_res_guest_email'>
      </div>
      <div class='vert_center'>
        <div> Telefon </div>
        <input type='text' class='text_input form_res_guest_phone'>
      </div>
      <div class='vert_center'>
        <div> &nbsp; </div>
        <div class='flex_center'>
          <div class='list_action'><img class='list_action_icon edit' title='Dodatni podaci'> </div>
          <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>
        </div>
      </div>
    </div>`);
});
// Delete guest
$("#form_res_guests").on("click", ".delete", function(){
  if($(".form_res_guest").length == 1){
    $(".form_res_guest_id").val("");
    $(".form_res_guest_name").val("");
    $(".form_res_guest_surname").val("");
    $(".form_res_guest_email").val("");
    $(".form_res_guest_phone").val("");
  }
  else {
    $(this).closest(".form_res_guest").remove();
  }
});
// Add service
$("#form_res_new_service").click(function(){
  $("#form_res_services").append(`
    <div class='flex_around form_res_service'>
      <div class='vert_center'>
        <div> Naziv usluge </div>
        <input type='text' class='text_input form_res_service_name'>
      </div>
      <div class='vert_center'>
        <div> Cijena </div>
        <input type='number' class='number_input form_res_service_price' value=0>
      </div>
      <div class='vert_center'>
        <div> Količina </div>
        <input type='number' class='number_input form_res_service_amount' value=1>
      </div>
      <div class='vert_center'>
        <div> Porez (%) </div>
        <input type='number' class='number_input form_res_service_tax' value=0>
      </div>
      <div class='vert_center'>
        <div> Ukupna cijena </div>
        <input type='number' class='number_input form_res_service_total form_readonly' readonly value=0>
      </div>
      <div class='vert_center'>
        <div> &nbsp; </div>
        <div class='flex_center'>
          <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>
        </div>
      </div>
    </div>`);
});
// Delete service
$("#form_res_services").on("click", ".delete", function(){
  $(this).closest(".form_res_service").remove();
});
// Close
$(".form_cancel").click(function(){
    $("html, body").css("overflow", "");
    $(".form_container").hide();
  });

// Opens from list

// Open from list
$("#reservations_list").on("click", ".edit", function(e){
  e.stopPropagation();
  let id  = $(this).closest(".reservation")[0].id.split("_");
  id = id[id.length - 1];
  let res = all_reservations[id];
  if(res.rooms.length == 1)
    open_reservation_form(res);
  else {
    open_group_form(res);
  }
});

$("body").on("click", "#res_info_edit", function(){
  let id  = $("#res_info_invoice").attr("data-value");
  let res = all_reservations[id];
  click_to_hide();
  if(res.rooms.length == 1)
    open_reservation_form(res);
  else {
    open_group_form(res);
  }
});

});

function get_res_rooms(){
  if(disable_calls)
    return;
  $.ajax({
    url: api_link + 'data/freeRooms',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: date_to_iso($('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0]),
            dto: date_to_iso($('#form_res_dto').datepicker().data('datepicker').selectedDates[0])
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      $("#form_res_room .room").remove();
      for(let i=0;i<sve.rooms.length;i++)
      {
        $("#form_res_room").append(`<option class='room' value='${sve.rooms[i].id}'> ${sve.rooms[i].name} </option>`);
      }
      $("#form_res .secondary").css("opacity", "");
      $("#form_res .secondary").css("pointer-events", "");
      let id = $("#form_res_id").val();
      if(id !== ""){
        let res_room = all_reservations[id].rooms[0];
        if($(`#form_res_room option[value='${res_room}']`).length == 0){
            $("#form_res_room").append(`<option class='room' value='${res_room}'> ${rooms_map[res_room].name} </option>`);
        }
        $("#form_res_room").val(res_room).change();
      }
      else {
        $("#form_res_room").prop("selectedIndex", 0).change();
      }
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function res_price_update(){

  var nights = $("#form_res_nights").val().split(" ")[0];
  if(nights === "")
    nights = 0;
  else
    nights = parseFloat(nights);
  var night_price = parseFloat($("#form_res_night_price").val());
  if(night_price < 0 || isNaN(night_price))
    night_price = 0;
  var discount = parseFloat($("#form_res_discount_value").val());
  if(discount < 0 || isNaN(discount))
    discount = 0;
  var discount_type = $("#form_res_discount_type").val();
  var avans = parseFloat($("#form_res_avans_value").val());
  if(avans < 0 || isNaN(avans))
    avans = 0;
  var avans_type = $("#form_res_avans_type").val();

  var amount = night_price * nights;
  var discounted_price = amount;
  if(discount_type == 'percent')
  {
    if(discount > 100)
      discount = 100;
    discounted_price = amount - amount * discount/100;
  }
  if(discount_type == 'fixed')
  {
    if(discount > amount)
      discount = amount;
    discounted_price = amount - discount;
  }
  var services_price = 0;

  $(".form_res_service").each(function(){
    var number = parseFloat($(this).find(".form_res_service_amount")[0].value);
    var price = parseFloat($(this).find(".form_res_service_price")[0].value);
    var tax = parseFloat($(this).find(".form_res_service_tax")[0].value);
    if(number < 0 || isNaN(number))
      number = 0;
    if(price < 0 || isNaN(price))
      price = 0;
    if(tax < 0 || isNaN(tax))
      tax = 0;
    var total = (number*price) + (number*price)*tax/100;
    total = isNaN(total) ? 0 : total;
    $(this).find(".form_res_service_amount").val(number);
    $(this).find(".form_res_service_price").val(price);
    $(this).find(".form_res_service_tax").val(tax);
    $(this).find(".form_res_service_total").val(total.toFixed(2));
    services_price += total;
  });
  var total_price = services_price + discounted_price;

  var avans_percent = 0;
  var avans_amount = 0;
  if(avans_type == 'percent')
  {
    if(avans > 100)
      avans = 100;
    avans_amount = (discounted_price/100) * avans;
    avans_percent = avans;
    $("#form_res_avans_value").val(avans_percent);
    $("#form_res_avans_text").text("(" + avans_amount + " " + currency + ")");
  }
  if(avans_type == 'fixed')
  {
    if(avans > discounted_price)
      avans = discounted_price;
    avans_amount = avans;
    avans_percent = 100 - (discounted_price - avans) * 100 / discounted_price;
    $("#form_res_avans_value").val(avans_amount);
    avans_percent = isNaN(avans_percent) ? 0 : avans_percent.toFixed(2);
    $("#form_res_avans_text").text("(" + avans_percent + " %)");
  }
  var remaining_amount = discounted_price - avans_amount;
  var remaining_percent = 100 - avans_percent;

  var commission = 0;
  if(channels_map[$("#form_res_channel").val()] != undefined)
    commission = parseFloat(channels_map[$("#form_res_channel").val()].commission);
  commission_amount = (total_price/100)*commission;
  $("#form_res_commission_text").text(commission.toFixed(2));
  $("#form_res_commission_amount").val(commission_amount.toFixed(2));
  $("#form_res_night_price").val(night_price);
  $("#form_res_amount").val(amount);
  $("#form_res_discount_value").val(discount);
  $("#form_res_discounted_price").val(discounted_price.toFixed(2));
  $("#form_res_services_price").val(services_price.toFixed(2));
  $("#form_res_remaining_amount").val(remaining_amount.toFixed(2));
  $("#form_res_total_price").val(total_price.toFixed(2));
  $("#form_res_total_paid").val(avans_amount.toFixed(2));
  $("#form_res_total_remaining").val((total_price.toFixed(2) - avans_amount.toFixed(2)).toFixed(2));
}

function open_reservation_form(res) {
  disable_calls = true;
  // Show form
  $("#form_res").show();
  scroll_lock();
  // Clear values
  $("#form_res h1").text("Ažuriranje rezervacije");
  $("#form_res_id").val(res.reservation_code);
  if(res.pricing_plan == "")
    res.pricing_plan = $("#default_price").val();
  $("#form_res_pid").val(res.pricing_plan);
  $("#form_res_dfrom").datepicker().data('datepicker').selectDate(new Date(res.date_arrival));
  $("#form_res_dto").datepicker().data('datepicker').selectDate(new Date(res.date_departure));
  $('#form_res_dfrom').datepicker().data('datepicker').update('maxDate', "");
  $('#form_res_dfrom').datepicker().data('datepicker').update('minDate', new Date());
  $('#form_res_dto').datepicker().data('datepicker').update('minDate', new Date());
  $("#form_res_channel").val(res.id_woodoo);
  $("#form_res_commission_text").text("0.00");
  $("#form_res_commission_amount").val(0.00);
  $("#form_res_adults").val(res.men);
  $("#form_res_children").val(res.children);
  $("#form_res_night_price").val(res.room_data[0].price);
  $("#form_res_nights").val(res.nights + " noći");
  $("#form_res_amount").val(res.amount);
  $("#form_res_discount_value").val(res.discount.value);
  $("#form_res_discount_type").val(res.discount.type).change();
  $("#form_res_avans_value").val(res.payment_gateway_fee.value);
  $("#form_res_avans_type").val(res.payment_gateway_fee.type).change();
  $("#form_res_remaining_amount").val(0);
  $("#form_res_total_price").val(0);
  $("#form_res_total_paid").val(0);
  $("#form_res_total_remaining").val(0);
  $("#form_res_error").text("");
  $("#form_res_note").val(res.note);

  $("#form_res_guests").empty();
  for(let i=0;i<res.guests.length;i++){
    let guest = res.guests[i];
    guests_map[guest.id] = guest;
    $("#form_res_guests").append(`
      <div class='flex_around form_res_guest' id='form_res_guest_${+(new Date())}'>
        <input type='hidden' class='form_res_guest_id' value='${guest.id}'>
        <div class='vert_center'>
          <div> Ime </div>
          <input type='text' class='text_input form_res_guest_name' value='${guest.name}'>
        </div>
        <div class='vert_center'>
          <div> Prezime </div>
          <input type='text' class='text_input form_res_guest_surname' value='${guest.surname}'>
        </div>
        <div class='vert_center'>
          <div> Email </div>
          <input type='text' class='text_input form_res_guest_email' value='${guest.email}'>
        </div>
        <div class='vert_center'>
          <div> Telefon </div>
          <input type='text' class='text_input form_res_guest_phone' value='${guest.phone}'>
        </div>
        <div class='vert_center'>
          <div> &nbsp; </div>
          <div class='flex_center'>
            <div class='list_action'><img class='list_action_icon edit' title='Dodatni podaci'> </div>
            <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>
          </div>
        </div>
      </div>`);
  }

  $("#form_res .secondary").css("opacity", "");
  $("#form_res .secondary").css("pointer-events", "");
  $("#form_res .tertiary").css("opacity", "");
  $("#form_res .tertiary").css("pointer-events", "");

  // Getting free rooms
  let res_room = res.rooms[0];
  let res_room_number = res.room_numbers[0];
  $.ajax({
    url: api_link + 'data/freeRooms',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: date_to_iso($('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0]),
            dto: date_to_iso($('#form_res_dto').datepicker().data('datepicker').selectedDates[0])
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      $("#form_res_room .room").remove();
      for(let i=0;i<sve.rooms.length;i++){
        $("#form_res_room").append(`<option class='room' value='${sve.rooms[i].id}'> ${sve.rooms[i].name} </option>`);
      }
      let id = $("#form_res_id").val();
      // Select the res room and add it if it isn't listed as free
      if($(`#form_res_room option[value='${res_room}']`).length == 0){
          $("#form_res_room").append(`<option class='room' value='${res_room}'> ${rooms_map[res_room].name} </option>`);
      }
      $("#form_res_room").val(res_room).change();

      // Getting room numbers

      $.ajax({
        url: api_link + 'data/resRoomData',
        method: 'POST',
        data: {
                key: main_key,
                account: account_name,
                lcode: main_lcode,
                dfrom: date_to_iso($('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0]),
                dto: date_to_iso($('#form_res_dto').datepicker().data('datepicker').selectedDates[0]),
                room: res_room,
                pid: $("#form_res_pid").val()
              },
        success: function(rezultat){
          var sve = check_json(rezultat);
          if(sve.status !== "ok") {
            add_change_error(sve.status);
            return;
          }
          $("#form_res_room_number .room").remove();
          for(let i=0;i<sve.room_numbers.length;i++){
            $("#form_res_room_number").append(`<option class='room' value='${sve.room_numbers[i].id}'> ${sve.room_numbers[i].name} </option>`);
          }
          // Selecting room number and adding it if it isn't listed as free
          if($(`#form_res_room_number option[value='${res_room_number}']`).length == 0){
              $("#form_res_room_number").append(`<option class='room' value='${res_room_number}'> ${rooms_map[res_room].room_numbers[res_room_number]} </option>`);
          }
          $("#form_res_room_number").val(res_room_number).change();
          res_price_update();
          disable_calls = false;
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
          disable_calls = false;
        }
      });

    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
      disable_calls = false;
    }
  });
}
function open_group_form(res) {
  // Show form
  $("#form_group").show();
  scroll_lock();
  // Clear values
  $("#form_group h1").text("Ažuriranje rezervacije");
  $("#form_group_id").val(res.reservation_code);
  if(res.pricing_plan == "")
    res.pricing_plan = $("#default_price").val();
  $("#form_group_pid").val(res.pricing_plan);
  $("#form_group_dfrom").datepicker().data('datepicker').selectDate(new Date(res.date_arrival));
  $("#form_group_dto").datepicker().data('datepicker').selectDate(new Date(res.date_departure));
  $('#form_group_dfrom').datepicker().data('datepicker').update('maxDate', "");
  $('#form_group_dfrom').datepicker().data('datepicker').update('minDate', new Date());
  $('#form_group_dto').datepicker().data('datepicker').update('minDate', new Date());
  $("#form_group_channel").val(res.id_woodoo);
  $("#form_group_commission_text").text("0.00");
  $("#form_group_commission_amount").val(0.00);
  $("#form_group_adults").val(res.men);
  $("#form_group_children").val(res.children);
  $("#form_group_nights").val(res.nights + " noći");
  $("#form_group_amount").val(res.amount);
  $("#form_group_remaining_amount").val(0);
  $("#form_group_total_price").val(0);
  $("#form_group_total_paid").val(0);
  $("#form_group_total_remaining").val(0);
  $("#form_group_note").val(res.note);

  $("#form_group_discount_value").val(res.discount.value);
  $("#form_group_discount_type").val(res.discount.type).change();
  $("#form_group_avans_value").val(res.payment_gateway_fee.value);
  $("#form_group_avans_type").val(res.payment_gateway_fee.type).change();

  $("#form_group_guests").empty();
  for(let i=0;i<res.guests.length;i++){
    let guest = res.guests[i];
    guests_map[guest.id] = guest;
    $("#form_group_guests").append(`
      <div class='flex_around form_group_guest' id='form_group_guest_${+(new Date())}'>
        <input type='hidden' class='form_group_guest_id' value='${guest.id}'>
        <div class='vert_center'>
          <div> Ime </div>
          <input type='text' class='text_input form_group_guest_name' value='${guest.name}'>
        </div>
        <div class='vert_center'>
          <div> Prezime </div>
          <input type='text' class='text_input form_group_guest_surname' value='${guest.surname}'>
        </div>
        <div class='vert_center'>
          <div> Email </div>
          <input type='text' class='text_input form_group_guest_email' value='${guest.email}'>
        </div>
        <div class='vert_center'>
          <div> Telefon </div>
          <input type='text' class='text_input form_group_guest_phone' value='${guest.phone}'>
        </div>
        <div class='vert_center'>
          <div> &nbsp; </div>
          <div class='flex_center'>
            <div class='list_action'><img class='list_action_icon edit' title='Dodatni podaci'> </div>
            <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>
          </div>
        </div>
      </div>`);
  }



  $("#form_group_error").text("");

  group_price_update();
}
