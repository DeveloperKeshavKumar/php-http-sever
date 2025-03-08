
# PHP HTTP Server

A lightweight, dependency-free HTTP server written in PHP. This server supports basic routing, middleware, and WebSocket integration, and is designed to be modular and easy to extend.

---

## Features

- **HTTP/1.1 Support**: Handles basic HTTP requests and responses.
- **Request Parsing**: Parses incoming HTTP requests to extract method, URI, headers, and body.
- **Response Handling**: Sends structured HTTP responses with customizable status codes, headers, and body.
- **Modular Design**: Built with modularity in mind, making it easy to extend and customize.

---

## Getting Started

### Prerequisites

- PHP 7.4 or higher.
- Basic knowledge of PHP and HTTP.

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/developerkeshavkumar/php-http-server.git
   cd php-http-server
   ```

2. (Optional) Install Composer for autoloading:

   ```bash
   composer install
   ```

3. Start the server:

   ```bash
   php public/index.php (or) composer dev
   ```

   The server will start listening on `http://localhost:8080`.

---

## Usage

### Starting the Server

To start the server, run:

```bash
 php public/index.php (or) composer dev
```

The server will listen on `http://localhost:8080` by default. You can customize the host and port by modifying the `Server` constructor in `public/index.php`.

---

### Handling Requests

The server parses incoming HTTP requests into a `Request` object, which provides the following methods:

- `getMethod()`: Returns the HTTP method (e.g., `GET`, `POST`).
- `getUri()`: Returns the request URI (e.g., `/`, `/users/123`).
- `getHeaders()`: Returns an associative array of request headers.
- `getBody()`: Returns the request body (if any).

Example:

```php
$request = new Request($rawRequest);
echo "Method: " . $request->getMethod() . "\n";
echo "URI: " . $request->getUri() . "\n";
echo "Headers: " . print_r($request->getHeaders(), true) . "\n";
echo "Body: " . $request->getBody() . "\n";
```

---

### Sending Responses

The server uses a `Response` object to send HTTP responses. The `Response` class provides the following methods:

- `setStatusCode(int $statusCode)`: Sets the HTTP status code (e.g., `200`, `404`).
- `setHeader(string $name, string $value)`: Adds a header to the response.
- `setBody(string $body)`: Sets the response body.
- `send(resource $conn)`: Sends the response to the client.

Example:

```php
$response = new Response();
$response->setStatusCode(200)
         ->setHeader('Content-Type', 'text/plain')
         ->setBody('Hello, World!')
         ->send($conn);
```

---

### Example Workflow

1. The server accepts a connection and reads the raw HTTP request.
2. The raw request is parsed into a `Request` object.
3. The server processes the request and creates a `Response` object.
4. The response is sent back to the client, and the connection is closed.

---

## Directory Structure

```
php-http-server/
├── src/
│   ├── Core/
│   │   ├── Server.php          # Main server logic
│   │   ├── Request.php         # Request class to handle HTTP requests
│   │   └── Response.php        # Response class to handle HTTP responses
├── public/
│   └── index.php               # Entry point for the server
├── composer.json               # Composer configuration (optional, for autoloading)
└── README.md                   # Project documentation
```

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository.
2. Create a new branch for your feature or bugfix.
3. Commit your changes and push to the branch.
4. Submit a pull request.

---

## Acknowledgments

- Inspired by Express.js for Node.js.
- Built with ❤️ and PHP.