$(document).ready(function(){

// Open New
$("#new_user").on("click", function(){
  // Show form
  $("#form_user").show();
  scroll_lock();
  // Clear values
  $("#form_user h1").text("Unos novog korisnika");
  $("#form_user_id").val("");
  $("#form_user_name").val("");
  $("#form_user_email").val("");
  $('#form_user_email').attr('readonly', false);
  $('#form_user_email').removeClass("readonly");
  $("#form_user_reservations").val(0);
  $("#form_user_reservations").change();
  $("#form_user_guests").val(0);
  $("#form_user_guests").change();
  $("#form_user_invoices").val(0);
  $("#form_user_invoices").change();
  $("#form_user_prices").val(0);
  $("#form_user_prices").change();
  $("#form_user_restrictions").val(0);
  $("#form_user_restrictions").change();
  $("#form_user_avail").val(0);
  $("#form_user_avail").change();
  $("#form_user_rooms").val(0);
  $("#form_user_rooms").change();
  $("#form_user_channels").val(0);
  $("#form_user_channels").change();
  $("#form_user_statistics").val(0);
  $("#form_user_statistics").change();
  $("#form_user_changelog").val(0);
  $("#form_user_changelog").change();
  $("#form_user .custom_checkbox").attr('data-value', '0');
  $("#form_user .checked").removeClass("checked");

});
// Close
$("#form_user_cancel").click(function(){
    $("#form_user").hide();
    $("html, body").css("overflow", "");
  });

// Open edit

// Open from list
$("#users_list").on("click", ".edit", function(e){
  e.stopPropagation();
  let id  = $(this).closest(".user")[0].id.split("_");
  id = id[id.length - 1];
  let user = users_map[id];
  click_to_hide();
  scroll_lock();
  open_user_form(user);
});

// Insert

$("#form_user_confirm").click(function(){
  // Loaders
  $("#form_user_confirm, #form_user_cancel").addClass("button_loader");
  // Parameters
  let id = $("#form_user_id").val();
  let name = $("#form_user_name").val();
  let email = $("#form_user_email").val();
  var properties = [];
  for(let i=0;i<properties_list.length;i++){
    if($("#form_user_property_"+properties_list[i]).attr('data-value') == 1){
      properties.push(properties_list[i]);
    }
  }
  properties = properties.join(",");
  // Call
  let action = id == "" ? 'insert/user' : 'edit/user';
  $.ajax({
    type: 'POST',
    url: api_link + action,
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: $("#form_user_id").val(),
      name: name,
      email: email,
      reservations: $("#form_user_reservations").val(),
      guests: $("#form_user_guests").val(),
      invoices: $("#form_user_invoices").val(),
      prices: $("#form_user_prices").val(),
      restrictions: $("#form_user_restrictions").val(),
      avail: $("#form_user_avail").val(),
      rooms: $("#form_user_rooms").val(),
      channels: $("#form_user_channels").val(),
      statistics: $("#form_user_statistics").val(),
      changelog: $("#form_user_changelog").val(),
      properties: properties
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      if(id == "")
        add_change(`Dodat korisnik ${name}`, sve.data.id); // Add changelog
      else
        add_change(`Izmjenjen korisnik ${name}`, sve.data.id); // Add changelog
      $("#form_user_cancel").click();
      get_users(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

// Access edit
$("#form_user_reservations").change(function(){
  var val = $("#form_user_reservations").val();
  if(val == 0)
    $("#form_user_reservations_tooltip").text("Korisnik neće moći da vidi rezervacije.");
  if(val == 1)
    $("#form_user_reservations_tooltip").text("Korisnik će moći da vidi, ali ne i da dodaje, menja ili briše rezervacije.");
  if(val == 2)
    $("#form_user_reservations_tooltip").text("Korisnik će moći da vidi i dodaje rezervacije, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_reservations_tooltip").text("Korisnik će imati puna prava da vidi, dodaje, menja i briše sve rezervacije.");
});
$("#form_user_guests").change(function(){
  var val = $("#form_user_guests").val();
  if(val == 0)
    $("#form_user_guests_tooltip").text("Korisnik neće moći da vidi goste i kompanije.");
  if(val == 1)
    $("#form_user_guests_tooltip").text("Korisnik će moći da vidi, ali ne i da dodaje, menja ili briše goste.");
  if(val == 2)
    $("#form_user_guests_tooltip").text("Korisnik će moći da vidi i dodaje goste, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_guests_tooltip").text("Korisnik će imati puna prava da vidi, dodaje, menja i briše sve goste.");
});
$("#form_user_invoices").change(function(){
  var val = $("#form_user_invoices").val();
  if(val == 0)
    $("#form_user_invoices_tooltip").text("Korisnik neće moći da vidi račune.");
  if(val == 1)
    $("#form_user_invoices_tooltip").text("Korisnik će moći da vidi, ali ne i da dodaje, menja ili briše račune.");
  if(val == 2)
    $("#form_user_invoices_tooltip").text("Korisnik će moći da vidi i dodaje račune, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_invoices_tooltip").text("Korisnik će imati puna prava da vidi, dodaje, menja i briše sve račune.");
});
$("#form_user_prices").change(function(){
  var val = $("#form_user_prices").val();
  if(val == 0)
    $("#form_user_prices_tooltip").text("Korisnik neće moći da vidi cijenovnike.");
  if(val == 1)
    $("#form_user_prices_tooltip").text("Korisnik će moći da vidi, ali ne i da menja cijenovnike.");
  if(val == 2)
    $("#form_user_prices_tooltip").text("Korisnik će moći da vidi i dodaje cijenovnike, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_prices_tooltip").text("Korisnik će imati puna prava da vidi i menja sve cijenovnike.");
});
$("#form_user_restrictions").change(function(){
  var val = $("#form_user_restrictions").val();
  if(val == 0)
    $("#form_user_restrictions_tooltip").text("Korisnik neće moći da vidi restrikcije.");
  if(val == 1)
    $("#form_user_restrictions_tooltip").text("Korisnik će moći da vidi, ali ne i da menja restrikcije.");
  if(val == 2)
    $("#form_user_restrictions_tooltip").text("Korisnik će moći da vidi i dodaje restrikcije, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_restrictions_tooltip").text("Korisnik će imati puna prava da vidi i menja sve restrikcije.");
});
$("#form_user_avail").change(function(){
  var val = $("#form_user_avail").val();
  if(val == 0)
    $("#form_user_avail_tooltip").text("Korisnik neće moći da vidi raspoloživost i zatvaranje kanala.");
  if(val == 1)
    $("#form_user_avail_tooltip").text("Korisnik će moći da vidi, ali ne i da menja raspoloživost i zatvaranje kanala.");
  if(val == 3)
    $("#form_user_avail_tooltip").text("Korisnik će imati puna prava da vidi i menja raspoloživost i zatvaranje kanala.");
});
$("#form_user_rooms").change(function(){
  var val = $("#form_user_rooms").val();
  if(val == 0)
    $("#form_user_rooms_tooltip").text("Korisnik neće moći da vidi podešavanja jedinica.");
  if(val == 1)
    $("#form_user_rooms_tooltip").text("Korisnik će moći da vidi, ali ne i da dodaje, menja ili briše jedinice.");
  if(val == 2)
    $("#form_user_rooms_tooltip").text("Korisnik će moći da vidi i dodaje jedinica, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_rooms_tooltip").text("Korisnik će imati puna prava da vidi i menja podešavanja jedinica.");
});
$("#form_user_channels").change(function(){
  var val = $("#form_user_channels").val();
  if(val == 0)
    $("#form_user_channels_tooltip").text("Korisnik neće moći da vidi podešavanja kanala.");
  if(val == 1)
    $("#form_user_channels_tooltip").text("Korisnik će moći da vidi, ali ne i da dodaje, menja ili briše prodajne kanale.");
  if(val == 2)
    $("#form_user_channels_tooltip").text("Korisnik će moći da vidi i dodaje prodajne kanale, ali ne i da ih menja i briše (osim onih koje je lično uneo).");
  if(val == 3)
    $("#form_user_channels_tooltip").text("Korisnik će imati puna prava da vidi i menja podešavanja kanala.");
});
$("#form_user_statistics").change(function(){
  var val = $("#form_user_statistics").val();
  if(val == 0)
    $("#form_user_statistics_tooltip").text("Korisnik neće moći da vidi statistiku.");
  if(val == 1)
    $("#form_user_statistics_tooltip").text("Korisnik će moći da vidi statistiku.");
});
$("#form_user_changelog").change(function(){
  var val = $("#form_user_changelog").val();
  if(val == 0)
    $("#form_user_changelog_tooltip").text("Korisnik neće moći da vidi changelog.");
  if(val == 1)
    $("#form_user_changelog_tooltip").text("Korisnik će moći da vidi changelog.");
});

});


function open_user_form(user){
  $("#form_user h1").text("Ažuriranje korisnika");
  $("#form_user_id").val(user.id);
  $("#form_user_name").val(user.client_name);
  $("#form_user_email").val(user.email);
  $('#form_user_email').attr('readonly', true);
  $('#form_user_email').addClass("readonly");
  $("#form_user_reservations").val(user.reservations);
  $("#form_user_reservations").change();
  $("#form_user_guests").val(user.guests);
  $("#form_user_guests").change();
  $("#form_user_invoices").val(user.invoices);
  $("#form_user_invoices").change();
  $("#form_user_prices").val(user.prices);
  $("#form_user_prices").change();
  $("#form_user_restrictions").val(user.restrictions);
  $("#form_user_restrictions").change();
  $("#form_user_avail").val(user.avail);
  $("#form_user_avail").change();
  $("#form_user_rooms").val(user.rooms);
  $("#form_user_rooms").change();
  $("#form_user_channels").val(user.channels);
  $("#form_user_channels").change();
  $("#form_user_statistics").val(user.statistics);
  $("#form_user_statistics").change();
  $("#form_user_changelog").val(user.changelog);
  $("#form_user_changelog").change();
  $("#form_user .custom_checkbox").attr('data-value', '0');
  $("#form_user .checked").removeClass("checked");
  var props = user.properties;
  for(var i=0;i<props.length;i++)
  {
    set_checkbox("form_user_property_" + props[i], 1);
  }
  $("#form_user_error").text("");
  $("#form_user").show();
  scroll_lock();
}
