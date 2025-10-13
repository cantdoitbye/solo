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
            background: radial-gradient(circle at 50% 0%, #3a235c 0%, #181828 100%);
            min-height: 100vh;
            padding: 0;
            color: white;
            overflow-x: hidden;
        }

        .app-header {
            display: flex;
            align-items: center;
            padding: 16px 24px;
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
            transition: background 0.3s ease;
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: white;
        }

        .header {
            padding: 48px 28px 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 12px;
            color: white;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
            max-width: 340px;
            margin: 0 auto;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.08);
            padding: 14px 24px;
            margin: 0 28px 32px;
            border-radius: 16px;
            text-align: center;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .plans-wrapper {
            position: relative;
            padding-bottom: 24px;
        }

        .plans-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding: 0 28px 20px 28px;
        }

        /* Hide scrollbar */
        .plans-container::-webkit-scrollbar {
            display: none;
        }

        .plan-card {
            background: rgba(30, 30, 46, 0.8);
            border: 2px solid #fd3270;
            border-radius: 24px;
            padding: 36px 28px;
            position: relative;
            backdrop-filter: blur(44px);
            box-shadow: 0 0 16px 1px #fd32707c, 0 1.5px 15px #fd327050;
            min-width: 320px;
            max-width: 320px;
            flex-shrink: 0;
            scroll-snap-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 0 24px 2px #fd32707c, 0 4px 20px #fd327050;
        }

        .plan-card.popular {
            border-color: #fd3270;
            box-shadow: 0 0 20px 2px #fd32707c, 0 1.5px 18px #fd327050;
        }

        .plan-card.active {
            border-color: #10b981;
            box-shadow: 0 0 16px 1px rgba(16, 185, 129, 0.5), 0 1.5px 15px rgba(16, 185, 129, 0.3);
        }

        .plan-header {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }

        .plan-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
            color: white;
            margin-right: 14px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .plan-title-section {
            flex: 1;
        }

        .plan-name {
            font-size: 22px;
            font-weight: 600;
            color: white;
            margin-bottom: 4px;
            letter-spacing: -0.3px;
        }

        .plan-price {
            font-size: 34px;
            font-weight: 700;
            color: #FF2B55;
            line-height: 1;
        }

        .plan-price span {
            font-size: 16px;
            color: #FF2B55;
            font-weight: 600;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 32px;
        }

        .plan-features li {
            padding: 13px 0;
            color: rgba(255, 255, 255, 0.75);
            display: flex;
            align-items: flex-start;
            font-size: 14px;
            line-height: 1.6;
        }

        .plan-features li::before {
            content: "✓";
            color: rgba(255, 255, 255, 0.5);
            font-weight: bold;
            margin-right: 14px;
            font-size: 12px;
            flex-shrink: 0;
            width: 22px;
            height: 22px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Scroll indicator dots */
        .scroll-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 0 28px 20px;
        }

        .scroll-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .scroll-dot:hover {
            background: rgba(255, 255, 255, 0.4);
        }

        .scroll-dot.active {
            width: 28px;
            border-radius: 4px;
            background: #FF2B55;
        }

        .footer {
            text-align: center;
            padding: 24px 28px 40px;
            color: rgba(255, 255, 255, 0.35);
            font-size: 13px;
        }

        /* Badge */
        .plan-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #FF2B55 0%, #DB143B 100%);
            color: white;
            padding: 7px 22px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            box-shadow: 0 4px 12px rgba(255, 43, 85, 0.5);
        }

        .active-badge-top {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 7px 22px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 16px;
        }

        /* Mobile (Portrait) */
        @media (max-width: 480px) {
            .header {
                padding: 40px 20px 32px;
            }

            .header h1 {
                font-size: 28px;
            }

            .header p {
                font-size: 14px;
            }

            .plans-container {
                padding: 0 20px 20px 20px;
                gap: 16px;
            }

            .plan-card {
                padding: 32px 24px;
                min-width: 280px;
                max-width: 280px;
            }

            .plan-icon {
                width: 50px;
                height: 50px;
                font-size: 22px;
            }

            .plan-name {
                font-size: 20px;
            }

            .plan-price {
                font-size: 30px;
            }

            .user-info {
                margin: 0 20px 28px;
                padding: 12px 20px;
            }

            .scroll-indicator {
                padding: 0 20px 16px;
            }
        }

        /* Tablet (Portrait) */
        @media (min-width: 481px) and (max-width: 768px) {
            .header {
                padding: 52px 32px 44px;
            }

            .header h1 {
                font-size: 36px;
            }

            .plans-container {
                padding: 0 32px 24px 32px;
                gap: 24px;
            }

            .plan-card {
                min-width: 340px;
                max-width: 340px;
                padding: 40px 32px;
            }

            .user-info {
                margin: 0 32px 36px;
                padding: 16px 28px;
                font-size: 15px;
            }

            .scroll-indicator {
                padding: 0 32px 24px;
            }
        }

        /* Tablet (Landscape) & Small Desktop */
        @media (min-width: 769px) and (max-width: 1024px) {
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 20px;
            }

            .app-container {
                max-width: 900px;
                width: 100%;
                background: radial-gradient(circle at 50% 0%, #3a235c 0%, #181828 100%);
                border-radius: 32px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }

            .plans-container {
                padding: 0 40px 24px 40px;
                gap: 24px;
            }

            .plan-card {
                min-width: 360px;
                max-width: 360px;
            }

            .user-info {
                margin: 0 40px 36px;
            }

            .scroll-indicator {
                padding: 0 40px 24px;
            }
        }

        /* Desktop (Large screens) */
        @media (min-width: 1025px) {
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 40px 20px;
            }

            .app-container {
                max-width: 480px;
                width: 100%;
                background: radial-gradient(circle at 50% 0%, #3a235c 0%, #181828 100%);
                border-radius: 32px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }

            .plans-container {
                padding: 0 40px 24px 40px;
            }

            .plan-card {
                min-width: 360px;
                max-width: 360px;
            }

            .user-info {
                margin: 0 40px 36px;
            }

            .scroll-indicator {
                padding: 0 40px 24px;
            }
        }

        /* Extra smooth scrolling on touch devices */
        @media (hover: none) and (pointer: coarse) {
            .plans-container {
                scroll-behavior: smooth;
            }
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
            <strong>{{ $user->name ?? 'User' }}</strong> • {{ $user->email }}
        </div>
        @endif

        <div class="plans-wrapper">
            <div class="plans-container" id="plansContainer">
                @forelse($plans as $index => $plan)
                <div class="plan-card {{ $plan['id'] === 'pro' ? 'popular' : '' }} {{ ($activePlan && $activePlan->plan_id === $plan['id']) ? 'active' : '' }}" data-index="{{ $index }}">
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
                                P
                            @else
                                D
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

            <!-- Scroll Indicator Dots -->
            @if(count($plans) > 1)
            <div class="scroll-indicator" id="scrollIndicator">
                @foreach($plans as $index => $plan)
                <div class="scroll-dot {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}" onclick="scrollToCard({{ $index }})"></div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="footer">
            <p>Secure payment powered by Fluidpay</p>
        </div>
    </div>

    <script>
        // Scroll indicator update
        const plansContainer = document.getElementById('plansContainer');
        const scrollIndicator = document.getElementById('scrollIndicator');
        const dots = scrollIndicator ? scrollIndicator.querySelectorAll('.scroll-dot') : [];
        const cards = plansContainer ? plansContainer.querySelectorAll('.plan-card') : [];

        // Update active dot on scroll
        if (plansContainer && dots.length > 0) {
            plansContainer.addEventListener('scroll', () => {
                const scrollLeft = plansContainer.scrollLeft;
                const containerWidth = plansContainer.offsetWidth;
                
                cards.forEach((card, index) => {
                    const cardLeft = card.offsetLeft;
                    const cardCenter = cardLeft + (card.offsetWidth / 2);
                    const containerCenter = scrollLeft + (containerWidth / 2);
                    
                    if (Math.abs(cardCenter - containerCenter) < card.offsetWidth / 2) {
                        dots.forEach(dot => dot.classList.remove('active'));
                        dots[index]?.classList.add('active');
                    }
                });
            });
        }

        // Scroll to specific card when dot is clicked
        function scrollToCard(index) {
            if (cards[index]) {
                const card = cards[index];
                const containerWidth = plansContainer.offsetWidth;
                const cardLeft = card.offsetLeft;
                const cardWidth = card.offsetWidth;
                const scrollTo = cardLeft - (containerWidth / 2) + (cardWidth / 2);
                
                plansContainer.scrollTo({
                    left: scrollTo,
                    behavior: 'smooth'
                });
            }
        }

        function goBack() {
        if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify({
                type: 'GO_BACK'
            }));
        } else if (window.FlutterChannel) {
            window.FlutterChannel.postMessage(JSON.stringify({
                type: 'GO_BACK'
            }));
        } else {
            window.history.back();
        }
    }

       function purchasePlan(planId, paymentUrl, userEmail, userId, userName) {
        const url = new URL(paymentUrl);
        
        // Add user info as query params
        if (userEmail) url.searchParams.append('customer_email', userEmail);
        if (userName) url.searchParams.append('customer_name', userName);
        
        // Add hash to success/cancel URLs
        const hash = '{{ $hash ?? "" }}';
        if (hash) {
            const successUrl = '{{ url("/plans/success") }}?hash=' + hash;
            const cancelUrl = '{{ url("/plans/cancel") }}?hash=' + hash;
            
            url.searchParams.append('success_url', successUrl);
            url.searchParams.append('cancel_url', cancelUrl);
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

        window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'PAYMENT_SUCCESS') {
            window.location.reload();
        }
    });
    </script>
</body>
</html>