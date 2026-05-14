<?php
$impactUserId = isset($impactUserId) ? (int)$impactUserId : (int)($_SESSION['user_id'] ?? 1);
?>
<section class="impact-dashboard" data-user-id="<?= $impactUserId ?>">
    <style>
        .impact-dashboard{margin:2rem 0}.impact-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem}.impact-card{background:#fff;border:1px solid #dfeee3;border-radius:8px;padding:1.1rem;box-shadow:0 8px 20px rgba(27,94,32,.08)}.impact-card strong{display:block;font-size:1.8rem;color:#1b5e20;margin:.25rem 0}.impact-card small{color:#607d68}.impact-card.energy strong{color:#b7791f}.impact-card.water strong{color:#0277bd}.impact-story{margin-top:1rem;background:#ecfdf3;border-left:5px solid #1b5e20;border-radius:8px;padding:1rem;color:#1f3d2b}.impact-forecast{margin-top:1rem;background:#fff;border:1px solid #dfeee3;border-radius:8px;padding:1rem}.impact-forecast ul{margin:.5rem 0 0;padding-left:1.2rem}.impact-badge{display:inline-block;background:#fff3cd;color:#7a4b00;border:1px solid #ffd66b;border-radius:999px;padding:.25rem .6rem;font-weight:800;font-size:.8rem;margin-top:.5rem}@media(max-width:800px){.impact-grid{grid-template-columns:1fr}}
        /* Dark Mode */
        body.dark-mode .impact-card { background: #1e1e1e; border-color: #333; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        body.dark-mode .impact-card strong { color: #81c784; }
        body.dark-mode .impact-card.energy strong { color: #ffd54f; }
        body.dark-mode .impact-card.water strong { color: #4fc3f7; }
        body.dark-mode .impact-card small { color: #aaa; }
        body.dark-mode .impact-story { background: rgba(27,94,32,0.15); border-left-color: #66bb6a; color: #e0e0e0; }
        body.dark-mode .impact-forecast { background: #1e1e1e; border-color: #333; color: #e0e0e0; }
        body.dark-mode .impact-dashboard h2 { color: #81c784 !important; }
        body.dark-mode .impact-dashboard p { color: #aaa !important; }
        body.dark-mode .impact-badge { background: rgba(255, 214, 107, 0.15); color: #ffd66b; border-color: rgba(255, 214, 107, 0.3); }
    </style>
    <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
        <div>
            <h2 style="margin:0;color:#1b5e20;font-size:1.45rem;"><?= $lang['impact_intelligence'] ?? 'Environmental Impact Intelligence' ?></h2>
            <p style="margin:.25rem 0 0;color:#667085;"><?= $lang['impact_desc'] ?? 'Your recycling translated into climate, water, and energy savings.' ?></p>
        </div>
        <span id="impact-status" style="font-size:.9rem;color:#667085;">Loading...</span>
    </div>
    <div class="impact-grid">
        <div class="impact-card"><small><?= $lang['co2_prevented'] ?? 'CO2 prevented' ?></small><strong id="impact-co2">0 kg</strong><span id="impact-car">0 <?= $lang['car_trips_avoided'] ?? 'car trips avoided' ?></span></div>
        <div class="impact-card water"><small><?= $lang['water_saved'] ?? 'Water saved' ?></small><strong id="impact-water">0 L</strong><span id="impact-bottles">0 <?= $lang['bottles_saved'] ?? 'bottles saved' ?></span></div>
        <div class="impact-card energy"><small><?= $lang['energy_saved'] ?? 'Energy saved' ?></small><strong id="impact-energy">0 kWh</strong><span id="impact-phone">0 <?= $lang['phone_charges'] ?? 'phone charges' ?></span></div>
    </div>
    <div class="impact-story" id="impact-story"><?= $lang['story_default'] ?? 'Every completed pickup will appear here as a clear environmental story.' ?></div>
    <div class="impact-forecast"><strong><?= $lang['forecast_90_days'] ?? '90-day forecast' ?></strong><ul id="impact-forecast-list"><li><?= $lang['loading_forecast'] ?? 'Loading forecast...' ?></li></ul></div>
</section>
<script>
(function(){
  const root=document.currentScript.previousElementSibling; const userId=root.dataset.userId; const api='api_impact.php';
  const fmt=(n,d=0)=>Number(n||0).toLocaleString(undefined,{maximumFractionDigits:d});
  function setText(id,text){const el=document.getElementById(id); if(el) el.textContent=text;}
    fetch(`${api}?action=impact&user_id=${userId}`).then(r=>r.json()).then(data=>{
    if(data.error) throw new Error(data.error + (data.message ? ': ' + data.message : ''));
    setText('impact-status','<?= $lang['live_impact_loaded'] ?? 'Live impact loaded' ?>');
    setText('impact-co2',`${fmt(data.co2_saved_kg,2)} kg`); setText('impact-water',`${fmt(data.water_saved_liters,1)} L`); setText('impact-energy',`${fmt(data.energy_saved_kwh,2)} kWh`);
    setText('impact-car',`${fmt(data.car_trip_equivalent)} <?= $lang['car_trips_avoided'] ?? 'car trips avoided' ?>`); setText('impact-bottles',`${fmt(data.water_bottle_equivalent)} <?= $lang['bottles_saved'] ?? 'bottles saved' ?>`); setText('impact-phone',`${fmt(data.phone_charge_equivalent)} <?= $lang['phone_charges'] ?? 'phone charges' ?>`);
    const badge=data.high_impact_badge?` <span class="impact-badge">${data.high_impact_badge}</span>`:'';
    <?php if ($currentLang === 'bn'): ?>
    document.getElementById('impact-story').innerHTML=`আপনি <strong>${fmt(data.co2_saved_kg,2)} কেজি CO2</strong> প্রতিরোধ করেছেন, <strong>${fmt(data.water_saved_liters,1)} লিটার পানি</strong> সাশ্রয় করেছেন, এবং <strong>${fmt(data.phone_charge_equivalent)}</strong> টি ফোন চার্জ করার জন্য যথেষ্ট শক্তি সঞ্চয় করেছেন।${badge}<br>${data.ewaste_message}`;
    <?php else: ?>
    document.getElementById('impact-story').innerHTML=`You prevented <strong>${fmt(data.co2_saved_kg,2)} kg CO2</strong>, saved <strong>${fmt(data.water_saved_liters,1)} liters of water</strong>, and enough energy for <strong>${fmt(data.phone_charge_equivalent)}</strong> phone charges.${badge}<br>${data.ewaste_message}`;
    <?php endif; ?>
  }).catch((err)=>setText('impact-status','Failed: ' + err.message));
  fetch(`${api}?action=forecast&user_id=${userId}`).then(r=>r.json()).then(data=>{
    if(data.error) throw new Error(data.error);
    const list=document.getElementById('impact-forecast-list'); list.innerHTML='';
    (data.forecast||[]).forEach(item=>{const li=document.createElement('li'); 
    <?php if ($currentLang === 'bn'): ?>
    li.textContent=`${item.month}: ${fmt(item.co2_saved_kg,2)} কেজি CO2, ${fmt(item.water_saved_liters,1)} লিটার পানি, ${fmt(item.energy_saved_kwh,2)} kWh (বিশ্বাসযোগ্যতা: ${data.confidence}, প্রবণতা: ${data.trend})`;
    <?php else: ?>
    li.textContent=`${item.month}: ${fmt(item.co2_saved_kg,2)} kg CO2, ${fmt(item.water_saved_liters,1)} L water, ${fmt(item.energy_saved_kwh,2)} kWh (${data.confidence} confidence, ${data.trend})`;
    <?php endif; ?>
    list.appendChild(li);});
  }).catch((err)=>{
    const list=document.getElementById('impact-forecast-list'); 
    list.innerHTML=`<li style="color:#b42318">Forecast unavailable: ${err.message || 'Service offline'}</li>`;
  });
})();
</script>
