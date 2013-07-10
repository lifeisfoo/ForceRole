<?php
if(!defined('APPLICATION')) die();

$PluginInfo['ForceRole'] = array(
	'Name' => 'Force Role',
	'Description' => 'Force logged user to select a role from a roles list (taken from the RegistrationRole plugin).',
	'Version' => '0.1.1',
	'RequiredApplications' => array('Vanilla' => '2.0.18'),
	'RequiredTheme' => FALSE,
	'RequiredPlugins' => array('RegistrationRole' => '>=0.1'),
	'SettingsUrl' => FALSE,
	'SettingsPermission' => 'Garden.Settings.Manage',
	'Author' => "Alessandro Miliucci",
	'AuthorEmail' => 'lifeisfoo@gmail.com',
	'AuthorUrl' => 'http://forkwait.net',
	'License' => 'GPL v3'
);

class ForceRolePlugin extends Gdn_Plugin{
public function Base_Render_Before($Sender) {
    $Sender->SetData('TotalThreads', 12345);
}
  public function Base_AfterAnalyzeRequest_Handler($Sender){
    if(!($Sender->EventArguments['Controller'] instanceof EntryController)){
      if(Gdn::Session()->IsValid() 
        && !Gdn::Session()->User->Admin
        && strpos(Gdn::Request()->GetValue('REQUEST_URI'), 'selectRole') == false
        && strpos(Gdn::Request()->GetValue('REQUEST_URI'), 'profile/setRole') == false
        && self::needRole(Gdn::Session()->UserID)
      ){
        //if UserRoles don't contains one of RegistrationRoles, his request is redirected to role selector page
        Redirect('/selectRole');
      }
    }
  }

  /**
   * Return true if the user don't have admin,moderator or a selected role
   */
  private static function needRole($UserID){
    $UserRoles = Gdn::UserModel()->GetRoles(intval($UserID))->Result();
    $Roles = self::availableRoles()->Result();
    $NeedRole = true;
    foreach ($UserRoles as $Role) {
      if($Role['Name'] == 'Moderator'){
        $NeedRole = false;
        break;
      }elseif($Role['Name'] == 'Administrator'){
        $NeedRole = false;
        break;
      }elseif (in_array($Role, $Roles)) {
        $NeedRole = false;
        break;
      }
    }
    return $NeedRole;
  }

  /**
   * Return available roles for internal plugin use
   */
  private static function availableRoles() {
    $RoleNames = self::registrationRolesNames();
    $RolesDataArray = Gdn::SQL()->Select('r.RoleID, r.Name')
                        ->From('Role r')
                        ->WhereIn('r.Name', $RoleNames)
                        ->Get()->Result(DATASET_TYPE_ARRAY);
    return new Gdn_DataSet($RolesDataArray);
  }

  /**
  * Return true if the role is in the selected list
  */
  private static function isAvailable($RoleID) {
    $Roles = self::availableRoles()->Result();
    $Av = false;
    foreach ($Roles as $Role) {
      if($Role['RoleID'] == $RoleID){
        $Av = true;
        break;
      }
    }
    return $Av;
  }

  /**
   * Return selected registration roles
   */
  private static function registrationRolesNames() {
    $RoleNames = array();
    foreach (C('Plugins.RegistrationRole') as $Key => $Value) {
      if( strcmp(C('Plugins.RegistrationRole.' . $Key, '0'), '1') == 0 ){
        array_push($RoleNames, self::denormalizeName($Key));
      }
    }
    return $RoleNames;
  }

  /**
   * Replace underscores with whitespaces
   */
  private static function denormalizeName($CatName){
    return str_replace("_", " ", $CatName);
  }

  private function FillUserField($Sender){
    $Sender->User = Gdn::UserModel()->GetID(Gdn::Session()->UserID);
  }

  public function ProfileController_ForceRole_Create($Sender){
    //protect agains direct page access
    if(Gdn::Session()->IsValid() && self::needRole(Gdn::Session()->UserID)){
      $this->FillUserField($Sender);
      $Sender->Roles = self::availableRoles();
      $Sender->Render(dirname(__FILE__) . DS . 'views' . DS . 'forcerole.php');
    }else{
      Redirect('/');
    }
  }

  public function ProfileController_SetRole_Create($Sender){
    //protect agains direct page access
    if(Gdn::Session()->IsValid() && self::needRole(Gdn::Session()->UserID)){
      $this->FillUserField($Sender);
      $UserID = Gdn::Session()->UserID;
      $RoleID = $Sender->RequestArgs[0];
      if(Gdn::Session()->IsValid() && self::isAvailable($RoleID) && self::needRole($UserID)){
        $CurrentRoles = Gdn::UserModel()->GetRoles($UserID);
        $RolesToSave = "";
        foreach ($CurrentRoles as $ARole) {
          $RoleName = GetValue('Name', $ARole);
          //remove member role from default roles (if present and if setting's selected)
          if( strcmp(trim($RoleName),'Member') != 0){
            $RolesToSave .= $RoleName. ',';
          }else{//if is member role and if needs to be removed
            if( strcmp(C('Plugins.RegistrationRole.RemoveMemberRole', '0'), '1') == 0 ){
              
            }else{
              $RolesToSave .= $RoleName. ',';
            }
          }
        }
      
        //Add selected role
        $RoleModel = new RoleModel();
        $RolesToSave .= GetValue('Name', $RoleModel->GetByRoleID($RoleID));

        //SaveRoles expect a string like "Moderator, Member, ..." see class.usermodel.php
        Gdn::UserModel()->SaveRoles($UserID, $RolesToSave);
      }
    }
    Redirect('/');
  }
  
  public function Setup() {
    $this->Structure();
  }

  public function OnDisable() {
    Gdn::Router()->DeleteRoute('selectRole');
  }
  
  public function Structure() {
    Gdn::Router()->SetRoute('selectRole', '/profile/ForceRole', 'Internal');
  }
  
}
?>
