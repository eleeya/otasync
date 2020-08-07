$(document).ready(function(){

// Global search
function search_function(){

    let val = $(this).val();
    $("#search_results").remove();
    if(val.length < 3)
      return;
    $("#search").after(`<div id='search_results'>${loader_html()}</div>`);
    let top = $("#search").position().top + 40;
    let right = $("html").width() - $("#search").position().left - $("#search").width() - 10; // Idk about this - 10 but it checks out
    $("#search_results").css("top", top + "px");
    $("#search_results").css("right", right + "px");
    $("#search_results").css("max-height", `calc(95vh - ${top}px)`);
    $.ajax({
      url: api_link + 'data/search',
      method: 'POST',
      data: {
              key: main_key,
              account: account_name,
              lcode: main_lcode,
              keyword: val
            },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        $("#search_results").empty();
        // Save data and append
        if(sve.reservations.length){
          $("#search_results").append("<div class='section_title'> Reservations </div>");
        }
        for(let i=0;i<sve.reservations.length;i++){
          all_reservations[sve.reservations[i].reservation_code] = sve.reservations[i];
          $("#search_results").append(compact_res_html("search_reservations", sve.reservations[i]));
        }
        if(sve.guests.length){
          $("#search_results").append("<div class='section_title'> Guests </div>");
        }
        for(let i=0;i<sve.guests.length;i++){
          guests_map[sve.guests[i].id] = sve.guests[i];
          $("#search_results").append(compact_guest_html("search_guests", sve.guests[i]));
        }
        if(sve.invoices.length){
          $("#search_results").append("<div class='section_title'> Invoices </div>");
        }
        for(let i=0;i<sve.invoices.length;i++){
          invoices_map[sve.invoices[i].id] = sve.invoices[i];
          $("#search_results").append(compact_invoice_html("search_invoices", sve.invoices[i]));
        }

      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("An error occured. " + xhr.responseText);
      }
    });


  }
$("#search").on("input", search_function);
$("#search").on("click", search_function)
$("#search").on("focusout", function(){
  setTimeout(function(){ // Async remove so info opens first
    $("#search_results").remove();
  }, 100);

});

// Guest search on reservation creation
function form_search_function(){

  let val = $(this).val();
  $("#form_search_results").remove();
  if(val.length < 3)
    return;
  $(this).after(`<div id='form_search_results'>${loader_html()}</div>`);
  let top = $(this).position().top + 40;
  let left = $(this).position().left; // Idk about this - 10 but it checks out
  $("#form_search_results").css("top", top + "px");
  $("#form_search_results").css("left", left + "px");
  $("#form_search_results").css("max-height", `70vh`);
  $.ajax({
    url: api_link + 'data/search',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            keyword: val
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      $("#form_search_results").empty();
      // Save data and append
      if(sve.guests.length){
        $("#form_search_results").append("<div class='section_title'> Guests </div>");
      }
      else {
        $("#form_search_results").remove();
        return;
      }
      for(let i=0;i<sve.guests.length;i++){
        guests_map[sve.guests[i].id] = sve.guests[i];
        $("#form_search_results").append(compact_guest_html("form_search_guests", sve.guests[i]));
      }
      // Changes class to not trigger opening details
      $("#form_search_results .guest").addClass("form_guest");
      $("#form_search_results .guest").removeClass("guest");
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("An error occured. " + xhr.responseText);
    }
  });


}
$("body").on("input", ".form_res_guest_name, .form_res_guest_surname, .form_group_guest_name, .form_group_guest_surname", form_search_function);
$("body").on("click", ".form_res_guest_name, .form_res_guest_surname, .form_group_guest_name, .form_group_guest_surname", form_search_function)
$("body").on("focusout", ".form_res_guest_name, .form_res_guest_surname, .form_group_guest_name, .form_group_guest_surname",  function(){
  setTimeout(function(){ // Async remove so info opens first
    $("#form_search_results").remove();
  }, 100);

});
$("body").on("click", "#form_search_results .form_guest", function(e){
  e.stopPropagation();
  let id = $(this)[0].id.split("_");
  id = id[id.length - 1];
  let guest = guests_map[id];
  let res_guest_id = $(this).closest(".flex_around")[0].id;
  $(`#${res_guest_id} .form_res_guest_id`).val(guest.id);
  $(`#${res_guest_id} .form_res_guest_name`).val(guest.name);
  $(`#${res_guest_id} .form_res_guest_surname`).val(guest.surname);
  $(`#${res_guest_id} .form_res_guest_email`).val(guest.email);
  $(`#${res_guest_id} .form_res_guest_phone`).val(guest.phone);
  $(`#${res_guest_id} .form_group_guest_id`).val(guest.id);
  $(`#${res_guest_id} .form_group_guest_name`).val(guest.name);
  $(`#${res_guest_id} .form_group_guest_surname`).val(guest.surname);
  $(`#${res_guest_id} .form_group_guest_email`).val(guest.email);
  $(`#${res_guest_id} .form_group_guest_phone`).val(guest.phone);
});

});
