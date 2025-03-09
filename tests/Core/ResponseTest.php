<?php

require __DIR__ . '/../../vendor/autoload.php';

// Create a Response object
$response = new \PhpHttpServer\Core\Response();

// Test plain text response
$response->sendText('Hello, World!');
echo "Plain Text Response:\n";
print_r($response->getHeaders());
echo $response->getBody() . "\n\n";

// Test JSON response
$response->sendJson(['message' => 'Hello, JSON!']);
echo "JSON Response:\n";
print_r($response->getHeaders());
echo $response->getBody() . "\n\n";

// Test HTML response
$response->sendHtml('<h1>Hello, HTML!</h1>');
echo "HTML Response:\n";
print_r($response->getHeaders());
echo $response->getBody() . "\n\n";

// Test file download
$response->download(__FILE__, 'test.php');
echo "File Download Response:\n";
print_r($response->getHeaders());
echo $response->getBody() . "\n\n";

// Test redirect
$response->redirect('https://example.com', 301);
echo "Redirect Response:\n";
print_r($response->getHeaders());
echo $response->getBody() . "\n\n";