<?php
  
  Class Model_company extends Model
  {
    public function __construct($param)
    {
      parent::__construct($param);
    }
    function action()
    {
//      header("Content-type:text/html;charset=utf8");
      switch ($_POST['action']) {
        case 'insert' :
          $_POST['userName'] = $_POST['companyName'];
          $_POST['userPW'] = $_POST['ceoPhoneNumber'];
          if(!$_POST['ceoID']){//새로운 사장 입력 시
            $this->insert('insert','ceo',$_POST);//사장 테이블에 입력
            $_POST['ceoID'] = $this->getLastID('ceo');
          }
          $this->insert('insert','company', $_POST);//거래처 테이블에 입력
          $_POST['companyID'] = $this->getLastID('company');
          $this->insert('insert','join_company',$_POST);//가입 테이블에 입력
          $this->insert('insert','user',$_POST);//유저 테이블에 입력
          foreach ($this->select('address') as $value){//존재하는 간단주소의 목록
            foreach ($value as $item){
              $addressTable[] = $item;
            }
          }
          if(!in_array($_POST['address'],$addressTable)){//새로운 간단주소 입력
            $this->insert('insert','address',$_POST);
          }
          foreach ($this->select('businessType') as $value){//존재하는 간단주소의 목록
            foreach ($value as $item){
              $businessTypeTable[] = $item;
            }
          }
          if(!in_array($_POST['businessType'],$businessTypeTable)){//새로운 간단주소 입력
            $this->insert('insert','businessType',$_POST);
          }
          $msg = "insert!";
          break;
        case 'update' :
          $this->insert('update', 'company', $_POST);
          $this->insert('update', 'ceo', $_POST);
          break;
        case 'new_insert':
          $_POST['companyID'] = $this->param->idx;
          $this->insert('addJoin', 'join_company', $_POST);
          break;
        case 'join_update':
          $joinID = $_POST['joinID'];
          $detail = $_POST['joinDetail'];
          $price = $_POST['price'];
          $this->executeSQL("UPDATE join_company SET `price`= '{$price}', `joinDetail` = '{$detail}' WHERE join_companyID = '{$joinID}' LIMIT 1");
          break;
        case 'update_call':
          $callID = $_POST['callID'];
          $detail = $_POST['detail'];
          $price = $_POST['price'];
          $this->executeSQL("UPDATE `call` SET `price` = '{$price}', `detail` = '{$detail}' WHERE `callID` = '{$callID}' LIMIT 1");
          break;
//        case 'callCancel':
//          $this->callCancel($_POST);
//          break;
      }
    }
  }