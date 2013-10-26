<?php
st();
require __DIR__ . '/../config/boot.php';
MyImouto\Application::dispatchRequest();

if (!preg_match('/\.(css|js|json)$/', Rails::application()->dispatcher()->request()->path()) && Rails::application()->dispatcher()->request()->isGet()) {
    // st(1);
    // echo ' ';
    // mu();
}

function st($end = false) {
  static $starttime;
  
  $mtime = microtime(); 
  $mtime = explode(" ",$mtime); 
  $mtime = $mtime[1] + $mtime[0]; 
  
  if (!$end) {
    $starttime = $mtime;
  } else {
    $endtime = $mtime; 
    $totaltime = ($endtime - $starttime); 
    echo $totaltime;
  }
}
function mu() {
  echo 'Memory usage: '.number_to_human_size(memory_get_usage());
}
function number_to_human_size($bytes){ 
	$size = $bytes / 1024; 
	if($size < 1024){ 
		$size = number_format($size, 1); 
		$size .= ' KB'; 
	} else { 
		if($size / 1024 < 1024){ 
				$size = number_format($size / 1024, 1); 
				$size .= ' MB'; 
		} else if ($size / 1024 / 1024 < 1024) { 
				$size = number_format($size / 1024 / 1024, 1); 
				$size .= ' GB'; 
		}  
	} 
	return $size; 
}

