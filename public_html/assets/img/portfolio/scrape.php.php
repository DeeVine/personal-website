<?php
require_once("seleniumhelper.php");
	require_once ("call.php");
class 
ScrapeMagnaCare extends SeleniumHelper
{
	protected $browser="*firefox";
	protected $url="https://clm.magnacare.com/MGProviderclms/Login.aspx";
    protected $mailTo = "suvarna@advanceresponse.com";
	protected $mailFrom = "From: ARSystem@advanceresponse.com";

//====================================
	// accessing credentials from the database
	// opening URL 
	// making sure the site opens
	// if site opens 
	//	Action: check username and password in database web_credential table status 
	//			if status is new or working 
	//			Action:  enter user name and password
	//					if cusername and password work
	//					Action: go to scrape_claim and start get_claims_dumps
	//				else
	//					email error message set status in web_credential table to invalid exit
	//			else
	//			Action: error email that credentials are invalid exit
	// else
	//	email error message and dump page link, return false
	//======================================
	/*Open URL https://clm.magnacare.com/MGProviderclms/Login.aspx  
	 
			Multi_text look for text Provider Login
			If text equals Provider Login 
				Action type  username 
				Action type password
				Action click login
Multi_text “Time is money and good health is priceless” and “Invalid UserID or Password.”
If text equals is money and good health is priceless.
Action call scrape_function 
				Else if text is Invalid UserID or Password.
					Action update the webcredential table status invalid
					Action exit
				Else 
					unexpected text on page 
Action update the webcredential table status invalid
					Action exit
			Else 
				Not on log in page
				Exit try again
		Else 
			URL page did not load exit scrape
*/
	
public function initialize($credentials, $claim){
	$this->trace("I", "\n in initialize\n");
	$app="Magnacare_OpenURL";
	$this->arOpen("https://clm.magnacare.com/MGProviderclms/Login.aspx",$app);
	$textOnPage = $this->multi_text_onpage(1, 20, "Provider Login", false);
	if($textOnPage== "Provider Login"){
		//$File="Url_Page".$this->getFileName();
		//$this->dumpPage($File);
		$this->trace( "\n opened url\n");
		$username = $credentials['username'];
		$password = $credentials['password'];
			//Check status for credentials
		$credentials_status = $this->checkCredentials($username, $password, $claim);
		if($credentials_status == false)
		{
			return false; //web credentials
		}
//----------------------------check for the precert ----------------------
		$text = $this->search_info;
		$text_array = explode('|',$text);
		
		if (isset($text_array[4]) && !empty($text_array[4]))
		{
			$this->providerTaxId=$text_array[4];
			$this->trace("I","====provider tax id is $this->providerTaxId====");
		}
		if (isset($text_array[5]) && !empty($text_array[5]))
		{
			$this->taxidforDropDown=$text_array[5];
			$this->trace("I","====tax id is $this->taxidforDropDown====");
		}
		//USE 6th slot in text in web_credential table for precertCheck flag (precertYes/precertNo)
		if (isset($text_array[6]) && !empty($text_array[6]))
		{
			$this->precertCheck = $text_array[6];
			$this->trace("I","=====precert check is $this->precertCheck=====");
		}	

//-------------------------------------------------------------------------------
		
		$this->trace("I", "\nEntering the login credentials\n");
		$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLogin", "$username", true, 30, "Username Field", true);
		$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtPassword","$password", true, 30, "Password Field", true);
		$this->trace("I", "\nClicking on the Login Submit button");
		$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnLogin", 30, true, "Login Button");
		$afterLoginPage = $this->multi_text_onpage(1, 20, "Time is money and good health is priceless::Invalid UserID or Password", false);
		if($afterLoginPage=="Time is money and good health is priceless"){
			$this->trace("I", "\n made past login\n");
			$File="afterLogin".$this->getFileName();
			$this->dumpPage($File);
		}
		else if($afterLoginPage=="Invalid UserID or Password"){
			$this->trace("I", "\n did not past login\n");
			$File="errorLogin".$this->getFileName();
			$this->dumpPage($File);
			$this->trace("E", "Invaild web credientials");
			$this->invalidWebCredentials($username, $password, $claim);
		}
		else{
			$this->trace("I", "\n unexpected text on page\n");
			$this->trace("I", "\npractice code\n");
			$WhereTextWasFound = "afterLogin";
			$this->UnexpectedText($WhereTextWasFound);
		}
	}
	else{
		$this->trace("I", "\n couldn't open url\n");
		return false;
	}
	$File="Url_Page".$this->getFileName();
	$this->dumpPage($File);		
}
	
	
	
//=====================================	
//checking if claims, elligibility and precert ran past 24hrs and tries are less the 6 then go to the functions to update time start and end
//======================================	
public function scrapeClaim($claim)
	
	{
		$this->trace("","In scrapeClaim function, and will find out which function to scrape Claims or Elig.");
		$checkClaims = $this->checkClaimRun24($claim);
		if ($checkClaims == true)
		{
			if ($this->check_scrape_tries($claim['id'])<=5) {
				$this->get_Claims_Only($claim);
			}
			else {
				$this->interaction_counter = 0; //sets counter for number of actions
				$this->num_searches = 0;
				$this->load_time_counter = 0;
				$this->currentTime = date('Y-m-d H:i:s');
				$currentStartDate=$this->currentTime;
				$this->currentTime = date('Y-m-d H:i:s');
				$currentEndDate=$this->currentTime;
				$this->updateClaimsField("num_records","0");
				$this->update_claim_field_reports($claim,$currentStartDate,$currentEndDate);
			}
		}
		
		$checkEligibility = $this->checkEligibilityRun24($claim);
		if ($checkEligibility == true)
		{
			if ($this->check_scrape_tries_elig($claim['id'])<=5) {
				$this->get_Eligibility_Only($claim);
			}
			else {
				$this->interaction_counter = 0; //sets counter for number of actions
				$this->num_searches = 0;
				$this->load_time_counter = 0;
				$this->currentTime = date('Y-m-d H:i:s');
				$currentStartDate=$this->currentTime;
				$this->currentTime = date('Y-m-d H:i:s');
				$currentEndDate=$this->currentTime;
				$this->updateClaimsField("eligibility_status","eligibility not found");
				$this->update_eligibility_field_reports($claim,$currentStartDate,$currentEndDate);
			}
			
		}
		//get precert
		//checking if in web_credential table if column test has PrecertYess if not precert will not run
			
		if ($this->precertCheck == "PrecertYes") {
			$this->trace("I", "precertCheck is 'PrecertYes'");
			if ($this->checkPrecertRun24($claim)) {
				if ($this->check_scrape_tries($claim['id'])<=5) {
					$this->trace("I", "Going to run Precertification search");
					$this->get_Precert_Only($claim);
				}
				else {
					$this->updateClaimsField("precert_status","0");
					$this->trace("I", "Scrape has been tried more than 5 times. Not going to run.");
				}	
			}
		}
		else {
			$this->trace("I", "precertCheck is $this->precertCheck, which is not equal to 'PrecertYes'.");
			$this->trace("I", "Precertification search not going to run");
		}
//=====================================================
//ask Radha if this is code needed to zip log files	
//=====================================================	
	//Insert code here to zip all the files
	/*if($this->host_name!="dev.advanceresponse.com") {
		$the_path="/var/www/advanceresponse/www/logs_dumps/".$this->environment;
		$zip_name = $this->claimId."_".$this->payer_name."_".$this->current_time.".zip";
		//system("mkdir -p $the_path");
		system("cd $the_path; zip -r $zip_name *.html; rm *.html");
	}
	else {*/
	/*	$the_path=$this->dir."/temp/".$this->environment;
		$zip_name = $this->claimId."_".$this->payer_name."_".$this->current_time.".zip";
		//system("mkdir -p $the_path");
		system("cd $the_path; zip -r $zip_name *.html; rm *.html");
		
		$the_pemPath = $this->dir."/selenium_keypair.pem";
		$remote_server = "craig@ec2-54-214-155-162.us-west-2.compute.amazonaws.com";
		$the_dumpPath = "/var/www/advanceresponse/www/logs_dumps_2/".$this->environment;
		//echo $the_host;
		shell_exec("ssh -o StrictHostKeyChecking=no -i $the_pemPath $remote_server mkdir -p $the_dumpPath");
		$dest_file = $the_path."/".$zip_name;
		shell_exec("scp -o StrictHostKeyChecking=no -i $the_pemPath  $dest_file $remote_server:$the_dumpPath");
	//}*/
	
	}
	
	
//============================================================
//Function for getting Claims, Elig. and precert
//With Start and End Messages and unique identifier
//This is to find if claims/elig./precert started and finished in logs
//============================================================
public function get_Claims_Only($claim)
	{
	//Claims Start Message with Date and Time stamp";
	$fiveDigitString=$this->random();		
	$this->currentTime = date('Y-m-d H:i:s');
	$currentDate=$this->currentTime;		
	$this->trace("I","Start_Claims_".$claim['id']."_".$fiveDigitString);
	$this->updateClaimsField("claim_start_time", "$currentDate");
	$this->get_claim_dump($claim);
	//Claims End Message with Date and Time stamp";
	$this->currentTime = date('Y-m-d H:i:s');
	$currentDate=$this->currentTime;
	$this->trace("I","End_Claims_".$claim['id']."_".$fiveDigitString);
	$this->updateClaimsField("claim_end_time", "$currentDate");
	return false;
	}
public function get_Eligibility_Only($claim)
	{
	//Eligibility Start Message with Date and Time stamp";
	$fiveDigitString=$this->random();
	$this->currentTime = date('Y-m-d H:i:s');
	$currentDate=$this->currentTime;
	$this->trace("I","Start_Eligibility_".$claim['id']."_".$fiveDigitString);
	$this->updateClaimsField("eligibility_start_time", "$currentDate");
	$this->get_eligibility_dump($claim);
	//Claims End Message with Date and Time stamp";
	$this->currentTime = date('Y-m-d H:i:s');
	$currentDate=$this->currentTime;
	$this->trace("I","End_Eligibility_".$claim['id']."_".$fiveDigitString);
	$this->updateClaimsField("eligibility_end_time", "$currentDate");
	}
public function get_Precert_Only($claim)
	{
	//Precert Start Message with Date and Time stamp";
	$fiveDigitString=$this->random();
	$this->currentTime = date('Y-m-d H:i:s');
	$currentDate=$this->currentTime;
	$this->trace("I","Start_Precert_".$claim['id']."_".$fiveDigitString);
	$this->updateClaimsField("precert_start_time", "$currentDate");
	$this->get_precert_dump($claim);
	//Claims End Message with Date and Time stamp";
	$this->currentTime = date('Y-m-d H:i:s');
	$currentDate=$this->currentTime;
	$this->trace("I","End_Eligibility_".$claim['id']."_".$fiveDigitString);
	$this->updateClaimsField("precert_end_time", "$currentDate");
	}
//=============================================
//make comments here how the code should work in english here before writing the code
//keep in mind you want a clicktimmer or multi_text_onpage when ever going to a new page
//when starting put a lot of dumps and traces 
//when ending only have the dumps that are used for the parser in the scrape
//=============================================
/*
Claims Search()
o	Click on Claims/RA
o	Call claims_search_Policy#,DOS()
o	If return = true
?	call claims_found()
o	else
•	call claims_search_LastName_FirstName,DOS () 
•	if return = true
o	call claims_found()
•	else
?	call claims_search_initials_DOS ()
?	if return = true
•	call claims_found()
?	else
•	call claims_search_Initials_total charges() 
•	if return = true
o	call claims_found()
?	else
•	call no_claims_found()
*/




public function get_claim_dump($claim)
	{
	 global $claim_found;
	$this->trace("I","\nin get_claim_dump function");
	$this->arClick("id=ctl00_hlnkClaims", 30, true, "Claims/RA button");
	$textOnPage = $this->multi_text_onpage(1, 20, "First Name", false);	
		if($textOnPage == "First Name"){
			$this->trace("I","\nOn claims page.");
			$this->claims_search_Policy_DOS($claim);
			if($claim_found == false){
				$this->claims_search_LastName_FirstName_DOS($claim);
			}
			if($claim_found == false){
				$this->claims_search_Initials_DOS($claim);
			}
			if($claim_found == false){
				$this->arClick("id=ctl00_hlnkClaims", 30, true, "Claims/RA button");
				sleep(10);
				$this->claims_search_Initials_Charges($claim);
			}
		}
		else{
			$this->trace("I","\nCouldn't open claims page");
			$Claims = "Warning_";
			$subject = "$could_not_open_claims_page";
			$this->errorEmail ($Claims,$subject);
			return false;			
		}
	}
/*1.	Function – claims_search_Policy #()
a.	Policy#
b.	DOS
i.	Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2;
*/
	
public function claims_search_Policy_DOS($claim){
	global $claim_found; //not sure if we need to declare this global
	$this->trace("I","\nin claims_search_Policy_DOS search");
	$insured_id = $claim['insured_id'];
	$starting_date_of_service = trim($claim['starting_date_of_service']);
	$startingDOS = $this->arDateDashestoSlashes($starting_date_of_service); 
	$this->trace("I","\n$insured_id $startingDOS");
	$this->trace("I","\ntyping PolicyID, DOS from");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtPolicyID", $insured_id, true, 30, "Policy Number", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtDOS_From",$startingDOS, true, 30, "DOS From", true);
	//$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtDOS_From","", true, 30, "DOS From", true);
	$this->trace("I", "\nClicking on the search button");	
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);
	$claim_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div/div[2]/div[1]/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[1]/th[5]/a","Provider Name");
	$this->trace("I", "\nclaim found is $claim_found");
	if ($claim_found=="Provider Name"){
		$this->trace("I", "\nFound Claim");	
		$this->arClick("//body/form/div[4]/div/div/div/div[2]/div/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[2]/td[7]", 30, true, "Search Button");
		sleep(5);
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo claims found.");
		return $claim_found = false;
	}
	
}

/*2.	Function – claims_search_LastName_FirstName,DOS()
a.	Last Name
b.	First Name
c.	DOS
i.	Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2
*/

public function claims_search_LastName_FirstName_DOS($claim){
	$this->trace("I","\nin LastName_FirstName_DOS search\n");
	$last_name = $claim['patient_last_name'];
	$first_name = $claim['patient_first_name'];
	$starting_date_of_service = trim($claim['starting_date_of_service']);
	$startingDOS = $this->arDateDashestoSlashes($starting_date_of_service); 
	$this->trace("I","\n$last_name $first_name $startingDOS");
	$this->trace("I","\ntyping last name, first name, DOS from");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLastName", $last_name, true, 30, "Last Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtFirstName", $first_name, true, 30, "First Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtDOS_From",$startingDOS, true, 30, "DOS From", true);
	//$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtDOS_From","", true, 30, "DOS From", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);
	$claim_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div/div[2]/div[1]/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[1]/th[5]/a","Provider Name");
	$this->trace("I", "\nclaim found is $claim_found");
	if ($claim_found=="Provider Name"){
		$this->trace("I", "\nFound Claim");	
		$this->arClick("//body/form/div[4]/div/div/div/div[2]/div/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[2]/td[7]", 30, true, "Search Button");
		sleep(5);
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo claims fonds.");
		return $claim_found = false;
	}
	
}
/*
3.	Function – claims_search_initials_DOS ()
a.	First Initial
b.	Last Initial
c.	DOS

Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2;
*/
	
public function claims_search_Initials_DOS($claim){
	$this->trace("I","\nin Initials_DOS search\n");
	$last_name = $claim['patient_last_name'];
	$last_initial = substr($last_name,0,1);
	$first_name = $claim['patient_first_name'];
	$first_initial = substr($first_name,0,1);
	$starting_date_of_service = trim($claim['starting_date_of_service']);
	$startingDOS = $this->arDateDashestoSlashes($starting_date_of_service); 
	$this->trace("I","\n$last_initial $first_initial $startingDOS");
	$this->trace("I","\ntyping last initial, first initial, DOS from");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLastName", $last_initial, true, 30, "Last Initial", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtFirstName", $first_initial, true, 30, "First Initial", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtDOS_From",$startingDOS, true, 30, "DOS From", true);
	//$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtDOS_From","", true, 30, "DOS From", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);
	$claim_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div/div[2]/div[1]/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[1]/th[5]/a","Provider Name");
	$this->trace("I", "\nclaim found is $claim_found");
	if ($claim_found=="Provider Name"){
		$this->trace("I", "\nFound Claim");	
		$this->arClick("//body/form/div[4]/div/div/div/div[2]/div/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[2]/td[7]", 30, true, "Search Button");
		sleep(5);
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo claims fonds.");
		return $claim_found = false;
	}	
}
/*
4.	Function – claims_search_ Initials_total charges()
a.	Initials
b.	Total Charges

Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2;
*/
public function claims_search_Initials_Charges($claim){
	$this->trace("I","\nin Initials_Charges search\n");
	$last_name = $claim['patient_last_name'];
	$last_initial = substr($last_name,0,1);
	$first_name = $claim['patient_first_name'];
	$first_initial = substr($first_name,0,1);
	$charges = $claim['amount']; 
	$this->trace("I","\n$last_initial $first_initial $charges");
	$this->trace("I","\ntyping last initial, first initial, total charges");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLastName", $last_initial, true, 30, "Last Initial", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtFirstName", $first_initial, true, 30, "First Initial", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtTotChargesFrom",$charges, true, 30, "Total Charges", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);
	$claim_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div/div[2]/div[1]/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[1]/th[5]/a","Provider Name");
	$this->trace("I", "\nclaim found is $claim_found");
	if ($claim_found=="Provider Name"){
		$this->trace("I", "\nFound Claim");	
		$this->arClick("//body/form/div[4]/div/div/div/div[2]/div/table/tbody/tr[4]/td[2]/div/div/table/tbody/tr[2]/td[7]", 30, true, "Search Button");
		sleep(5);
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo claims fonds.");
		return $claim_found = false;
	}	
}
	
//=============================================
//make comments here how the code should work in english here before writing the code
//keep in mind you want a clicktimmer or multi_text_onpage when ever going to a new page
//when starting put a lot of dumps and traces 
//when ending only have the dumps that are used for the parser in the scrape
//=============================================

function get_eligibility_dump($claim)
	{
		$this->trace("I","\nin get_eligibility_dump function yeah!!!!");
		$this->arClick("id=ctl00_hlnkASCElig", 15, true, "Eligibility button");
		$textOnPage = $this->multi_text_onpage(1, 20, "Policy ID", false);	
			if($textOnPage == "Policy ID"){
				$this->trace("I","\nOn Eligibility page.");
				$this->eligibility_search_policyid($claim);
					
		}
		else{
			$this->trace("I","\nCouldn't open Eligibility page");
			$Claims = "Warning_";
			$subject = "$could_not_open_eligibility_page";
			$this->errorEmail ($Claims,$subject);
			return false;			
		}
	}

	/*1.	Function – eligibility _search_Policy #()
	a.	Use policy # from website
	b.	Use Policy# from database if didn’t find on website
	i.	Call function multi_text()
	1.	If return false
	a.	Continue loop
	2.	else
	a.	return true
	b.	break 2;
	*/

public function eligibility_search_policyid($claim){
	$this->trace("I","\nin eligibility_search_policyid\n");
	$policy_id = $claim['insured_id'];
	$this->trace("I","\n$policy_id");
	$this->trace("I","\ntyping policyid");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtPolicyID", "$policy_id", true, 30, "Policy ID", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnApplyFilter", 30, true, "Search Button");
	$eligibility_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div[2]/div/div[3]/div/div/table/tbody/tr[2]/td[2]/div/table/tbody/tr[1]/th[6]/a","Policy ID");
	$this->trace("I", "\neligibility found is $eligibility_found");
	if ($eligibility_found=="Policy ID"){
		$this->trace("I", "\nFound Eligibility");
	}
	else{
		$this->trace("I", "\nNo eligibility could be found.");
	}
}

		
	
//=============================================
//make comments here how the code should work in english here before writing the code
//keep in mind you want a clicktimmer or multi_text_onpage when ever going to a new page
//when starting put a lot of dumps and traces 
//when ending only have the dumps that are used for the parser in the scrape
//=============================================	
public function get_precert_dump($claim){
	global $precertification_found;
	$this->arClick("id=ctl00_hlnkPrecert", 15, true, "Pre-Certification Button");
	$textOnPage = $this->multi_text_onpage(1, 20, "Precert", false);	
	if($textOnPage == "Precert"){
		$this->trace("I","\nOn Pre-certification page.");
		$this->precertification_search_policyid_dos($claim);		
			if($precertification_found == false){
				$this->arClick("id=ctl00_hlnkPrecert", 15, true, "Pre-Certification Button");
				sleep(10);
				$this->precertification_search_lastname_firstname_dos($claim);
			}	
			if($precertification_found == false){
				$this->arClick("id=ctl00_hlnkPrecert", 15, true, "Pre-Certification Button");
				sleep(10);
				$this->precertification_search_initials_dos($claim);
			}	
			if($precertification_found == false){
				$this->arClick("id=ctl00_hlnkPrecert", 15, true, "Pre-Certification Button");
				sleep(10);
				$this->precertification_search_lastname_firstname($claim);
			}		
	}
	else{
		$this->trace("I","\nCouldn't open Precertification page");
		$Claims = "Warning_";
		$subject = "$could_not_open_precertification_page";
		$this->errorEmail ($Claims,$subject);
		return false;			
	}
}
	
	
/*1.	Function – precertification_search_Policy #_EffectiveDate()
a.	Policy#
b.	EffectiveDate
i.	Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2;
*/

public function precertification_search_policyid_dos($claim){
	$this->trace("I","\nin LastName_FirstName_DOS search\n");
	$policy_id = $claim['insured_id'];
	//$policy_id = 155346385;
	$this->trace("I","\n$policy_id");
	$this->trace("I","\ntyping policyid");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtSSNALT", "$policy_id", true, 30, "Policy ID", true);	
	$starting_date_of_service = trim($claim['starting_date_of_service']);
	$startingDOS = $this->arDateDashestoSlashes($starting_date_of_service); 
	$this->trace("I","\ntyping effective date");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtEffectiveDate", "$startingDOS", true, 30, "Effective Date", true);
	$this->trace("I", "\nClicking on the Search Existing button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Existing Button");
	$precertification_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[1]/th[3]/a","Policy ID");
	$this->trace("I", "\nPrecertification found is $precertification_found");
	if ($precertification_found=="Policy ID"){
		$this->trace("I", "\nFound Precertification");
		$this->arClick("//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[2]/td[3]", 30, true, "Search Button");
		sleep(5);		
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo precertification could be found.");
		return $precertification_found = false;
	}
}
/*
2.	Function – precertification_search_ Last Name, First name, Effective date,  Thru date ()
a.	Last Name
b.	First Name
c.	Effective date
d.	Thru date
i.	Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2
*/

public function precertification_search_lastname_firstname_dos($claim){
	$this->trace("I","\nin precertification_search_lastname_firstname_dos\n");
	//$last_name = "MC CARREN";
	//$first_name = "DEBORAH";
	//$startingDOS = "03/07/2014";
	$last_name = $claim['patient_last_name'];
	$first_name = $claim['patient_first_name'];
	$starting_date_of_service = trim($claim['starting_date_of_service']);
	$startingDOS = $this->arDateDashestoSlashes($starting_date_of_service); 
	$this->trace("I","\n$last_name $first_name $startingDOS");
	$this->trace("I","\ntyping last name, first name, Effective Date");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLastName", $last_name, true, 30, "Last Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtFirstName", $first_name, true, 30, "First Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtEffectiveDate",$startingDOS, true, 30, "DOS From", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);	
	$precertification_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[1]/th[3]/a","Policy ID");
	$this->trace("I", "\nPrecertification found is $precertification_found");
	if ($precertification_found=="Policy ID"){
		$this->trace("I", "\nFound Precertification");
		$this->arClick("//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[2]/td[3]", 30, true, "Search Button");
		sleep(5);		
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo precertification could be found.");
		return $precertification_found = false;
	}
}

/*
3.	Function – precertification_search_ Initials, Effective date, thru date()
a.	Initials
b.	Effective date
c.	Thru date

Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2;
*/

public function precertification_search_initials_dos($claim){
	$this->trace("I","\nin precertification_search_initials_dos\n");
	//$last_name = "MC CARREN";
	//$first_name = "DEBORAH";
	//$startingDOS = "03/07/2014";
	$last_name = $claim['patient_last_name'];
	$last_initial = substr($last_name,0,1);
	$first_name = $claim['patient_first_name'];
	$first_initial = substr($first_name,0,1);
	$starting_date_of_service = trim($claim['starting_date_of_service']);
	$startingDOS = $this->arDateDashestoSlashes($starting_date_of_service); 
	$this->trace("I","\n$last_initial $first_initial $startingDOS");
	$this->trace("I","\ntyping last initial, first initial, Effective Date");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLastName", $last_initial, true, 30, "Last Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtFirstName", $first_initial, true, 30, "First Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtEffectiveDate",$startingDOS, true, 30, "DOS From", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);	
	$precertification_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[1]/th[3]/a","Policy ID");
	$this->trace("I", "\nPrecertification found is $precertification_found");
	if ($precertification_found=="Policy ID"){
		$this->trace("I", "\nFound Precertification");
		$this->arClick("//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[2]/td[3]", 30, true, "Search Button");
		sleep(5);		
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo precertification could be found.");
		return $precertification_found = false;
	}
}

/*
4.	Function – precertification_search_ Last name, first name()
a.	Last name
b.	First name

Call function multi_text()
1.	If return false
a.	Continue loop
2.	else
a.	return true
b.	break 2;
*/


public function precertification_search_lastname_firstname($claim){
	$this->trace("I","\nin precertification_search_lastname_firstname\n");
	//$last_name = $claim['patient_last_name'];
	//$first_name = $claim['patient_first_name'];
	$last_name = "MC CARREN";
	$first_name = "DEBORAH";
	$this->trace("I","\n$last_name $first_name");
	$this->trace("I","\ntyping last name, first name, Effective Date");
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtLastName", $last_name, true, 30, "Last Name", true);
	$this->arType("id=ctl00_ContentPlaceHolderContent_Main_txtFirstName", $first_name, true, 30, "First Name", true);
	$this->trace("I", "\nClicking on the search button");
	$this->arClick("id=ctl00_ContentPlaceHolderContent_Main_btnSearch", 30, true, "Search Button");
	sleep(10);	
	$precertification_found=$this->clicktimmer(1,10,"//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[1]/th[3]/a","Policy ID");
	$this->trace("I", "\nPrecertification found is $precertification_found");
	if ($precertification_found=="Policy ID"){
		$this->trace("I", "\nFound Precertification");
		$this->arClick("//body/form/div[4]/div/div/div[2]/div[2]/div[1]/div/table/tbody/tr/td[2]/div/div/table/tbody/tr[2]/td[3]", 30, true, "Search Button");
		sleep(5);		
		$File=$this->getFileName()."policy";
		$this->dumpPage($File);
	}
	else{
		$this->trace("I", "\nNo precertification could be found.");
		return $precertification_found = false;
	}
}

	
//===========================================
//Funtion Dump and scrape claims
//calls the mapping file and parser
//change the name of the mapping file and parser (do not include the extention php)
//DO NOT USE THIS FUNCTION IN A WHILE STATEMENT.. the html_write_multi function needs to be out side the while in order to keep num_records accurate
//===========================================
function dumpAndScrapeClaims ($claimReportMessage)
	{
	$this->trace("I","in dumpAndScrapeClaims function");
	$this->trace("I","found claim");
	//$mapping_file="mapping_file_name";
	//$parser="html_parser";
	$this->trace("I","mapping file to use is $mapping");
	$file_name="Claims";
	$File=$this->getFileName();
	$this->trace("I"," to database");
	$dump_page=$this->dumpPage($file_name.$File);
	$parser = 'put parser name here';
	$mapping = 'put mapping file name here';
	$this->html_write_multi($parser,$mapping);
	$this->my_parser->parse_source_page($dump_page);
	//Going to set the appeals days remaining calling function from selenium
	//calculates rs_date minus todays date, that number minus the number of days to appeal from the rules table.
	$claimIddb=$claim['id'];
	$this->get_appeals($claimIddb);
	$this->updateClaimsField("claim_report", $claimReportMessage);
	$this->trace("I","Going out of dumpAndScrapeClaims function");
	}
	
//===========================================
//Funtion Dump and scrape eligibility
//===========================================
function dumpAndScrapeEligibility ()
	{
	$this->trace("I","in dumpAndScrapeEligibility function");
	$this->trace("I","found eligibility");
	//$mapping_file="mapping_file_name";
	//$parser="html_parser";
	$this->trace("I","mapping file to use is $mapping");
	$file_name="Eligibility";
	$File=$this->getFileName();
	$this->trace("I"," to database");
	$dump_page=$this->dumpPage($file_name.$File);
	$parser = 'put parser name here';
	$mapping = 'put mapping file name here';
	$this->html_write_multi($parser,$mapping);
	$this->my_parser->parse_source_page($dump_page);
	$this->trace("I","Going out of dumpAndScrapeEligibility function");
	}	



		
//========================================================
//function for leaving claims and goning to home page to start eligibility
//clicks home page link 
//checks for text on page "Eligibility Inquiry"
//if that text is not there email error message and exit scrape
//========================================================	
function goingHome ()
	{	
	
	}
//==================
//errorEmail function 
//the $areaFailed is a message that is going to be in dump page link
// in the $areaFailed refers to claims or eligibility, although you can be a bit more specific if you like
//the $subject is what shows up in the email subject 
// in the $subject there should be what payer and which page didn't load
//the email will include a dump page and the logs
//for who to email it to put your email address in to 
//===================
Public Function errorEmail ($areaFailed,$subject)
	{
	$File="Error_".$areaFailed.$this->getFileName();
	$ErrorDump=$dump_page=$this->dumpPage($File);
	$htmlFileName = preg_replace('/\/var\/www\/advanceresponse\/www\//','https://www.advanceresponse.com/',$ErrorDump);
	$errorMessage = "$subject $htmlFileName \nExiting to try again ";
	mail($this->mailTo, "AR System Alert-".$subject, $errorMessage, $this->mailFrom);
	}

function noClaimsFound($claimReportMessage)
	{
	$lastname = $claim['patient_last_name'];
	$firstname = $claim['patient_first_name'];
	$this->dbData['patient_name']=$lastname." ".$firstname;
	$this->dbData['patient_dob'] = $claim['patient_dob'];
	$this->dbData['status'] = 'No Claims Found';
	$this->updateClaimsField('num_records',0);
	$this->updateClaimsField('status','No Claims Found');	
	$this->trace("I","num_records should be 0");
	$this->writeClaim();	
	//exit claims go to home page start eligibility
	//$this->goingHome();
	}
	
function noEligibilityFound($claim, $eligibilityReport)
	{
	$this->trace("I","***noEligibilityFound() function***");
	$this->updateClaimsField("eligibility_report","$eligibilityReport");
	$this->updateClaimsField("eligibility_status","No Eligibility Found");
	$lastname = $claim['patient_last_name'];
	$firstname = $claim['patient_first_name'];
	$this->dbData['subscriber_name']="$lastname, $firstname";
	$this->dbData['group_number'] = $claim['group_id'];
	$this->dbData['status']="No Eligibility Found";
	$this->writeEligibility();
	return false;
	//$this->goingHome();
	}
		
function UnexpectedText($WhereTextWasFound)
	{
		$this->trace("I","In UnxpectedText Function: $WhereTextWasFound");
		$File="UnxpctedText_".$this->getFileName();
		$ErrorDump=$dump_page=$this->dumpPage($File);
		$Claims = "Warning_";
		$subject = "$WhereTextWasFound";
		$this->errorEmail ($Claims,$subject);
	}

		
}














?>