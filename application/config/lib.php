<?php
  define("_TODAY", date("Y-m-d"));
  define("_TOMORROW", date('Y-m-d', strtotime('+1 day')));
  define("DAYOFWEEK", serialize(array('일', '월', '화', '수', '목', '금', '토')));

//Script Functions
  function alert($str)
  {
    echo "<script>alert('{$str}');</script>";
  }
  
  function getLog($str)
  {
    echo "<script>console.log('{$str}');</script>";
  }
  
  function move($str = false)
  {
    echo "<script>";
    echo $str ? "document.location.replace('{$str}');" : "history.back();";
    echo "</script>";
    exit;
  }

//Class Auto Load
  function __autoload($className)
  {
    $className = strtolower($className);
    $className2 = preg_replace('/(model|application)(.*)/', "$1", $className);
    switch ($className2) {
      case 'application'  :
        $dir = _APP;
        break;
      case 'model'        :
        $dir = _MODEL;
        break;
      default             :
        $dir = _CONTROLLER;
        break;
    }
    require_once("{$dir}{$className}.php");
  }
  
  function getAge($date)
  {
    $birthDate = new DateTime($date);
    $now = new DateTime();
    $difference = $now->diff($birthDate);
    $age = $difference->y;
    return $age;
  }
  
  function leftDays($date)
  {
    $datetime1 = new DateTime(_TODAY);
    $datetime2 = new DateTime($date);
    $interval = $datetime2->diff($datetime1);
    return $interval->format('%R%a');
  }