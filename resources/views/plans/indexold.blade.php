{{-- resources/views/plans/index.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Select Plan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            /* background: #1a1a2e; */
                background: radial-gradient(circle at 50% 0%, #3a235c 0%, #181828 100%);

            min-height: 100vh;
            padding: 0;
            color: white;
        }

        .app-header {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            background: rgba(0, 0, 0, 0.3);
        }

        .back-button {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            margin-right: 16px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .header {
            padding: 40px 28px 32px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            color: white;
        }

        .header p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.6;
            max-width: 300px;
            margin: 0 auto;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 28px;
            margin: 0 28px 24px;
            border-radius: 12px;
            text-align: center;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .plans-container {
            padding: 0 28px 40px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .plan-card {
            background: rgba(30, 30, 46, 0.8);
            /* border: 2.5px solid; */
                border: 2px solid #fd3270;

            border-image: linear-gradient(135deg, #FF2B55 0%, #FF2B55 100%) 1;
            border-radius: 24px;
            padding: 36px 28px;
            position: relative;
            backdrop-filter: blur(44px);
                box-shadow: 0 0 16px 1px #fd32707c, 0 1.5px 15px #fd327050;

        }

        .plan-card.popular {
            border-image: linear-gradient(135deg, #DB143B 0%, #DB143B 100%) 1;
            box-shadow: 0 8px 32px rgba(255, 43, 85, 0.3);
        }

        .plan-card.active {
            border-image: linear-gradient(135deg, #10b981 0%, #059669 100%) 1;
        }

        .plan-header {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }

        .plan-icon {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .plan-title-section {
            flex: 1;
        }

        .plan-name {
            font-size: 20px;
            font-weight: 600;
            color: white;
            margin-bottom: 2px;
        }

        .plan-price {
            font-size: 32px;
            font-weight: 700;
            color: #FF2B55;
            line-height: 1;
        }

        .plan-price span {
            font-size: 18px;
            color: #FF2B55;
            font-weight: 600;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 32px;
        }

        .plan-features li {
            padding: 14px 0;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: flex-start;
            font-size: 14px;
            line-height: 1.5;
        }

        .plan-features li::before {
            content: "✓";
            color: rgba(255, 255, 255, 0.4);
            font-weight: bold;
            margin-right: 14px;
            font-size: 14px;
            flex-shrink: 0;
            width: 20px;
            height: 20px;
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }

        .buy-button {
            width: 100%;
            padding: 18px 32px;
            background: linear-gradient(135deg, #FF2B55 0%, #DB143B 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(255, 43, 85, 0.4);
            text-transform: capitalize;
        }

        .buy-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(255, 43, 85, 0.5);
        }

        .buy-button:active {
            transform: translateY(0);
        }

        .buy-button:disabled {
            background: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.4);
            cursor: not-allowed;
            box-shadow: none;
        }

        .footer {
            text-align: center;
            padding: 20px 28px 40px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 12px;
        }

        /* Badge */
        .plan-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #FF2B55 0%, #DB143B 100%);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(255, 43, 85, 0.4);
        }

        .active-badge-top {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        /* Desktop */
        @media (min-width: 769px) {
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 20px;
            }

            .app-container {
                max-width: 420px;
                width: 100%;
                background: #1a1a2e;
                border-radius: 32px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }

            .plans-container {
                max-height: 600px;
                overflow-y: auto;
            }
        }

        /* Mobile specific */
        @media (max-width: 768px) {
            .header {
                padding: 32px 24px 24px;
            }

            .header h1 {
                font-size: 28px;
            }

            .plans-container {
                padding: 0 24px 32px;
            }

            .plan-card {
                padding: 32px 24px;
            }
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <button class="back-button" onclick="goBack()">←</button>
            <div class="page-title">Buy Premium</div>
        </div>

        <div class="header">
            <h1>Select Plan</h1>
            <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
        </div>

        @if($user)
        <div class="user-info">
            {{ $user->name ?? 'User' }} • {{ $user->email }}
        </div>
        @endif

        <div class="plans-container">
            @forelse($plans as $plan)
            <div class="plan-card {{ $plan['id'] === 'pro' ? 'popular' : '' }} {{ ($activePlan && $activePlan->plan_id === $plan['id']) ? 'active' : '' }}">
                @if($activePlan && $activePlan->plan_id === $plan['id'])
                    <div class="active-badge-top">Active</div>
                @elseif($plan['id'] === 'pro')
                    <div class="plan-badge">Popular</div>
                @endif
                
                <div class="plan-header">
                    <div class="plan-icon">
                        @if($plan['id'] === 'basic')
                            B
                        @elseif($plan['id'] === 'pro')
                            D
                        @else
                            P
                        @endif
                    </div>
                    <div class="plan-title-section">
                        <div class="plan-name">{{ $plan['name'] }}</div>
                        <div class="plan-price">
                            ${{ number_format($plan['amount'], 0) }}<span>/Monthly</span>
                        </div>
                    </div>
                </div>
                
                <ul class="plan-features">
                    @foreach($plan['features'] as $feature)
                    <li>Lorem Ipsum is simply dummy text</li>
                    @endforeach
                </ul>
                
                @if($activePlan && $activePlan->plan_id === $plan['id'])
                    <button class="buy-button" disabled>Current Plan</button>
                @else
                    <button 
                        class="buy-button" 
                        onclick="purchasePlan('{{ $plan['id'] }}', '{{ $plan['payment_url'] }}', '{{ $user?->email ?? '' }}', '{{ $user?->id ?? '' }}', '{{ $user?->name ?? '' }}')">
                        Join {{ $plan['name'] }}
                    </button>
                @endif
            </div>
            @empty
            <div class="loading">Loading plans...</div>
            @endforelse
        </div>

        <div class="footer">
            <p>Secure payment powered by Fluidpay</p>
        </div>
    </div>

    <script>
        function goBack() {
            // For React Native
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify({
                    type: 'GO_BACK'
                }));
            }
            // For Flutter
            else if (window.FlutterChannel) {
                window.FlutterChannel.postMessage(JSON.stringify({
                    type: 'GO_BACK'
                }));
            }
            // For web browser
            else {
                window.history.back();
            }
        }

        function purchasePlan(planId, paymentUrl, userEmail, userId, userName) {
            const url = new URL(paymentUrl);
            
            if (userEmail) {
                url.searchParams.append('customer_email', userEmail);
            }
            if (userId) {
                url.searchParams.append('customer_id', userId);
            }
            if (userName) {
                url.searchParams.append('customer_name', userName);
            }
            
            const finalUrl = url.toString();
            
            // For React Native
            if (window.ReactNativeWebView) {
                window.ReactNativeWebView.postMessage(JSON.stringify({
                    type: 'OPEN_PAYMENT',
                    planId: planId,
                    paymentUrl: finalUrl,
                    userEmail: userEmail,
                    userId: userId
                }));
            }
            // For Flutter
            else if (window.FlutterChannel) {
                window.FlutterChannel.postMessage(JSON.stringify({
                    type: 'OPEN_PAYMENT',
                    planId: planId,
                    paymentUrl: finalUrl,
                    userEmail: userEmail,
                    userId: userId
                }));
            }
            // For web browser
            else {
                window.location.href = finalUrl;
            }
        }

        // Listen for payment success
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'PAYMENT_SUCCESS') {
                window.location.reload();
            }
        });
    </script>
</body>
</html>