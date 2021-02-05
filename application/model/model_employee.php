<?php
  
  Class Model_employee extends Model
  {
    public function __construct($param)
    {
      parent::__construct($param);
    }
    function action()
    {
      header("Content-type:text/html;charset=utf8");
      switch ($_POST['action']) {
        case 'insert' :
          $this->insert('insert','employee', $_POST);//구직자 테이블에 입력
          foreach ($this->select('address') as $value){//존재하는 간단주소의 목록 확인
            foreach ($value as $item){
              $addressTable[] = $item;
            }
          }
          if($_POST['address']){
            if(!in_array($_POST['address'],$addressTable)){//새로운 간단주소
              $this->insert('insert','address',$_POST);
            }
          }
          if($_POST['workPlace']){
            if(!in_array($_POST['workPlace'],$addressTable)){//새로운 간단주소
              $_POST['address'] = $_POST['workPlace'];
              $this->insert('insert','address',$_POST);
            }
          }
          foreach ($this->select('workField') as $value){//존재하는 업종의 목록 확인
            foreach ($value as $item){
              $workFieldTable[] = $item;
            }
          }
          if($_POST['workField1']){
            if(!in_array($_POST['workField1'],$workFieldTable)){
              $_POST['workField'] = $_POST['workField1'];
              $this->insert('insert','workField',$_POST);//업종 테이블에 입력
            }
          }
          if($_POST['workField2']){
            if(!in_array($_POST['workField2'],$workFieldTable)){
              $_POST['workField'] = $_POST['workField2'];
              $this->insert('insert','workField',$_POST);//업종 테이블에 입력
            }
          }
          if($_POST['workField3']){
            if(!in_array($_POST['workField3'],$workFieldTable)){
              $_POST['workField'] = $_POST['workField3'];
              $this->insert('insert','workField',$_POST);//업종 테이블에 입력
            }
          }
          
          $_POST['employeeID'] = $this->getLastID('employee');
          $this->insert('insert','join_employee',$_POST);//join_employee 테이블 입력
          $this->insert('insert','employee_available_day',$_POST);//employee_available_day 테이블 입력
          $msg = "입력되었습니다!!!";
          break;
        case 'update' :
          $this->insert('update', 'employee', $_POST);
          $this->insert('update', 'employee_available_day', $_POST, 'employee', $this->param->idx);
          break;
        case 'new_insert':
          $_POST['employeeID'] = $this->param->idx;
          $this->insert('addJoin', 'join_employee', $_POST);
          break;
        case 'insert_day':
//          $_POST['employeeID'] = $this->getTable("SELECT employeeID from employee WHERE employeeName = '{$_POST['employeeName']}' ")[0]['employeeID'];
          $_POST['employeeID'] = $this->select('employee'," `employeeName` = '{$_POST['employeeName']}' ",'employeeID');
          $this->insert('insert','employee_available_date',$_POST);
          alert('입력 되었습니다.');
          break;
        case 'join_update':
          $joinID = $_POST['joinID'];
          $detail = $_POST['joinDetail'];
          $price = $_POST['price'];
          $this->executeSQL("UPDATE join_employee SET `price` = '{$price}', `joinDetail` = '{$detail}' WHERE join_employeeID = '{$joinID}' LIMIT 1");
          break;
        case 'update_call':
          $callID = $_POST['callID'];
          $detail = $_POST['detail'];
          $price = $_POST['price'];
          $this->executeSQL("UPDATE `call` SET `price` = '{$price}', `detail` = '{$detail}' WHERE `callID` = '{$callID}' LIMIT 1");
          break;
      }
//      unset($_POST);
      if(isset($msg)) alert($msg);
    }
  }