let invoices_page = 0;
let total_invoices_pages = 1;

invoices_map = {};

$(document).ready(function(){

  // Displaying invoice from reservation
  $("body").on("click", "#res_info_invoice", function(){
    let id = $(this).attr("data-value");
    click_to_hide();
    window.location.hash = "invoices";
    hash_change();
    open_res_invoice(id);
  });
  $("#reservations_list").on("click", ".invoice", function(){ // Show dialog and delete
    window.location.hash = "invoices";
    hash_change();
    event.stopPropagation();
    let row_id = $(this).closest(".reservation")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    open_res_invoice(id);
  });

// Filter
$("#invoices_filter_clear").click(function(){
  $("#invoices_filter_dfrom").datepicker().data('datepicker').clear();
  $("#invoices_filter_dto").datepicker().data('datepicker').clear();
  $("#invoices_filter .custom_checkbox").attr("data-value", 0);
  $("#invoices_filter .checkbox_value").removeClass("checked");
  hide_modals();
});
$("#invoices_filter").click(function(){
  invoices_page = 0;
})

// Edit paid
$("body").on("click", "#invoices_list .switch_button", function(){ // Show dialog and delete
  let row_id = $(this).closest(".invoice")[0].id;
  let id = row_id[row_id.length - 1];
  let status = $(this).attr('data-value');
  $.ajax({
    type: 'POST',
    url: api_link + "edit/invoiceStatus",
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: id,
      status: status
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      invoices_map[id].status = status;
      add_change(`Edited invoice ${invoices_map[id].mark}`, sve.data.id); // Add changelog
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("An error occured. " + xhr.responseText);
    }
  });
});

// Delete
$("#invoices_list").on("click", ".delete", function(){ // Show dialog and delete
  let row_id = $(this).closest(".small_invoice")[0].id;
  let id = row_id.split("_");
  id = id[id.length - 1];
  let invoice = invoices_map[id];
  if(confirm(`Are you sure you want to delete invoice ${invoice.mark}`)){
    $.ajax({
      type: 'POST',
      url: api_link + 'delete/invoice',
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
        add_change(`Deleted invoice ${invoice.mark}`, sve.data.id); // Add changelog
        invoices_page = 0;
        get_invoices(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("An error occured. " + xhr.responseText);
      }
    });
  }
});

// Open invoice from search
$("body").on("click", "#search_results .invoice", function(){
  let id = $(this)[0].id.split("_");
  id = id[id.length - 1];
  window.location.hash = "invoices";
  hash_change();
  open_invoice_form(invoices_map[id]);

});

});

function get_invoices()
{
  if(invoices_page == 0) // Only load once
    invoices_page = 1;
  // Clear list
  if(invoices_page == 1)
    $("#invoices_list").html("<div class='loader'><div class='loader_icon'></div></div>");
  else
    $("#invoices_list").append("<div class='loader'><div class='loader_icon'></div></div>");
  // Parameters
  let type = [];
  $("[id^=invoices_filter_type]").each(function(){
    if($(this).attr("data-value") == 1){
      let val = $(this)[0].id.split("_");
      val = val[val.length - 1];
      type.push(val)
    }
  });
  let status = [];
  $("[id^=invoices_filter_status]").each(function(){
    if($(this).attr("data-value") == 1){
      let val = $(this)[0].id.split("_");
      val = val[val.length - 1];
      status.push(val)
    }
  });
  $.ajax({
    url: api_link + 'data/invoices',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: date_to_iso($("#invoices_filter_dfrom").datepicker().data('datepicker').selectedDates[0]),
            dto: date_to_iso($("#invoices_filter_dto").datepicker().data('datepicker').selectedDates[0]),
            type: JSON.stringify(type),
            status: JSON.stringify(status),
            page: invoices_page
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      next_invoice_mark = sve.next_invoice_mark;
      display_invoices(sve);
    },
    error: function(xhr, textStatus, errorThrown){
      invoices_page = 0;
      window.alert("An error occured. " + xhr.responseText);
    }
  });
};

function display_invoices(data){
  let invoices_list = data.invoices;
  invoices_page = parseInt(data.page);
  total_invoices_pages = parseInt(data.total_pages_number);
  // Clear list
  if(invoices_page == 1)
    $("#invoices_list").empty();
  else
    $("#invoices_list").find(".loader").remove();
  // Display data
  for(let i=0;i<invoices_list.length;i++){
    invoices_map[invoices_list[i].id] = invoices_list[i];
    $("#invoices_list").append(invoice_html("invoices_list", invoices_list[i], (invoices_page - 1) * 20 + i+1));
  }
  if(invoices_list.length == 0 && invoices_page == 1){
    $("#invoices_list").append(empty_html("No invoices"));
  }

}

function invoice_html(loc, invoice, row_number) {
  var id = invoice.id;
  var created_date = iso_to_eur(invoice.created_date);
  var created_time = invoice.created_time;
  var mark = invoice.mark;
  var type = "<div>Invoice</div>";
  if(invoice.type == 1)
    type = "<div>Advance</div>";
  var status = invoice.status;
  var client_name = invoice.name;
  var client_email = invoice.email;
  var price = invoice.price;
  var paid_class = status == 1 ? "paid" : "notpaid";
  var invoice_edit = `<div class='list_action'><img class='list_action_icon edit' title='Edit' id='invoice_edit_${id}'> </div>`;
  var invoice_send = `<div class='list_action'><img class='list_action_icon mail' title='Send'> </div>`;
  var invoice_delete = `<div class='list_action'><img class='list_action_icon delete' title='Delete' id='invoice_delete_${id}'> </div>`;
  let currency = "EUR";
  if(account_access.articles > 0){
    currency = "RSD";
  }
  var ret_val = `
    <div class="list_row small_invoice" id='${loc}_${id}'>
      <div class='invoice_data'>
        <div class='small_invoice_gray'>
         ${created_date} ${created_time}
        </div>
        <div class='small_invoice_name'>
          ${client_name}
        </div>
        <div class='small_invoice_gray'>
         ${mark}
        </div>
        <div class='small_invoice_amount ${paid_class}'>
          ${price} ${currency}
        </div>
      </div>
      <div class='invoice_actions'>
        ${invoice_edit} ${invoice_delete}
      </div>
    </div>`;
  return ret_val;
}

function compact_invoice_html(loc, invoice) {
  var id = invoice.id;
  var created_date = iso_to_eur(invoice.created_date);
  var created_time = invoice.created_time;
  var mark = invoice.mark;
  var type = "<div>Invoice</div>";
  if(invoice.type == 1)
    type = "<div>Advance</div>";
  var status = invoice.status;
  var client_name = invoice.name;
  var client_email = invoice.email;
  var price = invoice.price;
  let currency = "EUR";
  if(account_access.articles > 0){
    currency = "RSD";
  }
  var ret_val = `
    <div class="list_row invoice" id='${loc}_${id}'>
      <div class='invoice_created'>
        <div class='invoice_date'>${created_date}</div>
        <div class='invoice_time'>${created_time}</div>
      </div>
      <div class='invoice_mark'>
        ${mark}
      </div>
      <div class='invoice_client'>
        <div class='invoice_client_name'>
          ${client_name}
        </div>
        <div class='invoice_client_email'>
          ${client_email}
        </div>
      </div>
      <div class='invoice_amount'>
        ${price} ${currency}
      </div>
    </div>`;
  return ret_val;
}
