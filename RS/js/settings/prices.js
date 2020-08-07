$(document).ready(function(){

  // Prices

  $("#default_price").change(function(){ // Default price update
    let val = $(this).val();
    update_property("default_price", val);
  });
  // New
  $("#new_price").click(function(){ // Clear values and show form

    $("#new_price_name").val("");
    set_switch("new_price_type", 1);
    $(".new_price_virtual").show();
    $("#new_price_master").prop("selectedIndex", 0).change();
    $("#new_price_variation_type").prop("selectedIndex", 0).change();
    $("#new_price_variation").val(0);
    $("#new_price_policy").prop("selectedIndex", 0).change();
    set_checkbox("new_price_engine", 0);
    $("#new_price_board").prop("selectedIndex", 0).change();
    $("#new_price_restriction").prop("selectedIndex", 0).change();
    $("#new_price_description").val("");

    $("#new_price").hide();
    $("#new_price_container").show();

    // Hide open edits
    $("#edit_price_container").remove();
    $(".pricing_plan.selected").removeClass("selected");
  });
  $("body").on("click", "#new_price_type", function(){ // Toggle virtual plan form
    let val = $("#new_price_type").attr("data-value");
    if(val == 1)
      $(".new_price_virtual").show();
    else
      $(".new_price_virtual").hide();
  });
  $("#new_price_cancel").click(function(){ // Hide form
    $("#new_price_container").hide();
    $("#new_price").show();
  });
  $("body").on("click", "#new_price_confirm", function(){  // Insert new
    // Loaders
    $("#new_price_confirm, #new_price_cancel").addClass("button_loader");
    // Parameters
    let name = $("#new_price_name").val();
    let type = $("#new_price_type").attr("data-value") == 1 ? "virtual" : "daily";
    let vpid = $("#new_price_master").val();
    let variation = $("#new_price_variation").val();
    let variation_type = $("#new_price_variation_type").val();
    let policy = $("#new_price_policy").val();
    let engine = $("#new_price_engine").attr('data-value');
    let board = $("#new_price_board").val();
    let restriction = $("#new_price_restriction").val();
    let description = $("#new_price_description").val();
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'insert/pricingPlan',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        name: name,
        type: type,
        vpid: vpid,
        variation: variation,
        variation_type: variation_type,
        policy: policy,
        booking_engine: engine,
        board: board,
        restriction_plan: restriction,
        description: description
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Dodat plan cena ${name}`, sve.data.id); // Add changelog
        get_pricing_plans(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  // Edit
  $("#pricing_plans_list").on("click", ".edit", function(){ // Appends edit form
    let row_id = $(this).closest(".pricing_plan")[0].id;
    if($("#edit_price_container").length && $(`#${row_id}`).hasClass("selected")){ // This plan edit is open
      return;
    }
    else {
      $("#edit_price_container").remove();
      $(".pricing_plan.selected").removeClass("selected");
      $(`#${row_id}`).addClass("selected");
      // Plan id
      let id = row_id.split("_");
      id = id[id.length - 1];
      let plan = pricing_plans_map[id];
      // Append HTML
      if(plan.type == "daily"){
        $(`#${row_id}`).after(`
          <div id='edit_price_container'>
          <input type='hidden' id='edit_price_id' value='${id}'>
            <div class='flex_center'>
              <div class="vert_center">
                <div>Naziv cenovnika</div>
                <input type='text' class='text_input' id='edit_price_name'>
              </div>
            </div>
            <div class='flex_between'>
              <div class="vert_center">
                <div>Politika otkazivanja</div>
                <select class='basic_select policy_select' id='edit_price_policy'>
                </select>
              </div>
              <div class="flex_center">
                <div class="custom_checkbox dynamic" id="edit_price_engine" data-value="0"> <img class="checkbox_value"> </div>
                Koristi za Booking Engine
              </div>
            </div>
            <div class='flex_between'>
              <div class="vert_center">
                <div>Plan restrikcija</div>
                <select class='basic_select restriction_select' id='edit_price_restriction'>
                  <option value='0'> Bez restrikcija </option>
                </select>
              </div>
              <div class="vert_center">
                <div>Obroci</div>
                <select class='basic_select' id='edit_price_board'>
                  <option value='nb'> Samo soba </option>
                  <option value='bb'> Doručak </option>
                  <option value='hb'> Polupansion </option>
                  <option value='fb'> Pun pansion </option>
                  <option value='ai'> All inclusive </option>
                </select>
              </div>
            </div>
            <div class="vert_center">
              <div>Opis</div>
              <textarea class='textarea_input' id='edit_price_description'></textarea>
            </div>
            <div class='flex_center'>
              <button class='cancel_button' id='edit_price_cancel'> PONIŠTI </button>
              <button class='confirm_button' id='edit_price_confirm'> SAČUVAJ </button>
            </div>
          </div>`);
      }
      else {
        $(`#${row_id}`).after(`
          <div id='edit_price_container'>
            <input type='hidden' id='edit_price_id' value='${id}'>
            <div class='flex_center'>
              <div class="vert_center">
                <div>Naziv cenovnika</div>
                <input type='text' class='text_input' id='edit_price_name'>
              </div>
            </div>
            <div class='flex_between'>
              <div class="vert_center">
                <div>Tip varijacije</div>
                <select class='basic_select' id='edit_price_variation_type'>
                  <option value='-2'> - (EUR) </option>
                  <option value='-1'> - (%) </option>
                  <option value='1'> + (%) </option>
                  <option value='2'> + (EUR) </option>
                </select>
              </div>
              <div class="vert_center">
                <div>Varijacija</div>
                <input type='number' class='number_input' id='edit_price_variation' value=0>
              </div>
            </div>
            <div class='flex_between'>
              <div class="vert_center">
                <div>Politika otkazivanja</div>
                <select class='basic_select policy_select' id='edit_price_policy'>
                </select>
              </div>
              <div class="flex_center">
                <div class="custom_checkbox dynamic" id="edit_price_engine" data-value="0"> <img class="checkbox_value"> </div>
                Koristi za Booking Engine
              </div>
            </div>
            <div class='flex_between'>
              <div class="vert_center">
                <div>Plan restrikcija</div>
                <select class='basic_select restriction_select' id='edit_price_restriction'>
                  <option value='0'> Bez restrikcija </option>
                </select>
              </div>
              <div class="vert_center">
                <div>Obroci</div>
                <select class='basic_select' id='edit_price_board'>
                  <option value='nb'> Samo soba </option>
                  <option value='bb'> Doručak </option>
                  <option value='hb'> Polupansion </option>
                  <option value='fb'> Pun pansion </option>
                  <option value='ai'> All inclusive </option>
                </select>
              </div>
            </div>
            <div class="vert_center">
              <div>Opis</div>
              <textarea class='textarea_input' id='edit_price_description'></textarea>
            </div>
            <div class='flex_center'>
              <button class='cancel_button' id='edit_price_cancel'> PONIŠTI </button>
              <button class='confirm_button' id='edit_price_confirm'> SAČUVAJ </button>
            </div>
          </div>`);
      }
      // Add values
      $("#edit_price_container .basic_select").select2({
          minimumResultsForSearch: Infinity,
          width: "element"
      });
      $("#edit_price_name").val(plan.name);
      $("#edit_price_variation_type").val(plan.variation_type).change();
      $("#edit_price_variation").val(plan.variation);
      set_checkbox("edit_price_engine", plan.booking_engine);
      $("#edit_price_board").val(plan.board).change();
      for(var i=0;i<restriction_plans_list.length;i++){ // Append options
        $("#edit_price_restriction").append(`<option value=${restriction_plans_list[i]} class='restriction_option'> ${restriction_plans_map[restriction_plans_list[i]]["name"]} </option>`);
      }
      $("#edit_price_restriction").val(plan.restriction_plan).change();
      for(var i=0;i<policies_list.length;i++){
        $(".policy_select").append(`<option value=${policies_list[i]} class='policy_option'> ${policies_map[policies_list[i]]["name"]} </option>`);
      }
      $("#edit_price_policy").val(plan.policy).change();
      $("#edit_price_description").val(plan.description);
    }
    $("#new_price_cancel").click(); // Hide new plan form
  });
  $("body").on("click", "#edit_price_cancel", function(){ // Hide form
    $("#edit_price_container").remove();
    $(".pricing_plan.selected").removeClass("selected");
  });
  $("body").on("click", "#edit_price_confirm", function(){ // Update plan
    $("#edit_price_confirm, #edit_price_cancel").addClass("button_loader");
    // Parameters
    let id = $("#edit_price_id").val();
    let name = $("#edit_price_name").val();
    let variation = $("#edit_price_variation").val();
    let variation_type = $("#edit_price_variation_type").val();
    let policy = $("#edit_price_policy").val();
    let booking_engine = $("#edit_price_engine").attr('data-value');
    let board = $("#edit_price_board").val();
    let restriction = $("#edit_price_restriction").val();
    let description = $("#edit_price_description").val();
    if(pricing_plans_map[id].type == "daily"){
      variation = "";
      variation_type = "";
    }
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/pricingPlan',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id,
        name: name,
        variation: variation,
        variation_type: variation_type,
        policy: policy,
        booking_engine: booking_engine,
        board: board,
        restriction_plan: restriction,
        description: description
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Izmenjen plan cena ${name}`, sve.data.id); // Add changelog
        get_pricing_plans(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  // Delete
  $("#pricing_plans_list").on("click", ".delete", function(){ // Show dialog and delete
    let row_id = $(this).closest(".pricing_plan")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    let plan = pricing_plans_map[id];
    if(confirm(`Da li želite da obrišete plan cena ${plan.name}`)){
      $("#pricing_plans_list").html(loader_html()); // Temp loader with JS dialog
      $("#new_price").hide();
      $("#new_price_container").hide();
      $.ajax({
        type: 'POST',
        url: api_link + 'delete/pricingPlan',
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
          add_change(`Obrisan plan cena ${plan.name}`, sve.data.id); // Add changelog
          get_pricing_plans(); // Refresh data
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
      });
    }
  });

  // Restrictions

  // New
  $("#new_restriction").click(function(){ // Clear values and show form

    set_switch("new_restriction_type", 1);
    $("#new_restriction_name").val("");

    $("#new_restriction_container").show();
    $("#new_restriction").hide();

    // Hide open edits
    $("#edit_restriction_container").remove();
    $(".restriction_plan.selected").removeClass("selected");
  });
  $("#new_restriction_cancel").click(function(){ // Hide form
    $("#new_restriction_container").hide();
    $("#new_restriction").show();
  });
  $("body").on("click", "#new_restriction_confirm", function(){  // Insert new
    // Loaders
    $("#new_restriction_confirm, #new_restriction_cancel").addClass("button_loader");
    // Parameters
    let name = $("#new_restriction_name").val();
    let type = $("#new_restriction_type").attr("data-value") == 1 ? "daily" : "compact";
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'insert/restrictionPlan',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        name: name,
        type: type
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Dodat plan restrikcija ${name}`, sve.data.id); // Add changelog
        get_restriction_plans(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });

  // Edit
  $("#restriction_plans_list").on("click", ".edit", function(){ // Appends edit form
    let row_id = $(this).closest(".restriction_plan")[0].id;
    if($("#edit_restriction_container").length && $(`#${row_id}`).hasClass("selected")){ // This plan edit is open
      return;
    }
    else {
      $("#edit_restriction_container").remove();
      $(".restriction_plan.selected").removeClass("selected");
      $(`#${row_id}`).addClass("selected");
      // Plan id
      let id = row_id.split("_");
      id = id[id.length - 1];
      let plan = restriction_plans_map[id];
      $(`#${row_id}`).after(`
        <div id='edit_restriction_container'>
        <input type='hidden' id='edit_restriction_id' value='${id}'>
          <div class='flex_center'>
            <div class="vert_center">
              <div>Naziv plana</div>
              <input type='text' class='text_input' id='edit_restriction_name'>
            </div>
          </div>
          <div class='flex_center'>
            <button class='cancel_button' id='edit_restriction_cancel'> PONIŠTI </button>
            <button class='confirm_button' id='edit_restriction_confirm'> SAČUVAJ </button>
          </div>
        </div>`);
      $("#edit_restriction_name").val(plan.name);
    }
  });
  $("body").on("click", "#edit_restriction_cancel", function(){ // Hide form
    $("#edit_restriction_container").remove();
    $(".restriction_plan.selected").removeClass("selected");
  });
  $("body").on("click", "#edit_restriction_confirm", function(){ // Update plan
    $("#edit_restriction_confirm, #edit_restriction_cancel").addClass("button_loader");
    // Parameters
    let id = $("#edit_restriction_id").val();
    let name = $("#edit_restriction_name").val();
    // Call
    $.ajax({
      type: 'POST',
      url: api_link + 'edit/restrictionPlan',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: id,
        name: name
      },
      success: function(rezultat){
        $(".button_loader").removeClass("button_loader");
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change(`Izmenjen plan restrikcija ${name}`, sve.data.id); // Add changelog
        get_restriction_plans(); // Refresh data
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });

  // Delete
  $("#restriction_plans_list").on("click", ".delete", function(){ // Show dialog and delete
    let row_id = $(this).closest(".restriction_plan")[0].id;
    let id = row_id.split("_");
    id = id[id.length - 1];
    let plan = restriction_plans_map[id];
    if(confirm(`Da li želite da obrišete plan restrikcija ${plan.name}`)){
      $("#restriction_plans_list").html(loader_html()); // Temp loader with JS dialog
      $("#new_restriction").hide();
      $("#new_restriction_container").hide();
      $.ajax({
        type: 'POST',
        url: api_link + 'delete/restrictionPlan',
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
          add_change(`Obrisan plan restrikcija ${plan.name}`, sve.data.id); // Add changelog
          get_restriction_plans(); // Refresh data
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
      });
    }
  });

});

function get_pricing_plans(){

  $.ajax({
    url: api_link + 'data/pricingPlans/',
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
      pricing_plans_list = [];
      pricing_plans_map = {};
      // Save data
      var pricing_plans = sve.pricing_plans;
      for(var i=0;i<pricing_plans.length;i++){
        pricing_plans_list.push(pricing_plans[i].id);
        pricing_plans_map[pricing_plans[i].id] = pricing_plans[i];
      }
      display_pricing_plans();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
function display_pricing_plans(){
  // Selects
  let default_plan = $("#default_price").val(); // Remember default plan
  $(".price_option").remove();
  for(let i=0;i<pricing_plans_list.length;i++){ // Append options
    $(".price_select").append(`<option value=${pricing_plans_list[i]} class='price_option'> ${pricing_plans_map[pricing_plans_list[i]]["name"]} </option>`);
    if(pricing_plans_map[pricing_plans_list[i]]["type"] == "daily")
      $(".daily_price_select").append(`<option value=${pricing_plans_list[i]} class='price_option'> ${pricing_plans_map[pricing_plans_list[i]]["name"]} </option>`);
  }
  if($(`#default_price option[value='${default_plan}']`).length){
    $("#default_price").val(default_plan); // Restore default if not deleted and don't update with server
    disable_calls = true;
  }
  $("#default_price").change(); // This will display correct value and update default price if it is changed
  disable_calls = true;
  $("#calendar_price").val($("#default_price").val()).change(); // Set calendar to default price
  disable_calls = false;
  // List
  $("#edit_price_cancel").click();
  $("#new_price_cancel").click(); // Hide forms
  $("#pricing_plans_list").empty();
  for(let i=0;i<pricing_plans_list.length;i++){
    let price = pricing_plans_map[pricing_plans_list[i]];
    // Data
    let id = price.id;
    let name = price.name;
    let type = price.type == "virtual" ? "Auto" : "Manual";
    var price_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmeni'> </div>`;
    var price_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
    $("#pricing_plans_list").append(`
      <div class="list_row pricing_plan" id='pricing_plans_list_${id}'>
        <div class='price_name'> ${name} </div>
        <div class='price_type'> ${type} </div>
        <div class='price_actions'> ${price_edit} ${price_delete} </div>
      </div>`);
  }
  if(pricing_plans_list.length == 0)
    $("#pricing_plans_list").append(empty_html("Nema planova cena"));
  else
    $("#pricing_plans_list").prepend(`
    <div class="list_names">
      <div class='price_name'> Naziv </div>
      <div class='price_type'> Tip </div>
      <div class='price_actions'> Akcije </div>
    </div>`);
};

function get_restriction_plans(){

  $.ajax({
    url: api_link + 'data/restrictionPlans/',
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
      restriction_plans_list = [];
      restriction_plans_map = {};
      // Save data
      var restriction_plans = sve.restriction_plans;
      for(var i=0;i<restriction_plans.length;i++){
        restriction_plans_list.push(restriction_plans[i].id);
        restriction_plans_map[restriction_plans[i].id] = restriction_plans[i];
      }
      display_restriction_plans();
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
function display_restriction_plans(){
  // Selects
  $(".restriction_option").remove();
  for(var i=0;i<restriction_plans_list.length;i++){ // Append options
    $(".restriction_select").append(`<option value=${restriction_plans_list[i]} class='restriction_option'> ${restriction_plans_map[restriction_plans_list[i]]["name"]} </option>`);
    if(restriction_plans_map[restriction_plans_list[i]]["type"] == "daily")
      $(".daily_restriction_select").append(`<option value=${restriction_plans_list[i]} class='restriction_option'> ${restriction_plans_map[restriction_plans_list[i]]["name"]} </option>`);
  }
  // List
  $("#edit_restriction_cancel").click();
  $("#new_restriction_cancel").click(); // Hide forms
  $("#restriction_plans_list").empty();
  for(let i=0;i<restriction_plans_list.length;i++){
    let restriction = restriction_plans_map[restriction_plans_list[i]];
    // Data
    let id = restriction.id;
    let name = restriction.name;
    let type = restriction.type == "daily" ? "Dnevni" : "Kompaktan";
    var restriction_edit = `<div class='list_action'><img class='list_action_icon edit' title='Izmeni'> </div>`;
    var restriction_delete = `<div class='list_action'><img class='list_action_icon delete' title='Obriši'> </div>`;
    $("#restriction_plans_list").append(`
      <div class="list_row restriction_plan" id='restriction_plans_list_${id}'>
        <div class='restriction_name'> ${name} </div>
        <div class='restriction_type'> ${type} </div>
        <div class='restriction_actions'> ${restriction_edit} ${restriction_delete} </div>
      </div>`);
  }
  if(restriction_plans_list.length == 0)
    $("#restriction_plans_list").append(empty_html("Nema planova restrikcija"));
  else
    $("#restriction_plans_list").prepend(`
    <div class="list_names">
      <div class='restriction_name'> Naziv </div>
      <div class='restriction_type'> Tip </div>
      <div class='restriction_actions'> Akcije </div>
    </div>`);
};
