<?php namespace App\Controllers;

class Home extends BaseController {
    public function index() {
        $data['result'] = [];
        $this->validation->setRule('data_file_user_channel', 'User channel Data file', 'uploaded[data_file_user_channel]|ext_in[data_file_user_channel,txt]');
        $this->validation->setRule('data_file_customer_channel', 'Customer channel Data file', 'uploaded[data_file_customer_channel]|ext_in[data_file_customer_channel,txt]');
        if ($this->request->getPost() && $this->validation->withRequest($this->request)->run()) {
            //User channel file
            $user_channel_file = $this->request->getFile('data_file_user_channel');
            $user_channel_file_content = file_get_contents($user_channel_file);
            $user_speech = [];
            if (preg_match_all("/^\[silencedetect\s[^]]+\]\s([^\n]+)/m", $user_channel_file_content, $user_channel_file_data)) {
                $user_speech_start[0] = 0;
                $user_speech_end[0] = 0;
                $u = 0;
                foreach ($user_channel_file_data[1] as $user_channel_data) {
                    if (preg_match('/^[silence_start]+\S\s([^\n]+)/m', $user_channel_data, $user_silence_start)) {
                        $user_speech_end[$u] = $user_silence_start[1];
                        $user_speech[] = [$user_speech_start[$u], $user_speech_end[$u]];
                        $u++;
                    } else {
                        $user_channel_data = explode('|', $user_channel_data);
                        if (preg_match('/^[silence_end]+\S\s([^\n]+)/m', $user_channel_data[0], $user_silence_end)) {
                            $user_speech_start[$u] = trim($user_silence_end[1]);
                        }
                    }
                }
            }

            //Customer channel file
            $customer_channel_file = $this->request->getFile('data_file_customer_channel');
            $customer_channel_file_content = file_get_contents($customer_channel_file);
            $customer_speech = [];
            if (preg_match_all("/^\[silencedetect\s[^]]+\]\s([^\n]+)/m", $customer_channel_file_content, $customer_channel_file_data)) {
                $customer_speech_start[0] = 0;
                $customer_speech_end[0] = 0;
                $c = 0;
                foreach ($customer_channel_file_data[1] as $customer_channel_data) {
                    if (preg_match('/^[silence_start]+\S\s([^\n]+)/m', $customer_channel_data, $customer_silence_start)) {
                        $customer_speech_end[$c] = $customer_silence_start[1];
                        $customer_speech[] = [$customer_speech_start[$c], $customer_speech_end[$c]];
                        $c++;
                    } else {
                        $customer_channel_data = explode('|', $customer_channel_data);
                        if (preg_match('/^[silence_end]+\S\s([^\n]+)/m', $customer_channel_data[0], $customer_silence_end)) {
                            $customer_speech_start[$c] = trim($customer_silence_end[1]);
                        }
                    }
                }
            }
            //Part 1
            //            var_dump(json_encode($user_speech));
            //            var_dump(json_encode($customer_speech));
            $data['result']['user'] = $user_speech;
            $data['result']['customer'] = $customer_speech;

            //Part 2
            $user_speech_duration[] = 0;
            $tmp=0;
            foreach ($user_speech as $key => $value) {
//                $d = $value[1] - $value[0];
//                var_dump($d);
//                if ($user_speech[$key][1] > $customer_speech[$key][1]) {
//
//
//                    if ($d <= 0) {
//                        $tmp = $d+$tmp;
//                    } else {
//                        $tmp = 0;
//                    }
//                }else{
//
//                }
//
//
//                    $user_speech_duration[$key] = $d + $tmp;
            }
//            var_dump($user_speech_duration);

            //            $this->session->setFlashdata('message', 'Success!');
        }
        $data['message'] = $this->validation->getErrors() ? $this->validation->listErrors() : $this->session->getFlashdata('message');

        return view('home_view', $data);
    }

}
