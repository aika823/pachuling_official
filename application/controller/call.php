<?php
  
  class Call extends Controller
  {
    var $callList;
    var $employeeList;
    var $punkList;
    
    function __construct($param)
    {
      parent::__construct($param);
      $this->basic();
      $this->content();
    }
    
    function basic()
    {
      $this->companyID = $this->model->select('user', "userID = $this->userID", 'companyID');
      $this->callList = $this->getCallTable();
//      $this->fixList = $this->model->getTable("SELECT * FROM `fix`");
      $this->employeeList = $this->model->getTable("SELECT * FROM `employee` WHERE activated = 1");
    }
  }