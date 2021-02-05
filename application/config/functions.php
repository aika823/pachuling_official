<?php
  
  Class Functions
  {
    public function content()
    {
      $dir = _VIEW . "{$this->param->page_type}/{$this->param->include_file}.php";
      if (file_exists($dir)) require_once($dir);
      echo "<script src='/config.js'></script>";
      echo "<script src='/public/js/common.js'></script>";
      echo "<script src='/public/js/functions.js'></script>";
      echo "<script src='/public/js/ajax.js'></script>";
      echo "<script src='/public/js/call.js'></script>";
      echo "<script src='/public/js/ceo.js'></script>";
    }
    
    public function initJoin($tableName)
    {
      //초기화 테스트
      $todayTime = strtotime(date("Y-m-d"));
      switch ($tableName) {
        case 'company':
          $days = 15;//만기임박 날짜 설정
          $joinList = $this->model->getTable("SELECT * FROM join_{$tableName} WHERE `deleted` = 0 AND point IS NULL AND deposit IS NULL");
          break;
        case 'employee':
          $days = 5;//만기임박 날짜 설정
          $joinList = $this->model->getTable("SELECT * FROM join_{$tableName} WHERE `deleted` = 0");
          break;
      }
      foreach ($joinList as $key => $value) {
        $joinID = $value["join_" . $tableName . "ID"];
        $endTime = strtotime($value['endDate']);
        $targetTime = strtotime($value['endDate'] . " -{$days} days");
        //imminent (만기임박)
        if (($targetTime <= $todayTime && $todayTime <= $endTime) || ($tableName == 'employee' && $value['paid'] == 0)) {
          $this->model->executeSQL("UPDATE join_{$tableName} SET `imminent` = '1' WHERE join_{$tableName}ID = {$joinID} LIMIT 1");
        } //가입 자동 만기시킴
        else {
          if ($todayTime > $endTime) {
            $this->model->executeSQL("UPDATE join_{$tableName} SET `activated` = '0', `imminent` = '0' WHERE join_{$tableName}ID = {$joinID} LIMIT 1");
          } else {
            $this->model->executeSQL("UPDATE join_{$tableName} SET `imminent` = '0' WHERE join_{$tableName}ID = {$joinID} LIMIT 1");
          }
        }
        
      }
    }
    
    //초기화 테스트
    public function initActCondition($list, $tableName)
    {
      foreach ($list as $key => $value) {
        $deactivated = 0;
        $activated = 0;
        $imminent = 0;
        $tableID = $value[$tableName . "ID"];
        $joinList = $this->model->getTable("SELECT * FROM `join_{$tableName}` WHERE {$tableName}ID = {$tableID} AND activated = 1");
        if (sizeof($joinList) > 0) {
          $this->model->executeSQL("UPDATE {$tableName} SET activated = 1 WHERE {$tableName}ID = {$tableID} LIMIT 1");
          foreach ($joinList as $key2 => $data) {
            if ($data['imminent'] == 1) {//만기임박
              $imminent += 1;
              break;
            } else {
              if ($data['activated'] == 1) {//activated
                $activated += 1;
              } else {//deactivated
                $deactivated += 1;
              }
            }
          }
        }
        if ($imminent > 0) {
          $this->model->executeSQL("UPDATE {$tableName} SET imminent = 1 WHERE {$tableName}ID = {$tableID} LIMIT 1");
        } else {
          if ($activated > 0) {//활성화
            $this->model->executeSQL("UPDATE {$tableName} SET activated = 1, imminent = 0 WHERE {$tableName}ID = {$tableID} LIMIT 1");
          } elseif ($deactivated == sizeof($joinList)) {//만기됨
            $this->model->executeSQL("UPDATE {$tableName} SET activated = 0, imminent = 0 WHERE {$tableName}ID = {$tableID} LIMIT 1");
          }
        }
      }
      
      
      return $list;
    }
    
    public function getActCondition($list, $tableName)
    {
      $tableID = $tableName . 'ID';
      $imminentArray = $this->model->getColumnList(   $this->model->getList([$this->imminentCondition],null,null), $tableID);
      $deactivatedArray = $this->model->getColumnList($this->model->getList([$this->deactivatedCondition],null,null), $tableID);
      $deletedArray = $this->model->getColumnList(    $this->model->getList([$this->deletedCondition],null,null), $tableID);
      foreach ($list as $key => $value) {
        $tableID = $tableName . 'ID';
        $tableID = $list[$key][$tableID];
        if (in_array($tableID, $deactivatedArray)) {
          $actCondition = "만기됨";
          $class = "deactivated";
        } elseif (in_array($tableID, $imminentArray)) {
          $actCondition = "만기임박";
          $class = "imminent";
        } elseif (in_array($tableID, $deletedArray)) {
          $actCondition = "삭제됨";
          $class = "deleted";
        } else {
          $actCondition = "가입중";
          $class = 'activated';
        }
        $list[$key]['actCondition'] = $actCondition;
        $list[$key]['class'] = $class;
      }
      return $list;
    }
    
    public function get_joinType($data)
    {
      if (isset($data['deposit'])) {
        return "보증금+콜비";
      } elseif (isset($data['point'])) {
        return "포인트";
      } elseif (isset($data['price'])) {
        return "구좌";
      } else {
        return "만기됨";
      }
    }
    
    public function get_joinPrice($data)
    {
      switch ($this->get_joinType($data)) {
        case '구좌':
          echo number_format($data['price']) . " 원";
          break;
        case '보증금+콜비':
          echo number_format($data['deposit']) . " 원 (보증금)";
          break;
        case '포인트':
          echo number_format($data['point']) . " 원 (포인트)";
          break;
      }
    }
    
    public function imminent_check($table, $data)
    {
      $tableID = $table . "ID";
      $id = $data[$tableID];
      if ($data['imminent'] == 1) {
        if ($table == 'employee') {
          $sql = "SELECT *FROM  `join_{$table}`WHERE `activated` = 1 AND `paid` = 1 AND `{$table}ID` = {$id} ORDER BY `endDate` ASC LIMIT 1";
          $date = $this->model->getTable($sql)[0]['endDate'];
          $paidSql = "SELECT * FROM  `join_{$table}` WHERE `paid` = 0 AND `{$table}ID` = {$id}";
          $paid = sizeof($this->model->getTable($paidSql));
          if ($paid > 0) {
            return "수금 미완료 ({$paid} 건)";
          } else {
            return "만기임박 (D" . leftDays($date) . ")";
          }
        } else {
          $sql = "SELECT * FROM  `join_{$table}` WHERE `activated` = 1 AND `{$table}ID` = {$id} ORDER BY `endDate` ASC LIMIT 1";
          $date = $this->model->getTable($sql)[0]['endDate'];
          return "만기임박 (D" . leftDays($date) . ")";
        }
      } elseif ($data['activated'] == 0) {
        if ($data['deleted'] == 1) {
          if (isset($data['deletedDate'])) {
            return "삭제일(" . $data['deletedDate'] . ")";
          } else {
            return "-";
          }
        } else {
          $sql = "SELECT * FROM `{$table}` LEFT JOIN `join_{$table}` on `{$table}`.`{$table}ID` = `join_{$table}`.{$table}ID WHERE `{$table}`.`{$table}ID` = {$id} ORDER BY `endDate` DESC LIMIT 1";
          $date = $this->model->getTable($sql)[0]['endDate'];
          if (isset($date)) {
            return "만기일(" . $date . ")";
          } else {
            return "-";
          }
        }
      } else {
        return "-";
      }
    }
    
    public function get_endDate($data, $tableName)
    {
      switch ($tableName) {
        case 'company':
          $days = 15;
          break;
        case 'employee':
          $days = 5;
          break;
      }
      $condition1 = strtotime($data['endDate'] . " -{$days} days") <= strtotime(_TODAY);
      $condition2 = strtotime(_TODAY) <= strtotime($data['endDate']);
      $string = $data['endDate'];
      $leftDays = leftDays($data['endDate']);
      if ($condition1 && $condition2) {
        $string .= " (D{$leftDays})";
      }
      return $string;
    }
    
    public function get_joinDetail($data)
    {
      if ($data['joinDetail']) {
        echo nl2br(strval($data['joinDetail']));
        if ($data['deleted'] == 1) {
          echo "<br/>(삭제사유: " . $data['deleteDetail'] . ")";
        } elseif ($data['deleted'] == 0 && $data['activated'] == 0) {
          echo "<br/>(가입 만기됨)";
        }
        if (isset($data['cancelDetail']) && $data['cancelDetail'] != '') {
          echo "<br/>(" . $data['cancelDetail'] . ")";
        }
      } else {
        if ($data['deleted'] == 1) echo "(삭제사유: " . $data['deleteDetail'] . ")";
        elseif ($data['deleted'] == 0 && $data['activated'] == 0) {
          echo "(가입 만기됨)";
        }
        if (isset($data['cancelDetail']) && $data['cancelDetail'] != '') {
          echo "<br/>(" . $data['cancelDetail'] . ")";
        }
      }
    }
    
    public function get_callDetail($data)
    {
      $detail_array['plain'] = ($data['detail']) ? "<a class='call-detail'>" . nl2br(strval($data['detail'])) . "</a>" : null;
      $detail_array['cancel'] = ($data['cancelDetail']) ? "<a class='call-cancel-detail'>(취소사유:" . nl2br(strval($data['cancelDetail'])) . ")<a>" : null;
      echo (array_filter($detail_array)) ? implode('<br>', $detail_array) : '-';
    }
    
    public function get_join_delete_btn($data, $tableName)
    {
      if ($data['activated'] == 1) {
        $tableID = "join_" . $tableName . "ID";
        return <<<HTML
<button class = "btn btn-danger btn-join-cancel-modal" value = "{$tableName}-{$data[$tableID]}" id="{$data[$tableID]}" >삭제</button>
HTML;
      } else {
        return $data['deletedDate'];
      }
    }
    
    public function get_deleteBtn($data, $tableName)
    {
      $tableID = $tableName . "ID";
      if ($data['deleted'] == 0) {
        return <<<HTML
          <button class="btn btn-danger btn-delete-modal" value="{$tableName}-{$data[$tableID]}" id="{$data[$tableID]}">삭제</button>
HTML;
      } else {
        return <<<HTML
        <button type="button" class="btn btn-insert btn-restore" value="{$tableName}-{$data[$tableID]}">복구</button>
HTML;
      }
    }
    
    public function getPayBtn($data, $table, $column)
    {
      if ($data[$column] > 0) {
        if ($data['paid'] == 0) {
          $val = number_format($data[$column]);
          return <<<HTML
<button type="button" class="btn btn-money getMoneyBtn_{$table}" id="{$data[$table . 'ID']}" value="{$table}-{$data[$column]}">
<i class="fa fa-won"></i>$val
</button>
HTML;
        }
        else {
          $price = number_format($data[$column]);
          $paid_message = '<br>수금(' . $data['receiver'] . ")";
          return <<<HTML
<b class="paid"><i class="fa fa-won"></i>{$price}</b>{$paid_message}
HTML;
        }
      }
      else{
        return '무료';
      }
    }
    
    public function makeDetail($array)
    {
      foreach ($array as $key => $value) {
        $value .= " : ";
        $newArray[] = $value;
      }
      $string = implode("\n", $newArray);
      return $string;
    }
    
    public function get_detail($data, $tableName)
    {
      $companyDetail = array('좌탁여부', '테이블수', '그릇종류', '식기세척기', '상주직원수', '주방환경', '교통환경', '주요업무', '가입경로', '기타사항', '작성자');
      $employeeDetail = array('경력', '특기', '체류비자', '월급제', '4대보험', '자차소유', '추천인', '외모', '이상여부', '지각', '빵꾸', '기타사항', '작성자');
      if (isset($data['detail'])) {
        return $data['detail'];
      } else {
        switch ($tableName) {
          case 'company':
            return $this->makeDetail($companyDetail);
          case 'employee':
            return $this->makeDetail($employeeDetail);
        }
      }
    }
    
    public function joinColor($data, $tableName)
    {
      
      if ($data['deleted'] == 1) {
        return "deleted";
      } else {
        $today = date('Y-m-d');
        $imminent = ($tableName == 'company') ? 15 : 5;
        if ($data['activated'] == 0) return "deactivated";
        elseif (strtotime($data['endDate'] . " -{$imminent} days") <= strtotime($today) && strtotime($today) < strtotime($data['endDate']))
          return "imminent";
        else echo "activated";
      }
    }
    
    public function companyName($id)
    {
      return $this->model->select('company', "`companyID`={$id}", 'companyName');
    }
    
    public function employeeName($id)
    {
      return $this->model->select('employee', "`employeeID`={$id}", 'employeeName');
    }
    
    public function callType($data)
    {
      if (isset($data['point'])) return '포인트';
      if (isset($data['price'])) return '유료';
      else return '일반';
    }

//    public function joinType($list)
//    {
//      $result = array();
//      if (isset ($list[0])) {
//        foreach ($list as $key => $value) {
//          if (isset($value['point'])) {
//            if (!in_array('포인트', $result)) $result[] = '포인트';
//          } elseif (isset($value['deposit'])) {
//            if (!in_array('보증금', $result)) $result[] = '보증금';
//          } elseif (isset($value['price'])) {
//            if (!in_array('구좌', $result)) $result[] = '구좌';
//          }
//        }
//        return implode(',', $result);
//      }
//      else return null;
//    }
    
    public function assignType($data, $class = false)
    {
      if (isset($data['point']) && $data['point'] > 0) {
        if ($class) {
          return 'point';
        } else {
          return '(P)';
        }
      } elseif (isset($data['price']) && $data['price'] > 0) {
        if ($class) {
          return 'charged';
        } else {
          return '(유)';
        }
      } else {
        if ($class) {
          return 'free';
        } else {
          return null;
        }
      }
    }
    
    public function get_punk_status($data)
    {
      $sql = "SELECT * FROM `punk` WHERE `callID` = '{$data['callID']}'";
      $table = $this->model->getTable($sql);
      return ($table) ? 'punked' : null;
    }
    
    
    public function timeType($data)
    {
      $start = explode(':', $data['startTime']);
      $end = explode(':', $data['endTime']);
      
      $start_hour = $start[0];
      $start_minute = ($start[1] == '30') ? ':' . $start[1] : '시';
      $start_time = $start_hour . $start_minute;
      
      
      $end_hour = ($end[0] < 25) ? $end[0] : '(익)' . (intval($end[0]) - 24);
      $end_minute = ($end[1] == '30') ? ':' . $end[1] : '시';
      $end_time = $end_hour . $end_minute;
      
      $workTime = $end[0] - $start[0];
      if ($workTime >= 10) $result = '종일';
      else {
        $result = ($start_hour < 12) ? '오전' : '오후';
      }
//      return $result . "<br>" . date('H:i', strtotime($data['startTime'])) . "~" . date('H:i', strtotime($data['endTime']));
      $result .= "<br>";
      $result .= $start_time;
      $result .= "~";
      $result .= $end_time;
      return $result;
    }
    
    public function getTime($i)
    {
      if ($i < 12) {
        $time = '오전 ' . $i . '시';
      } elseif ($i == 12) {
        $time = '정오';
      } elseif (12 < $i && $i < 24) {
        $time = $i - 12;
        $time = '오후 ' . $time . '시';
      } elseif ($i == 24) {
        $time = '자정';
      } elseif ($i > 24) {
        $time = $i - 24;
        $time = '익일오전 ' . $time . '시';
      }
      return $time;
    }
  }