let stats = {};
let cmp_stats = {};

var get_statistics = function()
{
  if($("#stat_dfrom").val() == "" || $("#stat_dto").val() == "")
    return
  var dfrom = date_to_iso($("#stat_dfrom").datepicker().data('datepicker').selectedDates[0]);
  var dto = date_to_iso($("#stat_dto").datepicker().data('datepicker').selectedDates[0]);
  var filter_by = $("#stat_filter_by").val();
  $("#stat_vertical").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#stat2_vertical").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#stat3_vertical").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#stat_pie").html("<div class='loader'><div class='loader_icon'></div></div>");
  $("#stat_table").html("<div class='loader'><div class='loader_icon'></div></div>");
  $.ajax({
    url: api_link + 'data/statistics',
    method: 'POST',
    data: {
            key: main_key,
            account: account_name,
            lcode: main_lcode,
            dfrom: dfrom,
            dto: dto,
            filter_by: filter_by
          },
    success: function(rezultat){
      var sve = check_json(rezultat);
      if(sve.status !== "ok") {
        add_change_error(sve.status);
        return;
      }
      stats = sve.data;
      var cmp_dfrom = dfrom.split("-");
      cmp_dfrom[0] = cmp_dfrom[0] - 1;
      cmp_dfrom = cmp_dfrom.join("-");
      var cmp_dto = dto.split("-");
      cmp_dto[0] = cmp_dto[0] - 1;
      cmp_dto = cmp_dto.join("-");

      $.ajax({ // Previous year
        url: api_link + 'data/statistics',
        method: 'POST',
        data: {
                key: main_key,
                account: account_name,
                lcode: main_lcode,
                dfrom: cmp_dfrom,
                dto: cmp_dto,
                filter_by: filter_by
              },
        success: function(rezultat){
          var sve = check_json(rezultat);
          if(sve.status !== "ok") {
            add_change_error(sve.status);
            return;
          }
          cmp_stats = sve.data;
          display_statistics();
        },
        error: function(xhr, textStatus, errorThrown){
          window.alert("Doslo je do greske. " + xhr.responseText);
        }
      });
    },
    error: function(xhr, textStatus, errorThrown){
      window.alert("Doslo je do greske. " + xhr.responseText);
    }
  });
}

var display_statistics = function()
{
  $("#stat_bar_select").change();
  $("#stat2_line_select").change();
  $("#stat3_bar_select").change();
  $("#stat_pie_select").change();
  $("#stat_table_select").change();
}

const stat_month_names = [0, 'Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Avg', 'Sep', 'Okt', 'Nov', 'Dec'];
const stat_bookwindow_names = [ '0-1 dan', '2-3 dana', '4-7 dana', '8-14 dana', '15-30 dana', '31-60 dana', '61-90 dana', '90+ dana'];


$(document).ready(function(){
  // Header
  $("#stats_plan_show").click(function(){
    $("#stats_plan").toggleClass("hidden");
  });
  $("#stats_plan_update").click(function(){
    var values = [];
    for(var i=0;i<12;i++)
    {
      values.push(parseFloat($("#stats_plan_"+i).val()));
    }
    $.ajax({
      type: 'POST',
      url: api_link + 'settings/property',
      data: {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        item: "planned_earnings",
        value: JSON.stringify(values),
      },
      success: function(rezultat){
        var sve = check_json(rezultat);
        if(sve.status !== "ok"){
          add_change_error(sve.status);
          return;
        }
        add_change_settings();
        display_statistics();
        $("#stats_plan_show").click();
      },
      error: function(xhr, textStatus, errorThrown){
        window.alert("Doslo je do greske. " + xhr.responseText);
      }
    });
  });
  $('#stat_dfrom').datepicker(
      {
        language: "en",
        dateFormat: "dd-M-yyyy",
        disableNavWhenOutOfRange: true,
        autoClose: true,
        position: "bottom left",
        onShow: function(inst, animationCompleted) {
          if(animationCompleted)
          {
            open_calendar = inst.el.id;
          }
        },
        onHide: function(inst, animationCompleted) {
          if(animationCompleted === false)
          {
            var dto_id = inst.el.id.split("_");
            dto_id[dto_id.length - 1] = "dto";
            dto_id = dto_id.join("_");
            open_calendar = "";
            if(inst.selectedDates.length)
            {
              var minDate = new Date(inst.selectedDates[0]);
              $('#' + dto_id).datepicker().data('datepicker').show();
              var prev = date_to_iso(inst.selectedDates[0]);
              prev = prev.split("-");
              prev[0] = prev[0] - 1;
              prev = prev.join("-");
            }
            else {
              var minDate = "";
            }
            $('#' + dto_id).datepicker().data('datepicker').update({
              minDate: minDate
            });
          }
        }
      }
    );
  $('#stat_dto').datepicker(
      {
        language: "en",
        dateFormat: "dd-M-yyyy",
        disableNavWhenOutOfRange: true,
        autoClose: true,
        position: "bottom right",
        onShow: function(inst, animationCompleted) {
          if(animationCompleted)
          {
            open_calendar = inst.el.id;
          }
        },
        onHide: function(inst, animationCompleted) {
          if(animationCompleted == false)
          {
            var dfrom_id = inst.el.id.split("_");
            dfrom_id[dfrom_id.length - 1] = "dfrom";
            dfrom_id = dfrom_id.join("_");
            open_calendar = "";
            if(inst.selectedDates.length)
            {
              var maxDate = new Date(inst.selectedDates[0]);
              var prev = date_to_iso(inst.selectedDates[0]);
              prev = prev.split("-");
              prev[0] = prev[0] - 1;
              prev = prev.join("-");
            }
            else {
              var maxDate = "";
            }
            $('#' + dfrom_id).datepicker().data('datepicker').update({
              maxDate: maxDate
            });
            get_statistics();
          }
        }
      }
  );
  $("#stat_filter_by").change(function(){
    get_statistics();
  });


  // Stat changes
  $("#stat_bar_select, #stat_line_select, #stat_cmp_select").change(function(){

    var bar_item = $("#stat_bar_select").val();
    var bar_item_name = $(`#stat_bar_select option[value="${bar_item}"]`).text();
    var line_item = $("#stat_line_select").val();
    var line_item_name = $(`#stat_line_select option[value="${line_item}"]`).text();
    var cmp_item = $("#stat_cmp_select").val();
    var bar_data = [];
    var line_data = [];
    var labels = [];
    var bar_cmp_item_name = bar_item_name + " prethodne";
    var line_cmp_item_name = line_item_name + " prethodne";

    for(var i=0;i<stats.months.length;i++)
    {
      var data = stats.months[i];
      bar_data.push(data[bar_item]);
      labels.push(stat_month_names[data.month] + " " + (data.year + "").substring(2));
      line_data.push(data[line_item]);
    }
    var datasets = [];
    datasets.push({ // Main line
      type: 'line',
      fill: false,
      cubicInterpolationMode: 'monotone',
      label: line_item_name,
      data: line_data,
      backgroundColor: "#0c5db3",
      borderColor: "rgba(12, 93, 179, 0.5)",
      yAxisID: 'line-y'
    });
    if(cmp_item == "prev_year")
    {
      var line_cmp_data = [];
      for(var i=0;i<cmp_stats.months.length;i++)
      {
        var data = cmp_stats.months[i];
        line_cmp_data.push(data[line_item]);
      }
      datasets.push({ // Cmp line
        type: 'line',
        fill: false,
        cubicInterpolationMode: 'monotone',
        label: line_cmp_item_name,
        data: line_cmp_data,
        backgroundColor: '#4fb3ff',
        borderColor: 'rgba(79, 179, 255, 0.5)',
        yAxisID: 'line-y'
      });
    }

    datasets.push({ // Main bar
      label: bar_item_name,
      data: bar_data,
      backgroundColor: "#083e79"
    });
    if(cmp_item == "prev_year")
    {
      var bar_cmp_data = [];
      for(var i=0;i<cmp_stats.months.length;i++)
      {
        var data = cmp_stats.months[i];
        bar_cmp_data.push(data[bar_item]);
      }
      datasets.push({ // Cmp bar
        label: bar_cmp_item_name,
        data: bar_cmp_data,
        backgroundColor: '#4294d3'
      });
    }
    else if(cmp_item == "plan")
    {
      bar_cmp_item_name = "Isplanirani prihod";
      var bar_cmp_data = [];
      var plan = properties_map[main_lcode].planned_earnings;
      for(var i=0;i<cmp_stats.months.length;i++)
      {
        var month = cmp_stats.months[i].month;
        bar_cmp_data.push(plan[month-1]);
      }
      datasets.push({ // Cmp bar
        label: bar_cmp_item_name,
        data: bar_cmp_data,
        backgroundColor: '#4294d3'
      });
    }
    // Tooltips
    var tooltips = {
      callbacks: {
        label: function (t, d) {
          if(cmp_item == '-1')
          {
            if(t.datasetIndex == 0)
            {
              if(line_item == "avg_income" || line_item == "max_income")
                return line_item_name + ": " + t.yLabel + ` ${currency}` + getLineGuest(t.index, line_item, false);
              else
                return line_item_name + ": " + t.yLabel + getLineGuest(t.index, line_item, false);
            }
            if(t.datasetIndex == 1)
            {
              if(bar_item == "income")
                return bar_item_name + ": " + t.yLabel + ` ${currency}`;
              else if(bar_item == "occupancy")
                return bar_item_name + ": " + t.yLabel + "%";
              else
                return bar_item_name + ": " + t.yLabel;
            }
          }
          if(cmp_item == "prev_year")
          {
            if(t.datasetIndex == 0)
            {
              if(line_item == "avg_income" || line_item == "max_income")
                return line_item_name + ": " + t.yLabel + ` ${currency}` + getLineGuest(t.index, line_item, false);
              else
                return line_item_name + ": " + t.yLabel + getLineGuest(t.index, line_item, false);
            }
            if(t.datasetIndex == 1)
            {
              if(line_item == "avg_income" || line_item == "max_income")
                return line_cmp_item_name + ": " + t.yLabel + ` ${currency}` + getLineGuest(t.index, line_item, true);
              else
                return line_cmp_item_name + ": " + t.yLabel + getLineGuest(t.index, line_item, true);
            }
            if(t.datasetIndex == 2)
            {
              if(bar_item == "income")
                return bar_item_name + ": " + t.yLabel + ` ${currency}`;
              else if(bar_item == "occupancy")
                return bar_item_name + ": " + t.yLabel + "%";
              else
                return bar_item_name + ": " + t.yLabel;
            }
            if(t.datasetIndex == 3)
            {
              if(bar_item == "income")
                return bar_cmp_item_name + ": " + t.yLabel + ` ${currency}`;
              else if(bar_item == "occupancy")
                return bar_cmp_item_name + ": " + t.yLabel + "%";
              else
                return bar_cmp_item_name + ": " + t.yLabel;
            }
          }
          if(cmp_item == "plan")
          {
            if(t.datasetIndex == 0)
            {
              if(line_item == "avg_income" || line_item == "max_income")
                return line_item_name + ": " + t.yLabel + ` ${currency}` + getLineGuest(t.index, line_item, false);
              else
                return line_item_name + ": " + t.yLabel + getLineGuest(t.index, line_item, false);
            }
            if(t.datasetIndex == 1)
            {
              if(bar_item == "income")
                return bar_item_name + ": " + t.yLabel + ` ${currency}`;
              else if(bar_item == "occupancy")
                return bar_item_name + ": " + t.yLabel + "%";
              else
                return bar_item_name + ": " + t.yLabel;
            }
            if(t.datasetIndex == 2)
              return bar_cmp_item_name + ": " + t.yLabel + ` ${currency}`;
          }
        }
      }
    };




    $("#stat_vertical").html(`<canvas id='stat_vertical_chart'></canvas>`);
    var ctx =$("#stat_vertical_chart");
    var myChart = new Chart(ctx, {
        type: 'bar',
        data:
        {
          labels: labels,
          datasets: datasets
        },
        options:
        {
          responsive: true,
          aspectRatio: 3.4,
          legend:
          {
           display: true,
           labels:
           {
             fontColor: "gray"
           }

          },
          scales:
          {
            yAxes: [
              {
                ticks:
                {
                  fontColor: "gray",
                  suggestedMin: 0
                },
                gridLines:
                {
                  display:false
                }
              },
              {
                id: 'line-y',
                display: false,
                ticks:
                {
                  beginAtZero: true,
                  suggestedMax: 1
                }
              },
              {
                id: 'line-y-cmp',
                display: false,
                ticks:
                {
                  beginAtZero: true,
                  suggestedMax: 1
                }
              }
            ],
            xAxes: [
              {
                ticks:
                {
                  fontColor: "gray",
                },
                gridLines:
                {
                  display:false
                }
             }
           ]
         },
         tooltips: tooltips
       }
    });
  });
  $("#stat2_line_select, #stat2_cmp_select").change(function(){
    var line_item = $("#stat2_line_select").val();
    var line_item_name = $(`#stat2_line_select option[value="${line_item}"]`).text();
    var cmp_item = $("#stat2_cmp_select").val();
    var line_data = [];
    var labels = [];
    var line_cmp_item_name = line_item_name + " prethodne";
    for(var i=0;i<stats.months.length;i++)
    {
      var data = stats.months[i];
      line_data.push(data[line_item]);
      labels.push(stat_month_names[data.month] + " " + (data.year + "").substring(2));
    }
    var datasets = [];
    if(cmp_item == "plan" && line_item != 'income')
    {
      datasets.push({ // Main line
        fill: false,
        label: line_item_name,
        data: line_data,
        backgroundColor: "#0c5db3",
        borderColor: "rgba(12, 93, 179, 0.5)",
        yAxisID: 'line-y-small'
      });
    }
    else {
      datasets.push({ // Main line
        fill: false,
        label: line_item_name,
        data: line_data,
        backgroundColor: "#0c5db3",
        borderColor: "rgba(12, 93, 179, 0.5)",
      });
    }
    if(cmp_item == "prev_year")
    {
      var line_cmp_data = [];
      for(var i=0;i<cmp_stats.months.length;i++)
      {
        var data = cmp_stats.months[i];
        line_cmp_data.push(data[line_item]);
      }
      datasets.push({ // Cmp line
        fill: false,
        label: line_cmp_item_name,
        data: line_cmp_data,
        backgroundColor: '#4fb3ff',
        borderColor: 'rgba(79, 179, 255, 0.5)'
      });
    }
    else if(cmp_item == "plan")
    {
      line_cmp_item_name = "Isplanirani prihod";
      var line_cmp_data = [];
      var plan = properties_map[main_lcode].planned_earnings;
      for(var i=0;i<cmp_stats.months.length;i++)
      {
        var month = cmp_stats.months[i].month;
        line_cmp_data.push(plan[month-1]);
      }
      datasets.push({ // Cmp line
        fill: false,
        label: line_cmp_item_name,
        data: line_cmp_data,
        backgroundColor: '#4fb3ff',
        borderColor: 'rgba(79, 179, 255, 0.5)'
      });
    }

    // Tooltips
    var tooltips = {
      callbacks: {
        label: function (t, d) {
          if(t.datasetIndex == 0)
          {
            if(line_item == "income")
              return line_item_name + ": " + t.yLabel + ` ${currency}`;
            else if(line_item == "occupancy")
              return line_item_name + ": " + t.yLabel + "%";
            else
              return line_item_name + ": " + t.yLabel;
          }
          if(t.datasetIndex == 1)
          {
            if(line_item == "income" || cmp_item == "plan")
              return line_cmp_item_name + ": " + t.yLabel + ` ${currency}`;
            else if(line_item == "occupancy")
              return line_cmp_item_name + ": " + t.yLabel + "%";
            else
              return line_cmp_item_name + ": " + t.yLabel;
          }
        }
      }
    };

    $("#stat2_vertical").html(`<canvas id='stat2_vertical_chart'></canvas>`);
    var ctx = $("#stat2_vertical_chart");
    var myChart = new Chart(ctx, {
        type: 'line',
        data:
        {
          labels: labels,
          datasets: datasets
        },
        options:
        {
          responsive: true,
          aspectRatio: 3.4,
          legend:
          {
           display: true,
           labels:
           {
             fontColor: "gray",
           }

          },
          scales:
          {
            yAxes: [
              {
                ticks:
                {
                  fontColor: "gray",
                  suggestedMin: 0
                },
                gridLines:
                {
                  display:false
                }
              },
              {
                id: 'line-y-small',
                display: false,
                ticks:
                {
                  beginAtZero: true,
                  suggestedMax: 1
                }
              }
            ],
            xAxes: [
              {
                ticks:
                {
                  fontColor: "gray",
                },
                gridLines:
                {
                  display:false
                }

             }
           ]
         },
         tooltips: tooltips
       }
    });
  });
  $("#stat3_bar_select").change(function(){

        var bar_item = $("#stat3_bar_select").val();
        var bar_item_name = $(`#stat3_bar_select option[value="${bar_item}"]`).text();
        var bar_data = [];
        var labels = [];
        for(var i=0;i<stats.bookwindow.length;i++)
        {
          var data = stats.bookwindow[i];
          bar_data.push(data[bar_item]);
          labels.push(stat_bookwindow_names[i]);
        }
        var datasets = [];
        var ticks = {
          fontColor: "gray",
          suggestedMin: 0
        };
        if(bar_item == 'canceled_percentage')
        {
          ticks = {
            fontColor: "gray",
            suggestedMin: 0,
            suggestedMax: 100
          };
        }
        if(bar_item == 'canceled_percentage')
        {
          datasets.push({ // Main bar
            label: bar_item_name,
            data: bar_data,
            backgroundColor: '#F24236'
          });
        }
        else {
          datasets.push({ // Main bar
            label: bar_item_name,
            data: bar_data,
            backgroundColor: "#083e79",
          });
        }



        // Tooltips
        var tooltips = {
          callbacks: {
            label: function (t, d) {
              if(bar_item == "avg_income")
                return bar_item_name + ": " + t.yLabel + ` ${currency}`;
              else if(bar_item == "canceled_percentage")
                return bar_item_name + ": " + t.yLabel + "%";
              else
                return bar_item_name + ": " + t.yLabel;
            }
          }
        };

        $("#stat3_vertical").html(`<canvas id='stat3_vertical_chart'></canvas>`);
        var ctx =$("#stat3_vertical_chart");
        var myChart = new Chart(ctx, {
            type: 'bar',
            data:
            {
              labels: labels,
              datasets: datasets
            },
            options:
            {
              responsive: true,
              aspectRatio: 3.4,
              legend:
              {
               display: true,
               labels:
               {
                 fontColor: "gray",
               }

              },
              scales:
              {
                yAxes: [
                  {
                    ticks: ticks,
                    gridLines:
                    {
                      display:false
                    }
                  }
                ],
                xAxes: [
                  {
                    barPercentage: 0.4,
                    ticks:
                    {
                      fontColor: "gray",
                    },
                    gridLines:
                    {
                      display:false
                    }
                 }
               ]
             },
             tooltips: tooltips
           }
        });
  });
  $("#stat_pie_select").change(function(){
    var pie_item = $(this).val();
    var pie_data = [];
    var pie_labels = [];
    var pie_colors = [];
    var all_colors = ["#4286f4","#0049bf","#13b536","#157c26","#673eef","#2e00c9","#e59900","#ddc133","#ef2f2f","#8e0c0c"];
    for(var i=0;i<stats[pie_item].length;i++)
    {
      var data = stats[pie_item][i];
      pie_data.push(data.value);
      if(pie_item == 'countries_percentage')
      {
        var id = data.id;
        var label = iso_countries[id];
        if(id == "--")
          label = "Ostale";
      }
      else if(pie_item == 'channels_percentage')
      {
        var id = data.id;
        var label = id;
        if(channels_map[id] !== undefined)
          label = channels_map[id].name;
        if(id == "private")
          label = "Direktne rezervacije";
      }
      pie_labels.push(label);
      pie_colors.push(all_colors[i % all_colors.length]);
    }
    $("#stat_pie").html(`<canvas id='stat_pie_chart'></canvas>`);
    var ctx =$("#stat_pie_chart");
    var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: pie_labels,
            datasets: [
              {
                label: pie_item,
                data: pie_data,
                backgroundColor: pie_colors,
                borderColor: pie_colors
              }
            ],
        },
        options: {
          responsive: true,
          legend:
          {
           display: true,
           position: 'right',
           align: 'center',
           labels:
           {
             fontColor: "gray",
           }

          }
        }
    });
  });
  $("#stat_table_select").change(function(){
    $("#stat_table").empty();
    var item = $(this).val();
    if(item == "rooms")
    {
      $("#stat_table").append(`
        <div class='stat_table_row'>
          <div>
            Naziv
          </div>
          <div>
            Broj rezervacija
          </div>
          <div>
            Ukupan prihod
          </div>
          <div>
            Ukupno noćenja
          </div>
          <div>
            Prosečna cena
          </div>
          <div>
            Prosečno noćenja
          </div>
        </div>
        `);
      for(var i=0;i<stats.rooms.length;i++)
      {
        var name = rooms_map[stats.rooms[i].id] == undefined ? stats.rooms[i].id : rooms_map[stats.rooms[i].id].name;
        var shortname = rooms_map[stats.rooms[i].id] == undefined ? stats.rooms[i].id : rooms_map[stats.rooms[i].id].shortname;
        if(stats.rooms[i].id == 'total')
        {
          name = "Ukupno";
          shortname = "Ukupno";
        }
        $("#stat_table").append(`
          <div class='stat_table_row'>
            <div class='stat_room_name'>
              ${name}
            </div>
            <div class='stat_room_shortname'>
              ${shortname}
            </div>
            <div>
              ${stats.rooms[i].count}
            </div>
            <div>
              ${stats.rooms[i].income} ${currency}
            </div>
            <div>
              ${stats.rooms[i].nights}
            </div>
            <div>
              ${stats.rooms[i].avg_income} ${currency}
            </div>
            <div>
              ${stats.rooms[i].avg_nights}
            </div>
          </div>
          `);
      }
    }
    if(item == "channels")
    {
      $("#stat_table").append(`
        <div class='stat_table_row'>
          <div>
            Naziv
          </div>
          <div>
            Broj rezervacija
          </div>
          <div>
            Ukupan prihod
          </div>
          <div>
            Ukupan trošak
          </div>
          <div>
            Ukupan profit
          </div>
          <div>
            % otkazanih
          </div>
        </div>
        `);
      for(var i=0;i<stats.channels.length;i++)
      {
        var name = channels_map[stats.channels[i].id] == undefined ? stats.channels[i].id : channels_map[stats.channels[i].id].name;
        if(stats.channels[i].id == 'total')
          name = "Ukupno";
        if(stats.channels[i].id == 'private')
          name = "Direktne rezervacije";
        var channel_img = "";
        if(channels_map[stats.channels[i].id] != undefined)
        {
          channel_img = `<img src="${channels_map[stats.channels[i].id].logo}">`;
        }
        $("#stat_table").append(`
          <div class='stat_table_row'>
            <div style='justify-content: flex-start'>
              ${channel_img}${name}
            </div>
            <div>
              ${stats.channels[i].count}
            </div>
            <div>
              ${stats.channels[i].income} ${currency}
            </div>
            <div>
              ${stats.channels[i].costs} ${currency}
            </div>
            <div>
              ${stats.channels[i].earnings} ${currency}
            </div>
            <div>
              ${stats.channels[i].canceled}%
            </div>
          </div>
          `);
      }
    }

  });

});


var getLineGuest = function(index, line_item, cmp){
  if(line_item == "avg_income" || line_item == "avg_nights")
    return "";
  if(cmp)
    var item = cmp_stats.months[index];
  else
    var item = stats.months[index];
  if(item[line_item] == 0)
    return "";
  return ` (${item[line_item + "_guest"]})`;
}
