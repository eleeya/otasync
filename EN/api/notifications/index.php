<?php

require "../main.php";

ignore_user_abort(true);
set_time_limit(0);

ob_start();

header('Connection: close');
header('Content-Length: '.ob_get_length());
ob_end_flush();
ob_flush();
flush();

  $account = getAction();
  $konekcija = connectToDB();
  $lcode = $_POST["lcode"];
  $rcode = $_POST["rcode"];
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $res = makeRequest("fetch_booking", array($userToken, $lcode, $rcode, 1));
  $res = $res[0];
  insertWubookReservation($lcode, $account, $res);
  sendReservationConfirmation($reservation_code, $lcode, $account, $konekcija);
  sendGuestEmail($reservation_code, $lcode, $account, $konekcija);

  $konekcija->close();

?>
