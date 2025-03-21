## Breakdown into Steps and Substeps

#### Step 1: Setup the Basic HTTP Server
- **Substep 1.1**: Create a basic PHP script that listens for incoming HTTP requests.
- **Substep 1.2**: Parse the incoming HTTP request to extract method, URI, headers, and body.
- **Substep 1.3**: Send a basic HTTP response back to the client.

#### Step 2: Implement Routing
- **Substep 2.1**: Define a routing mechanism that can map HTTP methods and URIs to specific handlers.
- **Substep 2.2**: Implement support for route parameters (e.g., `/user/:id`).
- **Substep 2.3**: Add support for different HTTP methods (GET, POST, PUT, DELETE, etc.).

#### Step 3: Implement Middleware Support
- **Substep 3.1**: Define a middleware interface that can be used to create both global and route-specific middlewares.
- **Substep 3.2**: Implement a middleware stack that processes requests in sequence.
- **Substep 3.3**: Allow middlewares to modify the request and response objects.

#### Step 4: Integrate WebSocket Support
- **Substep 4.1**: Implement a WebSocket handshake mechanism.
- **Substep 4.2**: Create a WebSocket server that can handle WebSocket frames.
- **Substep 4.3**: Integrate WebSocket support with the existing HTTP server.

#### Step 5: Make the Server Modular
- **Substep 5.1**: Refactor the code into separate modules (e.g., `Router`, `Middleware`, `WebSocket`).
- **Substep 5.2**: Define clear interfaces and contracts for each module.
- **Substep 5.3**: Ensure that each module can be used independently or together.

#### Step 6: Documentation and Testing
- **Substep 6.1**: Document each module, including its purpose, API, and usage examples.
- **Substep 6.2**: Write unit tests for each module to ensure reliability.
- **Substep 6.3**: Create a comprehensive README file that explains how to set up and use the server.

#### Step 7: Create a Sample Project
- **Substep 7.1**: Use the custom server to create a sample project (e.g., a simple blog or chat application).
- **Substep 7.2**: Demonstrate the use of global and route-specific middlewares.
- **Substep 7.3**: Showcase WebSocket integration in the sample project.

---

```
php-http-server/
├── src/
│   ├── Core/
│   │   ├── Server.php                    # Main HTTP server logic
│   │   ├── Request.php                   # HTTP request class
│   │   ├── Response.php                  # HTTP response class
│   │   ├── Router.php                    # Router class
│   │   └── RouterInterface.php           # Router interface
│   ├── WebSocket/
│   │   ├── WebSocketServer.php           # WebSocket server logic
│   │   └── WebSocketHandlerInterface.php # WebSocket handler interface
│   ├── Middleware/
│   │   ├── MiddlewareInterface.php       # Middleware contract
│   │   ├── AuthMiddleware.php            # Authentication middleware
│   │   ├── MiddlewareStack.php           # Middleware stack handler
│   │   └── ExampleMiddleware.php         # Example middleware
│   ├── Cache/
│   │   └── Cache.php                     # In-memory caching system
│   ├── Exceptions/
│   │   ├── HttpException.php             # Base HTTP exception
│   │   ├── NotFoundException.php         # 404 Not Found exception
│   │   └── UnauthorizedException.php     # 401 Unauthorized exception
│   └── Utils/
│       ├── FileUploader.php              # File upload handler
│       └── Logger.php                    # Logging utility
├── public/
│   ├── index.php                         # Entry point for the server
│   ├── assets/                           # Static assets (CSS, JS, images)
│   │   ├── css/
│   │   ├── js/
│   │   └── images/
│   └── uploads/                          # Directory for uploaded files
├── views/                                # Template files for rendering HTML
│   ├── home.php                          # Home page template
│   ├── about.php                         # About page template
│   └── error.php                         # Error page template
├── config/                               # Configuration files
│   ├── server.php                        # Server configuration (host, port, etc.)
│   └── routes.php                        # Route definitions
├── tests/                                # Unit tests
│   ├── Core/
│   │   ├── RequestTest.php
│   │   ├── ResponseTest.php
│   │   ├── RouterTest.php
│   │   └── ServerTest.php
│   ├── Middleware/
│   │   ├── AuthMiddlewareTest.php
│   │   └── LoggingMiddlewareTest.php
│   └── WebSocket/
│       └── WebSocketServerTest.php
├── logs/                                 # Log files
│   └── server.log                        # Server activity log
├── vendor/                               # Composer dependencies (if any)
├── composer.json                         # Composer configuration (optional)
├── README.md                             # Project documentation
└── .htaccess                             # Apache configuration (if needed)
```