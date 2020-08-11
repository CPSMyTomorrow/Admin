
<?php
	require_once('OAuth.php');
	
	// Establish an OAuth consumer based on our admin 'credentials'
	$headers = array(                    
		'Content-Type: application/json',
		'Accept: application/json',
		'X-Requested-With: XMLHttpRequest',
		'user-agent: Apache-HttpClient/UNA'
	);
	
	$CONSUMER_KEY = '32e1a789cd61d6df73426bab1421a23705cffcb4e';
	$CONSUMER_SECRET = 'adc4fce4abffa9c3384ca4f03cb71617';
	$consumer = new OAuthConsumer( $CONSUMER_KEY, $CONSUMER_SECRET, NULL );
	
	// Setup OAuth request based our previous credentials and query	
	
		
	$url = 'https://api.schoology.com/v1/courses';
		
	$request = OAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', $url);
	
	$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL );
	// Make signed OAuth request to the Contacts API server
	
	$response = send_request( $request->get_normalized_http_method(), $url, $request->to_header() );
	
	$data = json_decode($response, True);
	$coursesTotal = $data['total'];
	for ($i=0; $i<=$coursesTotal; $i+=200)
	{
		
		$url = 'https://api.schoology.com/v1/courses?start='.$i.'&limit=200';
		
		$request = OAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', $url);
		
		$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL );
		// Make signed OAuth request to the Contacts API server
		
		$response = send_request( $request->get_normalized_http_method(), $url, $request->to_header() );
		$data = json_decode($response, True);
		$courseData = $data['course'];		

		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveCourseData');
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $courseData ));
		$result = curl_exec($ch);
		curl_close( $ch );
	}
	$ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/GetAllCourses');
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	$result = curl_exec($ch);
	curl_close( $ch );
	$courses = json_decode($result);
	foreach($courses as $key=> $course)
	{	
		$courseArray = json_decode(json_encode($course), TRUE);
		$courseId =  $courseArray['id'];
		$url = 'https://api.schoology.com/v1/courses/'.$courseId.'/sections';
		
		$request = OAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', $url);
		
		$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL );		
		
		$response = send_request( $request->get_normalized_http_method(), $url, $request->to_header() );
							
		$sectionsData = json_decode($response, True);
		
		if(!empty($sectionsData['section']))
		{
			$sectionData = $sectionsData['section'];
			$sectionsArray = [];
			foreach($sectionData as $key=>$value)
			{
				$sectionArray = array(
					"SectionId" => 0,
					"id" => (!empty($value['id']) ? $value['id'] : ''),
					"courseId" => (!empty($value['course_id']) ? $value['course_id'] : ''),
					"description" => (!empty($value['description']) ? $value['description'] : ''),
					"section_school_code" => (!empty($value['section_school_code']) ? $value['section_school_code'] : ''),
					"grading_periods" => (!empty($value['grading_periods']) ? $value['grading_periods'] : '')
				);	
				$sectionsArray[] = $sectionArray;
			}
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveCourseSectionData');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($sectionsArray));
			$result = curl_exec($ch);
			curl_close( $ch );
		}
	}
	
	$ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/GetSections');
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	$result = curl_exec($ch);
	curl_close( $ch );
	$sections = json_decode($result);
	foreach($sections as $key=> $section)
	{
		$sectionArray = json_decode(json_encode($section), TRUE);
		$sectionId = $sectionArray['id'];
		$url = 'https://api.schoology.com/v1/sections/'.$sectionId.'/enrollments';
		
		$request = OAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', $url);
		
		$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL );		
		
		$response = send_request( $request->get_normalized_http_method(), $url, $request->to_header() );
							
		$enrollments = json_decode($response, True);
		
		if(!empty($enrollments['enrollment']))
		{
			$enrollmentsData = $enrollments['enrollment'];												
			$enrollmentsArray = [];
			foreach($enrollmentsData as $key=>$value)
			{												
				$enrollmentArray = array(						
					"EnrollmentId" => 0,
					"section_id" => $sectionId,
					"id" => $value['id'],
					"uid" => $value['uid'],
					"school_uid" => $value['school_uid'],
					"name_title" => $value['name_title'],
					"name_title_show" => $value['name_title_show'],
					"name_first" => $value['name_first'],
					"name_first_preferred" => $value['name_first_preferred'],
					"use_preferred_first_name" => $value['use_preferred_first_name'],
					"name_middle" => $value['name_middle'],
					"name_middle_show" => $value['name_middle_show'],
					"name_last" => $value['name_last'],
					"name_display" => $value['name_display'],
					"admin" => $value['admin'],
					"status" => $value['status'],
					"picture_url" => $value['picture_url'],
					"enrollment_source" => $value['enrollment_source'],
					"created_date" => date("Y-m-d H:i:sa"),
					"modified_date" => ""
				);
				$enrollmentsArray[] = $enrollmentArray;
			}						
			// echo "<pre>";
			// print_r(json_encode($enrollmentsArray));
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveEnrollments');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($enrollmentsArray));
			$result = curl_exec($ch);
			curl_close( $ch );
		}
	}
	
	foreach($sections as $key=> $section)
	{
		$sectionArray = json_decode(json_encode($section), TRUE);
		$sectionId = $sectionArray['id'];
		$url = 'https://api.schoology.com/v1/sections/'.$sectionId.'/assignments';
		
		$request = OAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', $url);
		
		$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL );		
		
		$response = send_request( $request->get_normalized_http_method(), $url, $request->to_header() );
							
		$assignments = json_decode($response, True);
		
		if(!empty($assignments['assignment']))
		{	
			$assignmentsData = $assignments['assignment'];											
			$assignmentsArray = [];
			foreach($assignmentsData as $key=>$value)
			{	
				if($value['due'] >= '2019-08-01 00:00:00')										
				{
					$assignmentArray = array(						
						"str_assignees" => "",
						"AssignmentId" => 0,
						"id" => $value['id'],
						"section_id" => $sectionId,
						"title" => $value['title'],
						"due" => $value['due'],
						"description" => $value['description'],
						"type" => $value['type'],
						"assignees" => $value['assignees'],
						"grading_group_ids" => $value['grading_group_ids'],
						"created_date" => date("Y-m-d H:i:sa"),
						"grading_scale" => $value['grading_scale'],
						"grading_period" => $value['grading_period'],
						"grading_category" => $value['grading_category'],
						"max_points" => $value['max_points'],
						"factor" => $value['factor'],
						"is_final" => $value['is_final'],
						"show_comments" => $value['show_comments'],
						"grade_stats" => $value['grade_stats'],
						"allow_dropbox" => $value['allow_dropbox'],
						"allow_discussion" => $value['allow_discussion'],
						"published" => $value['published'],
						"grade_item_id" => $value['grade_item_id'],
						"available" => $value['available'],
						"completed" => $value['completed'],
						"dropbox_locked" => $value['dropbox_locked'],
						"grading_scale_type" => $value['grading_scale_type'],
						"show_rubric" => $value['show_rubric'],
						"num_assignees" => $value['num_assignees'],
						"completion_status" => $value['completion_status'],
						"links" => "",
					);
					$assignmentsArray[] = $assignmentArray;
				}
			}	
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveAssignments');
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($assignmentsArray));
			$result = curl_exec($ch);
			curl_close( $ch );
		}
	}

	
	$ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveStudentAssignmentNotification');
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	$result = curl_exec($ch);
	curl_close( $ch );

	$ch = curl_init();
	curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/GetAllEnrollments');
	curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
	$result = curl_exec($ch);
	curl_close( $ch );
	$enrollments = json_decode($result);

	if(!empty($enrollments))
	{
		foreach($enrollments as $key=> $enrollment)
		{
			$enrollmentArray = json_decode(json_encode($enrollment), TRUE);
			$UID = $enrollmentArray['uid'];
			$url = 'https://api.schoology.com/v1/users/'.$UID.'/grades';
			
			$request = OAuthRequest::from_consumer_and_token( $consumer, NULL, 'GET', $url);
			
			$request->sign_request( new OAuthSignatureMethod_HMAC_SHA1(), $consumer, NULL );		
			
			$response = send_request( $request->get_normalized_http_method(), $url, $request->to_header() );
								
			$grades = json_decode($response, True);			
			$sectionData = $grades['section'];
			if(!empty($sectionData))
			{
				foreach($sectionData as $section)
				{				
					$section_id = $section['section_id'];
					if(array_key_exists('period', $section))
					{
						$periods = $section['period'];
						foreach($periods as $period)
						{
							$period_id = $period['period_id'];
							$assignments = $period['assignment'];
							$assignmentsArray = [];
							foreach($assignments as $key=>$assignment)
							{
								$assignmentArray = array(
									"section_id" => $section_id,		
									"period_id" => $period_id,
									"enrollment_id" => (array_key_exists('enrollment_id', $assignment)? $assignment['enrollment_id'] : ''),
									"assignment_id" => (array_key_exists('assignment_id', $assignment)? $assignment['assignment_id'] : ''),
									"grade" => (array_key_exists('grade', $assignment)? $assignment['grade'] : ''),
									"exception" => (array_key_exists('exception', $assignment)? $assignment['exception'] : ''),
									"max_points" => (array_key_exists('max_points', $assignment)? $assignment['max_points'] : ''),
									"is_final" => (array_key_exists('is_final', $assignment)? $assignment['is_final'] : ''),
									"timestamp" => (array_key_exists('timestamp', $assignment)? $assignment['timestamp'] : ''),
									"comment" => (array_key_exists('comment', $assignment)? $assignment['comment'] : ''),
									"comment_status" => (array_key_exists('comment_status', $assignment)? $assignment['comment_status'] : ''),
									"override" => (array_key_exists('override', $assignment)? $assignment['override'] : ''),
									"calculated_grade" => (array_key_exists('calculated_grade', $assignment)? $assignment['calculated_grade'] : ''),
									"pending" => (array_key_exists('pending', $assignment)? $assignment['pending'] : ''),
									"type" => (array_key_exists('type', $assignment)? $assignment['type'] : ''),
									"location" => (array_key_exists('location', $assignment)? $assignment['location'] : ''),
									"scale_id" => (array_key_exists('scale_id', $assignment)? $assignment['scale_id'] : ''),
									"scale_type" => (array_key_exists('scale_type', $assignment)? $assignment['scale_type'] : ''),
									"assignment_type" => (array_key_exists('assignment_type', $assignment)? $assignment['assignment_type'] : ''),
									"web_url" => (array_key_exists('web_url', $assignment)? $assignment['web_url'] : ''),
									"category_id" => (array_key_exists('category_id', $assignment)? $assignment['category_id'] : ''),
								);
								$assignmentsArray[] = $assignmentArray;
							}
							$ch = curl_init();
							curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveUserGradeAssignments');
							curl_setopt( $ch,CURLOPT_POST, true );
							curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
							curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
							curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
							curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($assignmentsArray));
							$result = curl_exec($ch);
							curl_close( $ch );
						}
					}

					if(array_key_exists('final_grade', $section))
					{
						$finalgrades = $section['final_grade'];
						$finalgradesArray = [];
						foreach($finalgrades as $key=>$finalgrade)
						{
							$finalgradeArray = array(
								"section_id" => $section_id,
								"period_id" => (array_key_exists('period_id', $assignment)? $assignment['period_id'] : ''),
								"grade" => (array_key_exists('grade', $assignment)? $assignment['grade'] : ''),
								"weight" => (array_key_exists('weight', $assignment)? $assignment['weight'] : ''),
								"comment" => (array_key_exists('comment', $assignment)? $assignment['comment'] : ''),
								"scale_id" => (array_key_exists('scale_id', $assignment)? $assignment['scale_id'] : '')
							);
							$finalgradesArray[] = $finalgradeArray;
						}
						$ch = curl_init();
						curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveFinalGrades');
						curl_setopt( $ch,CURLOPT_POST, true );
						curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
						curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
						curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
						curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($finalgradesArray));
						$result = curl_exec($ch);
						curl_close( $ch );
					}

					if(array_key_exists('grading_category', $section))
					{
						$gradingCategories = $section['grading_category'];
						$gradingCategoriesArray = [];
						foreach($gradingCategories as $key=>$gradingCategory)
						{
							$gradingCategoryArray = array(
								"section_id" => $section_id,
								"id" => (array_key_exists('id', $assignment)? $assignment['id'] : ''),
								"title" => (array_key_exists('title', $assignment)? $assignment['title'] : ''),
								"weight" => (array_key_exists('weight', $assignment)? $assignment['weight'] : '')
							);
							$gradingCategoriesArray[] = $gradingCategoryArray;
						}
						$ch = curl_init();
						curl_setopt( $ch,CURLOPT_URL, 'https://mytomorrowweb.cps-k12.org/API/Schoology/SaveGradeCategories');
						curl_setopt( $ch,CURLOPT_POST, true );
						curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
						curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
						curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
						curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($gradingCategoriesArray));
						$result = curl_exec($ch);
						curl_close( $ch );
					}
				}
				
			}
		}
	}
	
	
	/**
	 * Makes an HTTP request to the specified URL
	 * @param string $http_method The HTTP method (GET, POST, PUT, DELETE)
	 * @param string $url Full URL of the resource to access
	 * @param string $auth_header (optional) Authorization header
	 * @param string $postData (optional) POST/PUT request body
	 * @return string Response body from the server
	 */
	function send_request( $http_method, $url, $auth_header = null, $postData = null ) {
		$curl = curl_init( $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FAILONERROR, false );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		switch( $http_method ) {
			case 'GET':
				if( $auth_header ) {
					curl_setopt( $curl, CURLOPT_HTTPHEADER, array( $auth_header ) );
				}
				break;
			case 'POST':
				curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/atom+xml',
					$auth_header ) );
				curl_setopt( $curl, CURLOPT_POST, 1 );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $postData );
				break;
			case 'PUT':
				curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-Type: application/atom+xml',
					$auth_header ) );
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $http_method );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $postData );
				break;
			case 'DELETE':
				curl_setopt( $curl, CURLOPT_HTTPHEADER, array( $auth_header ) );
				curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $http_method );
				break;
		}
		$response = curl_exec( $curl );
		if( !$response ) {
			$response = curl_error( $curl );
		}
		curl_close( $curl );
		return $response;
	}
	
	/**
	 * Joins key:value pairs by inner_glue and each pair together by outer_glue
	 * @param string $inner_glue The HTTP method (GET, POST, PUT, DELETE)
	 * @param string $outer_glue Full URL of the resource to access
	 * @param array $array Associative array of query parameters
	 * @return string Urlencoded string of query parameters
	 */
	 
	 
	function implode_assoc( $inner_glue, $outer_glue, $array ) {
		$output = array();
		foreach( $array as $key => $item ) {
			$output[] = $key . $inner_glue . urlencode( $item );
		}
		return implode( $outer_glue, $output );
	}