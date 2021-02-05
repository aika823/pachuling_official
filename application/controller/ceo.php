<?php
  
  class Ceo extends Controller
  {
    var $userID;
    var $companyID;
    var $companyData;
    var $callList;
    var $payList;
    var $joinData;
    var $leftDays;
    var $weekendCount;
    var $weekdayCount;
    var $callPriceList;
    var $pointList;
    var $callPrice;
    var $totalPoint;
    var $lastJoinDate;
    var $yearList;
    var $monthList;
    
    function __construct($param)
    {
      parent::__construct($param);
      $this->basic();
      $this->content();
    }
    
    function basic()
    {
      $this->companyID = $this->model->select('user', "userID = $this->userID", 'companyID');
      $this->companyData = $this->model->getTable("SELECT * from `company` WHERE companyID ='{$this->companyID}' LIMIT 1")[0];
      $this->joinData = $this->model->getTable("SELECT * FROM `join_company` WHERE companyID = '{$this->companyID}' AND activated = 1");
      $this->callList = $this->model->getTable("SELECT * FROM `call` WHERE companyID = '{$this->companyID}'");
      $this->payList = $this->model->getTable("SELECT * FROM `call` WHERE companyID = '{$this->companyID}' AND `price` >0 AND `cancelled`=0");
      
      $this->joinType = $this->model->joinType($this->companyID);
      
      function condition($array){return implode(' AND ', $array);}
      $basic    = "SELECT * FROM `call` LEFT JOIN `holiday` ON `call`.workDate = `holiday`.holiday WHERE ";
      $company  = " companyID ={$this->companyID} ";
      $thisweek = " YEARWEEK( workDate, 1 ) = YEARWEEK( CURDATE( ) , 1 )";
      $weekday  = " (NOT (DAYOFWEEK( workDate ) =7 OR DAYOFWEEK( workDate ) =1) AND holiday IS NULL)";
      $weekend  = " ((DAYOFWEEK( workDate ) =7 OR DAYOFWEEK( workDate ) =1) OR holiday IS NOT NULL)";
      $charged  = " (price > 0) ";
      $free     = " (price IS NULL OR price=0) ";
      $active   = " (cancelled = 0)";
      $this->weekdayCount     = $this->model->getTable($basic.condition([$company,$thisweek,$active,$weekday,$free]));
      $this->weekendCount     = $this->model->getTable($basic.condition([$company,$thisweek,$active,$weekend,$free]));
      $this->weekdayPaidCount = $this->model->getTable($basic.condition([$company,$thisweek,$active,$weekday,$charged]));
      $this->weekendPaidCount = $this->model->getTable($basic.condition([$company,$thisweek,$active,$weekend,$charged]));
      $this->callPriceList = $this->model->getTable("SELECT * FROM `call`  WHERE companyID =  '{$this->companyID}' AND price >=0 AND `cancelled`=0 AND `paid`=0");
      $this->callPrice = $this->addAll($this->callPriceList,'price');
      $this->pointList = $this->model->getTable("SELECT point FROM `join_company` WHERE companyID = '{$this->companyID}' AND startDate <= CURDATE() AND `activated` = 1 AND deleted = 0 AND point>0");
      $this->totalPoint = $this->addAll($this->pointList,'point');
      if ($this->model->joinType($this->companyID) == 'gujwa') {
        $this->lastJoinDate = $this->model->getTable("SELECT * FROM join_company WHERE companyID = '{$this->companyID}' ORDER BY endDate DESC")[0]['endDate'];
      } else $this->lastJoinDate = null;
      
      $monthTable = $this->model->getTable("
SELECT workDate
FROM  `call`
WHERE companyID ={$this->companyID}
GROUP BY YEAR( workDate ) , MONTH( workDate ) ");
      foreach ($monthTable as $key => $value){
        $year = date('Y',strtotime($value['workDate']));
        $month = date('n',strtotime($value['workDate']));
        
        $this->yearList[] = $year;
        $this->monthList[] = $month;
        $this->yearList = array_unique($this->yearList);
        $this->monthList = array_unique($this->monthList);
      }
    }
    
    function lastJoinDate($id)
    {
    
    }
    
    function dateFormat($array)
    {
      foreach ($array as $item) {
        $array2[] = "\"" . $item . "\"";
      }
      $date = implode(',', $array2);
      return $date;
    }
    
    function addAll($array,$column)
    {
      $sum = 0;
      foreach ($array as $value) $sum += $value[$column];
      return $sum;
    }
    
    function getDate($list)
    {
      foreach ($list as $key => $value) {
        $year = date('Y', strtotime($value['workDate']));
        $array[$year][] = date('m', strtotime($value['workDate']));
      }
      return $array;
    }
    
    function employeeName($id)
    {
      return $this->model->getTable("SELECT * FROM employee WHERE employeeID = '{$id}'")[0]['employeeName'];
    }
  }