<?php

require "main.php";

function connectToNewDB()
{
  $server = "localhost";
  $serverUser = "otasyncm_korisnikU";
  $serverPassword = "CT*$,ULOqgb=";
  $database = "otasyncm_aplikacija";


  $konekcija = new mysqli($server, $serverUser, $serverPassword, $database);
  if ($konekcija->connect_error) {
    http_response_code(503);
      die("Failed to connect to database.");
  }
  mysqli_set_charset($konekcija , "utf8mb4");
  return $konekcija;
}

function initDatabase(){
  $konekcija = connectToDB();

  $sql = "CREATE TABLE all_users
  (
    id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(255),
    pwd VARCHAR(255),
    account VARCHAR(63),
    status INT,
    properties TEXT,
    reservations INT,
    guests INT,
    invoices INT,
    prices INT,
    restrictions INT,
    avail INT,
    rooms INT,
    channels INT,
    statistics INT,
    changelog INT,
    articles INT,
    wspay INT,
    engine INT,
    name VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(255),
    client_name VARCHAR(255),
    company_name VARCHAR(255),
    address TEXT,
    city VARCHAR(255),
    country VARCHAR(255),
    pib VARCHAR(255),
    mb VARCHAR(255),
    wspay_key VARCHAR(255),
    wspay_shop VARCHAR(255),
    undo_timer INT,
    notify_overbooking INT,
    notify_new_reservations INT,
    invoice_header INT,
    invoice_margin FLOAT,
    invoice_issued VARCHAR(63),
    invoice_delivery VARCHAR(63),
    room_count INT,
    ctypes TEXT,
    booking INT,
    booking_percentage FLOAT,
    expedia INT,
    airbnb INT,
    private INT,
    agency INT,
    split INT,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE all_properties
  (
    lcode VARCHAR(63) NOT NULL,
    account VARCHAR(63),
    name VARCHAR(255),
    type VARCHAR(63),
    address VARCHAR(255),
    zip VARCHAR(255),
    city VARCHAR(255),
    country VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(255),
    latitude VARCHAR(255),
    longitude VARCHAR(255),
    pib VARCHAR(255),
    mb VARCHAR(255),
    bank_account VARCHAR(255),
    iban VARCHAR(255),
    swift VARCHAR(255),
    logo TEXT,
    default_price VARCHAR(255),
    planned_earnings TEXT,
    custom_calendar TEXT,
    currency VARCHAR(63),
    currency_course FLOAT,
    articles_id VARCHAR(63),
    agency INT,
    rooms_tax_included INT,
    rooms_tax FLOAT,
    pdv_included INT,
    notify_guests INT,
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE all_sessions
  (
    pkey VARCHAR(63) NOT NULL,
    id INT,
    remember INT,
    last_action INT,
    notification_id VARCHAR(63),
    PRIMARY KEY (pkey)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE all_client_emails
  (
    lcode VARCHAR(63) NOT NULL,
    account VARCHAR(63),
    active INT,
    emails TEXT,
    arrivals INT,
    departures INT,
    stay INT,
    tomorrow INT,
    hour INT,
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE all_guest_emails
  (
    lcode VARCHAR(63) NOT NULL,
    account VARCHAR(63),
    received_active INT,
    received_subject TEXT,
    received_text TEXT,
    before_active INT,
    before_subject TEXT,
    before_text TEXT,
    after_active INT,
    after_subject TEXT,
    after_text TEXT,
    res_type INT,
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE all_channels
  (
    ctype INT NOT NULL,
    name VARCHAR(63),
    logo TEXT,
    commission FLOAT,
    PRIMARY KEY (ctype)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO all_channels (ctype, name, logo, commission) VALUES
  (1, 'Expedia', 'https://admin.otasync.me/img/ota/expedia.png', 18),
  (2, 'Booking.com', 'https://admin.otasync.me/img/ota/booking.png', 12),
  (3, 'Hotel.de/Hotel.info', 'https://wubook.net/imgs/default/channels_hotelde.png', 0),
  (5, 'InItalia', 'https://wubook.net/imgs/default/channels_init.png', 0),
  (6, 'Hotels.com (Expedia)', 'https://wubook.net/imgs/default/channels_hotelscom.png', 0),
  (9, 'HotelBeds', 'https://wubook.net/imgs/default/channels_hotelbeds.png', 23),
  (11, 'Hrs.com', 'https://admin.otasync.me/img/ota/hrs.png', 0),
  (13, 'Atrapalo', 'https://wubook.net/imgs/default/channels_atrapalo.png', 0),
  (17, 'BBPlanet', 'https://wubook.net/imgs/default/channels_bbplanet.png', 0),
  (19, 'Agoda', 'https://admin.otasync.me/img/ota/agoda.png', 0),
  (20, 'HostelsClub', 'https://wubook.net/imgs/default/channels_hostelsclub.png', 0),
  (23, 'Lastminute', 'https://wubook.net/imgs/default/channels_lastminute.png', 0),
  (25, 'Orbitz', 'https://wubook.net/imgs/default/channels_orbitz.png', 0),
  (27, 'InToscana', 'https://wubook.net/imgs/default/channels_intoscana.png', 0),
  (28, 'HostelWorld Group', 'https://wubook.net/imgs/default/channels_hostelworld4.png', 0),
  (29, 'HostelBookers (HostelWorld Group)', 'https://wubook.net/imgs/default/channels_hostelbookers.png', 0),
  (31, 'I Castelli', 'https://wubook.net/imgs/default/channels_icastelli.png', 0),
  (32, 'Vashotel', 'https://wubook.net/imgs/default/channels_vashotel.png', 0),
  (33, 'SleepingIn', 'https://wubook.net/imgs/default/channels_sleepingin.png', 0),
  (35, 'Feratel', 'https://wubook.net/imgs/default/channels_feratel.png', 0),
  (37, 'Synxis', 'https://wubook.net/imgs/default/channels_synxis.png', 0),
  (38, 'Tablethotels', 'https://wubook.net/imgs/default/channels_tablethotels.png', 0),
  (39, 'Easytobook', 'https://wubook.net/imgs/default/channels_easytobook.png', 0),
  (41, 'Ostrovok', 'https://wubook.net/imgs/default/channels_ostrovok.png', 18),
  (42, 'Hotusa', 'https://wubook.net/imgs/default/channels_hotusa.png', 0),
  (43, 'Airbnb', 'https://wubook.net/imgs/default/channels_airbnb.png', 3),
  (45, 'Wimdu', 'https://wubook.net/imgs/default/channels_wimdu.png', 0),
  (46, 'House Trip', 'https://wubook.net/imgs/default/channels_housetrip.png', 0),
  (48, 'Holiday Velvet', 'https://wubook.net/imgs/default/channels_holidayvelvet.png', 0),
  (49, 'FlipKey', 'https://wubook.net/imgs/default/channels_flipkey.png', 0),
  (50, '9Flats', 'https://wubook.net/imgs/default/channels_9flats.png', 0),
  (52, 'Amsterdam bed and breakfasts', 'https://wubook.net/imgs/default/channels_amsterdam-bed-and-breakfasts.png', 0),
  (53, 'Apartments Unlimited', 'https://wubook.net/imgs/default/channels_apartments-unlimited.png', 0),
  (54, 'Be My Guest', 'https://wubook.net/imgs/default/channels_bemyguest.png', 0),
  (55, 'Erfgoed', 'https://wubook.net/imgs/default/channels_erfgoed.png', 0),
  (56, 'Loving Apartments', 'https://wubook.net/imgs/default/channels_lovingapartments.png', 0),
  (58, 'eLong (Expedia)', 'https://wubook.net/imgs/default/channels_elong.png', 0),
  (59, 'Flat Club', 'https://wubook.net/imgs/default/channels_flat-club.png', 0),
  (60, 'TravelRepublic', 'https://wubook.net/imgs/default/channels_travelrepblic.png', 0),
  (61, 'AllHotelsMarket', 'https://wubook.net/imgs/default/channels_fenilot.png', 0),
  (62, 'BedAndBreakfast.eu', 'https://wubook.net/imgs/default/channels_bb_eu.png', 0),
  (63, 'ItalyHotels (Federalberghi)', 'https://wubook.net/imgs/default/channels_federalberghi.png', 0),
  (64, 'Capri Online', 'https://wubook.net/imgs/default/channels_caprionline.png', 0),
  (65, 'Bed-And-Breakfast.it', 'https://wubook.net/imgs/default/channels_scivoletto.png', 0),
  (66, 'Bronevik', 'https://wubook.net/imgs/default/channels_bronevik.png', 0),
  (67, 'GardaPass', 'https://wubook.net/imgs/default/channels_gardapass.png', 0),
  (68, 'Positano.com (Caprionline)', 'https://wubook.net/imgs/default/channels_positano.png', 0),
  (69, 'ItalyTraveller (Caprionline)', 'https://wubook.net/imgs/default/channels_italytraveller.png', 0),
  (70, 'Capri.it (Caprionline)', 'https://wubook.net/imgs/default/channels_capri_it.png', 0),
  (71, 'Capri.net (Caprionline)', 'https://wubook.net/imgs/default/channels_capri_net.png', 0),
  (73, 'Mr and Mrs Smith', 'https://wubook.net/imgs/default/channels_mrandmrssmith.png', 0),
  (74, 'HotelInn', 'https://wubook.net/imgs/default/channels_hotelinn.png', 0),
  (75, 'TheBestSpaHotels', 'https://wubook.net/imgs/default/channels_thebestspahotels.png', 0),
  (78, 'Airbnb ICal', 'https://wubook.net/imgs/default/channels_airbnb.png', 0),
  (79, 'Booking Piemonte', 'https://wubook.net/imgs/default/channels_bookingpiemonte.png', 0),
  (80, 'Bestday', 'https://wubook.net/imgs/default/channels_bestday.png', 0),
  (81, 'JacTravel', 'https://wubook.net/imgs/default/channels_jactravel.png', 0),
  (82, 'Moena', 'https://wubook.net/imgs/default/channels_moena.png', 0),
  (83, 'Italcamel', 'https://wubook.net/imgs/default/channels_italcamel.png', 0),
  (85, 'Revato (Roomguru)', 'https://wubook.net/imgs/default/channels_hotelscombined.png', 0),
  (86, 'MaxMind', 'https://wubook.net/imgs/default/channels_maxmind.png', 0),
  (87, 'Prestigia', 'https://wubook.net/imgs/default/channels_prestigia.png', 0),
  (89, 'Charme & Relax', 'https://wubook.net/imgs/default/channels_charmerelax.png', 0),
  (90, 'Lovevda', 'https://wubook.net/imgs/default/channels_lovevda.png', 0),
  (91, 'HotelDO (Bestday)', 'https://wubook.net/imgs/default/channels_hoteldo.png', 0),
  (93, 'Aja', 'https://wubook.net/imgs/default/channels_aja.png', 0),
  (95, 'Dorms.com', 'https://wubook.net/imgs/default/channels_dorms.png', 0),
  (97, 'UTS Travel (Hotelbook)', 'https://wubook.net/imgs/default/channels_utstravel.png', 0),
  (99, 'Despegar', 'https://wubook.net/imgs/default/channels_despegar.png', 0),
  (100, 'LiForYou', 'https://wubook.net/imgs/default/channels_liforyou.png', 0),
  (102, 'SunHotels', 'https://admin.otasync.me/img/ota/sunhotels.png', 0),
  (104, 'CBooking', 'https://wubook.net/imgs/default/channels_cbooking.png', 0),
  (105, 'WorldWide Hotel Link (WHL)', 'https://wubook.net/imgs/default/channels_whl.png', 0),
  (106, 'SpeedyBooker', 'https://wubook.net/imgs/default/channels_speedybooker.png', 0),
  (107, 'Specialhotels.nl (MaxMind)', 'https://wubook.net/imgs/default/channels_specialhotels.png', 0),
  (108, 'Agriturismo.net', 'https://wubook.net/imgs/default/channels_agriturismo.png', 0),
  (111, 'VeronaBooking', 'https://wubook.net/imgs/default/channels_veronabooking.png', 0),
  (112, 'Dolomiti.org', 'https://wubook.net/imgs/default/channels_dolomiti.png', 0),
  (113, 'LanghePass', 'https://wubook.net/imgs/default/channels_langhepass.png', 0),
  (114, 'Elba Promotion', 'https://wubook.net/imgs/default/channels_elbapromotion.png', 0),
  (117, 'Restel (Hotusa)', 'https://wubook.net/imgs/default/channels_restel.png', 0),
  (118, 'SignaTours', 'https://wubook.net/imgs/default/channels_signatours.png', 0),
  (121, 'DataHotel.Net', 'https://wubook.net/imgs/default/channels_datahotel.png', 0),
  (122, 'AdamelloSki (pontedilegnotonale.com)', 'https://wubook.net/imgs/default/channels_adamelloski.png', 0),
  (123, 'Distantis', 'https://wubook.net/imgs/default/channels_distantis.png', 0),
  (125, 'Zabroniryi.ru', 'https://wubook.net/imgs/default/channels_zabroniryl.png', 0),
  (126, 'Dobovo.com', 'https://wubook.net/imgs/default/channels_dobovo.png', 0),
  (127, 'Sirmionehotel', 'https://wubook.net/imgs/default/channels_sirmione.png', 0),
  (128, 'Acase.ru', 'https://wubook.net/imgs/default/channels_acase.png', 0),
  (129, 'Svoy Hotel', 'https://wubook.net/imgs/default/channels_svoyhotel.png', 0),
  (133, 'Salento.it', 'https://wubook.net/imgs/default/channels_salento.png', 0),
  (134, 'Megotel.ru', 'https://wubook.net/imgs/default/channels_megotel.png', 0),
  (135, 'Waytostay', 'https://wubook.net/imgs/default/channels_waytostay.png', 0),
  (138, 'Avia-centr.ru', 'https://wubook.net/imgs/default/channels_aviacentr.png', 0),
  (140, 'VeniceRentApartments', 'https://wubook.net/imgs/default/channels_venicerentapartments.png', 0),
  (141, 'AMK', 'https://wubook.net/imgs/default/channels_amk.png', 0),
  (143, 'WinBooking', 'https://wubook.net/imgs/default/channels_winbooking.png', 0),
  (144, 'OneTwoTrip!', 'https://wubook.net/imgs/default/channels_onetwotrip.png', 0),
  (145, 'Destination Florence', 'https://wubook.net/imgs/default/channels_destinatioflorence.png', 0),
  (146, 'Travel-click.ru', 'https://wubook.net/imgs/default/channels_travelclick.png', 0),
  (148, 'Odigeo Connect', 'https://wubook.net/imgs/default/channels_odigeo.png', 0),
  (149, 'Wanup', 'https://wubook.net/imgs/default/channels_wanup.png', 0),
  (150, 'HolidayLettings', 'https://wubook.net/imgs/default/channels_holidaylettings.png', 0),
  (151, 'Niumba', 'https://wubook.net/imgs/default/channels_niumba.png', 0),
  (152, 'Tripadvisor Vacation Rentals', 'https://wubook.net/imgs/default/channels_tripadvisorvacationrentals.png', 0),
  (153, 'Spotahome', 'https://wubook.net/imgs/default/channels_spotahome.png', 0),
  (154, 'BookingBuddy', 'https://wubook.net/imgs/default/channels_bookingbuddy.png', 0),
  (155, 'Ctrip', 'https://wubook.net/imgs/default/channels_ctrip.png', 0),
  (157, 'Booking Piemonte NEW', 'https://wubook.net/imgs/default/channels_bookingpiemonte.png', 0),
  (159, 'CharmingItaly', 'https://wubook.net/imgs/default/channels_charmingitaly.png', 0),
  (160, 'HotelsClick', 'https://wubook.net/imgs/default/channels_hotelsclick.png', 0),
  (161, 'HotelsCorse', 'https://wubook.net/imgs/default/channels_hotelscorse.png', 0),
  (162, 'Azent.ru', 'https://wubook.net/imgs/default/channels_azent.png', 0),
  (163, 'eDreams', 'https://wubook.net/imgs/default/channels_edreams.png', 0),
  (164, 'Opodo', 'https://wubook.net/imgs/default/channels_opodo.png', 0),
  (165, 'GO Voyages', 'https://wubook.net/imgs/default/channels_govoyages.png', 0),
  (166, 'Travellink', 'https://wubook.net/imgs/default/channels_travellink.png', 0),
  (168, 'Fluxto', 'https://wubook.net/imgs/default/channels_fluxto.png', 0),
  (169, 'Hotel Bonanza', 'https://wubook.net/imgs/default/channels_bonanza.png', 0),
  (171, 'Els Ports', 'https://wubook.net/imgs/default/channels_elsports.png', 0),
  (172, 'S7.ru', 'https://wubook.net/imgs/default/channels_s7.png', 0),
  (173, 'Gardasee.de', 'https://wubook.net/imgs/default/channels_gardasee.png', 0),
  (174, 'Welcomebeds', 'https://wubook.net/imgs/default/channels_welcomebeds.png', 0),
  (176, 'ConilHospeda', 'https://wubook.net/imgs/default/channels_conilhospeda.png', 0),
  (177, 'Fincahotels', 'https://wubook.net/imgs/default/channels_fincahotels.png', 0),
  (178, 'Italy Hotels Umbria', 'https://wubook.net/imgs/default/channels_italyhotels_umbria.png', 0),
  (179, 'Imperatore Travel', 'https://wubook.net/imgs/default/channels_imperatore.png', 0),
  (180, 'AIC Travel Group', 'https://wubook.net/imgs/default/channels_aicgroup.png', 0),
  (181, 'HostelGalaxy', 'https://wubook.net/imgs/default/channels_hostelgalaxy.png', 0),
  (182, 'Trentino Booking', 'https://wubook.net/imgs/default/channels_trentino.png', 0),
  (183, 'Grado.it', 'https://wubook.net/imgs/default/channels_grado.png', 0),
  (184, 'Livigno.eu', 'https://wubook.net/imgs/default/channels_livigno.png', 0),
  (185, 'Evadirte.com', 'https://wubook.net/imgs/default/channels_evadirte.png', 0),
  (186, 'HotelNet', 'https://wubook.net/imgs/default/channels_hotelnet.png', 0),
  (187, 'Ciclosophy', 'https://wubook.net/imgs/default/channels_fenilot.png', 0),
  (188, 'Travco', 'https://wubook.net/imgs/default/channels_travco.png', 0),
  (189, 'MRS Tour', 'https://wubook.net/imgs/default/channels_mrstour.png', 0),
  (191, 'BMS Travelminds', 'https://wubook.net/imgs/default/channels_travelminds.png', 0),
  (192, 'Homeaway', 'https://admin.otasync.me/img/ota/homeaway.png', 0),
  (196, 'DMS Valdichiana Living', 'https://wubook.net/imgs/default/channels_valdichiana.png', 0),
  (197, 'Mirai', 'https://wubook.net/imgs/default/channels_mirai.png', 0),
  (198, 'World2Meet', 'https://wubook.net/imgs/default/channels_w2m.png', 0),
  (199, 'Xenia', 'https://wubook.net/imgs/default/channels_xenia.png', 0),
  (200, 'DMS Aia Palas', 'https://wubook.net/imgs/default/channels_aia_palas.png', 0),
  (201, 'BedAndBreakfast.nl', 'https://wubook.net/imgs/default/channels_bb_nl.png', 0),
  (202, 'Esquiades', 'https://wubook.net/imgs/default/channels_esquiades.png', 0),
  (203, 'Ixpira', 'https://wubook.net/imgs/default/channels_ixpira.png', 0),
  (204, 'Alliance Resaux', 'https://wubook.net/imgs/default/channels_aresaux.png', 0),
  (205, 'Imperatore Travel World', 'https://wubook.net/imgs/default/channels_imperatore_world.png', 0),
  (206, 'YesAlps', 'https://wubook.net/imgs/default/channels_yalps.png', 0)";
  mysqli_query($konekcija, $sql);


  // Engine

  $sql = "CREATE TABLE engine_confirmation (
    lcode VARCHAR(63) NOT NULL,
    occupancy tinyint(1),
    phone tinyint(1),
    adress tinyint(1),
    city tinyint(1),
    country tinyint(1),
    card tinyint(1),
    cvv tinyint(1),
    checkIn varchar(20),
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE engine_contact (
    lcode VARCHAR(63) NOT NULL,
    address varchar(50),
    phone varchar(30),
    email varchar(50),
    web varchar(100),
    fb varchar(100),
    instagram varchar(100),
    yt varchar(100),
    longitude varchar(20),
    latitude varchar(20),
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE engine_footer (
    lcode VARCHAR(63) NOT NULL,
    description text,
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE engine_header (
    lcode VARCHAR(63) NOT NULL,
    name varchar(40),
    description varchar(100),
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE engine_messages (
    lcode VARCHAR(63) NOT NULL,
    welcome varchar(100),
    book text,
    noAvail text,
    voucher text,
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE engine_styles (
    lcode VARCHAR(63) NOT NULL,
    accentColor varchar(20),
    borderRadius int(20),
    logo text,
    backgroundImg text,
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE engine_selectdates (
    lcode VARCHAR(63) NOT NULL,
    nights int(11),
    sameDayReservation varchar(20),
    cents tinyint(1),
    children tinyint(1),
    PRIMARY KEY (lcode)
  )";
  mysqli_query($konekcija, $sql);
}

function initUser($account, $userToken, $konekcija){
  // Fetch properties
  $properties = makeRequest("fetch_properties", array($userToken));
  // Insert all properties
  $all_properties = [];
  foreach ($properties as $key => $value){
    array_push($all_properties, $key);
    $name = mysqli_real_escape_string($konekcija, $value['name']);
    $address = mysqli_real_escape_string($konekcija, $value['address']);
    $zip = $value['zip'];
    $city = mysqli_real_escape_string($konekcija, $value['city']);
    $country = mysqli_real_escape_string($konekcija, $value['country']);
    $email = $value['email'];
    $phone = $value['phone'];
    $latitude = $value['latitude'];
    $longitude = $value['longitude'];
    $custom_calendar = [];
    $sql = "INSERT IGNORE INTO all_properties VALUES
    (
      '$key',
      '$account',
      '$name',
      '',
      '$address',
      '$zip',
      '$city',
      '$country',
      '$email',
      '$phone',
      '$latitude',
      '$longitude',
      '',
      '',
      '',
      '',
      '',
      '',
      '',
      '[0,0,0,0,0,0,0,0,0,0,0,0]',
      '',
      'EUR',
      1,
      '',
      0,
      1,
      0,
      1,
      0
     )";
   $rezultat = mysqli_query($konekcija, $sql);
   if(!$rezultat)
     fatal_error("Failed to insert property $key - $name - $account.", 500); // Server failed
    else
      echo "Ubacen objekat $key - $name - $account <br>";
  }
  $all_properties = implode(",", $all_properties);
  // Insert user
  $sql = "INSERT INTO all_users (username, pwd, account, status, properties, reservations, guests, invoices, prices, restrictions, avail, rooms, channels, statistics, changelog, articles, wspay, engine, name, email, phone, client_name, company_name, address, city, country, pib, mb, wspay_key, wspay_shop, undo_timer, notify_overbooking, notify_new_reservations, invoice_header, invoice_margin, invoice_issued, invoice_delivery, room_count, ctypes, booking, booking_percentage, expedia, airbnb, private, agency, split)
  VALUES
  (
    '$account',
    '',
    '$account',
    1,
    '$all_properties',
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    3,
    0,
    0,
    3,
    'Master',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    60,
    0,
    0,
    0,
    10,
    'today',
    'today',
    0,
    '',
    0,
    15,
    0,
    0,
    1,
    0,
    0
  )";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Failed to insert user.", 500); // Server failed
  makeReleaseRequest("release_token", array($userToken));

  return explode(",", $all_properties);
}

function initProperty($account, $lcode, $konekcija){


  $sql = "CREATE TABLE rooms_$lcode
  (
    id VARCHAR(63) NOT NULL,
    name VARCHAR(255),
    shortname VARCHAR(255),
    type VARCHAR(255),
    price FLOAT,
    availability INT,
    occupancy INT,
    description TEXT,
    images TEXT,
    area FLOAT,
    bathrooms FLOAT,
    houserooms TEXT,
    amenities TEXT,
    booking_engine INT,
    room_numbers TEXT,
    linked_room TEXT,
    parent_room VARCHAR(63),
    additional_prices TEXT,
    status VARCHAR(255),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE channels_$lcode
  (
    id VARCHAR(63) NOT NULL,
    ctype INT,
    name VARCHAR(255),
    tag VARCHAR(255),
    logo VARCHAR(255),
    commission FLOAT,
    hotel_id VARCHAR(255),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE prices_$lcode
  (
    id VARCHAR(63) NOT NULL,
    name VARCHAR(255),
    type VARCHAR(255),
    variation VARCHAR(63),
    variation_type VARCHAR(63),
    vpid VARCHAR(63),
    description TEXT,
    policy INT,
    booking_engine INT,
    board VARCHAR(63),
    restriction_plan VARCHAR(63),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE restrictions_$lcode
  (
    id VARCHAR(63) NOT NULL,
    name VARCHAR(255),
    type VARCHAR(255),
    rules TEXT,
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE changelog_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    data_type VARCHAR(63),
    action VARCHAR(63),
    old_data TEXT,
    new_data TEXT,
    undone INT,
    created_by VARCHAR(255),
    created_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE reservations_$lcode
  (
      reservation_code VARCHAR(63) NOT NULL,
      status INT,
      was_modified INT,
      modified_reservation VARCHAR(63),
      new_reservation_code VARCHAR(63),
      date_received DATE,
      time_received TIME,
      date_arrival DATE,
      date_departure DATE,
      nights INT,
      rooms TEXT,
      room_data TEXT,
      real_rooms TEXT,
      room_numbers TEXT,
      men INT,
      children INT,
      guest_ids TEXT,
      customer_name VARCHAR(255),
      customer_surname VARCHAR(255),
      customer_mail VARCHAR(255),
      customer_phone VARCHAR(255),
      customer_country VARCHAR(255),
      customer_address VARCHAR(255),
      customer_zip VARCHAR(255),
      note TEXT,
      payment_gateway_fee TEXT,
      reservation_price FLOAT,
      services TEXT,
      services_price FLOAT,
      total_price FLOAT,
      pricing_plan VARCHAR(63),
      discount TEXT,
      invoices TEXT,
      cc_info INT,
      guest_status VARCHAR(63),
      date_canceled DATE,
      deleted_advance INT,
      addons_list VARCHAR(255),
      id_woodoo VARCHAR(255),
      channel_reservation_code VARCHAR(255),
      additional_data TEXT,
      created_by VARCHAR(255),
      PRIMARY KEY (reservation_code)
    )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE guests_$lcode
  (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(255),
      surname VARCHAR(255),
      email VARCHAR(255),
      phone VARCHAR(255),
      country_of_residence VARCHAR(7),
      place_of_residence VARCHAR(255),
      address TEXT,
      zip VARCHAR(255),
      country_of_birth VARCHAR(7),
      date_of_birth DATE,
      gender VARCHAR(15),
      host_again INT,
      note TEXT,
      total_arrivals INT,
      total_nights INT,
      total_paid INT,
      registration_data TEXT,
      created_by VARCHAR(255),
      PRIMARY KEY (id)
    )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE invoices_$lcode
  (
      id INT NOT NULL AUTO_INCREMENT,
      invoice_number INT,
      invoice_year INT,
      created_date DATE,
      created_time TIME,
      user VARCHAR(255),
      type VARCHAR(255),
      mark VARCHAR(255),
      room_id VARCHAR(63) NOT NULL DEFAULT '',
      status VARCHAR(255),
      issued DATE,
      delivery DATE,
      payment_type INT,
      name VARCHAR(255),
      pib VARCHAR(255),
      mb VARCHAR(255),
      address VARCHAR(255),
      email VARCHAR(255),
      phone VARCHAR(255),
      reservation_name VARCHAR(255),
      services TEXT,
      price FLOAT,
      note TEXT,
      reservation_code VARCHAR(63),
      created_by VARCHAR(255),
      PRIMARY KEY (id)
    )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE extras_$lcode
  (
      id INT NOT NULL AUTO_INCREMENT,
      name VARCHAR(255),
      description TEXT,
      type VARCHAR(63),
      price FLOAT,
      pricing INT,
      daily INT,
      dfrom DATE,
      dto DATE,
      restriction_plan VARCHAR(63),
      rooms TEXT,
      specific_rooms TEXT,
      image TEXT,
      tax FLOAT,
      created_by VARCHAR(255),
      PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE policies_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    name varchar(100),
    type varchar(100),
    value int(11),
    freeDays int(11),
    enableFreeDays tinyint(1),
    description text,
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);
  // Insert initial policy
  $sql = "INSERT INTO policies_$lcode (name, type, value, enableFreeDays, freeDays, description, created_by) VALUES
  (
    'Osnovna politika',
    'firstNight',
    '0',
    '0',
    '0',
    'U slučaju otkazivanja, zadržava se pravo naplate prve noći rezervacije',
    '1'
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE promocodes_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    code varchar(30),
    name varchar(30),
    target varchar(30),
    value varchar(30),
    description varchar(150),
    type varchar(20),
    created_by VARCHAR(255),
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  // Emails
  $sql = "SELECT email FROM all_users WHERE account = '$account' AND status = 1";
  $rezultat = mysqli_query($konekcija, $sql);
  $email = mysqli_fetch_assoc($rezultat);
  $email = $email["email"];

  $sql = "INSERT INTO all_client_emails VALUES (
    '$lcode',
    '$account',
    0,
    '$email',
    0,
    0,
    0,
    0,
    0
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO all_guest_emails VALUES (
    '$lcode',
    '$account',
    0,
    '',
    '',
    0,
    '',
    '',
    0,
    '',
    '',
    1
  )";
  mysqli_query($konekcija, $sql);


  // Inserting wubook data
  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));


  // Rooms
  $rooms = makeRequest("fetch_rooms", array($userToken, $lcode, 0));
  $colors = ["4286f4","0049bf","13b536","157c26","673eef","2e00c9","e59900","ddc133","ef2f2f","8e0c0c"];
  $cal_real_rooms = [];
  $cal_single_rooms = [];
  for($i=0;$i<sizeof($rooms);$i++){
    $id = $rooms[$i]["id"];
    $name = $rooms[$i]["name"];
    $shortname = $rooms[$i]["shortname"];
    $occupancy = $rooms[$i]["occupancy"];
    $price = $rooms[$i]["price"];
    $availability = $rooms[$i]["availability"];
    $color = $colors[$i%10];
    $parent_room = $rooms[$i]["subroom"];
    $room_numbers = [];
    $room_status = [];
    array_push($cal_real_rooms, $id);
    for($j=1;$j<=$availability;$j++){
      array_push($room_numbers, $j);
      array_push($room_status, "clean");
      array_push($cal_single_rooms, $id . "_" . ($j - 1));
    }
    $room_numbers = implode(",", $room_numbers);
    $room_status = implode(",", $room_status);
    $booking_engine = 1;
    if($parent_room != "0"){
      $booking_engine = 0;
    }

    $sql = "INSERT INTO rooms_$lcode VALUES (
      '$id',
      '$name',
      '$shortname',
      'apartment',
      $price,
      $availability,
      $occupancy,
      '',
      '[]',
      0,
      0,
      '[]',
      '[]',
      $booking_engine,
      '$room_numbers',
      '{\"active\":0, \"avail\":0, \"price\":0, \"restrictions\":0, \"variation\": 0, \"variation_type\": \"fixed\"}',
      '$parent_room',
      '{\"active\": 0, \"room\": -1, \"variation\": 0, \"variation_type\": \"fixed\"}',
      '$room_status',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Custom calendar
  $custom_calendar = [];
  $custom_calendar["type"] = "room_types";
  $custom_calendar["avail"] = "0";
  $custom_calendar["price"] = "1";
  $custom_calendar["min"] = "0";
  $custom_calendar["room_name"] = "1";
  $custom_calendar["room_type"] = "1";
  $custom_calendar["room_status"] = "1";
  $custom_calendar["room_types"] = $cal_real_rooms;
  $custom_calendar["single_rooms"] = $cal_single_rooms;
  $custom_calendar["days"] = "21";
  $custom_calendar = json_encode($custom_calendar);
  $sql = "UPDATE all_properties SET custom_calendar = '$custom_calendar' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);

  // Channels
  $channels = makeRequest("get_otas", array($userToken, $lcode));
  for($i=0;$i<sizeof($channels);$i++){
    $id = $channels[$i]["id"];
    $ctype = $channels[$i]["ctype"];
    $hotel_id = $channels[$i]["channel_hid"];
    $sql = "SELECT name, logo, commission
            FROM all_channels
            WHERE ctype = $ctype
            LIMIT 1";
    $rezultat = mysqli_query($konekcija, $sql); // Default channel data
    $red = mysqli_fetch_assoc($rezultat);
    $name = $red["name"];
    $tag = $channels[$i]["tag"];
    if($tag != "")
      $name = $name . " (" . $tag . ")";
    $logo = $red["logo"];
    $commission = $red["commission"];
    $sql = "INSERT INTO channels_$lcode VALUES (
      '$id',
      $ctype,
      '$name',
      '$tag',
      '$logo',
      $commission,
      '$hotel_id',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Pricing plans
  $pricing_plans = makeRequest("get_pricing_plans", array($userToken, $lcode));
  $default_price = "";
  for($i=0;$i<sizeof($pricing_plans);$i++){
    $plan_id = $pricing_plans[$i]["id"];
    $plan_name = $pricing_plans[$i]["name"];
    $plan_type = "daily";
    $plan_variation = "";
    $plan_variation_type = "";
    $plan_vpid = "";
    if(isset($pricing_plans[$i]["variation"])){
      $plan_type = "virtual";
      $plan_variation = $pricing_plans[$i]["variation"];
      $plan_variation_type = $pricing_plans[$i]["variation_type"];
      $plan_vpid = $pricing_plans[$i]["vpid"];
    }
    if($plan_type == "daily" && $default_price == "")
      $default_price = $plan_id;
    $sql = "INSERT INTO prices_$lcode VALUES(
      '$plan_id',
      '$plan_name',
      '$plan_type',
      '$plan_variation',
      '$plan_variation_type',
      '$plan_vpid',
      '',
      1,
      0,
      '',
      '',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }
  // Set default plan
  $sql = "UPDATE all_properties SET default_price = '$default_price' WHERE lcode = '$lcode'";
  mysqli_query($konekcija, $sql);

  // Restriction plans
  $restriction_plans = makeRequest("rplan_rplans", array($userToken, $lcode));
  $sql = "INSERT INTO restrictions_$lcode VALUES(
    '1',
    'Osnovne restrikcije',
    'daily',
    '{}',
    'Wubook'
  )";
  mysqli_query($konekcija, $sql);
  for($i=0;$i<sizeof($restriction_plans);$i++){
    $plan_id = $restriction_plans[$i]["id"];
    $plan_name = $restriction_plans[$i]["name"];
    $plan_type = "daily";
    $plan_rules = "{}";
    if(isset($restriction_plans[$i]["rules"])){
      $plan_type = "compact";
      $plan_rules = json_encode($restriction_plans[$i]["rules"]);
    }
    $sql = "INSERT INTO restrictions_$lcode VALUES(
      '$plan_id',
      '$plan_name',
      '$plan_type',
      '$plan_rules',
      'Wubook'
    )";
    mysqli_query($konekcija, $sql);
  }

  // Fetching all reservations



  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));

  makeUncheckedRequest("push_activation", array($userToken, $lcode, "https://admin.otasync.me/api/notifications/$account"));
  makeUncheckedRequest("unmark_reservations", array($userToken, $lcode, "01/01/2019"));
  $actions = 5; // Already did 5 actions with token
  $reservations = makeRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1));
  $modified_sqls = [];
  while(sizeof($reservations) > 0){
    for($i=0;$i<sizeof($reservations);$i++){ // Insert all
      // Res Data
      $reservation = $reservations[$i];
      $reservation_code = $reservation["reservation_code"];
      $status = $reservation["status"];
      $was_modified = $reservation["was_modified"];
      $modified_reservations = "";
      if(sizeof($reservation["modified_reservations"]))
        $modified_reservations = $reservation["modified_reservations"][0];

      $date_received_time = explode(" ", $reservation["date_received_time"]);
      $date_received = dmyToYmd($date_received_time[0]);
      $time_received = $date_received_time[1];
      $date_arrival = dmyToYmd($reservation["date_arrival"]);
      $date_departure = dmyToYmd($reservation["date_departure"]);
      $nights = dateDiff($date_arrival, $date_departure);

      // Rooms data
      $dayprices = $reservation["dayprices"];
      $rooms = $reservation["rooms"];
      $room_data = [];
      $real_rooms = [];

      $rooms_map = []; // Init map of used rooms
      $sql = "SELECT name, shortname, room_numbers, id, parent_room FROM rooms_$lcode WHERE id IN ($rooms)";
      $rezultat = mysqli_query($konekcija, $sql);
      while($red = mysqli_fetch_assoc($rezultat)){
        $rooms_map[$red["id"]] = [];
        $rooms_map[$red["id"]]["id"] = $red["id"];
        $rooms_map[$red["id"]]["name"] = $red["name"];
        $rooms_map[$red["id"]]["shortname"] = $red["shortname"];
        $rooms_map[$red["id"]]["count"] = 0;
        $rooms_map[$red["id"]]["parent_id"] = $red["id"];
        if($red["parent_room"] != '0'){
          $rooms_map[$red["id"]]["parent_id"] = $red["parent_room"];
        }
        $rooms_map[$red["id"]]["room_numbers"] = [];
      }
      $rooms = explode(",", $rooms);
      for($j=0;$j<sizeof($rooms);$j++){
        $room = $rooms[$j];
        array_push($real_rooms, $rooms_map[$room]["parent_id"]);
        $rooms_map[$room]["count"] += 1;
        $rooms_map[$room]["price"] = array_sum($dayprices[$room]) / sizeof($dayprices[$room]);
      }


      // Get room numbers
      $dfrom = $date_arrival;
      $dto = $date_departure;
      $occupied_rooms = []; // Init occupied rooms struct
      for($j=0;$j<sizeof($real_rooms);$j++){
        $occupied_rooms[$real_rooms[$i]] = [];
      }
      for($j=0;$j<sizeof($real_rooms);$j++){
        $occupied_rooms[$real_rooms[$j]] = []; // It's a map,, use isset to check if it's occupied
      }
      $sql = "SELECT real_rooms, room_numbers FROM reservations_$lcode WHERE date_arrival < '$dto' AND date_departure > '$dfrom' AND status = 1";
      $rezultat = mysqli_query($konekcija, $sql);
      while($red = mysqli_fetch_assoc($rezultat)){
        $res_rooms = explode(",", $red["real_rooms"]);
        $res_room_numbers = explode(",", $red["room_numbers"]);
        for($j=0;$j<sizeof($res_rooms);$j++){
          $occupied_rooms[$res_rooms[$j]][$res_room_numbers[$j]] = 1;
        }
      } // Occupied rooms done

      $room_numbers = [];
      for($j=0;$j<sizeof($real_rooms);$j++){ // Getting available rooms
        $room_id = $real_rooms[$j];
        $n=0;
        while(1){
          if(isset($occupied_rooms[$room_id][$n])){ // Room is occupied
            $n += 1;
          }
          else {
            array_push($room_numbers, $n);
            $occupied_rooms[$room_id][$n] = 1;
            array_push($rooms_map[$room_id]["room_numbers"], $n); // Remember room number used
            break;
          }
        }
      }
      foreach($rooms_map as $key => $values){
        array_push($room_data, $values);
      }
      $rooms = implode(",", $rooms);
      $real_rooms = implode(",", $real_rooms);
      $room_numbers = implode(",", $room_numbers);
      $room_data = json_encode($room_data);

      $men = $reservation["men"];
      $children = $reservation["children"];

      $guest_ids = insertWubookGuest($lcode, $reservation, $konekcija);
      $customer_name = mysqli_real_escape_string($konekcija, $reservation["customer_name"]);
      $customer_surname = mysqli_real_escape_string($konekcija, $reservation["customer_surname"]);
      $customer_mail = mysqli_real_escape_string($konekcija, $reservation["customer_mail"]);
      $customer_phone = mysqli_real_escape_string($konekcija, $reservation["customer_phone"]);
      $customer_country = mysqli_real_escape_string($konekcija, $reservation["customer_country"]);
      $customer_address = mysqli_real_escape_string($konekcija, $reservation["customer_address"]);
      $customer_zip = mysqli_real_escape_string($konekcija, $reservation["customer_zip"]);
      $note = mysqli_real_escape_string($konekcija, $reservation["customer_notes"]);

      $avans = $reservation['payment_gateway_fee'] != "" ? $reservation['payment_gateway_fee'] : 0;
      $payment_gateway_fee = [];
      $payment_gateway_fee["type"] = "fixed";
      $payment_gateway_fee["value"] = $avans;
      $payment_gateway_fee = json_encode($payment_gateway_fee);

      $reservation_price = $reservation["amount"];
      $services = "[]";
      $services_price = 0;
      $total_price = $reservation_price;
      $pricing_plan = "";
      $discount = [];
      $discount["type"] = "fixed";
      $discount["value"] = 0;
      $discount = json_encode($discount);
      $invoices = "[]";
      $cc_info = $reservation['cc_info'];
      $no_show = 0;
      $deleted_advance = isset($reservation['deleted_advance']) ? $reservation['deleted_advance'] : 0;
      $date_canceled = "0001-01-01";
      if(isset($reservation['deleted_at_time'])) {
        $date_canceled = explode(" ", $reservation['deleted_at_time']);
        $date_canceled = dmyToYmd($date_canceled[0]);
      }
      $addons_list = json_encode($reservation["addons_list"]);
      $id_woodoo = $reservation["id_woodoo"];
      $channel_reservation_code = $reservation["channel_reservation_code"];
      $additional_data = "{}";
      if(isset($reservation["ancillary"]))
        $additional_data = mysqli_real_escape_string($konekcija, json_encode($reservation["ancillary"]));
      $created_by = "Wubook";

      // Update old reservation
      if($modified_reservations != "" && $modified_reservations != $reservation_code){
        array_push($modified_sqls, "UPDATE reservations_$lcode SET new_reservation_code = '$reservation_code' WHERE reservation_code = '$modified_reservations'");
      }

      // Insert
      $sql = "INSERT INTO reservations_$lcode VALUES (
        '$reservation_code',
        $status,
        $was_modified,
        '$modified_reservations',
        '',
        '$date_received',
        '$time_received',
        '$date_arrival',
        '$date_departure',
        $nights,
        '$rooms',
        '$room_data',
        '$real_rooms',
        '$room_numbers',
        $men,
        $children,
        '$guest_ids',
        '$customer_name',
        '$customer_surname',
        '$customer_mail',
        '$customer_phone',
        '$customer_country',
        '$customer_address',
        '$customer_zip',
        '$note',
        '$payment_gateway_fee',
        $reservation_price,
        '$services',
        $services_price,
        $total_price,
        '$pricing_plan',
        '$discount',
        '$invoices',
        $cc_info,
        'waiting_arrival',
        '$date_canceled',
        $deleted_advance,
        '$addons_list',
        '$id_woodoo',
        '$channel_reservation_code',
        '$additional_data',
        'Wubook'
      )";
      mysqli_query($konekcija, $sql);
    }
    $reservations = makeRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1)); // Fetch next
    $actions += 1;
    if($actions > 50){ // Reset token
      makeReleaseRequest("release_token", array($userToken));
      $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
      $actions = 0;
    }
  }
  for($i=0;$i<sizeof($modified_sqls);$i++){
    mysqli_query($konekcija, $modified_sqls[$i]);
  }
  makeRequest("release_token", array($userToken));



  // Create avail/price/restriction values tables
  $real_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode WHERE parent_room = '0'"; // Only get real rooms for avail
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($real_rooms, $red["id"]);
  }
  $rooms = [];
  $restrictions_rooms = [];
  $sql = "SELECT id FROM rooms_$lcode"; // Get all rooms for prices and restrictions
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    array_push($rooms, $red["id"]);
    // Making all fields for restrictions table
    array_push($restrictions_rooms, "min_stay_" . $red["id"]);
    array_push($restrictions_rooms, "min_stay_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "max_stay_" . $red["id"]);
    array_push($restrictions_rooms, "closed_" . $red["id"]);
    array_push($restrictions_rooms, "closed_departure_" . $red["id"]);
    array_push($restrictions_rooms, "closed_arrival_" . $red["id"]);
    array_push($restrictions_rooms, "no_ota_" . $red["id"]);
  }



  // Avail
  $rooms_sql = "room_" . implode(" INT, room_", $real_rooms) . " INT"; // SQL to create a column for each room
  $sql = "CREATE TABLE avail_values_$lcode
  (
    avail_date DATE NOT NULL,
    $rooms_sql,
    PRIMARY KEY (avail_date)
  )";
  $rezultat = mysqli_query($konekcija, $sql);
  // Prices
  $rooms_sql = "room_" . implode(" FLOAT, room_", $rooms) . " FLOAT";
  $sql = "CREATE TABLE prices_values_$lcode
  (
    id VARCHAR(63),
    price_date DATE NOT NULL,
    $rooms_sql,
    PRIMARY KEY (id, price_date)
  )";
  mysqli_query($konekcija, $sql);
  // Restrictions
  $rooms_sql = implode(" INT, ", $restrictions_rooms) . " INT";
  $sql = "CREATE TABLE restrictions_values_$lcode
  (
    id VARCHAR(63),
    restriction_date DATE NOT NULL,
    $rooms_sql,
    PRIMARY KEY (id, restriction_date)
  )";
  mysqli_query($konekcija, $sql);

  // Values of avail/prices/restrictions
  $dfrom = date("Y-m-d");
  $time = strtotime($dfrom);
  $dto = date("Y-m-d", $time+364*24*60*60);
  plansInsertWubook($lcode, $account, $dfrom, $dto, $konekcija);
  $dfrom = date("Y-m-d", $time+365*24*60*60);
  $dto = date("Y-m-d", $time+729*24*60*60);
  plansInsertWubook($lcode, $account, $dfrom, $dto, $konekcija);
  plansInsertWubook($lcode, $account, "2021-03-01", "2021-03-31", $konekcija);


  // Articles

  $sql = "CREATE TABLE categories_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(63) NOT NULL,
    parent_id INT,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "CREATE TABLE articles_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    category_id INT NOT NULL,
    barcode INT NOT NULL DEFAULT -1,
    code INT NOT NULL,
    tax_rate TINYINT NOT NULL DEFAULT 0,
    description VARCHAR(32),
    class TINYINT NOT NULL DEFAULT 0,
    price FLOAT NOT NULL,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO categories_$lcode (name, parent_id) VALUES ('Artikli', 0)";
  mysqli_query($konekcija, $sql);

  // Yield

  $sql = "CREATE TABLE yield_variations_$lcode
  (
    id INT NOT NULL AUTO_INCREMENT,
    variation_type INT,
    variation_value FLOAT,
    PRIMARY KEY (id)
  )";
  mysqli_query($konekcija, $sql);

  // Engine

  // Get user defaults
  $sql = "SELECT email, phone, address, name, latitude, longitude, logo FROM all_properties WHERE lcode = '$lcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $property = mysqli_fetch_assoc($rezultat);
  $sql = "INSERT INTO engine_confirmation VALUES (
    '$lcode',
    0,
    1,
    1,
    1,
    1,
    1,
    1,
    '12:00-16:00'
  )";
  mysqli_query($konekcija, $sql);

  $address = $property["address"];
  $phone = $property["phone"];
  $email = $property["email"];
  $longitude = $property["longitude"];
  $latitude = $property["latitude"];
  $sql = "INSERT INTO engine_contact VALUES (
    '$lcode',
    '$address',
    '$phone',
    '$email',
    '',
    '',
    '',
    '',
    '$longitude',
    '$latitude'
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO engine_footer VALUES (
    '$lcode',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $name = $property["name"];
  $sql = "INSERT INTO engine_header VALUES (
    '$lcode',
    '$name',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO engine_messages VALUES (
    '$lcode',
    '',
    '',
    '',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $logo = $property["logo"];
  $sql = "INSERT INTO engine_styles VALUES (
    '$lcode',
    '#2c3e50',
    5,
    '$logo',
    ''
  )";
  mysqli_query($konekcija, $sql);

  $sql = "INSERT INTO engine_selectdates VALUES (
    '$lcode',
    1,
    '12:00',
    0,
    0
  )";
  mysqli_query($konekcija, $sql);


}

initDatabase();

$konekcija = connectToDB();
$account = $_GET["account"];
$account = "PV117";

$sql = "SELECT * FROM all_users WHERE account = '$account'";
$rezultat = mysqli_query($konekcija, $sql);
if($red = mysqli_fetch_assoc($rezultat)){
  echo "Korisnik vec postoji";
  die();
}
$userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
$lcodes = initUser($account, $userToken, $konekcija);
for($i=0;$i<sizeof($lcodes);$i++){
  initProperty($account, $lcodes[$i], $konekcija);
}
?>
