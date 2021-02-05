<?php

  class Model_manage extends Model
  {
    function __construct($param)
    {
      parent::__construct($param);
    }
    
    function action(){
      if(isset($_POST)){
        switch($_POST['action']){
          case 'black':
            $employeeName = $_POST['employeeName'];
            $companyName = $_POST['companyName'];
            $detail = $_POST['detail'];
            $type = $_POST['type'];
            $employeeID = $this->select('employee', "employeeName = '{$employeeName}'",'employeeID');
            $companyID = $this->select('company', "companyName = '{$companyName}'",'companyID');
            $this->executeSQL("INSERT INTO blackList SET employeeID = '{$employeeID}', companyID = '{$companyID}', detail = '{$detail}', ceoReg = '{$type}'");
            break;
        }
        unset($_POST);
      }
    }
  }