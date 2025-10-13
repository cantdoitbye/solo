{{-- resources/views/plans/success.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease;
        }
        .success-icon::after {
            content: "✓";
            color: white;
            font-size: 50px;
            font-weight: bold;
        }
        h1 { color: #333; font-size: 2rem; margin-bottom: 15px; }
        p { color: #666; font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px; }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn:hover { transform: scale(1.05); }
        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon"></div>
        <h1>Payment Successful!</h1>
        <p>{{ $message ?? 'Thank you for your purchase. Your plan will be activated shortly!' }}</p>
        <button class="btn" onclick="closeWebView()">Continue</button>
    </div>

    <script>
        function closeWebView() {
            // For React Native
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify({
                    type: 'PAYMENT_SUCCESS',
                    userId: '{{ $user_id ?? "" }}',
                    email: '{{ $email ?? "" }}'
                }));
            }
            // For Flutter
            else if (window.FlutterChannel) {
                window.FlutterChannel.postMessage(JSON.stringify({
                    type: 'PAYMENT_SUCCESS',
                    userId: '{{ $user_id ?? "" }}',
                    email: '{{ $email ?? "" }}'
                }));
            }
            // For web
            else {
                window.location.href = '/dashboard';
            }
        }

        // Auto-notify after 2 seconds
        setTimeout(closeWebView, 2000);
    </script>
</body>
</html>