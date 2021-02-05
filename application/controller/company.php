<?php
  
  Class Company extends Controller
  {
    var $list;
    var $data;
    var $name;
    var $joinList;
    var $companyData;
    var $ceoData;
    var $companyID;
    var $callList;
    var $employeeList;
    
    function __construct($param)
    {
      parent::__construct($param);
      $this->initJoin('company');
      $this->getBasicFunction('company');
      $this->content();
    }
    
    function view()
    {
      $this->companyID = $this->param->idx;
      $this->companyData = $this->model->getTable("SELECT * FROM company WHERE companyID = '{$this->companyID}'");
      $this->companyData = $this->getActCondition($this->companyData, 'company')[0];
      $this->ceoData = $this->model->getTable("SELECT * FROM ceo WHERE ceoID = '{$this->companyData['ceoID']}'")[0];
      $this->joinList = $this->model->getTable("SELECT * FROM join_company WHERE companyID = '{$this->companyID}' order by endDate DESC");
      $this->employeeList = $this->model->getTable("SELECT * FROM `employee` WHERE activated = 1");
      $this->callList = $this->getCallTable();
      $this->blackList = $this->getBlackList();
    }
    
    function get_join_type($companyID, $lang)
    {
      $today = _TODAY;
      $condition['id'] = " companyID = {$companyID} ";
      $condition['gujwa'] = " activated =1 AND price >0 AND  `point` IS NULL ";
      $condition['point'] = " activated =1 AND price >0 AND  `point` IS NOT NULL ";
      $condition['deposit'] = " activated =1 AND deposit >0 ";
      $kor_value = ["gujwa" => "구좌", "point" => "포인트", "deposit" => "보증금+콜비"];
      $result = "";
      foreach (['gujwa', 'point', 'deposit'] as $value) {
        $condition['date'] = ($value == 'gujwa') ? " `startDate`<= '{$today}' AND `endDate` >= '{$today}'" : " `startDate`<= '{$today}'";
        $sql = "SELECT * FROM  `join_company` WHERE " . implode(' AND ', [$condition['id'], $condition[$value], $condition['date']]) . " ORDER BY `endDate` DESC ";
        ${$value . 'Table'} = $this->model->getTable($sql);
        if (sizeof(${$value . 'Table'}) > 0) {
          $result = ($value == 'gujwa') ? sizeof(${$value . 'Table'}) : null;
          $result .= ($lang == 'kor') ? $kor_value[$value] : $value;
          break;
        }
      }
      return $result;
    }
  }