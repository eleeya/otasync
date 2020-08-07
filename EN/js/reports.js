let changelog_page = 1;
let total_changelog_pages = 1;

let changelog_map = {};

function get_changelog(){

    // No need for array formating, selects already have the value in the right format, just convert it to JSON
    // date_to_iso generates the right format, and returns an empty string if no date is selected
    let data = {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        actions: JSON.stringify($("#actions").val()),
        dfrom: date_to_iso($('#dfrom').datepicker().data('datepicker').selectedDates[0]),
        dto: date_to_iso($('#dto').datepicker().data('datepicker').selectedDates[0]),
        data_types: JSON.stringify($("#data_types").val()),
        page: changelog_page
    };
    $.ajax({
        url: api_link + "data/changelog",
        type: 'POST',
        data: data,
        success: (response) => {
            var sve = check_json(response);
            if(sve.status !== "ok"){
                add_change_error(sve.status);
                return;

            }
            //Resseting markup for new query.

            let changelog = sve.changelog;
            total_changelog_pages = sve.total_pages_number;
            let html = "";
            if(changelog_page == 1){ // Setting header and applying styles only if the first page is being shown
              $("#markup").html("");
              html = "<div class='list_header'><div class=''>Date</div><div class=''>Akcija</div><div class=''>Type</div> <div class=''>ID</div> <div class=''>User</div></div>";
            }

            let timePos = null;
            let date = null;
            let hms = null;
            let time = null;

            let dataType = null;
            let akcija = null;
            let oznaka = null;

            console.log(changelog);

            for(item in changelog){
                changelog_map[changelog[item].id] = changelog[item];
                //Splits date-time string value send by server, so it can be displayed separately.
                timePos = changelog[item].created_time.indexOf(" ");
                //Year, month and day string is sent by server with "-", this need to be displayed with dot delimiter. RegEx is used to change "-" with ".". This only applied to date part.
                // iso_to_eur does that
                date = iso_to_eur(changelog[item].created_time.substring(0,timePos));
                //Getting h:m:s part of dateTime, excluding seconds.
                hms = changelog[item].created_time.substring(timePos+1, changelog[item].created_time.length);

                oznaka = changelog[item].name;

                if(changelog[item].action==="edit"){
                    akcija = "Edit";
                }
                else if(changelog[item].action==="insert"){
                    akcija = "Insert";
                }
                else if(changelog[item].action==="delete"){
                    akcija = "Delete";
                }



                if(changelog[item].data_type==="reservation"){
                    dataType = "Reservation";
                }
                else if(changelog[item].data_type==="guest"){
                    dataType = "Guest";
                }
                else if(changelog[item].data_type==="invoice"){
                    dataType = "Invoice";
                }
                else if(changelog[item].data_type==="room"){
                    dataType = "Room";
                }
                else if(changelog[item].data_type==="extra"){
                    dataType = "Extra";
                }
                else if(changelog[item].data_type==="channel"){
                    dataType = "Channel";
                }
                else if(changelog[item].data_type==="price"){
                    dataType = "Price";
                }
                else if(changelog[item].data_type==="restriction"){
                    dataType = "Restriction";
                }
                else if(changelog[item].data_type==="restrictionCompact"){
                    dataType = "Restriction";
                }
                else if(changelog[item].data_type==="avail"){
                    dataType = "Availability";
                }
                else if(changelog[item].data_type==="pricingPlan"){
                    dataType = "Pricing plan";
                }
                else if(changelog[item].data_type==="restrictionPlan"){
                    dataType = "Restriction plan";
                }
                else if(changelog[item].data_type==="promocode"){
                    dataType = "Promo code";
                }
                else if(changelog[item].data_type==="policy"){
                    dataType = "Cancellation policy";
                }
                else if(changelog[item].data_type==="user"){
                    dataType = "User";
                }
                else if(changelog[item].data_type==="reservationGuestStatus"){
                    dataType = "Guest status";
                }
                else if(changelog[item].data_type==="guestStatus"){
                    dataType = "Guest status";
                }
                else if(changelog[item].data_type==="roomStatus"){
                    dataType = "Room status";
                }
                else if(changelog[item].data_type==="tetris"){
                    dataType = "Reservation";
                }
                else {
                  dataType = changelog[item].data_type;
                }

                if(changelog[item].created_by == "")
                  changelog[item].created_by = "Master";

                //Applying all of previously said to each row of data.
                html += `<div class='list_row change_${changelog[item].action}' data-value='` + changelog[item].id + "'><div class='change_date'>"+date+"<div class='hms'>"+hms+"</div></div><div class='change_action'>"+akcija+"</div><div class='change_data_type'>"+dataType+"</div>" + `<div class='change_data_mark'>${oznaka}</div>` +  "<div class='change_created_by'>"+changelog[item].created_by+"</div></div>";
            }

            //If response doesn't contain usefull values i.e. "empty".
            if(changelog.length === 0 && changelog_page == 1){
                html = empty_html("Nema izmena"); // Just using the existing function, to match the style
            }
            //Rendering previously set markup.
            $("#markup").append(html); // Append is used instead of html, since if the results aren't the first page, the previous results shouldn't be deleted, and if it's the first page, #markup got cleared anyway.
            // The function that updates page number is in navigation.js (on scroll)
        },
        error: function(xhr, textStatus, errorThrown){ // Error response returns 3 parameters
          window.alert("An error occured. " + xhr.responseText);
        }

    });

}

function collapse(event, par){

    $("#"+par).toggle(500);
    console.log("collapsed: "+par);

    if(event.target.className.indexOf("fa-angle-down")!=-1){
        event.target.classList.remove("fa-angle-down");
        event.target.classList.add("fa-angle-up");
        return;
    }

    if(event.target.className.indexOf("fa-angle-up")!=-1){
        event.target.classList.remove("fa-angle-up");
        event.target.classList.add("fa-angle-down");
        return;
    }

}

function occupancy(){

    let dfromOccu = date_to_iso($('#dFromOccu').datepicker().data('datepicker').selectedDates[0]);
    console.log(dfromOccu);
    let dtoOccu = date_to_iso($('#dToOccu').datepicker().data('datepicker').selectedDates[0]);
    if(dtoOccu == "")
      dtoOccu = dfromOccu;
    rooms = $("#tip_sobe").val();
    if(rooms.length == 0)
      rooms = rooms_list; // Global array of all rooms
    rooms = rooms.join(",");
    // Sends all rooms if non are selected
    let data = {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        date: dfromOccu,
        cmp: dtoOccu,
        rooms: rooms,
    };

    $.ajax({
        url: api_link + "data/occupancyReport",
        type: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
        },
        data: data,
        dataType: 'JSON',
        success: (response) => {
          console.log(response);
            let res = response.data;
            console.log(res);
            let resLen = Object.keys(res[0]).length;

            let jedinica = "";
            let ukupanKapacitet = "";
            let rezervisanKapacitet = "";
            let raspolozivKapacitet = "";
            let popunjenost = "";
            let prosecnaCena = "";
            let ukupanPrihod = "";
            if(resLen===7){

                jedinica = "<th colspan='1'>Room</th>";
                ukupanKapacitet = "<th colspan='1'>Total capacity</th>";
                rezervisanKapacitet = "<th colspan='1'>Occupied capacity</th>";
                raspolozivKapacitet = "<th colspan='1'>Available capacity</th>";
                popunjenost = "<th colspan='1'>Occupancy</th>";
                prosecnaCena = "<th colspan='1'>Average price</th>";
                ukupanPrihod = "<th colspan='1'>Total income</th>";

            }
            if(resLen===13){

                jedinica = "<th colspan='1'>Room</th>";
                ukupanKapacitet = "<th colspan='2'>Total capacity</th>";
                rezervisanKapacitet = "<th colspan='2'>Occupied capacity</th>";
                raspolozivKapacitet = "<th colspan='2'>Available capacity</th>";
                popunjenost = "<th colspan='2'>Occupancy</th>";
                prosecnaCena = "<th colspan='2'>Average price</th>";
                ukupanPrihod = "<th colspan='2'>Total income</th>";

            }

            let html = "<table class='table table-bordered' id='myTab'><thead><tr><th><input id='c1' type='checkbox' onchange='checkAll(event, `myTab`)'></th>"+jedinica+ukupanKapacitet+rezervisanKapacitet+raspolozivKapacitet+popunjenost+prosecnaCena+ukupanPrihod+"</tr></thead><tbody>";
            let len = res.length;

            let jedinicaVal = "";
            let ukupanKapacitetVal = "";
            let ukupanKapacitet_cmpVal = "";
            let rezervisanKapacitetVal = "";
            let rezervisanKapacitet_cmpVal = "";
            let raspolozivKapacitetVal = "";
            let raspolozivKapacitet_cmpVal = "";
            let popunjenostVal = "";
            let popunjenost_cmpVal = "";
            let prosecnaCenaVal = "";
            let prosecnaCena_cmpVal = "";
            let ukupanPrihodVal = "";
            let ukupanPrihod_cmpVal = "";

            for(let i=0;i<len;i++){

              // res[i].availabillity (and some others) can be 0, so res[i] && res[i].availability can return false, but the number should still be displayed

                if(resLen===13){

                    jedinicaVal = "<td>"+res[i].name+"</td>";//jedinica
                    ukupanKapacitetVal = "<td>"+res[i].availability+"</td>";//ukupan kapacitet
                    ukupanKapacitet_cmpVal = "<td>"+res[i].availability+"</td>";//ukupan kapacitet
                    rezervisanKapacitetVal = "<td>"+res[i].count+"</td>";//rezervisan kapacitet
                    rezervisanKapacitet_cmpVal = "<td>"+res[i].count_cmp+"</td>";//rezervisan kapacitet
                    raspolozivKapacitetVal = "<td>"+res[i].avail+"</td>";//raspoloziv kapacitet
                    raspolozivKapacitet_cmpVal = "<td>"+res[i].avail_cmp+"</td>";//raspoloziv kapacitet
                    popunjenostVal = "<td>"+res[i].occupancy+"</td>";//popunjenost
                    popunjenost_cmpVal = "<td>"+res[i].occupancy_cmp+"</td>";//popunjenost
                    prosecnaCenaVal = "<td>"+res[i].avg_price+"</td>";//prosecna cena
                    prosecnaCena_cmpVal = "<td>"+res[i].avg_price_cmp+"</td>";//prosecna cena
                    ukupanPrihodVal = "<td>"+res[i].total_price+"</td>";//ukupan prihod
                    ukupanPrihod_cmpVal = "<td>"+res[i].total_price_cmp+"</td>";//ukupan prihod

                    html += "<tr><td><input name='tab1' type='checkbox'></td>"+jedinicaVal+ukupanKapacitetVal+ukupanKapacitet_cmpVal+rezervisanKapacitetVal+rezervisanKapacitet_cmpVal+raspolozivKapacitetVal+raspolozivKapacitet_cmpVal+popunjenostVal+popunjenost_cmpVal+prosecnaCenaVal+prosecnaCena_cmpVal+ukupanPrihodVal+ukupanPrihod_cmpVal+"</tr>";

                }

                if(resLen===7){

                    jedinicaVal = "<td>"+res[i].name+"</td>";//jedinica
                    ukupanKapacitetVal = "<td>"+res[i].availability+"</td>";//ukupan kapacitet
                    rezervisanKapacitetVal = "<td>"+res[i].count+"</td>";//rezervisan kapacitet
                    raspolozivKapacitetVal = "<td>"+res[i].avail+"</td>";//raspoloziv kapacitet
                    popunjenostVal = "<td>"+res[i].occupancy+"</td>";//popunjenost
                    prosecnaCenaVal = "<td>"+res[i].avg_price+"</td>";//prosecna cena
                    ukupanPrihodVal = "<td>"+res[i].total_price+"</td>";//ukupan prihod

                    html += "<tr><td><input name='tab1' type='checkbox'></td>"+jedinicaVal+ukupanKapacitetVal+rezervisanKapacitetVal+raspolozivKapacitetVal+popunjenostVal+prosecnaCenaVal+ukupanPrihodVal+"</tr>";

                }

            }
            $("#Occupancy").html(html);
            html = "";

        },
        error: function(xhr, textStatus, errorThrown){ // Error response returns 3 parameters
          window.alert("An error occured. " + xhr.responseText);
        }
    });

}

function daily(){

    //Getting form values.
    let elems = $("#forma2").serializeArray();

    let data = {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        number: 0,
        room: 0,
        room_number: 0,
        guest: 0,
        adults: 0,
        children: 0,
        arrival: 0,
        departure: 0,
        note: 0,
        price_per_night: 0,
        total_price: 0,
        channel: 0
    };

    for(let i=0;i<elems.length;i++){

        if(elems[i].name==="number"){
            data.number = 1;
        }

        if(elems[i].name==="room"){
            data.room = 1;
        }

        if(elems[i].name==="room_number"){
            data.room_number = 1;
        }

        if(elems[i].name==="guest"){
            data.guest = 1;
        }

        if(elems[i].name==="adults"){
            data.adults = 1;
        }

        if(elems[i].name==="children"){
            data.children = 1;
        }

        if(elems[i].name==="arrival"){
            data.arrival = 1;
        }

        if(elems[i].name==="departure"){
            data.departure = 1;
        }

        if(elems[i].name==="note"){
            data.note = 1;
        }

        if(elems[i].name==="price_per_night"){
            data.price_per_night = 1;
        }

        if(elems[i].name==="total_price"){
            data.total_price = 1;
        }

        if(elems[i].name==="channel"){
            data.channel = 1;
        }

    }

    $.ajax({
        url: api_link + "data/dailyReport",
        type: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
        },
        data: data,
        dataType: 'JSON',
        success: (response) => {

            console.log("success");
            console.log("Received data: ");
            console.log(response);

            let res = response.data;
            let len = res.length;

            if(len>0){

                let number = "";
                let room = "";
                let room_number = "";
                let guest = "";
                let adults = "";
                let children = "";
                let arrival = "";
                let departure = "";
                let note = "";
                let price_per_night = "";
                let total_price = "";
                let channel = "" ;

                let objKeys = Object.keys(res[0]);
                for(let i=0;i<objKeys.length;i++){

                    if(objKeys[i]==="number"){
                        number = "<th>#</th>";
                    }

                    if(objKeys[i]==="room"){
                        room = "<th>Room type</th>";
                    }

                    if(objKeys[i]==="room_number"){
                        room_number = "<th>Room number</th>";
                    }

                    if(objKeys[i]==="guest"){
                        guest = "<th>Guest name</th>";
                    }

                    if(objKeys[i]==="men"){
                        adults = "<th>Adults</th>";
                    }

                    if(objKeys[i]==="children"){
                        children = "<th>Children</th>";
                    }

                    if(objKeys[i]==="arrival"){
                        arrival = "<th>Arrival date</th>";
                    }

                    if(objKeys[i]==="departure"){
                        departure = "<th>Departure date</th>";
                    }

                    if(objKeys[i]==="note"){
                        note = "<th>Note</th>";
                    }

                    if(objKeys[i]==="price_per_night"){
                        price_per_night = "<th>Price per night</th>";
                    }

                    if(objKeys[i]==="total_price"){
                        total_price = "<th>Total price</th>";
                    }

                    if(objKeys[i]==="channel"){
                        channel = "<th>Channel</th>" ;
                    }

                }

                let html = "<table class='table table-bordered' id='myTab2'><thead><tr><th><input id='c2' type='checkbox' onchange='checkAll(event, `myTab2`)'></th>"+number+room+room_number+guest+adults+children+arrival+departure+note+price_per_night+total_price+channel+"</tr></thead><tbody>";

                let numberVal = "";
                let roomVal = "";
                let room_numberVal = "";
                let guestVal = "";
                let adultsVal = "";
                let childrenVal = "";
                let arrivalVal = "";
                let departureVal = "";
                let noteVal = "";
                let price_per_nightVal = "";
                let total_priceVal = "";
                let channelVal = "";

                for(let i=0;i<len;i++){
                    //<td> - </td>
                    if(number!=""){
                        numberVal = "<td>"+(res[i].number)+"</td>";
                    }

                    if(room!=""){
                        roomVal = "<td>"+(res[i].room)+"</td>";
                    }

                    if(room_number!=""){
                        room_numberVal = "<td>"+(res[i].room_number)+"</td>";
                    }

                    if(guest!=""){
                        guestVal = "<td>"+(res[i].guest)+"</td>";
                    }

                    if(adults!=""){
                        adultsVal = "<td>"+(res[i].men)+"</td>";
                    }

                    if(children!=""){
                        childrenVal = "<td>"+(res[i].children)+"</td>";
                    }

                    if(arrival!=""){
                        arrivalVal = "<td>"+(res[i].arrival)+"</td>";
                    }

                    if(departure!=""){
                        departureVal = "<td>"+(res[i].departure)+"</td>";
                    }

                    if(note!=""){
                        res[i].note = "";
                        noteVal = "<td>"+(res[i].note)+"</td>";
                    }

                    if(price_per_night!=""){
                        price_per_nightVal = "<td>"+(res[i].price_per_night)+"</td>";
                    }

                    if(total_price!=""){
                        total_priceVal = "<td>"+(res[i].total_price)+"</td>";
                    }

                    if(channel!=""){
                        channelVal = "<td>"+(res[i].channel)+"</td>";
                    }

                    html += "<tr><td><input name='tab2' type='checkbox'></td>"+numberVal+roomVal+room_numberVal+guestVal+adultsVal+childrenVal+arrivalVal+departureVal+noteVal+price_per_nightVal+total_priceVal+channelVal+"</tr>"

                }
                html += "</tbody></table>";
                $("#Daily").html(html);
                html = "";

            }

            if(len==0){

                $("#Daily").html("<h2 class='NoData'>No data.</h2>");

            }

        },
        error: function(xhr, textStatus, errorThrown){ // Error response returns 3 parameters
          window.alert("An error occured. " + xhr.responseText);
        }

    });

}

function housekeeping(){

    let data = {
        key: main_key,
        account: account_name,
        lcode: main_lcode,
        rooms: JSON.stringify($("#Rooms").val()),
        room_status: JSON.stringify($("#room_status").val()),
        reservation_status: JSON.stringify($("#reservation_status").val()),
        guest_status: JSON.stringify($("#guest_status").val()),
    };
    $.ajax({
        url: api_link + "data/housekeepingReport",
        type: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
        },
        data: data,
        dataType: 'JSON',
        success: (response) => {
            console.log(response);

            let res = response.data;

            let rooms = "";
            let roomStatus = "";
            let guestStatus = "";
            let resStatus = "";
            let nextCheckIn = "";
            let nextCheckOut = "";

            rooms = "<th colspan='1'>Room type</th>";
            roomStatus = "<th colspan='1'>Room status</th>";
            resStatus = "<th colspan='1'>Reservation status</th>";
            guestStatus = "<th colspan='1'>Guest status</th>";
            nextCheckIn = "<th colspan='1'>Next arrival</th>";
            nextCheckOut = "<th colspan='1'>Next departure</th>";


            let html = "<table class='table table-bordered' id='myTab3'><thead><tr><th><input id='c3' type='checkbox' onchange='checkAll(event, `myTab3`)'></th>"+rooms+roomStatus+resStatus+guestStatus+nextCheckIn+nextCheckOut+"</tr></thead><tbody>";
            let len = res.length;
            console.log(len);

            let roomsVal = "";
            let roomStatusVal = "";
            let resStatusVal = "";
            let guestStatusVal = "";
            let nextCheckInVal = "";
            let nextCheckOutVal = "";

            for(let i=0;i<len;i++){

                roomsVal = "<td>"+res[i].name+"</td>";


                let temp1 = res[i].next_checkin == ""  ? "" : iso_to_eur(res[i].next_checkin);
                nextCheckInVal = "<td>"+temp1+"</td>";

                let temp2 = res[i].next_checkout == ""  ? "" : iso_to_eur(res[i].next_checkout);
                nextCheckOutVal = "<td>"+temp2+"</td>";

                if(res[i].status==="clean"){
                    res[i].status = "Clean";
                }
                else if(res[i].status==="dirty"){
                    res[i].status = "Dirty";
                }
                else if(res[i].status==="inspected"){
                    res[i].status = "Inspected";
                }

                if(res[i].reservation_status==="arrival"){
                    res[i].reservation_status = "Arrival";
                }
                else if(res[i].reservation_status==="departure"){
                    res[i].reservation_status = "Departure";
                }
                else if(res[i].reservation_status==="stay"){
                    res[i].reservation_status = "Stay";
                }
                else if(res[i].reservation_status==="free"){
                    res[i].reservation_status = "Free";
                }

                if(res[i].guest_status==="waiting_arrival"){
                    res[i].guest_status = "Waiting arrival";
                }
                else if(res[i].guest_status==="arrived"){
                    res[i].guest_status = "Arrived";
                }
                else if(res[i].guest_status==="arrived_and_paid"){
                    res[i].guest_status = "Arrived and paid";
                }
                else if(res[i].guest_status==="left"){
                    res[i].guest_status = "Left";
                }

                roomStatusVal = "<td>"+res[i].status+"</td>";
                resStatusVal = "<td>"+res[i].reservation_status+"</td>";
                guestStatusVal = "<td>"+res[i].guest_status+"</td>";

                html += "<tr><td><input name='tab3' type='checkbox'></td>"+roomsVal+roomStatusVal+resStatusVal+guestStatusVal+nextCheckInVal+nextCheckOutVal+"</tr>";
            }
            $("#HouseKeeping").html(html);
            html = "";

        },
        error: function(xhr, textStatus, errorThrown){ // Error response returns 3 parameters
          window.alert("An error occured. " + xhr.responseText);
        }

    });

}

function clearSUo(par){
    // Calling the display function of each tab on clear
    if(par === "forma1"){

        $("input[name=dFromOccu]").val("");
        $("input[name=dToOccu]").val("");
        $("#tip_sobe").val(null).trigger("change");
        occupancy();

    }
    else if(par === "forma2"){
        // Couldn't figure out how to change the values of select fields
        $("input[name=dDaily]").val("");
        $("#daily").val(null).trigger("change");
        daily();
    }
    else if(par === "forma3"){

        $("#Rooms").val(null).trigger("change");
        $("#room_status").val(null).trigger("change");
        $("#reservation_status").val(null).trigger("change");
        $("#guest_status").val(null).trigger("change");
        housekeeping();
    }

}

function checkAll(event, par){

    let isCheckedAll = event.target.checked;

    let table = document.getElementById(par);

    let row = table.children[1].children;
    let rowLen = table.children[1].children.length;

    if(isCheckedAll === true){

        for(let i=0;i<rowLen;i++){

            row[i].children[0].children[0].checked = true;

        }

    }

    if(isCheckedAll === false){

        for(let i=0;i<rowLen;i++){

            row[i].children[0].children[0].checked = false;

        }

    }

}

let cnt = true;
let checkedNotChecked = {
    checked: [],
    unChecked: []
};
function getChecked(event, par){

    cnt = !cnt;

    let checkbox = null;
    if(par==="myTab"){

        checkbox = "c1";

    }
    else if(par==="myTab2"){

        checkbox = "c2";

    }
    else if(par==="myTab3"){

        checkbox = "c3";

    }

    let table = document.getElementById(par);

    let row = table.children[1].children;
    let rowLen = table.children[1].children.length;

    for(let i=0;i<rowLen;i++){

        if(row[i].children[0].children[0].checked === true){
            checkedNotChecked.checked.push(i);
            //$(row[i]).show(1000, "linear");
        }

        if(row[i].children[0].children[0].checked === false){
            checkedNotChecked.unChecked.push(i);
            //$(row[i]).hide(1000, "linear");
        }

    }

    /*if(checkedNotChecked.unChecked.length === 0){
        document.getElementById(checkbox).checked = true;
        event.target.innerHTML = "Čekirano";
    }
    else{
        document.getElementById(checkbox).checked = false;
        event.target.innerHTML = "Odčekirano";
    }*/

    /*if(cnt === false){
        for(let i=0;i<checkedNotChecked.unChecked.length;i++){
            $(row[checkedNotChecked.unChecked[i]]).hide(1000, "linear");
        }
    }
    if(cnt === true){
        for(let i=0;i<checkedNotChecked.unChecked.length;i++){
            $(row[checkedNotChecked.unChecked[i]]).show(1000, "linear");
        }
    }*/
    cnt = true;

    console.log(checkedNotChecked);

}

function toExcell(par){

    let table = document.getElementById(par);

    let row = table.children[1].children;
    let rowLen = table.children[1].children.length;

    for(let i=0;i<rowLen;i++){

        if(row[i].children[0].children[0].checked === true){
            checkedNotChecked.checked.push(i);
            $(row[i]).attr("data-exclude", "false");
            //$(row[i]).show(1000, "linear");
        }

        if(row[i].children[0].children[0].checked === false){
            checkedNotChecked.unChecked.push(i);
            $(row[i]).attr("data-exclude", "true");
            //$(row[i]).hide(1000, "linear");
        }

    }

    if(checkedNotChecked.checked.length===0){

        for(let i=0;i<rowLen;i++){
            $(row[i]).attr("data-exclude", "false");
        }

    }

    let len = checkedNotChecked.unChecked.length;
    /*for(let i=len;i>=0;i--){
        $("#"+par+" > tbody > tr").eq(checkedNotChecked.unChecked[i]).remove();

    }*/

    checkedNotChecked = {
        checked: [],
        unChecked: []
    };

    TableToExcel.convert(table);
    console.log("Exported to Excell!");

}

$(document).ready(function(){

  // Most of the selects are automaticly initialized, the special ones that need placeholders are moved here from the <script> tag in index.html

  // Select inits
  $('#data_types').select2({
       dropdownAutoWidth: true,
       multiple: true,
       width: '100%',
       placeholder: "Data types",
       allowClear: true
   });

   $('#actions').select2({
       dropdownAutoWidth: true,
       multiple: true,
       width: '100%',
       placeholder: "Actions",
       allowClear: true,
   });



   $('#room_status').select2({
       dropdownAutoWidth: true,
       multiple: true,
       width: 'element',
       placeholder: "Room status",
       allowClear: true,
   });
   $('#reservation_status').select2({
       dropdownAutoWidth: true,
       multiple: true,
       width: 'element',
       placeholder: "Reservation status",
       allowClear: true,
   });
   $('#guest_status').select2({
       dropdownAutoWidth: true,
       multiple: true,
       width: 'element',
       placeholder: "Guest status",
       allowClear: true,
   });


   //Setting select2 field to 100%, since it isn't by default.
   $('.select2-search__field').css('width', '100%');

  // Changelog

  // Triggering calls without button
  $('#dfrom').datepicker().data('datepicker').update(
    {
      position: "bottom left",
      onHide: function(inst, animationCompleted) {
        if(animationCompleted === false)
        {
          changelog_page = 1; // Reset's pages when filter is changed
          get_changelog();
          if(inst.selectedDates.length){
            $('#dto').datepicker().data('datepicker').show();
          }
        }
      }
    }
  );
  $('#dto').datepicker().data('datepicker').update(
    {
      onHide: function(inst, animationCompleted) {
        if(animationCompleted === false)
        {
          changelog_page = 1; // Reset's pages when filter is changed
          get_changelog();
        }
      }
    }
  );


  $("#actions").change(function(){
    changelog_page = 1; // Reset's pages when filter is changed
    get_changelog();
  });
  $("#data_types").change(function(){
    changelog_page = 1; // Reset's pages when filter is changed
    get_changelog();
  });

  // Occupancy

  // Triggering calls without button

  $("#tip_sobe").change(occupancy);

    // let today = todaysDate(); today is already a global variable defined in global.js
    document.getElementById("today").innerHTML = "Daily report for: "+ iso_to_eur(today);


  // Changelog info

  $("#tab_changelog").on("click", ".list_row.change_edit", function(){
    if($(this).next().hasClass("changelog_info")){
      $("#changelog_info").remove();
      return;
    }
    $("#changelog_info").remove();
    let change = $(this).attr("data-value");
    change = changelog_map[change];
    let old_data = JSON.stringify(change.old_data, undefined, 2);
    let new_data = JSON.stringify(change.new_data, undefined, 2);
    // Somewhat formating JSON
    old_data = old_data.replace(/"/g, ``);
    old_data = old_data.replace(/\[/g, ``);
    old_data = old_data.replace(/\]/g, ``);
    old_data = old_data.replace(/{/g, ``);
    old_data = old_data.replace(/\}/g, ``);
    old_data = old_data.replace(/,/g, ``);

    new_data = new_data.replace(/"/g, ``);
    new_data = new_data.replace(/\[/g, ``);
    new_data = new_data.replace(/\]/g, ``);
    new_data = new_data.replace(/{/g, ``);
    new_data = new_data.replace(/\}/g, ``);
    new_data = new_data.replace(/,/g, ``);

    if(change.data_type == "avail" || change.data_type == "price" || change.data_type == "restriction"){

      old_data = old_data.replace(/room_/g, ``);
      old_data = old_data.replace(/avail_/g, ``);
      old_data = old_data.replace(/price_/g, ``);
      old_data = old_data.replace(/restriction_/g, ``);

      new_data = new_data.replace(/room_/g, ``);
      new_data = new_data.replace(/avail_/g, ``);
      new_data = new_data.replace(/price_/g, ``);
      new_data = new_data.replace(/restriction_/g, ``);

      for(let i=0;i<rooms_list.length;i++){
        let room = rooms_map[rooms_list[i]];
        let re = new RegExp(room.id, "g");
        old_data = old_data.replace(re, room.shortname);
        new_data = new_data.replace(re, room.shortname);
      }
    }

    $(this).after(`
      <div id='changelog_info' class='changelog_info'>
        <div>
          <div class='section_title'> Old values </div>
          <pre> ${old_data} </pre>
        </div>
        <div>
          <div class='section_title'> New values </div>
          <pre> ${new_data} </pre>
        </div>
      </div>`);
  });

});
