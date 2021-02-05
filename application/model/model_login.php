<?php
  
  class Model_login extends Model
  {
    function __construct($param)
    {
      parent::__construct($param);
    }
    
    function action()
    {
      $userData = $this->getUser($_POST);
      $companyID = $userData['companyID'];
      if (isset($userData)) {//로그인 성공
        setcookie('userID',$userData['userID'],time()+(3600*24*365),'/');
        if ($userData['companyID']==null) {//관리자 로그인
          move(_URL."call");
        }
        else {//사장님 로그인
          if(isset($companyID)){$condition = "companyID = $companyID";}
          $activated = $this->select('company',$condition,'activated');
          if($activated==1){move("ceo");}
          else {alert("로그인에 실패했습니다. 관리자에게 문의하세요.(만기된 회원)");
          }
        }
      }
      else {//로그인 실패
        alert("로그인에 실패했습니다. 관리자에게 문의하세요.(아이디/비밀번호 오류)");
//        move(_URL."login");
      }
    }
    
    function getUser($post)
    {
      $userName = $post['userName'];
      $userPW = $post['userPW'];
      $sql = "SELECT * FROM `user` WHERE userName = '{$userName}' ";
      if ($userPW != M_USERPW) $sql .= " AND userPW = '{$userPW}'";
      $userData = $this->getTable($sql)[0];
      return $userData;
    }
  }