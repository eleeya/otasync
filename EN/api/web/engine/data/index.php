<?php
    require '../../../main.php';
    //NISU RADJENE PROVERE DOZVOLA I OSTALO

    if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
        http_response_code(200);

    }
    else if ($_SERVER['REQUEST_METHOD'] == "POST") {
        $konekcija = connectToDB();
        $action = getAction();
        $lcode = checkPost("lcode");
        $result = [];

        if($action == "header"){
            $sql = "SELECT * FROM engine_header WHERE lcode = '$lcode'";
            $header = mysqli_query($konekcija, $sql);

            if($header){
                $result = mysqli_fetch_assoc($header);
            }
            else{
                http_response_code(500);
            }
        }
        if($action == "contact"){

            $sql = "SELECT * FROM engine_contact WHERE lcode = '$lcode'";
            $contact = mysqli_query($konekcija, $sql);

            if($contact){
                $result = mysqli_fetch_assoc($contact);
            }
            else{
                http_response_code(500);
            }
        }
        if($action == "appearance"){

            $sql = "SELECT * FROM engine_styles WHERE lcode = '$lcode'";
            $appearance = mysqli_query($konekcija, $sql);

            if($appearance){
                $result = mysqli_fetch_assoc($appearance);
                if($result["logo"] == "")
                  $result["logo"] = "https://admin.otasync.me/img/blank.png";
                if($result["backgroundImg"] == "")
                  $result["backgroundImg"] = "https://admin.otasync.me/img/blank.png";
            }
            else{
                http_response_code(500);
            }

        }
        if($action == "confirmation"){

            $sql = "SELECT * FROM engine_confirmation WHERE lcode = '$lcode'";
            $confirmation = mysqli_query($konekcija, $sql);

            if($confirmation){
                $result = mysqli_fetch_assoc($confirmation);
            }
            else{
                http_response_code(500);
            }

        }
        if($action == "footer"){

            $sql = "SELECT * FROM engine_footer WHERE lcode = '$lcode'";
            $footer = mysqli_query($konekcija, $sql);

            if($footer){
                $result = mysqli_fetch_assoc($footer);
            }
            else{
                http_response_code(500);
            }

        }
        if($action == "selectdates"){

            $sql = "SELECT * FROM engine_selectdates WHERE lcode = '$lcode';";
            $selectDates = mysqli_query($konekcija, $sql);

            if($selectDates){
                $result = mysqli_fetch_assoc($selectDates);
            }
            else{
                http_response_code(500);
            }

        }
        if($action == "messages"){

            $sql = "SELECT * FROM engine_messages WHERE lcode = '$lcode'";
            $messages = mysqli_query($konekcija, $sql);

            if($messages){
                $result = mysqli_fetch_assoc($messages);
            }
            else{
                http_response_code(500);
            }

        }
        if($action == "promocode"){

            $sql = "SELECT * FROM promocodes_$lcode;";
            $promocode = mysqli_query($konekcija, $sql);

            if($promocode){
                $result = array();
                while($r = mysqli_fetch_assoc($promocode)) {
                    $result[] = $r;
                }
            }
            else{
                http_response_code(500);
            }

        }
        if($action == "extras"){
          $dfrom = checkPost("dfrom");
          $dto = checkPost("dto");
          $rooms = checkPost("rooms");
          if(!(is_array($rooms)))
            $rooms = json_decode($rooms);

          $extras = [];
          $sql = "SELECT name, description, type, price, pricing, daily, rooms, image, tax FROM extras_$lcode";
          $rezultat = mysqli_query($konekcija, $sql);
          while($red = mysqli_fetch_assoc($rezultat)){
            $included_rooms = json_decode($red["rooms"]);
            $red["included"] = 0;
            for($i=0;$i<sizeof($rooms);$i++){
              if(in_array($rooms[$i], $included_rooms))
                $red["included"] += 1;
            }
            $red["price"] = ($red["price"] + ($red["price"] * $red["tax"] / 100)) * $red["pricing"];
            unset($red["pricing"]);
            unset($red["rooms"]);
            unset($red["tax"]);
            if($red["image"] == "")
              $red["image"] = "https://admin.otasync.me/img/blank.png";
            array_push($extras, $red);
          }
          $result = $extras;
        }
        if($action == "lcode"){
            $lcode = checkPost("lcode");
            $sql = "SELECT COUNT(*) AS broj FROM all_properties WHERE lcode = '$lcode'";
            $rezultat = mysqli_query($konekcija, $sql);
            $red = mysqli_fetch_assoc($rezultat);
            $broj = $red["broj"];
            $result = $broj == 0 ? 0 : 1;
        }
        if($action == "reviews"){
            $asoc_reviews = array (
              'reviews' =>
              array (
                0 =>
                array (
                  'author' => 'John Smith',
                  'date' => '25.01.2012',
                  'rating' => 10,
                  'review' => 'Comfortable beds, bathroom always incredibly clean with lots of hot water and strong pressure. Strong Wifi signal. Staff is very friendly and kitchen has the basics to cook a good meal.',
                )
              )
            );
            $result = $asoc_reviews;
        }
        if($action == "search")
        {
          $dfrom = checkPost("dfrom");
          $dto = checkPost("dto");
          $guests = checkPost("guests");

          // Rooms
          $rooms = [];
          $sql = "SELECT * FROM rooms_$lcode WHERE booking_engine = 1 AND occupancy >= $guests";
          $rezultat = mysqli_query($konekcija, $sql);
          while($red = mysqli_fetch_assoc($rezultat)){
            array_push($rooms, $red);
          }

          // Pricing plans
          $prices = [];
          $sql = "SELECT * FROM prices_$lcode WHERE booking_engine = 1";
          $rezultat = mysqli_query($konekcija, $sql);
          while($red = mysqli_fetch_assoc($rezultat)){
            array_push($prices, $red);
          }

          // Additional data
          for($i=0;$i<sizeof($rooms);$i++){
            $room_id = $rooms[$i]["id"];
            // Prices
            $room_prices = [];
            for($j=0;$j<sizeof($prices);$j++){
              $plan = $prices[$j];
              $plan_id = $plan["id"];
              if($plan["vpid"] != "") // Get price from parent if plan is virtual
                $plan_id = $plan["vpid"];

              $room_price = [];
              $room_price = $plan;
              $policy_id = $plan["policy"];
              $sql = "SELECT * FROM policies_$lcode WHERE id = '$policy_id'";
              $rezultat = mysqli_query($konekcija, $sql);
              $policy = mysqli_fetch_assoc($rezultat);
              $room_price["payment"] = $policy["type"];
              $room_price["cancellation"] = $policy["description"];
              $sql = "SELECT AVG(room_$room_id) AS price FROM prices_values_$lcode WHERE id = '$plan_id' AND (price_date >= '$dfrom' AND price_date < '$dto')";
              if($dfrom == "" || $dto == "")
                $sql = "SELECT MIN(room_$room_id) AS price FROM prices_values_$lcode WHERE id = '$plan_id'";
              $rezultat = mysqli_query($konekcija, $sql);
              $red = mysqli_fetch_assoc($rezultat);
              $price = $red["price"];
              if($plan["vpid"] != ""){ // Fixing virtual prices
                $variation_type = $plan["variation_type"];
                $variation = $plan["variation"];
                $value = $price;
                if($variation_type == -2) // - fixed
                  $new_value = $value - $variation;
                else if($variation_type == -1) // - %
                  $new_value = $value - $value * $variation / 100;
                else if($variation_type == 1) // + %
                  $new_value = $value + $value * $variation / 100;
                else if($variation_type == 2) // + fixed
                  $new_value = $value + $variation;
                if($new_value < 0)
                  $new_value = 0;
                $price = $new_value;
              }
            // Fixing guest number prices
              $additional_prices = (array) json_decode($rooms[$i]["additional_prices"]);
              if($additional_prices["active"] == 1){
                  if($additional_prices["variation"] < 0)
                    $additional_prices["variation"] = - $additional_prices["variation"];
                  if($additional_prices["variation_type"] == "fixed"){
                      $delta = $guests - $additional_prices["default"];
                      $price += $delta * $additional_prices["variation"];
                  }
                  else if($additional_prices["variation_type"] == "percent"){
                      $delta = $guests - $additional_prices["default"];
                      $delta_price = $price * $additional_prices["variation"] / 100;
                      $price += $delta * $delta_price;
                  }
              }

              $room_price["price"] = $price;
              array_push($room_prices, $room_price);
            }
            $rooms[$i]["prices"] = $room_prices;
            // Avail
            if($dfrom != "" && $dto != ""){
              $sql = "SELECT MIN(room_$room_id) AS avail FROM avail_values_$lcode WHERE avail_date >= '$dfrom' AND avail_date < '$dto'";
              $rezultat = mysqli_query($konekcija, $sql);
              $red = mysqli_fetch_assoc($rezultat);
              $rooms[$i]["avail"] = $red["avail"];
            }
            // Beds, will be a lot simpler with actual data
            $beds = $rooms[$i]["houserooms"];
            $rooms[$i]["beds"] = $beds;
          }

          // Fix rooms
          $available_rooms = [];
          for($i=0;$i<sizeof($rooms);$i++){
            if($dfrom == "" || $dto == "")
               array_push($available_rooms, $rooms[$i]);
            else if($rooms[$i]["avail"] > 0)
              array_push($available_rooms, $rooms[$i]);
          }
          $ret_val["rooms"] = $available_rooms;
          $result = $ret_val;
        }

        echo json_encode($result);
        $konekcija->close();

    }
    else {
        http_response_code(405);
    }

?>
