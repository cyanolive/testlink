<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/ 
 *
 * @filesource  navBar.php
 *
 * This file manages the navigation bar. 
 *
 * internal revisions
 *
**/
require_once('../../config.inc.php');
require_once("common.php");
testlinkInitPage($db,true);

$tproject_mgr = new testproject($db);
$args = init_args();
$gui = new stdClass();
$gui_cfg = config_get("gui");

$gui->tprojectID = isset($_SESSION['testprojectID']) ? $_SESSION['testprojectID'] : 0;
$gui->tcasePrefix = '';
// Julian: left magic here - do think this value will never be used as a project with a prefix
//         has to be created after first login -> searchSize should be set dynamically.
//         If any reviewer agrees on that feel free to change it.
$gui->searchSize = 8;
if($gui->tprojectID > 0)
{
    $gui->tcasePrefix = $tproject_mgr->getTestCasePrefix($gui->tprojectID) . 
                        config_get('testcase_cfg')->glue_character;
                        
    $gui->searchSize = tlStringLen($gui->tcasePrefix) + $gui_cfg->dynamic_quick_tcase_search_input_size;
}

define('DBUG_ON',1);

$user = $_SESSION['currentUser'];
$userID = $user->dbID;

$gui->TestProjects = $tproject_mgr->get_accessible_for_user($userID,'map',$tlCfg->gui->tprojects_combo_order_by);
$gui->TestProjectCount = sizeof($gui->TestProjects);
$gui->TestPlanCount = 0; 

$tprojectQty = $tproject_mgr->getItemCount();
if($gui->TestProjectCount == 0 && $tprojectQty > 0)
{
  // User rights configurations does not allow access to ANY test project
  $_SESSION['testprojectTopMenu'] = '';
  $gui->tprojectID = 0;
}

if($gui->tprojectID)
{
	$testPlanSet = $user->getAccessibleTestPlans($db,$gui->tprojectID);
  $gui->TestPlanCount = sizeof($testPlanSet);

	$tplanID = isset($_SESSION['testplanID']) ? $_SESSION['testplanID'] : null;
    if( !is_null($tplanID) )
    {
    	// Need to set this info on session with first Test Plan from $testPlanSet
		// if this test plan is present on $testPlanSet
		//	  OK we will set it on $testPlanSet as selected one.
		// else 
		//    need to set test plan on session
		//
		$index=0;
		$testPlanFound=0;
		$loop2do=count($testPlanSet);
		for($idx=0; $idx < $loop2do; $idx++)
		{
    		if( $testPlanSet[$idx]['id'] == $tplanID )
    		{
    	    	$testPlanFound = 1;
    	    	$index = $idx;
    	    	$break;
    	    }
    	}
    	if( $testPlanFound == 0 )
    	{
			$tplanID = $testPlanSet[0]['id'];
			setSessionTestPlan($testPlanSet[0]);     	
    	} 
    	$testPlanSet[$index]['selected']=1;
    }
}	

if ($gui->tprojectID && isset($user->tprojectRoles[$gui->tprojectID]))
{
	// test project specific role applied
	$role = $user->tprojectRoles[$gui->tprojectID];
	$testprojectRole = $role->getDisplayName();
}
else
{
	// general role applied
	$testprojectRole = $user->globalRole->getDisplayName();
}	
$gui->whoami = $user->getDisplayName() . ' ' . $tlCfg->gui->role_separator_open . 
	           $testprojectRole . $tlCfg->gui->role_separator_close;
                   

// only when the user has changed project using the combo the _GET has this key.
// Use this clue to launch a refresh of other frames present on the screen
// using the onload HTML body attribute
$gui->updateMainPage = 0;
if ($args->testproject)
{
	$gui->updateMainPage = 1;
	// set test project ID for the next session
	setcookie('TL_lastTestProjectForUserID_'. $userID, $args->testproject, TL_COOKIE_KEEPTIME, '/');
}

$gui->grants = getGrants($db);

$smarty = new TLSmarty();
$smarty->assign('gui',$gui);
$smarty->display('navBar.tpl');


/**
 * 
 */
function getGrants(&$db)
{
    $grants = new stdClass();
    $grants->view_testcase_spec = has_rights($db,"mgt_view_tc");
    return $grants;  
}

function init_args()
{
	$iParams = array("testproject" => array(tlInputParameter::INT_N));
	$args = new stdClass();
	$pParams = G_PARAMS($iParams,$args);
	return $args;
}
?>