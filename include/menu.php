<nav class="navbar navbar-expand-lg navbar-light svs-bg">
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav">
      <li class="nav-item">
<?php printNavItem("query.php", "Tagesdaten-Grafik"); ?>
      </li>
      <li class="nav-item">
<?php printNavItem("wind_statistics_graphical.php", "Windstatistik-Grafik"); ?>
      </li>
      <li class="nav-item">
<?php printNavItem("wind_statistics_text.php", "Windstatistik-Tabelle"); ?>
      </li>
    </ul>
  </div>
</nav>
<?php 
function printNavItem($url, $text)
{
	$activeClass = "";
	$self = pathinfo(get_included_files()[0], PATHINFO_BASENAME);
	if ($self == $url)
	{
		$activeClass = " active";
	}
	echo '<a class="nav-link' . $activeClass . '" href="' . $url . '">' . $text . '</a>';
}
?>