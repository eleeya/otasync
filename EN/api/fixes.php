<?php

function fixReservation($res, $channel_data, $konekcija, $lcode){
  if(isset($res["services"]))
    $res["services"] = json_decode($res["services"]);
  if(isset($res["addons_list"]))
    $res["addons_list"] = json_decode($res["addons_list"]);
  if(isset($res["discount"]))
    $res["discount"] = json_decode($res["discount"]);
  if(isset($res["payment_gateway_fee"]))
    $res["payment_gateway_fee"] = json_decode($res["payment_gateway_fee"]);
  if(isset($res["rooms"]))
    $res["rooms"] = explode(",", $res["rooms"]);
  if(isset($res["real_rooms"]))
    $res["real_rooms"] = explode(",", $res["real_rooms"]);
  if(isset($res["room_numbers"]))
    $res["room_numbers"] = explode(",", $res["room_numbers"]);
  if(isset($res["room_data"]))
    $res["room_data"] = json_decode($res["room_data"]);
  if(isset($res["invoices"]))
    $res["invoices"] = json_decode($res["invoices"]);
  if(isset($res["additional_data"]))
    $res["additional_data"] = json_decode($res["additional_data"]);
  // Adding channel logo and name
  if(isset($res["id_woodoo"])){
    if(isset($channel_data[$res["id_woodoo"]])){
      $res["channel_logo"] = $channel_data[$res["id_woodoo"]]["logo"];
      $res["channel_name"] = $channel_data[$res["id_woodoo"]]["name"];
    }
    else {
      $res["channel_logo"] = "https://admin.otasync.me/img/ota/youbook.png";
      $res["channel_name"] = "";
    }
  }
  // Get guests
  $guest_ids = $res["guest_ids"];
  if($guest_ids == "")
    $guest_ids = 0;
  $guests = [];
  $sql = "SELECT * FROM guests_$lcode WHERE id IN ($guest_ids)";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($guests, fixGuest($red));
  }
  $res["guests"] = $guests;
  return $res;
}

function fixRoom($room){
  $room["houserooms"] = json_decode($room["houserooms"]);
  $room["additional_prices"] = json_decode($room["additional_prices"]);
  if($room["additional_prices"]->variation < 0)
    $room["additional_prices"]->variation = - $room["additional_prices"]->variation;
  $room["linked_room"] = json_decode($room["linked_room"]);
  $room["amenities"] = json_decode($room["amenities"]);
  $room["images"] = json_decode($room["images"]);
  if(isset($room["room_numbers"]))
    $room["room_numbers"] = explode(",", $room["room_numbers"]);
  if(isset($room["status"]))
    $room["status"] = explode(",", $room["status"]);
  return $room;
}

function fixRestriction($restriction){
  $restriction["rules"] = json_decode($restriction["rules"]);
  return $restriction;
}

function fixGuest($guest){
  $guest["registration_data"] = json_decode($guest["registration_data"]);
  return $guest;
}

function fixProperty($property){
  $property["planned_earnings"] = json_decode($property["planned_earnings"]);
  $property["custom_calendar"] = json_decode($property["custom_calendar"]);
  return $property;
}

function fixInvoice($invoice){
  $invoice["services"] = json_decode($invoice["services"]);
  return $invoice;
}

function fixExtra($extra){
  $extra["rooms"] = json_decode($extra["rooms"]);
  $extra["specific_rooms"] = json_decode($extra["specific_rooms"]);
  return $extra;
}

function fixChange($change){
  $change["old_data"] = json_decode($change["old_data"]);
  $change["new_data"] = json_decode($change["new_data"]);
  return $change;
}


 ?>
