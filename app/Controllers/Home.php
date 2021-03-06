<?php namespace App\Controllers;

class Home extends BaseController {
    public function index() {
        $data['status'] = false;
        $data['result']['longest_user_monologue'] = 0;
        $data['result']['longest_customer_monologue'] = 0;
        $data['result']['user_talk_percentage'] = 0;
        $data['result']['user'] = 0;
        $data['result']['customer'] = 0;
        $this->validation->setRule('data_file_user_channel', 'User channel Data file', 'uploaded[data_file_user_channel]|ext_in[data_file_user_channel,txt]');
        $this->validation->setRule('data_file_customer_channel', 'Customer channel Data file', 'uploaded[data_file_customer_channel]|ext_in[data_file_customer_channel,txt]');
        if ($this->request->getPost() && $this->validation->withRequest($this->request)->run()) {
            // Get User channel file
            $user_channel_file = $this->request->getFile('data_file_user_channel');
            // Read user file data
            $user_channel_file_content = file_get_contents($user_channel_file);
            $user_speech = [];
            $user_channel = [];
            $user_speech_start = [];
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
                        $user_speech[] = [$user_speech_start[$u], $user_speech_end[$u]];
                        $user_speech_start[] = $user_speech_start[$u];
                        $user_channel[$u]['speech_start'] = $user_speech_start[$u];
                        $user_channel[$u]['speech_end'] = $user_speech_end[$u];
                        $user_channel[$u]['speech_duration'] = ($user_speech_end[$u] - $user_speech_start[$u]);
                        $user_channel[$u]['silence_start'] = $user_silence_start[1];
                        $u++;
                    } else {
                        $user_channel_data = explode(' | ', $user_channel_data);
                        // Find silence_end end get the value
                        if (preg_match('/^[silence_end]+\S\s([^\n]+)/m', $user_channel_data[0], $user_silence_end)) {
                            $user_speech_start[$u] = trim($user_silence_end[1]);
                            $user_channel[$u - 1]['silence_end'] = $user_silence_end[1];
                            // Calculate new silence_duration after removing noise
                            $user_channel[$u - 1]['silence_duration'] = $user_channel[$u - 1]['silence_end'] - $user_channel[$u - 1]['silence_start'];
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
            $customer_speech_start=[];
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
                        $customer_speech_start[] = $customer_speech_start[$c];
                        $customer_channel[$c]['speech_start'] = $customer_speech_start[$c];
                        $customer_channel[$c]['speech_end'] = $customer_speech_end[$c];
                        $customer_channel[$c]['speech_duration'] = $customer_speech_end[$c] - $customer_speech_start[$c];
                        $customer_channel[$c]['silence_start'] = $customer_silence_start[1];
                        $c++;
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

            // Part 2
            // Remove all interrupted speech data from user channel
            foreach ($user_channel as $key=>$u_speech) {
                foreach ($customer_speech_start as $speech_start) {
                    if ($speech_start > $u_speech['speech_start'] && $speech_start < $u_speech['speech_end']) {
                        unset($user_channel[$key]);
                    }
                }
            }
            // Sort user data by speech duration time (ascendant)
            usort($user_channel, function ($current, $next) {
                return $next['speech_duration'] <=> $current['speech_duration'];
            });
            // Get longest un-interrupted user speech from the array
            if(!empty($user_channel)) {
                $data['result']['longest_user_monologue'] = number_format(array_values($user_channel)[0]['speech_duration'],3);
            }

            // Remove all interrupted speech data from customer channel
            foreach ($customer_channel as $key=>$c_speech) {
                foreach ($user_speech_start as $speech_start) {
                    if ($speech_start > $c_speech['speech_start'] && $speech_start < $c_speech['speech_end']) {
                        unset($customer_channel[$key]);
                    }
                }
            }
            // Sort customer data by speech duration time (ascendant)
            usort($customer_channel, function ($current, $next) {
                return $next['speech_duration'] <=> $current['speech_duration'];
            });
            // Get longest un-interrupted customer speech from the array
            if(!empty($customer_channel)) {
                $data['result']['longest_customer_monologue'] = number_format(array_values($customer_channel)[0]['speech_duration'],3);
            }

            // Part 3
            $user_talk = 0;
            // Get total time of user talk
            foreach ($user_speech as $speech) {
                $user_talk += $speech[1] - $speech[0];
            }

            // Find total call duration time
            $user_call_duration = end($user_speech)[1];
            $customer_call_duration = end($customer_speech)[1];
            if ($user_call_duration > $customer_call_duration) {
                $total_call_duration = $user_call_duration;
            } else {
                $total_call_duration = $customer_call_duration;
            }

            $data['result']['user_talk_percentage'] = number_format(($user_talk / $total_call_duration) * 100, 2);

            $data['status'] = true;
            //            $this->session->setFlashdata('message', 'Success!');
        }
        $data['message'] = $this->validation->getErrors() ? $this->validation->listErrors() : $this->session->getFlashdata('message');

        return view('home_view', $data);
    }

}
