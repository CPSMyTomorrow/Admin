<?php
set_time_limit(0); 
ignore_user_abort(true);
ini_set('max_execution_time', 0);

//GET STUDENTS DETAILS FROM DATABASE

$ch = curl_init('https://mytomorrowweb.cps-k12.org/API/School/GetAllSchools');
curl_setopt( $ch,CURLOPT_HTTPHEADER, array('Accept: application/json'));
curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
$result = curl_exec($ch);
curl_close($ch);
$schoolsData = json_decode($result, true);

$headers1 = array(                    
	'Content-Type: application/json',
	'Accept: application/json',
	'X-Requested-With: XMLHttpRequest',
	'user-agent: Apache-HttpClient/UNA'
);


// DEV 
// $ps_url= "https://psdev01.cps-k12.org:80";
// PROD
$ps_url = "https://powerschool.cps-k12.org";
$auth_url = $ps_url."/oauth/access_token/";

// DEV ACCESS 
// $ps_code = "0acc272c-5e32-4147-acb5-b948f25830b1"; //Client ID
// $ps_secret = "4c4b8b25-f9b9-4349-969e-89881e74733f"; //Client Secret

//PROD ACCESS
$ps_code = "bcf6e5df-4b45-4541-89c8-4991210ec876";
$ps_secret = "1627ce47-8caf-4b67-a951-485d9544eccd";

$ch = curl_init($auth_url);
curl_setopt($ch, CURLOPT_POST, TRUE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
curl_setopt($ch, CURLOPT_USERPWD, "$ps_code:$ps_secret");
$response = curl_exec($ch);
$json = json_decode($response);
$access_token = $json->access_token;



foreach($schoolsData as $key => $schoolData) {

	// sections
	$count_url = 'https://powerschool.cps-k12.org/ws/v1/school/'.$schoolData['PS_Id'].'/section/count?q=term.start_year==2019';
	$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
	$process = curl_init();
	curl_setopt($process, CURLOPT_URL, $count_url);
	curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($process, CURLOPT_TIMEOUT, 30);
	curl_setopt($process, CURLOPT_HTTPGET, 1);
	curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
	$response = curl_exec($process);
	$xmlstring = simplexml_load_string($response);
	$arr = json_decode( json_encode($xmlstring) , 1);
	$pages = ceil($arr['count']/100);		
	$i = '';
	for($i = 1; $i <= $pages; $i++) {
		$url = 'https://powerschool.cps-k12.org/ws/v1/school/'.$schoolData['PS_Id'].'/section?q=term.start_year==2019&pagesize=100&page='.$i;
		
		$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_HTTPGET, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($process);
		$xmlstring = simplexml_load_string($response);

		foreach($xmlstring->children() as $key=>$school)
		{ 
			$schools[]= array(
				'SectionId' => '',
				'id' => (string)$school->id,
				'school_id' => $schoolData['PS_Id'],
				'course_id'=> (string)$school->course_id,
				'term_id'=> (string)$school->term_id,
				'section_number'=> (string)$school->section_number,
				'expression'=> (string)$school->expression,
				'external_expression'=> (string)$school->external_expression,
				'staff_id'=> (string)$school->staff_id,
				'gradebooktype'=> (string)$school->gradebooktype,
				'inserted_on'=> date('Y-m-d h:i:s'),
				'modified_on'=> date('Y-m-d h:i:s')
			); 
		} 	
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/PowerSchool/SaveSections');
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers1);
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($schools));
		$result = curl_exec($ch);
		curl_close( $ch );		
	}

	// Assignment Section

	$ch = curl_init('https://mytomorrowweb.cps-k12.org/API/PowerSchool/GetSectionBySchoolId?schoolId='.$schoolData['SCHOOLID']);
	curl_setopt( $ch,CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	$result = curl_exec($ch);
	curl_close($ch);
	$sectionsData = json_decode($result, true);

	foreach($sectionsData as $key=> $sectionData)
	{			
		$url = 'https://powerschool.cps-k12.org/ws/schema/table/assignmentsection?projection=assignmentsectionid,yearid,sectionsdcid,assignmentid,relatedgradescaleitemdcid,name,duedate,description,scoretype,scoreentrypoints,extracreditpoints,weight,totalpointvalue,iscountedinfinalgrade,isscoringneeded,publishoption,publishdaysbeforedue,publishonspecificdate,publisheddate,publishedscoretypeid,isscorespublish,maxretakeallowed&q=sectionsdcid=='.$sectionData['id'];
		
		$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_HTTPGET, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($process);
		$assignmentsSections = json_decode($response);
		$assignmentsSectionsArray  = json_decode(json_encode($assignmentsSections), 1);
		$assignmentsArray = array();
		if(!empty($assignmentsSectionsArray['record'])){
			foreach($assignmentsSectionsArray['record'] as $record)
			{
				foreach($record['tables'] as $table){
					$assignmentArray= array(
						"pk_AssignmentSectionId" => 0,
						"assignmentsectionid" => $table['assignmentsectionid'],
						"yearid" => $table['yearid'],
						"sectionsdcid" => $table['sectionsdcid'],
						"assignmentid" => $table['assignmentid'],
						"relatedgradescaleitemdcid" => $table['relatedgradescaleitemdcid'],
						"name" => $table['name'],
						"duedate" => $table['duedate'],
						"description" => $table['description'],
						"scoretype" => $table['scoretype'],
						"scoreentrypoints" => $table['scoreentrypoints'],
						"extracreditpoints" => $table['extracreditpoints'],
						"weight" => $table['weight'],
						"totalpointvalue" => $table['totalpointvalue'],
						"iscountedinfinalgrade" => $table['iscountedinfinalgrade'],
						"isscoringneeded" => $table['isscoringneeded'],
						"publishoption" => $table['publishoption'],
						"publishdaysbeforedue" => $table['publishdaysbeforedue'],
						"publishonspecificdate" => $table['publishonspecificdate'],
						"publisheddate" => $table['publisheddate'],
						"publishedscoretypeid" => $table['publishedscoretypeid'],
						"isscorespublish" => $table['isscorespublish'],
						"maxretakeallowed" => $table['maxretakeallowed'],
						'inserted_on'=> date('Y-m-d h =>i:s'),
						'modified_on'=> date('Y-m-d h:i:s')
					); 
					$assignmentsArray[] = $assignmentArray;				
				}
			} 
		}	
		if(!empty($assignmentsArray))
		{
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/PowerSchool/SaveAssignmentSection');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers1);
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($assignmentsArray));
			$result = curl_exec($ch);
			curl_close( $ch );
		}
	}

	// Assignment Score

	$ch = curl_init('https://mytomorrowweb.cps-k12.org/API/PowerSchool/GetSectionAssignment?schoolId='.$schoolData['SCHOOLID']);
	curl_setopt( $ch,CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	$result = curl_exec($ch);
	curl_close($ch);
	$assignmentSectionsData = json_decode($result, true);

	foreach($assignmentSectionsData as $key=> $assignmentSectionData)
	{			
		$url = 'https://powerschool.cps-k12.org/ws/schema/table/assignmentscore?projection=assignmentscoreid,yearid,assignmentsectionid,studentsdcid,islate,iscollected,isexempt,ismissing,isabsent,isincomplete,actualscoreentered,actualscorekind,actualscoregradescaledcid,scorepercent,scorepoints,scorelettergrade,scorenumericgrade,scoreentrydate,scoregradescaledcid,altnumericgrade,altalphagrade,altscoregradescaledcid,hasretake&q=assignmentsectionid=='.$assignmentSectionData['assignmentsectionid'];
		
		$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_HTTPGET, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($process);
		$assignmentsScores = json_decode($response);
		$assignmentsScoresArray  = json_decode(json_encode($assignmentsScores), 1);
		$scoresArray = array();
		if(!empty($assignmentsScoresArray['record'])){
			foreach($assignmentsScoresArray['record'] as $record)
			{
				foreach($record['tables'] as $table){
					$scoreArray= array(
						"pk_assignmentscoreid" => 0,
						"assignmentscoreid" => $table['assignmentscoreid'],
						"yearid" => $table['yearid'],
						"assignmentsectionid" => $table['assignmentsectionid'],
						"studentsdcid" => $table['studentsdcid'],
						"islate" => $table['islate'],
						"iscollected" => $table['iscollected'],
						"isexempt" => $table['isexempt'],
						"ismissing" => $table['ismissing'],
						"isabsent" => $table['isabsent'],
						"isincomplete" => $table['isincomplete'],
						"actualscoreentered" => $table['actualscoreentered'],
						"actualscorekind" => $table['actualscorekind'],
						"actualscoregradescaledcid" => $table['actualscoregradescaledcid'],
						"scorepercent" => $table['scorepercent'],
						"scorepoints" => $table['scorepoints'],
						"scorelettergrade" => $table['scorelettergrade'],
						"scorenumericgrade" => $table['scorenumericgrade'],
						"scoreentrydate" =>  $table['scoreentrydate'],
						"scoregradescaledcid" => $table['scoregradescaledcid'],
						"altnumericgrade" => $table['altnumericgrade'],
						"altalphagrade" => $table['altalphagrade'],
						"altscoregradescaledcid" => $table['altscoregradescaledcid'],
						"hasretake" => $table['hasretake']
					); 
					$scoresArray[] = $scoreArray;				
				}
			} 
		}
		if(!empty($scoresArray))
		{
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/PowerSchool/SaveAssignmentScore');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers1);
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($scoresArray));
			$result = curl_exec($ch);
			curl_close( $ch );
		}
	}

	// Standard Score
	$ch = curl_init('https://mytomorrowweb.cps-k12.org/API/PowerSchool/GetSectionAssignment?schoolId='.$schoolData['SCHOOLID']);
	curl_setopt( $ch,CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	$result = curl_exec($ch);
	curl_close($ch);
	$assignmentSectionsData = json_decode($result, true);

	foreach($assignmentSectionsData as $key=> $assignmentSectionData)
	{			
		$url = 'https://powerschool.cps-k12.org/ws/schema/table/standardscore?projection=altscoregradescaledcid,standardscoreid,islate,scorepoints,altnumericgrade,isexempt,studentsdcid,scorelettergrade,scorenumericgrade,isabsent,ismissing,assignmentstandardassocid,hasretake,isincomplete,yearid,iscollected,scoregradescaledcid,actualscorekind,assignmentsectionid,scoreentrydate,actualscoreentered,actualscoregradescaledcid,scorepercent,altalphagrade&q=assignmentsectionid=='.$assignmentSectionData['assignmentsectionid'];
		
		$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_HTTPGET, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($process);
		$standardScores = json_decode($response);
		$standardScoresArray  = json_decode(json_encode($standardScores), 1);
		if(!empty($standardScoresArray['record'])){
			$scoresArray = array();
			foreach($standardScoresArray['record'] as $record)
			{
				foreach($record['tables'] as $table){
					$scoreArray= array(
						"pk_standardscoreid" => 0,
						"altscoregradescaledcid" =>  $table['altscoregradescaledcid'],
						"standardscoreid" => $table['standardscoreid'],
						"islate" => $table['islate'],
						"scorepoints" => $table['scorepoints'],
						"altnumericgrade" => $table['altnumericgrade'],
						"isexempt" => $table['isexempt'],
						"studentsdcid" => $table['studentsdcid'],
						"scorelettergrade" => $table['scorelettergrade'],
						"scorenumericgrade" => $table['scorenumericgrade'],
						"isabsent" => $table['isabsent'],
						"ismissing" => $table['ismissing'],
						"assignmentstandardassocid" => $table['assignmentstandardassocid'],
						"hasretake" => $table['hasretake'],
						"isincomplete" => $table['isincomplete'],
						"yearid" => $table['yearid'],
						"iscollected" => $table['iscollected'],
						"scoregradescaledcid" => $table['scoregradescaledcid'],
						"actualscorekind" => $table['actualscorekind'],
						"assignmentsectionid" => $table['assignmentsectionid'],
						"scoreentrydate" => $table['scoreentrydate'],
						"actualscoreentered" => $table['actualscoreentered'],
						"actualscoregradescaledcid" => $table['actualscoregradescaledcid'],
						"scorepercent" => $table['scorepercent'],
						"altalphagrade" => $table['altalphagrade'],
					); 
					$scoresArray[] = $scoreArray;				
				}
			} 
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/PowerSchool/SaveStandardScore');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers1);
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($scoresArray));
			$result = curl_exec($ch);
			curl_close( $ch );
		}		
	}
}    

// Attendee

	$ch = curl_init('https://mytomorrowweb.cps-k12.org/API/Students/GetStudentsList');
	curl_setopt( $ch,CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	$result = curl_exec($ch);
	curl_close($ch);
	$studentsData = json_decode($result, true);


	$date = date("Y-m-d");
	$attendeesArray = array();
	foreach($studentsData as $key => $student) {
		$url = "https://powerschool.cps-k12.org/ws/schema/table/attendance?projection=ada_value_time,transaction_date,yearid,studentid,att_date,periodid,att_interval,total_minutes,id&q=studentid==".$student['STUDENTID'].";att_date==".$date;
		$headers = array('Content-Type: application/x-www-form-urlencoded',"Authorization: Bearer {$access_token}");
		$process = curl_init();
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_HTTPGET, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($process);
		$result = json_decode($response);
		$attendeeData = json_decode(json_encode($result), true);
		if(!empty($attendeeData['record']))
		{
			$attendeeArray = array(
				'schoolid' => $student['SCHOOLID'],
				'studentid' => $student['STUDENTID'],
				'att_date' => $date.' '.date("h:i:s")
			);
			$attendeesArray[] = $attendeeArray;
		}
		
	}
	$ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/PowerSchool/SaveAttendance');
	curl_setopt( $ch,CURLOPT_POST, true );
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers1);
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($attendeesArray));
	$result = curl_exec($ch);
	curl_close( $ch );

?>

