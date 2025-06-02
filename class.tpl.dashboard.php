<?php
/*******************************************************************************
 * @class		dashBoardDisplay phpfunction 
 * @author      	Balaji
 * @created date	2010-12-11
 ******************************************************************************/
fileRequire("classes/class.getSector.php");
fileRequire("classes/class.common.php");
fileRequire("dataModels/class.statusDetails.php");
fileRequire("dataModels/class.tenderStatusDetails.php");
fileRequire("classes/class.groupDetailsAccess.php");
class dashBoardDisplay extends getSector
{
	var $_Osmarty;
	var $_OobjResponse;
	var $_Oconnection;
	var $_AflightNumber;
	var $_StemplateDisplay;
	var $_Asectors;
	var $_OgetSector;
	var $_OstatusDetails;
	var $_JstatusDetails;
	var $_AstatusDetails;
	var $_SheadingName;
	var $_JrequestRequestType;
	var $_JquoteTypeDetails;
	var $_JcabinDetails;
	var $_Ocommon;
	var $_JgroupCategoryList;
	var $_Sview;
	var $_JuserDetails;
	var $_OgroupDetailsAccess;
	var $_IinputData;
	var $_JfareTpes=array();
	function __construct()
	{
		parent::__construct();
		$this->_Osmarty='';
		$this->_OobjResponse='';
		$this->_Oconnection='';
		$this->_AflightNumber=array();
		$this->_StemplateDisplay ='';
		$this->_Asectors=array();
		$this->_JstatusDetails='';
		$this->_AstatusDetails=array();
		$this->_OstatusDetails = new statusDetails();
		$this->_AtenderStatusDetails=array();
		$this->_OtenderStatusDetails = new tenderStatusDetails;
		$this->_SheadingName = "";	
		$this->_SmoduleName = "";
		$this->_Sview = "TA"; //TA - Travel Agent, AL - Airline
		$this->_JrequestRequestType=array();
		$this->_JquoteTypeDetails=array();
		$this->_JcabinDetails=array();
		$this->_Ocommon = new common; 
		$this->_JgroupCategoryList=array();
		$this->_SfromDate=$this->_Ocommon->_getDateFilters();
		$this->_SfromDateDisp = date('d-F-Y',strtotime($this->_SfromDate));
		$this->_SendDate=date('Y-m-d',strtotime($this->_Ocommon->_getUserDateFormatValue(date('Y-m-d H:i:s'))));
		$this->_SendDateDisp = date('d-F-Y',strtotime($this->_SendDate));
		$this->_JcorporateDetails = '[]';
		$this->_JuserDetails = '[]';
		$this->_OgroupDetailsAccess = new groupDetailsAccess;
		$this->_IinputData=array();
		$this->_JfareTpes = array();
	}
	
	function _getDashBoardDisplay()
	{		
		global $objCommon;
		global $CFG;
		if($this->_SheadingName=="")
		{
			$this->_SheadingName=$this->_Osmarty->getConfigVars('COMMON_VIEW_REQUEST_HEADING');
		}
		$this->_getSectorValue();		
		$this->_OstatusDetails->_Oconnection = $this->_Oconnection;
		if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
			{
				$this->_OstatusDetails->_SbackEnd = 'Y';
				if(!empty($CFG["default"]["hideQueryBoxStatusFilter"]["processRequest"]))
				{	
					$this->_OstatusDetails->_SstatusCode =  implode(',', $CFG["default"]["hideQueryBoxStatusFilter"]["processRequest"]);
					$this->_OstatusDetails->_Sincondition = 'NOT IN';
				}
			}
		else
		{
			$this->_OstatusDetails->_SfrontEnd = 'Y';
			if(!empty($CFG["default"]["hideQueryBoxStatusFilter"]["viewRequestPage"]))
			{	
				$this->_OstatusDetails->_SstatusCode =  implode(',', $CFG["default"]["hideQueryBoxStatusFilter"]["viewRequestPage"]);
				$this->_OstatusDetails->_Sincondition = 'NOT IN';
			}
		}
		
		//count value is wrong when directly get it from the function name
		$this->_OstatusDetails->_selectStatusDetails();							
		$count = count((array)$this->_OstatusDetails->_AstatusDetails);
		$ind=0;
		$statusDetails[$ind]='';
		$statusArray=Array();
		for($i=0;$i<$count;$i++)
		{
			/*
			*Author:dhamu G
			*Modified date:20/06/2019
			*hide the status based on the config
			*/
			$ind++;								
				if($this->_OstatusDetails->_AstatusDetails[$i]['status_id'] == 16)
				{
					if(isset($CFG['site']['verticalProcess']) && ($CFG['site']['verticalProcess']=='Y'))
					{
						$_AsentForReview = $this->_OstatusDetails->_AstatusDetails[$i];
						$_AsentForReview['status_name'] = $_AsentForReview['status_name'].'-GD';
						array_push($statusArray,$_AsentForReview);
						$_AsentForReview = $this->_OstatusDetails->_AstatusDetails[$i];
						$_AsentForReview['status_name'] = $_AsentForReview['status_name'].'-GS';
						array_push($statusArray,$_AsentForReview);
					}
					else
						array_push($statusArray,$this->_OstatusDetails->_AstatusDetails[$i]);
					
				}
				else
					array_push($statusArray,$this->_OstatusDetails->_AstatusDetails[$i]);
				$this->_AstatusDetails['statusId'][$ind]=$this->_OstatusDetails->_AstatusDetails[$i]['status_id'];
				$this->_AstatusDetails['statusName'][$ind]=$this->_OstatusDetails->_AstatusDetails[$i]['status_name'];							
		}
		$this->_JpnrQueueStore='';
		$this->_ApnrQueueNumber=array();
		if(!empty($CFG['queueReading']['queueProcess']))
		{
			$_AtempQueue=$CFG['queueReading']['queueProcess'];
			foreach($_AtempQueue as $_Skey => $_Avalue)
			{
				if(strtoupper($_Skey) != 'LOGIN')
				{
					$_AtemppnrQueueNumber=array('queue_name'=>ucfirst(strtolower($_Skey)),'queue_value'=>$_Avalue['queueNo']);
					$this->_ApnrQueueNumber[]=$_AtemppnrQueueNumber;
				}
			}
			$this->_JpnrQueueStore=json_encode($this->_ApnrQueueNumber,1);
		}
		if(isset($this->_IinputData['status_id']) && $this->_IinputData['status_id'] !='' )
			$this->_SstatusSelectedValue =$this->_IinputData['status_id'];
		
		//kathir code Jun 30th 2022  for dashboard filter process 
		if(!empty($this->_IinputData['requested_date']) && isset($this->_IinputData['requested_date']) && $this->_IinputData['requested_date'] !=''&& $this->_IinputData['requested_date'] !='undefined' ){
			
			$this->_SrequestDateSelectedValue =$this->_IinputData['requested_date'];
			$this->_SfromDateDisprequestSelectedValue = date('d-F-Y',strtotime($this->_SrequestDateSelectedValue));	

			$this->_SrequestendDateSelectedValue =date('Y-m-d');
			$this->_SendDateDisprequestSelectedValue = date('d-F-Y',strtotime($this->_SrequestendDateSelectedValue));	
		}
			
				
		$this->_JrequestRequestType = json_encode($objCommon->_getRequestTypeDetails());
		$this->_JstatusDetails = json_encode($statusArray);
		$this->_AquoteTypeDisplay=array();
		foreach (array_keys($CFG['default']['quoteTypeFilter']) as $key => $value) {
			$this->_AquoteTypeDisplay[$key]['display_value'] =$this->_Osmarty->getConfigVars("COMMON_".strtoupper($value));
			$this->_AquoteTypeDisplay[$key]['input_value']=$CFG['default']['quoteTypeFilter'][$value] ;
		}
		$this->_JquoteTypeDetails = json_encode($this->_AquoteTypeDisplay);
		/*Check for groupAccess config,if it is set then RM analyst the status is selected in process request*/
		if(isset($CFG['processRequest']['groupAccess']))
		{
			$_AgroupAccess = $CFG['processRequest']['groupAccess'];
			if(array_key_exists($_SESSION['groupRM']['groupId'],$_AgroupAccess))
			{
				$_AstatusId = array();
				foreach($_AgroupAccess[$_SESSION['groupRM']['groupId']] as $_SstatusCode)
					$_AstatusId[] = $this->_Ocommon->_getCurrentStatusId($_SstatusCode);
				$this->_Astatus = $_AstatusId;
			}
		}	
		
		/**
		 * To get the tender status array
		 **/
		 
		if( isset($CFG["site"]["tenderRequest"]) && ($CFG["site"]["tenderRequest"]=="Y"))
		{
			$this->_OtenderStatusDetails->_Oconnection = $this->_Oconnection;
			$this->_OtenderStatusDetails->_Stype = 'Tender';
			$countValue = count((array)$this->_OtenderStatusDetails->_selectTenderStatusDetails());
			$index=0;
			$tenderStatusDetails[$index]='';
			$tenderStatusArray=Array();
			for($i=0;$i<$countValue;$i++)
			{
				$index++;
				array_push($tenderStatusArray,$this->_OtenderStatusDetails->_AtenderStatusDetails[$i]);
				$this->_AtenderStatusDetails['statusId'][$index]=$this->_OtenderStatusDetails->_AtenderStatusDetails[$i]['tender_status_id'];
				$this->_AtenderStatusDetails['statusName'][$index]=$this->_OtenderStatusDetails->_AtenderStatusDetails[$i]['status_code'];	

			}		
			$this->_JtenderStatusDetails = json_encode($tenderStatusArray);
		}
		
		
/*		$userDetailsArray = $objCommon->_getUserEmail('','',0,'N');
		array_unshift($userDetailsArray,array(0=>array('user_id'=>0,'user_name'=>'','email_id'=>'')));
		$this->_JuserDetails = json_encode($userDetailsArray);*/
		#$this->_JcorporateDetails = $objCommon->_getCorporateListCommon();
		if(!in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
			$this->_AcreatorDetailsArray=$objCommon->_getUserEmail('','',$_SESSION['groupRM']['groupCorporateId'],'N','','N');
		else
			$this->_AcreatorDetailsArray=$objCommon->_getUserEmail('','',0,'Y','','N');
		#array_unshift($this->_AcreatorDetailsArray,array(0=>array('user_id'=>0,'user_name'=>'','email_id'=>'')));
		$this->_JcreatorDetails = json_encode($this->_AcreatorDetailsArray);
		
		if(!in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
		{
			$this->_OgroupDetailsAccess->_Oconnection = $this->_Oconnection;
			$_AgroupDetails = $this->_OgroupDetailsAccess->_getAccessGroupId($_SESSION['groupRM']['groupId']);
			if(empty($_AgroupDetails))
				$this->_AcreatorDetailsArray = array();
		}
		$this->_Osmarty->assign('headingName',$this->_SheadingName);
		
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		
		$this->_AfareTypes=array();
		$cabinDetails=$this->_Ocommon->_getCabinDetails();
		$this->_AfareTypes=$this->_Ocommon->_getFaretype();
		$this->_JfareTpes=json_encode($this->_AfareTypes);
		$this->_AcabinDisplay=array();				
		for($i=0;$i<count((array)$cabinDetails);$i++)
		{
			$this->_AcabinDisplay[$i]['cabin_name']=$this->_Osmarty->getConfigVars("COMMON_CABIN_".$cabinDetails[$i]['cabin_id']);
			$this->_AcabinDisplay[$i]['cabin_value']=$cabinDetails[$i]['cabin_value'];
		}
		$this->_JcabinDetails = json_encode($this->_AcabinDisplay);
		
		$groupCategoryList=$this->_Ocommon->_getGroupCategoryList();
		$this->_AgroupCategoryList=array();		
		$finalInput=array("inputArray"=>$groupCategoryList,"fieldName"=>"display_order","fieldType"=>"NUMBER","orderType"=>"ASC");
		$groupCategoryList = $this->_Ocommon->_dynamicSortFunction($finalInput);		
		for($i=0;$i<count($groupCategoryList);$i++)
		{
			$this->_AgroupCategoryList[$i]['group_category_id']=$groupCategoryList[$i]['group_category_id'];
			$this->_AgroupCategoryList[$i]['group_category_name']=$this->_Osmarty->getConfigVars("COMMON_GROUP_CATEGORY_".$groupCategoryList[$i]['group_category_id']);
		}
		$this->_JgroupCategoryList = json_encode($this->_AgroupCategoryList);
		
		$this->_getJSONCountryDetails();
		/*
		$this->_OgroupDetailsAccess->_Oconnection = $this->_Oconnection;
		$this->_AposDetails = $this->_OgroupDetailsAccess->_getPosDetailsBasedOnUser($_SESSION['groupRM']['groupUserId']);
	
		array_shift($this->_AposDetails);
		$this->_JposDetails=json_encode($this->_AposDetails);
		//$this->_AposDetails=$this->_Ocommon->_getPointOfSaleDetails();
		//$this->_JposDetails=json_encode($this->_AposDetails);
		/*
		#for get the city details
		$this->_AcityDetails=$this->_Ocommon->_getCityDetails();
		$this->_JcityDetails=json_encode($this->_AcityDetails);
		*/
		if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['posMappingGroup']))
		{
			$this->_Sview = "AL";
			//$this->_AposDetails = $this->_Ocommon->_getUserPOSDetails($_SESSION['groupRM']['groupUserId']);
			//$finalInput=array("inputArray"=>$this->_AposDetails,"fieldName"=>"pos_city","fieldType"=>"STRING","orderType"=>"ASC");
		//	$this->_AposDetails = $this->_Ocommon->_dynamicSortFunction($finalInput);
			
/*			$_AcorporateDetails = $this->_Ocommon->_getUserEmail('','',0,"N");
			//$this->_AcorporateDetails = array_column($this->_AcorporateDetails,"corporate_name","corporate_id");
			foreach ($_AcorporateDetails AS $_Acorporate) {
				$this->_AcorporateDetails[$_Acorporate['corporate_id']] = $_Acorporate['corporate_name'];
			}*/
		}
		else if($_SESSION['groupRM']['groupId']==1)
		{
/*			$_AcorporateDetails = $this->_Ocommon->_getUserEmail('','',0,"N");
			//$this->_AcorporateDetails = array_column($this->_AcorporateDetails,"corporate_name","corporate_id");
			foreach ($_AcorporateDetails AS $_Acorporate) {
				$this->_AcorporateDetails[$_Acorporate['corporate_id']] = $_Acorporate['corporate_name'];
			}*/
			$this->_Sview = "AL";
		}
		//To get group categries
		$groupCategoryList=$this->_Ocommon->_getGroupCategoryList();
		$this->_AgroupCategoryListValue=array();
		$this->_AgroupCategoryListDisplay=array();
		$_AtripType=array();
		$this->_Ocommon->_Osmarty=$this->_Osmarty;
		$this->_AtripTypeList=array();
		$_AtripType=$CFG['requestForm']['showTripType']['Filter'];
		foreach($_AtripType as $tripKey=>$tripValue)
		{
			$this->_AtripTypeList[$tripKey]['trip_id']=encrypt::staticDataEncode($tripValue);
			$this->_AtripTypeList[$tripKey]['trip_name']=$this->_Ocommon->_getTripTypeText($tripValue);
		}	
		$finalInput=array("inputArray"=>$groupCategoryList,"fieldName"=>"display_order","fieldType"=>"NUMBER","orderType"=>"ASC");
		$groupCategoryList = $this->_Ocommon->_dynamicSortFunction($finalInput);
		
		for($i=0;$i<count($groupCategoryList);$i++)
		{
			$this->_AgroupCategoryListValue[]=$groupCategoryList[$i]['group_category_id'];
			$this->_AgroupCategoryListDisplay[]=$this->_Osmarty->getConfigVars("COMMON_GROUP_CATEGORY_".$groupCategoryList[$i]['group_category_id']);
		}
		#For not displaying date in travel agency filter
		if(!in_array($_SESSION['groupRM']['groupId'], (array)$CFG["default"]["showDateFilter"]))
		{
			$this->_SfromDateDisp = '';
			$this->_SendDateDisp = '';
			$this->_SfromDate = '';
			$this->_SendDate = '';
		}

		#To get travel type from table
		$_AtravelTypeDetails = $this->_Ocommon->_getRequestTypeMaster();
		foreach ($_AtravelTypeDetails as $key => $value) {
			$this->_AtravelTypeDetails[$key] = encrypt::staticDataEncode($value['request_type_value']);
			$this->_AtravelTypeDisplay[$key] = $value['request_type_name'];			
		}
		//To get country list
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_AcountryDetails=$this->_Ocommon->_getCountryDetails();
		$this->_Osmarty->assign("countryCode",$this->_AcountryDetails['countryCode']);
		$this->_Osmarty->assign("countryName",$this->_AcountryDetails['countryName']);						
		# To search the request based on the sort by filter
		$_AsortByStore = array();
		$_AsortByOrderStore = array();
		if(isset($CFG["site"]["sortBySearch"]) && ($CFG["site"]["sortBySearch"]!="")){
			$_AsearchSortBy = $CFG["site"]["sortBySearch"];	
			foreach ($_AsearchSortBy["sortBy"] as $_Skey => $_Svalue) {
				$_AtempSort['display_value'] = $_Skey;								
				$_AtempSort['input_value'] = $_Svalue;
				$_AsortByStore[] = $_AtempSort;
			}
			foreach ($_AsearchSortBy["sortByOrder"] as $_Ikey => $_Avalue) {
				$_AtempSortOrder['display_value'] = $_Ikey;								
				$_AtempSortOrder['input_value'] = $_Avalue;
				$_AsortByOrderStore[] = $_AtempSortOrder;
			}						
			foreach ($_AsearchSortBy["sortStatusCode"] as $key => &$value)				
				$value = $this->_Ocommon->_getCurrentStatusId($value);				
			$this->_JsortByStore = json_encode($_AsortByStore);								
			$this->_JsortByOrderStore = json_encode($_AsortByOrderStore);	
			$this->_JsortStatus = json_encode($_AsearchSortBy["sortStatusCode"]);
			
			
		}	
		#To get Airline code from config
		$_Anewarray = [];
		if($CFG['site']['allowedFlightsCarrierCode'])
		{
			$_AallowedFlightCode = $CFG['site']['allowedFlightsCarrierCode'];
			foreach ($_AallowedFlightCode as $key => $value) {
			$_Anewarray[$key]['airlinecode'] = $value; 
			$_Anewarray[$key]['airlinename'] = $this->_Osmarty->getConfigVars("COMMON_".$value.'_AIRLINE_CODE');
			}
			$this->_JallowedFlightsCode = json_encode($_Anewarray);

		}
		$this->_Osmarty->assign('dashBoardDisplay',$this);
	}
}
?>
