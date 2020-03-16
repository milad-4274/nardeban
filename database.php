<?php

class Moshavere_DB
{
    protected $config;
    protected $users_table;
    protected $answers_table;
    protected $questions_table;
    protected $limit_table;
    
    public function __construct()
    {
        
        $this->config = parse_ini_file("config.ini");
        $this->users_table = "users_table";
        $this->answers_table = "answers_table";
        $this->questions_table = "questions_table";
        $this->limit_table = "limits_table";
        
        
    }
    
    public function connect()
    {
        $mysqli = new mysqli($this->config['servername'],$this->config['username'],$this->config['password'],$this->config['dbname']);
        $mysqli->set_charset('utf8mb4');
        if ($mysqli->connect_errno)
        {
            throw new Exception('Connection Error ' .mysqli_connect_error());
        } else {
            return $mysqli;
        }
    }
    
    public function create_tables()
    {
        $mysqli = $this->connect();
        $sql = "CREATE TABLE IF NOT EXISTS $this->users_table (
                id INT AUTO_INCREMENT,
                name varchar(100),
                username varchar(100),
                chat_id varchar(50) UNIQUE,
                joined TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                code varchar(10) UNIQUE DEFAULT 0,
                question_status INT DEFAULT 0,
                limit_status INT DEFAULT 0,
                
                PRIMARY KEY  (`id`)
                ) DEFAULT CHARSET=utf8mb4 ;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS $this->limit_table (
            id INT AUTO_INCREMENT,
            day INT,
            user_id INT,
            time varchar(20),

            PRIMARY KEY  (`id`)
            ) DEFAULT CHARSET=utf8mb4 ;";

        
        $sql .= "CREATE TABLE IF NOT EXISTS $this->answers_table (
                id INT AUTO_INCREMENT,
                user_id INT,
                question_id INT,
                answer TEXT,
                day INT,
                time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (`id`)
                ) DEFAULT CHARSET=utf8mb4 ;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS $this->questions_table (
            id INT AUTO_INCREMENT,
            type varchar(50),
            text TEXT,
            report TEXT,
            unit varchar(50),
            depend INT,
            PRIMARY KEY  (`id`)
            ) DEFAULT CHARSET=utf8mb4 ;";
        

        

        
        if ($mysqli->multi_query($sql))
        {
            echo json_encode($mysqli->error);
            return 1;
        } else {
            throw new Exception('database Creation failed' . $mysqli->error);
        }
    }
    
    public function new_user($chat_id) {
        $mysqli = $this->connect();
        $query = "SELECT COUNT(1) FROM $this->users_table WHERE chat_id= '$chat_id'";
        if ($result = $mysqli->query($query))
        {
            if($result->fetch_all()[0][0] == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception("query failed. " . $mysqli->error);
        }
    }
    
    public function save_user_info($user) {
        $user_id = $user->id;
        $name = $user->first_name . " " . $user->last_name;
        $username = $user->username;
        $mysqli = $this->connect();
        $new_user = $this->new_user($user_id);
        
        $query = "INSERT INTO $this->users_table (chat_id,name,username,code)
        VALUES ('$user_id','$name','$username','$user_id')";
        
        if($new_user) {
            if($mysqli->query($query))
            {
                return 1;
            } else {
                throw new Exception("query failed " . $mysqli->error);
            }
        } else {
            return 0;
        }
        
    }
    
    public function get_user_info($chat_id) {
        $mysqli = $this->connect();
        $query = "SELECT * FROM $this->users_table WHERE chat_id='$chat_id'";
        if ($result=$mysqli->query($query))
        {
            return $result->fetch_object();
        } else {
            throw new Exception("query failed " . $mysqli->error);
        }
    }
    
    
    public function set_questions($questions) {
        $mysqli = $this->connect();
        foreach ($questions as $question) {
            $query = "INSERT INTO $this->questions_table (type,text,report,unit,depend) VALUES ('$question->type','$question->text','$question->report','$question->unit','$question->depend')";
            if(!$mysqli->query($query)) {
                return "questions already saved or " . $mysqli->error;
            }
        }
    }
    
    public function reset_status($target,$chat_id,$state=0) {
        $mysqli = $this->connect();
        if($target === "qs") {
            $col = "question_status";
        } else if ($target=== "ls") {
            $col = "limit_status";
        } else {
            $col = "coupon_status";
        }
        $query = "UPDATE $this->users_table SET $col = '$state' WHERE chat_id = '$chat_id'";
        if($mysqli->query($query))
        {
            return 1;
        } else {
            throw new Exception("query failed " . $mysqli->error);
        }
    }
    
    public function get_all_questions() {
        $mysqli = $this->connect();
        $query = "SELECT * FROM $this->questions_table";
        if($result = $mysqli->query($query))
        {
            while ($row = $result->fetch_object())
            {
                $questions[] = $row;
            }
            return $questions;
        }
        else {
            throw new Exception("query failed " . $mysqli->error);
        }
    }
    
    public function get_questions($tel_user) {
//         $mysqli = $this->connect();
        $state = $this->get_user_info($tel_user->id)->question_status;
        $questions = $this->get_all_questions();


        
        
        if($questions[$state]->depend == 1 ) {
            
            $user_id = $this->get_user_info($tel_user->id)->id;
            $ans = $this->get_answer_question(intval($state) , $user_id);
            if($ans == 1) {
                return array($state,$questions[$state],sizeof($questions));
            } else {
                $this->reset_status('qs', $tel_user->id,intval($state) + 1);
                return False;
            }
            
        }
        return array($state,$questions[$state],sizeof($questions));
        
    }
    
    public function new_anwer($day,$tel_user,$q_id) {
        $mysqli = $this->connect();
        $user_id = $this->get_user_info($tel_user->id)->id;
        $query = "SELECT COUNT(1) FROM $this->answers_table WHERE day= '$day' AND user_id='$user_id' AND question_id='$q_id'";
        if ($result = $mysqli->query($query))
        {
            if($result->fetch_all()[0][0] == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception("query failed. " . $mysqli->error);
        }
    }
    
    
    public function save_answer($tel_user,$q_id,$answer,$day) {
        $mysqli = $this->connect();
        $user_id = $this->get_user_info($tel_user->id)->id;
        
        $new_answer = $this->new_anwer($day, $tel_user,$q_id);
        
        if($new_answer) {
            $query = "INSERT INTO $this->answers_table (user_id,question_id,answer,day)
        VALUES ('$user_id','$q_id','$answer','$day') ";
        } else {
            $query = "UPDATE $this->answers_table SET answer = '$answer' WHERE day= '$day' AND user_id='$user_id' AND question_id='$q_id'";
        }
        
        

        if($mysqli->query($query))
        {
            return 1;
        } else {
            throw new Exception("query failed " . $mysqli->error);
        }

        
    }
    
    public function get_answer_question($q_id,$user_id) {
        $mysqli = $this->connect();
        $query = "SELECT answer FROM $this->answers_table WHERE question_id = '$q_id' AND user_id = '$user_id'";
        
        if($res = $mysqli->query($query)) {
            return $res->fetch_object()->answer;
        } else {
            throw new Exception("query failed " . $mysqli->error);
        }
    }
    
    public function get_report($chat_id,$day=null,$report=False) {
        $mysqli = $this->connect();
        if (isset($day)) {
            $query = "
        SELECT a.question_id, a.answer, q.text, u.code,q.report,a.time,q.unit
        FROM answers_table a
        INNER JOIN users_table u
        	ON u.id=a.user_id
        INNER JOIN questions_table q
        	ON q.id = a.question_id
        WHERE u.chat_id = '$chat_id' AND day='$day'
        ";
            if($report) {
                $query .= " AND q.report <> ''";
            }
        } else {
            $query = "
        SELECT a.question_id, a.answer, q.text, u.code, q.report,a.time,q.unit
        FROM answers_table a
        INNER JOIN users_table u
        	ON u.id=a.user_id
        INNER JOIN questions_table q
        	ON q.id = a.question_id
        WHERE u.chat_id = '$chat_id'
        ";
        }

        
        if($result = $mysqli->query($query))
        {
            while ($row = $result->fetch_object())
            {
                $answes[] = $row;
            }
            return $answes;
        }
        else {
            throw new Exception("query failed " . $mysqli->error);
        }
        
    }
    

    
    
    public function new_limit($day,$tel_user) {
        $mysqli = $this->connect();
        $user_id = $this->get_user_info($tel_user->id)->id;
        $query = "SELECT COUNT(1) FROM $this->limit_table WHERE day= '$day' AND user_id='$user_id'";
        if ($result = $mysqli->query($query))
        {
            if($result->fetch_all()[0][0] == 0) {
                return true;
            } else {
                return false;
            }
        } else {
            throw new Exception("query failed. " . $mysqli->error);
        }
    }
    
    public function save_limit($day,$tel_user,$time) {
        $mysqli = $this->connect();
        $user_id = $this->get_user_info($tel_user->id)->id;
        
        $new_limit = $this->new_limit($day, $tel_user);
        
        if($new_limit) {
            $query = "INSERT INTO $this->limit_table (user_id,time,day)
        VALUES ('$user_id','$time','$day') ";
        } else {
            $query = "UPDATE $this->limit_table SET time = '$time' WHERE day= '$day' AND user_id='$user_id'";
        }
        
        
        
        if($mysqli->query($query))
        {
            return 1;
        } else {
            throw new Exception("query failed " . $mysqli->error);
        }
    }
    
    public function get_limit($day,$tel_user) {
        $user_id = $this->get_user_info($tel_user->id)->id;
        $mysqli = $this->connect();
        $query = "SELECT time from limits_table WHERE day=2 AND user_id=1";
        if($res = $mysqli->query($query)) {
            return $res->fetch_object()->time;
        } else {
            throw new Exception("query failed " . $mysqli->error);
        }
    }
    
    public function set_initial_limits($tel_user) {
        $dates = $this->get_day(True);
//         $day = $dates[0];
        $start = $dates[1];
        $next_friday = date(strtotime('next friday',$start));
        $dis_days = round(($next_friday - $start) / (60*60*24));
//         echo json_encode(date('l jS \of F Y h:i:s A',$start));

        
        for ($i = 0; $i <= $dis_days; $i++) {
            $this->save_limit($i+1, $tel_user, "-1");
        }
        
    }
    
    public function get_day($start=False,$date=null) {
        date_default_timezone_set("Asia/Tehran");
        $start_time = strtotime('2020-03-14 00:00:00');
        $now = time();
        
        //         echo json_encode(date('l jS \of F Y h:i:s A',$start_time));
        
        $diff = $now - $start_time;
        if($start === True) {
            if(isset($date)) {
                $date_diff = $date - $start_time;
                $res = round($date_diff / (60*60*24));
                return [round($diff / (60 * 60 * 24)),$start_time,$res];
            }
            return [round($diff / (60 * 60 * 24)),$start_time];
        } else {
            if(isset($date)) {
                $date_diff = $date - $start_time;
                $res = round($date_diff / (60*60*24));
                return [round($diff / (60 * 60 * 24)),$res];
            }
            return round($diff / (60 * 60 * 24));
        }
        
    }

    public function get_change_limit_questions($tel_user) {

        $now = time();
        $next_friday = date(strtotime('next friday 23:59:59',$now));
        
        $sd = $this->get_day(False,$now)[1];
        $ed = $this->get_day(False,$next_friday)[1];
        
        $days_between = $this->get_dis_days($next_friday, $now);
        echo json_encode(date('l jS \of F Y h:i:s A',$now));
        echo json_encode(date('l jS \of F Y h:i:s A',$next_friday));
        echo json_encode($days_between);
        
        echo json_encode($sd);
        echo json_encode($ed);
        
        
        
    }
    
    public function get_dis_days($next,$before) {
        return round(($next - $before) / (60*60*24));
    }
   
    
    
}
