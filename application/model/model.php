<?php
  
  Class Model
  {
    public $db;
    public $param;
    public $sql;
    
    public function __construct($param)
    {
      $this->param = $param;
      //올바른 로그인인지 체크
      if (!isset ($_POST['ajax'])) {
        $this->check_login();
      }
      //데이터베이스 설정
      $this->db = new PDO("mysql:host=" . _SERVERNAME . ";dbname=" . _DBNAME . "", _DBUSER, _DBPW);
      $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
      $this->db->exec("set names utf8");
      if (isset($_POST['action'])) $this->action();//post 로 받은 action 값이 있으면 action() 함수 실행
    }
    // debuging 용 loging 함수
    // public function Console_log($data){
    //   echo "<script>console.log('PHP_Console in Model: ".$data."');</script>";
    // }

    public function check_login()
    {
      if (in_array($this->param->page_type, ['company', 'employee', 'call', 'manage'])) {
        if (isset($_COOKIE['userID'])) {
          if ($_COOKIE['userID']) {
          } else {
            alert('접근 권한이 없습니다.');
            move(_URL . 'ceo');
          }
        } else {
          alert('로그인이 필요한 서비스입니다.');
          move(_URL . 'login');
        }
      } elseif (in_array($this->param->page_type, ['ceo'])) {
        if (isset($_COOKIE['userID'])) {
          if ($_COOKIE['userID'] == 1) {
//            alert('관리자페이지로 이동합니다.');
//            move(_URL . 'company');
          }
        } else {
          alert('로그인이 필요한 서비스입니다.');
          move(_URL . 'login');
        }
      } elseif ($this->param->page_type == 'login') {
      } else {
        alert("잘못된 페이지 접근입니다.");
        move(_URL . 'login');
      }
    }
    
    public function query($sql)
    {
      $this->sql = $sql;
      $res = $this->db->prepare($this->sql);
      if ($res->execute()) {
        return $res;
      } else {
        echo "<pre>";
        echo $this->sql;
        echo "</pre>";
      }
    }
    
    public function fetch()
    {
      return $this->query($this->sql)->fetch();
    }
    
    public function executeSQL($string)
    {
      $this->sql = $string;
      $this->fetch();
    }
    
    public function fetchAll()
    {
      // $usage = memory_get_usage();
      // $this->Console_log($usage);
      return $this->query($this->sql)->fetchAll();
    }
    
    public function getTable($sql)
    {
      $this->sql = $sql;
      // $this->Console_log("in getTable");
      return $this->fetchAll();
    }
    
    public function count()
    {
      return $this->query($this->sql)->rowCount();
    }
    
    public function getList($condition, $order, $direction)
    {
      $this->sql = "SELECT * FROM {$this->param->page_type}";
      if ($condition) {
        $getCondition = " WHERE " . implode(" AND ", $condition);
      }
      else $getCondition = " WHERE deleted = 0";
      $this->sql .= $getCondition;
      if (!isset($direction)) {
        $direction = 'DESC';
      }
      if (isset($order) && $order != "") $this->sql .= " ORDER BY {$order} {$direction}";
      // $this->Console_log("in getList");
      return $this->fetchAll();
    }
    
    // public function getListNum($conditionArray = null)
    // {
    //   return sizeof($this->getList($conditionArray));
    // }

    public function getListNum($conditionArray = null)
    {
      $sql = "SELECT count(*) FROM {$this->param->page_type}";
      if ($conditionArray){
        $sql .= " WHERE " . implode(" AND ", $conditionArray);
      }
      // $this->Console_log($temp_res);
      return $this->db->query($sql)->fetchColumn();
    }
    
    public function getColumnList($array, $column)
    {
      foreach ($array as $key => $value) {
        $result[] = $value[$column];
      }
      if (isset($result)) return $result;
      else return null;
    }
    
    public function removeDuplicate($post, $table, $column)
    {
      $result = $post["{$table}-{$column}"];
      $columnList = $this->getColumnList($this->getList(), $column);
      while (in_array($result, $columnList)) {
        $result .= "(중복됨)";
        continue;
      }
      return $result;
    }
    
    public function extractPost($post, $table)
    {
      $tblArray = array();
      foreach ($post as $key => $value) {
        if (isset($value)) {
          $arr = explode("-", $key);
          if ($table == $arr[0]) $tblArray[$table][] = "{$arr[1]} = '{$value}' ";
        }
      }
      return $tblArray;
    }
    
//    public function update_call($_POST){
//      $callID = $_POST['callID'];
//      $detail = $_POST['detail'];
//      $price = $_POST['price'];
//      $this->executeSQL("UPDATE `call` SET `price` = '{$price}', `detail` = '{$detail}' WHERE `callID` = '{$callID}' LIMIT 1");
//    }
    
    public function getQuery($post, $table, $focus = null)
    {
      $tbl = $this->extractPost($post, $table);
      if (isset($table)) {
        switch ($post['action']) {
          case 'insert':
            $sql = "INSERT INTO ";
            break;
          case 'update':
            $sql = "UPDATE ";
            break;
          case 'new_insert':
            $sql = "INSERT INTO ";
            break;
          default :
            $sql = "INSERT INTO ";
            break;
        }
        $sql .= "{$table} SET ";
        $sql .= implode(",", $tbl[$table]);
        if ($post['action'] == 'update' or $post['action'] == 'delete') {
          if (!isset($focus)) $sql .= " WHERE {$table}.{$table}ID = '{$post[$table.'-'.$table.'ID']}' LIMIT 1";
          if (isset($focus)) $sql .= " WHERE {$table}.{$focus}ID = '{$post[$focus.'-'.$focus.'ID']}' LIMIT 1";
        }
        $this->sql = $sql;
        $this->fetch();
      }
    }
    
    public function select($table, $condition = null, $column = null, $order = null)
    {
      $sql = "SELECT * FROM `{$table}` ";
      if (isset($condition)) $sql .= "WHERE $condition ";
      if (isset($order)) $sql .= "ORDER BY '{$order}' ASC ";
      if (isset($column)){
        if(isset($this->getTable($sql)[0])){
          return $this->getTable($sql)[0][$column];
        }
      } 
      else return $this->getTable($sql);
    }
    
    public function delete($post, $table)
    {
      $d = _TODAY;
      //거래처, 구직자 삭제
      if (!isset ($post['joinID'])) {
        //main table delete
        $string = "UPDATE {$table} SET deleted=1, activated=0, deletedDate= '{$d}', deleteDetail = '{$post['deleteDetail']}' WHERE {$table}ID = '{$post['deleteID']}'";
        $this->executeSQL($string);
        //join table delete
        $string2 = "UPDATE join_{$table} SET deleted=1, activated=0, deletedDate= '{$d}', deleteDetail = '거래처삭제({$d})' WHERE {$table}ID = '{$post['deleteID']}' AND activated=1";
        $this->executeSQL($string2);
      } //가입 삭제
      else $this->executeSQL("UPDATE join_{$table} SET deleted=1, activated=0, deletedDate= '{$d}', deleteDetail = '{$post['deleteDetail']}' WHERE join_{$table}ID = '{$post['joinID']}'");
    }
    
    public function isHoliday($date)
    {
      if (in_array(date('w', strtotime($date)), [0, 6])) {
        return true;
      } elseif (sizeof($this->getTable("SELECT * FROM `holiday` where holiday = '{$date}'")) > 0) {
        return true;
      } else {
        return false;
      }
    }
    
    public function joinType($companyID, $lang = null)
    {
      $gujwaTable = $this->getTable("SELECT * FROM  `join_company` WHERE companyID = {$companyID} AND activated =1 AND price >0 AND  `point` IS NULL ");
      $pointTable = $this->getTable("SELECT * FROM  `join_company` WHERE companyID = {$companyID} AND activated =1 AND price >0 AND  `point` IS NOT NULL ");
      $depositTable = $this->getTable("SELECT * FROM  `join_company` WHERE companyID = {$companyID} AND activated =1 AND deposit >0");
      if ($lang == 'kor') {
        if (sizeof($gujwaTable) > 0) return sizeof($gujwaTable).'구좌';
        elseif (sizeof($pointTable) > 0) return '포인트';
        elseif (sizeof($depositTable) > 0) return '보증금+콜비';
        else return '만기됨';
      } else {
        if (sizeof($gujwaTable) > 0) return 'gujwa';
        elseif (sizeof($pointTable) > 0) return 'point';
        elseif (sizeof($depositTable) > 0) return 'deposit';
        else return 'deactivated';
      }
    }
    
    public function getAllColumns($tableName)
    {
      $columnTable = $this->getTable("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N'{$tableName}'");
      foreach ($columnTable as $value) {
        foreach ($value as $item) {
          $columnList[] = $item;
        }
      }
      return $columnList;
    }

    public function insert($type, $table, $post, $tbl2=null, $tbl2ID=null)
    {
      $action = ($type == 'update') ? ' UPDATE ' : ' INSERT INTO ';
      foreach ($post as $key => $value) {
        if (!in_array($key, $this->getAllColumns($table))) {//table 내의 columnn 값이 아니면
          unset($post[$key]);
        }
        else{
          $arr[] = " `{$key}` = '{$value}' ";
        }
      }
      $str = implode(',',$arr);
      $sql = "{$action} `{$table}` SET {$str}";
      if($type =='addJoin' && $tbl2 && $tbl2ID){
        $sql.=" WHERE `{$tbl2}ID` = '{$tbl2ID}' ";
      }
      if($type =='update'){
        if($tbl2 && $tbl2ID){
          $sql .= " WHERE {$tbl2}ID = '{$tbl2ID}' LIMIT 1";
        }
        else{
          $sql .= " WHERE {$table}ID = '{$_POST[$table.'ID']}' LIMIT 1";
        }
      }
      $this->executeSQL($sql);
    }
    
    public function getLastID($table)
    {
      $tableID = $table . 'ID';
      return $this->getTable("SELECT * FROM `{$table}` ORDER BY `{$tableID}` DESC")[0][$tableID];
    }
    
    public function fixCancel($post)
    {
      $this->executeSQL("UPDATE `fix` SET employeeID = '0', `cancelled`='1', `cancelDetail`='{$post['detail']}' WHERE `fixID` = '{$post['fixID']}' LIMIT 1");
      $this->executeSQL("UPDATE `call` SET employeeID = '0', `cancelled`='1', `cancelDetail`='{$post['detail']}' WHERE `fixID`='{$post['fixID']}' AND `workDate` >= '{$post['date']}'");
    }
  }