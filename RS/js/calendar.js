var scroll_clicked = false;
var click_vals =
{
  dfrom: "",
  dto: "",
  field: "",
  room: "",
  room_number: ""
};
var tetris_vals =
{
  dfrom: "",
  dto: "",
  room: "",
  room_number: "",
  delta: 0,
  field: ""
};
var tetris_org =
{
  dfrom: "",
  dto: "",
  room: "",
  room_number: "",
  id: "",
  nights: 0
};

$(document).ready(function(){

  // Filters

  // Type change
  $("#cal_filter_type").change(function(){
    if($(this).val() == "room_types"){
      $(".single_rooms").hide();
      $(".room_types").show();
    }
    else {
      $(".room_types").hide();
      $(".single_rooms").show();
    }
    send_calendar_filters();
  });
  // Adding room types
  $("#cal_filter_rooms").change(function(){
    let val = $(this).val();
    if(val == -1)
      return;
    else if(val == "all"){
      for(let i=0;i<real_rooms_list.length;i++){
        let room_id = real_rooms_list[i];
        let shortname = rooms_map[room_id].shortname;
        if($("#cal_filter_room_" + room_id).length == 0){
          $("#cal_filter_rooms_types_list").append(`
            <div class='cal_filter_room' id='cal_filter_room_${room_id}' data-value='${room_id}'>
              <img class='list_action_icon delete'> ${shortname}
            </div>`);
        }
      }
    }
    else if(val == "none"){
      $("#cal_filter_rooms_types_list").empty();
    }
    else {
      let room_id = val;
      let shortname = rooms_map[room_id].shortname;
      if($("#cal_filter_room_" + room_id).length == 0){
        $("#cal_filter_rooms_types_list").append(`
          <div class='cal_filter_room' id='cal_filter_room_${room_id}' data-value='${room_id}'>
            <img class='list_action_icon delete'> ${shortname}
          </div>`);
      }
    }
    $(this).val("-1").change();
    send_calendar_filters();
  });
  // Adding room numbers
  $("#cal_filter_rooms_single").change(function(){
    let val = $(this).val();
    if(val == -1)
      return;
    else if(val == "all"){
      for(let i=0;i<real_rooms_list.length;i++){
        let room_id = real_rooms_list[i];
        let room = rooms_map[room_id];
        let shortname = room.shortname;
        for(let j=0;j<room.room_numbers.length;j++){
          let room_number = room.room_numbers[j];
          if($(`#cal_filter_room_${room_id}_${j}`).length == 0){
            $("#cal_filter_rooms_single_list").append(`
              <div class='cal_filter_room' id='cal_filter_room_${room_id}_${j}' data-value='${room_id}_${j}'>
                <img class='list_action_icon delete'> ${room_number} (${shortname})
              </div>`);
          }
        }
      }
    }
    else if(val == "none"){
      $("#cal_filter_rooms_single_list").empty();
    }
    else {
      let room_id = val.split("_")[0];
      let room = rooms_map[room_id];
      let shortname = room.shortname;
      let room_number_id = val.split("_")[1];
      let room_number = room.room_numbers[room_number_id];
      if($(`#cal_filter_room_${room_id}_${room_number_id}`).length == 0){
        $("#cal_filter_rooms_single_list").append(`
          <div class='cal_filter_room' id='cal_filter_room_${room_id}_${room_number_id}' data-value='${room_id}_${room_number_id}'>
            <img class='list_action_icon delete'> ${room_number} (${shortname})
          </div>`);
      }
    }
    $(this).val("-1").change();
    send_calendar_filters();
  });
  // Removing room types
  $("#cal_filter_rooms_types_list").on("click", ".delete", function(){
  $(this).closest(".cal_filter_room").remove();
  send_calendar_filters();
});
  // Removing room numbers
  $("#cal_filter_rooms_single_list").on("click", ".delete", function(){
  $(this).closest(".cal_filter_room").remove();
  send_calendar_filters();
});
  // Checkboxes & radio
  $("#cal_filters").on("click", ".custom_checkbox, .custom_radio", function(){
  send_calendar_filters();
});

  // Room status updates
  $("#calendar").on("click", ".room_status_input", function(){
    $(this).toggleClass("active");
  });
  $("#calendar").on("click", ".room_status_clean", function(e){
    e.stopPropagation();
    $(this).closest(".room_status_input").removeClass("active");
    $(this).closest(".room_status_input").attr("data-value", "clean");
    let row_id = $(this).closest(".cal_row_room")[0].id.split("_");
    send_room_status("clean", row_id[2], row_id[3]);
  });
  $("#calendar").on("click", ".room_status_inspected", function(e){
    e.stopPropagation();
    $(this).closest(".room_status_input").removeClass("active");
    $(this).closest(".room_status_input").attr("data-value", "inspected");
    let row_id = $(this).closest(".cal_row_room")[0].id.split("_");
    send_room_status("inspected", row_id[2], row_id[3]);
  });
  $("#calendar").on("click", ".room_status_dirty", function(e){
    e.stopPropagation();
    $(this).closest(".room_status_input").removeClass("active");
    $(this).closest(".room_status_input").attr("data-value", "dirty");
    let row_id = $(this).closest(".cal_row_room")[0].id.split("_");
    send_room_status("dirty", row_id[2], row_id[3]);
  });


  // Scroll
  let click_position = 0;
  $("#calendar").on("mousedown", "#cal_drag_to_scroll", function(e){ // Remembers click position
    click_position = e.pageX;
    scroll_clicked = true;
    start_scroll_position = cal_scroll;
  });
  $(document).on("mousemove", function(e){
    if(scroll_clicked){ // Handles scrolling
      cal_scroll = start_scroll_position - e.pageX + click_position;
      if(cal_scroll < 0){ // Sets scroll limit (min)
        click_position = e.pageX
        start_scroll_position = 0;
        cal_scroll = 0;
      }
      else if(cal_scroll > cal_max_scroll) { // Sets scroll limit (max)
        click_position = e.pageX
        start_scroll_position = cal_max_scroll;
        cal_scroll = cal_max_scroll;
      }
      $(".cal_scroll").css("margin-left", -cal_scroll + "px");


      // Handles reservation text and month names auto scrolling

      // Months
      m_left_array = [];
      m_right_array = [];
      for(var q=1;q<5;q++){
        if($("#cal_month_name_"+q).length){
          m_left_array.push(0);
          m_right_array.push(0);
        }
      }
      for(var q=0;q<cal_month_name_array.length;q++){
        m_left_array[q] = cal_scroll;
        for(var y=0;y<q;y++)
        {
          m_left_array[q] -= cal_month_name_array[y];
        }
        if(m_left_array[q] < 0)
          m_left_array[q] = 0;
        if(m_left_array[q] > (cal_month_name_array[q] - cal_month_label_array[q]))
          m_left_array[q] = cal_month_name_array[q] - cal_month_label_array[q];
        $("#cal_month_name_"+ (q + 1)).css("padding-left", m_left_array[q] + "px");
      }
      var right_scroll_position = cal_max_scroll - cal_scroll;
      for(var q=(cal_month_name_array.length - 1);q>=0;q--)
      {
        m_right_array[q] = right_scroll_position;
        for(var y=(q + 1);y<cal_month_name_array.length;y++)
        {
          m_right_array[q] -= cal_month_name_array[y];
        }
        if(m_right_array[q] < 0)
          m_right_array[q] = 0;
        if(m_right_array[q] > (cal_month_name_array[q] - cal_month_label_array[q]))
          m_right_array[q] = cal_month_name_array[q] - cal_month_label_array[q];
        $("#cal_month_name_"+ (q + 1)).css("padding-right", m_right_array[q] + "px");
      }

      // Reservations
      var cal_pos_left = $("#calendar").position().left + parseFloat($("#calendar").css("margin-left")) + parseFloat($("#cal_property").width()) + 5;
      $(".rezervacija_u_kalendaru").each(function(){ // Adjust all paddings
        var pos_left = $(this).position().left + parseFloat($(this).css("margin-left"));
        if(pos_left > cal_pos_left) // No padding needed
        {
          $(this).css("padding-left", "");
        }
        else {
          var max_padding = $(this).outerWidth() - field_width - 10;
          var new_padding = cal_pos_left - pos_left;
          if(new_padding > max_padding)
            new_padding = max_padding;
          $(this).css("padding-left", new_padding + "px");
        }
      });

    }
  });
  $(document).on("mouseup", function(e){
    if(scroll_clicked){ // Disable scrolling on mouseup
      scroll_clicked = false;
    }
    if(click_vals.dfrom !== "" && cmp_dates(click_vals.dfrom, click_vals.dto) <= 0){ // If a period is selected
      if(click_vals.field == "room"){ // Opens reservation form for rooms, assumes that the selected room is actualy free
        click_to_hide();
        cal_room_number = click_vals.room_number;
        if(click_vals.dfrom == click_vals.dto){
          click_vals.dto = relative_date(click_vals.dfrom, 1); // Both dfrom and dto need to be valid
        }
        disable_calls = true;
        $("#new_reservation").click();
        $("#form_res_dfrom").datepicker().data('datepicker').selectDate(new Date(click_vals.dfrom));
        $("#form_res_dto").datepicker().data('datepicker').selectDate(new Date(click_vals.dto));
        $('#form_res_dto').datepicker().data('datepicker').hide();
        $("#form_res_pid").val($("#calendar_price").val());
        dusable_calls = false;
        // Gets room avail and price
        $.ajax({
          url: api_link + 'data/freeRooms',
          method: 'POST',
          data: {
                  key: main_key,
                  account: account_name,
                  lcode: main_lcode,
                  dfrom: date_to_iso($('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0]),
                  dto: date_to_iso($('#form_res_dto').datepicker().data('datepicker').selectedDates[0])
                },
          success: function(rezultat){
            var sve = check_json(rezultat);
            if(sve.status !== "ok") {
              add_change_error(sve.status);
              return;
            }
            $("#form_res_room .room").remove();
            for(let i=0;i<sve.rooms.length;i++){ // Appends room options
              $("#form_res_room").append(`<option class='room' value='${sve.rooms[i].id}'> ${sve.rooms[i].name} </option>`);
            }
            disable_calls = true;
            $("#form_res_room").val(click_vals.room).change();
            disable_calls = false;
            $("#form_res .secondary").css("opacity", "");
            $("#form_res .secondary").css("pointer-events", "");
            $.ajax({
              url: api_link + 'data/resRoomData',
              method: 'POST',
              data: {
                      key: main_key,
                      account: account_name,
                      lcode: main_lcode,
                      dfrom: date_to_iso($('#form_res_dfrom').datepicker().data('datepicker').selectedDates[0]),
                      dto: date_to_iso($('#form_res_dto').datepicker().data('datepicker').selectedDates[0]),
                      room: click_vals.room,
                      pid: $("#calendar_price").val()
                    },
              success: function(rezultat){
                var sve = check_json(rezultat);
                if(sve.status !== "ok") {
                  add_change_error(sve.status);
                  return;
                }
                $("#form_res_room_number .room").remove();
                for(let i=0;i<sve.room_numbers.length;i++){ // Appends room numbers
                  $("#form_res_room_number").append(`<option class='room' value='${sve.room_numbers[i].id}'> ${sve.room_numbers[i].name} </option>`);
                }
                disable_calls = true;
                $("#form_res_room_number").val(click_vals.room_number).change();
                disable_calls = false;
                $("#form_res .tertiary").css("opacity", "");
                $("#form_res .tertiary").css("pointer-events", "");
                $("#form_res_night_price").val((parseFloat(sve.price)).toFixed(2));
                click_vals = // Reset click data
                {
                  dfrom: "",
                  dto: "",
                  field: "",
                  room: "",
                  room_number: ""
                };
                $("#cal_selected_period").remove();
                $(".selected_field").css("background-color", "");
                $(".selected_field").css("color", "");
                $(".selected_field").css("border-right", "");
                $(".selected_field").removeClass("selected_field")
                res_price_update();
              },
              error: function(xhr, textStatus, errorThrown){
                window.alert("Doslo je do greske. " + xhr.responseText);
              }
            });
            res_price_update();
            $("#form_res").show();

            scroll_lock();

          },
          error: function(xhr, textStatus, errorThrown){
            window.alert("Doslo je do greske. " + xhr.responseText);
          }
        });
      }
      else { // Handling avail/price/min updates
        var field_id = `#${click_vals.field}_${click_vals.room}_${click_vals.dfrom}`;
        $("#cal_modal_dfrom").datepicker().data('datepicker').selectDate(new Date(click_vals.dfrom));
        $("#cal_modal_dto").datepicker().data('datepicker').selectDate(new Date(click_vals.dto));
        $("#cal_modal_room").val(click_vals.room);
        $("#cal_modal_field").val(click_vals.field);
        $(".cal_modal_item").hide();
        // Showing different parts of modal based on clicked field
        if(click_vals.field == "avail") {
          $("#cal_modal_value").val(parseInt($(field_id).text()));
          $("#cal_modal_avail_variation").val(0);
          $("#cal_modal h1").text("Raspoloživost");
          $(".cal_modal_item.avail").show();
        }
        else if(click_vals.field == "price") {
          var pid = $("#calendar_price").val();
          $("#cal_modal_pid").val(pid);
          $("#cal_modal h1").text("Cena");
          $("#cal_modal h2").text(pricing_plans_map[pid].name);
          if(pricing_plans_map[pid].type == "daily") {
            $("#cal_modal_price_variation").val(0);
            $("#cal_modal_value").val(parseInt($(field_id).text()));
            $(".cal_modal_item.price").show();
          }
          else {
            $("#cal_modal_virtual_variation").val(pricing_plans_map[pid].variation_type);
            $("#cal_modal_value").val(pricing_plans_map[pid].variation);
            $(".cal_modal_item.virtual").show();
          }
        }
        else if(click_vals.field == "min") {
          var pid = $("#calendar_restriction").val();
          $("#cal_modal_pid").val(pid);
          $("#cal_modal h1").text("Minimalni boravak");
          $("#cal_modal h2").text(restriction_plans_map[pid].name);
          if(restriction_plans_map[pid].type == "daily") {
            $("#cal_modal_value").val(parseInt($(field_id).text()));
            $(".cal_modal_item.rest").show();
          }
          else {
            $("#cal_modal_value").val(parseInt(restriction_plans_map[pid].rules.min_stay));
            $(".cal_modal_item.compact").show();
          }
        }
        $("#cal_modal_error").text("");
        $("#cal_modal").show();
        $("#click_to_hide").css("background-color", "rgba(0,0,0,0.5)");
        $("#click_to_hide").show();
        click_vals = // Reset values
        {
          dfrom: "",
          dto: "",
          field: "",
          room: "",
          room_number: ""
        };
        $(".selected_field").css("background-color", "");
        $(".selected_field").css("border-right", "");
        $(".selected_field").css("color", "");
        $(".selected_field").removeClass("selected_field");
      }
    }
    else if(click_vals.dfrom !== ""){ // Negative period is selected, just reset values
      click_vals =
      {
        dfrom: "",
        dto: "",
        field: "",
        room: "",
        room_number: ""
      };
      $("#cal_selected_period").remove();
      $(".selected_field").css("background-color", "");
      $(".selected_field").css("color", "");
      $(".selected_field").css("border-right", "");
      $(".selected_field").removeClass("selected_field");
    }
    else if(tetris_vals.dfrom !== "")
    {
      $("#cal_dialog_changes").empty();
      $(".cal_main_hovered").removeClass("cal_main_hovered");
      var res_id = tetris_org.id.split("_")[1];
      var changed = false;
      tetrisAjaxData = {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        id: res_id
      };
      if(open_info !== "") { // open_info gets reset on timer, this opens the info if the timer hasn't triggered yet
        var res = all_reservations[open_info];
        open_res_info(res);
        open_info = "";
      }
      else { // Tetris action happeninig, appends different parts to modal depending on changed data
        if(tetris_org.dfrom !== tetris_vals.dfrom || tetris_org.dto !== tetris_vals.dto){
          changed = true;
          $("#cal_dialog_changes").append("<div> Period: </div>");
          $("#cal_dialog_changes").append(`<div class='cal_dialog_change'> ${iso_to_eur(tetris_org.dfrom).substring(0,5)} - ${iso_to_eur(tetris_org.dto).substring(0,5)} > <span> ${iso_to_eur(tetris_vals.dfrom).substring(0,5)} - ${iso_to_eur(tetris_vals.dto).substring(0,5)} </span> </div>`);
          tetrisAjaxData.date_arrival = tetris_vals.dfrom;
          tetrisAjaxData.date_departure = tetris_vals.dto;
          tetrisAjaxData.nights = num_of_nights(tetris_vals.dfrom, tetris_vals.dto);
        }
        if(tetris_org.room !== tetris_vals.room || tetris_org.room_number !== tetris_vals.room_number){
          changed = true;
          var old_number = rooms_map[tetris_org.room].room_numbers;
          old_number = old_number[tetris_org.room_number];
          var new_number = rooms_map[tetris_vals.room].room_numbers;
          new_number = new_number[tetris_vals.room_number];
          $("#cal_dialog_changes").append("<div> Jedinica: </div>")
          $("#cal_dialog_changes").append(`<div class='cal_dialog_change'> ${rooms_map[tetris_org.room].shortname} - ${old_number} > <span> ${rooms_map[tetris_vals.room].shortname} - ${new_number} </span> </div>`);
          var res_id = tetris_org.id.split("_")[1];
          if(all_reservations[res_id].rooms.length > 1){
            var res_number = tetris_org.id.split("_")[2];
            var old_rooms = all_reservations[res_id].rooms;
            var old_numbers = all_reservations[res_id].room_numbers;
            old_rooms[res_number] = tetris_vals.room;
            old_numbers[res_number] = tetris_vals.room_number;
            tetrisAjaxData.rooms = old_rooms.join(",");
            tetrisAjaxData.room_numbers = old_numbers.join(",");
          }
          else {
            tetrisAjaxData.rooms = tetris_vals.room;
            tetrisAjaxData.room_numbers = tetris_vals.room_number;
          }
        }
      }
      if(changed){ // If tetris was changed
        $("#cal_dialog_container").show();
      }
      else { // Reset data
        $("#cal_dialog_container").hide();
        $("#tetris_ghost_res").remove();
        $(".rezervacija_u_kalendaru").css("pointer-events", "");
        $("#" + tetris_org.id).css("opacity", "");
        var res_width = tetris_org.nights*field_width - 10;
        var res_margin = (field_width / 2 + 5) + "px";
        if($("#cal_filter_margin").attr("data-value") == 1)
          res_margin = "5px";
        $("#" + tetris_org.id).css("margin-left", res_margin);
        $("#" + tetris_org.id).css("width", res_width);
        tetris_vals =
        {
          dfrom: "",
          dto: "",
          room: "",
          room_number: "",
          delta: 0,
          field: ""
        };
        tetris_org =
        {
          dfrom: "",
          dto: "",
          room: "",
          room_number: "",
          id: "",
          nights: 0
        };
      }
    }
  });
  $("#calendar").on("click", ".rezervacija_u_kalendaru", function(){ // Opening info for non tetris reservations
    let id = $(this)[0].id.split("_")[1];
    open_res_info(all_reservations[id]);
    open_info = "";
  });

  // Tetris

  $("#cal_dialog_cancel").click(function(){
    $("#cal_dialog_container").hide();
    $("#tetris_ghost_res").remove();
    $(".rezervacija_u_kalendaru").css("pointer-events", "");
    $("#" + tetris_org.id).css("opacity", "");
    var res_width = tetris_org.nights*field_width - 10;
    var res_margin = (field_width / 2 + 5) + "px";
    if($("#cal_filter_margin").attr("data-value") == 1)
      res_margin = "5px";
    $("#" + tetris_org.id).css("margin-left", res_margin);
    $("#" + tetris_org.id).css("width", res_width);
    let id = tetris_org.id.split("_")[1];
    if(all_reservations[id].real_rooms.length > 1)
      get_calendar();
    tetris_vals =
    {
      dfrom: "",
      dto: "",
      room: "",
      room_number: "",
      delta: 0,
      field: ""
    };
    tetris_org =
    {
      dfrom: "",
      dto: "",
      room: "",
      room_number: "",
      id: "",
      nights: 0
    };
    tetrisAjaxData = {};
  });
  $("#cal_dialog_confirm").click(function(){

    $("#cal_dialog_confirm").addClass("button_loader");
    console.log(tetrisAjaxData);
    $.ajax({
      url: api_link + 'edit/tetris',
      method: 'POST',
      data: tetrisAjaxData,
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok") {
          add_change_error(sve.status);
          return;
        }
        add_change(`Izmenjena rezervacija ${tetrisAjaxData.id}`, sve.data.id); // Add changelog
        $(".button_loader").removeClass("button_loader");
        $("#cal_dialog_container").hide();
        tetris_vals =
        {
          dfrom: "",
          dto: "",
          room: "",
          room_number: "",
          delta: 0,
          field: ""
        };
        tetris_org =
        {
          dfrom: "",
          dto: "",
          room: "",
          room_number: "",
          id: "",
          nights: 0
        };
        get_calendar();
      },
      error: function(xhr, textStatus, errorThrown){
        $(".button_loader").removeClass("button_loader");
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });

  $("#calendar").on("mousedown", ".avail_field, .price_field, .min_field, .room_field", function(e){
    if(e.target.id == "" || $(this).hasClass("block_start"))
      return;

    var field_split = e.target.id.split("_");
    if(cmp_dates(field_split[2], today) < 0)
      return;
    if(field_split[1] == "google")
      return;
    click_vals.dfrom = field_split[2];
    click_vals.dto = field_split[2];
    click_vals.field = field_split[0];
    click_vals.room = field_split[1];
    if(click_vals.field == "room")
      click_vals.room_number = field_split[3];
    else
      click_vals.room_number = "";
  });
  $("#calendar").on("mouseover", ".avail_field, .price_field, .min_field, .room_field", function(e){
    if(e.target.id == "")
    {
      return;
    }
    // Hovers

    var field_date = e.target.id.split("_")[2];
    $(this).closest(".cal_scroll").addClass("cal_hovered");
    $(".cal_column_"+field_date).addClass("cal_hovered");
    if($(this).hasClass("block_start") == false)
      $(this).addClass("cal_main_hovered");

    // Drags
    if(click_vals.dfrom !== "")
    {
      click_vals.dto = field_date;
      var clicked_count = num_of_nights(click_vals.dfrom, click_vals.dto);
      var list_of_dates = range_of_dates(click_vals.dfrom, clicked_count + 1);
      $("#cal_selected_period").remove();
      $(".selected_field").css("background-color", "");
      $(".selected_field").css("color", "");
      $(".selected_field").css("border-right", "");
      $(".selected_field").removeClass("selected_field");
      if(clicked_count < 0)
        return;
      for(var i=0;i<list_of_dates.length;i++)
      {
        if(click_vals.field == "room")
          var field_id = `${click_vals.field}_${click_vals.room}_${list_of_dates[i]}_${click_vals.room_number}`;
        else
          var field_id = `${click_vals.field}_${click_vals.room}_${list_of_dates[i]}`;
        if(i !== 0 && $("#" + field_id).hasClass("block_end")) // zaustavljanje ako postoji druga rez
          break;
        $("#" + field_id).css("background-color", "#306bad"); // Check color
        $("#" + field_id).css("color", "white"); // Check color
        $("#" + field_id).addClass("selected_field");
        if(i !== list_of_dates.length - 1) // Border
          $("#" + field_id).css("border-right", "0px");
      }
      if(clicked_count > 0 && click_vals.field == "room")
      {
        $(`#${click_vals.field}_${click_vals.room}_${click_vals.dfrom}_${click_vals.room_number}`).append(
          `
          <div id='cal_selected_period'>
          ${(iso_to_eur(click_vals.dfrom)).substring(0,5)} - ${(iso_to_eur(click_vals.dto)).substring(0,5)} <br>
          ${clicked_count} noći
          </div>
          `
        );
      }
    }

    // Tetris
    if(tetris_vals.field == "right")
    {
      var cur_field = tetris_vals.dto;
      if(cmp_dates(tetris_org.dto, field_date) < 0)
      {
        var clicked_count = num_of_nights(tetris_org.dto, field_date);
        var list_of_dates = range_of_dates(tetris_org.dto, clicked_count + 1);
        for(var i=1;i<list_of_dates.length;i++)
        {
          var field_id = `#room_${tetris_org.room}_${list_of_dates[i]}_${tetris_org.room_number}`;
          if($(field_id).hasClass("block_end") == false)
            tetris_vals.dto = list_of_dates[i];
          else
            break;
        }
      }
      else if(cmp_dates(field_date, tetris_org.dfrom) == 1)
        tetris_vals.dto = field_date;
      if(tetris_vals.dto !== cur_field)
      {
        var res_width = num_of_nights(tetris_vals.dfrom, tetris_vals.dto)*field_width - 10;
        $("#" + tetris_org.id).css("width", res_width + "px");
        var res_id = tetris_org.id.split("_")[1];
        if(all_reservations[res_id].rooms.length > 1)
        {
          var cnt = all_reservations[res_id].rooms.length;
          for(var q=0;q<cnt;q++)
          {
            $(`#rez_${res_id}_${q}`).css("width", res_width + "px");
          }
        }
      }
    }
    if(tetris_vals.field == "left")
    {
      var cur_field = tetris_vals.dfrom;
      if(cmp_dates(field_date, tetris_org.dfrom) < 0)
      {
        var clicked_count = num_of_nights(field_date, tetris_org.dfrom);
        var list_of_dates = range_of_dates(field_date, clicked_count);
        for(var i=list_of_dates.length-1;i>=0;i--)
        {
          var field_id = `#room_${tetris_org.room}_${list_of_dates[i]}_${tetris_org.room_number}`;
          if($(field_id).hasClass("block_start") == false)
            tetris_vals.dfrom = list_of_dates[i];
          else
            break;
        }
      }
      else if(cmp_dates(field_date, tetris_org.dto) < 0)
        tetris_vals.dfrom = field_date;
      if(tetris_vals.dfrom !== cur_field)
      {
        var res_margin = (field_width / 2 + 5);
        if($("#cal_filter_margin").attr("data-value") == 1)
          res_margin = 5;
        res_margin = res_margin + num_of_nights(tetris_org.dfrom, tetris_vals.dfrom)*field_width;
        var res_width = num_of_nights(tetris_vals.dfrom, tetris_vals.dto)*field_width - 10;
        $("#" + tetris_org.id).css("width", res_width + "px");
        $("#" + tetris_org.id).css("margin-left", res_margin + "px");
        var res_id = tetris_org.id.split("_")[1];
        if(all_reservations[res_id].rooms.length > 1)
        {
          var cnt = all_reservations[res_id].rooms.length;
          for(var q=0;q<cnt;q++)
          {
            $(`#rez_${res_id}_${q}`).css("width", res_width + "px");
            $(`#rez_${res_id}_${q}`).css("margin-left", res_margin + "px");
          }
        }
      }
    }
    if(tetris_vals.field == "center")
    {
      var field_split = e.target.id.split("_");
      if(field_split[0] != "room")
        return;
      var dfrom = relative_date(field_split[2], -tetris_vals.delta);
      var dto = relative_date(dfrom, tetris_org.nights);
      var res_id = tetris_org.id.split("_")[1];
      if(all_reservations[res_id].rooms.length > 1)
      {
        dfrom = tetris_org.dfrom;
        dto = tetris_org.dto;
      }
      var room = field_split[1];
      var room_number = field_split[3];
      var list_of_dates = range_of_dates(dfrom, tetris_org.nights + 1);
      var should_change = true;
      $(".cal_main_hovered").removeClass("cal_main_hovered");
      for(var i=0;i<tetris_org.nights;i++)
      {
        var start_id = `#room_${room}_${list_of_dates[i]}_${room_number}`;
        var end_id = `#room_${room}_${list_of_dates[i+1]}_${room_number}`;
        if($(start_id).hasClass("block_start"))
        {
          if(room != tetris_org.room || room_number != tetris_org.room_number || cmp_dates(list_of_dates[i], tetris_org.dfrom) < 0)
            should_change = false;
        }
        if($(end_id).hasClass("block_end"))
        {
          if(room != tetris_org.room || room_number != tetris_org.room_number || cmp_dates(tetris_org.dto, list_of_dates[i + 1]) < 0)
            should_change = false;
        }
        $(start_id).addClass("cal_main_hovered");
      }
      if(should_change)
      {
        tetris_vals.dfrom = dfrom;
        tetris_vals.dto = dto;
        tetris_vals.room = room;
        tetris_vals.room_number = room_number;
        var field_id = `#room_${room}_${dfrom}_${room_number}`;
        var res_width = tetris_org.nights*field_width - 10;
        var res_margin = (field_width / 2 + 5) + "px";
        if($("#cal_filter_margin").attr("data-value") == 1)
          res_margin = "5px";
        var res_name = $("#" + tetris_org.id).text();
        $("#tetris_ghost_res").remove();
        $(field_id).append(
          `
          <div style='width:${res_width}px;margin-left:${res_margin};' id='tetris_ghost_res'>
           ${res_name}
          </div>
          `
        );
        if(tetris_vals.dfrom == tetris_org.dfrom && tetris_vals.dto == tetris_org.dto && tetris_vals.room == tetris_org.room && tetris_vals.room_number == tetris_org.room_number)
          $("#" + tetris_org.id).css("opacity", "");
        else
          $("#" + tetris_org.id).css("opacity", "0.3");
      }
    }
  });
  $("#calendar").on("mouseout", ".avail_field, .price_field, .min_field, .room_field", function(e){
    // Hovers
    var field_id = e.target.id.split("_");
    var field_date = field_id[2];
    $(this).closest(".cal_scroll").removeClass("cal_hovered");
    $(".cal_column_"+field_date).removeClass("cal_hovered");
    $(this).removeClass("cal_main_hovered");
  });
  $("#calendar").on("mousedown", ".tetris_right, .tetris_left, .tetris_center", function(e){
    e.stopPropagation();
    var res = $(this).closest(".rezervacija_u_kalendaru")[0].id.split("_")[1];
    res = all_reservations[res];
    var field_split = $(this).closest(".room_field")[0].id.split("_");
    open_info = res.reservation_code;
    if($(this).hasClass("disabled")){
      if(true)
      {
        // OPENING RES INFO
        var res = all_reservations[open_info];

        open_res_info(res);

        open_info = "";
      }
      return;
    }
    setTimeout(function(){
      open_info = "";
    }, 200);
    tetris_org.dfrom = res.date_arrival;
    tetris_org.dto = res.date_departure;
    tetris_org.nights = num_of_nights(tetris_org.dfrom, tetris_org.dto);
    tetris_org.room = field_split[1];
    tetris_org.room_number = field_split[3];
    tetris_org.id = $(this).closest(".rezervacija_u_kalendaru")[0].id;
    $(".rezervacija_u_kalendaru").css("pointer-events", "none");

    tetris_vals.dfrom = res.date_arrival;
    tetris_vals.dto = res.date_departure;
    tetris_vals.room = field_split[1];
    tetris_vals.room_number = field_split[3];
    if($(this).hasClass("tetris_right"))
      tetris_vals.field = "right";
    else if($(this).hasClass("tetris_left"))
      tetris_vals.field = "left";
    else if($(this).hasClass("tetris_center"))
    {
      tetris_vals.field = "center";
      var origin = $("#" + tetris_org.id).position().left + $("#calendar").position().left - parseFloat($("#calendar").css("margin-left"));
      var delta = e.pageX - origin + parseFloat($("#" + tetris_org.id).css("margin-left"));
      tetris_vals.delta = Math.floor(delta/field_width);
    }
  });

  // Res forms

  $("#cal_new_reservation").click(function(){
    $("#new_reservation").click();
  });
  $("#cal_new_group_reservation").click(function(){
    $("#new_group_reservation").click();
  });

  $("#cal_modal_update").click(function(){
    details_call = "cal";
    var field = $("#cal_modal_field").val();
    var dfrom = date_to_iso($("#cal_modal_dfrom").datepicker().data('datepicker').selectedDates[0]);
    var dto = date_to_iso($("#cal_modal_dto").datepicker().data('datepicker').selectedDates[0]);
    var room = $("#cal_modal_room").val();
    var rooms = [room];
    var value = $("#cal_modal_value").val();
    var values = {};
    if(field == 'avail')
    {
      values[room] = value;
      var variation_type = $("#cal_modal_avail_variation").val();
      send_avail(dfrom, dto, values, rooms, variation_type)
    }
    else if(field == 'price')
    {
      var pid = $("#cal_modal_pid").val();
      if(pricing_plans_map[pid].type == "daily")
      {
        values[room] = value;
        var variation_type = $("#cal_modal_price_variation").val();
        send_price(dfrom, dto, pid, values, rooms, variation_type);
      }
      else
      {
        var variation = $("#cal_modal_virtual_variation").val();
        send_price_virtual(value, variation, pid);
      }
    }
    else if(field == 'min')
    {
      var pid = $("#cal_modal_pid").val();
      if(restriction_plans_map[pid].type == "compact")
      {
        send_restriction_compact(value, field, pid);
      }
      else
      {
        values[room] = {};
        values[room]["min_stay"] = value;
        send_restriction(dfrom, dto, pid, values, rooms);
      }
    }
  });

  // Calendar updates

  $("#calendar_price, #calendar_restriction").change(function(){
    get_calendar();
  });
  $('#calendar_date').datepicker().data('datepicker').update(
    {
      position: "bottom right",
      todayButton: new Date(),
      onHide: function(inst, animationCompleted) {
        if(animationCompleted === false)
        {
          open_calendar = "";
        }
        else {
          if($("#calendar_date").val() !== "")
            get_calendar();
          else
            $("#calendar_date").datepicker().data('datepicker').selectDate(new Date());
        }
      }
    }
  );
  $("#calendar_prev").click(function(){
    var cal_start = $("#calendar_date").datepicker().data('datepicker').selectedDates[0];
    cal_start = relative_date(cal_start, - parseInt($("#cal_filter_days").val()));
    $("#calendar_date").datepicker().data('datepicker').selectDate(new Date(cal_start));
    get_calendar();
  });
  $("#calendar_next").click(function(){
    var cal_start = $("#calendar_date").datepicker().data('datepicker').selectedDates[0];
    cal_start = relative_date(cal_start, parseInt($("#cal_filter_days").val()));
    $("#calendar_date").datepicker().data('datepicker').selectDate(new Date(cal_start));
    get_calendar();
  });

  // Overbooking
  $("#calendar").on("click", ".cal_error", function(){
    var id = $(this)[0].id.split("_");
    id = id[id.length - 1];
    if($(".cal_row_room_error_" + id).css("display") == "flex")
        $(".cal_row_room_error_" + id).css("display", "");
    else
      $(".cal_row_room_error_" + id).css("display", "flex");
  });

  // Shift Scroll
  $("#calendar").on('mousewheel DOMMouseScroll', function(event) {


    if(event.shiftKey){
        $(".cal_scroll, .cal_month_name").css("transition-duration", "0.2s");
        cal_scroll = cal_scroll + event.originalEvent.deltaY;
        if(cal_scroll < 0){
          cal_scroll = 0;
        }
        else if(cal_scroll > cal_max_scroll) {
          cal_scroll = cal_max_scroll;
        }
        $(".cal_scroll").css("margin-left", -cal_scroll + "px");

        // Handles reservation text and month names auto scrolling

        // Months
        m_left_array = [];
        m_right_array = [];
        for(var q=1;q<5;q++){
          if($("#cal_month_name_"+q).length){
            m_left_array.push(0);
            m_right_array.push(0);
          }
        }
        for(var q=0;q<cal_month_name_array.length;q++){
          m_left_array[q] = cal_scroll;
          for(var y=0;y<q;y++)
          {
            m_left_array[q] -= cal_month_name_array[y];
          }
          if(m_left_array[q] < 0)
            m_left_array[q] = 0;
          if(m_left_array[q] > (cal_month_name_array[q] - cal_month_label_array[q]))
            m_left_array[q] = cal_month_name_array[q] - cal_month_label_array[q];
          $("#cal_month_name_"+ (q + 1)).css("padding-left", m_left_array[q] + "px");
        }
        var right_scroll_position = cal_max_scroll - cal_scroll;
        for(var q=(cal_month_name_array.length - 1);q>=0;q--)
        {
          m_right_array[q] = right_scroll_position;
          for(var y=(q + 1);y<cal_month_name_array.length;y++)
          {
            m_right_array[q] -= cal_month_name_array[y];
          }
          if(m_right_array[q] < 0)
            m_right_array[q] = 0;
          if(m_right_array[q] > (cal_month_name_array[q] - cal_month_label_array[q]))
            m_right_array[q] = cal_month_name_array[q] - cal_month_label_array[q];
          $("#cal_month_name_"+ (q + 1)).css("padding-right", m_right_array[q] + "px");
        }

        // Reservations
        function fixPaddings(){
          var cal_pos_left = $("#calendar").position().left + parseFloat($("#calendar").css("margin-left")) + parseFloat($("#cal_property").width()) + 5;
          $(".rezervacija_u_kalendaru").each(function(){ // Adjust all paddings
            var pos_left = $(this).position().left + parseFloat($(this).css("margin-left"));
            if(pos_left > cal_pos_left) // No padding needed
            {
              $(this).css("padding-left", "");
            }
            else {
              var max_padding = $(this).outerWidth() - field_width - 10;
              var new_padding = cal_pos_left - pos_left;
              if(new_padding > max_padding)
                new_padding = max_padding;
              $(this).css("padding-left", new_padding + "px");
            }
          });
        }
        setTimeout(fixPaddings, 40);
        setTimeout(fixPaddings, 80);
        setTimeout(fixPaddings, 120);
        setTimeout(fixPaddings, 160);
        setTimeout(fixPaddings, 200);

        setTimeout(function(){$(".cal_scroll, .cal_month_name").css("transition-duration", "0s");}, 200)

    }
  });

  // Fullscreen

  $("#calendar").on("click", "#calendar_enter_fullscreen", function(){
    // Set initial width to look cleaner;
    $("#calendar").css("width", "100vw");
    $("#calendar").addClass("fullscreen");
    get_calendar();
  });
  $("#calendar").on("click", "#calendar_exit_fullscreen", function(){
    $("#calendar").removeClass("fullscreen");
    calendar_width = $("body").width() - 40;
    $("#calendar").css("width", calendar_width + "px");
    get_calendar();
  });
});

// Send filters
function send_calendar_filters(){
  if(disable_calls)
    return;
  let data = {};
  data.type = $("#cal_filter_type").val();
  data.avail = $("#cal_filter_avail").attr("data-value");
  data.price = $("#cal_filter_price").attr("data-value");
  data.min = $("#cal_filter_min").attr("data-value");
  data.margin = $("#cal_filter_margin").attr("data-value");
  data.room_name = $("#cal_filter_room_name").attr("data-value");
  data.room_type = $("#cal_filter_room_type").attr("data-value");
  data.room_status = $("#cal_filter_room_status").attr("data-value");
  let room_types = [];
  $("#cal_filter_rooms_types_list .cal_filter_room").each(function(){
    room_types.push($(this).attr("data-value"));
  });
  let single_rooms = [];
  $("#cal_filter_rooms_single_list .cal_filter_room").each(function(){
    single_rooms.push($(this).attr("data-value"));
  });
  data.room_types = room_types;
  data.single_rooms = single_rooms;
  data.days = $("#cal_filter_days").val();
  $.ajax({
    type: 'POST',
    url: api_link + 'settings/property',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      item: "custom_calendar",
      value: JSON.stringify(data),
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      properties_map[main_lcode].custom_calendar = data;
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

function send_room_status(status, room, room_number){
  $.ajax({
    type: 'POST',
    url: api_link + 'edit/roomStatus',
    data: {
      key: main_key,
      account: account_name,
      lcode: main_lcode,
      id: room,
      status: status,
      room_number: room_number,
    },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok"){
        add_change_error(sve.status);
        return;
      }
      add_change("Izmena sačuvana", sve.data.id);
      rooms_map[room].status[room_number] = status;
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
// Calendar

// Get data
let cal_dfrom = "";
let cal_dto = "";
function get_calendar() {
  let filters = properties_map[main_lcode].custom_calendar;
  $("#calendar").empty();
  $("#calendar").append(loader_html());
  let cal_start = $("#calendar_date").datepicker().data('datepicker').selectedDates[0];
  cal_dfrom = relative_date(date_to_iso(cal_start), - filters.days);
  cal_dto = relative_date(date_to_iso(cal_start), 2 * filters.days - 1);
  $.ajax({
    url: api_link + 'data/calendar',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: cal_dfrom,
            dto: cal_dto,
            price_id: $("#calendar_price").val(),
            restriction_id: $("#calendar_restriction").val()
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      display_calendar();
      display_calendar_details(sve);
      display_calendar_reservations(sve);
    },
    error: function(rezultat){
      console.log("greska");
    }
  });
};

// Displaying empty calendar
let field_width = 0;
let cal_label_width = 150;
let cal_scroll = 0;
let cal_max_scroll = 0;
const month_names = [0,"Januar","Februar","Mart","April","Maj","Jun","Jul","Avgust","Septembar","Oktobar","Novembar","Decembar"];
const day_names = ["NE","PO","UT","SR","ČE","PE","SU"];
function display_calendar(){
  // Filters
  let filters = properties_map[main_lcode].custom_calendar;

  //Fullscreen
  let calendar_width = 0;
  if($("#calendar").hasClass("fullscreen")){
    calendar_width = $(document).width();
    field_width = (calendar_width - cal_label_width) / filters.days;
    field_width = Math.floor(field_width);
  }
  else {
    calendar_width = $("body").width() - 40;
    field_width = (calendar_width - cal_label_width) / filters.days;
    field_width = Math.floor(field_width);
    calendar_width = filters.days * field_width + cal_label_width;
  }
  $("#calendar").css("width", calendar_width + "px");

  // Field and calendar width


  // Scroll values
  cal_scroll = field_width * filters.days;
  cal_max_scroll = field_width * filters.days * 2;

  // All dates
  let dates_list = range_of_dates(cal_dfrom, filters.days * 3);

  // Reset
  $("#calendar").empty();

  // Header
  let property_name = properties_map[main_lcode].name;
  $("#calendar").append(`
    <img class='calendar_fullscreen_button' id='calendar_enter_fullscreen' src='img/expand.png'>
    <img class='calendar_fullscreen_button' id='calendar_exit_fullscreen' src='img/collapse.png'>
    <div id='cal_header'>
      <div class='cal_label' id='cal_property'>
        ${property_name}
      </div>
      <div id='cal_drag_to_scroll'>
        <div id='cal_month_names' class='cal_scroll'>
        </div>
        <div id='cal_month_dates' class='cal_scroll'>
        </div>
      </div>
    </div>
    <div id='cal_body'>
    </div>`);

  // Months and days
  let month_headers = [];
  let cur_month = dates_list[0].split("-")[1];
  let month_count = 0;
  month_headers.push([cur_month, 0]);
  // Getting width of each month and appending days
  for(var i=0;i<dates_list.length;i++){ // Counts number of days in each month and total number of months
    let date_split = dates_list[i].split("-");
    if(cur_month != date_split[1]){
      cur_month = date_split[1];
      month_count += 1;
      month_headers.push([cur_month, 0]);
    }
    month_headers[month_count][1] += 1;
    var cur_date = parseInt(date_split[2]);
    var dateobj = new Date(dates_list[i]);
    var daycount = dateobj.getDay();
    var daytext = day_names[daycount];
    $("#cal_month_dates").append(`
      <div style='width:${field_width}px' class='date_field cal_column_${dates_list[i]}' id='date_${dates_list[i]}'>
        <div style='font-size:16px;'>${cur_date}</div>
        <div style='font-size:12px;'>${daytext}</div>
      </div>`);
  }
  // Appending months
  for(var i=0;i<month_headers.length;i++){
    var month_name = parseInt(month_headers[i][0]);
    var month_width = month_headers[i][1];
    month_width *= field_width;
    if(month_width > 100)
      month_name = month_names[month_name];
    else
      month_name = "";
    $("#cal_month_names").append(`
      <div class='cal_month_name' id='cal_month_name_${i + 1}' style='width:${month_width}px;'>
        <div class='cal_month_name_label'>${month_name}</div>
      </div>`);
  }

  // Getting list of individual rooms to be displayed
  let cal_rooms_list = [];
  if(filters.type == "single_rooms") {
    cal_rooms_list = filters.single_rooms;
  }
  else {
    for(let i=0;i<filters.room_types.length;i++){ // Add all room numbers of selected rooms to list
      let room_id = filters.room_types[i];
      let room_availability =  rooms_map[room_id].availability;
      for(let j=0;j<room_availability;j++){
        cal_rooms_list.push(`${room_id}_${j}`);
      }
    }
  }

  // Displaying room rows
  for(var i=0;i<cal_rooms_list.length;i++)
  {
    let row_id = cal_rooms_list[i];
    let room_id = row_id.split("_")[0];
    let room_number_id = row_id.split("_")[1];
    let room = rooms_map[room_id];

    // Getting room name
    let room_name = "";
    if(filters.room_name == 1 && filters.room_type == 1){
      room_name = `
      <div class='cal_label_half'>
        ${room.room_numbers[room_number_id]}
      </div>
      <div class='cal_label_half2'>
        ${room.shortname}
      </div>`;
    }
    else if (filters.room_name == 1) {
      room_name = `<div class='cal_label_full'> ${room.room_numbers[room_number_id]} </div>`;
    }
    else if (filters.room_type == 1) {
      room_name = `<div class='cal_label_full'> ${room.shortname} </div>`;
    }
    let room_status_class = "";
    if(filters.room_status == 1){
      room_status_class = "room_status";
      room_name += `
      <div class='room_status_input' id='room_status_${row_id}' data-value='${room.status[room_number_id]}'>
        <div class='room_status_clean'>
          Čisto
        </div>
        <div class='room_status_inspected'>
          Pregledano
        </div>
        <div class='room_status_dirty'>
          Prljavo
        </div>
      </div>`;
    }

    // Appending label
    $("#cal_body").append(`
      <div class='cal_row_room' id='cal_row_${row_id}'>
        <div class='cal_label ${room_status_class}'  title='${room.name}'>
           ${room_name}
        </div>
        <div class='cal_scroll' id='cal_room_fields_${row_id}'>
        </div>
      </div>`);

    // Apending date fields
    for(let j=0;j<dates_list.length;j++){
      $(`#cal_room_fields_${row_id}`).append(`
        <div style='width:${field_width}px' class='cal_column_${dates_list[j]} room_field' id='room_${room_id}_${dates_list[j]}_${room_number_id}'>
        </div>`);
    }
  }

  // Adding avail/price/min rows
  if(filters.type == "room_types"){
    for(let i=0;i<filters.room_types.length;i++){
      let room = rooms_map[filters.room_types[i]];

      // Appending data before first row of the room

      // Avail
      if(filters.avail == 1){
        // Label
        $(`#cal_row_${room.id}_0`).before(`
          <div class='cal_row_min'>
            <div class='cal_label'>
              Raspoloživost
            </div>
            <div class='cal_scroll' id='cal_avail_fields_${room.id}'>
            </div>
          </div>`);
        // Date fields
        for(let j=0;j<dates_list.length;j++){
          $(`#cal_avail_fields_${room.id}`).append(`
            <div style='width:${field_width}px' class='cal_column_${dates_list[j]} avail_field' id='avail_${room.id}_${dates_list[j]}'>
            </div>`);
        }
      }
      // Price
      if(filters.price == 1){
        // Label
        $(`#cal_row_${room.id}_0`).before(`
          <div class='cal_row_min'>
            <div class='cal_label'>
              Cena
            </div>
            <div class='cal_scroll' id='cal_price_fields_${room.id}'>
            </div>
          </div>`);
        // Date fields
        for(let j=0;j<dates_list.length;j++){
          $(`#cal_price_fields_${room.id}`).append(`
            <div style='width:${field_width}px' class='cal_column_${dates_list[j]} price_field' id='price_${room.id}_${dates_list[j]}'>
            </div>`);
        }
      }
      // Min stay
      if(filters.min == 1){
        // Label
        $(`#cal_row_${room.id}_0`).before(`
          <div class='cal_row_min'>
            <div class='cal_label'>
              Minimalni boravak
            </div>
            <div class='cal_scroll' id='cal_min_fields_${room.id}'>
            </div>
          </div>`);
        // Date fields
        for(let j=0;j<dates_list.length;j++){
          $(`#cal_min_fields_${room.id}`).append(`
            <div style='width:${field_width}px' class='cal_column_${dates_list[j]} min_field' id='min_${room.id}_${dates_list[j]}'>
            </div>`);
        }
      }


      // Setting border of last room row
      $(`#cal_row_${room.id}_${room.availability - 1}`).css("border-bottom", "5px solid #bbb");
    }
  }

  // Missing virtual room prices


  // Scroll position
  $(".cal_scroll").css("margin-left", -cal_scroll + "px");

  // Today colors
  $("#date_" + today).addClass("today");
  $(".cal_column_"+ today).addClass("today");

  // Autoscroll of month names and reservation text, needs refactoring

  cal_header_width = $("#calendar").width() - $("#cal_property").width();
  cal_month_name_array = [];
  cal_month_label_array = [];
  m_left_array = [];
  m_right_array = [];
  for(var q=1;q<5;q++)
  {
    if($("#cal_month_name_"+q).length)
    {
      cal_month_name_array.push($("#cal_month_name_"+q).width());
      cal_month_label_array.push($("#cal_month_name_"+q+" .cal_month_name_label").width() + 10);
      m_left_array.push(0);
      m_right_array.push(0);
    }
  }
  for(var q=0;q<cal_month_name_array.length;q++)
  {
    m_left_array[q] = cal_scroll;
    for(var y=0;y<q;y++)
    {
      m_left_array[q] -= cal_month_name_array[y];
    }
    if(m_left_array[q] < 0)
      m_left_array[q] = 0;
    if(m_left_array[q] > (cal_month_name_array[q] - cal_month_label_array[q]))
      m_left_array[q] = cal_month_name_array[q] - cal_month_label_array[q];
    $("#cal_month_name_"+ (q + 1)).css("padding-left", m_left_array[q] + "px");
  }
  var right_scroll_position = cal_max_scroll - cal_scroll;
  for(var q=(cal_month_name_array.length - 1);q>=0;q--)
  {
    m_right_array[q] = right_scroll_position;
    for(var y=(q + 1);y<cal_month_name_array.length;y++)
    {
      m_right_array[q] -= cal_month_name_array[y];
    }
    if(m_right_array[q] < 0)
      m_right_array[q] = 0;
    if(m_right_array[q] > (cal_month_name_array[q] - cal_month_label_array[q]))
      m_right_array[q] = cal_month_name_array[q] - cal_month_label_array[q];
    $("#cal_month_name_"+ (q + 1)).css("padding-right", m_right_array[q] + "px");
  }
  var cal_pos_left = $("#calendar").position().left + parseFloat($("#calendar").css("margin-left")) + parseFloat($("#cal_property").width()) + 5;
  $(".rezervacija_u_kalendaru").each(function(){ // Adjust all paddings
    var pos_left = $(this).position().left + parseFloat($(this).css("margin-left"));
    if(pos_left > cal_pos_left) // No padding needed
    {
      $(this).css("padding-left", "");
    }
    else {
      var max_padding = $(this).outerWidth() - field_width - 10;
      var new_padding = cal_pos_left - pos_left;
      if(new_padding > max_padding)
        new_padding = max_padding;
      $(this).css("padding-left", new_padding + "px");
    }
  });
}


// Apending avail/price/min

function display_calendar_details(data){
  let filters = properties_map[main_lcode].custom_calendar;
  if(filters.type == "room_types"){ // Details are only displayed if sorted by room types
    let dates_list = range_of_dates(cal_dfrom, filters.days * 3);
    let rooms = filters.room_types;
    let avail = data.avail;
    let prices = data.prices;
    let restrictions = data.restrictions;

    console.log(data);

    for(let i=0;i<rooms.length;i++){
      for(let j=0;j<dates_list.length;j++){

        // Avail
        let field_id = "#avail_" + rooms[i] + "_" + dates_list[j];
        console.log(field_id);
        let value = avail[rooms[i]][dates_list[j]];
        if(value == undefined)
          value = "-";
        $(field_id).text(value);
        if(value == 0){
          $(field_id).addClass("empty_field");
        }
        else {
          $(field_id).removeClass("empty_field");
        }

        // Price

        field_id = "#price_" + rooms[i] + "_" + dates_list[j];
        value = prices[rooms[i]][dates_list[j]];
        if(value == undefined)
          value = "-";
        $(field_id).text(value);

        // Min

        field_id = "#min_" + rooms[i] + "_" + dates_list[j];
        value = restrictions[rooms[i]][dates_list[j]];
        if(value == undefined)
          value = "-";
        else
          value = value.min_stay;
        $(field_id).text(value);

      }
    }
  }
}

function display_calendar_reservations(data){
  let filters = properties_map[main_lcode].custom_calendar;
  let reservations = data.reservations;
  var res_margin = (field_width / 2 + 5) + "px";
  if(filters.margin == 1)
    res_margin = "5px";

  for(var i=0;i<reservations.length;i++){

    // Reservation data
    var reservation = reservations[i];
    if(reservation.reservation_code == "1593961694")
      console.log(reservation);
    all_reservations[reservation.reservation_code] = reservation;
    var res_id = reservation.reservation_code;
    var res_nights = reservation.nights;
    var res_channel = reservation.id_woodoo;
    if(channels_map[res_channel] !== undefined)
      res_channel = channels_map[res_channel].name;
    else
      res_channel = "Direktna rezervacija";
    // Name and Info
    var channel_img = "";
    var res_name = `${reservation.customer_name} ${reservation.customer_surname}`;
    if(channels_map[reservation.id_woodoo] !== undefined)
    {
      if(channels_map[reservation.id_woodoo] !== undefined)
        channel_img =`<img title='${res_channel}' src='${channels_map[reservation.id_woodoo].logo}'>`;
    }
    else{
      channel_img = `<img title='${res_channel}' src='img/ota/youbook.png'>`;
    }
    var res_name_info = `
      <div class='ruk_info'> ${channel_img} <div class='ruk_text'> ${reservation.customer_name} ${reservation.customer_surname} </div> </div>
      <div class='ruk_info'> ${res_nights} <img src='img/cal_nights.png'> <div class='ruk_text'> ${reservation.total_price} ${currency} </div> </div>
    `;

    var res_arrival = reservation.date_arrival;
    var res_departure = reservation.date_departure;

    var res_width = res_nights*field_width - 10;
    // Long reservations
    var long_res = false;
    if(cmp_dates(res_arrival, cal_dfrom) < 0){
      res_arrival = cal_dfrom;
      res_nights = num_of_nights(cal_dfrom, res_departure);
      long_res = true;
      res_width = res_nights*field_width - 10 + parseFloat(res_margin);
    }
    var res_rooms = reservation.real_rooms;
    var res_room_numbers = reservation.room_numbers;
    let guest_status = reservation.guest_status;
    let res_color = "";
    if(guest_status == "waiting_arrival")
      res_color = "#f0535a";
    if(guest_status == "arrived")
      res_color = "#c6a43d";
    if(guest_status == "arrived_and_paid")
      res_color = "#0DC85E";
    if(guest_status == "left")
      res_color = "#282828";
    for(var j=0; j<res_rooms.length; j++){

      // Single room data
      var res_room = res_rooms[j];
      var res_row = res_room_numbers[j];


      // Overbooking
      if(rooms_map[res_room] != undefined && parseInt(res_row) >= parseInt(rooms_map[res_room].availability)){ // First part is for deleted rooms and bugs
        if(cmp_dates(reservation.date_departure, today) >= 0){ // Only for current dates
          if($(`#cal_row_${res_room}_${rooms_map[res_room].availability - 1} .cal_error`).length == 0){ // Displaying notification
            $(`#cal_row_${res_room}_${rooms_map[res_room].availability - 1} .cal_label`).append(`
              <div class='cal_error' id='cal_error_${res_room}'>
                <div class='cal_error_tooltip'>
                  U ovoj jedinici postoji više rezervacija od ukupne raspoloživosti, kliknite za prikaz tih rezervacija.
                </div>
              </div>`);
          }
          if($(`#cal_row_${res_room}_${res_row}`).length == 0){ // Create new row
            var room_name = rooms_map[res_room].shortname;
            var room_id = rooms_map[res_room].id;

            // Appending label
            $(`#cal_row_${res_room}_${rooms_map[res_room].availability - 1}`).after(`
              <div class='cal_row_room cal_row_room_error cal_row_room_error_${res_room}' id='cal_row_${room_id}_${res_row}'>
                <div class='cal_label'  title='${rooms_map[res_room].name}'>
                  ${room_name}
                </div>
                <div class='cal_scroll' style='margin-left:${-cal_scroll}px;' id='cal_room_fields_${room_id}_${res_row}'>
                </div>
              </div>`);

            // Appending fields
            let dates_list = range_of_dates(cal_dfrom, properties_map[main_lcode].custom_calendar.days * 3);
            for(var q=0;q<dates_list.length;q++){
              $(`#cal_room_fields_${room_id}_${res_row}`).append(`
                <div style='width:${field_width}px' class='cal_column_${dates_list[q]} room_field' id='room_${room_id}_${dates_list[q]}_${res_row}'>
                </div>`);
            }
          }
        }
      }

      // Reservation
      var res_field_id = `#room_${res_room}_${res_arrival}_${res_row}`;
      if(long_res){
        $(res_field_id).append(`
          <div style='width:${res_width}px;background-color:${res_color};margin-left:0px;border-top-left-radius:0px;border-bottom-left-radius:0px' title='${res_name}' class='rezervacija_u_kalendaru rez_${res_id}' id='rez_${res_id}_${j}'>
           ${res_name_info}
          </div>`);
      }
      else {
        $(res_field_id).append(`
          <div style='width:${res_width}px;background-color:${res_color};margin-left:${res_margin};' title='${res_name}' class='rezervacija_u_kalendaru rez_${res_id}' id='rez_${res_id}_${j}'>
           ${res_name_info}
             <div class='tetris_left'>
             </div>
             <div class='tetris_center'>
             </div>
             <div class='tetris_right'>
             </div>
          </div>`);
      }


      // Blocks
      var res_blocks = range_of_dates(res_arrival, res_nights + 1);
      for(var q=0;q<res_nights;q++){
        var res_block_id = `#room_${res_room}_${res_blocks[q]}_${res_row}`;
        $(res_block_id).addClass("block_start");
        var res_block_end_id = `#room_${res_room}_${res_blocks[q+1]}_${res_row}`;
        $(res_block_end_id).addClass("block_end");
      }
    }
  }
}


var send_avail = function(dfrom, dto, values, rooms, variation_type)
{
  $(".wubook_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/avail',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            values: JSON.stringify(values),
            rooms: JSON.stringify(rooms),
            variation_type: variation_type
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmenjena raspoživost", sve.data.id);
      click_to_hide()
      get_calendar();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
};

var send_price = function(dfrom, dto, pid, values, rooms, variation_type)
{
  $(".wubook_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/price',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            pid: pid,
            values: JSON.stringify(values),
            rooms: JSON.stringify(rooms),
            variation_type: variation_type
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmenjene cene", sve.data.id);
      click_to_hide()
      get_calendar();

    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
};
var send_restriction = function(dfrom, dto, pid, values, rooms)
{
  $(".wubook_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/restriction',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            pid: pid,
            values: JSON.stringify(values),
            rooms: JSON.stringify(rooms)
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmenjene restrikcije", sve.data.id);
      click_to_hide()
      get_calendar();

    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });

}
var send_restriction_compact = function(value, field, pid)
{
  var rules = restriction_plans_map[pid].rules;
  var values =
  {
    closed: rules.closed,
    closed_arrival: rules.closed_arrival,
    closed_departure: rules.closed_departure,
    max_stay: rules.max_stay,
    min_stay: rules.min_stay,
    min_stay_arrival: rules.min_stay_arrival
  };
  if(field == 'min')
    values.min_stay = value;
  if(field == 'max')
    values.max_stay = value;
  if(field == 'closure')
    values.closed = value;
  $(".wubook_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/restrictionCompact',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            pid: pid,
            values: JSON.stringify(values)
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmenjena restrikcije", sve.data.id);
      restriction_plans_map[pid].rules = sve.data.new_data.values;
      click_to_hide()
      get_calendar();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
var send_price_virtual = function(value, variation, pid)
{
  $(".wubook_update").addClass("button_loader");
  $.ajax({
    url: api_link + 'edit/priceVirtual',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            pid: pid,
            variation: value,
            variation_type: variation
          },
    success: function(rezultat){
      $(".button_loader").removeClass("button_loader");
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      add_change("Izmenjene cene", sve.data.id);
      click_to_hide()
      get_calendar();
    },
    error: function(xhr, textStatus, errorThrown){
      $(".button_loader").removeClass("button_loader");
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}
