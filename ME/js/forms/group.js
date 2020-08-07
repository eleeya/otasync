
$(document).ready(function(){

// Open New
$("#new_group_reservation").click(function(){
  // Show form
  $("#form_group").show();
  scroll_lock();
  // Clear values
  $("#form_group h1").text("Unos grupne rezervacije");
  $("#form_group_id").val("");
  $("#form_group_pid").val(properties_map[main_lcode].default_price);
  $("#form_group_dfrom").datepicker().data('datepicker').clear();
  $("#form_group_dto").datepicker().data('datepicker').clear();
  $('#form_group_dfrom').datepicker().data('datepicker').update('maxDate', "");
  $('#form_group_dto').datepicker().data('datepicker').update('minDate', new Date());
  $("#form_group_channel").val(-1);
  $("#form_group_room_others").remove();
  $("#form_group_rooms .room").remove();
  $(".form_group_room_numbers").remove();
  $("#form_group_commission_text").text("0.00");
  $("#form_group_commission_amount").val(0.00);
  $("#form_group_adults").val(0);
  $("#form_group_children").val(0);
  $("#form_group_nights").val("0 noći");
  $("#form_group_amount").val(0);
  $("#form_group_discount_value").val(0);
  $("#form_group_discount_type").val("percent").change();
  $("#form_group_avans_value").val(0);
  $("#form_group_avans_type").val("percent").change();
  $("#form_group_services").empty();
  $("#form_group_remaining_amount").val(0);
  $("#form_group_total_price").val(0);
  $("#form_group_total_paid").val(0);
  $("#form_group_total_remaining").val(0);
  $("#form_group_error").text("");
  // Add first guest
  $("#form_group_guests").empty();
  $("#form_group_guests").append(`
    <div class='flex_around form_group_guest' id='form_group_guest_${+(new Date())}'>
      <input type='hidden' class='form_group_guest_id' value=''>
      <div class='vert_center'>
        <div> Ime </div>
        <input type='text' class='text_input form_group_guest_name'>
      </div>
      <div class='vert_center'>
        <div> Prezime </div>
        <input type='text' class='text_input form_group_guest_surname'>
      </div>
      <div class='vert_center'>
        <div> Email </div>
        <input type='text' class='text_input form_group_guest_email'>
      </div>
      <div class='vert_center'>
        <div> Telefon </div>
        <input type='text' class='text_input form_group_guest_phone'>
      </div>
      <div class='vert_center'>
        <div> &nbsp; </div>
        <div class='flex_center'>
          <div class='list_action'><img class='list_action_icon edit' title='Dodatni podaci'> </div>
          <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>
        </div>
      </div>
    </div>`);

    // Disable inputs until dates are selected
    $("#form_group .secondary").css("opacity", "0.5");
    $("#form_group .secondary").css("pointer-events", "none");
});


$("#form_group_confirm").click(function(){
  // Loaders
  $("#form_group_confirm, #form_group_cancel").addClass("button_loader");
  // Parameters
  var id = $("#form_group_id").val();
  var date_arrival = date_to_iso($("#form_group_dfrom").datepicker().data('datepicker').selectedDates[0]);
  var date_departure = date_to_iso($("#form_group_dto").datepicker().data('datepicker').selectedDates[0]);
  var rooms = [];
  $("#form_group_rooms .room").each(function(){
    let room = {};
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    room.id = id;
    room.count = $(`#form_group_room_number_${id}`).val();
    room.price =$(`#form_group_room_price_${id}`).val();
    room.room_numbers = [];
    if(room.count > 0)
    rooms.push(room);
  });
  rooms = JSON.stringify(rooms);
  var adults = $("#form_group_adults").val();
  var children = $("#form_group_children").val();
  let channel = $("#form_group_channel").val();
  let guests = [];
  $(".form_group_guest").each(function(){
    let guest = {};
    guest["id"] = $(this).find(".form_group_guest_id")[0].value;
    guest["name"] = $(this).find(".form_group_guest_name")[0].value;
    guest["surname"] = $(this).find(".form_group_guest_surname")[0].value;
    guest["email"] = $(this).find(".form_group_guest_email")[0].value;
    guest["phone"] = $(this).find(".form_group_guest_phone")[0].value;
    guests.push(guest);
  });
  guests = JSON.stringify(guests);
  let discount = {};
  discount["type"] = $("#form_group_discount_type").val();
  discount["value"] = $("#form_group_discount_value").val();
  discount = JSON.stringify(discount);
  let avans = {};
  avans["type"] = $("#form_group_avans_type").val();
  avans["value"] = $("#form_group_avans_value").val();
  avans = JSON.stringify(avans);
  let services = [];
  $(".form_group_service").each(function(){
    let service = {};
    service["name"] = $(this).find(".form_group_service_name")[0].value;
    service["price"] = parseFloat($(this).find(".form_group_service_price")[0].value);
    service["amount"] = parseFloat($(this).find(".form_group_service_amount")[0].value);
    service["tax"] = parseFloat($(this).find(".form_group_service_tax")[0].value);
    services.push(service);
  });
  services = JSON.stringify(services);
  var total_price = $("#form_group_total_price").val();
  let note = $("#form_group_note").val();
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
      $("#form_group_cancel").click();
      hash_change();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

// Date inputs

$('#form_group_dfrom').datepicker().data('datepicker').update(
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
          $('#form_group_dto').datepicker().data('datepicker').show();
        }
        else {
          var minDate = new Date();
        }
        minDate.setDate(minDate.getDate() + 1);
        $('#form_group_dto').datepicker().data('datepicker').update({
          minDate: minDate
        });
        if($("#form_group_dfrom").val() !== "" && $("#form_group_dto").val() !== "")
        {
          var dolazak_date = $('#form_group_dfrom').datepicker().data('datepicker').selectedDates[0];
          var odlazak_date = $('#form_group_dto').datepicker().data('datepicker').selectedDates[0];
          dolazak_date = date_to_iso(dolazak_date);
          odlazak_date = date_to_iso(odlazak_date);
          $("#form_group_nights").val(`${num_of_nights(dolazak_date, odlazak_date)} noći`);
          get_group_rooms();
        }
        else {
          $("#form_group .secondary").css("opacity", "0.5");
          $("#form_group .secondary").css("pointer-events", "none");
        }
      }
    }
  }
);
$('#form_group_dto').datepicker().data('datepicker').update(
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
        $('#form_group_dfrom').datepicker().data('datepicker').update({
          maxDate: maxDate
        });
        if($("#form_group_dfrom").val() !== "" && $("#form_group_dto").val() !== "")
        {
          var dolazak_date = $('#form_group_dfrom').datepicker().data('datepicker').selectedDates[0];
          var odlazak_date = $('#form_group_dto').datepicker().data('datepicker').selectedDates[0];
          dolazak_date = date_to_iso(dolazak_date);
          odlazak_date = date_to_iso(odlazak_date);
          $("#form_group_nights").val(`${num_of_nights(dolazak_date, odlazak_date)} noći`);
          get_group_rooms();
        }
        else {
          $("#form_group_room").prop("selectedIndex", 0).change();
          $("#form_group .secondary").css("opacity", "0.5");
          $("#form_group .secondary").css("pointer-events", "none");
        }
      }
    }
  }
);

function get_group_rooms(){
  $.ajax({
    url: api_link + 'data/groupResRooms',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: date_to_iso($('#form_group_dfrom').datepicker().data('datepicker').selectedDates[0]),
            dto: date_to_iso($('#form_group_dto').datepicker().data('datepicker').selectedDates[0]),
            pid: $("#form_group_pid").val()
          },
    success: function(rezultat){
      console.log(rezultat);
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      $("#form_group_rooms .room").remove();
      $("#form_group_room_others").remove();
      $(".form_group_room_numbers").remove();
      for(let i=0;i<sve.rooms.length;i++){
        let room = sve.rooms[i];
        $("#form_group_rooms").append(
          `<div class='room' id='form_group_room_${room.id}'>
            <div class='form_group_room_room'>
              <div> ${room.name} </div>
              <div>
                Raspoloživo: <span id='form_group_avail_${room.id}'>${room.avail}</span>
              </div>
            </div>
            <div class='form_group_room_number'>
              <div class='vert_center'>
                <div> Broj </div>
                <div class='flex_center'>
                  <div class='number_input_minus' id='form_group_room_number_${room.id}_minus'>-</div>
                  <input type='number' min=0 class='number_input form_group_room_count' id='form_group_room_number_${room.id}' value=0>
                  <div class='number_input_plus' id='form_group_room_number_${room.id}_plus'>+</div>
                </div>
              </div>
            </div>
            <div class='form_group_room_price'>
              <div class='vert_center'>
                <div> Cijena noćenja </div>
                <input type='number' class='number_input' id='form_group_room_price_${room.id}' value=${(parseFloat(room.price)).toFixed(2)}>
              </div>
            </div>
            <div class='form_group_room_total'>
              <div class='vert_center'>
                <div> Ukupno </div>
                <input type='number' class='number_input form_readonly' readonly id='form_group_room_total_${room.id}'>
              </div>
            </div>
          </div>
          <div class='form_group_room_numbers' id='form_group_room_numbers_${room.id}'>
          </div>`);
        let room_numbers = room.room_numbers;
        for(let j=0;j<room_numbers.length;j++){
          continue;
          $(`#form_group_room_numbers_${room.id}`).append(create_checkbox(`form_group_room_numbers_${room.id}_${j}`, 0, room_numbers[j].name));
        }
      }
      if($("#form_group_id").val() != ""){
        let res = all_reservations[$("#form_group_id").val()];
        for(let i=0;i<res.room_data.length;i++){
          let room = res.room_data[i];
          $("#form_group_room_number_" + room.id).val(room.count);
          $("#form_group_room_price_" + room.id).val(room.price);
        }
        for(let i=0;i<rooms_list.length;i++){
          if($("#form_group_room_number_" + rooms_list[i]).val() == 0){
            $("#form_group_room_number_" + rooms_list[i]).closest(".room").hide();
          }
        }
        $("#form_group_rooms").after(`<button id='form_group_room_others' class='add_button' style='margin:auto;display:block;margin-top:20px;'> + </button>`);
      }
      group_price_update();
      $("#form_group .secondary").css("opacity", "");
      $("#form_group .secondary").css("pointer-events", "");
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

$("body").on("click", "#form_group_room_others", function(){
  $(this).remove();
  $("#form_group_rooms .room").show();
});

// Add guest
$("#form_group_new_guest").click(function(){
  $("#form_group_guests").append(`
    <div class='flex_around form_group_guest' id='form_group_guest_${+(new Date())}'>
      <input type='hidden' class='form_group_guest_id' value=''>
      <div class='vert_center'>
        <div> Ime </div>
        <input type='text' class='text_input form_group_guest_name'>
      </div>
      <div class='vert_center'>
        <div> Prezime </div>
        <input type='text' class='text_input form_group_guest_surname'>
      </div>
      <div class='vert_center'>
        <div> Email </div>
        <input type='text' class='text_input form_group_guest_email'>
      </div>
      <div class='vert_center'>
        <div> Telefon </div>
        <input type='text' class='text_input form_group_guest_phone'>
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
$("#form_group_guests").on("click", ".delete", function(){
  if($(".form_group_guest").length == 1){
    $(".form_group_guest_id").val("");
    $(".form_group_guest_name").val("");
    $(".form_group_guest_surname").val("");
    $(".form_group_guest_email").val("");
    $(".form_group_guest_phone").val("");
  }
  else {
    $(this).closest(".form_group_guest").remove();
  }
});
// Add service
$("#form_group_new_service").click(function(){
  $("#form_group_services").append(`
    <div class='flex_around form_group_service'>
      <div class='vert_center'>
        <div> Naziv usluge </div>
        <input type='text' class='text_input form_group_service_name'>
      </div>
      <div class='vert_center'>
        <div> Cijena </div>
        <input type='number' class='number_input form_group_service_price' value=0>
      </div>
      <div class='vert_center'>
        <div> Količina </div>
        <input type='number' class='number_input form_group_service_amount' value=1>
      </div>
      <div class='vert_center'>
        <div> Porez (%) </div>
        <input type='number' class='number_input form_group_service_tax' value=0>
      </div>
      <div class='vert_center'>
        <div> Ukupna cijena </div>
        <input type='number' class='number_input form_group_service_total form_readonly' readonly value=0>
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
$("#form_group_services").on("click", ".delete", function(){
  $(this).closest(".form_group_service").remove();
});

// Number inputs
$("#form_group_rooms").on("input", ".number_input", group_price_update);
$("#form_group_discount_value").on("input", group_price_update);
$("#form_group_avans_value").on("input", group_price_update);
$("#form_group_discount_type").change(group_price_update);
$("#form_group_avans_type").change(group_price_update);
$("#form_group_services").on("input", ".number_input", group_price_update);

$("#form_group_rooms").on("change", ".form_group_room_count", function(){
  let val = $(this).val();
  let id = $(this)[0].id.split("_");
  id = id[id.length - 1];
  let avail = $("#form_group_avail_" + id).text();
  if(val > parseInt(avail)){
    val = parseInt(avail);
  }
  $(this).val(val);
  group_price_update();
});


});

var group_price_update = function()
{

  var nights = $("#form_group_nights").val().split(" ")[0];
  if(nights === "")
    nights = 0;
  else
    nights = parseFloat(nights);

  amount = 0;
  $("#form_group_rooms .room").each(function(){
    let id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    var room_avail = parseInt($("#form_group_room_avail_" + id).text());
    var room_count = parseFloat($("#form_group_room_number_"+id).val());
    var room_price = parseFloat($("#form_group_room_price_"+id).val());
    if(room_count > room_avail)
      room_count = room_avail;
    if(room_count < 0 || isNaN(room_count))
      room_count = 0;
    if(room_price < 0 || isNaN(room_price))
      room_price = 0;
    room_total = room_count * room_price * nights;
    if(room_count>0)
    {
      $("#form_group_room_" + id).addClass("selected");
      $("#form_group_room_" + id + "_numbers").show();
    }
    else
    {
      $("#form_group_room_" + id).removeClass("selected");
      $("#form_group_room_" + id + "_numbers").hide();
    }
    $("#form_group_room_number_"+id).val(room_count);
    $("#form_group_room_price_"+id).val(room_price);
    $("#form_group_room_total_"+id).val(room_total.toFixed(2));
    amount += room_total;
  });
  if(amount < 0 || isNaN(amount))
    amount = 0;
  var discount = parseFloat($("#form_group_discount_value").val());
  if(discount < 0 || isNaN(discount))
    discount = 0;
  var discount_type = $("#form_group_discount_type").val();
  var avans = parseFloat($("#form_group_avans_value").val());
  if(avans < 0 || isNaN(avans))
    avans = 0;
  var avans_type = $("#form_group_avans_type").val();

  var discounted_price = amount;
  if(discount_type == 1)
  {
    if(discount > 100)
      discount = 100;
    discounted_price = amount - amount * discount/100;
  }
  if(discount_type == 2)
  {
    if((discount*nights) > amount)
      discount = amount/nights;
    discount = isNaN(discount) ? 0 : discount;
    discounted_price = amount - (discount * nights);
  }
  if(discount_type == 3)
  {
    if(discount > amount)
      discount = amount;
    discounted_price = amount - discount;
  }
  var services_price = 0;
  $(".form_group_service").each(function(){
    var number = parseFloat($(this).find(".form_group_service_amount")[0].value);
    var price = parseFloat($(this).find(".form_group_service_price")[0].value);
    var tax = parseFloat($(this).find(".form_group_service_tax")[0].value);
    if(number < 0 || isNaN(number))
      number = 0;
    if(price < 0 || isNaN(price))
      price = 0;
    if(tax < 0 || isNaN(tax))
      tax = 0;

      console.log(number, price, tax);
    var total = (number*price) + (number*price)*tax/100;
    total = isNaN(total) ? 0 : total;
    $(this).find(".form_group_service_amount").val(number);
    $(this).find(".form_group_service_price").val(price);
    $(this).find(".form_group_service_tax").val(tax);
    $(this).find(".form_group_service_total").val(total.toFixed(2));
    services_price += total;
  });
  var total_price = services_price + discounted_price;

  var avans_percent = 0;
  var avans_amount = 0;
  if(avans_type == 1)
  {
    if(avans > 100)
      avans = 100;
    avans_amount = (discounted_price/100) * avans;
    avans_percent = avans;
    $("#form_group_avans_value").val(avans_percent);
    $("#form_group_avans_text").text("(" + avans_amount + " " + currency + ")");
  }
  if(avans_type == 3)
  {
    if(avans > discounted_price)
      avans = discounted_price;
    avans_amount = avans;
    avans_percent = 100 - (discounted_price - avans) * 100 / discounted_price;
    $("#form_group_avans_value").val(avans_amount);
    avans_percent = isNaN(avans_percent) ? 0 : avans_percent.toFixed(2);
    $("#form_group_avans_text").text("(" + avans_percent + " %)");
  }
  var remaining_amount = discounted_price - avans_amount;
  var remaining_percent = 100 - avans_percent;

  var commission = 0;
  if(channels_map[$("#form_group_channel").val()] != undefined)
    commission = parseFloat(channels_map[$("#form_group_channel").val()].commission);
  commission_amount = (total_price/100)*commission;
  $("#form_group_commission_text").text(commission.toFixed(2));
  $("#form_group_commission_amount").val(commission_amount.toFixed(2));
  $("#form_group_amount").val(amount);
  $("#form_group_discount_value").val(discount);
  $("#form_group_discounted_price").val(discounted_price.toFixed(2));
  $("#form_group_services_price").val(services_price.toFixed(2));
  $("#form_group_remaining_amount").val(remaining_amount.toFixed(2));
  $("#form_group_total_price").val(total_price.toFixed(2));
  $("#form_group_total_paid").val(avans_amount.toFixed(2));
  $("#form_group_total_remaining").val((total_price.toFixed(2) - avans_amount.toFixed(2)).toFixed(2));

};
