<?php
require_once(__DIR__ . '/../config/functions.php');

Class Controller extends Functions
{
    var $param;
    var $userID;
    var $model;
    var $list;
    var $title;
    var $setAjax;
    var $keyword;
    var $join;
    var $day;
    var $tables;
    var $tableName;
    //필터 버튼 조건
    public $defaultCondition = " (deleted = 0) ";
    public $activatedCondition = " (activated = 1 AND deleted = 0) ";
    public $imminentCondition = " (bookmark = 1 OR imminent = 1) ";
    public $deactivatedCondition = " (activated = 0 AND deleted = 0) ";
    public $deletedCondition = " (activated = 0 AND deleted = 1) ";

    //생성자
    function __construct($param)
    {
        header("Content-type:text/html;charset=utf8");
        $this->param = $param;
        if (isset($_COOKIE['userID'])) $this->userID = $_COOKIE['userID'];
        $modelName = "Model_{$this->param->page_type}";//Model 객체 생성
        $this->model = new $modelName($this->param);

        $this->getFunctions();
        $method = isset($this->param->action) ? $this->param->action : null;
        if (method_exists($this, $method)) $this->$method();
        require_once(_VIEW . "common/header.php");
    }

    //선택한 테이블들의 모든 데이터를 불러와서 table_List 배열 생성
    function getFunctions()
    {
        // $this->tables = array('company', 'ceo', 'employee', 'call', 'address', 'businessType', 'workField', 'call', 'employee_available_date', 'fix');
        // foreach ($this->tables as $value) {
        //   $this->{$value . '_List'} = $this->model->select($value);
        // }
        $this->tableName = $this->param->page_type;
        if ($this->param->action == 'available_date') {
            $this->thisWeekCondition = "(YEARWEEK( availableDate, 1 ) = YEARWEEK( CURDATE( ) , 1 )) OR (YEARWEEK( notAvailableDate, 1 ) = YEARWEEK( CURDATE( ) , 1 ))";
            $this->thisMonthCondition = "(YEAR(availableDate) = YEAR(CURDATE()) AND MONTH(availableDate) = MONTH(CURDATE())) OR (YEAR(notAvailableDate) = YEAR(CURDATE()) AND MONTH(notAvailableDate) = MONTH(CURDATE()))";
        }
    }

    function getBasicFunction($tableName)
    {
        if(isset($_POST['keyword'])){
            $this->keyword = $_POST['keyword'];
        }
        else{
            $this->keyword = null;
        }
        if(isset($_POST['order'])){
            $this->order = $_POST['order'];
        }
        else{
            $this->order = null;
        }
        if(isset($_POST['direction'])){
            $this->direction = $_POST['direction'];
        }
        else{
            $this->direction = null;
        }
        
        

        if (isset($_POST['filter'])) {
            switch ($_POST['filter']) {
                case 'all':
                    $condition[] = $this->defaultCondition;
                    break;
                case 'activated':
                    $condition[] = $this->activatedCondition;
                    break;
                case 'imminent':
                    $condition[] = $this->imminentCondition;
                    break;
                case 'deactivated':
                    $condition[] = $this->deactivatedCondition;
                    break;
                case 'deleted':
                    $condition[] = $this->deletedCondition;
                    break;
            }
        } else {
            $_POST['filter'] = 'activated';
            $condition[] = $this->activatedCondition;
        }
        if (isset($_POST['keyword']) && $_POST['keyword'] != "") {
            $condition[] = " (`{$tableName}Name` LIKE '%{$this->keyword}%' OR `address` LIKE '%{$this->keyword}%' OR `detail` LIKE '%{$this->keyword}%') ";
        }

        if(isset($_POST['order'])){
            $order = ($_POST['order']);
        }
        else{
            $order = $this->param->page_type . "ID";
        }    
        if(isset($_POST['direction'])){
            $direction = ($_POST['direction']);
        }
        else{
            $direction = "DESC";
        }
        $this->list = $this->model->getList($condition, $order, $direction);
//      초기화 테스트
        $this->initJoin($tableName);
        $this->list = $this->initActCondition($this->list, $tableName);
        $this->list = $this->getActCondition($this->list, $tableName);
    }

    function getCallTable()
    {
        $table = $this->param->page_type;
        $today = _TODAY;
        // initialize condition variable
        $condition['date'] = null;
        $condition['year'] = null;
        $condition['month'] = null;
        $condition['week'] = null;
        if($table == 'employee'){
            $sql ="
SELECT *, call.employeeID as employeeID, punk.employeeID as punk_employeeID, call.detail as detail, punk.detail as punk_detail
FROM `call` LEFT JOIN  `punk` USING ( callID ) ";
            $condition['employee'] = " (`call`.`employeeID` = '{$this->param->idx}' OR `punk`.`employeeID` = '{$this->param->idx}') ";
        }
        else{
            //$sql = "SELECT * FROM `call` ";
            $sql = "
 SELECT
 callID,
 call.companyID as companyID, call.detail AS detail, call.deleted as deleted,
 workDate, workField, startTime, endTime, salary,
 price, employeeID, point,paid, receiver, fixID, cancelled, cancelDetail
 FROM `call` LEFT JOIN `company` USING (companyID) ";
        }
        if ($table == 'company') $condition['company']= " `call`.`companyID` = '{$this->param->idx}' ";
        if(isset($_POST['date'])) $condition['date'] =  " `call`.`workDate` = '{$_POST['date']}'";
        $year   = (isset($_POST['year'])) ?  $_POST['year'] : date('Y', strtotime(_TODAY));
        $month  = (isset($_POST['month'])) ? $_POST['month'] : date('m', strtotime(_TODAY));
        if(isset($_POST['year']))  $condition['year']    = " (YEAR(workDate) = '{$year}' )";
        if(isset($_POST['month'])) $condition['month']   = " (YEAR(workDate) = '{$year}' AND MONTH(workDate) = '{$month}') ";
        if(isset($_POST['week']))  $condition['week']    = " (YEARWEEK(workDate, 1)) = (YEARWEEK(curdate(), 1))";
        if(! ($condition['date'] || $condition['year'] || $condition['month'] || $condition['week'] )){
            $condition['date'] = " `workDate` = '{$today}' ";
        } 
        if(isset($_POST['order'])){
            $direction = ($_POST['direction']) ? $_POST['direction'] : "ASC";
            $order = " ORDER BY {$_POST['order']} {$direction} ";
        }
        else{
            $order = ($this->param->page_type == 'call') ? " ORDER BY `callID` DESC" : " ORDER BY `workDate` ASC";
        }
        $sql .= ($condition) ? " WHERE ".implode(' AND ', array_filter($condition)) : null;
        $sql .= $order;
        return $this->model->getTable($sql);
    }

    function getBlackList()
    {
        $tbl = $this->tableName;
        return $this->model->getTable("SELECT * FROM `blackList` WHERE `{$tbl}ID` = '{$this->param->idx}' ORDER BY `ceoReg` DESC");
    }

    function get_fixType($data, $class=false)
    {
        if ($data['fixID'] > 0) {
            if ($this->model->select('fix', "`fixID`='{$data['fixID']}'", 'monthlySalary') > 0) {
                if($class) return 'salary';
                else return '(월급)';
            } else {
                if($class) return 'fixed';
                else return '(고정)';
            }
        }
        else{
            if($class) return 'normal';
            else return null;
        }
    }

    function getClass($data)
    {
        if ($data['deleted'] == 0) {
            if ($data['activated'] == 1) {
                if ($data['imminent'] == 1 OR $data['bookmark'] == 1) {
                    return 'imminent';
                } else {
                    return null;
                }
            } else {
                if ($data['bookmark'] == 1) {
                    return 'imminent';
                } else {
                    return 'deactivated';
                }
            }
        } else return 'deleted';
    }

    function get_punk_list($callID){
        return $this->model->getTable("SELECT * FROM `punk` WHERE `callID` = '{$callID}'");
    }

    function get_address_list(){
        return $this->model->getTable("SELECT address FROM address");
    }

    function get_ceo_list(){
        return $this->model->getTable("SELECT ceoName FROM ceo");
    }

    function get_employee_list(){
        return $this->model->getTable("SELECT employeeName FROM employee");
    }

    function get_workfield_list(){
        return $this->model->getTable("SELECT workField FROM workField");
    }

    function get_business_type_list(){
        return $this->model->getTable("SELECT businessType FROM businessType");
    }
}