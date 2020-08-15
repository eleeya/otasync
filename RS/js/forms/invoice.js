let next_invoice_mark = "";


$(document).ready(function(){

// Input
// Select2
$("#form_invoice_status").select2({
    minimumResultsForSearch: Infinity,
    width: "element",
    templateSelection: function(state){
      let $state;
      if(state.id == 1){
        $state =  $(`<div class='form_invoice_paid_color'> ${state.text} </div>`);
      }
      else {
        $state =  $(`<div class='form_invoice_not_paid_color'> ${state.text} </div>`);
      }
      return $state;
    }
});
// Services insert
$("#form_invoice_new_service").click(function(){
  let currency = "EUR";
  if(account_access.articles > 0){
    currency = "RSD";
  }
  $("#form_invoice_services").append(`
    <div class='flex_around form_invoice_service'>
      <div class='form_invoice_service_name'> <input type='text' class='text_input form_invoice_service_name_input' placeholder='Unesite naziv'> </div>
      <div class='form_invoice_service_amount'> <input type='number' class='number_input form_invoice_service_amount_input' value=1> </div>
      <div class='form_invoice_service_price'> <input type='number' class='number_input form_invoice_service_price_input' value=0> ${currency} </div>
      <div class='form_invoice_service_tax'> <input type='number' class='number_input form_invoice_service_tax_input' value=0> ${currency} </div>
      <div class='form_invoice_service_total'> 0.00 ${currency} </div>
      <div class='form_invoice_service_total_tax'> 0.00 ${currency} </div>
      <div class='form_invoice_service_actions'> <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div> </div>
    </div>`);
});
// Services delete
$("#form_invoice_services").on("click", ".delete", function(){
  if($(".form_invoice_service").length == 1){
    $(".form_invoice_service_code").remove();
    $(".form_invoice_service_name_input").val("");
    $(".form_invoice_service_price_input").val(0);
    $(".form_invoice_service_amount_input").val(1);
    $(".form_invoice_service_tax_input").val(0);
    $(".form_invoice_service_total_input").val(0);
    $(".form_invoice_service_total_tax_input").val(0);
  }
  else {
    $(this).closest(".form_invoice_service").remove();
  }
  invoice_price_update();
});
// Updates
$("#form_invoice_services").on("input", ".number_input", invoice_price_update);

// Article service

$("#form_invoice_add_article").change(function(){
  if($(this).val() == -1)
    return;
  var article = all_articles_map[$(this).val()];
  let currency = "RSD";
  $(this).val(-1).change();
  $("#form_invoice_services").append(`
    <div class='flex_around form_invoice_service'>
      <input type='hidden' class='form_invoice_service_code' value='${article.code}'>
      <div class='form_invoice_service_name'> <input type='text' class='text_input form_invoice_service_name_input' placeholder='Unesite naziv' value='${article.description}'> </div>
      <div class='form_invoice_service_amount'> <input type='number' class='number_input form_invoice_service_amount_input' value=1> </div>
      <div class='form_invoice_service_price'> <input type='number' class='number_input form_invoice_service_price_input' value=${article.price}> ${currency} </div>
      <div class='form_invoice_service_tax'> <input type='number' class='number_input form_invoice_service_tax_input' value=${0}> ${currency} </div>
      <div class='form_invoice_service_total'> 0.00 ${currency} </div>
      <div class='form_invoice_service_total_tax'> 0.00 ${currency} </div>
      <div class='form_invoice_service_actions'> <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div> </div>
    </div>`);
  invoice_price_update();
});

// Open new
$("#new_invoice").click(function(){
  $("#tab_invoices").addClass("form_opened");

  // Header
  let property = properties_map[main_lcode];
  if(account_name == "NT072")
    property.name = "CBA TEAM D.O.O.";
  $("#form_invoice_id").val("");
  $("#form_invoice_property").text(property.name);
  $("#form_invoice_pib").text(property.pib);
  $("#form_invoice_mb").text(property.mb);
  $("#form_invoice_address").text(property.address);
  $("#form_invoice_bank_account").text(property.bank_account);
  $("#form_invoice_iban").text(property.iban);
  $("#form_invoice_swift").text(property.swift);
  $("#form_invoice_user").val(user_name);
  $("#form_invoice_logo").attr("src", property.logo);
  if(property.pdv_included == 0)
    $("#form_invoice_pdv").text("Obveznik nije u sistemu PDV-a");
  else
    $("#form_invoice_pdv").text("Obveznik je u sistemu PDV-a");

  // Info
  $("#form_invoice_type").val(2).change();
  $("#form_invoice_mark").val(next_invoice_mark);
  $("#form_invoice_status").val(1).change();
  $("#form_invoice_issued").datepicker().data('datepicker').selectDate(new Date());
  $("#form_invoice_delivery").datepicker().data('datepicker').selectDate(new Date());
  $("#form_invoice_payment_type").val(1).change();

  // Client
  $("#form_invoice_client_name").val("");
  $("#form_invoice_client_pib").val("");
  $("#form_invoice_client_mb").val("");
  $("#form_invoice_client_document_number").val("");
  $("#form_invoice_client_address").val("");
  $("#form_invoice_client_country").val("");
  $("#form_invoice_client_email").val("");
  $("#form_invoice_client_phone").val("");
  $("#form_invoice_client_reservation_name").val("");

  // Services
  $("#form_invoice_services").empty();
  let currency = "EUR";
  if(account_access.articles > 0){
    currency = "RSD";
  }
  $("#form_invoice_services").append(`
    <div class='flex_around form_invoice_service'>
      <div class='form_invoice_service_name'> <input type='text' class='text_input form_invoice_service_name_input' placeholder='Unesite naziv'> </div>
      <div class='form_invoice_service_amount'> <input type='number' class='number_input form_invoice_service_amount_input' value=1> </div>
      <div class='form_invoice_service_price'> <input type='number' class='number_input form_invoice_service_price_input' value=0> ${currency} </div>
      <div class='form_invoice_service_tax'> <input type='number' class='number_input form_invoice_service_tax_input' value=0> ${currency} </div>
      <div class='form_invoice_service_total'> 0.00 ${currency} </div>
      <div class='form_invoice_service_total_tax'> 0.00 ${currency} </div>
      <div class='form_invoice_service_actions'> <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div> </div>
    </div>`);

  // Total
  $("#form_invoice_total_price").text(`0.00 ${currency}`);
  $("#form_invoice_total_tax").text(`0.00 ${currency}`);
  $("#form_invoice_total_with_tax").text(`0.00 ${currency}`);
  $("#form_invoice_note").val("");
  $("#form_invoice_reservation_code").val("");


});
// Open from list
$("#invoices_list").on("click", ".edit, .small_invoice", function(){
  let id = $(this)[0].id.split("_");
  id = id[id.length - 1];
  let invoice = invoices_map[id];
  open_invoice_form(invoice);
});

// Insert
$("#form_invoice_confirm").click(function(){
  // Loaders
  $("#form_invoice_confirm, #form_invoice_cancel").addClass("button_loader");
  // Parameters
  let id = $("#form_invoice_id").val();
  let user = $("#form_invoice_user").val();
  let type = $("#form_invoice_type").val();
  let mark = $("#form_invoice_mark").val();
  let status = $("#form_invoice_status").val();
  let issued = date_to_iso($("#form_invoice_issued").datepicker().data('datepicker').selectedDates[0]);
  let delivery = date_to_iso($("#form_invoice_delivery").datepicker().data('datepicker').selectedDates[0]);
  let payment_type = $("#form_invoice_payment_type").val();
  let name = $("#form_invoice_client_name").val();
  let mb = $("#form_invoice_client_mb").val();
  let pib = $("#form_invoice_client_pib").val();
  let address = $("#form_invoice_client_address").val();
  let email = $("#form_invoice_client_email").val();
  let phone = $("#form_invoice_client_phone").val();
  let reservation_name  = $("#form_invoice_client_reservation_name").val();
  let services = [];
  $(".form_invoice_service").each(function(){
    let service = {};
    service["name"] = $(this).find(".form_invoice_service_name_input")[0].value;
    service["amount"] = parseFloat($(this).find(".form_invoice_service_amount_input")[0].value);
    service["price"] = parseFloat($(this).find(".form_invoice_service_price_input")[0].value);
    service["tax"] = parseFloat($(this).find(".form_invoice_service_tax_input")[0].value);
    if($(this).find(".form_invoice_service_code").length)
    service["code"] = $(this).find(".form_invoice_service_code")[0].value;
    services.push(service);
  });
  services = JSON.stringify(services);
  let note = $("#form_invoice_note").val();
  let price = $("#form_invoice_total_with_tax").text().split(" ")[0];
  let reservation_code = $("#form_invoice_reservation_code").val();
  // Call
  let action = id == "" ? 'insert/invoice' : 'edit/invoice';
  $.ajax({
    type: 'POST',
    url: api_link + action,
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: id,
      user: user,
      type: type,
      mark: mark,
      status: status,
      issued: issued,
      delivery: delivery,
      payment_type: payment_type,
      name: name,
      pib: pib,
      mb: mb,
      address: address,
      email: email,
      phone: phone,
      reservation_name: reservation_name,
      services: services,
      price: price,
      note: note,
      reservation_code: reservation_code
    },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      if(id == "")
        add_change(`Dodat račun ${mark}`, sve.data.id); // Add changelog
      else
        add_change(`Izmenjen račun ${mark}`, sve.data.id); // Add changelog
      $("#form_invoice_cancel").click();
      invoices_page = 0;
      get_invoices(); // Refresh data
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
});

// Close
$("#form_invoice_cancel").click(function(){
  $("#new_invoice").click();
});

// Export
$("#form_invoice_print").click(function(){


  $("#form_invoice input").each(function(){
    $(this).attr("value", $(this).val());
  });
  $("#form_invoice textarea").each(function(){
    $(this).attr("value", $(this).val());
  });
  $("#form_invoice_issued").attr("value", iso_to_eur(date_to_iso($("#form_invoice_issued").datepicker().data('datepicker').selectedDates[0])));
  $("#form_invoice_delivery").attr("value", iso_to_eur(date_to_iso($("#form_invoice_delivery").datepicker().data('datepicker').selectedDates[0])));

  $("#form_invoice_export_action").val("print");
  hide_empty_invoice_fields();
  $("#form_invoice_export_html").val($("#form_invoice").html());
  $("#form_invoice .hidden").css("display", "");
  $("#form_invoice .hidden").removeClass("hidden");
  $("#form_invoice_export_form").submit();
});

$("#form_invoice_pdf").click(function(){

  $("#form_invoice input").each(function(){
    $(this).attr("value", $(this).val());
  });
  $("#form_invoice textarea").each(function(){
    $(this).attr("value", $(this).val());
  });
  $("#form_invoice_issued").attr("value", iso_to_eur(date_to_iso($("#form_invoice_issued").datepicker().data('datepicker').selectedDates[0])));
  $("#form_invoice_delivery").attr("value", iso_to_eur(date_to_iso($("#form_invoice_delivery").datepicker().data('datepicker').selectedDates[0])));

  $("#form_invoice_export_action").val("pdf");
  hide_empty_invoice_fields();
  $("#form_invoice_export_html").val($("#form_invoice").html());
  $("#form_invoice .hidden").css("display", "");
  $("#form_invoice .hidden").removeClass("hidden");
  $("#form_invoice_export_form").submit();
});

});

function invoice_price_update(){
  let total_price = 0;
  let total_tax = 0;
  let total_with_tax = 0;
  let currency = "EUR";
  if(account_access.articles > 0){
    currency = "RSD";
  }
  $(".form_invoice_service").each(function(){
    let amount = parseFloat($(this).find(".form_invoice_service_amount_input")[0].value);
    let price = parseFloat($(this).find(".form_invoice_service_price_input")[0].value);
    let tax = parseFloat($(this).find(".form_invoice_service_tax_input")[0].value);
    if(amount < 0 || isNaN(amount))
      amount = 0;
    if(price < 0 || isNaN(price))
      price = 0;
    if(tax < 0 || isNaN(tax))
      tax = 0;

    let service_total_price = amount*(price);
    service_total_price = isNaN(service_total_price) ? 0 : service_total_price;
    let service_total_tax = amount*(tax);
    service_total_tax = isNaN(service_total_tax) ? 0 : service_total_tax;
    $(this).find(".form_invoice_service_amount_input").val(amount);
    $(this).find(".form_invoice_service_price_input").val(price);
    $(this).find(".form_invoice_service_tax_input").val(tax);
    $(this).find(".form_invoice_service_total").text(service_total_price.toFixed(2) + ` ${currency}`);
    $(this).find(".form_invoice_service_total_tax").text(service_total_tax.toFixed(2) + ` ${currency}`);
    total_price += service_total_price;
    total_tax += service_total_tax;
    total_with_tax += (service_total_price + service_total_tax);
  });
  $("#form_invoice_total_price").text(total_price.toFixed(2) + ` ${currency}`);
  $("#form_invoice_total_tax").text(total_tax.toFixed(2) + ` ${currency}`);
  $("#form_invoice_total_with_tax").text(total_with_tax.toFixed(2) + ` ${currency}`);

}

function open_res_invoice(res_id){
  let res = all_reservations[res_id];
  $("#tab_invoices").addClass("form_opened");

  // Header
  let property = properties_map[main_lcode];
  if(account_name == "NT072")
    property.name = "CBA TEAM D.O.O.";
  $("#form_invoice_id").val("");
  $("#form_invoice_property").text(property.name);
  $("#form_invoice_pib").text(property.pib);
  $("#form_invoice_mb").text(property.mb);
  $("#form_invoice_address").text(property.address);
  $("#form_invoice_bank_account").text(property.bank_account);
  $("#form_invoice_iban").text(property.iban);
  $("#form_invoice_swift").text(property.swift);
  $("#form_invoice_user").val(user_name);
  $("#form_invoice_logo").attr("src", property.logo);

  // Info
  $("#form_invoice_type").val(2).change();
  $("#form_invoice_mark").val(next_invoice_mark);
  $("#form_invoice_status").val(1).change();
  $("#form_invoice_issued").datepicker().data('datepicker').selectDate(new Date());
  if($("#account_invoice_delivery").val() == "dfrom"){
    $("#form_invoice_issued").datepicker().data('datepicker').selectDate(new Date(res.date_arrival));
  }
  $("#form_invoice_delivery").datepicker().data('datepicker').selectDate(new Date());
  if($("#account_invoice_due").val() == "dto"){
    $("#form_invoice_delivery").datepicker().data('datepicker').selectDate(new Date(res.date_departure));
  }
  $("#form_invoice_payment_type").val(1).change();
  // Client
  $("#form_invoice_client_name").val(res.customer_name + " " + res.customer_surname);
  $("#form_invoice_client_pib").val("");
  $("#form_invoice_client_mb").val("");
  $("#form_invoice_client_address").val(res.customer_address);
  $("#form_invoice_client_email").val(res.customer_email);
  $("#form_invoice_client_phone").val(res.customer_phone);
  $("#form_invoice_client_reservation_name").val(res.customer_name + " " + res.customer_surname);
  // Services
  let exchange_rate = 1;
  let currency = "EUR";

  if(account_access.articles > 0){
    currency = "RSD";
    exchange_rate = parseFloat($("#settings_articles_course").val());
  }

  $("#form_invoice_services").empty();
  for(let i=0;i<res.room_data.length;i++){
    let room = res.room_data[i];
    let code = "";
    if(account_access.articles == 3){
      let article = all_articles_name_map[rooms_map[room.id].shortname].code;
      code = `<input type='hidden' class='form_invoice_service_code' value='${article}'>`;
    }

    let service_price = room.price * exchange_rate;
    let service_tax = 0;
    if(properties_map[main_lcode].rooms_tax_included == 1){
      let tax = properties_map[main_lcode].rooms_tax;
      let total_service_price = service_price;
      // service_price + service_price * tax / 100 = total_service_price
      // service_price * (1 + tax / 100) = total_service_price
      // service_price = total_service_price / (1 + tax / 100)
      service_price = (total_service_price / (1 + tax / 100)).toFixed(2);
      service_tax = (total_service_price - service_price).toFixed(2);
      console.log(service_price, service_tax);
    }
    $("#form_invoice_services").append(`
      <div class='flex_around form_invoice_service'>
        ${code}
        <div class='form_invoice_service_name'> <input type='text' class='text_input form_invoice_service_name_input' placeholder='Unesite naziv' value='${room.name}'> </div>
        <div class='form_invoice_service_amount'> <input type='number' class='number_input form_invoice_service_amount_input' value=${room.count * res.nights}> </div>
        <div class='form_invoice_service_price'> <input type='number' class='number_input form_invoice_service_price_input' value=${service_price}> ${currency} </div>
        <div class='form_invoice_service_tax'> <input type='number' class='number_input form_invoice_service_tax_input' value=${service_tax}> ${currency} </div>
        <div class='form_invoice_service_total'> 0.00 ${currency} </div>
        <div class='form_invoice_service_total_tax'> 0.00 ${currency} </div>
        <div class='form_invoice_service_actions'> <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div> </div>
      </div>`);

  }

  // Total
  $("#form_invoice_total_price").text("0.00 " + `${currency}`);
  $("#form_invoice_total_tax").text("0.00 " + `${currency}`);
  $("#form_invoice_total_with_tax").text("0.00 " + `${currency}`);
  $("#form_invoice_note").val("");
  $("#form_invoice_reservation_code").val("");
  invoice_price_update();
};

function open_invoice_form(invoice){
  $("#tab_invoices").addClass("form_opened");
  let property = properties_map[main_lcode];
  if(account_name == "NT072")
    property.name = "CBA TEAM D.O.O.";
  // Header
  $("#form_invoice_id").val(invoice.id);
  $("#form_invoice_property").text(property.name);
  $("#form_invoice_pib").text(property.pib);
  $("#form_invoice_mb").text(property.mb);
  $("#form_invoice_address").text(property.address);
  $("#form_invoice_bank_account").text(property.bank_account);
  $("#form_invoice_iban").text(property.iban);
  $("#form_invoice_swift").text(property.swift);
  $("#form_invoice_logo").attr("src", property.logo);
  $("#form_invoice_user").val(invoice.user);

  // Info
  $("#form_invoice_type").val(invoice.type).change();
  $("#form_invoice_mark").val(invoice.mark);
  $("#form_invoice_status").val(invoice.status).change();
  $("#form_invoice_issued").datepicker().data('datepicker').selectDate(new Date(invoice.issued));
  $("#form_invoice_delivery").datepicker().data('datepicker').selectDate(new Date(invoice.delivery));
  $("#form_invoice_payment_type").val(invoice.payment_type).change();

  // Client
  $("#form_invoice_client_name").val(invoice.name);
  $("#form_invoice_client_pib").val(invoice.pib);
  $("#form_invoice_client_mb").val(invoice.mb);
  $("#form_invoice_client_document_number").val(invoice.document_number);
  $("#form_invoice_client_address").val(invoice.address);
  $("#form_invoice_client_country").val(invoice.country);
  $("#form_invoice_client_email").val(invoice.email);
  $("#form_invoice_client_phone").val(invoice.phone);
  $("#form_invoice_client_reservation_name").val(invoice.reservation_name);

  // Services
  $("#form_invoice_services").empty();
  for(let i=0;i<invoice.services.length;i++){
    let service = invoice.services[i];
    let currency = "EUR";
    if(account_access.articles > 0){
      currency = "RSD";
    }
    let code = "";
    if(service.code !== undefined)
      code = `<input type='hidden' class='form_invoice_service_code' value='${service.code}'>`;
    $("#form_invoice_services").append(`
      <div class='flex_around form_invoice_service'>
        ${code}
        <div class='form_invoice_service_name'> <input type='text' class='text_input form_invoice_service_name_input' placeholder='Unesite naziv' value='${service.name}'> </div>
        <div class='form_invoice_service_amount'> <input type='number' class='number_input form_invoice_service_amount_input' value=${service.amount}> </div>
        <div class='form_invoice_service_price'> <input type='number' class='number_input form_invoice_service_price_input' value=${service.price}> ${currency} </div>
        <div class='form_invoice_service_tax'> <input type='number' class='number_input form_invoice_service_tax_input' value=${service.tax}> ${currency} </div>
        <div class='form_invoice_service_total'> 0.00 ${currency} </div>
        <div class='form_invoice_service_total_tax'> 0.00 ${currency} </div>
        <div class='form_invoice_service_actions'> <div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div> </div>
      </div>`);
  }
  $("#form_invoice_note").val(invoice.note);
  $("#form_invoice_reservation_code").val(invoice.reservation_code);
  invoice_price_update();
};

function hide_empty_invoice_fields(){

  $("#form_invoice_header > .flex_start").each(function(){
    let hide = false;
    $(this).find("div").each(function(){
      let text = $(this).text();
      if(text == "")
        hide = true;
    });
    $(this).find("input").each(function(){
      let text = $(this).val();
      if(text == "")
        hide = true;
    });
    if(hide){
      $(this).css("display", "none");
      $(this).addClass("hidden");
    }
  });
  $("#form_invoice_client > .flex_between").each(function(){
    let hide = false;
    $(this).find("input").each(function(){
      let text = $(this).val();
      if(text == "")
        hide = true;
    });
    if(hide){
      $(this).css("display", "none");
      $(this).addClass("hidden");
    }
  });

};
