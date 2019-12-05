<?php
$_boxes = 12;
$_key   = $_GET['key'];
$_site  = 'index.php?monitoring=1&key='.$_key;
echo '
<nav class="navbar navbar-expand-lg navbar-absolute navbar-transparent">
    <div class="container-fluid">
       <div class="navbar-wrapper">
           <a class="navbar-brand" href="./index.php">Screenyl OSE Monitoring</a>
       </div>
    </div>
</nav>
<div class="content">';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if($_key == $securityToken){
  if($playerCount > 0){
    if (isset($_GET['next'])) $next = (int) $_GET['next'];
		else $next = 0;

    $para = $next + $_boxes;
    $n = 0;

    $playerSQL 		= $db->query("SELECT * FROM player");
    while($count = $playerSQL->fetchArray(SQLITE3_ASSOC)) $n++;

    if($para < $n) $site = $_site.'&next='.$para;
		else $site = $_site;

    $current_site = $para / $_boxes;
    $total_site = ceil($n / $_boxes);

    $pagination = 'Site '.$current_site.' of '.$total_site.' - ';

    redirect($site, 10);


    $playerSQL 		= $db->query("SELECT * FROM player ORDER BY name LIMIT ".$next.",".$_boxes);
    echo'
    <div class="row">
    ';
    while($player = $playerSQL->fetchArray(SQLITE3_ASSOC)){
      if($player['name'] == ''){
        $name	 			= 'No Player Name';
        $imageTag 	= 'No Player Name '.$player['playerID'];
      }
      else {
        $name 			= $player['name'];
        $imageTag 	= $player['name'];
      }
      echo'
      <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
        <div class="card">
          <div class="card-header">
            <h4 class="d-inline">'.$name.'</h4>
            <h5>'.$player['address'].'</h5>
          </div>
          <div class="card-body card-monitor">
            <img class="player" src="'.playerImage($player['address']).'" alt="'.$imageTag.'">
          </div>
        </div>
      </div>
      ';
    }
    echo '
    </div>
  </div>
  ';
  }
  else sysinfo('warning', 'No Player available!');
}
else sysinfo('danger', 'Token incorrect - Access denied!');