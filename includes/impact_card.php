<?php
$impactUserId = isset($impactUserId) ? (int)$impactUserId : (int)($_SESSION['user_id'] ?? 1);
$currentLang = $_SESSION['lang'] ?? 'en';

// Page-specific bilingual dictionary
$i18n = [
    'en' => [
        'hero_title' => 'Start Your Eco-Journey',
        'hero_sub' => 'Your recycling can save 100+ kg of CO2 per year. Request your first pickup to see your impact!',
        'req_pickup' => 'Request Pickup',
        'lvl' => 'LVL',
        'rank_sub' => 'Keep recycling to unlock the next rank!',
        'xp_to_next' => 'XP (Rank Progress: %s%%)',
        'next_tier' => 'Next Tier',
        'streak' => '1 Month Streak',
        'co2_label' => 'CO₂ PREVENTED',
        'water_label' => 'WATER SAVED',
        'energy_label' => 'ENERGY SAVED',
        'co2_sub' => 'Total emissions avoided',
        'water_sub' => 'Clean water preserved',
        'energy_sub' => 'Total power preserved',
        'forecast_title' => '90-Day AI Forecast',
        'forecast_sub' => 'Projected impact based on your recycling trends.',
        'locked_title' => 'Complete 1 pickup to unlock<br>your 90-day AI forecast.',
        'book_pickup' => 'Book a Pickup',
        'trend_title' => 'Monthly Impact Trend',
        'trend_sub' => 'Breakdown of CO₂ saved by category each month.',
        'trend_empty' => 'Your impact chart is waiting',
        'trend_steps' => 'How to get started in 3 easy steps:',
        'step1' => 'Request a recycling pickup from your dashboard',
        'step2' => 'Wait for our agent to collect and weigh items',
        'step3' => 'Watch your impact grow with every completion!',
    ],
    'bn' => [
        'hero_title' => 'আপনার পরিবেশবান্ধব যাত্রা শুরু করুন',
        'hero_sub' => 'আপনার রিসাইক্লিং বছরে ১০০+ কেজি CO2 সাশ্রয় করতে পারে। আপনার প্রভাব দেখতে প্রথম পিকআপ অনুরোধ করুন!',
        'req_pickup' => 'পিকআপ অনুরোধ করুন',
        'lvl' => 'লেভেল',
        'rank_sub' => 'পরবর্তী র‍্যাঙ্ক আনলক করতে রিসাইক্লিং চালিয়ে যান!',
        'xp_to_next' => 'XP (অগ্রগতি: %s%%)',
        'next_tier' => 'পরবর্তী ধাপ',
        'streak' => '১ মাসের ধারাবাহিকতা',
        'co2_label' => 'প্রতিরোধকৃত CO₂',
        'water_label' => 'সাশ্রয়কৃত পানি',
        'energy_label' => 'সাশ্রয়কৃত শক্তি',
        'co2_sub' => 'মোট নির্গমন এড়ানো হয়েছে',
        'water_sub' => 'বিশুদ্ধ পানি সংরক্ষিত',
        'energy_sub' => 'মোট বিদ্যুৎ সংরক্ষিত',
        'forecast_title' => '৯০-দিনের AI পূর্বাভাস',
        'forecast_sub' => 'আপনার রিসাইক্লিং প্রবণতার উপর ভিত্তি করে সম্ভাব্য প্রভাব।',
        'locked_title' => 'আপনার ৯০-দিনের AI পূর্বাভাস আনলক করতে<br>১টি পিকআপ সম্পন্ন করুন।',
        'book_pickup' => 'পিকআপ বুক করুন',
        'trend_title' => 'মাসিক প্রভাবের প্রবণতা',
        'trend_sub' => 'প্রতি মাসে ক্যাটাগরি অনুযায়ী সাশ্রয়কৃত CO₂ এর বিভাজন।',
        'trend_empty' => 'আপনার প্রভাব চার্ট অপেক্ষা করছে',
        'trend_steps' => 'কিভাবে শুরু করবেন ৩টি সহজ ধাপে:',
        'step1' => 'আপনার ড্যাশবোর্ড থেকে একটি পিকআপ অনুরোধ করুন',
        'step2' => 'আমাদের এজেন্টের সংগ্রহ এবং ওজন করার জন্য অপেক্ষা করুন',
        'step3' => 'প্রতিটি কাজ সম্পন্ন হওয়ার সাথে সাথে আপনার প্রভাব বাড়তে দেখুন!',
    ]
];

$t = $i18n[$currentLang];
?>
<div class="impact-container" id="impact-root" data-user-id="<?= $impactUserId ?>" data-lang="<?= $currentLang ?>">
    <style>
        :root {
            --green-primary: #1D9E75;
            --green-dark: #065F46;
            --green-light: #E6F5EE;
            --blue-primary: #2563EB;
            --amber-primary: #D97706;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --border-default: #E5E7EB;
            --bg-page: #F5F7F2;
            --bg-card: #FFFFFF;
            --bg-subtle: #F9FAFB;
        }

        .impact-container { font-family: 'DM Sans', 'Noto Sans Bengali', sans-serif; color: var(--text-primary); }
        
        /* Banner */
        .hero-banner { 
            background: linear-gradient(135deg, #E6F5EE, #D1FAE5); 
            border: 1px solid #6EE7B7; 
            border-radius: 12px; 
            padding: 20px 24px; 
            display: flex; 
            align-items: center; 
            gap: 16px; 
            margin-bottom: 24px; 
            position: relative;
        }
        .banner-icon { width: 48px; height: 48px; border-radius: 50%; background: var(--green-primary); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
        .banner-text h3 { font-size: 16px; font-weight: 600; color: var(--green-dark); margin: 0; }
        .banner-text p { font-size: 13px; color: #047857; margin: 4px 0 0 0; }
        .banner-cta { margin-left: auto; background: var(--green-primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .banner-close { position: absolute; top: 12px; right: 12px; background: none; border: none; color: var(--text-secondary); cursor: pointer; font-size: 18px; }

        /* Gamification Card */
        .gami-card { background: var(--bg-card); border: 1px solid var(--border-default); border-radius: 12px; padding: 20px 24px; display: flex; align-items: center; gap: 20px; margin-bottom: 24px; }
        .level-badge { width: 56px; height: 56px; border-radius: 50%; background: var(--green-light); border: 2px solid var(--green-primary); display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; }
        .lvl-label { font-size: 9px; font-weight: 500; color: var(--green-dark); line-height: 1; text-transform: uppercase; }
        .lvl-num { font-size: 18px; font-weight: 700; color: var(--green-primary); line-height: 1.2; }
        .gami-info { flex: 1; }
        .rank-name { font-size: 16px; font-weight: 600; color: var(--text-primary); margin: 0; }
        .rank-sub { font-size: 13px; color: var(--text-secondary); margin-top: 2px; }
        .xp-bar-wrapper { margin-top: 10px; height: 8px; background: var(--border-default); border-radius: 99px; overflow: hidden; }
        .xp-bar-fill { height: 100%; background: linear-gradient(90deg, var(--green-primary), #34D399); border-radius: 99px; width: 0%; transition: width 1.2s cubic-bezier(0.4, 0, 0.2, 1); }
        .xp-label { font-size: 11px; color: var(--text-muted); margin-top: 5px; }
        .gami-right { text-align: right; }
        .next-tier-label { font-size: 11px; font-weight: 500; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; }
        .next-tier-name { font-size: 14px; font-weight: 500; color: var(--green-primary); margin-top: 4px; }
        .streak-pill { display: inline-block; background: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 500; padding: 4px 10px; border-radius: 99px; margin-top: 8px; }

        /* Metrics Grid */
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .metric-card { background: var(--bg-card); border: 1px solid var(--border-default); border-radius: 12px; padding: 20px; }
        .metric-label { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 500; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; }
        .metric-label i { font-size: 16px; }
        .metric-val { font-size: 32px; font-weight: 700; margin: 10px 0 4px 0; }
        .co2-val { color: var(--green-primary); }
        .water-val { color: var(--blue-primary); }
        .energy-val { color: var(--amber-primary); }
        .metric-sub { font-size: 12px; color: var(--text-muted); }
        .metric-divider { height: 1px; background: #F3F4F6; margin: 14px 0 10px 0; }
        .metric-equiv { font-size: 12px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px; }

        /* Fun Fact */
        .fun-fact-box { background: #F0FDF4; border-left: 4px solid var(--green-primary); border-radius: 0 8px 8px 0; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 24px; min-height: 70px; }
        .fact-icon { color: var(--green-primary); font-size: 20px; flex-shrink: 0; margin-top: 1px; }
        .fact-content { font-size: 13px; color: var(--green-dark); line-height: 1.6; transition: opacity 0.8s ease; }
        .fact-lang-divider { margin: 4px 0; border-top: 1px dashed rgba(29, 158, 117, 0.2); }
        .fact-bn { font-family: 'Noto Sans Bengali', sans-serif; font-weight: 500; }

        /* Forecast Card */
        .forecast-card { background: var(--bg-card); border: 1px solid var(--border-default); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; }
        .card-header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
        .card-title-text { font-size: 16px; font-weight: 600; color: var(--text-primary); }
        .live-pill { background: #DCFCE7; color: #166534; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 500; }
        .card-subtitle-text { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
        
        .locked-overlay { height: 160px; background: var(--bg-subtle); border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center; }
        .lock-icon { font-size: 32px; color: #D1D5DB; }
        .lock-text { font-size: 14px; color: var(--text-secondary); }
        .unlock-btn { background: white; border: 1px solid #D1D5DB; border-radius: 8px; padding: 8px 16px; font-size: 13px; cursor: pointer; transition: background 0.3s; }
        .unlock-btn:hover { background: var(--bg-subtle); }
        
        .forecast-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
        .forecast-item { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #F3F4F6; }
        .forecast-item:last-child { border-bottom: none; }
        .forecast-month { font-weight: 600; font-size: 14px; color: var(--text-primary); }
        .forecast-vals { font-size: 13px; color: var(--text-secondary); }

        /* Monthly Card */
        .monthly-card { background: var(--bg-card); border: 1px solid var(--border-default); border-radius: 12px; padding: 20px 24px; }
        .monthly-empty { padding: 32px 0; text-align: center; }
        .empty-icon-lg { font-size: 40px; color: var(--border-default); margin-bottom: 12px; }
        .empty-headline { font-size: 15px; font-weight: 500; color: #374151; }
        .empty-subtext { font-size: 13px; color: var(--text-muted); margin: 4px 0 20px 0; }
        .steps-list { max-width: 320px; margin: 0 auto; text-align: left; display: flex; flex-direction: column; gap: 12px; }
        .step-item { display: flex; gap: 12px; align-items: center; }
        .step-num { width: 28px; height: 28px; border: 1.5px solid var(--border-default); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--text-muted); flex-shrink: 0; }
        .step-txt { font-size: 13px; color: #374151; }

        @media (max-width: 768px) {
            .metrics-grid { grid-template-columns: 1fr; }
            .gami-card { flex-direction: column; text-align: center; }
            .gami-right { text-align: center; }
            .hero-banner { flex-direction: column; text-align: center; }
            .banner-cta { margin: 0; }
        }
    </style>

    <!-- 1. Hero CTA Banner -->
    <div id="hero-banner" class="hero-banner" style="display: none;">
        <button class="banner-close" onclick="document.getElementById('hero-banner').remove()">×</button>
        <div class="banner-icon"><i class="fa-solid fa-leaf"></i></div>
        <div class="banner-text">
            <h3><?= $t['hero_title'] ?></h3>
            <p><?= $t['hero_sub'] ?></p>
        </div>
        <a href="user_request_pickup.php" class="banner-cta"><?= $t['req_pickup'] ?></a>
    </div>

    <!-- 2. Gamification Card -->
    <div class="gami-card">
        <div class="level-badge">
            <span class="lvl-label"><?= $t['lvl'] ?></span>
            <span class="lvl-num" id="eco-level-num">১</span>
        </div>
        <div class="gami-info">
            <h4 class="rank-name" id="eco-rank-name">...</h4>
            <p class="rank-sub"><?= $t['rank_sub'] ?></p>
            <div class="xp-bar-wrapper">
                <div class="xp-bar-fill" id="eco-progress-fill"></div>
            </div>
            <p class="xp-label" id="eco-xp-label">... XP (<?= sprintf($t['xp_to_next'], '০') ?>)</p>
        </div>
        <div class="gami-right">
            <div class="next-tier-label"><?= $t['next_tier'] ?></div>
            <div class="next-tier-name" id="eco-next-tier">...</div>
            <div class="streak-pill" id="eco-streak"><?= $t['streak'] ?></div>
        </div>
    </div>

    <!-- 3. Metrics Grid -->
    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label"><i class="fa-solid fa-cloud"></i> <?= $t['co2_label'] ?></div>
            <div class="metric-val co2-val" id="impact-co2">০ কেজি</div>
            <div class="metric-sub"><?= $t['co2_sub'] ?></div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-car-equiv"><i class="fa-solid fa-car"></i> ০ car trips avoided</div>
        </div>
        <div class="metric-card">
            <div class="metric-label"><i class="fa-solid fa-droplet"></i> <?= $t['water_label'] ?></div>
            <div class="metric-val water-val" id="impact-water">০ লিটার</div>
            <div class="metric-sub"><?= $t['water_sub'] ?></div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-water-equiv"><i class="fa-solid fa-bottle-water"></i> ০ bottles saved</div>
        </div>
        <div class="metric-card">
            <div class="metric-label"><i class="fa-solid fa-bolt"></i> <?= $t['energy_label'] ?></div>
            <div class="metric-val energy-val" id="impact-energy">০ kWh</div>
            <div class="metric-sub"><?= $t['energy_sub'] ?></div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-energy-equiv"><i class="fa-solid fa-plug"></i> ০ phone charges</div>
        </div>
    </div>

    <!-- 4. Fun Fact Callout (Bilingual) -->
    <div class="fun-fact-box">
        <i class="fa-solid fa-lightbulb fact-icon"></i>
        <div class="fact-content" id="rotating-fact" style="opacity: 0;">
            <!-- Content injected by JS after 3s -->
        </div>
    </div>

    <!-- 5. 90-Day Forecast -->
    <div class="forecast-card">
        <div class="card-header-row">
            <div class="card-title-text"><?= $t['forecast_title'] ?></div>
            <div class="live-pill">LIVE</div>
        </div>
        <p class="card-subtitle-text"><?= $t['forecast_sub'] ?></p>
        
        <div id="forecast-locked" class="locked-overlay" style="display: none;">
            <i class="fa-solid fa-lock lock-icon"></i>
            <p class="lock-text"><?= $t['locked_title'] ?></p>
            <a href="user_request_pickup.php" class="unlock-btn"><?= $t['book_pickup'] ?></a>
        </div>
        
        <div id="forecast-list" class="forecast-list"></div>
    </div>

    <!-- 6. Monthly Impact -->
    <div class="monthly-card">
        <div class="card-header-row">
            <div class="card-title-text"><?= $t['trend_title'] ?></div>
        </div>
        <p class="card-subtitle-text"><?= $t['trend_sub'] ?></p>
        
        <div id="monthly-empty" class="monthly-empty" style="display: none;">
            <i class="fa-solid fa-chart-line empty-icon-lg"></i>
            <div class="empty-headline"><?= $t['trend_empty'] ?></div>
            <p class="empty-subtext"><?= $t['trend_steps'] ?></p>
            <div class="steps-list">
                <div class="step-item"><div class="step-num">১</div><div class="step-txt"><?= $t['step1'] ?></div></div>
                <div class="step-item"><div class="step-num">২</div><div class="step-txt"><?= $t['step2'] ?></div></div>
                <div class="step-item"><div class="step-num">৩</div><div class="step-txt"><?= $t['step3'] ?></div></div>
            </div>
        </div>
        <div id="monthly-chart-container" style="height: 300px; display: none;"><canvas id="monthlyImpactChart"></canvas></div>
    </div>
</div>

<script>
(function(){
    const root = document.getElementById('impact-root');
    const userId = root.dataset.userId;
    const isBn = root.dataset.lang === 'bn';
    
    const en2bn = (n) => {
        if (!isBn) return n;
        const eng = ['0','1','2','3','4','5','6','7','8','9'];
        const bng = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        return String(n).replace(/[0-9]/g, w => bng[eng.indexOf(w)]);
    };
    const fmt = (n, d = 0) => {
        let val = Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: d });
        return isBn ? en2bn(val) : val;
    };

    // Bilingual Rotating Facts
    const facts = [
        {
            en: "Did you know? Mobile phone recycling has <b>29×</b> higher environmental impact than mixed plastic recycling.",
            bn: "আপনি কি জানেন? মিশ্র প্লাস্টিকের তুলনায় মোবাইল ফোন রিসাইক্লিংয়ের পরিবেশগত প্রভাব প্রায় <b>২৯ গুণ</b> বেশি।"
        },
        {
            en: "Recycling a single aluminum can saves enough energy to run a <b>TV for 3 hours</b>.",
            bn: "একটি অ্যালুমিনিয়াম ক্যান রিসাইক্লিং করলে যে শক্তি সাশ্রয় হয় তা দিয়ে একটি <b>টিভি ৩ ঘণ্টা</b> চালানো সম্ভব।"
        },
        {
            en: "Producing paper from recycled fibers uses <b>40% less energy</b> than virgin wood.",
            bn: "ভার্জিন কাঠের তুলনায় রিসাইক্লিং করা ফাইবার থেকে কাগজ তৈরি করলে <b>৪০% কম শক্তি</b> ব্যয় হয়।"
        },
        {
            en: "A single recycled glass bottle saves enough energy to power a <b>laptop for 25 minutes</b>.",
            bn: "একটি কাঁচের বোতল রিসাইক্লিং করলে একটি <b>ল্যাপটপ ২৫ মিনিট</b> চালানোর মতো শক্তি সাশ্রয় হয়।"
        }
    ];
    let factIdx = 0;
    
    const showFact = () => {
        const el = document.getElementById('rotating-fact');
        const f = facts[factIdx];
        el.style.opacity = '0';
        setTimeout(() => {
            el.innerHTML = `
                <div class="fact-en">${f.en}</div>
                <div class="fact-lang-divider"></div>
                <div class="fact-bn">${f.bn}</div>
            `;
            el.style.opacity = '1';
            factIdx = (factIdx + 1) % facts.length;
        }, 800);
    };

    // Start rotation after 3 seconds
    setTimeout(() => {
        showFact();
        setInterval(showFact, 8000);
    }, 3000);

    // Fetch Data
    fetch(`api_impact.php?action=impact&user_id=${userId}`)
    .then(r => r.json())
    .then(data => {
        if(data.error) return;
        if (data.total_pickups === 0) document.getElementById('hero-banner').style.display = 'flex';
        
        if (data.gamification) {
            document.getElementById('eco-level-num').textContent = en2bn(data.gamification.level_number);
            document.getElementById('eco-rank-name').textContent = isBn ? translateRank(data.gamification.level_name) : data.gamification.level_name;
            document.getElementById('eco-progress-fill').style.width = data.gamification.progress_percent + '%';
            document.getElementById('eco-xp-label').textContent = `${fmt(data.gamification.xp)} XP (${isBn ? 'অগ্রগতি' : 'Rank Progress'}: ${fmt(data.gamification.progress_percent)}%)`;
            document.getElementById('eco-next-tier').textContent = isBn ? translateRank(data.gamification.next_level_name) : data.gamification.next_level_name;
            document.getElementById('eco-streak').textContent = isBn ? en2bn(data.gamification.streak_msg || '১ মাসের ধারাবাহিকতা') : (data.gamification.streak_msg || '1 Month Streak');
        }

        document.getElementById('impact-co2').textContent = fmt(data.co2_saved_kg, 1) + (isBn ? ' কেজি' : ' kg');
        document.getElementById('impact-water').textContent = fmt(data.water_saved_liters, 0) + (isBn ? ' লিটার' : ' L');
        document.getElementById('impact-energy').textContent = fmt(data.energy_saved_kwh, 1) + ' kWh';
        
        document.getElementById('impact-car-equiv').innerHTML = `<i class="fa-solid fa-car"></i> ${fmt(data.car_trip_equivalent)} ${isBn ? 'গাড়ির ট্রিপ এড়ানো হয়েছে' : 'car trips avoided'}`;
        document.getElementById('impact-water-equiv').innerHTML = `<i class="fa-solid fa-bottle-water"></i> ${fmt(data.water_bottle_equivalent)} ${isBn ? 'বোতল সাশ্রয়' : 'bottles saved'}`;
        document.getElementById('impact-energy-equiv').innerHTML = `<i class="fa-solid fa-plug"></i> ${fmt(data.phone_charge_equivalent)} ${isBn ? 'ফোন চার্জ' : 'phone charges'}`;
    });

    fetch(`api_impact.php?action=forecast&user_id=${userId}`)
    .then(r => r.json())
    .then(data => {
        if (data.cold_start || !data.forecast || data.forecast.length === 0) {
            document.getElementById('forecast-locked').style.display = 'flex';
            document.getElementById('forecast-list').style.display = 'none';
        } else {
            const list = document.getElementById('forecast-list');
            list.innerHTML = data.forecast.map(item => `
                <div class="forecast-item">
                    <span class="forecast-month">${isBn ? translateMonth(item.month) : item.month}</span>
                    <span class="forecast-vals">${fmt(item.co2_saved_kg, 1)} kg CO₂, ${fmt(item.energy_saved_kwh, 1)} kWh</span>
                </div>
            `).join('');
        }
    });

    function translateRank(name) {
        const ranks = {
            'Eco-Seed': 'পরিবেশ-বীজ', 'Eco-Sprout': 'পরিবেশ-অঙ্কুর', 'Eco-Sapling': 'পরিবেশ-চারা', 
            'Eco-Tree': 'পরিবেশ-বৃক্ষ', 'Eco-Forest': 'পরিবেশ-বন', 'Eco-Guardian': 'পরিবেশ-রক্ষক',
            'Earth Hero': 'পৃথিবীর বীর', 'Climate Commander': 'জলবায়ু সেনাপতি', 
            'Atmosphere Architect': 'বায়ুমণ্ডল স্থপতি', 'Planet Savior': 'গ্রহ রক্ষাকর্তা', 'Max Level': 'সর্বোচ্চ পর্যায়'
        };
        return ranks[name] || name;
    }

    function translateMonth(m) {
        if (!m) return m;
        const [y, mm] = m.split('-');
        const months = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
        return en2bn(y) + '-' + months[parseInt(mm)-1];
    }

    fetch(`api_impact.php?action=impact&user_id=${userId}`).then(r=>r.json()).then(data=>{
        if (data.total_pickups === 0) document.getElementById('monthly-empty').style.display = 'block';
    });
})();
</script>

