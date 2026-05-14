<?php
$impactUserId = isset($impactUserId) ? (int)$impactUserId : (int)($_SESSION['user_id'] ?? 1);
?>
<div class="impact-container" id="impact-root" data-user-id="<?= $impactUserId ?>">
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

        .impact-container { font-family: 'DM Sans', sans-serif; color: var(--text-primary); }
        
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
        .lvl-label { font-size: 10px; font-weight: 500; color: var(--green-dark); line-height: 1; }
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
        .fun-fact-box { background: #F0FDF4; border-left: 4px solid var(--green-primary); border-radius: 0 8px 8px 0; padding: 14px 18px; display: flex; gap: 12px; align-items: flex-start; margin-bottom: 24px; }
        .fact-icon { color: var(--green-primary); font-size: 20px; flex-shrink: 0; margin-top: 1px; }
        .fact-content { font-size: 13px; color: var(--green-dark); line-height: 1.6; }
        .fact-content b { font-weight: 700; }
        .fact-item { transition: opacity 0.4s ease; }

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

    <!-- 1. Hero CTA Banner (Conditional) -->
    <div id="hero-banner" class="hero-banner" style="display: none;">
        <button class="banner-close" onclick="document.getElementById('hero-banner').remove()">×</button>
        <div class="banner-icon"><i class="fa-solid fa-leaf"></i></div>
        <div class="banner-text">
            <h3>Start Your Eco-Journey</h3>
            <p>Your recycling can save 100+ kg of CO2 per year. Request your first pickup to see your impact!</p>
        </div>
        <a href="user_request_pickup.php" class="banner-cta">Request Pickup</a>
    </div>

    <!-- 2. Gamification Card -->
    <div class="gami-card">
        <div class="level-badge">
            <span class="lvl-label">LVL</span>
            <span class="lvl-num" id="eco-level-num">1</span>
        </div>
        <div class="gami-info">
            <h4 class="rank-name" id="eco-rank-name">Eco-Seed</h4>
            <p class="rank-sub">Keep recycling to unlock the next rank!</p>
            <div class="xp-bar-wrapper">
                <div class="xp-bar-fill" id="eco-progress-fill"></div>
            </div>
            <p class="xp-label" id="eco-xp-label">0 / 100 XP to next rank</p>
        </div>
        <div class="gami-right">
            <div class="next-tier-label">Next Tier</div>
            <div class="next-tier-name" id="eco-next-tier">Eco-Sprout</div>
            <div class="streak-badge" id="eco-streak">1 Month Streak</div>
        </div>
    </div>

    <!-- 3. Metrics Grid -->
    <div class="metrics-grid">
        <!-- CO2 Card -->
        <div class="metric-card">
            <div class="metric-label"><i class="fa-solid fa-cloud"></i> CO₂ PREVENTED</div>
            <div class="metric-val co2-val" id="impact-co2">0 kg</div>
            <div class="metric-sub">Total emissions avoided</div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-car-equiv"><i class="fa-solid fa-car"></i> 0 car trips avoided</div>
        </div>
        <!-- Water Card -->
        <div class="metric-card">
            <div class="metric-label"><i class="fa-solid fa-droplet"></i> WATER SAVED</div>
            <div class="metric-val water-val" id="impact-water">0 L</div>
            <div class="metric-sub">Clean water preserved</div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-water-equiv"><i class="fa-solid fa-bottle-water"></i> 0 bottles saved</div>
        </div>
        <!-- Energy Card -->
        <div class="metric-card">
            <div class="metric-label"><i class="fa-solid fa-bolt"></i> ENERGY SAVED</div>
            <div class="metric-val energy-val" id="impact-energy">0 kWh</div>
            <div class="metric-sub">Total power preserved</div>
            <div class="metric-divider"></div>
            <div class="metric-equiv" id="impact-energy-equiv"><i class="fa-solid fa-plug"></i> 0 phone charges</div>
        </div>
    </div>

    <!-- 4. Fun Fact Callout -->
    <div class="fun-fact-box">
        <i class="fa-solid fa-lightbulb fact-icon"></i>
        <div class="fact-content" id="rotating-fact">
            Did you know? Mobile phone recycling has <b>29×</b> higher environmental impact than mixed plastic recycling.
        </div>
    </div>

    <!-- 5. 90-Day Forecast -->
    <div class="forecast-card">
        <div class="card-header-row">
            <div class="card-title-text">90-Day AI Forecast</div>
            <div class="live-pill">LIVE</div>
        </div>
        <p class="card-subtitle-text">Projected impact based on your recycling trends.</p>
        
        <div id="forecast-locked" class="locked-overlay" style="display: none;">
            <i class="fa-solid fa-lock lock-icon"></i>
            <p class="lock-text">Complete 1 pickup to unlock<br>your 90-day AI forecast.</p>
            <a href="user_request_pickup.php" class="unlock-btn">Book a Pickup</a>
        </div>
        
        <div id="forecast-list" class="forecast-list">
            <!-- Items injected by JS -->
        </div>
    </div>

    <!-- 6. Monthly Impact -->
    <div class="monthly-card">
        <div class="card-header-row">
            <div class="card-title-text">Monthly Impact Trend</div>
        </div>
        <p class="card-subtitle-text">Breakdown of CO₂ saved by category each month.</p>
        
        <div id="monthly-empty" class="monthly-empty" style="display: none;">
            <i class="fa-solid fa-chart-line empty-icon-lg"></i>
            <div class="empty-headline">Your impact chart is waiting</div>
            <p class="empty-subtext">How to get started in 3 easy steps:</p>
            <div class="steps-list">
                <div class="step-item">
                    <div class="step-num">1</div>
                    <div class="step-txt">Request a recycling pickup from your dashboard</div>
                </div>
                <div class="step-item">
                    <div class="step-num">2</div>
                    <div class="step-txt">Wait for our agent to collect and weigh items</div>
                </div>
                <div class="step-item">
                    <div class="step-num">3</div>
                    <div class="step-txt">Watch your impact grow with every completion!</div>
                </div>
            </div>
        </div>

        <div id="monthly-chart-container" style="height: 300px; display: none;">
            <canvas id="monthlyImpactChart"></canvas>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const root = document.getElementById('impact-root');
    const userId = root.dataset.userId;
    const fmt = (n, d = 0) => Number(n || 0).toLocaleString(undefined, { maximumFractionDigits: d });
    
    // Rotating Facts
    const facts = [
        "Did you know? Mobile phone recycling has <b>29×</b> higher environmental impact than mixed plastic recycling.",
        "Recycling a single aluminum can saves enough energy to run a <b>TV for 3 hours</b>.",
        "Producing paper from recycled fibers uses <b>40% less energy</b> than virgin wood.",
        "A single recycled glass bottle saves enough energy to power a <b>laptop for 25 minutes</b>."
    ];
    let factIdx = 0;
    setInterval(() => {
        const el = document.getElementById('rotating-fact');
        el.style.opacity = '0';
        setTimeout(() => {
            factIdx = (factIdx + 1) % facts.length;
            el.innerHTML = facts[factIdx];
            el.style.opacity = '1';
        }, 400);
    }, 8000);

    // Fetch Impact Data
    fetch(`api_impact.php?action=impact&user_id=${userId}`)
    .then(r => r.json())
    .then(data => {
        if(data.error) return;

        // Show Hero Banner if no pickups
        if (data.total_pickups === 0) {
            document.getElementById('hero-banner').style.display = 'flex';
        }

        // Gamification
        if (data.gamification) {
            document.getElementById('eco-level-num').textContent = data.gamification.level_number;
            document.getElementById('eco-rank-name').textContent = data.gamification.level_name;
            document.getElementById('eco-progress-fill').style.width = data.gamification.progress_percent + '%';
            document.getElementById('eco-xp-label').textContent = `${fmt(data.gamification.xp)} XP (Rank Progress: ${data.gamification.progress_percent}%)`;
            document.getElementById('eco-next-tier').textContent = data.gamification.next_level_name;
            document.getElementById('eco-next-tier').nextElementSibling.textContent = data.gamification.next_rank_msg;
        }

        // Metrics
        document.getElementById('impact-co2').textContent = fmt(data.co2_saved_kg, 1) + ' kg';
        document.getElementById('impact-water').textContent = fmt(data.water_saved_liters, 0) + ' L';
        document.getElementById('impact-energy').textContent = fmt(data.energy_saved_kwh, 1) + ' kWh';
        
        document.getElementById('impact-car-equiv').innerHTML = `<i class="fa-solid fa-car"></i> ${fmt(data.car_trip_equivalent)} car trips avoided`;
        document.getElementById('impact-water-equiv').innerHTML = `<i class="fa-solid fa-bottle-water"></i> ${fmt(data.water_bottle_equivalent)} bottles saved`;
        document.getElementById('impact-energy-equiv').innerHTML = `<i class="fa-solid fa-plug"></i> ${fmt(data.phone_charge_equivalent)} phone charges`;
    });

    // Fetch Forecast
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
                    <span class="forecast-month">${item.month}</span>
                    <span class="forecast-vals">${fmt(item.co2_saved_kg, 1)} kg CO₂, ${fmt(item.energy_saved_kwh, 1)} kWh</span>
                </div>
            `).join('');
        }
    });

    // Fetch Monthly Trend
    // We'll use the same API for now, or just handle empty state if needed.
    // Assuming Monthly Impact logic is integrated into api_impact.php or handled here.
    // For now, let's just show the empty state if impact is 0.
    fetch(`api_impact.php?action=impact&user_id=${userId}`)
    .then(r => r.json())
    .then(data => {
        if (data.total_pickups === 0) {
            document.getElementById('monthly-empty').style.display = 'block';
            document.getElementById('monthly-chart-container').style.display = 'none';
        } else {
            // Ideally call a monthly history API here. For now, we'll keep the empty state logic simple.
        }
    });

})();
</script>
