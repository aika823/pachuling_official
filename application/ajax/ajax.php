<?php
  require_once 'AjaxModel.php';
  
  class Ajax extends AjaxModel
  {
    public $param;
    public $db;
    public $sql;
    
    public function __construct($param)
    {
      parent::__construct($param);
      $this->param = $param;
    }
  }
  
  $obj = new Ajax($_POST['param']);
  $date = $_POST['workDate'];
  $id = $_POST['companyID'];
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      
      case 'get_company_id':
        $result = $obj->getTable("SELECT `companyID` FROM `company` WHERE `companyName` = '{$_POST['name']}'")[0]['companyID'];
        if($result){
          echo $result;
        }
        else{
          $result['error'] = "올바른 상호명을 입력하세요";
          echo json_encode($result);
        }
        break;
      case 'get_join_type' :
        $id =$obj->select('company', "companyName = '{$_POST['name']}'", 'companyID');
        if($id){
          echo json_encode($obj->joinType($id, 'kor',$_POST['date']));
        }
        else{
          $result['error'] = "올바른 상호명를 입력하세요";
          echo json_encode($result);
        }
        break;
        
      case 'call' :
        echo $obj->call($_POST);
        break;
      case 'cancel':
        $obj->cancel($_POST);
        if ($obj->joinType($id) == 'gujwa') {
          $obj->reset($_POST);
        }
        break;
      case 'fix':
        echo $obj->fix($_POST);
        break;
      case 'getCompanyID':
        echo $obj->select('company', "companyName = '{$_POST['companyName']}'", 'companyID');
        break;
      case 'getEmployeeID':
        echo $obj->select('employee', "employeeName = '{$_POST['employeeName']}'", 'employeeID');
        break;
      case 'get_info':
        echo json_encode($obj->select($_POST['table'],"`{$_POST['table']}ID` = '{$_POST['id']}'")[0]);
        break;
      case 'deleteBlack':
        $obj->executeSQL("DELETE FROM `blackList` WHERE `blackList`.`blackListID` = {$_POST['blackID']} LIMIT 1");
        echo "삭제되었습니다.";
        break;
      case 'deleteAvailable':
        $obj->executeSQL("DELETE FROM `employee_available_date` WHERE `employee_available_date`.`availableDateID` = {$_POST['availableDateID']} LIMIT 1");
        echo "삭제되었습니다.";
        break;
      case 'bookmark':
        $table = $_POST['tableName'];
        $id = $_POST['id'];
        $bookmark = ($obj->select($table, "{$table}ID = {$id}", 'bookmark') == 1) ? 0 : 1;
        $string = "UPDATE `{$table}` SET `bookmark` = {$bookmark} WHERE `{$table}ID` = '{$id}' LIMIT 1";
        $obj->executeSQL($string);
        $imminent = $obj->select($table, "{$table}ID = {$id}", 'imminent');
        $result['bookmark'] = $bookmark;
        $result['imminent'] = $imminent;
        echo json_encode($result);
        break;
      case 'getMoney':
        $obj->getMoney($_POST);
        break;
      case 'toggleFilter':
        echo $obj->toggleFilter($_POST);
        break;
      case 'availableFilter':
        $result['arr'] = $obj->availableFilter($_POST)['arr'];
        $result['sql'] = $obj->availableFilter($_POST)['sql'];
        $result['post'] = $obj->availableFilter($_POST)['post'];
        echo json_encode($result);
        break;
      case 'assignFilter':
        echo json_encode($obj->assignFilter($_POST));
        break;
      case 'callFilter':
        echo json_encode($obj->callFilter($_POST));
        break;
      case 'assign':
        echo json_encode($obj->assign($_POST));
        break;
      case 'assignCancel':
        $sql = "UPDATE `call` SET `employeeID` = NULL WHERE `callID` = '{$_POST['callID']}' LIMIT 1";
        $obj->executeSQL($sql);
        echo $sql;
        break;
      case 'punk':
        $assign_cancel_sql = "UPDATE `call` SET `employeeID` = NULL WHERE `callID` = '{$_POST['callID']}' LIMIT 1";
        $employee_punk_sql = "INSERT INTO `punk` (`callID` ,`employeeID` ,`detail`) VALUES ('{$_POST['callID']}' ,  '{$_POST['employeeID']}',  '{$_POST['detail']}')";
        echo json_encode([$assign_cancel_sql,$employee_punk_sql]);
        $obj->executeSQL($assign_cancel_sql);
        $obj->executeSQL($employee_punk_sql);
        break;
      case 'fetchCallTable':
        $result['body'] = $obj->fetchCallTable($_POST)[0];
        $result['total'] = $obj->fetchCallTable($_POST)[1];
        echo json_encode($result);
        break;
      case 'alertDetail':
        echo $obj->select('call', "`callID` = '{$_POST['id']}'", 'detail');
        break;
      case 'callCancel':
        $obj->callCancel($_POST);
        echo json_encode($obj->reset($_POST));
        break;
      case 'delete':
        $table = $_POST['table'];
        $detail = $_POST['deleteDetail'];
        $id = $_POST['id'];
        $today = date('Y-m-d');
        $sql = "UPDATE `{$table}` SET `deleted` = 1, `activated` = 0, `imminent` = 0, `deleteDetail` = '{$detail}', `deletedDate` = '{$today}' WHERE `{$table}ID` = {$id} LIMIT 1";
        $sql2 = "UPDATE `join_{$table}` SET `deleted` = 1, `activated` = 0, `imminent` = 0, `deleteDetail` = '{$detail}' WHERE `{$table}ID` = {$id}";
        $obj->executeSQL($sql2);
        $obj->executeSQL($sql);
        break;
      case 'joinDelete':
        $table = $_POST['table'];
        $detail = $_POST['detail'];
        $id = $_POST['id'];
        $today = date('Y-m-d');
        $sql = "UPDATE `join_{$table}` SET `deleted` = 1, `activated` = 0, `imminent` = 0, `deleteDetail` = '{$detail}', `deletedDate` = '{$today}' WHERE `join_{$table}ID` = {$id} LIMIT 1";
        $obj->executeSQL($sql);
        break;
      case 'restore':
        $sql = "UPDATE `{$_POST['table']}` SET `deleted` = 0, `deleteDetail`= NULL, `deletedDate` = NULL WHERE `{$_POST['table']}ID` = {$_POST['id']} LIMIT 1";
        $obj->executeSQL($sql);
//        echo $sql;
        break;
      case 'checkDuplicate':
        $table = $_POST['table'];
        $tableName = $table . 'Name';
        
        if ($_POST['name']) {
          $name = $_POST['name'];
          $sql = "SELECT `{$tableName}` FROM `{$table}` WHERE `{$tableName}` LIKE '%{$name}%' ";
          $duplicateTable = $obj->getTAble($sql);
          $match = $obj->select($table, "`{$tableName}` = '{$name}'", $tableName);
          foreach ($duplicateTable as $value) {
            foreach ($value as $item) {
              $arr[] = $item;
            }
          }
          if ($arr) {
            $return['list'] = implode(' ', $arr);
            $return['match'] = $match;
          } else {
            $return['msg'] = "새로 추가 가능한 이름입니다";
          }
        } else {
          $return['msg'] = '이름을 입력 해 주세요';
        }
        echo json_encode($return);
        break;
      case 'matchCeo':
        $ceoName = $_POST['name'];
        if ($ceoName) {
          $result['ceoPhoneNumber'] = $obj->select('ceo', " `ceoName` = '$ceoName' ", 'ceoPhoneNumber');
          $result['ceoID'] = $obj->select('ceo', " `ceoName` = '$ceoName' ", 'ceoID');
          if ($result['ceoID'] && $result['ceoPhoneNumber']) {
            echo json_encode($result);
          }
        }
        break;
      case 'getLastJoinDate':
        $sql = "SELECT `endDate` FROM `join_employee` WHERE `employeeID`='{$_POST['id']}}' AND `deleted` = 0 ORDER BY `endDate` DESC LIMIT 1";
        $startDate = date('Y-m-d', strtotime($obj->getTable($sql)[0]['endDate']));
        $endDate = date("Y-m-d", strtotime("+1 31days", strtotime($startDate)));
        $result['startDate'] = $startDate;
        $result['endDate'] = $endDate;
        echo json_encode($result);
        break;
      case 'getCompanyJoinForm':
        switch ($_POST['type']) {
          default:
            echo <<<HTML
<div class="table table-add-join" id="companyAddJoinTable_gujwa">
        <div class="tr">
            <div class="td td-3">
                <label for="company-startDate">가입시작일</label>
                <input type="date" name="startDate" id="company-startDate" required>
            </div>
            <div class="td td-3">
                <label for="company-endDate">가입만기일</label>
                <input type="date" name="endDate" id="company-endDate" required>
            </div>
            <div class="td">
                <button type="button" class="btn btn-option" id="btn6Month">6개월</button>
                <button type="button" class="btn btn-option" id="btn1Year">1년</button>
            </div>
        </div>
        <div class="tr">
            <div class="td td-3">
                <label for="company-price">가입금액</label>
                <input type="number" name="price" id="company-price" required>
            </div>
            <div class="td td-3">
                <label for="company-joinDetail">가입비고</label>
                <textarea name="joinDetail" id="company-joinDetail">내용: &#10;작성자: </textarea>
            </div>
        </div>
    </div>
HTML;
            break;
          case 'deposit':
            echo <<<HTML
<div class="table table-add-join" id="companyAddJoinTable_deposit">
    <div class="tr">
        <div class="td">
            <label for="company-startDate">가입시작일</label>
            <input type="date" name="startDate" id="company-startDate" required>
        </div>
        <div class="td">
            <label for="company-deposit">보증금</label>
            <input type="number" name="deposit" id="company-deposit" required>
        </div>
    </div>
    <div class="tr">
        <div class="td">
            <label for="company-joinDetail">가입비고</label>
            <textarea name="joinDetail" id="company-joinDetail">내용: &#10;작성자: </textarea>
        </div>
    </div>
</div>
HTML;
            break;
          case 'point':
            echo <<<HTML
<div class="table table-add-join" id="companyAddJoinTable_point">
    <div class="tr">
        <div class="td">
            <label for="company-startDate">가입일</label>
            <input type="date" name="startDate" id="company-startDate" required>
        </div>
        <div class="td">
            <label for="company-price">가입금액</label>
            <input type="number" name="price" id="company-price" required>
        </div>
        <div class="td">
            <label for="company-point">포인트</label>
            <input type="number" name="point" id="company-point" required>
        </div>
    </div>
    <div class="tr">
        <div class="td">
            <label for="company-joinDetail">가입비고</label>
            <textarea name="joinDetail" id="company-joinDetail">내용: &#10;작성자: </textarea>
        </div>
    </div>
</div>
HTML;
            break;
        }
        break;
      case 'check_holiday':
        if ($_POST['date']) {
          $result['holiday'] = $obj->isHoliday($_POST['date']);
        } else {
          $result['msg'] = "no date";
        }
        echo json_encode($result);
        break;
      default :
        $result['msg'] = 'no matching action name';
        echo json_encode($result);
        break;
    }
  } else {
    $result['msg'] = 'no action';
    echo json_encode($result);
  }