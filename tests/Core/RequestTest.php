<?php

require __DIR__ . '/../../vendor/autoload.php';


// Simulate a raw HTTP request
$rawRequest = "POST /path/to/resource?param1=value1&param2=value2 HTTP/1.1\r\n"
    . "Host: example.com\r\n"
    . "Content-Type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW\r\n"
    . "Cookie: sessionId=abc123; userId=42\r\n"
    . "Accept: application/json\r\n"
    . "\r\n"
    . "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\n"
    . "Content-Disposition: form-data; name=\"file\"; filename=\"example.txt\"\r\n"
    . "Content-Type: text/plain\r\n"
    . "\r\n"
    . "This is a test file.\r\n"
    . "------WebKitFormBoundary7MA4YWxkTrZu0gW--\r\n";

// Create a Request object
$request = new \PhpHttpServer\Core\Request($rawRequest);

// Test getters
echo "Method: " . $request->getMethod() . "\n"; // Expected: POST
echo "URI: " . $request->getUri() . "\n"; // Expected: /path/to/resource?param1=value1&param2=value2
echo "Protocol: " . $request->getProtocol() . "\n"; // Expected: HTTP/1.1
echo "Hostname: " . $request->getHostname() . "\n"; // Expected: example.com
echo "IP: " . $request->getIp() . "\n"; // Expected: 0.0.0.0 (or the actual IP if running in a server context)

// Test headers
echo "Headers:\n";
print_r($request->getHeaders()); // Expected: Array of headers
echo "Content-Type: " . $request->getHeader('Content-Type') . "\n"; // Expected: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW

// Test query parameters
echo "Query Parameters:\n";
print_r($request->getQueryParams()); // Expected: Array with param1 and param2
echo "Query Param 'param1': " . $request->getQueryParam('param1') . "\n"; // Expected: value1

// Test cookies
echo "Cookies:\n";
print_r($request->getCookies()); // Expected: Array with sessionId and userId
echo "Cookie 'sessionId': " . $request->getCookie('sessionId') . "\n"; // Expected: abc123

// Test files
echo "Files:\n";
print_r($request->getFiles()); // Expected: Array with file details
echo "File 'file' content: " . $request->getFile('file')['content'] . "\n"; // Expected: This is a test file.

// Test body
echo "Body: " . $request->getBody() . "\n"; // Expected: Empty (since body is part of multipart data)

// Test path parameters
$request->extractPathParams('|/path/to/resource|');
echo "Path Parameters:\n";
print_r($request->getPathParams()); // Expected: Array with matched path segments

// Test request method checks
echo "Is GET: " . ($request->isGet() ? 'Yes' : 'No') . "\n"; // Expected: No
echo "Is POST: " . ($request->isPost() ? 'Yes' : 'No') . "\n"; // Expected: Yes
echo "Is PUT: " . ($request->isPut() ? 'Yes' : 'No') . "\n"; // Expected: No
echo "Is DELETE: " . ($request->isDelete() ? 'Yes' : 'No') . "\n"; // Expected: No

// Test content type checks
echo "Is multipart/form-data: " . ($request->is('multipart/form-data') ? 'Yes' : 'No') . "\n"; // Expected: Yes
echo "Accepts application/json: " . ($request->accepts('application/json') ? 'Yes' : 'No') . "\n"; // Expected: Yes

// Test HTTPS check
echo "Is HTTPS: " . ($request->isHttps() ? 'Yes' : 'No') . "\n"; // Expected: No