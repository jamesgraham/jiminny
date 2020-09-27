<?php namespace App\Controllers;

class Home extends BaseController {
    public function index() {
        $data['result'] = [];
        $this->validation->setRule('data_file_user_channel', 'User channel Data file', 'uploaded[data_file_user_channel]|ext_in[data_file_user_channel,txt]');
        $this->validation->setRule('data_file_customer_channel', 'Customer channel Data file', 'uploaded[data_file_customer_channel]|ext_in[data_file_customer_channel,txt]');
        if ($this->request->getPost() && $this->validation->withRequest($this->request)->run()) {
            // Get User channel file
            $user_channel_file = $this->request->getFile('data_file_user_channel');
            // Read user file data
            $user_channel_file_content = file_get_contents($user_channel_file);
            $user_speech = [];
            $user_channel = [];
            // remove [silencedetect @ 0x7fbfbbc076a0] including white space after "]"
            if (preg_match_all("/^\[silencedetect\s[^]]+\]\s([^\n]+)/m", $user_channel_file_content, $user_channel_file_data)) {
                $user_speech_start[0] = 0;
                $user_speech_end[0] = 0;
                $u = 0;
                foreach ($user_channel_file_data[1] as $user_channel_data) {
                    // Find silence_start in single line end get the value
                    if (preg_match('/^[silence_start]+\S\s([^\n]+)/m', $user_channel_data, $user_silence_start)) {
                        $user_speech_end[$u] = $user_silence_start[1];
                        // Save speech points into array
                        $user_speech[] = [(float)$user_speech_start[$u], (float)$user_speech_end[$u]];
                        // Prevent saving noise in the array
                        if((int)($user_speech_end[$u] - $user_speech_start[$u]) >0) {
                            $user_channel[$u]['speech_start'] = (float)$user_speech_start[$u];
                            $user_channel[$u]['speech_end'] = (float)$user_speech_end[$u];
                            $user_channel[$u]['speech_duration'] = ((float)$user_speech_end[$u] - (float)$user_speech_start[$u]);
                            $user_channel[$u]['silence_start'] = (float)$user_silence_start[1];
                            $u++;
                        }
                    } else {
                        $user_channel_data = explode(' | ', $user_channel_data);
                        // Find silence_end end get the value
                        if (preg_match('/^[silence_end]+\S\s([^\n]+)/m', $user_channel_data[0], $user_silence_end)) {
                            $user_speech_start[$u] = trim($user_silence_end[1]);
                            $user_channel[$u - 1]['silence_end'] = (float)$user_silence_end[1];
                            // Calculate new silence_duration after removing noise
                            $user_channel[$u - 1]['silence_duration'] = $user_channel[$u - 1]['silence_end'] - $user_channel[$u-1]['silence_start'];
                        }
                    }
                }
            }

            // Get Customer channel file
            $customer_channel_file = $this->request->getFile('data_file_customer_channel');
            // Read user file data
            $customer_channel_file_content = file_get_contents($customer_channel_file);
            $customer_speech = [];
            $customer_channel = [];
            // remove [silencedetect @ 0x7fa7edd0c160] including white space after "]"
            if (preg_match_all("/^\[silencedetect\s[^]]+\]\s([^\n]+)/m", $customer_channel_file_content, $customer_channel_file_data)) {
                $customer_speech_start[0] = 0;
                $customer_speech_end[0] = 0;
                $c = 0;
                foreach ($customer_channel_file_data[1] as $customer_channel_data) {
                    // Find silence_start in single line end get the value
                    if (preg_match('/^[silence_start]+\S\s([^\n]+)/m', $customer_channel_data, $customer_silence_start)) {
                        $customer_speech_end[$c] = $customer_silence_start[1];
                        // Save speech points into array
                        $customer_speech[] = [$customer_speech_start[$c], $customer_speech_end[$c]];
                        // Prevent saving noise in the array
                        if((int)($customer_speech_end[$c] - $customer_speech_start[$c]) >0) {
                            $customer_channel[$c]['speech_start'] = $customer_speech_start[$c];
                            $customer_channel[$c]['speech_end'] = $customer_speech_end[$c];
                            $customer_channel[$c]['speech_duration'] = $customer_speech_end[$c] - $customer_speech_start[$c];
                            $customer_channel[$c]['silence_start'] = $customer_silence_start[1];
                            $c++;
                        }
                    } else {
                        $customer_channel_data = explode(' | ', $customer_channel_data);
                        // Find silence_end end get the value
                        if (preg_match('/^[silence_end]+\S\s([^\n]+)/m', $customer_channel_data[0], $customer_silence_end)) {
                            $customer_speech_start[$c] = trim($customer_silence_end[1]);
                            if (isset($customer_channel[$c - 1]['silence_start'])) {
                                $customer_channel[$c - 1]['silence_end'] = $customer_silence_end[1];
                                // Calculate new silence_duration after removing noise
                                $customer_channel[$c - 1]['silence_duration'] = $customer_channel[$c - 1]['silence_end'] - $customer_channel[$c - 1]['silence_start'];
                            }
                        }
                    }
                }
            }
            // Part 1
            $data['result']['user'] = $user_speech;
            $data['result']['customer'] = $customer_speech;
            //            var_dump(json_encode($user_speech));
            //            var_dump(json_encode($customer_speech));

            // Part 2
            // Sort user data by speech duration time (ascendant)
            usort($user_channel, function ($current, $next) {
                return $next['speech_duration'] <=> $current['speech_duration'];
            });
//            dd($user_channel);
//            foreach($user_channel as $user_speech){
//
//            }

            // Sort customer data by speech duration time (ascendant)
            usort($customer_channel, function ($current, $next) {
                return $next['speech_duration'] <=> $current['speech_duration'];
            });
//            dd($customer_channel);

            // Part 3
            $user_talk =0;
            foreach ($user_speech as $speech){
                $user_talk += $speech[1]-$speech[0];
            }
//            var_dump($user_talk);

            $user_call_duration = end($user_speech)[1];
            $customer_call_duration = end($customer_speech)[1];
            if($user_call_duration>$customer_call_duration){
                $total_call_duration = $user_call_duration;
            }else {
                $total_call_duration = $customer_call_duration;
            }

            $user_talk_percentage = ($user_talk / $total_call_duration)*100;

//            dd($user_talk_percentage);
            //            $this->session->setFlashdata('message', 'Success!');
        }
        $data['message'] = $this->validation->getErrors() ? $this->validation->listErrors() : $this->session->getFlashdata('message');

        return view('home_view', $data);
    }

}
