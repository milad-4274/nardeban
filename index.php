<?php

require_once "database.php";
require_once 'vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$db = new Moshavere_DB();
// $mysqli = $db->connect();
$created = $db->create_tables();

if($created){
    echo "created";
} else {
    echo "there is a problem";
}



// require_once 'telegram.php';
// $telegram = new Moshavere_Telegram();
// $telegram->setWebhook();

class Question 
{
    public $type;
    public $text; 
    public $report;
    public $unit;
    public $depend;
    
    public function __construct($type,$text,$report_text=null,$unit=null,$depend=0) {
        $this->type = $type;
        $this->text = $text;
        $this->report = $report_text;
        $this->unit = $unit;
        $this->depend = $depend;
    }
}


$questions = [
    new Question("text", "خب " . "[name]" . " عزیز، امروز چند ساعت مطالعه داشتی؟(فرمت ساعت مطالعه‌ت رو به فرمت ساعت یعنی مثل 9:30 به زبان انگلیسی بنویس.)","میزان مطالعه","ساعت"),
    new Question("text", "میانگین درصد عملی شده گام های روزانه ‌ت رو به صورت عدد وارد کن.","درصد عملکرد","درصد"),
    new Question("img", "یه عکس واضح از برنامه ریزی روزانه ت رو بارگزاری کن."),
    new Question("text", "اگر توضیح کوتاهی در مورد گزارش هست که دوست داری اساتید بدونن در حد یک جمله بنویس"),
    new Question("text", "راستی امروز چه ساعتی بیدار شدی؟(با فرمت ساعت بنویس)","ساعت بیداری"),
    new Question("text", "امروز چند ساعت از گوشی‌ت استفاده کردی؟ (بازم به فرمت ساعت)","میزان استفاده از گوشی","ساعت"),
    new Question("img", "عکس (اسکرین شات) نرم افزاری که میزان استفاده از گوشیت رو نشون میده رو بفرست"),
    new Question("text", "امروز تمرین محاسبات رو انجام دادی؟ اگر آره عدد 1 و اگر نه عدد 0 رو بفرست.","امروز محاسبات رو کامل انجام"),
    new Question("img", "عکس برگه محاسبات امروزت رو هم بفرست(دو صفحه رو بذار کنار هم عکس بگیر بفرست)",null,null,1),
    new Question("text", "میخوای امروز از کوپنت استفاده کنی؟ اگر آره 1 رو بفرست و اگر نه عدد 0 رو بفرست","امروز از کوپنم استفاده ")
];

// $db->set_questions($questions);

// echo json_encode($db->get_all_questions());

// $db->reset_user_question_status(87084352);

// $db->reset_status('qs', 87084352,10);

class Tel {
    public $username;
    public $id;
    public $first_name;
    public $last_name;
    
    public function __construct($u,$i,$f,$l) {
        $this->username = $u;
        $this->id = $i;
        $this->first_name = $f;
        $this->last_name = $l;
    }
}

$user = new Tel("mn", 87084352, "m", "n");
// echo json_encode($db->get_user_info(87084352)->id);

// $db->save_user_info($user);

// $answes = $db->get_answers(87084352);
// echo json_encode($answes);

// $day = $db->get_day(False);
// echo json_encode($day);

// $db->save_answer($user, 1, "test", 1);


// $new_limit = $db->new_limit(1, $user);
// if($new_limit) {
//     echo "new";
// } else {
//     echo "repeated";
// }
// $db->save_limit(1, $user, "5");


// $db->set_initial_limits($user);

// $ans = $db->get_answer_question(3,1);
// echo  json_encode($ans);

// $response = $db->get_questions($user);
// // $question = $response[1];
// $state = $response[0];
// // $q_num = $response[2];

// echo json_encode($response);

// $db->reset_status('qs', $user->id,$state+1);

// $img_name = "test";
// $img = 'images/' . $img_name . ".jpg";

// file_put_contents($img, file_get_contents('https://api.telegram.org/file/bot911080398:AAGn6pxY2iow0FuEygApnnawLTHPYzHkavY/photos//file_0.jpg'));


$db->get_change_limit_questions($user);