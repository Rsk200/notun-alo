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
        'locked_title' => 'Complete 1 pickup to unlock your 90-day AI forecast.',
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
        'locked_title' => 'আপনার ৯০-দিনের AI পূর্বাভাস আনলক করতে ১টি পিকআপ সম্পন্ন করুন।',
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
<div class="impact-content-root" id="impact-root" data-user-id="<?= $impactUserId ?>" data-lang="<?= $currentLang ?>">
    <style>
        :root {
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --brand-dark: #0A2E1E;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --white-card: #FFFFFF;
            --gold: #D97706;
            --blue: #2563EB;
        }

        .impact-content-root { font-family: 'Inter', 'Noto Sans Bengali', sans-serif; color: var(--text-secondary); }
        
        .white-card {
            background: var(--white-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 4px 12px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }

        /* Banner */
        .hero-banner { 
            background: linear-gradient(135deg, #065F46, #1D9E75);
            display: flex; 
            align-items: center; 
            gap: 20px; 
            position: relative;
            color: white;
            border: none;
        }
        .banner-icon { width: 56px; height: 56px; border-radius: 16px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; }
        .banner-text h3 { font-size: 20px; font-weight: 700; margin: 0; }
        .banner-text p { font-size: 14px; opacity: 0.85; margin: 6px 0 0 0; }
        .banner-cta { margin-left: auto; background: white; color: #065F46; border: none; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .banner-cta:hover { transform: scale(1.03); }
        .banner-close { position: absolute; top: 12px; right: 12px; background: none; border: none; color: rgba(255,255,255,0.5); cursor: pointer; font-size: 22px; }

        /* Gamification */
        .gami-card { display: flex; align-items: center; gap: 24px; border-left: 4px solid var(--gold); }
        .level-badge { width: 64px; height: 64px; border-radius: 16px; background: #FFFBEB; border: 1.5px solid var(--gold); display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0; }
        .lvl-label { font-size: 10px; font-weight: 700; color: var(--gold); text-transform: uppercase; }
        .lvl-num { font-size: 22px; font-weight: 800; color: var(--gold); }
        .rank-name { font-size: 20px; font-weight: 700; color: var(--text-primary); margin: 0; }
        .xp-bar-wrapper { margin-top: 14px; height: 8px; background: #F3F4F6; border-radius: 99px; overflow: hidden; }
        .xp-bar-fill { height: 100%; background: linear-gradient(90deg, var(--gold), #F59E0B); width: 0%; transition: width 1.4s ease; }
        .streak-pill { display: inline-block; background: #F0FDF4; color: #166534; font-size: 12px; font-weight: 600; padding: 6px 14px; border-radius: 99px; margin-top: 12px; border: 1px solid #D1FAE5; }

        /* Metrics */
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px; }
        .metric-label { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; }
        .metric-val { font-size: 32px; font-weight: 700; margin: 12px 0 6px 0; color: var(--text-primary); }
        .co2-val { color: var(--brand-primary); }
        .water-val { color: var(--blue); }
        .energy-val { color: var(--gold); }
        .metric-divider { height: 1px; background: var(--border); margin: 16px 0 12px 0; }
        .metric-equiv { font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 8px; font-weight: 500; }

        /* Fun Fact */
        .fun-fact-box { background: #F0FDF4; border: 1px solid #D1FAE5; border-radius: 16px; padding: 18px 24px; display: flex; gap: 16px; align-items: center; margin-bottom: 24px; min-height: 80px; }
        .fact-icon { color: var(--brand-primary); font-size: 24px; flex-shrink: 0; }
        .fact-content { font-size: 14px; color: var(--text-primary); line-height: 1.6; }

        /* Forecast */
        .forecast-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-radius: 12px; background: var(--bg-subtle); border: 1px solid var(--border); margin-bottom: 8px; }
        .forecast-month { font-weight: 600; color: var(--text-primary); }
        .forecast-vals { font-size: 14px; color: var(--brand-primary); font-weight: 600; }
        .locked-overlay { padding: 40px; text-align: center; background: var(--bg-subtle); border: 1px dashed var(--border); border-radius: 16px; }

        @media (max-width: 768px) { .metrics-grid { grid-template-columns: 1fr; } .hero-banner, .gami-card { flex-direction: column; text-align: center; } .banner-cta { margin: 10px 0 0 0; } }
    </style>

    <!-- 1. Hero CTA Banner -->
    <div id="hero-banner" class="white-card hero-banner" style="display: none;">
        <button class="banner-close" onclick="document.getElementById('hero-banner').remove()">×</button>
        <div class="banner-icon"><i class="ti ti-leaf"></i></div>
        <div class="banner-text">
            <h3><?= $t['hero_title'] ?></h3>
            <p><?= $t['hero_sub'] ?></p>
        </div>
        <a href="user_request_pickup.php" class="banner-cta"><?= $t['req_pickup'] ?></a>
    </div>

    <!-- 2. Gamification Card -->
    <div class="white-card gami-card">
        <div class="level-badge">
            <span class="lvl-label"><?= $t['lvl'] ?></span>
            <span class="lvl-num" id="eco-level-num">১</span>
        </div>
        <div style="flex:1">
            <h4 class="rank-name" id="eco-rank-name">...</h4>
            <div class="xp-bar-wrapper">
                <div class="xp-bar-fill" id="eco-progress-fill"></div>
            </div>
            <p style="font-size:12px; color:var(--text-muted); margin-top:8px;" id="eco-xp-label">...</p>
        </div>
        <div style="text-align:right">
            <div style="font-size:11px; font-weight:600; color:var(--gold); text-transform:uppercase;"><?= $t['next_tier'] ?></div>
            <div id="eco-next-tier" style="font-weight:600; color:var(--text-primary);">...</div>
            <div class="streak-pill" id="eco-streak"><?= $t['streak'] ?></div>
        </div>
    </div>

    <!-- 3. Metrics Grid -->
    <div class="metrics-grid">
        <div class="white-card">
            <div class="metric-label"><i class="ti ti-cloud-storm"></i> <?= $t['co2_label'] ?></div>
            <div class="metric-val co2-val" id="impact-co2">০ কেজি</div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-car-equiv"><i class="ti ti-car"></i> ...</div>
        </div>
        <div class="white-card">
            <div class="metric-label"><i class="ti ti-droplet-filled"></i> <?= $t['water_label'] ?></div>
            <div class="metric-val water-val" id="impact-water">০ লিটার</div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-water-equiv"><i class="ti ti-bottle"></i> ...</div>
        </div>
        <div class="white-card">
            <div class="metric-label"><i class="ti ti-bolt"></i> <?= $t['energy_label'] ?></div>
            <div class="metric-val energy-val" id="impact-energy">০ kWh</div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-energy-equiv"><i class="ti ti-device-mobile-charging"></i> ...</div>
        </div>
    </div>

    <!-- 4. Fun Fact -->
    <div class="fun-fact-box">
        <i class="ti ti-bulb-filled fact-icon"></i>
        <div class="fact-content" id="rotating-fact"></div>
    </div>

    <!-- 5. 90-Day Forecast -->
    <div class="white-card">
        <h3 style="font-size:20px; font-weight:700; color:var(--text-primary); margin-bottom:4px;"><?= $t['forecast_title'] ?></h3>
        <p style="font-size:14px; color:var(--text-muted); margin-bottom:20px;"><?= $t['forecast_sub'] ?></p>
        <div id="forecast-locked" class="locked-overlay" style="display: none;">
            <p style="margin-bottom:12px;"><?= $t['locked_title'] ?></p>
            <a href="user_request_pickup.php" style="background:var(--brand-primary); color:white; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:600;"><?= $t['book_pickup'] ?></a>
        </div>
        <div id="forecast-list"></div>
    </div>
</div>

<script>
(function(){
    const root = document.getElementById('impact-root');
    const userId = root.dataset.userId;
    const isBn = root.dataset.lang === 'bn';
    
    const en2bn = (n) => { if (!isBn) return n; const eng=['0','1','2','3','4','5','6','7','8','9'], bng=['০','১','২','৩','৪','৫','৬','৭','৮','৯']; return String(n).replace(/[0-9]/g, w => bng[eng.indexOf(w)]); };
    const fmt = (n, d = 0) => { let val = Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: d }); return isBn ? en2bn(val) : val; };

    const facts = [
        { en: "Mobile phone recycling has <b>29×</b> higher impact than mixed plastic.", bn: "মোবাইল ফোন রিসাইক্লিংয়ের প্রভাব প্লাস্টিকের চেয়ে <b>২৯ গুণ</b> বেশি।" },
        { en: "Recycling one aluminum can saves energy for <b>3 hours of TV</b>.", bn: "একটি ক্যান রিসাইক্লিং করলে <b>৩ ঘণ্টা টিভি</b> দেখার শক্তি সাশ্রয় হয়।" }
    ];
    let fIdx = 0;
    const showFact = () => {
        const el = document.getElementById('rotating-fact');
        const f = facts[fIdx];
        el.style.opacity = '0';
        setTimeout(() => {
            el.innerHTML = isBn ? f.bn : f.en;
            el.style.opacity = '1';
            fIdx = (fIdx + 1) % facts.length;
        }, 500);
    };
    setInterval(showFact, 8000); showFact();

    fetch(`api_impact.php?action=impact&user_id=${userId}`).then(r=>r.json()).then(data=>{
        if(data.total_pickups === 0) document.getElementById('hero-banner').style.display = 'flex';
        if(data.gamification){
            document.getElementById('eco-level-num').textContent = en2bn(data.gamification.level_number);
            document.getElementById('eco-rank-name').textContent = data.gamification.level_name;
            document.getElementById('eco-progress-fill').style.width = data.gamification.progress_percent + '%';
            document.getElementById('eco-xp-label').textContent = `${fmt(data.gamification.xp)} XP (${fmt(data.gamification.progress_percent)}%)`;
            document.getElementById('eco-next-tier').textContent = data.gamification.next_level_name;
        }
        document.getElementById('impact-co2').textContent = fmt(data.co2_saved_kg, 1) + (isBn ? ' কেজি' : ' kg');
        document.getElementById('impact-water').textContent = fmt(data.water_saved_liters, 0) + (isBn ? ' লিটার' : ' L');
        document.getElementById('impact-energy').textContent = fmt(data.energy_saved_kwh, 1) + ' kWh';
        document.getElementById('impact-car-equiv').innerHTML = `<i class="ti ti-car"></i> ${fmt(data.car_trip_equivalent)} ${isBn ? 'গাড়ির ট্রিপ এড়ানো হয়েছে' : 'car trips'}`;
        document.getElementById('impact-water-equiv').innerHTML = `<i class="ti ti-bottle"></i> ${fmt(data.water_bottle_equivalent)} ${isBn ? 'বোতল সাশ্রয়' : 'bottles'}`;
        document.getElementById('impact-energy-equiv').innerHTML = `<i class="ti ti-device-mobile-charging"></i> ${fmt(data.phone_charge_equivalent)} ${isBn ? 'ফোন চার্জ' : 'charges'}`;
    });

    fetch(`api_impact.php?action=forecast&user_id=${userId}`).then(r=>r.json()).then(data=>{
        if (data.cold_start || !data.forecast || data.forecast.length === 0) document.getElementById('forecast-locked').style.display = 'block';
        else {
            document.getElementById('forecast-list').innerHTML = data.forecast.map(item => `
                <div class="forecast-item">
                    <span class="forecast-month">${item.month}</span>
                    <span class="forecast-vals">${fmt(item.co2_saved_kg, 1)} kg CO₂</span>
                </div>
            `).join('');
        }
    });
})();
</script>
