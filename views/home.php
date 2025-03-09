<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>

<body>
    <h1>Welcome, <?= htmlspecialchars($name) ?>!</h1>
    <p>This is the home page.</p>
    <a href="/users/192">Users</a>

    <div id="messages"></div>

    <script>
        const ws = new WebSocket('ws://localhost:8080');

        let isWebSocketOpen = false;

        ws.onopen = () => {
            isWebSocketOpen = true;
            ws.send('Hello, Server!');
        };

        ws.onmessage = (event) => {
            const messageDiv = document.createElement('div');
            messageDiv.textContent = event.data;
            document.getElementById('messages').appendChild(messageDiv);
        };

        function sendMessage(message) {
            if (isWebSocketOpen) {
                ws.send(message);
            } else {
                console.log('WebSocket is still connecting, message not sent');
            }
        }

        sendMessage('Hello, Server!');
    </script>

</body>

</html>