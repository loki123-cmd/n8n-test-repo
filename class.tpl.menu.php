<?php
/*******************************************************************************
 * @class		displayMenu phpfunction 
 * @author      	Balaji
 * @created date	2010-11-02
 ******************************************************************************/
class displayMenu
{
	var $_Osmarty;
	var $_OobjResponse;
	var $_Oconnection;

	var $menu;
	var $subMenu;
	var $_StemplateDisplay;
	var $_Jmenu;
	var $_JsubMenu;
	
	function __construct()
	{
		$this->_Osmarty='';
		$this->_OobjResponse='';
		$this->_Oconnection='';
		$this->menu=array();
		$this->subMenu=array();
		$this->_StemplateDisplay ='';
		$this->_Jmenu='';
		$this->_JsubMenu='';
	}
	
	function _menuDisplay()
	{
		global $CFG;
		$sql="select distinct
				md.menu_id,
				md.menu_name,
				md.menu_link,
				cgmd.display_order,
				umsm.display_status
			from 
				".$CFG['db']['tbl']['menu_details']." md,
				".$CFG['db']['tbl']['user_menu_submenu_mapping']." umsm, 
				".$CFG['db']['tbl']['corporate_group_menu_details']." cgmd 
			where
				umsm.group_id='".$_SESSION['groupRM']['groupId']."' AND
				umsm.group_id=cgmd.group_id AND 
				umsm.menu_id=md.menu_id AND 
				umsm.menu_id=cgmd.menu_id AND
				umsm.display_status='y'
				AND md.menu_name!='Logout'
			order by 
				cgmd.display_order";

		if(DB::isError($result=$this->_Oconnection->query($sql)))
		{
			$this->_OobjResponse->call("commonObj.showErrorMessage","System down. Contact administrator.");
			fileWrite($sql,'SqlError','a+');
			return false;
		}
		$index=0;
		while($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$row['menu_name']=$this->_Osmarty->getConfigVars('MENU_MENUID_'.$row['menu_id']);
			$this->menu[] = $row;
			$menuIdArray[$index]=$row['menu_id'];
			$index++;
        	}
		
		$this->_resetMenuDetails($menuIdArray);
		
        	$this->_Jmenu=json_encode($this->menu);
	}

	function _resetMenuDetails($menuIdArray)
	{
		global $CFG;
		$_ImenuCount = count($menuIdArray);
		for($i=0;$i<$_ImenuCount;$i++)
		{
			if(isset($menuIdArray[$i]))
			{
				$sql = "SELECT 
						cms.menu_name,
						cms.menu_link,
						cms.display_status,
						cms.display_order,
						cms.corporate_id,
						cms.group_id,
						cms.menu_id	
					FROM  
						".$CFG['db']['tbl']['corporate_menu_settings']." cms
					WHERE 
						cms.corporate_id IN (".$_SESSION['groupRM']['groupCorporateId'].",0)
						AND cms.group_id IN (".$_SESSION['groupRM']['groupId'].",0)
						AND cms.user_id IN (".$_SESSION['groupRM']['groupUserId'].",0)
						AND cms.menu_id =".$menuIdArray[$i]."
					ORDER BY
						cms.user_id DESC,
						cms.corporate_id DESC,
						cms.group_id DESC
					LIMIT 1";

				if ( DB::isError($result = $this->_Oconnection->query($sql))) 
				{
					$this->_SerrorMsg = $result->getMessage();
					fileWrite($sql,'SqlError','a+');
					return FALSE;
				}

				if($result->numRows() > 0)
				{
					while($row=$result->fetchRow(DB_FETCHMODE_ASSOC))
					{
						if(in_array($row['menu_id'], $menuIdArray))
						{		
							$key=array_search($row['menu_id'], $menuIdArray);
							if($row['menu_name'])
								$this->menu[$key]['menu_name'] = $row['menu_name'];
							if($row['menu_link'])
								$this->menu[$key]['menu_link'] = $row['menu_link'];
							if($row['display_order'])
								$this->menu[$key]['display_order'] = $row['display_order'];
							if($row['display_status'])
								$this->menu[$key]['display_status'] = $row['display_status'];
							if($row['display_status']=="N")
							{
								unset($this->menu[$key]);
								unset($menuIdArray[$i]);
								$menuIdArray = array_values($menuIdArray);
								$this->menu = array_values($this->menu);
								$i--;
							}
						}
					}
				}
			}
		}
	}

	function _subMenuDisplay()
	{
		global $CFG;
		$sql="select distinct 
				sd.submenu_id, 
				sd.submenu_name,
				sd.submenu_link,
				md.menu_id,
				cgsd.display_order,
				umsm.display_status,
				umsm.parent_id,
				umsm.submenu_order
			from 
				".$CFG['db']['tbl']['menu_details']." md,
				".$CFG['db']['tbl']['submenu_details']." sd,
				".$CFG['db']['tbl']['user_menu_submenu_mapping']." umsm,
				".$CFG['db']['tbl']['corporate_group_submenu_details']." cgsd 
			where
				umsm.group_id='".$_SESSION['groupRM']['groupId']."' AND
				umsm.group_id=cgsd.group_id AND 
				umsm.menu_id=md.menu_id AND 
				umsm.submenu_id=sd.submenu_id AND 				
				umsm.submenu_id=cgsd.submenu_id AND
				umsm.display_status='y'
			order by
				umsm.submenu_order ASC,cgsd.display_order ASC";

		if(DB::isError($result=$this->_Oconnection->query($sql)))
		{
			$this->_OobjResponse->call("commonObj.showErrorMessage","System down. Contact administrator.");
			fileWrite($sql,'SqlError','a+');
			return false;
		}
		$index=0;
		while($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$row['submenu_name']=$this->_Osmarty->getConfigVars('MENU_SUBMENUID_'.$row['submenu_id']);
			$this->subMenu[] = $row;
			$subMenuIdArray[$index]=$row['submenu_id'];
			$index++;
		}
		//print_r($this->subMenu);exit;
		$this->_resetSubmenuDetails($subMenuIdArray);
		//print_r($this->subMenu);exit;
		$this->subMenuNew=$this->subMenu;
		foreach($this->subMenuNew as $key=>&$value)
		{
			if($value['parent_id']!=0)
			{
				$this->_mapSubmenuInsideSubmenu($this->subMenuNew,$value['parent_id'],$value);
			}
		}
		$this->_JsubMenu=json_encode($this->subMenu);
	}

	function _mapSubmenuInsideSubmenu(&$givenArray,$parentSubmenuId,$subArray)
	{
		foreach($givenArray as $key=>$value)
		{
			if($value['submenu_id']==$parentSubmenuId)
			{
				$givenArray[$key]['submenuArray'][]=$subArray;
				$givenArray[$key]['parentSubmenu']='Y';
			}
		}
	}

	function _resetSubmenuDetails($subMenuIdArray)
	{
		global $CFG;
		$submenuCount = count($subMenuIdArray);
		for($i=0;$i<$submenuCount;$i++)
		{
			if(isset($subMenuIdArray[$i]))
			{
				$sql = "SELECT 
						csms.submenu_name,
						csms.submenu_link,
						csms.menu_id,
						csms.display_status,
						csms.display_order,
						csms.corporate_id,
						csms.group_id,
						csms.submenu_id
					FROM  
						".$CFG['db']['tbl']['corporate_submenu_settings']." csms
					WHERE 
						csms.corporate_id IN (".$_SESSION['groupRM']['groupCorporateId'].",0)
						AND csms.group_id IN (".$_SESSION['groupRM']['groupId'].",0)
						AND csms.user_id IN (".$_SESSION['groupRM']['groupUserId'].",0)
						AND csms.submenu_id =".$subMenuIdArray[$i]."
					ORDER BY
						csms.user_id DESC,
						csms.corporate_id DESC,
						csms.group_id DESC
					LIMIT 1";

				if ( DB::isError($result = $this->_Oconnection->query($sql))) 
				{
					$this->_SerrorMsg = $result->getMessage();
					fileWrite($sql,'SqlError','a+');
					return FALSE;
				}

				if($result->numRows() > 0)
				{
					while($row=$result->fetchRow(DB_FETCHMODE_ASSOC))
					{
						if(in_array($row['submenu_id'], $subMenuIdArray))
						{		
							$key=array_search($row['submenu_id'], $subMenuIdArray);
							if($row['submenu_name'])
								$this->subMenu[$key]['submenu_name'] = $row['submenu_name'];
							if($row['submenu_link'])
								$this->subMenu[$key]['submenu_link'] = $row['submenu_link'];
							if($row['menu_id'])
								$this->subMenu[$key]['menu_id'] = $row['menu_id'];
							if($row['display_order'])
								$this->subMenu[$key]['display_order'] = $row['display_order'];
							if($row['display_status'])
								$this->subMenu[$key]['display_status'] = $row['display_status'];
							if(strtoupper($row['display_status'])=="N")
							{
								unset($this->subMenu[$key]);
								unset($subMenuIdArray[$i]);
								$subMenuIdArray = array_values($subMenuIdArray);
								$this->subMenu = array_values($this->subMenu);
								$i--;
							}
						}

					}
				
				}
			}
		}
	}

	function _getMenu()
	{
		global $CFG;
		$this->_AfinalMenuArray=array();
	
		foreach($this->menu as $menuKey=>$menuValue)
		{
			$this->_AfinalMenuArray[$menuKey]=$menuValue;
			foreach($this->subMenuNew as $submenuKey=>$submenuValue)
			{
				if($menuValue['menu_id']==$submenuValue['menu_id'] && $submenuValue['parent_id']==0)
				{
					$this->_AfinalMenuArray[$menuKey]['submenuArray'][]=$submenuValue;
				}
			}
		}
		
		
		$this->_Osmarty->assign('menu',$this->menu);
		$this->_Osmarty->assign('subMenu',$this->subMenu);
		$this->_Osmarty->assign('Jmenu',$this);
		$this->_Osmarty->assign('menuArray',$this->_AfinalMenuArray);
		$groupArray=array(0=>1,1=>2,2=>5,3=>6,4=>7);

		$initialModule = '';
		if(!in_array($_SESSION['groupRM']['groupId'],$groupArray))
		{
			$initialModule = "wrapperScript('adhocRequest','')";
		}
		else if(in_array($_SESSION['groupRM']['groupId'],$groupArray))
		{
			$initialModule = "wrapperScript('processDashBoard')";
		}

		$initialMenu = '';
		if(!in_array($_SESSION['groupRM']['groupId'],$groupArray))
		{
			#$initialMenu = "1";
			$initialMenu="";
			$initialSubMenu = "1";
		}
		else if(in_array($_SESSION['groupRM']['groupId'],$groupArray))
		{
			$initialMenu = "11";
			$initialSubMenu = "14";
		}
		
		
		$this->_Osmarty->assign('initialMenu',$initialMenu);
		$this->_Osmarty->assign('initialSubMenu',$initialSubMenu);
		$this->_Osmarty->assign('initialModule',$initialModule);
	}
}
?>
