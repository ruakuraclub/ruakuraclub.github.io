<?php
  
  require_once( 'connection.php' );

  class Members{
    private $mysql;

    function __construct() {
      $database = new MYSQL_DB();
      $this->mysql = $database->getDBConnection();
    }

    function saveMember() {
      $now = date("Y-m-d H:i:s");
      $paid = isset( $_POST["paid"] ) ? true : false;
      $printed = isset( $_POST["card_printed"] ) ? true : false;
      $eftpos = isset( $_POST["eftpos"] ) ? true : false;
      $banking_rcc = isset( $_POST["banking_rcc"] ) ? true : false;
      $banking_squash = isset( $_POST["banking_squash"] ) ? true : false;

      if ( $stmt = $this->mysql->prepare('INSERT INTO members (
        firstname, lastname, prefername, member_number, birthdate, home_address, mobile, phone, email,
        firstname2, lastname2, prefername2, member_number2, birthdate2, home_address2, mobile2, phone2, email2,
        juniors, single_fee, double_fee, junior_fee, student_id, student_fee, retiredfee,
        renew_pool_key_fee, new_pool_key_fee, number_bands, bands_fee,
        renew_tennis_key_fee, new_tennis_key_fee, discount, rcc_total_fee,
        squash_competition_fee, squash_social_fee, squash_junior_fee, squash_summer_fee, squash_access_card, squash_total_fee,
        eftpos, banking_rcc, banking_squash, applied, paid, card_printed )
        VALUES (
          :firstname, :lastname, :prefername, :member_number, :birthdate, :home_address, :mobile, :phone, :email,
          :firstname2, :lastname2, :prefername2, :member_number2, :birthdate2, :home_address2, :mobile2, :phone2, :email2,
          :juniors, :single_fee, :double_fee, :junior_fee, :student_id, :student_fee, :retiredfee,
          :renew_pool_key_fee, :new_pool_key_fee, :number_bands, :bands_fee,
          :renew_tennis_key_fee, :new_tennis_key_fee, :discount, :rcc_total_fee,
          :squash_competition_fee, :squash_social_fee, :squash_junior_fee, :squash_summer_fee, :squash_access_card, :squash_total_fee,
          :eftpos, :banking_rcc, :banking_squash, :applied, :paid, :card_printed )'
        ) ) {
          $stmt->bindParam( ':firstname', $_POST["firstname"], PDO::PARAM_STR, 1024 );
          $stmt->bindParam( ':lastname', $_POST["lastname"], PDO::PARAM_STR, 1024 );
          $stmt->bindParam( ':prefername', $_POST["prefername"], PDO::PARAM_STR, 1024 );
          $stmt->bindParam( ':member_number', $_POST["member_number"], PDO::PARAM_INT );
          $stmt->bindParam( ':birthdate', $_POST["birthdate"], PDO::PARAM_STR );
          $stmt->bindParam( ':home_address', $_POST["home_address"], PDO::PARAM_STR );
          $stmt->bindParam( ':mobile', $_POST["mobile"], PDO::PARAM_STR );
          $stmt->bindParam( ':phone', $_POST["phone"], PDO::PARAM_STR );
          $stmt->bindParam( ':email', $_POST["email"], PDO::PARAM_STR );
          $stmt->bindParam( ':firstname2', $_POST["firstname2"], PDO::PARAM_STR );
          $stmt->bindParam( ':lastname2', $_POST["lastname2"], PDO::PARAM_STR );
          $stmt->bindParam( ':prefername2', $_POST["prefername2"], PDO::PARAM_STR );
          $stmt->bindParam( ':member_number2', $_POST["member_number2"], PDO::PARAM_INT );
          $stmt->bindParam( ':birthdate2', $_POST["birthdate2"], PDO::PARAM_STR );
          $stmt->bindParam( ':home_address2', $_POST["home_address2"], PDO::PARAM_STR );
          $stmt->bindParam( ':mobile2', $_POST["mobile2"], PDO::PARAM_STR );
          $stmt->bindParam( ':phone2', $_POST["phone2"], PDO::PARAM_STR );
          $stmt->bindParam( ':email2', $_POST["email2"], PDO::PARAM_STR );
          $stmt->bindParam( ':juniors', $_POST["juniors"], PDO::PARAM_STR );
          $stmt->bindParam( ':single_fee', $_POST["singleMemberOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':double_fee', $_POST["doubleMemberOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':junior_fee', $_POST["juniorMemberOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':student_id', $_POST["student_id"], PDO::PARAM_STR );
          $stmt->bindParam( ':student_fee', $_POST["studentMemberOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':retiredfee', $_POST["retiredMemberOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':renew_pool_key_fee', $_POST["poolKeyOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':new_pool_key_fee', $_POST["newPoolKeyOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':number_bands', $_POST["wristBandOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':bands_fee', $_POST["wristBandTotal"], PDO::PARAM_STR );
          $stmt->bindParam( ':renew_tennis_key_fee', $_POST["tennisKeyOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':new_tennis_key_fee', $_POST["newTennisKeyOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':discount', $_POST["discountOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':rcc_total_fee', $_POST["rcc_total_fee"], PDO::PARAM_STR );
          $stmt->bindParam( ':squash_competition_fee', $_POST["squashCompetitionOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':squash_social_fee', $_POST["squashSocialOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':squash_junior_fee', $_POST["squashJuniorOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':squash_summer_fee', $_POST["squashSummerOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':squash_access_card', $_POST["squashAccessOption"], PDO::PARAM_STR );
          $stmt->bindParam( ':squash_total_fee', $_POST["squash_total_fee"], PDO::PARAM_STR );
          $stmt->bindParam( ':eftpos', $_POST["eftpos"], PDO::PARAM_BOOL );
          $stmt->bindParam( ':banking_rcc', $banking_rcc, PDO::PARAM_BOOL );
          $stmt->bindParam( ':banking_squash', $banking_squash, PDO::PARAM_BOOL );
          $stmt->bindParam( ':applied', $now, PDO::PARAM_STR );
          $stmt->bindParam( ':paid', $paid, PDO::PARAM_BOOL );
          $stmt->bindParam( ':card_printed', $printed, PDO::PARAM_BOOL );

        $stmt->execute();

        ini_set("sendmail_from", "webmaster@ruakura-club.co.nz");
        mail("test@example.com","Membership renewal","This is a test email");
        return 'saved';
      } else {
        // Something is wrong with the sql statement, check to make sure table exists with all columns.
        return 'Could not prepare statement!';
      }
    }
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $result = new Members();
    echo $result->saveMember();
  }