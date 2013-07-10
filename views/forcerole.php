<?php
if(!defined('APPLICATION')) die();

?>
<style type="text/css">
.InformMessages {
  display: none !important;
}
</style>
<h1><?php echo T('Select a role to continue'); ?></h1>
<div class="forcerole">
  <ul>
<?php
   $Alt = FALSE;
   foreach ($this->Roles->Result() as $Role) {
     $Alt = $Alt ? FALSE : TRUE;
     echo '<li>';
     echo '<a href="'.Url("/profile/setRole/".$Role['RoleID']).'">'.$Role['Name'].'</a>';
     echo '</li>';
   }
?>
  </ul>
</div>