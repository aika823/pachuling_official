<?php
  
  class Model_call extends Model
  {
    var $group1;
    var $group1_list;
    var $group2;
    var $group3;
    
    function __construct($param)
    {
      parent::__construct($param);
    }
    
    function action()
    {
      header("Content-type:text/html;charset=utf8");
      if (isset($_POST)) {
        switch ($_POST['action']) {
          case 'update_call':
            $callID = $_POST['callID'];
            $detail = $_POST['detail'];
            $price = $_POST['price'];
            $this->executeSQL("UPDATE `call` SET `price` = '{$price}', `detail` = '{$detail}' WHERE `callID` = '{$callID}' LIMIT 1");
            break;
//          case 'punk':
//            $employeeID = $this->select('employee', "`employeeName` = '{$_POST['employeeName']}'", 'employeeID');
//            $this->executeSQL("INSERT INTO `punk` SET `callID` = '{$_POST['callID']}', `employeeID` = '{$employeeID}', `detail`='{$_POST['detail']}'");
//            $this->executeSQL("UPDATE `call` SET `employeeID` = NULL WHERE `callID` = '{$_POST['callID']}' LIMIT 1");
//            break;
//          case 'callCancel':
//            $this->callCancel($_POST);
//            break;
//          case 'fixCancel':
//            $this->fixCancel($_POST);
//            break;
//          case 'assignCancel':
//            $this->executeSQL("UPDATE `call` SET employeeID = NULL WHERE `callID` = '{$_POST['callID']}' LIMIT 1");
//            break;
//          case 'call':
//            $this->call($_POST);
//            break;
        }
      }
    }
    
    function workTimeType($data)
    {
      $start = $data['startTime'];
      $end = $data['endTime'];
      $workTime = $end - $start;
      if ($workTime >= 10) $result = '종일';
      else {
        if ($start < 12) $result = '오전'; else $result = '오후';
      }
      return $result;
    }
  }