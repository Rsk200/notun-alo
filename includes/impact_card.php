<?php
$impactUserId = isset($impactUserId) ? (int)$impactUserId : (int)($_SESSION['user_id'] ?? 1);
$currentLang = $_SESSION['lang'] ?? 'en';
?>
<div class="impact-content-root" id="impact-root" data-user-id="<?= $impactUserId ?>" data-lang="<?= $currentLang ?>">
    <style>
        /* ===== IMPACT CARD DARK MODE OVERRIDES ===== */
        body.dark-mode .impact-callout {
            background: #0a1f12 !important;
            border-color: #1e3222 !important;
            border-left-color: #34d399 !important;
        }
        body.dark-mode .callout-main { color: #34d399 !important; }
        body.dark-mode .tab-btn {
            background: #0b130c !important;
            border-color: #1e3222 !important;
            color: #64748B !important;
        }
        body.dark-mode .tab-btn.active {
            background: #0f1a10 !important;
            color: #E2E8F0 !important;
            border-top-color: #34d399 !important;
        }
        body.dark-mode .tab-content {
            background: #0f1a10 !important;
            border-color: #1e3222 !important;
            box-shadow: 0 4px 30px rgba(0,0,0,0.4) !important;
        }
        body.dark-mode .forecast-pill {
            background: #0b130c !important;
            border-color: #1e3222 !important;
        }
        body.dark-mode .f-month { color: #94A3B8 !important; }
        body.dark-mode .impact-footer {
            background: #0a1f12 !important;
            border-color: #1e3222 !important;
        }
        body.dark-mode .footer-text { color: #34d399 !important; }
        body.dark-mode .comp-bar-bg { background: #152018 !important; }
        body.dark-mode .xp-bar-wrapper { background: #152018 !important; }
        body.dark-mode .badge-name { color: #E2E8F0 !important; }
        body.dark-mode .gami-hero { border-left-color: #FBBF24 !important; }
        body.dark-mode .toggle-btn {
            border-color: #1e3222 !important;
            color: #94A3B8 !important;
        }
        body.dark-mode .toggle-btn.active { background: #34d399 !important; border-color: #34d399 !important; color: #0a1f12 !important; }
        /* Section 1: Gamification Hero */
        .gami-hero { display: flex; align-items: center; justify-content: space-between; gap: 32px; padding: 32px; border-left: 6px solid var(--gold); }
        .lvl-badge-block { display: flex; align-items: center; }
        .lvl-circle { width: 80px; height: 80px; border: 4px solid var(--gold); border-radius: 50%; background: var(--gold-bg); display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(217,119,6,0.15); }
        .lvl-circle .lvl-label { font-size: 11px; font-weight: 600; color: var(--gold-text); text-transform: uppercase; }
        .lvl-circle .lvl-val { font-size: 32px; font-weight: 800; color: var(--gold); line-height: 1; }
        .badge-info { margin-left: 20px; }
        .badge-name { font-size: 24px; font-weight: 800; color: var(--text-primary); margin: 0; }
        .badge-sub { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
        .streak-pill { display: inline-block; background: var(--gold-bg); color: var(--gold-text); font-size: 12px; font-weight: 500; padding: 4px 12px; border-radius: 99px; margin-top: 8px; }

        .xp-container { flex: 1; max-width: 450px; }
        .xp-label-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: 500; color: var(--text-secondary); margin-bottom: 10px; }
        .xp-bar-wrapper { height: 14px; background: var(--border-light); border-radius: 99px; position: relative; overflow: hidden; margin-bottom: 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); }
        .xp-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold), #F59E0B); width: 0%; border-radius: 99px; transition: width 1.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .xp-sub { font-size: 11px; color: var(--text-muted); text-align: center; }

        .next-tier-preview { text-align: right; }
        .next-label { font-size: 10px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
        .next-badge { font-size: 14px; font-weight: 600; color: var(--text-secondary); margin: 4px 0; }
        .streak-pill-outline { border: 1px solid var(--border); font-size: 12px; color: var(--text-secondary); padding: 4px 12px; border-radius: 99px; display: inline-block; margin-top: 8px; }

        /* HERO STAT (CO2) */
        .hero-metric-card { grid-column: span 3; display: flex; align-items: center; gap: 40px; padding: 40px; border-bottom: 4px solid var(--brand-primary); }
        .hero-metric-info { flex: 1; }
        .hero-m-label { font-size: 12px; font-weight: 700; color: var(--brand-primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 12px; display: block; }
        .hero-m-num { font-size: 56px; font-weight: 800; color: var(--text-primary); line-height: 1; }
        .hero-m-unit { font-size: 24px; font-weight: 500; color: var(--text-muted); }
        .hero-m-sub { font-size: 16px; color: var(--text-secondary); margin-top: 16px; font-weight: 500; }
        .summary-highlight { font-weight: 800; color: var(--brand-primary); }
        .hero-m-visual { width: 120px; height: 120px; background: var(--brand-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 64px; color: var(--brand-primary); flex-shrink: 0; }

        /* SUPPORTING METRICS */
        .supporting-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px; }
        .s-metric-card { padding: 24px; display: flex; align-items: center; gap: 20px; }
        .s-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0; }
        .s-info .m-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; display: block; }
        .s-info .m-num { font-size: 28px; font-weight: 700; color: var(--text-primary); }
        .s-info .m-unit { font-size: 16px; font-weight: 400; color: var(--text-muted); }
        .m-trend { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; }

        /* Section 3: Summary Callout */
        .impact-callout { background: #F0FDF4; border: 1px solid #D1FAE5; border-left: 4px solid var(--brand-primary); border-radius: 0 12px 12px 0; padding: 16px 20px; display: flex; align-items: flex-start; gap: 14px; position: relative; }
        .callout-icon { font-size: 22px; color: var(--brand-primary); flex-shrink: 0; margin-top: 2px; }
        .callout-main { font-size: 14px; color: #065F46; line-height: 1.7; }
        .callout-sub { font-size: 13px; color: var(--brand-primary); font-weight: 500; margin-top: 6px; }
        .share-btn { border: 1px solid var(--brand-primary); color: var(--brand-primary); background: transparent; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; margin-left: auto; align-self: center; transition: 0.2s; }
        .share-btn:hover { background: var(--brand-primary); color: white; }

        /* TABS */
        .tab-component { margin-bottom: 24px; }
        .tab-nav { display: flex; gap: 8px; margin-bottom: 0; padding: 0 12px; }
        .tab-btn { padding: 12px 24px; border-radius: 12px 12px 0 0; font-size: 14px; font-weight: 600; cursor: pointer; border: 1px solid var(--border); border-bottom: none; background: var(--bg-subtle, #F9FAFB); color: var(--text-muted); transition: background 0.3s, color 0.3s, border-color 0.3s; position: relative; top: 1px; }
        .tab-btn.active { background: var(--bg-card, white); color: var(--text-primary); z-index: 1; border-top: 3px solid var(--brand-primary); }
        .tab-content { background: var(--bg-card, white); border: 1px solid var(--border); border-radius: 0 16px 16px 16px; padding: 32px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); min-height: 350px; transition: background 0.3s, border-color 0.3s; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; animation: fadeIn 0.4s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* CHARTS */
        .chart-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .chart-title { font-size: 18px; font-weight: 600; color: var(--text-primary); }
        .chart-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .ai-pill { background: var(--purple-bg); color: var(--purple); font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 99px; }
        .forecast-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 16px; }
        .forecast-pill { background: var(--bg-subtle, #F9FAFB); border: 1px solid var(--border); border-radius: 10px; padding: 10px 16px; transition: background 0.3s, border-color 0.3s; }
        .f-month { font-size: 12px; font-weight: 500; color: var(--text-secondary); }
        .f-vals { font-size: 14px; font-weight: 600; margin-top: 4px; }
        .toggle-btn { padding: 5px 10px; border-radius: 99px; font-size: 11px; font-weight: 600; cursor: pointer; border: 1px solid var(--border); color: var(--text-secondary); background: transparent; transition: 0.2s; }
        .toggle-btn.active { background: var(--brand-primary); color: white; border-color: var(--brand-primary); }

        /* COMPARISON */
        .comp-card { padding: 32px; }
        .comp-row { margin-bottom: 24px; }
        .comp-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .comp-cat { font-size: 14px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
        .comp-badge { font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: 99px; }
        .comp-bar-container { position: relative; }
        .comp-label-top { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 6px; }
        .comp-bar-bg { height: 12px; background: var(--border-light, #F3F4F6); border-radius: 99px; position: relative; overflow: hidden; transition: background 0.3s; }
        .comp-bar-fill { height: 100%; border-radius: 99px; }
        .avg-marker { position: absolute; top: 0; height: 100%; width: 2px; background: #9CA3AF; z-index: 2; }

        /* IMPACT CALLOUT */
        .impact-callout { background: var(--brand-light, #F0FDF4); border: 1px solid #D1FAE5; border-left: 4px solid var(--brand-primary); border-radius: 0 12px 12px 0; padding: 16px 20px; display: flex; align-items: flex-start; gap: 14px; position: relative; transition: background 0.3s, border-color 0.3s; }

        /* FOOTER */
        .impact-footer { background: var(--brand-light, #F0FDF4); border: 1px solid #D1FAE5; border-radius: 16px; padding: 20px 28px; display: flex; justify-content: space-between; align-items: center; transition: background 0.3s, border-color 0.3s; }
        .footer-text { font-size: 14px; font-weight: 500; color: #065F46; }
        .footer-cta { border: 1.5px solid var(--brand-primary); color: var(--brand-primary); background: transparent; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .footer-cta:hover { background: var(--brand-primary); color: white; }

        @media (max-width: 768px) { .supporting-grid, .forecast-grid { grid-template-columns: 1fr; } .gami-hero { flex-direction: column; text-align: center; } .next-tier-preview { text-align: center; } }
    </style>

    <!-- SECTION 1: Gamification Hero -->
    <div class="white-card gami-hero">
        <div class="lvl-badge-block">
            <div class="lvl-circle">
                <span class="lvl-label"><?= $currentLang === 'bn' ? 'লেভেল' : 'Lvl' ?></span>
                <span class="lvl-val" id="g-lvl">7</span>
            </div>
            <div class="badge-info">
                <h2 class="badge-name" id="g-name">Earth Hero 🌍</h2>
                <p class="badge-sub"><span id="g-xp">22,377</span> <?= $currentLang === 'bn' ? 'এক্সপি অর্জিত' : 'XP earned' ?></p>
                <div class="streak-pill">🔥 <?= $currentLang === 'bn' ? 'সক্রিয় রিসাইক্লার' : 'Active recycler' ?></div>
            </div>
        </div>

        <div class="xp-container">
            <div class="xp-label-row">
                <span id="curr-rank-label">Earth Hero</span>
                <span id="next-rank-label">Climatic Commander</span>
            </div>
            <div class="xp-bar-wrapper">
                <div class="xp-bar-fill" id="g-fill"></div>
            </div>
            <p class="xp-sub"><span id="g-needed">22,377 / 33,000</span> <?= $currentLang === 'bn' ? 'পরবর্তী স্তরের জন্য এক্সপি' : 'XP to next tier' ?></p>
        </div>

        <div class="next-tier-preview">
            <div class="next-label"><?= $currentLang === 'bn' ? 'পরবর্তী স্তর' : 'NEXT TIER' ?></div>
            <div class="next-badge" id="g-next-name">Climatic Commander 🌤️</div>
            <div class="streak-pill-outline"><?= $currentLang === 'bn' ? '১ মাসের ধারা' : '1 month streak' ?></div>
        </div>
    </div>

    <!-- SECTION 2: Hero Metric (CO2) -->
    <div class="white-card hero-metric-card">
        <div class="hero-metric-info">
            <span class="hero-m-label"><?= $currentLang === 'bn' ? 'প্রাথমিক প্রভাব হিরো' : 'Primary Impact Hero' ?></span>
            <div class="hero-m-num"><span id="m-co2">0</span><span class="hero-m-unit"> <?= $currentLang === 'bn' ? 'কেজি CO₂' : 'kg CO₂' ?></span></div>
            <p class="hero-m-sub"><?= $currentLang === 'bn' ? 'আপনি ব্যক্তিগতভাবে' : 'You have personally prevented the equivalent of' ?> <span id="m-co2-sub" class="summary-highlight">880</span> <?= $currentLang === 'bn' ? 'টি গাড়ির ট্রিপের সমতুল্য নির্গমন রোধ করেছেন।' : 'car trips worth of emissions.' ?></p>
            <div class="m-trend" style="color:var(--brand-primary); margin-top:16px;">
                <i class="ti ti-trending-up"></i>
                <span>+12.4 <?= $currentLang === 'bn' ? 'কেজি এই মাসে' : 'kg this month' ?></span>
            </div>
        </div>
        <div class="hero-m-visual">
            <i class="ti ti-cloud"></i>
        </div>
    </div>

    <!-- SECTION 3: Supporting Metrics -->
    <div class="supporting-grid">
        <div class="white-card s-metric-card">
            <div class="s-icon" style="background:var(--blue-bg); color:var(--blue);"><i class="ti ti-droplet"></i></div>
            <div class="s-info">
                <span class="m-label"><?= $currentLang === 'bn' ? 'পানি সাশ্রয়' : 'Water Saved' ?></span>
                <div class="s-info-val"><span class="m-num" id="m-water">0</span><span class="m-unit"> <?= $currentLang === 'bn' ? 'লিটার' : 'L' ?></span></div>
                <div class="m-trend" style="color:var(--blue); font-size:11px; margin-top:4px;"><i class="ti ti-trending-up"></i> +124 <?= $currentLang === 'bn' ? 'লিটার' : 'L' ?></div>
            </div>
        </div>
        <div class="white-card s-metric-card">
            <div class="s-icon" style="background:var(--gold-bg); color:var(--gold);"><i class="ti ti-bolt"></i></div>
            <div class="s-info">
                <span class="m-label"><?= $currentLang === 'bn' ? 'শক্তি সাশ্রয়' : 'Energy Saved' ?></span>
                <div class="s-info-val"><span class="m-num" id="m-energy">0</span><span class="m-unit"> <?= $currentLang === 'bn' ? 'কিলোওয়াট ঘণ্টা' : 'kWh' ?></span></div>
                <div class="m-trend" style="color:var(--gold); font-size:11px; margin-top:4px;"><i class="ti ti-trending-up"></i> +89 <?= $currentLang === 'bn' ? 'কিলোওয়াট ঘণ্টা' : 'kWh' ?></div>
            </div>
        </div>
    </div>

    <!-- SECTION 4: AI Insight Highlight -->
    <div class="impact-callout" style="margin-bottom:32px;">
        <i class="ti ti-bulb-filled callout-icon"></i>
        <div class="callout-content">
            <div class="callout-main" style="font-size:15px; font-weight:600;">
                <?= $currentLang === 'bn' ? 'প্রো টিপ: মোবাইল ফোন রিসাইক্লিং-এর প্রভাব' : 'Pro Tip: Mobile phone recycling has' ?> <span style="color:var(--brand-primary)">29× <?= $currentLang === 'bn' ? 'বেশি' : 'higher impact' ?></span> <?= $currentLang === 'bn' ? 'মিশ্র প্লাস্টিকের চেয়ে।' : 'than mixed plastic.' ?>
            </div>
            <p class="callout-sub"><?= $currentLang === 'bn' ? 'আপনার ই-বর্জ্য অবদান বায়ুমণ্ডল রক্ষার সবচেয়ে কার্যকর উপায়।' : 'Your e-waste contributions are the most effective way to protect the atmosphere.' ?></p>
        </div>
        <button class="share-btn" onclick="shareProgress(this)"><i class="ti ti-share"></i> <?= $currentLang === 'bn' ? 'অগ্রগতি শেয়ার করুন' : 'Share Progress' ?></button>
    </div>

    <!-- SECTION 5: Tabbed Analytics -->
    <div class="tab-component">
        <div class="tab-nav">
            <button class="tab-btn active" onclick="openTab(event, 'forecast-tab')"><?= $currentLang === 'bn' ? '৯০-দিনের এআই পূর্বাভাস' : '90-Day AI Forecast' ?></button>
            <button class="tab-btn" onclick="openTab(event, 'history-tab')"><?= $currentLang === 'bn' ? 'প্রভাবের ইতিহাস' : 'Impact History' ?></button>
        </div>
        <div class="tab-content">
            <!-- Forecast Tab -->
            <div id="forecast-tab" class="tab-pane active">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title"><?= $currentLang === 'bn' ? 'এআই পূর্বাভাস' : 'AI Projections' ?></h3>
                        <p class="chart-sub"><?= $currentLang === 'bn' ? 'আপনার গত ৬ মাসের রিসাইক্লিংয়ের ওপর ভিত্তি করে অনুমিত প্রভাব।' : 'Projected impact based on your last 6 months of recycling.' ?></p>
                    </div>
                    <div class="ai-pill"><?= $currentLang === 'bn' ? 'এআই চালিত 🤖' : 'AI Powered 🤖' ?></div>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="forecastChart"></canvas>
                </div>
                <div class="forecast-grid" id="forecast-details"></div>
            </div>
            <!-- History Tab -->
            <div id="history-tab" class="tab-pane">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title"><?= $currentLang === 'bn' ? 'মাসিক বিশ্লেষণ' : 'Monthly Analytics' ?></h3>
                        <p class="chart-sub"><?= $currentLang === 'bn' ? 'বিভাগ অনুযায়ী আপনার CO₂ সাশ্রয়ের ঐতিহাসিক বিবরণ।' : 'Historical breakdown of your CO₂ savings by category.' ?></p>
                    </div>
                    <div class="toggle-group">
                        <button class="toggle-btn active"><?= $currentLang === 'bn' ? 'বার্ষিক ভিউ' : 'Yearly View' ?></button>
                    </div>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 6: Comparison -->
    <div class="white-card comp-card">
        <h3 class="chart-title" style="font-size:18px; margin-bottom:8px;"><?= $currentLang === 'bn' ? 'আপনি কীভাবে তুলনা করেন' : 'How You Compare' ?></h3>
        <p class="chart-sub" style="margin-bottom:32px;"><?= $currentLang === 'bn' ? 'আপনার পারফরম্যান্স বনাম ঢাকার শহরের গড়' : 'Your performance vs. Dhaka\'s city average' ?></p>
        
        <div>
            <!-- CO2 -->
            <div class="comp-row">
                <div class="comp-header">
                    <span class="comp-cat"><i class="ti ti-cloud" style="color:var(--brand-primary)"></i> <?= $currentLang === 'bn' ? 'CO₂ প্রতিরোধ' : 'CO₂ Prevented' ?></span>
                    <span class="comp-badge badge" style="background:var(--success-bg); color:var(--success-text);">2.4× <?= $currentLang === 'bn' ? 'গড়ের চেয়ে বেশি' : 'ABOVE AVG' ?></span>
                </div>
                <div class="comp-bar-container">
                    <div class="comp-label-top">
                        <span><?= $currentLang === 'bn' ? 'আপনি' : 'You' ?>: 184.8 kg</span>
                        <span style="position:absolute; left:41%; margin-left:-25px; color:var(--text-muted)"><?= $currentLang === 'bn' ? 'গড়' : 'Avg' ?>: 76.2 kg</span>
                    </div>
                    <div class="comp-bar-bg">
                        <div class="comp-bar-fill" style="background:var(--brand-primary); width:100%;"></div>
                        <div class="avg-marker" style="left:41%;"></div>
                    </div>
                </div>
            </div>
            <!-- Water -->
            <div class="comp-row">
                <div class="comp-header">
                    <span class="comp-cat"><i class="ti ti-droplet" style="color:var(--blue)"></i> <?= $currentLang === 'bn' ? 'পানি সাশ্রয়' : 'Water Saved' ?></span>
                    <span class="comp-badge badge" style="background:var(--success-bg); color:var(--success-text);">1.7× <?= $currentLang === 'bn' ? 'গড়ের চেয়ে বেশি' : 'ABOVE AVG' ?></span>
                </div>
                <div class="comp-bar-container">
                    <div class="comp-label-top">
                        <span><?= $currentLang === 'bn' ? 'আপনি' : 'You' ?>: 1,516 L</span>
                        <span style="position:absolute; left:58%; margin-left:-25px; color:var(--text-muted)"><?= $currentLang === 'bn' ? 'গড়' : 'Avg' ?>: 892 L</span>
                    </div>
                    <div class="comp-bar-bg">
                        <div class="comp-bar-fill" style="background:var(--blue); width:100%;"></div>
                        <div class="avg-marker" style="left:58%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 7: Footer / Rank Banner -->
    <div class="impact-footer" id="rankBanner">
        <div id="rankSkeleton" style="display:flex; align-items:center; gap:10px; width:100%;">
            <div style="width:24px; height:24px; border-radius:50%; background:var(--border); animation:pulse 1.5s infinite;"></div>
            <div style="flex:1; height:16px; border-radius:8px; background:var(--border); animation:pulse 1.5s infinite;"></div>
        </div>
        <p class="footer-text" id="rankText" style="display:none;"></p>
        <a href="user_request_pickup.php" class="footer-cta"><?= $currentLang === 'bn' ? 'পরবর্তী পিকআপ শিডিউল করুন →' : 'Schedule Next Pickup →' ?></a>
    </div>
    <style>
        @keyframes pulse { 0%,100%{opacity:.4} 50%{opacity:1} }
        #rankBanner .footer-text { transition: all .3s ease; }
        .rank-improve { animation: rankPop .8s ease; }
        @keyframes rankPop { 0%{transform:scale(1)} 30%{transform:scale(1.12)} 60%{transform:scale(.95)} 100%{transform:scale(1)} }
        #rankBanner.hidden { display:none !important; }
    </style>

    <!-- Share Progress Container (Hidden Off-screen) -->
    <div id="shareImageContainer" style="position: absolute; top: -9999px; left: -9999px; width: 600px; background: linear-gradient(135deg, #065F46 0%, #1D9E75 100%); color: white; padding: 40px; border-radius: 24px; font-family: 'Inter', sans-serif; box-sizing: border-box;">
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 30px;">
            <div style="background: white; color: #065F46; width: 60px; height: 60px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 32px;">
                <i class="ti ti-recycle"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: 28px; font-weight: 800; font-family: 'Playfair Display', serif;"><?= $currentLang === 'bn' ? 'নতুন আলো' : 'Notun Alo' ?></h2>
                <p style="margin: 0; font-size: 14px; opacity: 0.9;"><?= $currentLang === 'bn' ? 'স্মার্ট রিসাইক্লিং সিস্টেম' : 'Smart Recycling System' ?></p>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; margin-bottom: 30px; border: 1px solid rgba(255,255,255,0.2);">
            <h3 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 600;"><?= $_SESSION['name'] ?? 'Eco Hero' ?></h3>
            <p style="margin: 0 0 24px 0; font-size: 16px; opacity: 0.9;"><?= $currentLang === 'bn' ? 'আমাদের গ্রহে একটি বাস্তব প্রভাব ফেলছে!' : 'is making a real impact on our planet!' ?></p>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 24px;">
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 4px;" id="share-co2">0<span style="font-size: 16px;">kg</span></div>
                    <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;"><?= $currentLang === 'bn' ? 'CO₂ প্রতিরোধ' : 'CO₂ Prevented' ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 4px;" id="share-water">0<span style="font-size: 16px;">L</span></div>
                    <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;"><?= $currentLang === 'bn' ? 'পানি সাশ্রয়' : 'Water Saved' ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 4px;" id="share-energy">0<span style="font-size: 16px;">kWh</span></div>
                    <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;"><?= $currentLang === 'bn' ? 'শক্তি সাশ্রয়' : 'Energy Saved' ?></div>
                </div>
            </div>
            
            <div style="text-align: center; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 24px;">
                <span style="background: #F59E0B; color: white; padding: 6px 16px; border-radius: 99px; font-weight: 700; font-size: 14px;" id="share-tier">
                    <i class="ti ti-star"></i> Bronze Tier
                </span>
            </div>
        </div>
        
        <div style="text-align: center; font-size: 14px; opacity: 0.8;">
            <?= $currentLang === 'bn' ? 'আমাদের সাথে যোগ দিন <strong>notunalo.com</strong>-এ' : 'Join the movement at <strong>notunalo.com</strong>' ?>
        </div>
    </div>

</div>

<script>
(function(){
    const root = document.getElementById('impact-root');
    const userId = root.dataset.userId;

    // 1. Fetch Data
    fetch(`api_impact.php?action=impact&user_id=${userId}`).then(r=>r.json()).then(data=>{
        if(data.error) return console.error(data.error);
        updateGamification(data.gamification);
        updateMetrics(data);
    }).catch(e => console.error(e));

    fetch(`api_impact.php?action=forecast&user_id=${userId}`).then(r=>r.json()).then(data=>{
        initForecastChart(data.forecast || []);
    }).catch(e => console.error(e));

    // 1b. Percentile Rank Banner
    let lastPercentile = null;
    const rankBanner = document.getElementById('rankBanner');
    const rankSkeleton = document.getElementById('rankSkeleton');
    const rankText = document.getElementById('rankText');
    const bn = (root.dataset.lang || 'en') === 'bn';

    function fetchPercentile() {
        fetch(`api_impact.php?action=percentile_rank&user_id=${userId}`)
            .then(r => r.json())
            .then(data => {
                rankSkeleton.style.display = 'none';
                rankText.style.display = '';
                if (data.hide || data.error) {
                    rankBanner.classList.add('hidden');
                    return;
                }
                rankBanner.classList.remove('hidden');

                if (data.oneOf && data.totalUsersInCity < 10) {
                    // Less than 10 users — show "one of N" style
                    rankText.innerHTML = bn
                        ? `🌍 <strong>${data.city}</strong>-এ <strong>${data.totalUsersInCity}</strong> জন সক্রিয় রিসাইক্লারের মধ্যে একজন!`
                        : `🌍 One of <strong>${data.totalUsersInCity}</strong> active recyclers in <strong>${data.city}</strong>!`;
                    return;
                }

                const pct = data.percentile;
                const city = data.city;

                // Special message for #1
                if (pct === 1) {
                    rankText.innerHTML = bn
                        ? `🏆 <strong>${city}</strong>-এ #১ রিসাইক্লার! দারুণ কাজ!`
                        : `🏆 <strong>#1 recycler</strong> in ${city}! Amazing work!`;
                    return;
                }

                // Build display text
                rankText.innerHTML = bn
                    ? `🌍 আপনি <strong>${city}</strong>-এর শীর্ষ <strong>${pct}%</strong> রিসাইক্লারদের মধ্যে আছেন। চালিয়ে যান!`
                    : `🌍 You're in the top <strong>${pct}%</strong> of recyclers in <strong>${city}</strong>. Keep it up!`;

                // Animate improvement
                if (lastPercentile !== null && pct < lastPercentile) {
                    rankText.classList.remove('rank-improve');
                    void rankText.offsetWidth; // reflow
                    rankText.classList.add('rank-improve');
                }
                lastPercentile = pct;
            })
            .catch(() => {
                rankSkeleton.style.display = 'none';
                rankBanner.classList.add('hidden');
            });
    }

    fetchPercentile();
    // Poll every 10 minutes
    setInterval(fetchPercentile, 600000);
    // Re-fetch on visibility change (user returns to tab)
    document.addEventListener('visibilitychange', () => { if (!document.hidden) fetchPercentile(); });

    // 2. Gamification UI
    function updateGamification(g) {
        if(!g) return;
        document.getElementById('g-lvl').textContent = g.level_number;
        document.getElementById('g-name').textContent = g.level_name + ' 🌍';
        document.getElementById('g-xp').textContent = g.xp.toLocaleString();
        document.getElementById('g-fill').style.width = g.progress_percent + '%';
        document.getElementById('g-needed').textContent = `${g.xp.toLocaleString()} / ${g.next_level_xp.toLocaleString()}`;
        document.getElementById('g-next-name').textContent = g.next_level_name + ' 🌤️';
        document.getElementById('curr-rank-label').textContent = g.level_name;
        document.getElementById('next-rank-label').textContent = g.next_level_name;
        
        const shareTier = document.getElementById('share-tier');
        if (shareTier) shareTier.innerHTML = `<i class="ti ti-star"></i> ${g.level_name}`;
    }

    // 3. Metrics Count-up
    function updateMetrics(data) {
        animateValue("m-co2", 0, data.co2_saved_kg, 1500, 1);
        animateValue("m-water", 0, data.water_saved_liters, 1500, 0);
        animateValue("m-energy", 0, data.energy_saved_kwh, 1500, 1);
        
        const co2Sub = document.getElementById('m-co2-sub');
        if(co2Sub) co2Sub.textContent = data.car_trip_equivalent.toLocaleString();
        
        const shareCo2 = document.getElementById('share-co2');
        if(shareCo2) shareCo2.innerHTML = `${data.co2_saved_kg.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1})}<span style="font-size: 16px;">kg</span>`;
        const shareWater = document.getElementById('share-water');
        if(shareWater) shareWater.innerHTML = `${data.water_saved_liters.toLocaleString()}<span style="font-size: 16px;">L</span>`;
        const shareEnergy = document.getElementById('share-energy');
        if(shareEnergy) shareEnergy.innerHTML = `${data.energy_saved_kwh.toLocaleString(undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1})}<span style="font-size: 16px;">kWh</span>`;
    }

    function animateValue(id, start, end, duration, decimals) {
        const obj = document.getElementById(id);
        if(!obj) return;
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            obj.innerHTML = (progress * (end - start) + start).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
            if (progress < 1) window.requestAnimationFrame(step);
        };
        window.requestAnimationFrame(step);
    }

    // 4. Forecast Chart
    function initForecastChart(forecast) {
        const canvas = document.getElementById('forecastChart');
        if(!canvas) return;
        const ctx = canvas.getContext('2d');
        const labels = forecast.map(f => f.month);
        
        const config = {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'CO₂ (kg)',
                        data: forecast.map(f => f.co2_saved_kg),
                        borderColor: '#1D9E75',
                        backgroundColor: 'rgba(29,158,117,0.1)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Water (L)',
                        data: forecast.map(f => f.water_saved_liters),
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37,99,235,0.05)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Energy (kWh)',
                        data: forecast.map(f => f.energy_saved_kwh),
                        borderColor: '#D97706',
                        backgroundColor: 'rgba(217,119,6,0.05)',
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { left: 10, right: 10, top: 20, bottom: 0 } },
                interaction: { mode: 'index', intersect: false },
                plugins: { 
                    legend: { 
                        display: true, 
                        position: 'top',
                        align: 'end',
                        labels: { usePointStyle: true, pointStyle: 'circle', padding: 20, font: { size: 11, weight: '500' } }
                    },
                    tooltip: {
                        backgroundColor: 'white',
                        titleColor: '#111827',
                        bodyColor: '#6B7280',
                        borderColor: '#E5E7EB',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) label += context.parsed.y.toFixed(1);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'CO₂ (kg)', color: '#1D9E75', font: { size: 11, weight: 'bold' }, padding: { bottom: 10 } },
                        grid: { color: '#F3F4F6', drawTicks: false },
                        border: { display: false },
                        ticks: { padding: 8, color: '#9CA3AF', font: { size: 10 } },
                        grace: '10%'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Water & Energy', color: '#6B7280', font: { size: 11, weight: 'bold' }, padding: { bottom: 10 } },
                        grid: { drawOnChartArea: false, drawTicks: false },
                        border: { display: false },
                        ticks: { padding: 8, color: '#9CA3AF', font: { size: 10 } },
                        grace: '10%'
                    },
                    x: { grid: { display: false }, ticks: { color: '#9CA3AF', font: { size: 10 }, padding: 10 } }
                }
            }
        };
        window.forecastChart = new Chart(ctx, config);

        // Inject Pills
        document.getElementById('forecast-details').innerHTML = forecast.map(f => `
            <div class="forecast-pill">
                <div class="f-month">${f.month}</div>
                <div class="f-vals">
                    <span style="color:#1D9E75">${f.co2_saved_kg.toFixed(1)} kg</span> · 
                    <span style="color:#2563EB">${f.water_saved_liters.toFixed(0)} L</span> · 
                    <span style="color:#D97706">${f.energy_saved_kwh.toFixed(1)} kWh</span>
                </div>
            </div>
        `).join('');
    }

    // 5. Monthly Chart
    function initMonthlyChart() {
        const canvas = document.getElementById('monthlyChart');
        if(!canvas) return;
        const ctx = canvas.getContext('2d');
        window.monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: (function() {
                    const yr = <?= date('Y') ?>;
                    return ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"].map(m => m + " " + yr);
                }()),
                datasets: [
                    { label: 'Paper', backgroundColor: '#1D9E75', data: [8,10,12,9,15,11,13,14,10,12,11,9], borderRadius: 4 },
                    { label: 'Plastic', backgroundColor: '#2563EB', data: [5,6,4,7,8,6,9,7,5,8,6,7], borderRadius: 4 },
                    { label: 'Electronics', backgroundColor: '#7C3AED', data: [2,0,3,0,4,0,2,1,0,3,0,2], borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 6, font: { size: 11 } } } },
                scales: {
                    y: { stacked: false, grid: { color: '#F3F4F6' }, border: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
    initMonthlyChart();

    // 6. Tab Logic
    window.openTab = function(evt, tabName) {
        let i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-pane");
        for (i = 0; i < tabcontent.length; i++) tabcontent[i].classList.remove("active");
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) tablinks[i].classList.remove("active");
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
        
        setTimeout(() => {
            if (window.forecastChart) window.forecastChart.resize();
            if (window.monthlyChart) window.monthlyChart.resize();
        }, 50);
    };

    // 7. Share Progress Image Generation
    window.shareProgress = function(btn) {
        const ogText = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-loader" style="display:inline-block; animation: spin 1s linear infinite;"></i> Generating...';
        
        // Add spin animation dynamically if it doesn't exist
        if (!document.getElementById('spin-keyframes')) {
            const style = document.createElement('style');
            style.id = 'spin-keyframes';
            style.innerHTML = `@keyframes spin { 100% { transform: rotate(360deg); } }`;
            document.head.appendChild(style);
        }
        
        function run() {
            const el = document.getElementById('shareImageContainer');
            // Temporarily move it to viewport to ensure proper rendering by html2canvas
            const oldTop = el.style.top;
            const oldLeft = el.style.left;
            el.style.top = '0px';
            el.style.left = '0px';
            el.style.zIndex = '-9999';
            
            html2canvas(el, { scale: 2, backgroundColor: null }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'NotunAlo_Impact.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                // Restore hidden position
                el.style.top = oldTop;
                el.style.left = oldLeft;
                btn.innerHTML = ogText;
            }).catch(e => {
                console.error(e);
                el.style.top = oldTop;
                el.style.left = oldLeft;
                btn.innerHTML = ogText;
                alert('Failed to generate image. Please try again.');
            });
        }
        
        if (typeof html2canvas === 'undefined') {
            const script = document.createElement('script');
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js";
            script.onload = run;
            document.head.appendChild(script);
        } else {
            run();
        }
    };

})();
</script>
