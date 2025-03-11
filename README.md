# PHP HTTP Server [![Latest Version on Packagist](https://img.shields.io/packagist/v/arishem/php-http-server.svg?style=flat-square)](https://packagist.org/packages/arishem/php-http-server)

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

- PHP 8.3 or higher.
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

The WebSocket server, if configured, will listen on `ws://localhost:8081` by default.

---

## Directory Structure

```
php-http-server/
├── src/                             # Source code directory
│   ├── Core/                        # Core server logic
│   │   ├── Server.php               # Main HTTP server logic
│   │   ├── Request.php              # HTTP request class
│   │   ├── Response.php             # HTTP response class
│   │   ├── Router.php               # HTTP routing logic
│   │   └── RouterInterface.php      # Router interface
│   ├── WebSocket/                   # WebSocket server implementation
│   │   ├── WebSocketServer.php      # WebSocket server logic
│   │   └── WebSocketInterface.php   # WebSocket frame handling logic
│   ├── Middleware/                   # Middleware components
│   │   ├── MiddlewareInterface.php  # Middleware contract
│   │   ├── ModifyRequestResponseMiddleware.php  # Middleware to modify Request and Response
│   │   └── ExampleMiddleware.php    # Example middleware
├── public/                          # Public directory (entry point for the server)
│   └── index.php                    # Entry point for the server
├── tests/                           # Test cases directory
│   ├── Core/                        # Tests for core components
│   │   ├── RequestTest.php          # Unit test for Request
│   │   ├── ResponseTest.php         # Unit test for Response
│   │   ├── RouterTest.php           # Unit test for Router
│   │   └── test_template.php        # Test template
│   ├── Cache/                       # Cache-related tests
│   │   ├── test_cache               # Cache test cases
│   │   └── CacheTest.php            # Unit test for cache functionality
│   └── Middleware/                   # Middleware-related tests
│       └── TestMiddleware.php       # Example middleware test
├── composer.json                    # Composer configuration (optional, for autoloading)
└── README.md                        # Project documentation
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