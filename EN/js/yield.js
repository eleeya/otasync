// Work in progress

let variations_list = [];
let variations_map = {};
let selected_variation = undefined;


$(document).ready(function(){

  $("#new_variation").click(function(){ // Clear values and show form
    $("#new_variation_type").val(-1).change();
    $("#new_variation_value").val(0);
    $(".variation.selected").removeClass("selected");
    selected_variation = undefined;
    $("#new_variation_container").show();
    $("#new_variation").hide();
  });
  $("#new_variation_cancel").click(function(){  // Hide form
    $("#new_variation_container").hide();
    $("#new_variation").show();
  });
  $("body").on("click", "#new_variation_confirm", function(){  // Insert new
    // Loaders
    $("#new_variation_confirm, #new_variation_cancel").addClass("button_loader");
    // Parameters
    let type = $("#new_variation_type").val();
    let value = $("#new_variation_value").val();
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'insert/yieldVariations',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        variation_type: type,
        variation_value: value
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Inserted variation`, sve.data.id); // Add changelog
        get_variations(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("An error occured. " + xhr.responseText);
      }
    });
  });

  $("#variations_list").on("click", ".delete", function(e){ // Show dialog and delete
    e.stopPropagation();
    let row_id = $(this).closest(".variation")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    if(confirm(`Are you sure you want to delete the variation?`)){
      $("#variations_list").html(loader_html()); // Temp loader with JS dialog
      $("#new_variation").hide();
      $("#new_variation_container").hide();
      $.ajax({
        type: 'POST',
        url: api_link + 'delete/yieldVariations',
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
          add_change(`Deleted variation`, sve.data.id); // Add changelog
          get_variations(); // Refresh data
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("An error occured. " + xhr.responseText);
        }
      });
    }
  });

  $("#variations_list").on("click", ".variation", function(){
    let row_id = $(this)[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    $("#new_variation_container").hide();
    $("#new_variation").show();
    $(".variation.selected").removeClass("selected");
    $(this).addClass("selected");
    selected_variation = variations_map[id];
  });
});

function get_variations(){
  $.ajax({
    url: api_link + 'data/yieldVariations/',
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
      variations_list = [];
      variations_map = {};
      var variations = sve.variations;
      for(var i=0;i<variations.length;i++){
        variations_list.push(variations[i].id);
        variations_map[variations[i].id] = variations[i];
      }
      display_variations();
    },
    error: function(xhr, textStatus, errorThrown){
      // Loading
      $("#login_confirm").removeClass("button_loader");
      window.alert("An error occured. " + xhr.responseText);
    }
  });
}

function display_variations(){
  selected_variation = undefined;
  // List
  $("#new_variation_cancel").click(); // Hide forms
  $("#variations_list").empty();
  for(let i=0;i<variations_list.length;i++){
    let variation = variations_map[variations_list[i]];
    // Data
    let id = variation.id;
    let type = variation.variation_type;
    let value = variation.variation_value;
    let amount = "";
    if(type == -2)
      amount = `-${value} EUR`;
    if(type == -1)
      amount = `-${value}%`;
    if(type == 1)
      amount = `+${value}%`;
    if(type == 2)
      amount = `+${value} EUR`;
    var variation_delete = `<div class='list_action'><img class='list_action_icon delete' title='Delete'> </div>`;
    $("#variations_list").append(`
      <div class="list_row variation" id='variations_list_${id}'>
        <div class='variation_amount'> ${amount} </div>
        <div class='variation_actions'> ${variation_delete} </div>
      </div>`);
  }
  if(variations_list.length == 0)
    $("#variations_list").append(empty_html("No variations"));
};

function display_yield(){

  // Fiksni parametri radi testiranja

  let dfrom = "2020-09-01";
  let dto = "2020-09-30";

  $.ajax({
    url: api_link + 'data/yieldPrices',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            plan_id: "96870",
            room_id: "288964"
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      prices = sve.prices;

      var days_list = range_of_dates(dfrom, num_of_nights(dfrom, dto) + 1); // Generisanje liste datuma od prvog do poslednjeg
      var yield_dates = "";
      console.log(days_list);
      for(var i=0;i<days_list.length;i++)
      {
        console.log(days_list[i]);
        console.log(prices);
        var price = prices[days_list[i]].price;

        var day = days_list[i].split("-")[2]; // Datum
        var day_name = (new Date(days_list[i])).getDay();
        day_name = day_names[day_name]; // Naziv dana
        if(price == undefined) // U slucaju da je izabran datum bez cene (za stare datume)
        {
          yield_dates += `
          <div class='yield_date'>
            <div class='yield_day'> <div>${day}</div> <div> ${day_name} </div> </div>
            <div class='yield_price'> </div>
            <div class='yield_delta'> </div>
          </div>`;
          continue;
        }
        var delta = prices[days_list[i]].fixed_variation; // Fiksna varijacija
        var delta_percent = prices[days_list[i]].percent_variation; // Procentualna varijacija
        var style_class = "";
        var sign = "";
        if(delta < 0) // Klase u zavisnosti da li je popust/poskupljenje
          style_class = " decrease";
        if(delta > 0)
        {
          style_class = " increase";
          sign = "+";
        }
        // Dodavanje htmla
        yield_dates += `
        <div class='yield_date'>
          <div class='yield_day'> <div>${day}</div> <div> ${day_name} </div> </div>
          <div class='yield_price'> ${price} ${currency} </div>
          <div class='yield_footer'>
            <div class='yield_avail'></div>
            <div class='yield_delta${style_class}'> <div> ${sign}${delta.toFixed(2)} ${currency} </div> <div> ${sign}${delta_percent.toFixed(2)}% </div> </div>
          </div>
        </div>`;
      }
      var yield_html =
      `
      <div class='yield_calendar'>
        <div class='yield_header'> </div>
        ${yield_dates}
      </div>
      `;

      $("#yield").empty();
      $("#yield").append(yield_html);
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });

}
