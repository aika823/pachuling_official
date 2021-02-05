<?php
  
  class Model_ceo extends Model
  {
    var $userID;
    var $companyID;
    var $gujwaTable;
    var $pointTable;
    var $depositTable;
    var $error;
    
    function __construct($param)
    {
      parent::__construct($param);
      if (isset($_COOKIE['userID'])) $this->userID = $_COOKIE['userID']; else move("login");
      $this->companyID = $this->getTable("SELECT * FROM user WHERE `userID`= '{$this->userID}' LIMIT 1")[0]['companyID'];
    }
    
    function action()
    {
      switch ($_POST['action']) {
        case 'paidCall':
          $this->call($_POST);
          break;
        case 'reset':
          unset($_POST);
          move('ceo');
          break;
      }
    }
    
    function reset($post, $companyID)
    {
      $sql = "SELECT * FROM `call` WHERE `companyID`='{$companyID}' AND YEARWEEK( workDate, 1 ) = YEARWEEK( '{$post['workDate']}' , 1 ) AND `cancelled`=0 ORDER BY `workDate` ASC";
      $all = $this->getTable($sql);
      $max = 26000 * sizeof($this->gujwaTable);
      $point = 0;
      $this->executeSQL("UPDATE `call` SET `price`=NULL WHERE `companyID` = '{$companyID}'  AND YEARWEEK( workDate, 1 ) = YEARWEEK( '{$post['workDate']}' , 1 ) AND `cancelled`=0");
      for ($i = 0; $i < sizeof($all); $i++) {
        if ($this->isWeekend($all[$i]['workDate'])) $point += 10000;
        else $point += 8000;
        if ($point <= $max) $this->executeSQL("UPDATE `call` SET `price`=NULL WHERE `callID` = '{$all[$i]['callID']}' LIMIT 1");
        else $this->executeSQL("UPDATE `call` SET `price`=6000 WHERE `callID` = '{$all[$i]['callID']}' LIMIT 1");
      }
    }
  }