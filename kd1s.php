<?php

require __DIR__.'/vendor/autoload.php';
$ig = new \InstagramAPI\Instagram();

output_clean("
 Masslooking-Story-Instagram
 Fixed By Kasper  @6zx
");

output_clean("");
output_clean("@6zx");
output_clean("v.9.3");
output_clean("");
output_clean("");

run($ig);

/**
 * Let's start the show
 */
function run($ig) {
    try {
        output('Please provide login data of your Instagram Account.');
        $login = getVarFromUser("Username :");
        if (empty($login)) {
            do { 
                $login = getVarFromUser("Username :"); 
            } while (empty($login));
        }

        $password = getVarFromUser("Password :");
        if (empty($password)) {
            do { 
                $password = getVarFromUser("Password :");
            } while (empty($password));
        }

        $first_loop = true;
        do {
            if ($first_loop) {
                output("(Optional) Set proxy, if needed. It's better to use a proxy from the same country where you running this script.");
                output("Proxy should match following pattern:");
                output("http://ip:port or http://username:password@ip:port");
                output("Don't use in pattern https://.");
                output("Type 3 to skip and don't use proxy.");
                $first_loop = false;
            } else {
                output("Proxy - [NOT VALID]");
                output("Please check the proxy syntax and try again.");
            }

            $proxy = getVarFromUser("Proxy");

            if (empty($proxy)) {
                do { 
                    $proxy = getVarFromUser("Proxy");
                } while (empty($proxy));
            }

            if ($proxy == '3') {
                // Skip proxy setup
                break;
            }
        } while (!isValidProxy($proxy));

        if ($proxy == "3") {
           // Skip proxy setup
        } else {
            output("Proxy - [OK]");
            $ig->setProxy($proxy);
        }

        output('Please choose the masslooking estimated speed.');
        output('Type integer value without spaces from 1 to 500 000 stories/day or 0 for maximum possible speed.');
        output('When you are using the maximum speed you may exceed the masslooking limits per day if this account actively used by a user in the Instagram app at the same time.');
        output('If you are using another type of automation, we recommend to you reducing masslooking speed to find your golden ratio. In that case we recommend 400 000 stories/day.');
        $speed = (int)getVarFromUser("Speed");

        if ($speed > 500000) {
            do { 
                output("Speed value is incorrect. Type integer value from 1 to 500 000 stories/day.");
                output('Type 0 for maximum speed.');
                $speed = (int)getVarFromUser("Delay");
            } while ($speed > 500000);
        }

        if ($speed == 0) {
            output("Maximum speed enabled.");
            $delay = 34;
        } else {
            output("Speed set to " . $speed . " stories/day.");
            $delay = round(60*60*24*200/$speed);
        }

        $is_connected = false;
        $is_connected_count = 0;
        $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";

        do {
            if ($is_connected_count == 10) {
                if ($e->getResponse()) {
                    output($e->getMessage());
                }
                throw new Exception($fail_message);
            }

            try {
                if ($is_connected_count == 0) {
                    output("Emulation of an Instagram app initiated...");
                }
                $login_resp = $ig->login($login, $password);
    
                if ($login_resp !== null && $login_resp->isTwoFactorRequired()) {
                    // Default verification method is phone
                    $twofa_method = '1';
    
                    // Detect is Authentification app verification is available 
                    $is_totp = json_decode(json_encode($login_resp), true);
                    if ($is_totp['two_factor_info']['totp_two_factor_on'] == '1'){
                        output("Two-factor authentication required, please enter the code from you Authentication app");
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                        $twofa_method = '3';
                    } else {
                        output("Two-factor authentication required, please enter the code sent to your number ending in %s", 
                            $login_resp->getTwoFactorInfo()->getObfuscatedPhoneNumber());
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                    }
    
                    $twofa_code = getVarFromUser("Two-factor code");
    
                    if (empty($twofa_code)) {
                        do { 
                            $twofa_code = getVarFromUser("Two-factor code");
                        } while (empty($twofa_code));
                    }
    
                    $is_connected = false;
                    $is_connected_count = 0;
                    do {
                        if ($is_connected_count == 10) {
                            if ($e->getResponse()) {
                                output($e->getMessage());
                            }
                            throw new Exception($fail_message);
                        }

                        if ($is_connected_count == 0) {
                            output("Two-factor authentication in progress...");
                        }

                        try {
                            $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                            $is_connected = true;
                        } catch (\InstagramAPI\Exception\NetworkException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                            $is_code_correct = false;
                            $is_connected= true;
                            do {
                                output("Code is incorrect. Please check the syntax and try again.");
                                $twofa_code = getVarFromUser("Two-factor code");
            
                                if (empty($twofa_code)) {
                                    do { 
                                        $twofa_code = getVarFromUser("Security code");
                                    } while (empty($twofa_code));
                                }
            
                                $is_connected = false;
                                $is_connected_count = 0;
                                do {
                                    try {
                                        if ($is_connected_count == 10) {
                                            if ($e->getResponse()) {
                                                output($e->getMessage());
                                            }
                                            throw new Exception($fail_message);
                                        }

                                        if ($is_connected_count == 0) {
                                            output("Verification in progress...");
                                        }
                                        $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                                        $is_code_correct = true;
                                        $is_connected = true;
                                    } catch (\InstagramAPI\Exception\NetworkException $e) { 
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                                        $is_code_correct = false;
                                        $is_connected = true;
                                    } catch (\Exception $e) {
                                        throw $e;
                                    }
                                    $is_connected_count += 1;
                                } while (!$is_connected);
                            } while (!$is_code_correct);
                        } catch (\Exception $e) {
                            throw $e;
                        }

                        $is_connected_count += 1;
                    } while (!$is_connected);
                }

                $is_connected = true;
            } catch (\InstagramAPI\Exception\NetworkException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                throw new Exception("Please go to Instagram website or mobile app and pass checkpoint!");
            } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

                if (!($ig instanceof InstagramAPI\Instagram)) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (invalid_instagram_client)");
                }
        
                if (!($e instanceof InstagramAPI\Exception\ChallengeRequiredException)) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (unexpected_exception)");
                }

                if (!$e->hasResponse() || !$e->getResponse()->isChallenge()) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (unexpected_exception_response)");
                }
        
                $challenge = $e->getResponse()->getChallenge();

                if (is_array($challenge)) {
                    $api_path = $challenge["api_path"];
                } else {
                    $api_path = $challenge->getApiPath();
                }

                output("Instagram want to send you a security code to verify your identity.");
                output("How do you want receive this code?");
                output("1 - [Email]");
                output("2 - [SMS]");
                output("3 - [Exit]");

                $choice = getVarFromUser("Choice");

                if (empty($choice)) {
                    do { 
                        $choice = getVarFromUser("Choice");
                    } while (empty($choice));
                }

                if ($choice == '1' || $choice == '2' || $choice == '3') {
                    // All fine
                } else {
                    $is_choice_ok = false;
                    do {
                        output("Choice is incorrect. Type 1, 2 or 3.");
                        $choice = getVarFromUser("Choice");

                        if (empty($choice)) {
                            do { 
                                $choice = getVarFromUser("Choice");
                            } while (empty($choice));
                        }

                        if ($confirm == '1' || $confirm == '2' || $confirm == '3') { 
                            $is_choice_ok = true;
                        }
                    } while (!$is_choice_ok);
                }

                $challange_choice = 0;
                if ($choice == '3') {
                    run($ig);
                } elseif ($choice == '1') {
                    // Email
                    $challange_choice = 1;
                } else {
                    // SMS
                    $challange_choice = 0;
                }

                $is_connected = false;
                $is_connected_count = 0;
                do {
                    if ($is_connected_count == 10) {
                        if ($e->getResponse()) {
                            output($e->getMessage());
                        }
                        throw new Exception($fail_message);
                    }

                    try {
                        $challenge_resp = $ig->sendChallangeCode($api_path, $challange_choice);

                        // Failed to send challenge code via email. Try with SMS.
                        if ($challenge_resp->status != "ok") {
                            $challange_choice = 0;
                            sleep(7);
                            $challenge_resp = $ig->sendChallangeCode($api_path, $challange_choice);
                        }

                        $is_connected = true;
                    } catch (\InstagramAPI\Exception\NetworkException $e) {
                        sleep(7);
                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                        sleep(7);
                    } catch (\Exception $e) {
                        throw $e;
                    }

                    $is_connected_count += 1;
                } while (!$is_connected);
                
                if ($challenge_resp->status != "ok") {
                    if (isset($challenge_resp->message)) {
                        if ($challenge_resp->message == "This field is required.") {
                            output("We received the response 'This field is required.'. This can happen in 2 reasons:");
                            output("1. Instagram already sent to you verification code to your email or mobile phone number. Please enter this code.");
                            output("2. Instagram forced you to phone verification challenge. Try login to Instagram app or website and take a look at what happened.");
                        }
                    } else {
                        output("Instagram Response: " . json_encode($challenge_resp));
                        output("Couldn't send a verification code for the login challenge. Please try again later.");
                        output("- Is this account has attached mobile phone number in settings?");
                        output("- If no, this can be a reason of this problem. You should add mobile phone number in account settings.");
                        throw new Exception("- Sometimes Instagram can force you to phone verification challenge process.");
                    }
                }

                if (isset($challenge_resp->step_data->contact_point)){
                    $contact_point = $challenge_resp->step_data->contact_point;
                    if ($choice == 2) {
                        output("Enter the code sent to your number ending in " . $contact_point . ".");
                    } else {
                        output("Enter the 6-digit code sent to the email address " . $contact_point . ".");
                    }
                }

                $security_code = getVarFromUser("Security code");

                if (empty($security_code)) {
                    do { 
                        $security_code = getVarFromUser("Security code");
                    } while (empty($security_code));
                }

                if ($security_code == "3") {
                    throw new Exception("Reset in progress...");
                }

                // Verification challenge
                $ig = challange($ig, $login, $password, $api_path, $security_code, $proxy);

            } catch (\InstagramAPI\Exception\AccountDisabledException $e) {
                throw new Exception("Your account has been disabled for violating Instagram terms. Go Instagram website or mobile app to learn how you may be able to restore your account.");
            } catch (\InstagramAPI\Exception\ConsentRequiredException $e) {
                throw new Exception("Instagram updated Terms and Data Policy. Please go to Instagram website or mobile app to review these changes and accept them.");
            } catch (\InstagramAPI\Exception\SentryBlockException $e) {
                throw new Exception("Access to Instagram API restricted for spam behavior or otherwise abusing. You can try to use Session Catcher script (available by https://kd1s.com/session-catcher) to get valid Instagram session from location, where your account created from.");
            } catch (\InstagramAPI\Exception\IncorrectPasswordException $e) {
                throw new Exception("The password you entered is incorrect. Please try again.");
            } catch (\InstagramAPI\Exception\InvalidUserException $e) {
                throw new Exception("The username you entered doesn't appear to belong to an account. Please check your username and try again.");
            } catch (\Exception $e) {
                throw $e;
            }

            $is_connected_count += 1;
        } while (!$is_connected);

        output("Logged as @" . $login . " successfully.");

        $data = define_targets($ig);

        // Disabled, because this feature not supported at the moment
        // Because of new Instagram limits
        $is_parsers_enabled = false;

        if (!$is_parsers_enabled) {
            masslooking_v2($data, $ig, $delay);
        } else {
            output('Do you want to add parsers? (additional Instagram accounts for speed up masslooking speed)');
            output("1 - [Yes]");
            output("2 - [No]");
            output("3 - [Exit]");

            $confirm_p = getVarFromUser("Choice");

            if (empty($confirm_p)) {
                do { 
                    $confirm_p = getVarFromUser("Choice");
                } while (empty($confirm_p));
            }

            if ($confirm_p == '1' || $confirm_p == '2' || $confirm_p == '3') {
                // All fine
            } else {
                $is_choice_ok = false;
                do {
                    output("Choice is incorrect. Type 1, 2 or 3.");
                    $confirm_p = getVarFromUser("Choice");

                    if (empty($confirm_p)) {
                        do { 
                            $confirm_p = getVarFromUser("Choice");
                        } while (empty($confirm_p));
                    }

                    if ($confirm_p == '1' || $confirm_p == '2' || $confirm_p == '3') { 
                        $is_choice_ok = true;
                    }
                } while (!$is_choice_ok);
            }

            if ($confirm_p == '3') {
                throw new Exception("Reset in progress...");
            } elseif ($confirm_p == '2') {
                masslooking_v2($data, $ig, $delay);
            } else {
                // Yes, I want to add parsers.
            }

            output('How many parsers do you want to add? (integer value from 1 to 5)');
            output("Type 11 - to don't use parsers and start masslooking.");
            output("Type 12 - to exit.");
            $parsers_count = (int)getVarFromUser("Parsers count");

            if ($parsers_count > 12) {
                do { 
                    output("Parsers count is incorrect. Type integer value from 1 to 12.");
                    $parsers_count = (int)getVarFromUser("Parsers count");
                } while ($parsers_count > 12);
            } 
            
            if ($parsers_count == 11) {
                masslooking_v2($data, $ig, $delay);
            } elseif ($parsers_count == 12) {
                throw new Exception("Reset in progress...");
            } else {
                output("You want to add " . $parsers_count . " parser(s).");
                output("Let's get started.");
            }

            for ($i = 1; $i < count($parsers_count); $i++) {
                $ig_p[] = add_parser($i);
            }

            output("All " . $parsers_count . " parser(s) successfully connected.");

            // $user_resp = $ig->people->getInfoById($data[0]["pk"]);
            // output(json_encode($user_resp));

            // $user_resp = $ig_p[1]->people->getInfoById($data[1]["pk"]);
            // output(json_encode($user_resp));

            // $user_resp = $ig_p[2]->people->getInfoById($data[2]["pk"]);
            // output(json_encode($user_resp));

        }

    } catch (\Exception $e){
        output($e->getMessage());
        output("Please run script command again.");
        exit;
    }
}

/**
 * Add parser
 */
function add_parser($pn) {
    try {
        $igp = new \InstagramAPI\Instagram();

        output("Please provide login data of your parser #" . $pn . " (additional Instagram account for speed up masslooking speed)");
            
        $login = getVarFromUser("Login");
        if (empty($login)) {
            do { 
                $login = getVarFromUser("Login"); 
            } while (empty($login));
        }

        $password = getVarFromUser("Password");
        if (empty($password)) {
            do { 
                $password = getVarFromUser("Password");
            } while (empty($password));
        }

        $first_loop = true;
        do {
            if ($first_loop) {
                output("(Optional) Set proxy, if needed. It's better to use a proxy from the country from you running this script.");
                output("Proxy should match following pattern:");
                output("http://ip:port or http://username:password@ip:port");
                output("Don't use in pattern https://.");
                output("Type 3 to skip and don't use proxy.");
                $first_loop = false;
            } else {
                output("Proxy - [NOT VALID]");
                output("Please check the proxy syntax and try again.");
            }

            $proxy = getVarFromUser("Proxy");

            if (empty($proxy)) {
                do { 
                    $proxy = getVarFromUser("Proxy");
                } while (empty($proxy));
            }

            if ($proxy == '3') {
                // Skip proxy setup
                break;
            }
        } while (!isValidProxy($proxy));

        if ($proxy == "3") {
            // Skip proxy setup
        } else {
            output("Proxy - [OK]");
            $igp->setProxy($proxy);
        }

        $is_connected = false;
        $is_connected_count = 0;
        $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";

        do {
            if ($is_connected_count == 10) {
                if ($e->getResponse()) {
                    output($e->getMessage());
                }
                throw new Exception($fail_message);
            }

            try {
                if ($is_connected_count == 0) {
                    output("Emulation of an Instagram app for parser #" . $pn . " initiated...");
                }
                $login_resp = $igp->login($login, $password);

                if ($login_resp !== null && $login_resp->isTwoFactorRequired()) {
                    // Default verification method is phone
                    $twofa_method = '1';

                    // Detect is Authentification app verification is available 
                    $is_totp = json_decode(json_encode($login_resp), true);
                    if ($is_totp['two_factor_info']['totp_two_factor_on'] == '1'){
                        output("Two-factor authentication required, please enter the code from you Authentication app");
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                        $twofa_method = '3';
                    } else {
                        output("Two-factor authentication required, please enter the code sent to your number ending in %s", 
                            $login_resp->getTwoFactorInfo()->getObfuscatedPhoneNumber());
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                    }

                    $twofa_code = getVarFromUser("Two-factor code");

                    if (empty($twofa_code)) {
                        do { 
                            $twofa_code = getVarFromUser("Two-factor code");
                        } while (empty($twofa_code));
                    }

                    $is_connected = false;
                    $is_connected_count = 0;
                    do {
                        if ($is_connected_count == 10) {
                            if ($e->getResponse()) {
                                output($e->getMessage());
                            }
                            throw new Exception($fail_message);
                        }

                        if ($is_connected_count == 0) {
                            output("Two-factor authentication in progress...");
                        }

                        try {
                            $twofa_resp = $igp->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                            $is_connected = true;
                        } catch (\InstagramAPI\Exception\NetworkException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                            $is_code_correct = false;
                            $is_connected= true;
                            do {
                                output("Code is incorrect. Please check the syntax and try again.");
                                $twofa_code = getVarFromUser("Two-factor code");
            
                                if (empty($twofa_code)) {
                                    do { 
                                        $twofa_code = getVarFromUser("Security code");
                                    } while (empty($twofa_code));
                                }
            
                                $is_connected = false;
                                $is_connected_count = 0;
                                do {
                                    try {
                                        if ($is_connected_count == 10) {
                                            if ($e->getResponse()) {
                                                output($e->getMessage());
                                            }
                                            throw new \Exception($fail_message);
                                        }

                                        if ($is_connected_count == 0) {
                                            output("Verification in progress...");
                                        }
                                        $twofa_resp = $igp->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                                        $is_code_correct = true;
                                        $is_connected = true;
                                    } catch (\InstagramAPI\Exception\NetworkException $e) { 
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                                        $is_code_correct = false;
                                        $is_connected = true;
                                    } catch (\Exception $e) {
                                        throw $e;
                                    }
                                    $is_connected_count += 1;
                                } while (!$is_connected);
                            } while (!$is_code_correct);
                        } catch (\Exception $e) {
                            throw $e;
                        }

                        $is_connected_count += 1;
                    } while (!$is_connected);
                }

                $is_connected = true;
            } catch (\InstagramAPI\Exception\NetworkException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                throw new Exception("Please go to Instagram website or mobile app and pass checkpoint!");
            } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {

                if (!($igp instanceof InstagramAPI\Instagram)) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (invalid_instagram_client)");
                }
        
                if (!($e instanceof InstagramAPI\Exception\ChallengeRequiredException)) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (unexpected_exception)");
                }

                if (!$e->hasResponse() || !$e->getResponse()->isChallenge()) {
                    throw new Exception("Oops! Something went wrong. Please try again later! (unexpected_exception_response)");
                }
        
                $challenge = $e->getResponse()->getChallenge();

                if (is_array($challenge)) {
                    $api_path = $challenge["api_path"];
                } else {
                    $api_path = $challenge->getApiPath();
                }

                output("Instagram will send you a security code to verify your identity.");
                output("How do you want receive this code?");
                output("1 - [Email]");
                output("2 - [SMS]");
                output("3 - [Exit]");

                $choice = getVarFromUser("Choice");

                if (empty($choice)) {
                    do { 
                        $choice = getVarFromUser("Choice");
                    } while (empty($choice));
                }

                if ($choice == '1' || $choice == '2' || $choice == '3') {
                    // All fine
                } else {
                    $is_choice_ok = false;
                    do {
                        output("Choice is incorrect. Type 1, 2 or 3.");
                        $choice = getVarFromUser("Choice");
    
                        if (empty($choice)) {
                            do { 
                                $choice = getVarFromUser("Choice");
                            } while (empty($choice));
                        }

                        if ($confirm == '1' || $confirm == '2' || $confirm == '3') { 
                            $is_choice_ok = true;
                        }
                    } while (!$is_choice_ok);
                }
    
                $challange_choice = 0;
                if ($choice == '3') {
                    throw new Exception("Reset in progress...");
                } elseif ($choice == '1') {
                    // Email
                    $challange_choice = 1;
                } else {
                    // SMS
                    $challange_choice = 0;
                }

                $is_connected = false;
                $is_connected_count = 0;
                do {
                    if ($is_connected_count == 10) {
                        if ($e->getResponse()) {
                            output($e->getMessage());
                        }
                        throw new Exception($fail_message);
                    }

                    try {
                        $challenge_resp = $igp->sendChallangeCode($api_path, $challange_choice);
    
                        // Failed to send challenge code via email. Try with SMS.
                        if ($challenge_resp->status != "ok") {
                            $challange_choice = 0;
                            $challenge_resp = $igp->sendChallangeCode($api_path, $challange_choice);
                        }

                        $is_connected = true;
                    } catch (\InstagramAPI\Exception\NetworkException $e) {
                        sleep(7);
                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                        sleep(7);
                    } catch (\Exception $e) {
                        throw $e;
                    }

                    $is_connected_count += 1;
                } while (!$is_connected);
    
                if ($challenge_resp->message == "This field is required.") {
                    output("Instagram already sent to you verification code to your email or mobile phone number. Please enter this code.");
                } elseif ($challenge_resp->status != "ok") {
                    throw new Exception("Couldn't send a verification code for the login challenge. Please try again later.");
                }
    
                if (isset($challenge_resp->step_data->contact_point)){
                    $contact_point = $challenge_resp->step_data->contact_point;
                    if ($choice == 2) {
                        output("Enter the code sent to your number ending in " . $contact_point . ".");
                    } else {
                        output("Enter the 6-digit code sent to the email address " . $contact_point . ".");
                    }
                }

                output("Type 3 - to exit.");

                $security_code = getVarFromUser("Security code");

                if (empty($security_code)) {
                    do { 
                        $security_code = getVarFromUser("Security code");
                    } while (empty($security_code));
                }

                if ($security_code == "3") {
                    throw new Exception("Reset in progress...");
                }

                // Verification challenge
                $igp = challange($igp, $login, $password, $api_path, $security_code, $proxy);

                if ($igp == "3") {
                    throw new Exception("Reset in progress...");
                }

            } catch (\InstagramAPI\Exception\AccountDisabledException $e) {
                throw new Exception("Your account has been disabled for violating Instagram terms. Go Instagram website or mobile app to learn how you may be able to restore your account.");
            } catch (\InstagramAPI\Exception\ConsentRequiredException $e) {
                throw new Exception("Instagram updated Terms and Data Policy. Please go to Instagram website or mobile app to review these changes and accept them.");
            } catch (\InstagramAPI\Exception\SentryBlockException $e) {
                throw new Exception("Access to Instagram API restricted for spam behavior or otherwise abusing. You can try to use Session Catcher script (available by https://kd1s.com/session-catcher) to get valid Instagram session from location, where your account created from.");
            } catch (\InstagramAPI\Exception\IncorrectPasswordException $e) {
                throw new Exception("The password you entered is incorrect. Please try again.");
            } catch (\InstagramAPI\Exception\InvalidUserException $e) {
                throw new Exception("The username you entered doesn't appear to belong to an account. Please check your username and try again.");
            } catch (\Exception $e) {
                throw $e;
            }

            $is_connected_count += 1;
        } while (!$is_connected);

        output("Logged as @" . $login . " successfully. Parser #" . $pn . " connected.");

    } catch (\Exception $e){
        output($e->getMessage());
        $igp = add_parser($pn);
    }

    return $igp;
}

/**
 * Define targets for masslooking
 */
function define_targets($ig) {

    do {
        output("Please define the targets.");
        output("Write all Instagram profile usernames via comma without '@' symbol.");
        output("Example: zns, kd1sweb, elhamalfadalah, ali.saber.official, dalisalih, joellemardinian, shaima4sabt, elissazkh, hind_albloushii, saifamer_official, houdasalah, hebaaldurri, actress_alaa_hussein, roaa_alsabban, mohd_alramadan, nohastyleicon, star_casablanca, balqeesfathi, me_alsafi, mayal3eidankwt, eman_gsa, hussain_almahdi, lojain_omran, noorstars, hayaalshuaibi_79, maiooon_albloushi, farah_alhady, fakhriya_khamis, msh3ibiii, baderalsh3ibiii, somoud_alkandari, shamsofficial_, myriamfares, rafatalbadr, esra_alaseil, yaserabdalwahab, ali_athab9, amerra_mohammed, tareq_al_ali, raedabufatean, alialkalede, lailaabdallah, shahab_vid, nou, ahlamalshamsi, amen_90, ");
        $targets_input = getVarFromUser("Usernames");

        if (empty($targets_input)) {
            do { 
                $targets_input = getVarFromUser("Usernames");
            } while (empty($targets_input));
        }

        $targets_input = str_replace(' ','',$targets_input);
        $targets = [];
        $targets = explode(',',trim($targets_input));
        $targets = array_unique($targets);

        $pks = [];
        $filtered_targets = [];
        foreach ($targets as $target) {

            $is_connected = false;
            $is_connected_count = 0;
            do {
                if ($is_connected_count == 10) {
                    if ($e->getResponse()) {
                        output($e->getMessage());
                    }
                    $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";
                    output($fail_message);
                    run($ig);
                }

                try {
                    $user_resp = $ig->people->getUserIdForName($target);
                    output("@" . $target . " - [OK]");
                    $filtered_targets[] = $target;
                    $pks[] = $user_resp;
                    $is_connected = true;
                    if (($target != $targets[count($targets) - 1]) && (count($targets) > 0)) {
                        sleep(1);
                    }
                } catch (\InstagramAPI\Exception\NetworkException $e) { 
                    sleep(7);
                } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                    sleep(7);
                } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
                    output("Please login again and pass verification challenge. Instagram will send you a security code to verify your identity.");
                    run($ig);
                } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                    output("Please go to Instagram website or mobile app and pass checkpoint!");
                    run($ig);
                } catch (\InstagramAPI\Exception\AccountDisabledException $e) {
                    output("Your account has been disabled for violating Instagram terms. Go Instagram website or mobile app to learn how you may be able to restore your account.");
                    output("Use this form for recovery your account: https://help.instagram.com/contact/1652567838289083");
                    run($ig);
                } catch (\InstagramAPI\Exception\ConsentRequiredException $e) {
                    output("Instagram updated Terms and Data Policy. Please go to Instagram website or mobile app to review these changes and accept them.");
                    run($ig);
                } catch (\InstagramAPI\Exception\SentryBlockException $e) {
                    output("Access to Instagram API restricted for spam behavior or otherwise abusing. You can try to use Session Catcher script (available by https://kd1s.com/session-catcher) to get valid Instagram session from location, where your account created from.");
                    run($ig);
                } catch (\InstagramAPI\Exception\ThrottledException $e) {
                    output("Throttled by Instagram because of too many API requests.");
                    output("Please login again after 1 hour. You reached Instagram limits.");
                    run($ig);
                } catch (InstagramAPI\Exception\NotFoundException $e) {
                    $is_connected = true;
                    $is_username_correct = false;
                    do {
                        output("Instagram profile username @" . $target . " is incorrect or maybe user just blocked you (Login to Instagram website or mobile app and check that).");
                        output("Type 3 for skip this target.");
                        $target_new = getVarFromUser("Please provide valid username");
    
                        if (empty($target_new)) {
                            do { 
                                $target_new = getVarFromUser("Please provide valid username");
                            } while (empty($target_new));
                        }
    
                        if ($target_new == "3") {
                            break;
                        } else {
                            $is_connected = false;
                            $is_connected_count = 0;
                            do { 
                                if ($is_connected_count == 10) {
                                    if ($e->getResponse()) {
                                        output($e->getMessage());
                                    }
                                    $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";
                                    output($fail_message);
                                    run($ig);
                                }

                                try {
                                    $user_resp = $ig->people->getUserIdForName($target_new);
                                    output("@" . $target_new . " - [OK]");
                                    $filtered_targets[] = $target_new;
                                    $pks[] = $user_resp;
                                    $is_username_correct = true;
                                    $is_connected = true;
                                } catch (\InstagramAPI\Exception\NetworkException $e) { 
                                    sleep(7);
                                } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                                    sleep(7);
                                } catch (InstagramAPI\Exception\NotFoundException $e) {
                                    $is_username_correct = false;
                                    $is_connected = true;
                                } catch (\Exception $e) {
                                    output($e->getMessage());
                                    run($ig);
                                }
                                $is_connected_count += 1;
                            } while (!$is_connected);
                        }
                    } while (!$is_username_correct);
                } catch (Exception $e){
                    output($e->getMessage());
                    run($ig);
                }
                
                $is_connected_count += 1;
            } while (!$is_connected);
        }
    } while (empty($filtered_targets));

    $targets = array_unique($filtered_targets);
    $pks = array_unique($pks);

    $data = [];
    for ($i = 0; $i < count($targets); $i++) {
        $data[$i] = [
            'username' => $targets[$i],
            'pk' => $pks[$i],
        ];
    }

    output("Selected " . count($targets) . " targets: @" . implode(", @", $targets) . ".");
    output("Please confirm that the selected targets are correct.");
    output("1 - [Yes]");
    output("2 - [No]");
    output("3 - [Exit]");

    $confirm = getVarFromUser("Choice");

    if (empty($confirm)) {
        do { 
            $confirm = getVarFromUser("Choice");
        } while (empty($confirm));
    }

    if ($confirm == '1' || $confirm == '2' || $confirm == '3') {
        // All fine
    } else {
        $is_choice_ok = false;
        do {
            output("Choice is incorrect. Type 1, 2 or 3.");
            $confirm = getVarFromUser("Choice");

            if (empty($confirm)) {
                do { 
                    $confirm = getVarFromUser("Choice");
                } while (empty($confirm));
            }

            if ($confirm == '1' || $confirm == '2' || $confirm == '3') { 
                $is_choice_ok = true;
            }
        } while (!$is_choice_ok);
    }

    if ($confirm == '3') {
        run($ig);
    } elseif ($confirm == '2') {
        $data = define_targets($ig);
    } else {
        // All fine. Going to masslooking.
    }

    return $data;
}

/**
 * Get varable from user
 */
function getVarFromUser($text) {
    echo $text . ": ";
    $var = trim(fgets(STDIN));
    return $var;
}

/**
 * Output message with data to console
 */
function output($message) {
    echo "[", date("H:i:s"), "] ", $message, PHP_EOL;
}

/**
 * Output clean message to console
 */
function output_clean($message) {
    echo $message, PHP_EOL;
}

/**
 * Validates proxy address
 */
function isValidProxy($proxy) {
    output("Connecting to Instagram...");

    try {
        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', 'http://www.instagram.com', 
                                [
                                    "timeout" => 60,
                                    "proxy" => $proxy
                                ]);
        $code = $res->getStatusCode();
        $is_connected = true;
    } catch (\Exception $e) {
        output($e->getMessage());
        return false;
    }

    return $code == 200;
}

/**
 * Validates proxy address
 */
function finishLogin($ig, $login, $password, $proxy) {
    $is_connected = false;
    $is_connected_count = 0;

    try {
        do {
            if ($is_connected_count == 10) {
                if ($e->getResponse()) {
                    output($e->getMessage());
                }
                $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";
                output($fail_message);
                run($ig);
            }

            if ($proxy == "3") {
                // Skip proxy setup
            } else {
                $ig->setProxy($proxy);
            }

            try {
                $login_resp = $ig->login($login, $password);
        
                if ($login_resp !== null && $login_resp->isTwoFactorRequired()) {
                    // Default verification method is phone
                    $twofa_method = '1';

                    // Detect is Authentification app verification is available 
                    $is_totp = json_decode(json_encode($login_resp), true);
                    if ($is_totp['two_factor_info']['totp_two_factor_on'] == '1'){
                        output("Two-factor authentication required, please enter the code from you Authentication app");
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                        $twofa_method = '3';
                    } else {
                        output("Two-factor authentication required, please enter the code sent to your number ending in %s", 
                            $login_resp->getTwoFactorInfo()->getObfuscatedPhoneNumber());
                        $twofa_id = $login_resp->getTwoFactorInfo()->getTwoFactorIdentifier();
                    }

                    $twofa_code = getVarFromUser("Two-factor code");

                    if (empty($twofa_code)) {
                        do { 
                            $twofa_code = getVarFromUser("Two-factor code");
                        } while (empty($twofa_code));
                    }

                    $is_connected = false;
                    $is_connected_count = 0;
                    do {
                        if ($is_connected_count == 10) {
                            if ($e->getResponse()) {
                                output($e->getMessage());
                            }
                            output($fail_message);
                            run($ig);
                        }

                        if ($is_connected_count == 0) {
                            output("Two-factor authentication in progress...");
                        }

                        try {
                            $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                            $is_connected = true;
                        } catch (\InstagramAPI\Exception\NetworkException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                            sleep(7);
                        } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                            $is_code_correct = false;
                            $is_connected= true;
                            do {
                                output("Code is incorrect. Please check the syntax and try again.");
                                $twofa_code = getVarFromUser("Two-factor code");
            
                                if (empty($twofa_code)) {
                                    do { 
                                        $twofa_code = getVarFromUser("Security code");
                                    } while (empty($twofa_code));
                                }
            
                                $is_connected = false;
                                $is_connected_count = 0;
                                do {
                                    try {
                                        if ($is_connected_count == 10) {
                                            if ($e->getResponse()) {
                                                output($e->getMessage());
                                            }
                                            output($fail_message);
                                            run($ig);
                                        }

                                        if ($is_connected_count == 0) {
                                            output("Verification in progress...");
                                        }
                                        $twofa_resp = $ig->finishTwoFactorLogin($login, $password, $twofa_id, $twofa_code, $twofa_method);
                                        $is_code_correct = true;
                                        $is_connected = true;
                                    } catch (\InstagramAPI\Exception\NetworkException $e) { 
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                                        sleep(7);
                                    } catch (\InstagramAPI\Exception\InvalidSmsCodeException $e) {
                                        $is_code_correct = false;
                                        $is_connected = true;
                                    } catch (\Exception $e) {
                                        throw new $e;
                                    }
                                    $is_connected_count += 1;
                                } while (!$is_connected);
                            } while (!$is_code_correct);
                        } catch (\Exception $e) {
                            throw $e;
                        }

                        $is_connected_count += 1;
                    } while (!$is_connected);
                }

                $is_connected = true;
            } catch (\InstagramAPI\Exception\NetworkException $e) { 
                sleep(7);
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                throw new Exception("Please go to Instagram website or mobile app and pass checkpoint!");
            } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
                output("Instagram Response: " . json_encode($e->gerResponse()));
                output("Couldn't complete the verification challenge. Please try again later.");
                throw new Exception("Developer code: Challenge loop.");
            } catch (\Exception $e) {
                throw $e;
            }

            $is_connected_count += 1;
        } while (!$is_connected);
    } catch (\Exception $e){
        output($e->getMessage());
        run($ig);
    }

    return $ig;
}

/**
 * Verification challenge
 */
function challange($ig, $login, $password, $api_path, $security_code, $proxy) {
    $is_connected = false;
    $is_connected_count = 0;
    $fail_message = "There is a problem with your Ethernet connection or Instagram is down at the moment. We couldn't establish connection with Instagram 10 times. Please try again later.";

    do {
        if ($is_connected_count == 10) {
            if ($e->getResponse()) {
                output($e->getMessage());
            }
            throw new Exception($fail_message);
        }

        if ($is_connected_count == 0) {
            output("Verification in progress...");
        }

        try {
            $challenge_resp = $ig->finishChallengeLogin($login, $password, $api_path, $security_code);
            $is_connected = true;
        } catch (\InstagramAPI\Exception\NetworkException $e) {
            sleep(7);
        } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
            sleep(7);
        } catch (\InstagramAPI\Exception\InstagramException $e) {

            if ($e->hasResponse()) {
                $msg = $e->getResponse()->getMessage();
                output($msg);
            } else {
                $msg = explode(":", $e->getMessage(), 2);
                $msg = end($msg);
                output($msg);
            }

            output("Type 3 - to exit.");

            $security_code = getVarFromUser("Security code");

            if (empty($security_code)) {
                do { 
                    $security_code = getVarFromUser("Security code");
                } while (empty($security_code));
            }

            if ($security_code == "3") {
                throw new Exception("Reset in progress...");
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if ($msg == 'Invalid Login Response at finishChallengeLogin().') {
                sleep(7);
                $ig = finishLogin($ig, $login, $password, $proxy);
                $is_connected = true;
            } else {
                throw $e;
            }
        }

        $is_connected_count += 1;
    } while (!$is_connected);

    return $ig;
}

/**
 * Masslooking loop - Algorithm #2
 */
function masslooking_v2($data, $ig, $delay) {

    $view_count = 0;
    $st_count = 0;
    $st_count_seen = 0;
    $begin = strtotime(date("Y-m-d H:i:s"));
    $begin_ms = strtotime(date("Y-m-d H:i:s"));
    $begin_f = strtotime(date("Y-m-d H:i:s"));
    $speed = 0;
    $delitel = 0;
    $counter1 = 0;
    $counter2 = 0;
    $stories = [];

   // $story_per_request = getVarFromUser('Enter Story Count Per Request   mix 10 , max 100');
   $story_per_request = rand(10 , 40);
    output("Masslooking loop started.");

    $targets = [];
    $targets = $data;

    for ($i = 0; $i < count($data); $i++) {
        $data[$i] += [
            'rank_token' => \InstagramAPI\Signatures::generateUUID(), 
            'users_count' => 0, 
            'max_id' => null,
            'begin_gf' => null
        ];
    }

    do {
        foreach ($data as $key => $d) {
            try {
                if ($d['max_id'] == null) {
                    $is_gf_first = 1;
                }

                if (!empty($d['begin_gf'])) {
                    $current_time = strtotime(date("Y-m-d H:i:s"));
                    if (($current_time - $d['begin_gf']) < 7) {
                        $sleep_time = 7 - ($current_time - $d['begin_gf']);
                        sleep($sleep_time);
                    }
                }

                $followers = $ig->people->getFollowers($d['pk'], $d['rank_token'], null, $d['max_id']);
                $data[$key]['begin_gf'] = strtotime(date("Y-m-d H:i:s"));

                if (empty($followers->getUsers())) { 
                    output("@" . $d['username'] . " don't have any follower.");
                    unset($data[$key]);
                    continue;
                }

                // Get next $max_id value
                $data[$key]['max_id'] = $followers->getNextMaxId();

                $followers_ids = [];
                foreach ($followers->getUsers() as $follower) {
                    $followers_ids[] = $follower->getPk();
                }

                $data[$key]['users_count'] = $d['users_count'] + count($followers_ids);

                $number = count($followers_ids);

                if ($is_gf_first) {
                    output($number . " followers of @" . $d['username'] . " collected.");
                    $is_gf_first = 0;
                } else {
                    output("Next " . $number . " followers of @" . $d['username'] . " collected. Total: " . number_format($data[$key]['users_count'], 0, '.', ' ') . " followers of @" . $d['username'] . " parsed.");
                }

                $index_new = 0;
                $index_old = 0;
                $last = false;

                do { 
                    $index_new += 13;

                    if (!isset($followers_ids[$index_new])) {
                        do {
                            $index_new -= 1;
                        } while (!isset($followers_ids[$index_new]));
                        $last = true;
                    }

                    if ($index_new < $index_old) {
                        break;
                    }

                    $ids = [];
                    for ($i = $index_old; $i <= $index_new; $i++) {
                        $ids[] = $followers_ids[$i];
                    }

                    try {
                        $stories_reels = $ig->story->getReelsMediaFeed($ids);

                        $counter1 += 1;

                        if (isset($stories_reels) && $stories_reels->isOk() && count($stories_reels->getReels()->getData()) > 0) {
                            // Save user story reels's to array
                            $reels = [];
                            $reels = $stories_reels->getReels()->getData();
                            $users =[] ;
                            foreach ($reels as $r) {
                                $items = [];
                                $stories_loop = [];
                                $items = $r->getItems();
                                $json_data = json_decode($r);
                                output($json_data->user->username);
                                $users = $json_data->user->username;
                                foreach ($items as $item) {
                                    if (!$item->getId()) {
                                        // Item is not valid
                                        continue;
                                    }
                                    $stories_loop[] = $item;
                                }

                                if (empty($stories)) {
                                    $stories = $stories_loop;
                                } else {
                                    $stories = array_merge($stories, $stories_loop);
                                }

                                $st_count =  $st_count + count($stories_loop);
                                $view_count = $view_count + count($stories_loop);

                                $now = strtotime(date("Y-m-d H:i:s"));
                                if ($now - $begin > 299) {
                                    $begin = strtotime(date("Y-m-d H:i:s"));
                                    $delitel = $delitel + 1;
                                    $speed = (int)($view_count * 12 * 24 / $delitel);
                                    output_clean("");
                                    output_clean("Estimated speed is " . number_format($speed, 0, '.', ' ') . " stories/day.");
                                    output_clean("Fixed");
                                    output_clean("");
                                }

                                $now_f = strtotime(date("Y-m-d H:i:s"));
                                if ($now_f - $begin_f > 1) {
                                    $begin_f = strtotime(date("Y-m-d H:i:s"));
                                    // output($st_count . " stories found. / Debug: getReelsMediaFeed (" . $counter1 . "), markMediaSeen (" . $counter2 . ")");
                                    output($st_count . " stories found.");
                                }
                                
                                if ($st_count > $story_per_request) {
                                    // output($st_count . " stories found. / Debug: getReelsMediaFeed (" . $counter1 . "), markMediaSeen (" . $counter2 . ")");
                                    output($st_count . " stories found.");
                                     $now_ms = strtotime(date("Y-m-d H:i:s"));
                                     if ($now_ms - $begin_ms >= $delay) {
                                        // all fine
                                    } else {
                                        $counter3 = $delay - ($now_ms - $begin_ms);
                                        output("Starting " . $counter3 . " second(s) delay for bypassing Instagram limits.");
                                        do {
                                         output($counter3 . " second(s) left.");
                                             sleep(1);
                                             $counter3 -= 1;
                                         } while ($counter3 != 0);
                                     }

                                    // Mark collected stories as seen
                                    $mark_seen_resp = $ig->story->markMediaSeen($stories);
                                    $begin_ms = strtotime(date("Y-m-d H:i:s"));
                                    
                                    $st_count_seen += number_format($st_count, 0, '.', ' ');
                                    $counter2 += 1;
                                    // output($st_count_seen . " stories marked as seen. / Debug: getReelsMediaFeed (" . $counter1 . "), markMediaSeen (" . $counter2 . ")."); 
                                    output($st_count_seen . " stories marked as seen.");

                                    output_clean("");
                                    output_clean("Total: " . number_format($view_count, 0, '.', ' ') . " stories successfully seen.");
                                    output_clean("© Hyperloop Terminal. Developed by Nextpost Developers Team (https://kd1s.com) Fixed By Kasper @6zx");
                                    output_clean("");
                
                                    // Initialize arrays and parameters again
                                    $stories = [];
                                    $st_count = 0;
                                }
                            }
                        }
                        
                        if (($st_count > 0) && $last && $data[$key]['max_id'] == null) {
                            // Mark collected stories as seen
                            $mark_seen_resp = $ig->story->markMediaSeen($stories);  
                            
                            $st_count_seen = number_format($st_count, 0, '.', ' ');
                            $counter2 += 1;
                            // output($st_count_seen . " stories marked as seen. / Debug: getReelsMediaFeed (" . $counter1 . "), markMediaSeen (" . $counter2 . ")."); 
                            output($st_count_seen . " stories marked as seen.");
                            output_clean("");
                            output_clean("Total: " . number_format($view_count, 0, '.', ' ') . " stories successfully seen.");
                            output_clean("");

                            // Initialize arrays and parameters again
                            $stories = [];
                            $st_count = 0;
                        }
                    } catch (\InstagramAPI\Exception\NetworkException $e) {
                        output("We couldn't connect to Instagram at the moment. Trying again.");
                        sleep(7);
                        $index_new -= 13;
                        continue;
                    } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                        output("Instagram sent us empty response. Trying again.");
                        sleep(7);
                        $index_new -= 13;
                        continue;
                    } catch (\InstagramAPI\Exception\LoginRequiredException $e) {
                        output("Please login again to your Instagram account. Login required.");
                        run($ig);
                    } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
                        output("Please login again and pass verification challenge. Instagram will send you a security code to verify your identity.");
                        run($ig);
                    } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                        output("Please go to Instagram website or mobile app and pass checkpoint!");
                        run($ig);
                    } catch (\InstagramAPI\Exception\AccountDisabledException $e) {
                        output("Your account has been disabled for violating Instagram terms. Go Instagram website or mobile app to learn how you may be able to restore your account.");
                        output("Use this form for recovery your account: https://help.instagram.com/contact/1652567838289083");
                        run($ig);
                    } catch (\InstagramAPI\Exception\ConsentRequiredException $e) {
                        output("Instagram updated Terms and Data Policy. Please go to Instagram website or mobile app to review these changes and accept them.");
                        run($ig);
                    } catch (\InstagramAPI\Exception\SentryBlockException $e) {
                        output("Access to Instagram API restricted for spam behavior or otherwise abusing.");
                        run($ig);
                    } catch (\InstagramAPI\Exception\ThrottledException $e) {
                        output("Throttled by Instagram because of too many API requests.");
                        output("Please connect account again after 12 hours. You reached Instagram daily limit for masslooking. Just take a break.");
                        run($ig);
                    } catch (Exception $e){
                        output($e->getMessage());
                        sleep(7);
                    }
                    
                    $index_old = $index_new + 1;

                } while ($last == false);

                // Check is $max_id is null
                if ($data[$key]['max_id'] == null) {
                    output_clean("All stories of @" .  $d['username'] . "'s followers successfully seen.");
                    unset($data[$key]);
                    continue;
                }

            } catch (\InstagramAPI\Exception\NetworkException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\EmptyResponseException $e) {
                sleep(7);
            } catch (\InstagramAPI\Exception\LoginRequiredException $e) {
                output("Please login again to your Instagram account. Login required.");
                run($ig);
            } catch (\InstagramAPI\Exception\ChallengeRequiredException $e) {
                output("Please login again and pass verification challenge. Instagram will send you a security code to verify your identity.");
                run($ig);
            } catch (\InstagramAPI\Exception\CheckpointRequiredException $e) {
                output("Please go to Instagram website or mobile app and pass checkpoint!");
                run($ig);
            } catch (\InstagramAPI\Exception\AccountDisabledException $e) {
                output("Your account has been disabled for violating Instagram terms. Go Instagram website or mobile app to learn how you may be able to restore your account.");
                output("Use this form for recovery your account: https://help.instagram.com/contact/1652567838289083");
                run($ig);
            } catch (\InstagramAPI\Exception\ConsentRequiredException $e) {
                output("Instagram updated Terms and Data Policy. Please go to Instagram website or mobile app to review these changes and accept them.");
                run($ig);
            } catch (\InstagramAPI\Exception\SentryBlockException $e) {
                output("Access to Instagram API restricted for spam behavior or otherwise abusing. You can try to use Session Catcher script (available by https://kd1s.com/session-catcher) to get valid Instagram session from location, where your account created from.");
                run($ig);
            } catch (\InstagramAPI\Exception\ThrottledException $e) {
                output("Throttled by Instagram because of too many API requests.");
                output("Please connect account again after 12 hours. You reached Instagram daily limit for masslooking. Just take a break.");
                run($ig);
            } catch (Exception $e){
                output($e->getMessage());
                sleep(7);
            }
        }
    } while (!empty($data));

    output_clean("All stories related to the targets seen. Starting the new loop.");
    output_clean("");

    masslooking_v2($targets, $ig, $delay);
}

/**
 * Send request
 * @param $url
 * @return mixed
 */
function request($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $return = curl_exec($ch);

    curl_close($ch);

    return $return;
}
  
/**
 * Get IP details
 */
function ip_details() {
    try {
        $json = request("http://www.geoplugin.net/json.gp");
    } catch (Exception $e){
        $msg = $e->getMessage();
        output($msg);
        run($ig);
    }
    $details = json_decode($json);
    return $details;
}
/**
 * Validate license
 * @param $license_key
 * @return string
 */
function activate_license($license_key, $ig) {
    return 'valid';
}

?>