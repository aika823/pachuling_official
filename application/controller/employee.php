<?php
  
  Class Employee extends Controller
  {
    var $list;
    var $data;
    var $joinList;
    var $dayList;
    var $employeeData;
    var $employeeID;
    var $callList;
    var $employeeList;
    var $blackList;
    
    function __construct($param)
    {
      parent::__construct($param);
      $this->initJoin('employee');
      $this->getBasicFunction('employee');
      $this->content();
    }
    
    function view()
    {
      $this->employeeID = $this->param->idx;
      $this->employeeData = $this->model->getTable("SELECT * FROM employee WHERE employeeID = '{$this->employeeID}'");
      $this->employeeData = $this->getActCondition($this->employeeData, 'employee')[0];
      $this->dayList = $this->model->getTable("SELECT * FROM employee_available_day WHERE employeeID = '{$this->employeeID}'");
      $this->joinList = $this->model->getTable("SELECT * FROM join_employee WHERE employeeID = '{$this->employeeID}' order by endDate DESC");
      $this->callList = $this->getCallTable();
      $this->punkList = $this->model->getTable(
        "SELECT callID, punk.employeeID, companyID, workDate, startTime, endTime, workField, salary, call.detail as detail, punk.detail as punkDetail
         FROM  `punk` LEFT JOIN `call` USING (callID) WHERE punk.employeeID = '{$this->employeeID}'");
      $this->employeeList = $this->model->getTable("SELECT * FROM `employee` WHERE activated = 1");
      $this->blackList = $this->getBlackList();
    }
    
    function getDay($day, $type)
    {
      if (isset($type)) {
        if ($type != '종일') {
          if (($this->dayList[0][$day] == $type) || ($this->dayList[0][$day] == '반반')) return "checked";
        } else {
          if ($this->dayList[0][$day] == $type)
            return "checked";
        }
      } else return $this->dayList[0][$day];
    }
  }