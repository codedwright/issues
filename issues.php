<?php
use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
// Using https://www.cloudmailin.com/addresses/e2f15d58132ba200014c
// for email to POST JSON
$post = trim(file_get_contents('php://input'));
$email = json_decode($post);
if(is_null($email)) {
    exit;
}

// If you're dealing with multiple repos (apps), you can setup any number of
// forwarding email addresses and then post to the appropriate repo based on
// which address you forwarded the user's email to.
$repos = ['ieaf'];
$match = false;
foreach($repos as $repo) {
    if(stripos($email->headers->subject, $repo) !== false) {
        create_github_issue('ieaf', $email);
        $match = true;
    }
}
if(!$match) {
    var_dump("bad");
    return;
}

function create_github_issue($repo, $email) {
    $headers = ["Authorization: token " . $_ENV['GITHUB_PERSONAL_ACCESS_TOKEN'], 'User-Agent: Email-To-Issue-Bot'];
    $json = [];
    $json['title'] = $email->headers->subject;
    $json['body'] = $email->plain;
    // List any tags you want applied to the new issue.
    // You must create these tags in GitHub first.
    $json['labels'] = ['bug'];

    //foreach ($email->attachments as $a) {
    //    if (strpos($a->ContentType, 'image') !== false) {
    //        // Get the attachment's file extension
    //        $parts = explode('.', $a->Name);
    //        $ext = array_pop($parts);
    //
    //        // Create a unique filename
    //        $fn = md5(microtime() . $a->Name) . ".$ext";
    //
    //        // Open a writable file handle and save the attachment data
    //        $fp = fopen(rtrim($attachment_dir, '/') . '/' . $fn, 'w');
    //        $data = base64_decode($a->Content);
    //        fwrite($fp, $data);
    //        fclose($fp);
    //
    //        // Embed the attachment's public URL as a Markdown image in the issue's body content so we can see it on GitHub
    //        $url = rtrim($attachment_url, '/') . '/' . $fn;
    //        $json['body'] .= "\n\n![{$a->Name}]($url)";
    //    }
    //}
    // var_dump(json_encode($json));
    // Create the new GitHub issue
    $ch = curl_init("https://api.github.com/repos/codedwright/$repo/issues");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    curl_exec($ch);
    curl_close($ch);
}