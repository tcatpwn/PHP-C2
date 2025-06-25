<?php   
// Created by tcatpwn
// Version 1: 6/25/2025
                                                                      
// Get raw POST body                   
$input = file_get_contents('php://input');                                                               
                                                                                              
// Try to decode           
$decoded = base64_decode($input, true);                                       
if ($decoded === false) {                                                                     
    http_response_code(400);
    file_put_contents('error.log', "Base64 decode failed\n", FILE_APPEND);    
    exit("Invalid base64");            
}                           
                                                                                              
// Try to decompress          
$decompressed = @gzuncompress($decoded);                                      
if ($decompressed === false) {                                                                
    http_response_code(400);
    file_put_contents('error.log', "Gzip decompress failed\n", FILE_APPEND);  
    exit("Invalid gzip");              
}                           
                                                                                              
// Try to parse JSON     
$data = json_decode($decompressed, true);                                     
if (!is_array($data)) {                                                                       
    http_response_code(400);
    file_put_contents('error.log', "JSON decode failed\n", FILE_APPEND);                      
    exit("Invalid JSON");              
}                  
                                                                                              
// Save to log file                    
file_put_contents('log.txt', print_r($data, true), FILE_APPEND);                                         
                                               
// Respond quietly                             
http_response_code(200);                       
echo "OK";                                          
?>        
