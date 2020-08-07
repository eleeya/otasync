
function updateData() {
  if(disable_calls)
    return;
  var header = {
    name: $("#name").val(),
    description: $("#description").val()
  };
  var data = JSON.stringify(header);
  $.ajax({
    url: api_link + "engine/update/header",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
     },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function updateAppearance() {
  if(disable_calls)
    return;
  var borderRadius = $("#borderRadius").attr("data-value") == 1 ? 25 : 5;
  var appearance = {
    color: $("#colors").val(),
    borderRadius: borderRadius,
  };
  var data = JSON.stringify(appearance);
  $.ajax({
    url: api_link + "engine/update/appearance",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
     },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function updateLocation() {
  if(disable_calls)
    return;
  var header = {
    longitude: $("#longitude").val(),
    latitude: $("#latitude").val()
  };
  var data = JSON.stringify(header);
  $.ajax({
    url: api_link + "engine/update/location",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
    },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function updateSelectDates() {
  if(disable_calls)
    return;
  var header = {
    children: $("#children").attr("data-value"),
    nights: $("#nights").val(),
    sameDay: $("#sameDay").val(),
    cents: $("#cents").attr("data-value")
  };
  var data = JSON.stringify(header);
  $.ajax({
    url: api_link + "engine/update/selectdates",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
     },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function updateContact() {
  if(disable_calls)
    return;
  var header = {
    adress: $("#adresse").val(),
    phone: $("#phone").val(),
    email: $("#email").val(),
    web: $("#webSite").val(),
    fb: $("#facebook").val(),
    instagram: $("#instagram").val(),
    yt: $("#youtube").val()
  };
  var data = JSON.stringify(header);
  $.ajax({
    url: api_link + "engine/update/contact",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
    },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function updateConfirmation() {
  if(disable_calls)
    return;
  var checkIn = $("#checkFrom").val() + "-" + $("#checkTo").val();
  var header = {
    occupancy: $("#occupancySpecificaiton").attr("data-value"),
    phone: $("#showPhone").attr("data-value"),
    adress: $("#showAdress").attr("data-value"),
    city: $("#showCity").attr("data-value"),
    country: $("#showCountry").attr("data-value"),
    card: $("#showCard").attr("data-value"),
    cvv: $("#showCardCvv").attr("data-value"),
    checkIn: checkIn
  };
  var data = JSON.stringify(header);
  $.ajax({
    url: api_link + "engine/update/confirmation",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
     },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function updateMessages() {
  if(disable_calls)
    return;
  var header = {
    welcome: $("#welcome").val(),
    book: $("#book").val(),
    noAvail: $("#noAvail").val(),
    voucher: $("#voucher").val()
  };
  var data = JSON.stringify(header);
  $.ajax({
    url: api_link + "engine/update/messages",
    method: "POST",
    data: {
      mydata: data,
      lcode: main_lcode
     },
    success: function(rezultat) {
      add_change_settings();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}

function updateLogo(){
  var file = $("#engine_logo_file")[0].files[0];
  var formData = new FormData();
  if (!file)
    formData.append("clear", 1);
  else
    formData.append("logo", file);
  formData.append("key", main_key);
  formData.append("account", account_name);
  formData.append("lcode", main_lcode);

  $.ajax({
    type: 'POST',
    url: api_link + 'engine/update/logo',
    data: formData,
    processData: false,
    contentType: false,
    success: function(rezultat){
      add_change_settings();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
function updateBackground(){
  var file = $("#engine_background_file")[0].files[0];
  var formData = new FormData();
  if (!file)
    formData.append("clear", 1);
  else
    formData.append("background", file);
  formData.append("key", main_key);
  formData.append("account", account_name);
  formData.append("lcode", main_lcode);

  $.ajax({
    type: 'POST',
    url: api_link + 'engine/update/background',
    data: formData,
    processData: false,
    contentType: false,
    success: function(rezultat){
      add_change_settings();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

$(document).ready(function(){

  $("#colors").change(function(){
    $("#colorPreview").css("background-color", $("#colors").val());
  });
  var coll = document.getElementsByClassName("collapsible");
  for(var i = 0; i < coll.length; i++){
    coll[i].addEventListener("click", function() {
      this.classList.toggle("active");
      var content = this.nextElementSibling;
      if(content.style.display === "flex") {
        content.style.display = "none";
      } else {
        content.style.display = "flex";
      }
    });
  }

  // Updates
  // Inputs and selects
  $(".engine_appearance").change(updateAppearance);
  $(".engine_location").change(updateLocation);
  $(".engine_selectdates").change(updateSelectDates);
  $(".engine_contact").change(updateContact);
  $(".engine_confirmation").change(updateConfirmation);
  // Checkboxes
  $(".engine_appearance.custom_checkbox").click(updateAppearance);
  $(".engine_location.custom_checkbox").click(updateLocation);
  $(".engine_selectdates.custom_checkbox").click(updateSelectDates);
  $(".engine_contact.custom_checkbox").click(updateContact);
  $(".engine_confirmation.custom_checkbox").click(updateConfirmation);
  // Images
  $("#engine_logo_file").change(updateLogo);
  $("#engine_background_file").change(updateBackground);

  // Promocodes
  $("#new_promocode").click(function(){ // Clear values and show form

    $("#new_promocode_container input").val('');
    $("#new_promocode_container .number_input").val(0);
    $("#promocodeDescription").val("");
    $("#new_promocode_container select").prop("selectedIndex", 0).change();

    $("#new_promocode_container").show();
    $("#new_promocode").hide();

    $("#edit_promocode_container").remove();
    $(".promocode.selected").removeClass("selected"); // Hide open edits
  });
  $("#new_promocode_cancel").click(function(){ // Hide form
    $("#new_promocode_container").hide();
    $("#new_promocode").show();
  });
  $("#new_promocode_confirm").click(function(){
    // Loaders
    $("#new_promocode_confirm, #new_promocode_cancel").addClass("button_loader");
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'insert/promocode',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        code: $("#promocode").val(),
        name: $("#promocodeName").val(),
        target: $("#promocodeTarget").val(),
        value: $("#promocodeValue").val(),
        type: $("#promocodeType").val(),
        description: $("#promocodeDescription").val()
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Dodat promo kod ${$("#promocodeName").val()}`, sve.data.id); // Add changelog
        get_promocodes(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  // Edit
  $("#promocodes_list").on("click", ".edit", function(){
    let row_id = $(this).closest(".promocode")[0].id;
    if($("#edit_promocode_container").length && $(`#${row_id}`).hasClass("selected")){ // This promocode edit is open
      return;
    }
    else {
      $("#edit_promocode_container").remove();
      $(".promocode.selected").removeClass("selected");
      $(`#${row_id}`).addClass("selected");
      // Plan id
      let id = row_id.split("_");
      id = id[id.length - 1];
      let promocode = promocodes_map[id];
      $(`#${row_id}`).after(`
        <div id='edit_promocode_container'>
        <input type='hidden' id='edit_promocode_id' value='${id}'>
          <div class='flex_between'>
            <div class='vert_center'>
              <div> Naziv </div>
              <input type="text" id="edit_promocodeName" value='${promocode.name}'>
            </div>
            <div class='vert_center'>
              <div> Kod </div>
              <input type="text" id="edit_promocode" value='${promocode.code}'>
            </div>
          </div>
          <div class='flex_between'>
            <div class='vert_center'>
              <div> Tip </div>
              <select id="edit_promocodeTarget" class='basic_select'>
                <option value="all">Sve</option>
                <option value="room">Sobe</option>
                <option value="extras">Dodaci</option>
              </select>
            </div>
            <div class='vert_center'>
              <div> Iznos </div>
              <div class='form_center'>
                <input type="number" class='number_input' id="edit_promocodeValue" value='${promocode.value}'>
                <select id="edit_promocodeType" class='basic_select short'>
                  <option value="percentage">%</option>
                  <option value="fixed">EUR</option>
                </select>
              </div>
            </div>
          </div>
          <div class='vert_center'>
            <div> Opis </div>
            <textarea id="edit_promocodeDescription">
            </textarea>
          </div>
          <div class='flex_center'>
            <button class='cancel_button' id='edit_promocode_cancel'> PONIŠTI </button>
            <button class='confirm_button' id='edit_promocode_confirm'> SAČUVAJ </button>
          </div>
        </div>`);
      // Add values
      $("#edit_promocode_container select").select2({
          minimumResultsForSearch: Infinity,
          width: "element"
      });
      $("#edit_promocodeTarget").val(promocode.target).change();
      $("#edit_promocodeType").val(promocode.type).change();
      $("#edit_promocodeDescription").val(promocode.description);
      $("#new_promocode_cancel").click(); // Hide new promocode form

    }
  });
  $("body").on("click", "#edit_promocode_cancel", function(){ // Hide form
    $("#edit_promocode_container").remove();
    $(".promocode.selected").removeClass("selected");
  });
  $("body").on("click", "#edit_promocode_confirm", function(){
    // Loaders
    $("#edit_promocode_confirm, #edit_promocode_cancel").addClass("button_loader");
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/promocode',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: $("#edit_promocode_id").val(),
        code: $("#edit_promocode").val(),
        name: $("#edit_promocodeName").val(),
        target: $("#edit_promocodeTarget").val(),
        value: $("#edit_promocodeValue").val(),
        type: $("#edit_promocodeType").val(),
        description: $("#edit_promocodeDescription").val()
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Izmjenjen promo kod ${$("#edit_promocodeName").val()}`, sve.data.id); // Add changelog
        get_promocodes(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  // Delete
  $("#promocodes_list").on("click", ".delete", function(){ // Show dialog and delete
    let row_id = $(this).closest(".promocode")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    let promocode = promocodes_map[id];
    if(confirm(`Da li želite da obrišete promo kod ${promocode.name}`)){
      $("#promocodes_list").html(loader_html()); // Temp loader with JS dialog
      $("#new_promocode").hide();
      $("#new_promocode_container").hide();
      $.ajax({
        type: 'POST',
        url: api_link + 'delete/promocode',
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
          add_change(`Obrisan promo kod ${promocode.name}`, sve.data.id); // Add changelog
          get_promocodes(); // Refresh data
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
      });
    }
  });

  // Policies
  // Inputs
  $("#policyType").change(function(){
    let description;
    $(".new_policy_percentage").hide(); // Hide by default
    if($("#policyType").val() === "firstNight"){
      description = "U slučaju otkazivanja, zadržava se pravo naplate prve noći rezervacije"
    }
    else if($("#policyType").val() === "amountPercentage"){
      description = "U slučaju otkazivanja, zadrzava se pravo naplate prve noći i procenta od ukupnog iznosa rezervacije"
      $(".new_policy_percentage").show();
    }
    else if($("#policyType").val() === "noPenalty"){
      description = "U slučaju otkazivanja, neće biti naplaćena naknada za otkazivanje"
    }
    else if($("#policyType").val() === "entireAmount"){
      description = "U slučaju otkazivanja, biće naplaćen pun iznos rezervacije"
    }
    else if($("#policyType").val() === "notRefundable"){
      description = "U slučaju otkazivanja, ne postoji mogućnost refundiranja"
    }
    else if($("#policyType").val() === "notRefImmediate"){
      description = "Odmah po izvršenoj rezervaciji, iznos mora biti uplaćen ili će biti naplaćen"
    }
    else if($("#policyType").val() === "custom"){
      description = "";
    }
    $("#policyValue").val(0);
    $("#policyDescription").val(description);
  })
  $("#policyFree").click(function(){
    if($(this).attr("data-value") == 1){
      $(".new_policy_free_days").show();
    }
    else {
      $(".new_policy_free_days").hide();
    }
    $("#policyFreeDays").val(0);
  });
  // New
  $("#new_policy").click(function(){ // Clear values and show form

    $("#new_policy_container input").val('');
    $("#new_policy_container .number_input").val(0);
    $("#policyDescription").val("");
    $("#new_policy_container select").prop("selectedIndex", 0).change();
    set_checkbox("policyFree", 0);
    $(".new_policy_free_days").hide();

    $("#new_policy_container").show();
    $("#new_policy").hide();

    $("#edit_policy_container").remove();
    $(".policy.selected").removeClass("selected"); // Hide open edits
  });
  $("#new_policy_cancel").click(function(){ // Hide form
    $("#new_policy_container").hide();
    $("#new_policy").show();
  });
  $("#new_policy_confirm").click(function(){
    // Loaders
    $("#new_policy_confirm, #new_policy_cancel").addClass("button_loader");
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'insert/policy',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        name: $("#policyName").val(),
        type: $("#policyType").val(),
        value: $("#policyValue").val(),
        enableFreeDays: $("#policyFree").attr("data-value"),
        freeDays: $("#policyFreeDays").val(),
        description: $("#policyDescription").val()
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Dodata politika otkazivanja ${$("#policyName").val()}`, sve.data.id); // Add changelog
        get_policies(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  // Edit
  $("#policies_list").on("click", ".edit", function(){
    let row_id = $(this).closest(".policy")[0].id;
    if($("#edit_policy_container").length && $(`#${row_id}`).hasClass("selected")){ // This policy edit is open
      return;
    }
    else {
      $("#edit_policy_container").remove();
      $(".policy.selected").removeClass("selected");
      $(`#${row_id}`).addClass("selected");
      // Plan id
      let id = row_id.split("_");
      id = id[id.length - 1];
      let policy = policies_map[id];
      $(`#${row_id}`).after(`
        <div id='edit_policy_container'>
          <input type='hidden' id='edit_policy_id' value='${id}'>
          <div class='flex_between'>
            <div> Naziv </div>
            <input type="text" id="edit_policyName" value='${policy.name}'>
          </div>
          <div class='flex_between'>
            <div> Tip </div>
            <select id="edit_policyType" class='basic_select'>
              <option value="firstNight">First Night</option>
              <option value="amountPercentage">Amount Percentage</option>
              <option value="noPenalty">No Penalty</option>
              <option value="entireAmount">Entire Amount</option>
              <option value="notRefundable">Not Refundable</option>
              <option value="notRefImmediate">Not Ref, immediate chrage</option>
              <option value="custom">Custom</option>
            </select>
          </div>
          <div class='flex_between edit_policy_percentage'>
            <div> Iznos naplate (%) </div>
            <input type="number" class='number_input' id="edit_policyValue" value='${policy.value}'>
          </div>
          <div class='flex_between'>
            <div> Besplatno otkazivanje </div>
            <div class='custom_checkbox dynamic' id='edit_policyFree' data-value=0> <img class='checkbox_value'> </div>
          </div>
          <div class='flex_between edit_policy_free_days'>
            <div> Broj dana za besplatno otkazivanje </div>
            <input type="number" class='number_input' id="edit_policyFreeDays" value='${policy.freeDays}'>
          </div>
          <div class='vert_center'>
            <div> Opis </div>
            <textarea id="edit_policyDescription">
            </textarea>
          </div>
          <div class='flex_center'>
            <button class='cancel_button' id='edit_policy_cancel'> PONIŠTI </button>
            <button class='confirm_button' id='edit_policy_confirm'> SAČUVAJ </button>
          </div>
        </div>`);
      // Add values
      $("#edit_policy_container select").select2({
          minimumResultsForSearch: Infinity,
          width: "element"
      });
      $("#edit_policyType").val(policy.type).change();
      set_checkbox("edit_policyFree", policy.enableFreeDays);
      if($("#edit_policyFree").attr("data-value") == 1){
        $(".edit_policy_free_days").show();
      }
      else {
        $(".edit_policy_free_days").hide();
      }
      $("#edit_policyDescription").val(policy.description);
      $("#new_policy_cancel").click(); // Hide new promocode form
    }
  });
  // Inputs
  $("#policies_list").on("change", "#edit_policyType", function(){
    let description;
    $(".edit_policy_percentage").hide(); // Hide by default
    if($("#edit_policyType").val() === "firstNight"){
      description = "U slučaju otkazivanja, zadržava se pravo naplate prve noći rezervacije"
    }
    else if($("#edit_policyType").val() === "amountPercentage"){
      description = "U slučaju otkazivanja, zadrzava se pravo naplate prve noći i procenta od ukupnog iznosa rezervacije"
      $(".edit_policy_percentage").show();
    }
    else if($("#edit_policyType").val() === "noPenalty"){
      description = "U slučaju otkazivanja, neće biti naplaćena naknada za otkazivanje"
    }
    else if($("#edit_policyType").val() === "entireAmount"){
      description = "U slučaju otkazivanja, biće naplaćen pun iznos rezervacije"
    }
    else if($("#edit_policyType").val() === "notRefundable"){
      description = "U slučaju otkazivanja, ne postoji mogućnost refundiranja"
    }
    else if($("#edit_policyType").val() === "notRefImmediate"){
      description = "Odmah po izvršenoj rezervaciji, iznos mora biti uplaćen ili će biti naplaćen"
    }
    else if($("#edit_policyType").val() === "custom"){
      description = "";
    }
    $("#edit_policyDescription").val(description);
  })
  $("body").on("click", "#edit_policyFree", function(){
    if($(this).attr("data-value") == 1){
      $(".edit_policy_free_days").show();
    }
    else {
      $(".edit_policy_free_days").hide();
    }
  });
  $("body").on("click", "#edit_policy_cancel", function(){ // Hide form
    $("#edit_policy_container").remove();
    $(".policy.selected").removeClass("selected");
  });
  $("body").on("click", "#edit_policy_confirm", function(){
    // Loaders
    $("#edit_promocode_confirm, #edit_promocode_cancel").addClass("button_loader");
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/policy',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: $("#edit_policy_id").val(),
        name: $("#edit_policyName").val(),
        type: $("#edit_policyType").val(),
        value: $("#edit_policyValue").val(),
        enableFreeDays: $("#edit_policyFree").attr("data-value"),
        freeDays: $("#edit_policyFreeDays").val(),
        description: $("#edit_policyDescription").val()
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Izmjenjena politika otkazivanja ${$("#edit_policyName").val()}`, sve.data.id); // Add changelog
        get_policies(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  // Delete
  $("#policies_list").on("click", ".delete", function(){ // Show dialog and delete
    let row_id = $(this).closest(".policy")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    let policy = policies_map[id];
    if(confirm(`Da li želite da obrišete politiku otkazivanja ${policy.name}`)){
      $("#policies_list").html(loader_html()); // Temp loader with JS dialog
      $("#new_policy").hide();
      $("#new_policy_container").hide();
      $.ajax({
        type: 'POST',
        url: api_link + 'delete/policy',
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
          add_change(`Obrisana politika otkazivanja ${policy.name}`, sve.data.id); // Add changelog
          get_policies(); // Refresh data
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
      });
    }
  });
});

function showEngineData() { // Fetches input values
  $.ajax({
    url: api_link + "engine/data/header",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      $("#name").val(info.name);
      $("#description").val(info.description);
      disable_calls = false;
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  $.ajax({
    url: api_link + "engine/data/contact",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      $("#adresse").val(info.address);
      $("#phone").val(info.phone);
      $("#email").val(info.email);
      $("#webSite").val(info.web);
      $("#facebook").val(info.fb);
      $("#instagram").val(info.instagram);
      $("#youtube").val(info.yt);
      $("#longitude").val(info.longitude);
      $("#latitude").val(info.latitude);
      disable_calls = false;
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  $.ajax({
    url: api_link + "engine/data/appearance",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      var borderRadius = info.borderRadius == 25 ? 1 : 0;
      set_checkbox("borderRadius", borderRadius);
      $("#colors").val(info.accentColor).change();
      $("#colorPreview").css("background-color", info.accentColor);
      disable_calls = false;
      if(info.logo == ""){
        $("#engine_logo_container").hide();
        $("#engine_logo").show();
      }
      else {
        $("#engine_logo").hide();
        $("#engine_logo_container img").attr("src", info.logo);
        $("#engine_logo_container").show();
      }
      if(info.backgroundImg == ""){
        $("#engine_background_container").hide();
        $("#engine_background").show();
      }
      else {
        $("#engine_background").hide();
        $("#engine_background_container img").attr("src", info.backgroundImg);
        $("#engine_background_container").show();
      }
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  $.ajax({
    url: api_link + "engine/data/footer",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      $("#footerDescription").val(info.description);
      disable_calls = false;
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  $.ajax({
    url: api_link + "engine/data/selectdates",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      console.log(info);
      set_checkbox("children", info.children);
      set_checkbox("cents", info.cents);
      $("#sameDay").val(info.sameDayReservation).change();
      $("#nights").val(info.nights);
      disable_calls = false;
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  $.ajax({
    url: api_link + "engine/data/confirmation",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      set_checkbox("occupancySpecificaiton", info.occupancy);
      set_checkbox("showPhone", info.phone);
      set_checkbox("showAdress", info.adress);
      set_checkbox("showCity", info.city);
      set_checkbox("showCountry", info.country);
      set_checkbox("showCard", info.card);
      set_checkbox("showCardCvv", info.cvv);
      var [checkFrom, checkTo] = info.checkIn.split("-");
      $("#checkFrom").val(checkFrom).change();
      $("#checkTo").val(checkTo).change();
      disable_calls = false;
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  $.ajax({
    url: api_link + "engine/data/messages",
    method: "POST",
    data: {lcode: main_lcode},
    success: function(rezultat) {
      info = JSON.parse(rezultat);
      disable_calls = true;
      $("#welcome").val(info.welcome);
      $("#noAvail").val(info.noAvail);
      $("#book").val(info.book);
      $("#voucher").val(info.voucher);
      disable_calls = false;
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
  get_promocodes()
  get_policies();
}
function get_promocodes(){
  $.ajax({
    url: api_link + "data/promocodes",
    method: "POST",
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode
    },
    success: function(rezultat) {
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      // Clear data
      promocodes_list = [];
      promocodes_map = {};
      var promocodes = sve.promocodes;
      for(var i=0;i<promocodes.length;i++){
        promocodes_list.push(promocodes[i].id);
        promocodes_map[promocodes[i].id] = promocodes[i];
      }
      display_promocodes(sve.promocodes);
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function display_promocodes(promocodes){
  // List
  $("#edit_promocode_cancel").click();
  $("#new_promocode_cancel").click(); // Hide forms
  $("#promocodes_list").empty();
  for(let i=0;i<promocodes.length;i++){
    let promocode = promocodes[i];
    // Data
    let id = promocode.id;
    let name = promocode.name;
    let code = promocode.code;
    let target = promocode.target;
    if(target == "all")
      target = "Sve";
    if(target == "room")
      target = "Jedinice";
    if(target == "extras")
      target = "Dodaci";
    let amount = promocode.value;
    if(promocode.type == "percentage")
      amount += "%";
    else
      amount += " EUR";
    var promocode_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmjeni'> </div>`;
    var promocode_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
    $("#promocodes_list").append(`
      <div class="list_row promocode" id='promocodes_list_${id}'>
        <div class='promocode_name'> ${name} </div>
        <div class='promocode_code'> ${code} </div>
        <div class='promocode_target'> ${target} </div>
        <div class='promocode_amount'> ${amount} </div>
        <div class='promocode_actions'> ${promocode_edit} ${promocode_delete} </div>
      </div>`);
  }
  if(promocodes.length == 0)
    $("#promocodes_list").append(empty_html("Nema aktivnih promo kodova"));
  else
    $("#promocodes_list").prepend(`
    <div class="list_names">
    <div class='promocode_name'> Naziv </div>
    <div class='promocode_code'> Kod </div>
    <div class='promocode_target'> Tip </div>
    <div class='promocode_amount'> Iznos </div>
    <div class='promocode_actions'> Akcije </div>
    </div>`);
};
function get_policies(){
  $.ajax({
    url: api_link + "data/policies",
    method: "POST",
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode
    },
    success: function(rezultat) {
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      // Clear data
      policies_list = [];
      policies_map = {};
      var policies = sve.policies;
      for(var i=0;i<policies.length;i++){
        policies_list.push(policies[i].id);
        policies_map[policies[i].id] = policies[i];
      }
      display_policies();
    },
    error: function(rezultat) {
      window.alert("Doslo je do greske.");
    }
  });
}
function display_policies(){
  // Selects
  $(".policy_option").remove();
  for(var i=0;i<policies_list.length;i++){
    $(".policy_select").append(`<option value=${policies_list[i]} class='policy_option'> ${policies_map[policies_list[i]]["name"]} </option>`);
  }
  // List
  $("#edit_policy_cancel").click();
  $("#new_policy_cancel").click(); // Hide forms
  $("#policies_list").empty();
  for(let i=0;i<policies_list.length;i++){
    let policy = policies_map[policies_list[i]];
    // Data
    let id = policy.id;
    let name = policy.name;
    let type = policy.type;
    // Types
    if(policy.type == "firstNight")
    type = "First Night";
    if(policy.type == "amountPercentage")
    type = "Amount Percentage";
    if(policy.type == "noPenalty")
    type = "No Penalty";
    if(policy.type == "entireAmount")
    type = "Entire Amount";
    if(policy.type == "notRefundable")
    type = "Not Refundable";
    if(policy.type == "notRefImmediate")
    type = "Not Ref, immediate charge";
    if(policy.type == "custom")
    type = "Custom";
    let description = policy.description;
    var policy_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmjeni'> </div>`;
    var policy_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
    $("#policies_list").append(`
      <div class="list_row policy" id='policies_list_${id}'>
        <div class='policy_name'> ${name} </div>
        <div class='policy_type'> ${type} </div>
        <div class='policy_description'> ${description} </div>
        <div class='policy_actions'> ${policy_edit} ${policy_delete} </div>
      </div>`);
  }
  if(policies_list.length == 0)
    $("#policies_list").append(empty_html("Nema politika otkazivanja"));
  else
    $("#policies_list").prepend(`
    <div class="list_names">
      <div class='policy_name'> Naziv </div>
      <div class='policy_type'> Tip </div>
      <div class='policy_description'> Opis </div>
      <div class='policy_actions'> Akcije </div>
    </div>`);
};
