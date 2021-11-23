<?php
  $singleMemberOptions = array("N/A"=>0, "1 year"=>45, "3 years"=>115, "5 years"=>185);
  $doubleMemberOptions = array("N/A"=>0, "1 year"=>65, "3 years"=>175, "5 years"=>275);
  $juniorMemberOptions = array("N/A"=>0, "1"=>5, "2"=>10, "3"=>15 , "4"=>20, "5"=>25 , "6"=>30 , "7"=>35 , "8"=>40, "9"=>45, "10"=>50);
  $studentMemberOptions = array("N/A"=>0, "Student"=>30);
  $retiredMemberOptions = array("N/A"=>0, "1"=>30, "2"=>60);
  $poolKeyOptions = array("N/A"=>0, "Pool Key Renewal & 1 Band"=>80);
  $newPoolKeyOptions = array("N/A"=>0, "New Pool Key & 1 Band"=>100);
  $wristBandFee = 30;
  $wristBandOptions = array("0"=>0, "1"=>1, "2"=>2, "3"=>3, "4"=>4, "5"=>5, "6"=>6, "7"=>7, "8"=>8, "9"=>9, "10"=>10);
  $tennisKeyOptions = array("N/A"=>0, "Tennis Key Renewal"=>20);
  $newTennisKeyOptions = array("N/A"=>0, "New Tennis Key"=>35);
  $discountOptions = array("N/A"=>0, "Discount"=>-5);
  $squashCompetitionOptions = array("N/A"=>0, "One Member"=>160, "Two Members"=>320);
  $squashSocialOptions = array("N/A"=>0, "One Member"=>120, "Two Members"=>240);
  $squashJuniorOptions = array("N/A"=>0, "One Junior"=>40, "Two Juniors"=>80, "Three Juniors"=>120, "Four Juniors"=>160);
  $squashSummerOptions = array("N/A"=>0, "One Member"=>70, "Two Members"=>140);
  $squashAccessOptions = array("N/A"=>0, "One Card"=>20, "Two Cards"=>40);
  
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = test_input($_POST["firstname"]);
    $lastname = test_input($_POST["lastname"]);
    $prefername = test_input($_POST["prefername"]);
    $member_number = test_input($_POST["member_number"]);
    $birthdate = test_input($_POST["birthdate"]);
    $home_address = test_input($_POST["home_address"]);
    $mobile = test_input($_POST["mobile"]);
    $phone = test_input($_POST["phone"]);
    $email = test_input($_POST["email"]);
    $firstname2 = test_input($_POST["firstname2"]);
    $lastname2 = test_input($_POST["lastname2"]);
    $prefername2 = test_input($_POST["prefername2"]);
    $member_number2 = test_input($_POST["member_number2"]);
    $birthdate2 = test_input($_POST["birthdate2"]);
    $home_address2 = test_input($_POST["home_address2"]);
    $mobile2 = test_input($_POST["mobile2"]);
    $phone2 = test_input($_POST["phone2"]);
    $email2 = test_input($_POST["email2"]);
    $juniors = test_input($_POST["juniors"]);
    
    $single_fee = test_input($_POST["singleMemberOption"]);
    $double_fee = test_input($_POST["doubleMemberOption"]);
    $junior_fee = test_input($_POST["juniorMemberOption"]);
    $student_id = test_input($_POST["student_id"]);
    $student_fee = test_input($_POST["studentMemberOption"]);
    $retiredfee = test_input($_POST["retiredMemberOption"]);
    $renew_pool_key_fee = test_input($_POST["poolKeyOption"]);
    $new_pool_key_fee = test_input($_POST["newPoolKeyOption"]);
    $number_bands = test_input($_POST["wristBandOption"]);
    $bands_fee = test_input($_POST["wristBandTotal"]);
    $renew_tennis_key_fee = test_input($_POST["tennisKeyOption"]);
    $new_tennis_key_fee = test_input($_POST["newTennisKeyOption"]);
    $discount = test_input($_POST["discountOption"]);
    $rcc_total_fee = test_input($_POST["rcc_total_fee"]);
    
    $squash_competition_fee = test_input($_POST["squashCompetitionOption"]);
    $squash_social_fee = test_input($_POST["squashSocialOption"]);
    $squash_junior_fee = test_input($_POST["squashJuniorOption"]);
    $squash_summer_fee = test_input($_POST["squashSummerOption"]);
    $squash_access_card = test_input($_POST["squashAccessOption"]);
    $squash_total_fee = test_input($_POST["squash_total_fee"]);
    
    $eftpos = test_input($_POST["eftpos"]);
    $banking_rcc = test_input($_POST["banking_rcc"]);
    $banking_squash = test_input($_POST["banking_squash"]);
    $applied = date("Y-m-d H:i:s");
    $paid = false;
    $card_printed = false;

    // Save to database .. do other actions, create confirmation and email it?
  }
  
  function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }
  
?>

<!DOCTYPE html>
<html>
  <head>
    <title>Ruakura Campus Club Inc.</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="main.css" />  
    <style type="text/css">
      .partnerDetails{ display: none; }
      .juniorDetails{ display: none; }
      .squashDetails{ display: none; }
      .squashPaymentDetails{ display: none; }
      </style>
  </head>
  <body>
    <div class="jumbotron text-center">
      <div class="container">
        <div class="row pb-2">
          <div class="col-md-4"><img src="Logo_600x524.png" width="150px" height="131px" alt="RCC Logo" class="mb-2"/></div>
          <div class="col-md-8 my-auto"><h2>Membership Renewal</h2></div>
        </div>
        <form action="../database/save_member.php" method="POST">
          <div class="form-group text-left border-bottom font-weight-bold">Personal Details</div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="firstname">First name</label>
              <input type="text" id="firstname" name="firstname" class="form-control" placeholder="First name" required />
            </div>
            <div class="form-group col-md-6">
              <label for="lastname">Last name</label>
              <input type="text" id="lastname" name="lastname" class="form-control" placeholder="Last name" required />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-4">
              <label for="prefername">Preferred name</label>
              <input type="text" id="prefername" name="prefername" class="form-control" placeholder="Preferred name" />
            </div>
            <div class="form-group col-md-4">
              <label for="member_number">RCC card number (if known)</label>
              <input type="number" id="member_number" name="member_number" class="form-control" placeholder="RCC Card Number (if known)" />
            </div>
            <div class="form-group col-md-4">
              <label for="birthdate">Date of Birth</label>
              <input type="date" id="birthdate" name="birthdate" class="form-control" required />
            </div>
          </div>
          <div class="form-group">
            <label for="home_address">Home address</label>
            <input type="text" class="form-control" id="home_address" name="home_address" placeholder="Home address">
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="mobile">Phone (mobile)</label>
              <input type="text" id="mobile" name="mobile" class="form-control" placeholder="Phone (mobile)" />
            </div>
            <div class="form-group col-md-6" >
              <label for="phone">Phone (home)</label>
              <input type="text" id="phone" name="phone" class="form-control" placeholder="Phone (home)" />
            </div>
          </div>
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Email Address" required />
          </div>
          <div class="form-group">
            <div class="form-check form-check-inline">
              <input class="form-check-input mr-2" type="checkbox" id="PartnerApplication" name="PartnerApplication">
              <label class="form-check-label" for="PartnerApplication">Joint Membership?</label>
            </div>
          </div>
          <div class="partnerDetails">
            <div class="form-group text-left border-bottom font-weight-bold">Partner Details</div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="firstname2">First name</label>
                <input type="text" id="firstname2" name="firstname2" class="form-control" placeholder="First name" />
              </div>
              <div class="form-group col-md-6">
                <label for="lastname2">Last name</label>
                <input type="text" id="lastname2" name="lastname2" class="form-control" placeholder="Last name" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="prefername2">Preferred name</label>
                <input type="text" id="prefername2" name="prefername2" class="form-control" placeholder="Preferred name" />
              </div>
              <div class="form-group col-md-4">
                <label for="member_number2">RCC card number (if known)</label>
                <input type="number" id="member_number2" name="member_number2" class="form-control" placeholder="RCC Card Number (if known)" />
              </div>
              <div class="form-group col-md-4">
                <label for="birthdate2">Date of Birth</label>
                <input type="date" id="birthdate2" name="birthdate2" class="form-control" />
              </div>
            </div>
            <div class="form-group">
              <label for="home_address2">Home address</label>
              <input type="text" class="form-control" id="home_address2" name="home_address2" placeholder="Home address">
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="mobile2">Phone (mobile)</label>
                <input type="text" id="mobile2" name="mobile2" class="form-control" placeholder="Phone (mobile)" />
              </div>
              <div class="form-group col-md-6" >
                <label for="phone2">Phone (home)</label>
                <input type="text" id="phone2" name="phone2" class="form-control" placeholder="Phone (home)" />
              </div>
            </div>
            <div class="form-group">
              <label for="email2">Email Address</label>
              <input type="email" id="email2" name="email2" class="form-control" placeholder="Email Address" />
            </div>
          </div>
          <div class="form-group">
            <div class="form-check form-check-inline">
              <input class="form-check-input mr-2" type="checkbox" id="juniorMembers" name="juniorMembers">
              <label class="form-check-label" for="juniorMembers">
                Junior Members? (under 18 years)
              </label>
            </div>
          </div>
          <div class="juniorDetails">
            <div class="form-row">
              <div class="form-group col-md-12">
                <textarea id="juniors" name="juniors" class="form-control" placeholder="Junior member names and ages" rows="3"></textarea>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-row mb-2 border-bottom">
              <div class="col-md-3 font-weight-bold text-left">Ruakura Club Membership</div>
              <div class="col-md-2">1 year</div>
              <div class="col-md-2">3 years</div>
              <div class="col-md-2">5 years</div>
              <div class="col-md-3">$ inc GST</div>
            </div>
            <div class="form-row mb-2" id="singleMemberOption">
              <div class="col-md-3">Single Member</div>
              <?php foreach(array_slice($singleMemberOptions,1) as $key=>$value){
                ?><div class="col-md-2">$<?php echo $value; ?></div>
              <?php }; ?>
              <div class="col-md-3">
                <select name="singleMemberOption" class="form-control text-right rccOptions">
                  <?php foreach($singleMemberOptions as $key=>$value){
                    ?><option name="singleFee" value="<?php echo $value; ?>"><?php echo $key .' ($' . number_format($value,2) .')'; ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2 doubleMemberOption">
              <div class="col-md-3">Double Member</div>
              <?php foreach(array_slice($doubleMemberOptions,1) as $key=>$value){
                ?><div class="col-md-2">$<?php echo $value; ?></div>
              <?php }; ?>
              <div class="col-md-3">
                <select name="doubleMemberOption" class="form-control text-right rccOptions">
                <?php foreach($doubleMemberOptions as $key=>$value){
                  ?><option name="doubleFee" value="<?php echo $value; ?>"><?php echo $key .' ($' . number_format($value,2) .')'; ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2 juniorOption">
              <div class="col-md-3">Junior (under 18 years old)</div>
              <div class="col-md-2">$<?php echo $juniorMemberOptions['1']; ?></div>
              <div class="col-md-2"></div>
              <div class="col-md-2"></div>
              <div class="col-md-3">
                <select name="juniorMemberOption" class="form-control text-right rccOptions">
                <?php foreach($juniorMemberOptions as $key=>$value){
                  ?><option name="juniorFee" value="<?php echo $value; ?>"><?php echo $key .' ($' . number_format($value,2) . ')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">Student</div>
              <div class="col-md-2">$<?php echo $studentMemberOptions['Student']; ?></div>
              <div class="col-md-4">
                <input type="text" id="student_id" name="student_id" class="form-control" placeholder="Uni/Tech name & ID no." />
              </div>
              <div class="col-md-3">
                <select name="studentMemberOption" class="form-control text-right rccOptions">
                <?php foreach($studentMemberOptions as $key=>$value){
                  ?><option name="studentFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) . ')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">Retired</div>
              <div class="col-md-2">$<?php echo $retiredMemberOptions['1']; ?></div>
              <div class="col-md-4"></div>
              <div class="col-md-3">
                <select name="retiredMemberOption" class="form-control text-right rccOptions">
                <?php foreach($retiredMemberOptions as $key=>$value){
                  ?><option name="retiredFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) . ')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-row border-bottom mb-2">
              <div class="col-md-12 font-weight-bold text-left">Swimming Pool (must be a current member of Ruakura Club)</div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">Pool key (renewal)</div>
              <div class="col-md-2">$<?php echo $poolKeyOptions['Pool Key Renewal & 1 Band']; ?></div>
              <div class="col-md-2"></div>
              <div class="col-md-2"></div>
              <div class="col-md-3">
                <select name="poolKeyOption" class="form-control text-right rccOptions">
                  <?php foreach($poolKeyOptions as $key=>$value){
                  ?><option name="poolKeyFee" value="<?php echo $value; ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">New pool key (inc. deposit)</div>
              <div class="col-md-2">$<?php echo $newPoolKeyOptions['New Pool Key & 1 Band']; ?></div>
              <div class="col-md-2"></div>
              <div class="col-md-2"></div>
              <div class="col-md-3">
                <select name="newPoolKeyOption" class="form-control text-right rccOptions">
                  <?php foreach($newPoolKeyOptions as $key=>$value) {
                  ?><option name="newPoolKeyFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">Extra pool wristbands</div>
              <div class="col-md-2">$<?php echo $wristBandFee; ?></div>
              <div class="col-md-2"></div>
              <div class="col-md-2">
                <select name="wristBandOption" class="form-control text-right rccOptions">
                  <?php foreach($wristBandOptions as $key=>$value) {
                  ?><option name="wristbandNumber" value="<?php echo $value; ?>"><?php echo $key; ?></option>
                  <?php }; ?>
                </select>
              </div>
              <div class="col-md-3">
                <input type="hidden" id="wristBandFee" name="wristBandFee" value="<?php echo $wristBandFee ?>">
                <div class="form-control text-right" id="wristBandTotal" name="wristBandTotal">$0.00</div>
              </div>
            </div>
            <div class="font-italic">
              <strong>NOTE:</strong> Key purchase includes <strong>ONE</strong> wristband and key holders will be required to read and sign a copy of the pool rules before receiving their key.
            </div>
          </div>

          <div class="form-group border-bottom">
            <div class="form-row border-bottom mb-2">
              <div class="col-md-12 font-weight-bold text-left">Tennis (must be a current member of Ruakura Club)</div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">Tennis key (renewal)</div>
              <div class="col-md-2">$<?php echo $tennisKeyOptions['Tennis Key Renewal']; ?></div>
              <div class="col-md-2"></div>
              <div class="col-md-2"></div>
              <div class="col-md-3">
                <select name="tennisKeyOption" class="form-control text-right rccOptions">
                  <?php foreach($tennisKeyOptions as $key=>$value) {
                  ?><option name="tennisKeyFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="col-md-3">New tennis key (inc. deposit)</div>
              <div class="col-md-2">$<?php echo $newTennisKeyOptions['New Tennis Key']; ?></div>
              <div class="col-md-4">NB: Pool & Tennis Fees are payable to the RCC</div>
              <div class="col-md-3">
                <select name="newTennisKeyOption" class="form-control text-right rccOptions">
                  <?php foreach($newTennisKeyOptions as $key=>$value) {
                  ?><option name="newTennisKeyFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
            <div class="font-italic text-left">
              NOTE: Lost Tennis or pool keys can be replaced at a cost of $15 each.
            </div>
          </div>

          <div class="form-group">
            <div class="form-row mb-2">
              <div class="col-md-9">Discount for EXISTING members if the RCC subscription is received BEFORE 17 December 2021 ($<?php echo $discountOptions['Discount']; ?>)</div>
              <div class="col-md-3">
                <select name="discountOption" class="form-control text-right rccOptions">
                  <?php foreach($discountOptions as $key=>$value) {
                  ?><option name="discountFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                  <?php }; ?>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-row mb-2">
              <div class="col-md-9 font-weight-bold text-right">Total amount payable to RCC</div>
              <div class="col-md-3">
                <input type="hidden" id="rcc_total_input" name="rcc_total_input" value=0 />
                <div class="form-control text-right" id="rcc_total_fee" name="rcc_total_fee">$0.00</div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-check form-check-inline">
              <input class="form-check-input mr-2" type="checkbox" id="squashMember" name="squashMember">
              <label class="form-check-label" for="squashMember">
                Squash Club Membership?
              </label>
            </div>
          </div>
          <div class="squashDetails">
            <div class="form-group">
              <div class="form-row border-bottom mb-2">
               <div class="col-md-12 text-left"><strong>Ruakura Squash Club Subscription Period: </strong>1 April 2022 - 31 March 2023</div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-3">Full member - Competition</div>
                <div class="col-md-2">$<?php echo $squashCompetitionOptions['One Member']; ?></div>
                <div class="col-md-4"></div>
                <div class="col-md-3">
                  <select name="squashCompetitionOption" class="form-control text-right rccOptions">
                    <?php foreach($squashCompetitionOptions as $key=>$value) {
                    ?><option name="squashCompetitionFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                    <?php }; ?>
                  </select>
                </div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-3">Full member - Social</div>
                <div class="col-md-2">$<?php echo $squashSocialOptions['One Member']; ?></div>
                <div class="col-md-4"></div>
                <div class="col-md-3">
                  <select name="squashSocialOption" class="form-control text-right rccOptions">
                    <?php foreach($squashSocialOptions as $key=>$value) {
                    ?><option name="squashSocialFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                    <?php }; ?>
                  </select>
                </div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-3">Junior member</div>
                <div class="col-md-2">$<?php echo $squashJuniorOptions['One Junior']; ?></div>
                <div class="col-md-4"></div>
                <div class="col-md-3">
                  <select name="squashJuniorOption" class="form-control text-right rccOptions">
                    <?php foreach($squashJuniorOptions as $key=>$value) {
                    ?><option name="squashJuniorFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                    <?php }; ?>
                  </select>
                </div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-3">Summer membership</div>
                <div class="col-md-2">$<?php echo $squashSummerOptions['One Member']; ?></div>
                <div class="col-md-4"></div>
                <div class="col-md-3">
                  <select name="squashSummerOption" class="form-control text-right rccOptions">
                    <?php foreach($squashSummerOptions as $key=>$value) {
                    ?><option name="squashSummerFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                    <?php }; ?>
                  </select>
                </div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-3">Access card*</div>
                <div class="col-md-2">$<?php echo $squashAccessOptions['One Card']; ?></div>
                <div class="col-md-4"></div>
                <div class="col-md-3">
                  <select name="squashAccessOption" class="form-control text-right rccOptions">
                    <?php foreach($squashAccessOptions as $key=>$value) {
                    ?><option name="squashAccessFee" value="<?php echo $value ?>"><?php echo $key .' ($' . number_format($value,2) .')' ?></option>
                    <?php }; ?>
                  </select>
                </div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-9 text-left font-italic small">
                  *This card permits access to the squash courts and through the Barrier arm on the Ruakura Rd entrance. The card is
                  for your personal use only and must not be transferred to others. The card is for use only for access to legitimate RCC
                  events and not to enable use of the Ruakura campus as a thoroughfare. You must ensure that the cards are not
                  misused, loaned to other people etc. and, if lost, stolen or damaged, you must report that to the Ruakura Squash
                  Membership Convenor so that the card can be disabled immediately.
                </div>
              </div>
              <div class="form-row mb-2">
                <div class="col-md-4 text-left">
                  <strong>Squash Convenor: </strong><a href="mailto:squashruakura@gmail.com">squashruakura@gmail.com</a>
                </div>
                <div class="col-md-5 text-right font-weight-bold">Total amount payable to Ruakura Squash Club</div>
                <div class="col-md-3">
                  <input type="hidden" id="squash_total_input" name="squash_total_input" value=0.00 />
                  <div class="form-control text-right" id="squash_total_fee" name="squash_total_fee">$0.00</div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="form-row border-bottom mb-2 text-left font-weight-bold">Payment Methods</div>
            <div class="form-row mb-2">
              <div class="form-check form-check-inline col-md-12 mr-0">
                <input type="checkbox" class="form-check-input mr-2" id="eftpos" name="eftpos" />
                <label for="eftpos" class="form-check-label">
                  EFT-POS (Payable at the Campus Club Bar) -- $<span id="payableEftpos">0.00</span>
                </label>
              </div>
            </div>
            <div class="form-row mb-2">
              <div class="form-check-inline col-md-12 mr-0">
                <input type="checkbox" class="form-check-input mr-2" id="banking_rcc" name="banking_rcc" />
                <label for="banking_rcc" class="form-check-label">Internet Banking RCC Sub -- Ruakura Campus Club -- 02-0342-0016379-00 -- $<span id="payableRCC">0.00</span> *</label>
              </div>
            </div>
          </div>
          <div class="form-group squashPaymentDetails">
            <div class="form-row mb-2">
              <div class="form-check-inline col-md-12 mr-0">
                <input type="checkbox" class="form-check-input mr-2" id="banking_squash" name="banking_squash" />
                <label for="banking_squash" class="form-check-label">Internet Banking Squash Sub -- Ruakura Squash Club -- 02-0342-0016379-06 -- $<span id="payableSquash">0.00</span> *</label>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="form-row align-left font-italic mb-2">
              * Please include your membership number or name, and type of membership
            </div>
          </div>

          <button type="submit" class="btn btn-primary border-style">Submit application (will open a filled PDF for your records)</button>
        </form>
      </div>
    </div>
    <script type="text/javascript">
      $(document).ready(function(){
        $("#PartnerApplication").on('change',function(){
          let selectedValue = $("#PartnerApplication:checked").val();
          if (selectedValue) {
            $(".partnerDetails").slideDown(500);
            jQuery($("#firstname2")).attr('required','');
            jQuery($("#lastname2")).attr('required','');
            jQuery($("#birthdate2")).attr('required','');
            jQuery($("#email2")).attr('required','');
            jQuery($("#doubleFee")).attr('required','');
          }else{
            $(".partnerDetails").slideUp(500);
            jQuery($("#firstname2")).removeAttr('required');
            jQuery($("#lastname2")).removeAttr('required');
            jQuery($("#birthdate2")).removeAttr('required');
            jQuery($("#email2")).removeAttr('required');
            jQuery($("#doubleFee")).removeAttr('required');
          }
        });
        $("#juniorMembers").on('change',function(){
          let selectedValue = $("#juniorMembers:checked").val();
          if (selectedValue) {
            $(".juniorDetails").slideDown(500);
            jQuery($("#juniors")).attr('required','');
            jQuery($("#juniorFee")).attr('required','');
          }else{
            $(".juniorDetails").slideUp(500);
            jQuery($("#juniors")).removeAttr('required');
            jQuery($("#juniorFee")).removeAttr('required');
          }
        });
        $("#squashMember").on('change',function(){
          let selectedValue = $("#squashMember:checked").val();
          if (selectedValue) {
            $(".squashDetails").slideDown(500);
            $(".squashPaymentDetails").slideDown(500);
          }else{
            $(".squashDetails").slideUp(500);
            $(".squashPaymentDetails").slideUp(500);
          }
        });

        $('select').on('change',function(){
          let values = $( ":input" ).serializeArray();
          let rccTotal = 
            Number( values.find( ({ name }) => name === 'singleMemberOption').value )
            + Number( values.find( ({ name }) => name === 'doubleMemberOption').value )
            + Number( values.find( ({ name }) => name === 'juniorMemberOption').value )
            + Number( values.find( ({ name }) => name === 'studentMemberOption').value )
            + Number( values.find( ({ name }) => name === 'retiredMemberOption').value )
            + Number( values.find( ({ name }) => name === 'poolKeyOption').value )
            + Number( values.find( ({ name }) => name === 'newPoolKeyOption').value )
            + Number( values.find( ({ name }) => name === 'wristBandOption').value * 30 )
            + Number( values.find( ({ name }) => name === 'tennisKeyOption').value )
            + Number( values.find( ({ name }) => name === 'newTennisKeyOption').value )
            + Number( values.find( ({ name }) => name === 'discountOption').value );
          document.getElementById('wristBandTotal').innerHTML = '$' + Number( values.find( ({ name }) => name === 'wristBandOption').value * 30 ).toFixed(2);
          document.getElementById('rcc_total_fee').innerHTML = '$' + rccTotal.toFixed(2);
          document.getElementById('rcc_total_input').value = rccTotal;

          let squashTotal =
            Number( values.find( ({ name }) => name === 'squashCompetitionOption').value )
            + Number( values.find( ({ name }) => name === 'squashSocialOption').value )
            + Number( values.find( ({ name }) => name === 'squashJuniorOption').value )
            + Number( values.find( ({ name }) => name === 'squashSummerOption').value )
            + Number( values.find( ({ name }) => name === 'squashAccessOption').value );
          document.getElementById('squash_total_fee').innerHTML = '$' + squashTotal.toFixed(2);
          document.getElementById('squash_total_input').value = squashTotal;

          document.getElementById('payableEftpos').innerHTML = (rccTotal+squashTotal).toFixed(2);
          document.getElementById('payableRCC').innerHTML = rccTotal.toFixed(2);
          document.getElementById('payableSquash').innerHTML = squashTotal.toFixed(2);
        });
      });
      </script>

  </body>
</html>
