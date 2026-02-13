<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Buildflow - Complete Construction ERP for contractors, builders, and construction companies. Manage projects, finances, teams, and more.">
    <title>Buildflow - Construction Management Made Simple</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            background: var(--bg-primary);
        }

        /* Navigation */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
        }

        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .nav-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2563EB, #7C3AED);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
        }

        .nav-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--primary-600);
        }

        .nav-cta {
            display: flex;
            gap: 1rem;
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 8rem 2rem 4rem;
            background: linear-gradient(135deg, #F0F9FF 0%, #FEF3C7 50%, #F0FDF4 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%232563eb' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero-content h1 span {
            background: linear-gradient(135deg, #2563EB, #7C3AED);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: 1.25rem;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-xl {
            padding: 1rem 2rem;
            font-size: 1.125rem;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary-xl {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: white;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .btn-primary-xl:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
        }

        .btn-secondary-xl {
            background: white;
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .btn-secondary-xl:hover {
            border-color: var(--primary-500);
            color: var(--primary-600);
        }

        .hero-stats {
            display: flex;
            gap: 2rem;
        }

        .hero-stat {
            text-align: left;
        }

        .hero-stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .hero-stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        .hero-visual {
            position: relative;
        }

        .hero-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            padding: 1.5rem;
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
        }

        .hero-card-header {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .hero-card-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .hero-card-content {
            background: linear-gradient(135deg, #F8FAFC, #E2E8F0);
            border-radius: 12px;
            padding: 1.5rem;
            min-height: 300px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mini-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .mini-card-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .mini-card-text h4 {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .mini-card-text p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Features Section */
        .features {
            padding: 6rem 2rem;
            background: white;
        }

        .section-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-label {
            color: var(--primary-600);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            padding: 2rem;
            border-radius: 16px;
            background: var(--bg-secondary);
            border: 1px solid transparent;
            transition: all 0.3s;
        }

        .feature-card:hover {
            border-color: var(--primary-200);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transform: translateY(-4px);
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 1.5rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Pricing Section */
        .pricing {
            padding: 6rem 2rem;
            background: linear-gradient(180deg, #F8FAFC 0%, white 100%);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }

        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid var(--border-color);
            position: relative;
            transition: all 0.3s;
        }

        .pricing-card:hover {
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
            transform: translateY(-4px);
        }

        .pricing-card.popular {
            border: 2px solid var(--primary-500);
            box-shadow: 0 20px 50px rgba(37, 99, 235, 0.15);
        }

        .pricing-badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #2563EB, #7C3AED);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .pricing-name {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .pricing-users {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        .pricing-amount {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .pricing-currency {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .pricing-value {
            font-size: 3.5rem;
            font-weight: 800;
        }

        .pricing-period {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .pricing-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            min-height: 60px;
        }

        .pricing-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .pricing-features li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.5rem 0;
            font-size: 0.875rem;
        }

        .pricing-features li svg {
            color: var(--success-500);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .pricing-cta {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .pricing-cta.primary {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: white;
        }

        .pricing-cta.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
        }

        .pricing-cta.secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .pricing-cta.secondary:hover {
            border-color: var(--primary-500);
            color: var(--primary-600);
        }

        /* FAQ Section */
        .faq {
            padding: 6rem 2rem;
            background: white;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .faq-item {
            padding: 1.5rem;
            border-radius: 12px;
            background: var(--bg-secondary);
        }

        .faq-question {
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .faq-answer {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, #1E3A8A, #7C3AED);
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .cta p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.125rem;
            margin-bottom: 2rem;
        }

        .cta .btn-xl {
            background: white;
            color: var(--primary-700);
        }

        .cta .btn-xl:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        /* Footer */
        .footer {
            padding: 4rem 2rem 2rem;
            background: #0F172A;
            color: white;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: white;
        }

        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .trust-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-stats {
                justify-content: center;
            }

            .hero-visual {
                display: none;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .features-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin: 0 auto;
            }

            .faq-grid {
                grid-template-columns: 1fr;
            }

            .nav-links {
                display: none;
            }
        }

        /* Checkout Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            display: none;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
        }

        .form-submit:hover {
            transform: translateY(-1px);
        }

        .form-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .selected-plan {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .selected-plan-name {
            font-weight: 600;
        }

        .selected-plan-price {
            color: var(--primary-600);
            font-weight: 700;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="nav">
        <div class="nav-container">
            <a href="/" class="nav-brand">
                <div class="nav-logo">B</div>
                <span class="nav-title">Buildflow</span>
            </a>
            <div class="nav-links">
                <a href="#features">Features</a>
                <a href="#pricing">Pricing</a>
                <a href="#faq">FAQ</a>
            </div>
            <div class="nav-cta">
                <a href="/login" class="btn btn-outline">Sign In</a>
                <a href="#pricing" class="btn btn-primary">Start Free Trial</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1>Build Smarter, <span>Profit More</span></h1>
                <p>The complete ERP solution built for construction professionals. Manage projects, track costs, handle
                    payroll, and grow your business—all in one platform.</p>
                <div class="hero-buttons">
                    <a href="#pricing" class="btn-xl btn-primary-xl">
                        Start 14-Day Free Trial
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                    <a href="#features" class="btn-xl btn-secondary-xl">See How It Works</a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-value">500+</div>
                        <div class="hero-stat-label">Active Companies</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value">25%</div>
                        <div class="hero-stat-label">Avg Cost Savings</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value">99.9%</div>
                        <div class="hero-stat-label">Uptime</div>
                    </div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-card">
                    <div class="hero-card-header">
                        <div class="hero-card-dot" style="background: #EF4444;"></div>
                        <div class="hero-card-dot" style="background: #F59E0B;"></div>
                        <div class="hero-card-dot" style="background: #22C55E;"></div>
                    </div>
                    <div class="hero-card-content">
                        <div class="mini-card">
                            <div class="mini-card-icon" style="background: linear-gradient(135deg, #2563EB, #1D4ED8);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                </svg>
                            </div>
                            <div class="mini-card-text">
                                <h4>Downtown Office Tower</h4>
                                <p>$2.4M budget • 68% complete</p>
                            </div>
                        </div>
                        <div class="mini-card">
                            <div class="mini-card-icon" style="background: linear-gradient(135deg, #22C55E, #16A34A);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="mini-card-text">
                                <h4>Revenue This Month</h4>
                                <p>$145,280 • +12% vs last month</p>
                            </div>
                        </div>
                        <div class="mini-card">
                            <div class="mini-card-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="mini-card-text">
                                <h4>Team Hours Logged</h4>
                                <p>1,284 hours this week</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-container">
            <div class="section-header">
                <div class="section-label">Features</div>
                <h2 class="section-title">Everything You Need to Run Your Business</h2>
                <p class="section-subtitle">From project tracking to payroll, we've got you covered with tools designed
                    specifically for construction companies.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #2563EB, #1D4ED8);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Project Management</h3>
                    <p class="feature-description">Track projects from bid to completion with tasks, milestones, and
                        real-time progress updates.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #22C55E, #16A34A);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Financial Tracking</h3>
                    <p class="feature-description">Monitor budgets, expenses, and profitability per project. Integrated
                        invoicing with Stripe payments.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h3 class="feature-title">Time Tracking</h3>
                    <p class="feature-description">Clock in/out functionality with GPS, manual entry options, and
                        automatic overtime calculations.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                    </div>
                    <h3 class="feature-title">Payroll Management</h3>
                    <p class="feature-description">Hourly, daily, salary, and commission payments with tax calculations
                        and payment records.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #06B6D4, #0891B2);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="feature-title">Inventory Control</h3>
                    <p class="feature-description">Track materials, tools, and equipment. Get low-stock alerts and
                        manage supplier relationships.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <h3 class="feature-title">Reports & Analytics</h3>
                    <p class="feature-description">Comprehensive dashboards with profitability insights. Export to PDF,
                        Excel, or CSV.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing" id="pricing">
        <div class="section-container">
            <div class="section-header">
                <div class="section-label">Pricing</div>
                <h2 class="section-title">Simple, Transparent Pricing</h2>
                <p class="section-subtitle">Start with a 14-day free trial. No credit card required. Cancel anytime.</p>
            </div>
            <div class="pricing-grid" id="pricing-cards">
                <!-- Plans will be loaded via JavaScript -->
                <div class="pricing-card">
                    <div class="pricing-name">Loading...</div>
                </div>
            </div>
            <div class="trust-badges">
                <div class="trust-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Secure Payments via Stripe
                </div>
                <div class="trust-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Cancel Anytime
                </div>
                <div class="trust-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    14-Day Free Trial
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq" id="faq">
        <div class="section-container">
            <div class="section-header">
                <div class="section-label">FAQ</div>
                <h2 class="section-title">Frequently Asked Questions</h2>
            </div>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">What happens after I sign up?</div>
                    <div class="faq-answer">Your workspace is created instantly. You'll receive login credentials via
                        email and can start adding your team members and projects right away.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Can I change plans later?</div>
                    <div class="faq-answer">Absolutely! You can upgrade or downgrade at any time. Changes take effect
                        immediately with prorated billing.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">What if I need more users?</div>
                    <div class="faq-answer">Teams over 10 users can add additional seats for $14/month each. Contact us
                        for enterprise pricing with unlimited users.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Is my data secure?</div>
                    <div class="faq-answer">Yes! We use bank-level encryption, secure cloud infrastructure, and regular
                        backups. Your data is isolated from other tenants.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Can I import existing data?</div>
                    <div class="faq-answer">Yes, we support CSV imports for clients, projects, and other data. Our
                        support team can help with migration.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question">Do you offer mobile access?</div>
                    <div class="faq-answer">Yes! Our Progressive Web App (PWA) works offline on any device. Perfect for
                        job sites without reliable internet.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <h2>Ready to Transform Your Business?</h2>
        <p>Join hundreds of construction companies already using Buildflow.</p>
        <a href="#pricing" class="btn-xl">Start Your Free Trial</a>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="nav-logo">B</div>
                <span>Buildflow</span>
            </div>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Support</a>
            </div>
        </div>
    </footer>

    <!-- Checkout Modal -->
    <div class="modal-overlay" id="checkout-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Start Your Free Trial</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="selected-plan">
                <span class="selected-plan-name" id="selected-plan-name">Business Plan</span>
                <span class="selected-plan-price" id="selected-plan-price">$90/month</span>
            </div>
            <form id="checkout-form">
                <input type="hidden" name="plan_slug" id="plan-slug">
                <div class="form-group">
                    <label class="form-label">Company Name *</label>
                    <input type="text" class="form-input" name="company_name" required
                        placeholder="Your Construction Co.">
                </div>
                <div class="form-group">
                    <label class="form-label">Your Email *</label>
                    <input type="email" class="form-input" name="email" required placeholder="you@company.com">
                </div>
                <div class="form-group">
                    <label class="form-label">Your Name</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <input type="text" class="form-input" name="first_name" placeholder="First">
                        <input type="text" class="form-input" name="last_name" placeholder="Last">
                    </div>
                </div>
                <button type="submit" class="form-submit" id="submit-btn">
                    Continue to Payment
                </button>
                <p style="text-align: center; margin-top: 1rem; font-size: 0.75rem; color: var(--text-muted);">
                    14-day free trial • No credit card required • Cancel anytime
                </p>
            </form>
        </div>
    </div>

    <script>
        // Load pricing plans
        async function loadPlans() {
            try {
                const response = await fetch('/api/plans');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    renderPlans(result.data);
                }
            } catch (error) {
                console.error('Failed to load plans:', error);
            }
        }

        function renderPlans(plans) {
            const container = document.getElementById('pricing-cards');

            // Features lists for each plan
            const planFeatures = {
                'team': [
                    'Up to 3 team members',
                    'Client management',
                    'Project tracking',
                    'Time logging',
                    'Estimates & invoices',
                    'Basic reports',
                    'Email notifications',
                    'Standard support'
                ],
                'business': [
                    'Up to 5 team members',
                    'Everything in Team, plus:',
                    'Payroll management',
                    'Inventory tracking',
                    'Advanced analytics',
                    'PWA offline mode',
                    'Support tickets',
                    'Priority email support'
                ],
                'professional': [
                    'Up to 10 team members',
                    'Everything in Business, plus:',
                    'Advanced reporting suite',
                    'Custom branding',
                    'Export tools (PDF, Excel)',
                    'Automated workflows',
                    'Priority chat support',
                    'Dedicated account manager'
                ]
            };

            container.innerHTML = plans.map(plan => {
                const features = planFeatures[plan.slug] || [];
                const isPopular = plan.is_popular;

                return `
                    <div class="pricing-card ${isPopular ? 'popular' : ''}">
                        ${isPopular ? '<div class="pricing-badge">Most Popular</div>' : ''}
                        <div class="pricing-name">${plan.name}</div>
                        <div class="pricing-users">Up to ${plan.user_limit} users</div>
                        <div class="pricing-amount">
                            <span class="pricing-currency">$</span>
                            <span class="pricing-value">${plan.price}</span>
                            <span class="pricing-period">/month</span>
                        </div>
                        <div class="pricing-description">${plan.description}</div>
                        <ul class="pricing-features">
                            ${features.map(f => `
                                <li>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    ${f}
                                </li>
                            `).join('')}
                        </ul>
                        <button class="pricing-cta ${isPopular ? 'primary' : 'secondary'}" 
                                onclick="openCheckout('${plan.slug}', '${plan.name}', ${plan.price})">
                            Start Free Trial
                        </button>
                    </div>
                `;
            }).join('');
        }

        function openCheckout(slug, name, price) {
            document.getElementById('plan-slug').value = slug;
            document.getElementById('selected-plan-name').textContent = name + ' Plan';
            document.getElementById('selected-plan-price').textContent = '$' + price + '/month';
            document.getElementById('checkout-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('checkout-modal').classList.remove('active');
        }

        // Handle checkout form
        document.getElementById('checkout-form').addEventListener('submit', async function (e) {
            e.preventDefault();

            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch('/api/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (result.success && result.data.checkout_url) {
                    window.location.href = result.data.checkout_url;
                } else {
                    alert(result.error || 'Something went wrong. Please try again.');
                    btn.disabled = false;
                    btn.textContent = 'Continue to Payment';
                }
            } catch (error) {
                alert('Connection error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Continue to Payment';
            }
        });

        // Close modal when clicking outside
        document.getElementById('checkout-modal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Handle checkout cancelled
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('checkout') === 'cancelled') {
            alert('Checkout was cancelled. Feel free to try again when you\'re ready!');
            window.history.replaceState({}, document.title, '/');
        }

        // Load plans on page load
        loadPlans();
    </script>
</body>

</html>