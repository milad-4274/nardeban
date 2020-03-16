<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// $test = new NewsTelegram();
// $cat = $test->cat_db->get_categories();
// echo json_encode($cat['1']);
require_once 'database.php';
require_once 'vendor/autoload.php';

use Morilog\Jalali\Jalalian;

class Moshavere_Telegram
{
    protected $token;
    protected $db;
    protected $mysqli;
    public $username;
    public $base_url;
    protected $admin_id;

    public function __construct()
    {
        
        $this->db = new Moshavere_DB();
        $this->mysqli = $this->db->connect();
        $this->token = "911080398:AAGn6pxY2iow0FuEygApnnawLTHPYzHkavY";
        $this->username = "Nregbot";
        $this->base_url = "https://milad4274.ir/bots/moshavere_bot";
        $this->admin_id = 87084352;
        
    }

    public function setWebhook()
    {
        $url = $this->base_url . "/input.php";
        $this->apiRequest("setwebhook",[
            'url' => $url
        ]);
    }

    public function handle()
    {
        
        $curr_date = date('w');
        
        $send_report_text = "ثبت گزارش";
        $sent_limit_text = "ثبت حد چله";
        $use_coupon_text = "استفاده از کوپن";
        
        $greeting = "به دوره چله جدیت و تلاش خوش اومدید.
من ربات چله هستم و از آشنایی باتون خوشبختم :)) 
توی این ۴۰ روز من کنارتون هستم و تقریبا هرشب قراره با هم در ارتباط باشیم. مایه افتخاره 
خب بریم سر اصل مطلب. شما یه تعداد گزینه این پایین می‌بینید. هر شب که خواستید گزارشتون رو بفرستید، روی این گزینه ها کلیک می‌کنید و مواردی که ازتون خواستم رو بهم می‌گید. بعد من یه چیز شسته رفته بهتون تحویل میدم و شما خروجیش رو می‌فرستید توی گروهتون. به همین سادگی.
راستی یادت باشه که همه اعداد رو به زبان انگلیسی و بدون هیچ اضافه (مثل فاصله) وارد کنی!
راستی اگر به مشکلی توی کار کردن با من برخوردی میتونی به آقای باختری (@Mbakhtari) پیام بدی.";
        
        $error_unrelevant = "لطفا از دکمه های قرار داده شده استفاده کنید.";
        $error_week_limit = "عزیزم قوانین رو یادت رفت؟ فقط پنجشنبه ها میتونی حد هفته بعد رو مشخص کنی.";
        
        $update = json_decode(file_get_contents("php://input"));
        $message_obj = $update->message;
        $message = $update->message->text;
        $chat_id = $update->message->chat->id;
        $telegram_user = $update->message->from;
        
        $coupon_message = "هر کس حق استفاده از ۵ کوپن در دوره را دارد که شما تا به حال از " . "[uc]" . " استفاده کردی و " . "[rc]" . " کوپن برات باقی مونده. اگر مطمئنی میخوای امروز از یکی از کوپنات استفاده کنی، عدد ۱ رو وارد کن.";
        $limit_message = "میخوای حد هفته بعدت رو مشخص کنی یا اینکه میخوای حد روز های بعدت رو اصلاح کنی؟";
        
        $week_limit_text = "ثبت حد هفته";
        $change_limit_text = "اصلاح حد";
        
//         if ($chat_id == $this->admin_id) {
//             $this->apiRequest("sendmessage",[
//                 'chat_id' => $chat_id,
//                 'text' => "I know you are admin"
//             ]);
//         }

        $buttons = [$send_report_text,$sent_limit_text];


        switch($message) {
            case "/start":
                $new = $this->db->new_user($chat_id);
                if ($new) {
                    
                    $this->apiRequest('sendmessage',
                        [
                            'chat_id' => $chat_id,
                            'text' => "$greeting",
                            'reply_markup' => json_encode([
                                    'keyboard' => [$buttons],
                                    'resize_keyboard' => True,

                                ])

                        ]);
                    
                    $this->db->save_user_info($telegram_user);
                    $this->db->set_initial_limits($telegram_user);
                    
                } else {
                    $user = $this->db->get_user_info($chat_id);
                    $info = "نام ثبت شده: " . $user->name;
                    $this->apiRequest('sendmessage',
                        [
                            'chat_id' => $chat_id,
                            'text' => "شما قبلا ثبت نام کرده اید." . "\n" . $info,
                            'reply_markup' => json_encode([
                                'keyboard' => [$buttons],
                                'resize_keyboard' => True,
                                
                            ])

                        ]);
                }
            break;
            
            case $send_report_text:
                $this->db->reset_status('qs',$chat_id);

                $this->send_questions($telegram_user);
            break;
            
            case $sent_limit_text:
                $this->apiRequest("sendmessage",[
                    'text' => $limit_message,
                    'chat_id' => $chat_id,
                    'reply_markup' => json_encode([
                    'keyboard' => [[$change_limit_text,$week_limit_text]],
                    'resize_keyboard' => True,
                    
                    ])  
                ]);
                break;
                
            case $change_limit_text:
                $this->apiRequest("sendmessage",[
                    'chat_id' => $chat_id,
                    'text' => "عامو حالا نمیشه عوضش نکنی؟"
                ]);
                break;
            
            case $week_limit_text:
                if($curr_date===4) {
                    $this->apiRequest('sendmessage',[
                        'text' => "امروز میتونی حد هفته بعد رو ست کنی",
                        'chat_id' => $chat_id,

                    ]);
                } else {
                    $this->apiRequest("sendmessage" , [
                        'text' => $error_week_limit,
                        'chat_id' => $chat_id,
                        'reply_markup' => json_encode([
                            'keyboard' => [$buttons],
                            'resize_keyboard' => True,
                        ])
                    ]);
                }
                break;
                
            default:
                
                $user = $this->db->get_user_info($chat_id);
                $qs = intval($user->question_status);
                $ls = intval($user->limit_status);
                $cs = intval($user->coupon_status);
                
                
                
                if($qs===0 and $ls === 0 and $cs ===0) {
                    $this->apiRequest('sendmessage',[
                        'text'=> $error_unrelevant,
                        'chat_id' => $chat_id
                    ]);
                } else if($qs != 0) {
                    $answer = $message;
                    $q_id = $qs;
                    
                    $day = $this->db->get_day();
                    
                    $questions = $this->db->get_all_questions();
                    
                    $photo = $message_obj->photo;
                    
                    if($questions[$q_id -1]->type == "img" and !isset($photo)) {
                        $this->send_error($chat_id, "img");

                    } else if($questions[$q_id-1]->type == "text" and isset($photo)) {
                        $this->send_error($chat_id, "text");

                    } else {
                        
                        if(isset($photo)) {
                            
                            
                            $file_id = $this->apiRequest('getFile',[
                                'file_id' => end($photo)->file_id
                            ]);
                            
                            
                            $file_path = $file_id['file_path'];
                            $img_name = end($photo)->file_unique_id;
                            
                            $download_link = $this->get_download_link($file_path);
                            
                            $ext = end(explode('.', $download_link));
                            

                            
                            $img = 'images/' . $img_name . "." . $ext;
                            
                            file_put_contents($img, file_get_contents($download_link));
                            

                            $this->db->save_answer($telegram_user, $q_id, $img,$day);
                            $this->send_questions($telegram_user);
                            
                        } else {
                            $this->db->save_answer($telegram_user, $q_id, $answer,$day);
                            $this->send_questions($telegram_user);
                            
                        }
                        
                        
                    }
                    
                    
                    
                    
                    

                } else if($ls != 0) {
                    $this->apiRequest('sendmessage',[
                        'text' => 'میخوای حد عوض کنی؟',
                        'chat_id' => $chat_id
                    ]);
                } 
                    
                    
                
                
                
            break;
        }
    }
    
    public function change_limit_questions($tel_user) {
        
    }
    
    public function send_questions($tel_user) {
        $response = $this->db->get_questions($tel_user);
        $question = $response[1];
        $state = $response[0];
        $q_num = $response[2];
        
        if($response === False) {
            $response = $this->db->get_questions($tel_user);
            $question = $response[1];
            $state = $response[0];
            $q_num = $response[2];
        }
        
//         $this->apiRequest('sendmessage',[
//             'text' => json_encode($response),
//             'chat_id' => $tel_user->id
//         ]);
        
        if(intval($state) === $q_num) {
            
            $report = $this->send_report($tel_user);
            if(isset($report)) {
                $this->db->reset_status("qs",$tel_user->id);
            }
        } else {
            
        
            $text = str_replace("[name]", $tel_user->first_name ,$question->text);
            $message = $this->apiRequest('sendmessage',[
                'text' => $text,
                'chat_id' => $tel_user->id
            ]);
            
            if (isset($message)) {
                $this->db->reset_status("qs",$tel_user->id,intval($state)+1);
            }
        
        }
        
        
    }
    
    public function send_report($tel_user) {
        
        $day = (string)$this->db->get_day();
        $user = $this->db->get_user_info($tel_user->id);
        
        $mixed = $this->db->get_report($tel_user->id,$day,True);
        $date = strtotime($mixed[0]->time);

        $limit = $this->db->get_limit($day, $tel_user);
        
        if($limit ==-1) {
            $limit_text = "تعریف نشده";
        } else {
            $limit_text = (string)$limit . " ساعت";

        }
        
        
        $date = jdate($date);
        $p_date = $date->format('%A,%d,%B,%Y');
        
        
        
        $report_text = "#" . "گزارش" . $day . "\n" .
            $p_date .  "\n" .
            "روز " . $day . "\n" .
            "<b>" . "$user->name" . "</b>" . "\n" .
            "حد امروز  " . "<b>" . $limit_text. "</b>" . "\n";
        
        
        foreach ($mixed as $mix) {
            $answer = $mix->answer;
            if($mix->question_id == 8) {
                if($mix->answer == 0) {
                    $answer = "ندادم";
                } else if ($mix->answer == 1) {
                    $answer = "دادم";
                }
            }
            
            if ($mix->question_id == 10) {
                if($mix->answer == 0) {
                    $answer = "نمیکنم";
                } else if ($mix->answer == 1) {
                    $answer = "میکنم";
                }
            }
            $report_text .= $mix->report . "  ". "<b>" . $answer . $mix->unit . "</b>" . "\n"  ;
        }
        

        
        $base_url = "https://milad4274.ir/bots/moshavere_bot/" ;
        

        $programming_image = $this->db->get_answer_question(3, $user->id);
        
        $calculate_answer = $this->db->get_answer_question(8, $user->id);
        
        
        
        $report = $this->apiRequest("sendphoto",[
            'chat_id' => $tel_user->id,
            'caption' => $report_text,
            'photo' => $base_url . $programming_image,
            'parse_mode' => 'HTML'
        ]);
        
        if($calculate_answer == 1) {
            $calc_image = $this->db->get_answer_question(9, $user->id);
            
            $calc_test = "#" . "محاسبات" . "\n" .
                "روز " . (string)$day . "\n" . 
                $user->name ; 
                
            
            $this->apiRequest("sendphoto",[
                'chat_id' => $tel_user->id,
                'caption' => $calc_test,
                'photo' => $base_url . $calc_image,
                'parse_mode' => 'HTML'
            ]);
            
        }

        return $report;
    }
    
    public function send_error($chat_id,$type) {
        switch ($type) {
            case "img":
                $message = "لطفا تصویر معتبر ارسال کنید.";
                break;
            case "text":
                $message = "لطفا متن ارسال کنید.";
                break;
            default:
                $message = "در پاسخ شما خطایی وجود دارد";
                
        }
        
        $this->apiRequest('sendmessage',[
            'text' => $message,
            'chat_id' => $chat_id
        ]);
    }
    
    public function get_download_link($file_path) {
        $link = "https://api.telegram.org/file/bot" . $this->token . "/" . $file_path;
        return $link;
    }

        

    public function apiRequest($method,$parameters=[])
    {
        $url = "https://api.telegram.org/bot" . $this->token . "/" .$method;
        $handle = curl_init($url);
        $headers = array(
            "Content-Type: text/html; charset: UTF-8"
        );
        curl_setopt($handle,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($handle,CURLOPT_CONNECTTIMEOUT,5);
        curl_setopt($handle,CURLOPT_TIMEOUT,60);
        curl_setopt($handle, CURLOPT_POST, $headers );
        curl_setopt($handle,CURLOPT_POSTFIELDS,http_build_query($parameters));
        $response = curl_exec($handle);


        if ($response === false) {
            curl_close($handle);
            return false;
        }
        curl_close($handle);
        $response = json_decode($response, true);
        if ($response['ok'] === false) {
            throw new Exception($response['description']);
        }
        $response = $response["result"];
        return $response;
    }
}