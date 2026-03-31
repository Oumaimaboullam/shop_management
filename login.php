<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Shop Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            color: #2E86AB;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't affect width */
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #2E86AB;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #246b8a;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Shop Management</h2>
            <p>Please login to continue</p>
        </div>
        <div id="error-message" class="error-message"></div>
        <form id="login-form">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit">Login</button>
        </form>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('error-message');
            const button = this.querySelector('button');
            
            // Reset error
            errorMessage.style.display = 'none';
            errorMessage.textContent = '';
            
            // Disable button
            button.disabled = true;
            button.textContent = 'Logging in...';
            
            try {
                const response = await fetch('api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.role === 'cashier') {
                        window.location.href = 'sales/pos.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                } else {
                    errorMessage.textContent = data.message || 'Login failed';
                    errorMessage.style.display = 'block';
                    button.disabled = false;
                    button.textContent = 'Login';
                }
            } catch (error) {
                console.error('Error:', error);
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
                button.disabled = false;
                button.textContent = 'Login';
            }
        });
    </script>
</body>
</html>