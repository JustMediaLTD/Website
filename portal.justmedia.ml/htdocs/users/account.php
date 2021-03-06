<?php
/*
UserSpice 4
An Open Source PHP User Management System
by the UserSpice Team at http://UserSpice.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
?>
<?php require_once 'init.php'; ?>
<?php require_once $abs_us_root.$us_url_root.'users/includes/header.php'; ?>
<?php require_once $abs_us_root.$us_url_root.'users/includes/navigation.php'; ?>

<?php if (!securePage($_SERVER['PHP_SELF'])){die();}?>
<?php


//Widget Loader



//dealing with if the user is logged in
if($user->isLoggedIn() || !$user->isLoggedIn() && !checkMenu(2,$user->data()->id)){
	if (($settings->site_offline==1) && (!in_array($user->data()->id, $master_account)) && ($currentPage != 'login.php') && ($currentPage != 'maintenance.php')){
		$user->logout();
		Redirect::to($us_url_root.'users/maintenance.php');
	}
}
$grav = get_gravatar(strtolower(trim($user->data()->email)));
$get_info_id = $user->data()->id;
// $groupname = ucfirst($loggedInUser->title);
$raw = date_parse($user->data()->join_date);
$signupdate = $raw['month']."/".$raw['day']."/".$raw['year'];
$userdetails = fetchUserDetails(NULL, NULL, $get_info_id); //Fetch user details
 ?>
 <script>
   (function (w,i,d,g,e,t,s) {w[d] = w[d]||[];t= i.createElement(g);
     t.async=1;t.src=e;s=i.getElementsByTagName(g)[0];s.parentNode.insertBefore(t, s);
   })(window, document, '_gscq','script','//widgets.getsitecontrol.com/113186/script.js');
 </script>

<div id="page-wrapper">
<div class="container">
<div class="well">
<div class="row">
	<div class="col-xs-12 col-md-3">
		<p><img src="<?=$grav; ?>" class="img-thumbnail" alt="Generic placeholder thumbnail"></p>
		<p><a href="user_settings.php" class="btn btn-primary">Edit Account Info</a></p>
		<p><a class="btn btn-primary " href="profile.php?id=<?=$get_info_id;?>" role="button">Public Profile</a></p>

	</div>
	<div class="col-xs-12 col-md-9">
		<h1><?=echousername($user->data()->id)?></h1>
		<p><?=ucfirst($user->data()->fname)." ".ucfirst($user->data()->lname)?> / <?=echouser($user->data()->id)?></p>
		<p>Member Since:<?=$signupdate?></p>
		<p>Number of Logins: <?=$user->data()->logins?></p>
		<p>The payment logs & information have moved. <a href="payment.php" class="btn btn-primary">Click here to go there.</a> </p>
	</div>
</div>
</div>

</div> <!-- /container -->

</div> <!-- /#page-wrapper -->

<!-- footers -->
<?php require_once $abs_us_root.$us_url_root.'users/includes/page_footer.php'; // the final html footer copyright row + the external js calls ?>

<!-- Place any per-page javascript here -->

<?php require_once $abs_us_root.$us_url_root.'users/includes/html_footer.php'; // currently just the closing /body and /html ?>
