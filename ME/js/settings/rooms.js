$(document).ready(function(){




// Rooms

// Input changes
function room_virtual_change(){ // Show / hide linked room form
  let val = $("#room_settings_virtual").attr("data-value");
  if(val == 1){ // Clear values on show
    set_checkbox("room_settings_virtual_avail", 0);
    set_checkbox("room_settings_virtual_price", 0);
    room_virtual_price_change(); // Show / hide price variation form
    set_checkbox("room_settings_virtual_restriction", 0);
    $("#room_settings_virtual_inputs").fadeIn(200);
  }
  else {
    $("#room_settings_virtual_inputs").hide();
  }
}
function room_virtual_price_change(){ // Show / hide price variation form
  let val = $("#room_settings_virtual_price").attr("data-value");
  if(val == 1){ // Clear values on show
    $("#room_settings_virtual_sign").prop("selectedIndex", 0).change();
    $("#room_settings_virtual_value").val(0);
    $("#room_settings_virtual_variation").prop("selectedIndex", 0).change();
    $("#room_settings_virtual_sign").prop("selectedIndex", 0).change();
    $("#room_settings_virtual_price_container").show();
  }
  else {
    $("#room_settings_virtual_price_container").hide();
  }
};
function room_variations_change(){ // Show / hide additional prices form
  let val = $("#room_settings_variations").attr("data-value");
  if(val == 1){
    let num = $("#room_settings_occupancy").val();
    $("#room_settings_variations_default option").remove();
    for(let q=1;q<=num;q++){
      $("#room_settings_variations_default").append(`<option value=${q}> ${q} osoba </option>`);
    }
    $("#room_settings_variations_inputs").fadeIn(200);
  }
  else {
    $("#room_settings_variations_inputs").hide();
  }
}
$("#room_settings_availability").change(function(){ // Append inputs for room numbers on availability change
  let val = $(this).val();
  if(isNaN(val) || val == '' || val < 0){ // Value must be a positive number
    val = 0;
    $(this).val(val);
  }
  $("#room_settings_numbers").empty(); // Clear old inputs
  for(let i=0;i<val;i++){ // Append inputs with default values
    $("#room_settings_numbers").append(`<input type='text' class='number_input' id='room_settings_number_${i}' value='${i+1}'>`);
  }
  let room_id = $("#room_settings_active").val();
  if(room_id != -1){ // Fill existing room numbers
    let room_numbers = rooms_map[room_id].room_numbers;
    for(let i=0;i<room_numbers.length;i++){
      $(`#room_settings_number_${i}`).val(room_numbers[i]);
    }
  }
});
$("#room_settings_houserooms").change(function(){ // Append inputs for houserooms
  let val = $(this).val();
  if(isNaN(val) || val == "" || val < 0){  // Value must be a positive number
    val = 0;
    $(this).val(val);
  }
  $("#room_settings_houserooms_inputs").empty(); // Clear old inputs
  for(let i=0;i<val;i++){ // Append inputs
    $("#room_settings_houserooms_inputs").append(`
      <div class='houseroom flex_between'>
        <div class="vert_center">
          <div> Tip prostorije </div>
          <input type='text' class='text_input' id='houseroom_name_${i}'>
        </div>
        <div class="vert_center">
          <div> Broj kreveta </div>
          <input type='number' class='number_input houseroom_beds' id='houseroom_beds_${i}'>
        </div>
      </div>`);
  }
});
$("#room_settings_houserooms_inputs").on("change", ".houseroom_beds", function(){ // Append inputs for beds of houseroom

  let val = $(this).val();
  if(isNaN(val) || val == "" || val < 0){  // Value must be a positive number
    val = 0;
    $(this).val(val);
  }
  let id = $(this)[0].id.split("_")[2];
  $(`.houseroom_beds_${id}`).remove(); // Clear old inputs
  for(let i=0;i<val;i++){ // Append inputs
    $(this).closest('.houseroom').after(`
      <div class='vert_center houseroom_beds_${id}'>
      <div> Tip kreveta </div>
      <input type='text' class='text_input' id='houseroom_bed_${id}_${i}'>
      </div>`);
  }

});
$("#room_settings_virtual").click(room_virtual_change); // On checkbox click
$("#room_settings_variations").click(room_variations_change); // On checkbox click
$("#room_settings_occupancy").change(function(){ // Check value and update additional prices data
  let val = $(this).val();
  if(isNaN(val) || val == "" || val < 0){  // Value must be a positive number
    val = 0;
    $(this).val(val);
  }
  room_variations_change();
});
$("#room_settings_virtual_price").click(room_virtual_price_change); // On checkbox click
// Images
$("body").on("click", ".room_image_input", function(){
  let id = $(this)[0].id;
  $(`#${id}_file`).click();
});
$("body").on("change", ".room_image_file", function(){
  let id = $(this)[0].id.split("_");
  id.pop();
  id = id.join("_");
  let obj = $(this)[0];
  if(obj.files && obj.files[0]) {

      for(let i=0;i<obj.files.length;i++){
        let reader = new FileReader();
        reader.onload = function(e) {
          $(`#${id}_container`).prepend(`<img src='${e.target.result}' class='room_image_image'>`)
        }
        reader.readAsDataURL(obj.files[i]);
      }
      $(`#${id}`).closest(".room_image").addClass("file");
      $(`#${id}`).remove();
      // Add new input
      let image_id = new Date() / 1; // ID of each img has to be different
      $(`#${id}_container`).append(`<div class="room_image_cancel"> Ukloni </div>`);
      $("#room_settings_images").append(`
        <div class='room_image'>
          <div class='room_image_container' id='new_room_image_${image_id}_container'> </div>
          <input type='file' class='room_image_file' id='new_room_image_${image_id}_file' multiple>
          <button class='add_button room_image_input' id='new_room_image_${image_id}'> + </button>
        </div>`);
  }
});
$("body").on("click", ".room_image_cancel", function(){
  $(this).closest(".room_image").remove();
});

// New
$("#new_room").click(function(){ // Clear values and show form

  $("#room_settings_header").hide(); // Hide edit select
  $("#new_room").hide(); // Hide button

  $("#room_settings_inputs").fadeIn(200);
  $("#room_settings_inputs .text_input").val('');
  $("#room_settings_inputs .number_input").val(0);
  $("#room_settings_type").prop("selectedIndex", 0).change();
  set_checkbox("room_settings_engine", 0);
  $("#room_settings_houserooms").change();
  set_checkbox("room_settings_virtual", 0);
  room_virtual_change();
  set_checkbox("room_settings_variations", 0);
  room_variations_change();
  $("#room_settings_description").val('');
  $("#room_settings_amenities").val([]).change();

  $("#room_settings_images").empty();
  let image_id = new Date() / 1; // ID of each img has to be different
  $("#room_settings_images").append(`
    <div class='room_image'>
      <div class='room_image_container' id='new_room_image_${image_id}_container'> </div>
      <input type='file' class='room_image_file' id='new_room_image_${image_id}_file' multiple>
      <button class='add_button room_image_input' id='new_room_image_${image_id}'> + </button>
    </div>`);
});
// Edit
$("#room_settings_active").change(function(){
  let id = $(this).val();
  if(id == -1){ // Hide form and show new room button
    $("#room_settings_header").show();
    $("#room_settings_inputs").hide();
    $("#new_room").fadeIn(200);
  }
  else {
    $("#new_room").hide();
    $("#room_settings_inputs").fadeIn(200);
    let room = rooms_map[id];
    let houserooms = room.houserooms;
    let virtual = room.linked_room;
    let variations = room.additional_prices;
    $("#room_settings_name").val(room.name);
    $("#room_settings_shortname").val(room.shortname);
    $("#room_settings_type").val(room.type).change();
    $("#room_settings_price").val(room.price);
    $("#room_settings_availability").val(room.availability).change();
    set_checkbox("room_settings_engine", room.booking_engine);
    $("#room_settings_occupancy").val(room.occupancy);
    $("#room_settings_area").val(room.area);
    $("#room_settings_bathrooms").val(room.bathrooms);
    $("#room_settings_houserooms").val(houserooms.length == 0 ? 0 : houserooms.length).change();
    for(let i=0;i<houserooms.length;i++){
      let houseroom = houserooms[i];
      $(`#houseroom_name_${i}`).val(houseroom.name);
      $(`#houseroom_beds_${i}`).val(houseroom.beds.length).change();
      for(let j=0;j<houseroom.beds.length;j++){
        $(`#houseroom_bed_${i}_${j}`).val(houseroom.beds[j]);
      }
    }
    set_checkbox("room_settings_virtual", virtual.active);
    $("#room_settings_virtual_parent").val(virtual.parent).change();
    room_virtual_change();
    set_checkbox("room_settings_virtual_avail", virtual.avail);
    set_checkbox("room_settings_virtual_price", virtual.price);
    room_virtual_price_change();
    $("#room_settings_virtual_sign").val(virtual.variation < 0 ? -1 : 1).change();
    $("#room_settings_virtual_value").val(Math.abs(virtual.variation));
    $("#room_settings_virtual_variation").val(virtual.variation_type).change();
    set_checkbox("room_settings_virtual_restrictions", virtual.restrictions);
    set_checkbox("room_settings_variations", variations.active);
    room_variations_change();
    $("#room_settings_variations_default").val(variations.default).change();
    $("#room_settings_variations_value").val(Math.abs(variations.variation));
    $("#room_settings_variations_variation").val(variations.variation_type).change();
    $("#room_settings_description").val(room.description);
    $("#room_settings_amenities").val(room.amenities).change();

    $("#room_settings_images").empty();
    for(let i=0;i<room.images.length;i++){
      $("#room_settings_images").append(`
        <div class='room_image link'>
          <div class='room_image_container'>
            <img src='${room.images[i]}' class='room_image_image'>
            <div class="room_image_cancel"> Ukloni </div>
          </div>
        </div>`);
    }
    let image_id = new Date() / 1; // ID of each img has to be different
    $("#room_settings_images").append(`
      <div class='room_image'>
        <div class='room_image_container' id='new_room_image_${image_id}_container'> </div>
        <input type='file' class='room_image_file' id='new_room_image_${image_id}_file' multiple>
        <button class='add_button room_image_input' id='new_room_image_${image_id}'> + </button>
      </div>`);
  }
});
// Cancel
$("#room_settings_cancel").click(function(){
  $("#room_settings_inputs").hide();
  $("#room_settings_active").val(-1).change();
});
// Update
$("#room_settings_confirm").click(function(){
  // Loaders
  $("#room_settings_confirm, #room_settings_cancel").addClass("button_loader");
  // Parameters
  let id = $("#room_settings_active").val();
  let name = $("#room_settings_name").val();
  let shortname = $("#room_settings_shortname").val();
  let type = $("#room_settings_type").val();
  let price = $("#room_settings_price").val();
  let availability = $("#room_settings_availability").val();
  let booking_engine = $("#room_settings_engine").attr('data-value');
  let occupancy = $("#room_settings_occupancy").val();
  let area = $("#room_settings_area").val();
  let bathrooms = $("#room_settings_bathrooms").val();
  let houserooms = $("#room_settings_houserooms").val();
  let houserooms_struct = [];
  for(let i=0;i<houserooms;i++){
    let houseroom_struct = {};
    houseroom_struct.name = $(`#houseroom_name_${i}`).val();
    let beds = $(`#houseroom_beds_${i}`).val();
    if(isNaN(beds) || beds == "" || beds < 0)
      beds = 0;
    let beds_struct = [];
    for(let j=0;j<beds;j++){
      beds_struct.push($(`#houseroom_bed_${i}_${j}`).val());
    }
    houseroom_struct.beds = beds_struct;
    houserooms_struct.push(houseroom_struct);
  }
  let virtual_struct = {};
  virtual_struct.active = $("#room_settings_virtual").attr('data-value');
  virtual_struct.parent = $("#room_settings_virtual_parent").val();
  virtual_struct.avail = $("#room_settings_virtual_avail").attr('data-value');
  virtual_struct.price = $("#room_settings_virtual_price").attr('data-value');
  virtual_struct.variation_type = $("#room_settings_virtual_variation").val();
  virtual_struct.variation = $("#room_settings_virtual_sign").val() * $("#room_settings_virtual_value").val();
  virtual_struct.restrictions = $("#room_settings_virtual_restrictions").attr('data-value');

  let variations_struct = {};
  variations_struct.active = $("#room_settings_variations").attr('data-value');
  variations_struct.default = $("#room_settings_variations_default").val();
  variations_struct.variation = $("#room_settings_variations_value").val();
  variations_struct.variation_type = $("#room_settings_variations_variation").val();

  let room_numbers = [];
  for(let i=0;i<availability;i++){
    room_numbers.push($(`#room_settings_number_${i}`).val());
  }
  room_numbers = room_numbers.join(",");
  let description = $("#room_settings_description").val();
  let amenities = $("#room_settings_amenities").val();
  let action = id == -1 ? "insert" : "edit"; // Edit or insert

  // Formating and adding images
  let formData = new FormData();
  formData.append("key", main_key);
  formData.append("account", account_name);
  formData.append("lcode", main_lcode);
  formData.append("id", id);
  formData.append("name", name);
  formData.append("shortname", shortname);
  formData.append("type", type);
  formData.append("price", price);
  formData.append("availability", availability);
  formData.append("booking_engine", booking_engine);
  formData.append("occupancy", occupancy);
  formData.append("area", area);
  formData.append("bathrooms", bathrooms);
  formData.append("houserooms", JSON.stringify(houserooms_struct));
  formData.append("linked_room", JSON.stringify(virtual_struct));
  formData.append("additional_prices", JSON.stringify(variations_struct));
  formData.append("room_numbers", room_numbers);
  formData.append("description", description);
  formData.append("amenities", JSON.stringify(amenities));
  let image_links = [];
  let files_count = 0;
  $(".room_image").each(function(){
    if($(this).hasClass("link")){
      image_links.push($(this).find("img").attr("src"));
    }
    if($(this).hasClass("file")){
      for(let i=0;i < $(this).find(".room_image_file")[0].files.length;i++){
        let file = $(this).find(".room_image_file")[0].files[i];
        formData.append(`file_${files_count}`, file);
        files_count += 1;
      }
    }
  });
  formData.append("image_links", JSON.stringify(image_links));
  formData.append("files_count", files_count);

  $.ajax({
    type: 'POST',
    url: api_link + `${action}/room`,
    data: formData,
    processData: false,
    contentType: false,
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      let change_text = id == -1 ? `Dodata jedinica ${name} ` : `Izmjenjena jedinica ${name}`; // Edit or insert
      add_change(change_text, sve.data.id); // Add changelog
      get_rooms(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

//  All amenities
$("#all_rooms_amenities_confirm").click(function(){
  let amenities = $("#all_rooms_amenities").val();
  amenities = JSON.stringify(amenities);
  $("#all_rooms_amenities_confirm").addClass("button_loader");
  $.ajax({
    type: 'POST',
    url: api_link + `edit/amenities`,
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      amenities: amenities
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      $("#all_rooms_amenities").val([]).change();
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change("Dodati sadržaji za sve jedinice", -1); // Add changelog
      get_rooms(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});


// Extras

// New
function extra_period_type_change(){ // Show correct period inputs
  let val = $("#new_extra_period_type").attr("data-value");
  if(val == 1){
    $("#new_extra_dates").hide();
    $("#new_extra_restriction").prop("selectedIndex", 0).change();
    $("#new_extra_restrictions").fadeIn(200);
  }
  else {
    $("#new_extra_restrictions").hide();
    $("#new_extra_dfrom").datepicker().data('datepicker').clear();
    $("#new_extra_dto").datepicker().data('datepicker').clear();
    $("#new_extra_dates").fadeIn(200);
  }
}
$("#new_extra_period_type").click(extra_period_type_change); // On checkbox click
$("#new_extra_type").change(function(){ // Show / Hide daily input
  let val = $(this).val();
  if(val == 'one') {
    $("#new_extra_daily_container").hide();
    set_switch("new_extra_daily", 0);
  }
  else {
    $("#new_extra_daily_container").fadeIn(200);
  }
});
$("#new_extra_predefined").change(function(){ // Add predefined values
  let val = $(this).val();
  if(val == -1){
    $("#new_extra_name").val("");
    $("#new_extra_variation").prop("selectedIndex", 0).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").prop("selectedIndex", 0).change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
  }
  else if(val == 1){ // Dorucak
    $("#new_extra_name").val("Doručak");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("person").change();
    set_checkbox("new_extra_daily", 1);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/Breakfast.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 2){ // Polupansion
    $("#new_extra_name").val("Polupansion");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("person").change();
    set_checkbox("new_extra_daily", 1);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/halfboard.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 3){ // Pun pansion
    $("#new_extra_name").val("Pun pansion");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("person").change();
    set_checkbox("new_extra_daily", 1);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/fullboard.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 4){ // Parking
    $("#new_extra_name").val("Parking");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/Parking.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 5){ // Spa centar
    $("#new_extra_name").val("Spa centar");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/spacenter.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 6){ // Sauna
    $("#new_extra_name").val("Sauna");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/sauna.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 7){ // Prevoz od aerodroma
    $("#new_extra_name").val("Prevoz od aerodroma");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/AirportTransfer.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 8){ // Prevoz od aerodroma (povratni)
    $("#new_extra_name").val("Prevoz od aerodroma (povratni)");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/AirportTransfer.jpg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 9){ // Prevoz brodom
    $("#new_extra_name").val("Prevoz brodom");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/boatTransfer.jpeg");
    $(`#new_extra_image`).hide();
  }
  else if(val == 10){ // Ulaznica u klub
    $("#new_extra_name").val("Ulaznica u klub");
    $("#new_extra_variation").val(1).change();
    $("#new_extra_price").val(0);
    $("#new_extra_tax").val(0);
    $("#new_extra_type").val("one").change();
    set_checkbox("new_extra_daily", 0);
    $("#new_extra_description").val("");
    set_checkbox("new_extra_period_type", 0);
    extra_period_type_change();
    $("#new_extra_rooms").val([]).change();
    $("#new_extra_image_file").val("").change();
    $(`#new_extra_image_container`).show();
    $(`#new_extra_image_container .image_input_image`).attr('src', "https://admin.otasync.me/beta/img/extras/ClubTicket.jpg");
    $(`#new_extra_image`).hide();
  }
});
$("#new_extra").click(function(){ // Clear values and show form

  $("#new_extra_name").val("");
  $("#new_extra_predefined").prop("selectedIndex", 0).change();
  $("#new_extra_variation").prop("selectedIndex", 0).change();
  $("#new_extra_price").val(0);
  $("#new_extra_tax").val(0);
  $("#new_extra_type").prop("selectedIndex", 0).change();
  set_checkbox("new_extra_daily", 0);
  $("#new_extra_description").val("");
  set_checkbox("new_extra_period_type", 0);
  extra_period_type_change();
  $("#new_extra_rooms").val([]).change();
  $("#new_extra_specific_rooms").val([]).change();
  $("#new_extra_image_file").val("").change();

  $("#new_extra_container").show();
  $("#new_extra").hide();

  // Hide open edits
  $("#edit_extra_container").remove();
  $(".extra.selected").removeClass("selected");
});
$("#new_extra_cancel").click(function(){  // Hide form
  $("#new_extra_container").hide();
  $("#new_extra").show();
});
$("body").on("click", "#new_extra_confirm", function(){  // Insert new
  // Loaders
  $("#new_extra_confirm, #new_extra_cancel").addClass("button_loader");
  // Parameters
  let name = $("#new_extra_name").val();
  let variation = $("#new_extra_variation").val();
  let price = $("#new_extra_price").val();
  let tax = $("#new_extra_tax").val();
  let type = $("#new_extra_type").val();
  let daily = $("#new_extra_daily").attr("data-value");
  if(type == 'one')
    daily = 0;
  let description = $("#new_extra_description").val();
  let dfrom = '0001-01-01';
  let dto = '0001-01-01';
  let restriction = '0';
  if($("#new_extra_period_type").attr("data-value") == 1){
    restriction = $("#new_extra_restriction").val();
  }
  else {
    dfrom = date_to_iso($("#new_extra_dfrom").datepicker().data('datepicker').selectedDates[0]);
    dto = date_to_iso($("#new_extra_dto").datepicker().data('datepicker').selectedDates[0]);
  }
  let rooms = $("#new_extra_rooms").val();
  let specific_rooms = $("#new_extra_specific_rooms").val();
  // Form data with image
  var formData = new FormData();
  formData.append("key", main_key);
  formData.append("account", account_name);
  formData.append("lcode", main_lcode);
  formData.append("name", name);
  formData.append("pricing", variation);
  formData.append("price", price);
  formData.append("tax", tax);
  formData.append("type", type);
  formData.append("daily", daily);
  formData.append("description", description);
  formData.append("dfrom", dfrom);
  formData.append("dto", dto);
  formData.append("restriction", restriction);
  formData.append("rooms", JSON.stringify(rooms));
  formData.append("specific_rooms", JSON.stringify(specific_rooms));
  var file = $("#new_extra_image_file")[0].files[0];
  if (!file){
    formData.append("img_link", 1);
    if($("#new_extra_predefined").val() != -1){
      formData.append("image", $("#new_extra_image_container img").attr("src"));
    }
  }
  else {
    formData.append("image", file);
  }
  // Call
  $.ajax({
    type: 'POST',
    url: api_link + 'insert/extra',
    data: formData,
    processData: false,
    contentType: false,
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change(`Dodat dodatak ${name}`, sve.data.id); // Add changelog
      get_extras(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});
// Edit
function edit_extra_period_type_change(){ // Show correct period inputs without clearing values
  let val = $("#edit_extra_period_type").attr("data-value");
  if(val == 1){
    $("#edit_extra_dates").hide();
    $("#edit_extra_restrictions").fadeIn(200);
  }
  else {
    $("#edit_extra_restrictions").hide();
    $("#edit_extra_dates").fadeIn(200);
  }
}
$("body").on("click", "#edit_extra_period_type", edit_extra_period_type_change); // On checkbox click
$("body").on("change", "#edit_extra_type", function(){ // Show / Hide daily input without clearing
  let val = $(this).val();
  if(val == 'one') {
    $("#edit_extra_daily_container").hide();
  }
  else {
    $("#edit_extra_daily_container").fadeIn(200);
  }
});
$("#extras_list").on("click", ".edit", function(){
  let row_id = $(this).closest(".extra")[0].id;
  if($("#edit_extra_container").length && $(`#${row_id}`).hasClass("selected")){ // This plan edit is open
    return;
  }
  else {
    $("#edit_extra_container").remove();
    $(".extra.selected").removeClass("selected");
    $(`#${row_id}`).addClass("selected");
    // Plan id
    let id = row_id.split("_");
    id = id[id.length - 1];
    let extra = extras_map[id];
    $(`#${row_id}`).after(`
      <div id='edit_extra_container'>
        <input type='hidden' id='edit_extra_id' value='${id}'>
        <div class='flex_between'>
          <div class="vert_center">
            <div>Naziv</div>
            <input type='text' class='text_input' id='edit_extra_name' value='${extra.name}'>
          </div>
        </div>
        <div class='flex_between'>
          <div class="vert_center">
            <div>Varijacija</div>
            <select class='basic_select' id='edit_extra_variation'>
              <option value=-1> Smanjenje </option>
              <option value=1> Povećanje </option>
            </select>
          </div>
          <div class="vert_center">
            <div>Cijena</div>
            <input type='number' class='number_input' id='edit_extra_price' value='${extra.price}'>
          </div>
        </div>
        <div class="flex_between">
          <div>Porez (%)</div>
          <input type='number' class='number_input' id='edit_extra_tax' value='${extra.tax}'>
        </div>
        <div class="flex_between">
          <div class="vert_center">
            <div> Tip </div>
            <select class='basic_select' id='edit_extra_type'>
              <option value='room'> Po sobi </option>
              <option value='person'> Po osobi </option>
              <option value='one'> Jednokratno </option>
            </select>
          </div>
          <div class="flex_center" id='edit_extra_daily_container'>
            <div class="custom_checkbox dynamic" id="edit_extra_daily" data-value="0"> <img class="checkbox_value"> </div>
            Dnevno
          </div>
        </div>
        <div class='flex_between'>
          <div>Uključeno u cenu</div>
          <select class='basic_select real_room_select' id='edit_extra_rooms' multiple='multiple'>
          </select>
        </div>
        <div class='flex_between'>
          <div>Samo za određene jedinice</div>
          <select class='basic_select real_room_select' id='edit_extra_specific_rooms' multiple='multiple'>
          </select>
        </div>
        <div class="flex_between">
          <div> Period po planu restrikcija </div>
          <div class='custom_checkbox dynamic' id='edit_extra_period_type' data-value=0> <img class='checkbox_value'> </div>
        </div>
        <div class='flex_between' id='edit_extra_dates'>
          <div>Period</div>
          <div class='flex_end'>
            <input type='text' class='calendar_input filter_dfrom_calendar' readonly id='edit_extra_dfrom'>
            <input type='text' class='calendar_input filter_dto_calendar' readonly id='edit_extra_dto'>
          </div>
        </div>
        <div class='flex_between' id='edit_extra_restrictions'>
          <div>Plan restrikcija</div>
          <select class='basic_select daily_restriction_select' id='edit_extra_restriction'>
          </select>
        </div>
        <div class='flex_between'>
          <div>Slika</div>
          <div class='flex_center'>
            <div class='image_input_container' id='edit_extra_image_container'>
              <img class='image_input_image' src='${extra.image}'>
              <div class='image_input_cancel'>Ukloni</div>
            </div>
            <input type='file' class='image_input_file' id='edit_extra_image_file'>
            <button class='add_button image_input' id='edit_extra_image'> + </button>
          </div>
        </div>
        <div class='flex_between'>
          <div>Opis</div>
          <textarea class='textarea_input' id='edit_extra_description' value='${extra.description}'></textarea>
        </div>
        <div class='flex_center'>
          <button class='cancel_button' id='edit_extra_cancel'> PONIŠTI </button>
          <button class='confirm_button' id='edit_extra_confirm'> SAČUVAJ </button>
        </div>
      </div>`);
      // Add values
      $("#edit_extra_container .basic_select").select2({
          minimumResultsForSearch: Infinity,
          width: "element"
      });
      $("#edit_extra_rooms").select2({
          placeholder: "Izaberi jedinice",
          minimumResultsForSearch: Infinity,
          width: "element",
          allowClear: true,
          templateSelection: function(state){
            if(!state.id || rooms_map[state.id] == undefined){
              return state.text;
            }
            return rooms_map[state.id].shortname;
          }
      });
      $("#edit_extra_specific_rooms").select2({
          placeholder: "Izaberi jedinice",
          minimumResultsForSearch: Infinity,
          width: "element",
          allowClear: true,
          templateSelection: function(state){
            if(!state.id || rooms_map[state.id] == undefined){
              return state.text;
            }
            return rooms_map[state.id].shortname;
          }
      });
      $("#edit_extra_variation").val(extra.pricing).change();
      $("#edit_extra_type").val(extra.type).change();
      set_checkbox("edit_extra_daily", extra.daily);
      $("#edit_extra_description").val(extra.description);
      let period_val = extra.restriction_plan == 0 ? 0 : 1;
      set_checkbox("edit_extra_period_type", period_val);
      edit_extra_period_type_change();
      for(var i=0;i<restriction_plans_list.length;i++){ // Append options

        if(restriction_plans_map[restriction_plans_list[i]]["type"] == "daily")
          $("#edit_extra_restriction").append(`<option value=${restriction_plans_list[i]} class='restriction_option'> ${restriction_plans_map[restriction_plans_list[i]]["name"]} </option>`);
      }
      $("#edit_extra_dfrom").datepicker({
          language: "en",
          dateFormat: "dd-M-yyyy",
          disableNavWhenOutOfRange: true,
          autoClose: true,
          position: "bottom right",
          onShow: function(inst, animationCompleted) {
            if(animationCompleted){
              open_calendar = inst.el.id;
            }
          },
          onHide: function(inst, animationCompleted) {
            if(animationCompleted === false){
              open_calendar = "";
            }
          }
        });
      $("#edit_extra_dto").datepicker({
            language: "en",
            dateFormat: "dd-M-yyyy",
            disableNavWhenOutOfRange: true,
            autoClose: true,
            position: "bottom right",
            onShow: function(inst, animationCompleted) {
              if(animationCompleted){
                open_calendar = inst.el.id;
              }
            },
            onHide: function(inst, animationCompleted) {
              if(animationCompleted === false){
                open_calendar = "";
              }
            }
          });
      if(period_val){
        $("#edit_extra_restriction").val(period_val).change();
      }
      else {
        $("#edit_extra_dfrom").datepicker().data('datepicker').selectDate(new Date(extra.dfrom));
        $("#edit_extra_dto").datepicker().data('datepicker').selectDate(new Date(extra.dto));
      }
      for(var i=0;i<real_rooms_list.length;i++){
        $("#edit_extra_rooms").append(`<option value=${real_rooms_list[i]} class='room_option'> ${rooms_map[real_rooms_list[i]]["name"]} </option>`);
        $("#edit_extra_specific_rooms").append(`<option value=${real_rooms_list[i]} class='room_option'> ${rooms_map[real_rooms_list[i]]["name"]} </option>`);
      }
      if(extra.image == "")
        $("#edit_extra_image_container").hide();
      else
        $("#edit_extra_image").hide();
      $("#edit_extra_rooms").val(extra.rooms).change();
      $("#edit_extra_specific_rooms").val(extra.specific_rooms).change();
  }
  $("#new_extra_cancel").click(); // Hide new extra form
});
$("body").on("click", "#edit_extra_cancel", function(){
  $("#edit_extra_container").remove();
  $(".extra.selected").removeClass("selected");
});
$("body").on("click", "#edit_extra_confirm", function(){
  $("#edit_extra_confirm, #edit_extra_cancel").addClass("button_loader");
  // Parameters
  let id = $("#edit_extra_id").val();
  let name = $("#edit_extra_name").val();
  let variation = $("#edit_extra_variation").val();
  let price = $("#edit_extra_price").val();
  let tax = $("#edit_extra_tax").val();
  let type = $("#edit_extra_type").val();
  let daily = $("#edit_extra_daily").attr("data-value");
  if(type == 'one')
    daily = 0;
  let description = $("#edit_extra_description").val();
  let dfrom = '0001-01-01';
  let dto = '0001-01-01';
  let restriction = '0';
  if($("#edit_extra_period_type").attr("data-value") == 1){
    restriction = $("#edit_extra_restriction").val();
  }
  else {
    dfrom = date_to_iso($("#edit_extra_dfrom").datepicker().data('datepicker').selectedDates[0]);
    dto = date_to_iso($("#edit_extra_dto").datepicker().data('datepicker').selectedDates[0]);
  }
  let rooms = $("#edit_extra_rooms").val();
  let specific_rooms = $("#edit_extra_specific_rooms").val();
  // Form data with image
  var formData = new FormData();
  formData.append("key", main_key);
  formData.append("account", account_name);
  formData.append("lcode", main_lcode);
  formData.append("id", id);
  formData.append("name", name);
  formData.append("pricing", variation);
  formData.append("price", price);
  formData.append("tax", tax);
  formData.append("type", type);
  formData.append("daily", daily);
  formData.append("description", description);
  formData.append("dfrom", dfrom);
  formData.append("dto", dto);
  formData.append("restriction", restriction);
  formData.append("rooms", JSON.stringify(rooms));
  formData.append("specific_rooms", JSON.stringify(specific_rooms));
  var file = $("#edit_extra_image_file")[0].files[0];
  if (!file){
    formData.append("img_link", 1);
    formData.append("image", $("#edit_extra_image_container img").attr("src"));
  }
  else {
    formData.append("image", file);
  }
  // Call
  $.ajax({
    type: 'POST',
    url: api_link + 'edit/extra',
    data: formData,
    processData: false,
    contentType: false,
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change(`Izmjenjen dodatak ${name}`, sve.data.id); // Add changelog
      get_extras(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});
// Delete
$("#extras_list").on("click", ".delete", function(){ // Show dialog and delete
  let row_id = $(this).closest(".extra")[0].id;
  let id = row_id.split("_");
  id = id[id.length - 1];
  let extra = extras_map[id];
  if(confirm(`Da li želite da obrišete dodatak ${extra.name}`)){
    $("#extras_list").html(loader_html()); // Temp loader with JS dialog
    $("#new_extra").hide();
    $("#new_extra_container").hide();
    $.ajax({
      type: 'POST',
      url: api_link + 'delete/extra',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Obrisan dodatak ${extra.name}`, sve.data.id); // Add changelog
        get_extras(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  }
});

// Channels

// New

$("#new_channel").click(function(){ // Clear values and show form

  $("#new_channel_commission").val(0);
  $("#new_channel_name").val("");

  $("#new_channel_container").show();
  $("#new_channel").hide();

  $("#edit_channel_container").remove();
  $(".channel.selected").removeClass("selected"); // Hide open edits
});
$("#new_channel_cancel").click(function(){ // Hide form
  $("#new_channel_container").hide();
  $("#new_channel").show();
});
$("#new_channel_confirm").click(function(){
  // Loaders
  $("#new_channel_confirm, #new_channel_cancel").addClass("button_loader");
  // Parameters
  let name = $("#new_channel_name").val();
  let commission = $("#new_channel_commission").val();
  // Call
  $.ajax({
    type: 'POST',
    url: api_link + 'insert/channel',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      name: name,
      commission: commission
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change(`Dodat kanal ${name}`, sve.data.id); // Add changelog
      get_channels(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});
// Edit
$("#channels_list").on("click", ".edit", function(){
  let row_id = $(this).closest(".channel")[0].id;
  if($("#edit_channel_container").length && $(`#${row_id}`).hasClass("selected")){ // This plan edit is open
    return;
  }
  else {
    $("#edit_channel_container").remove();
    $(".channel.selected").removeClass("selected");
    $(`#${row_id}`).addClass("selected");
    // Plan id
    let id = row_id.split("_");
    id = id[id.length - 1];
    let channel = channels_map[id];
    $(`#${row_id}`).after(`
      <div id='edit_channel_container'>
        <input type='hidden' id='edit_channel_id' value='${id}'>
        <div class='flex_between'>
          <div>Naziv</div>
          <input type='text' class='text_input' id='edit_channel_name' value='${channel.name}'>
        </div>
        <div class='flex_between'>
          <div>Provizija (%)</div>
          <input type='number' class='number_input' id='edit_channel_commission' value='${channel.commission}'>
        </div>
        <div class='flex_center'>
          <button class='cancel_button' id='edit_channel_cancel'> PONIŠTI </button>
          <button class='confirm_button' id='edit_channel_confirm'> SAČUVAJ </button>
        </div>
      </div>`);
    $("#new_channel_cancel").click(); // Hide new channel form
  }
});
$("body").on("click", "#edit_channel_cancel", function(){ // Hide form
  $("#edit_channel_container").remove();
  $(".channel.selected").removeClass("selected");
});
$("body").on("click", "#edit_channel_confirm", function(){
  $("#edit_channel_confirm, #edit_channel_cancel").addClass("button_loader");
  // Parameters
  let id = $("#edit_channel_id").val();
  let name = $("#edit_channel_name").val();
  let commission = $("#edit_channel_commission").val();
  // Call
  $.ajax({
    type: 'POST',
    url: api_link + 'edit/channel',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: id,
      name: name,
      commission: commission
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change(`Izmjenjen kanal ${name}`, sve.data.id); // Add changelog
      get_channels(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

// Delete
$("#channels_list").on("click", ".delete", function(){ // Show dialog and delete
  let row_id = $(this).closest(".channel")[0].id;
  let id = row_id.split("_");
  id = id[id.length - 1];
  let channel = channels_map[id];
  if(confirm(`Da li želite da obrišete kanal ${channel.name}`)){
    $("#channels_list").html(loader_html()); // Temp loader with JS dialog
    $("#new_channel").hide();
    $("#new_channel_container").hide();
    $.ajax({
      type: 'POST',
      url: api_link + 'delete/channel',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Obrisan kanal ${channel.name}`, sve.data.id); // Add changelog
        get_channels(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  }
});

});

function get_rooms(){
  $.ajax({
    url: api_link + 'data/rooms/',
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
      // Clear data
      rooms_list = [];
      real_rooms_list = [];
      rooms_map = {};
      // Save data
      var rooms = sve.rooms;
      for(var i=0;i<rooms.length;i++){
        rooms_list.push(rooms[i].id);
        rooms_map[rooms[i].id] = rooms[i];
        if(rooms_map[rooms[i].id].parent_room == "0")
          real_rooms_list.push(rooms[i].id);
      }
      display_rooms();
    },
    error: function(xhr, textStatus, errorThrown){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function display_rooms(){
  // Selects
  $(".room_option").remove();
  for(var i=0;i<rooms_list.length;i++){
    $(".room_select").append(`<option value=${rooms_list[i]} class='room_option'> ${rooms_map[rooms_list[i]]["name"]} </option>`);
  }
  for(var i=0;i<real_rooms_list.length;i++){
    $(".real_room_select").append(`<option value=${real_rooms_list[i]} class='room_option'> ${rooms_map[real_rooms_list[i]]["name"]} </option>`);
    // Room numbers selects
    for(let j=0;j<rooms_map[real_rooms_list[i]].room_numbers.length;j++){
      $(".room_numbers_select").append(`<option value=${real_rooms_list[i]}_${j} class='room_option'> ${rooms_map[real_rooms_list[i]].room_numbers[j]} </option>`);
    }
  }
  $("#room_settings_cancel").click(); // Hide form
}

function get_extras(){
  $.ajax({
    url: api_link + 'data/extras/',
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
      // Clear data
      extras_list = [];
      extras_map = {};
      var extras = sve.extras;
      for(var i=0;i<extras.length;i++){
        extras_list.push(extras[i].id);
        extras_map[extras[i].id] = extras[i];
      }
      display_extras();
    },
    error: function(xhr, textStatus, errorThrown){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function display_extras(){
  // List
  $("#edit_extra_cancel").click();
  $("#new_extra_cancel").click(); // Hide forms
  $("#extras_list").empty();
  for(let i=0;i<extras_list.length;i++){
    let extra = extras_map[extras_list[i]];
    // Data
    let id = extra.id;
    let name = extra.name;
    let price = extra.price * extra.pricing;
    let type = extra.type;
    if(type == "room")
      type = "Po sobi";
    else if(type == "person")
      type = "Po osobi";
    else if(type == "one")
      type = "Jednokratno";
    var extra_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmjeni'> </div>`;
    var extra_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
    $("#extras_list").append(`
      <div class="list_row extra" id='extras_list_${id}'>
        <div class='extra_name'> ${name} </div>
        <div class='extra_type'> ${type} </div>
        <div class='extra_price'> ${price} EUR </div>
        <div class='extra_actions'> ${extra_edit} ${extra_delete} </div>
      </div>`);
  }
  if(extras_list.length > 0)
    $("#extras_list").prepend(`
    <div class="list_names">
    <div class='extra_name'> Naziv </div>
    <div class='extra_type'> Tip </div>
    <div class='extra_price'> Cijena </div>
      <div class='extra_actions'> Akcije </div>
    </div>`);
};

function get_channels(){
  $.ajax({
    url: api_link + 'data/channels/',
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
      // Clear data
      channels_list = [];
      channels_map = {};
      var channels = sve.channels;
      for(var i=0;i<channels.length;i++){
        channels_list.push(channels[i].id);
        channels_map[channels[i].id] = channels[i];
      }
      display_channels();
    },
    error: function(xhr, textStatus, errorThrown){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function display_channels(){
  // Select
  $(".channel_option").remove();
  $(".channel_select").append(`<option value='-1' class='channel_option'> <img src='img/ota/youbook.png'> Direktna rezervacija </option>`);
  for(var i=0;i<channels_list.length;i++){
    $(".channel_select").append(`<option value=${channels_list[i]} class='channel_option'> <img src='${channels_map[channels_list[i]]["logo"]}'> ${channels_map[channels_list[i]]["name"]} </option>`);
  }
  // List
  $("#edit_channel_cancel").click();
  $("#new_channel_cancel").click(); // Hide forms
  $("#channels_list").empty();
  for(let i=0;i<channels_list.length;i++){
    let channel = channels_map[channels_list[i]];
    // Data
    let id = channel.id;
    let channel_logo = channel.logo;
    let name = channel.name;
    let commission = channel.commission;
    var channel_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmjeni'> </div>`;
    var channel_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
    if(channel.created_by == "Wubook")
      channel_delete = "";
    $("#channels_list").append(`
      <div class="list_row channel" id='channels_list_${id}'>
        <div class='channel_logo'> <img src='${channel_logo}'> </div>
        <div class='channel_name'> ${name} </div>
        <div class='channel_commission'> ${commission}% </div>
        <div class='channel_actions'> ${channel_edit} ${channel_delete} </div>
      </div>`);
  }
  if(channels_list.length == 0)
    $("#channels_list").append(empty_html("Nema prodajnih kanala"));
  else
    $("#channels_list").prepend(`
    <div class="list_names">
      <div class='channel_logo'> </div>
      <div class='channel_name'> Naziv </div>
      <div class='channel_commission'> Provizija </div>
      <div class='channel_actions'> Akcije </div>
    </div>`);
};
