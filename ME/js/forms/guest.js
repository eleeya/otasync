$(document).ready(function(){

// Open New
$("#form_res_guests").on("click", ".edit", function(){

  let field_id = $(this).closest(".form_res_guest")[0].id;

  let id = $(`#${field_id} .form_res_guest_id`).val();
  if(id != ""){ // Open edit form of existing guest
    open_guest_form(guests_map[id]);
    $("#form_guest_res_id").val(field_id);
  }
  else {
    // Show form
    $("#form_guest").show();
    // Clear values
    $("#form_guest h1").text("Unos novog gosta");
    $("#form_guest_id").val("");
    $("#form_guest_res_id").val(field_id);
    $("#form_guest_name").val( $(`#${field_id} .form_res_guest_name`).val() );
    $("#form_guest_surname").val( $(`#${field_id} .form_res_guest_surname`).val() );
    $("#form_guest_email").val( $(`#${field_id} .form_res_guest_email`).val() );
    $("#form_guest_phone").val( $(`#${field_id} .form_res_guest_phone`).val() );
    $("#form_guest_address").val("");
    $("#form_guest_city").val("");
    $("#form_guest_zip").val("");
    $("#form_guest_country").val("--");
    $("#form_guest_date_of_birth").datepicker().data('datepicker').clear();
    set_switch("form_guest_gender", 1);
    set_switch("form_guest_host_again", 1);
    $("#form_guest_note").val("");
    $("#form_guest_document_type").val("");
    $("#form_guest_document_number").val("");
    $("#form_guest_error").text("");

  }
});
$("#form_group_guests").on("click", ".edit", function(){

  let field_id = $(this).closest(".form_group_guest")[0].id;

  let id = $(`#${field_id} .form_group_guest_id`).val();
  if(id != ""){ // Open edit form of existing guest
    open_guest_form(guests_map[id]);
    $("#form_guest_res_id").val(field_id);
  }
  else {
    // Show form
    $("#form_guest").show();
    // Clear values
    $("#form_guest h1").text("Unos novog gosta");
    $("#form_guest_id").val("");
    $("#form_guest_res_id").val(field_id);
    $("#form_guest_name").val( $(`#${field_id} .form_group_guest_name`).val() );
    $("#form_guest_surname").val( $(`#${field_id} .form_group_guest_surname`).val() );
    $("#form_guest_email").val( $(`#${field_id} .form_group_guest_email`).val() );
    $("#form_guest_phone").val( $(`#${field_id} .form_group_guest_phone`).val() );
    $("#form_guest_address").val("");
    $("#form_guest_city").val("");
    $("#form_guest_zip").val("");
    $("#form_guest_country").val("--");
    $("#form_guest_date_of_birth").datepicker().data('datepicker').clear();
    set_switch("form_guest_gender", 1);
    set_switch("form_guest_host_again", 1);
    $("#form_guest_note").val("");
    $("#form_guest_document_type").val("");
    $("#form_guest_document_number").val("");
    $("#form_guest_error").text("");

  }
});
// Close
$("#form_guest_cancel").click(function(){
    $("#form_guest").hide();
  });

// Open edit

// Open from list
$("#guests_list").on("click", ".edit", function(e){
  e.stopPropagation();
  let id  = $(this).closest(".guest")[0].id.split("_");
  id = id[id.length - 1];
  let guest = guests_map[id];
  click_to_hide();
  scroll_lock();
  open_guest_form(guest);
});

$("body").on("click", "#guest_info_edit", function(){
  let id  = $("#guest_info_edit").attr("data-value");
  let guest = guests_map[id];
  click_to_hide();
  scroll_lock();
  open_guest_form(guest);
});


// Insert

$("#form_guest_confirm").click(function(){
  // Loaders
  $("#form_guest_confirm, #form_guest_cancel").addClass("button_loader");
  // Parameters
  let id = $("#form_guest_id").val();
  let name = $("#form_guest_name").val();
  let surname = $("#form_guest_surname").val();
  let date_of_birth = date_to_iso($("#form_guest_date_of_birth").datepicker().data('datepicker').selectedDates[0]);
  if(date_of_birth == "")
    date_of_birth = "0001-01-01";
  let gender = $("#form_guest_gender").attr("data-value") == 1 ? "M" : "F";
  // Call
  let action = id == "" ? 'insert/guest' : 'edit/guest';
  $.ajax({
    type: 'POST',
    url: api_link + action,
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: $("#form_guest_id").val(),
      name: name,
      surname: surname,
      email: $("#form_guest_email").val(),
      phone: $("#form_guest_phone").val(),
      address: $("#form_guest_address").val(),
      city: $("#form_guest_city").val(),
      zip: $("#form_guest_zip").val(),
      country_of_residence: $("#form_guest_country").val(),
      document_type: $("#form_guest_document_type").val(),
      document_number: $("#form_guest_document_number").val(),
      date_of_birth: date_of_birth,
      gender: gender,
      host_again: $("#form_guest_host_again").attr('data-value'),
      note: $("#form_guest_note").val()
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      if(id == "")
        add_change(`Dodat gost ${name} ${surname}`, sve.data.id); // Add changelog
      else
        add_change(`Izmjenjen gost ${name} ${surname}`, sve.data.id); // Add changelog
      $("#form_guest_cancel").click();
      get_guests(); // Refresh data
      let res_guest_id = $("#form_guest_res_id").val();
      if(res_guest_id != ""){
        guests_map[sve.data.new_data.id] = sve.data.new_data;
        $(`#${res_guest_id} .form_res_guest_id`).val(sve.data.new_data.id);
        $(`#${res_guest_id} .form_res_guest_name`).val(sve.data.new_data.name);
        $(`#${res_guest_id} .form_res_guest_surname`).val(sve.data.new_data.surname);
        $(`#${res_guest_id} .form_res_guest_email`).val(sve.data.new_data.email);
        $(`#${res_guest_id} .form_res_guest_phone`).val(sve.data.new_data.phone);
        $(`#${res_guest_id} .form_group_guest_id`).val(sve.data.new_data.id);
        $(`#${res_guest_id} .form_group_guest_name`).val(sve.data.new_data.name);
        $(`#${res_guest_id} .form_group_guest_surname`).val(sve.data.new_data.surname);
        $(`#${res_guest_id} .form_group_guest_email`).val(sve.data.new_data.email);
        $(`#${res_guest_id} .form_group_guest_phone`).val(sve.data.new_data.phone);
      }
      else {
        $("html, body").css("overflow", "");
      }
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

});


function open_guest_form(guest){
  $("#form_guest h1").text("Ažuriranje gosta");
  $("#form_guest_id").val(guest.id);
  $("#form_guest_res_id").val("");
  $("#form_guest_name").val(guest.name);
  $("#form_guest_surname").val(guest.surname);
  $("#form_guest_email").val(guest.email);
  $("#form_guest_phone").val(guest.phone);
  $("#form_guest_address").val(guest.address);
  $("#form_guest_city").val(guest.city);
  $("#form_guest_zip").val(guest.zip);
  $("#form_guest_country").val(guest.country_of_residence);
  set_switch("form_guest_gender", guest.gender == "M" ? 1 : 0);
  set_switch("form_guest_host_again", guest.host_again);
  $("#form_guest_date_of_birth").datepicker().data('datepicker').clear();
  if(guest.date_of_birth != "0001-01-01"){
    $("#form_guest_date_of_birth").datepicker().data('datepicker').selectDate(new Date(guest.date_of_birth));
  }
  $("#form_guest_note").val(guest.note);
  $("#form_guest_document_type").val(guest.registration_data.document_type);
  $("#form_guest_document_number").val(guest.registration_data.document_number);
  $("#form_guest_error").text("");
  $("#form_guest").show();
}
