<?php
// *** Wotan3 — Login pages (forgot password + reset password)

// *** Forgot password form
if (pwcd('/login/forgotpassword/') && !empty($_POST['email'])) {
    preg_match('/[a-zA-Z0-9_%\-\.]+@[a-zA-Z0-9_%\-\.]+\.[a-zA-Z_%\-]{2,4}/', $_POST['email'], $emlres);
    $safe_email = !empty($emlres[0]) ? mysqli_real_escape_string($GLOBALS['_mysqli'], $emlres[0]) : '';

    $result = [];
    if ($safe_email)
        $result = pw_dbtoarray("SELECT * FROM users WHERE email='$safe_email' AND online='1' ORDER BY timestamp DESC LIMIT 1");

    if (!empty($result[1]) && $safe_email) {
        $token      = bin2hex(random_bytes(32));
        $expires    = time() + 3600;
        $safe_token = mysqli_real_escape_string($GLOBALS['_mysqli'], $token);
        $uid        = (int)$result[1]['id'];

        mysqli_query($GLOBALS['_mysqli'],
            "UPDATE users SET reset_token='$safe_token', reset_expires='$expires' WHERE id='$uid'"
        );

        $reset_url             = 'https://' . $_SERVER['HTTP_HOST'] . '/login/resetpassword/?token=' . $token;
        $result['reset_url']   = $reset_url;
        $result['reset_email'] = $result[1]['email'];

        $sendmail['toemail'][1]   = $result[1]['email'];
        $sendmail['fromemail'][1] = 'noreply@' . $domainname;
        $sendmail['subject'][1]   = 'Reset your ' . $domainname . ' password';
        $sendmail['header']       = "Content-Type: text/html; charset=\"utf-8\"\nMIME-Version: 1.0\n";
        $sendmail['message']      = wotan('pw_htmlfile4/pw_htmlfile', '$obj[htmlemail]',
            ['htmlfile="/share/emails/forgotpassword/english.xhtml"', 'incbody=1', 'runall()']);
        wotan('email/email', '', 'sendemails($sendmail)');

        $echo['msg'] = 'A password reset link has been sent to <b>' . htmlspecialchars($result[1]['email']) . '</b>.<br>'
            . 'The link is valid for 1 hour. Check your spam folder if you don\'t receive it.';
    } else {
        $echo['msg'] = 'Sorry, no account found for: <b>' . htmlspecialchars($_POST['email'] ?? '') . '</b>';
    }
}

// *** Reset password page
if (pwcd('/login/resetpassword/')) {
    $raw_token = $_GET['token'] ?? $_POST['token'] ?? '';
    $token     = preg_replace('/[^a-f0-9]/', '', $raw_token);

    $tokenres = [];
    if ($token) {
        $safe_token = mysqli_real_escape_string($GLOBALS['_mysqli'], $token);
        $now        = time();
        $tokenres   = pw_dbtoarray("SELECT * FROM users WHERE reset_token='$safe_token' AND reset_expires>'$now' AND online='1' LIMIT 1");
    }

    if (!empty($tokenres[1])) {
        if (isset($_POST['newpassword'], $_POST['newpassword2'])) {
            if ($_POST['newpassword'] !== $_POST['newpassword2']) {
                $echo['msg']         = 'Passwords do not match.';
                $echo['reset_form']  = 1;
                $echo['reset_token'] = $token;
            } elseif (strlen($_POST['newpassword']) < 8) {
                $echo['msg']         = 'Password must be at least 8 characters.';
                $echo['reset_form']  = 1;
                $echo['reset_token'] = $token;
            } else {
                $new_hash  = password_hash($_POST['newpassword'], PASSWORD_BCRYPT);
                $safe_hash = mysqli_real_escape_string($GLOBALS['_mysqli'], $new_hash);
                $uid       = (int)$tokenres[1]['id'];
                mysqli_query($GLOBALS['_mysqli'],
                    "UPDATE users SET hpassword='$safe_hash', password='*', reset_token=NULL, reset_expires=0 WHERE id='$uid'"
                );
                pw_systemlog('login.php', 'PasswordReset', '', ['uid' => $uid]);
                $echo['msg']        = 'Your password has been updated. <a href="/login/">Click here to log in.</a>';
                $echo['reset_done'] = 1;
            }
        } else {
            $echo['reset_form']  = 1;
            $echo['reset_token'] = $token;
        }
    } elseif ($token) {
        $echo['msg'] = 'This reset link is invalid or has expired. <a href="/login/forgotpassword/">Request a new one</a>.';
    } else {
        $echo['msg'] = 'No reset token provided. <a href="/login/forgotpassword/">Request a reset link</a>.';
    }
}

$echo['show_reset_msg']     = !empty($echo['msg'])         ? 'block' : 'none';
$echo['show_reset_form']    = (!empty($echo['reset_form']) && empty($echo['reset_done'])) ? 'block' : 'none';
$echo['show_invalid_token'] = (empty($echo['reset_form']) && empty($echo['reset_done']) && empty($echo['msg'])) ? 'block' : 'none';
$echo['safe_reset_token']   = htmlspecialchars($echo['reset_token'] ?? '');
$echo['show_msg']           = !empty($echo['msg']) ? 'block' : 'none';
$echo['show_form']          = !empty($echo['msg']) ? 'none'  : 'block';
?>
