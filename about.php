<?php
require_once 'includes/config.php';
startSession();
$currentLang = $_SESSION['lang'] ?? 'en';

$t = function(string $en, string $bn) use ($currentLang): string {
    return $currentLang === 'bn' ? $bn : $en;
};
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'bn' ? 'bn' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t('About Us — Notun Alo', 'আমাদের সম্পর্কে — নতুন আলো') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-dark: #0A2E1E;
            --brand-primary: #1D9E75;
            --brand-light: #E6F5EE;
            --gold: #D97706;
            --gold-bg: #FEF3C7;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-muted: #9CA3AF;
            --border: #E5E7EB;
            --bg-page: #F5F7F2;
            --card-bg: #FFFFFF;
            --team-bg: linear-gradient(135deg, #f0fdf4, #dcfce7);
            --team-border: #bbf7d0;
        }
        body.dark-mode {
            --brand-dark: #111827;
            --brand-light: #1a2e24;
            --text-primary: #E5E7EB;
            --text-secondary: #9CA3AF;
            --text-muted: #6B7280;
            --border: #374151;
            --bg-page: #0F1712;
            --card-bg: #1a2320;
            --team-bg: linear-gradient(135deg, #0a1f15, #0f2e1a);
            --team-border: #1b4d2a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg-page); color: var(--text-secondary); }

        .page-wrap { max-width: 1060px; margin: 0 auto; padding: 32px 24px; }
        @media (max-width: 640px) { .page-wrap { padding: 20px 16px; } }

        .back-link {
            display: inline-flex; align-items: center; gap: 8px;
            color: #065f46; background: #ecfdf5;
            border: 1px solid #d1fae5;
            padding: 8px 16px; border-radius: 8px;
            text-decoration: none; font-size: 14px; font-weight: 600;
            margin-bottom: 24px; transition: 0.2s;
        }
        .back-link:hover { background: #d1fae5; transform: translateX(-2px); }
        body.dark-mode .back-link { background: #1a2e24; color: #6ee7b7; border-color: #1b4d2a; }
        body.dark-mode .back-link:hover { background: #0f2e1a; }

        /* HERO */
        .about-hero {
            background: linear-gradient(135deg, #064e3b, #065f46, #1D9E75);
            border-radius: 28px; padding: 56px 64px;
            position: relative; overflow: hidden; margin-bottom: 40px;
        }
        .about-hero::before {
            content: ''; position: absolute;
            width: 350px; height: 350px;
            background: rgba(52,211,153,0.12); border-radius: 50%;
            top: -100px; right: -80px; filter: blur(60px);
        }
        .about-hero::after {
            content: ''; position: absolute;
            width: 250px; height: 250px;
            background: rgba(163,230,53,0.08); border-radius: 50%;
            bottom: -80px; left: -60px; filter: blur(50px);
        }
        .hero-inner { position: relative; z-index: 1; display: flex; align-items: flex-start; gap: 24px; flex-wrap: wrap; }
        .hero-icon { font-size: 3.5rem; line-height: 1; }
        .hero-text { flex: 1; min-width: 280px; }
        .hero-label {
            display: inline-block; font-size: 0.75rem; font-weight: 700;
            color: #6ee7b7; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 12px;
        }
        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 700; color: white; margin-bottom: 16px; line-height: 1.3;
        }
        .hero-title span { color: #a3e635; }
        .hero-desc { color: rgba(255,255,255,0.88); font-size: 1rem; line-height: 1.8; max-width: 680px; }

        /* MANIFESTO */
        .manifesto {
            background: linear-gradient(135deg, #064e3b, #065f46, #0d7556);
            border-radius: 28px; padding: 56px 64px; color: white;
            margin-bottom: 40px; position: relative; overflow: hidden;
            box-shadow: 0 30px 80px rgba(6,78,59,0.35);
        }
        .manifesto::before {
            content: ''; position: absolute;
            width: 350px; height: 350px;
            background: rgba(52,211,153,0.12); border-radius: 50%;
            top: -100px; right: -80px; filter: blur(60px);
        }
        .manifesto::after {
            content: ''; position: absolute;
            width: 250px; height: 250px;
            background: rgba(163,230,53,0.1); border-radius: 50%;
            bottom: -80px; left: -60px; filter: blur(50px);
        }
        .manifesto-inner { position: relative; z-index: 1; }
        .manifesto-icon { font-size: 3rem; margin-bottom: 24px; }
        .manifesto-quote {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.4rem, 3vw, 2.2rem);
            font-weight: 700; margin-bottom: 28px; line-height: 1.4;
        }
        .manifesto-quote span { color: #a3e635; }
        .manifesto p { font-size: 1.05rem; opacity: 0.9; line-height: 1.9; max-width: 800px; margin-bottom: 20px; }
        .manifesto strong { color: #6ee7b7; }

        /* PILLARS */
        .pillars {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px; margin-bottom: 40px;
        }
        .pillar-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px; padding: 36px 28px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .pillar-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.1); }
        .pillar-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin-bottom: 20px; }
        .pillar-card h4 { font-size: 1.15rem; font-weight: 700; color: var(--text-primary); margin-bottom: 12px; }
        .pillar-card p { color: var(--text-secondary); line-height: 1.75; font-size: 0.95rem; }

        /* TEAM */
        .team-section {
            background: var(--team-bg);
            border: 1px solid var(--team-border);
            border-radius: 20px; padding: 40px;
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: 24px; margin-bottom: 40px;
        }
        .team-info .label { font-size: 0.8rem; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 8px; }
        .team-info .name { font-size: 1.5rem; font-weight: 800; color: #064e3b; }
        .team-info .uni { color: #4b5563; margin-top: 4px; font-size: 0.95rem; }
        .team-info .event { color: #16a34a; font-weight: 600; font-size: 0.9rem; margin-top: 4px; }
        body.dark-mode .team-info .label { color: #6ee7b7; }
        body.dark-mode .team-info .name { color: #e5e7eb; }
        body.dark-mode .team-info .uni { color: #9ca3af; }
        body.dark-mode .team-info .event { color: #4ade80; }
        .team-badges { display: flex; gap: 16px; flex-wrap: wrap; }
        .team-badge { text-align: center; background: var(--card-bg); border-radius: 14px; padding: 16px 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .team-badge .icon { font-size: 1.6rem; font-weight: 800; }
        .team-badge .txt { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; }
        body.dark-mode .team-badge { background: #1a2320; }

        /* CTA */
        .cta-about {
            background: linear-gradient(135deg, #064e3b, #065f46);
            border-radius: 20px; padding: 40px; text-align: center; color: white;
        }
        .cta-about h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 12px; }
        .cta-about p { opacity: 0.9; margin-bottom: 24px; }
        .cta-about a {
            display: inline-block; background: #d97706; color: white;
            font-weight: 700; padding: 14px 36px; border-radius: 12px; text-decoration: none;
        }
        .cta-about a:hover { background: #b45309; }

        @media (max-width: 768px) {
            .about-hero { padding: 36px 28px; }
            .manifesto { padding: 36px 28px; }
            .team-section { flex-direction: column; text-align: center; justify-content: center; }
            .team-badges { justify-content: center; }
        }
        @media (max-width: 480px) {
            .about-hero { padding: 24px 20px; border-radius: 20px; }
            .manifesto { padding: 24px 20px; border-radius: 20px; }
            .team-section { padding: 28px 20px; }
        }
    </style>
</head>
<body>

    <?php include 'includes/navbar.php'; ?>

    <main class="page-wrap">

        <!-- BACK -->
        <a href="<?= isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php' ?>" class="back-link">&larr; <?= $t('Back', 'ফিরে যান') ?></a>

        <!-- HERO -->
        <div class="about-hero">
            <div class="hero-inner">
                <div class="hero-icon">&#9851;&#65039;</div>
                <div class="hero-text">
                    <div class="hero-label"><?= $t('About Notun Alo', 'নতুন আলো সম্পর্কে') ?></div>
                    <h1 class="hero-title"><?= $t('Turning Waste Into ', 'আবর্জনাকে ') ?><span><?= $t('New Light', 'নতুন আলোয়') ?></span><?= $t('', ' রূপান্তর') ?></h1>
                    <p class="hero-desc">
                        <?= $t(
                            'Notun Alo was born from a conviction — that Bangladesh\'s 170 million people deserve a smarter, fairer way to deal with their waste. We built a platform that doesn\'t just collect recyclables; it builds habits, rewards action, and creates a community of environmental champions.',
                            'নতুন আলো একটি বিশ্বাস থেকে জন্ম নিয়েছে — যে বাংলাদেশের ১৭ কোটি মানুষ তাদের বর্জ্য ব্যবস্থাপনার জন্য একটি স্মার্ট, ন্যায্য উপায়ের যোগ্য। আমরা এমন একটি প্ল্যাটফর্ম তৈরি করেছি যা শুধু পুনর্ব্যবহারযোগ্য জিনিস সংগ্রহ করে না; এটি অভ্যাস গড়ে তোলে, কাজকে পুরস্কৃত করে এবং পরিবেশ রক্ষাকারীদের একটি সম্প্রদায় তৈরি করে।'
                        ) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- MANIFESTO -->
        <div class="manifesto">
            <div class="manifesto-inner">
                <div class="manifesto-icon">&#9851;&#65039;</div>
                <div class="manifesto-quote">
                    <?= $t(
                        '"Every day, Dhaka alone generates over 5,700 tons of waste. <span>We decided to turn that crisis into an opportunity.</span>"',
                        '"প্রতিদিন, শুধু ঢাকাই উৎপন্ন করে ৫,৭০০ টনের বেশি বর্জ্য। <span>আমরা সেই সংকটকে সুযোগে পরিণত করেছি।</span>"'
                    ) ?>
                </div>
                <p>
                    <?= $t(
                        'Notun Alo — meaning <strong>"New Light"</strong> in Bengali — was built by a team of passionate students who saw a broken system and chose to fix it. Bangladesh produces millions of tons of recyclable waste each year, yet only a fraction is recovered. The gap exists not because people don\'t care — it\'s because <em>there was no easy, rewarding way to act.</em>',
                        'নতুন আলো — বাংলায় যার অর্থ <strong>"নতুন আলো"</strong> — তৈরি করেছে একদল আবেগী শিক্ষার্থী যারা একটি ভাঙা ব্যবস্থা দেখেছে এবং এটি ঠিক করতে এগিয়ে এসেছে। বাংলাদেশ প্রতি বছর লক্ষ লক্ষ টন পুনর্ব্যবহারযোগ্য বর্জ্য উৎপাদন করে, অথচ তার সামান্য অংশই পুনরুদ্ধার হয়। এই ব্যবধানটি বিদ্যমান কারণ মানুষ যত্ন নেয় না — বরং <em>কাজ করার সহজ ও পুরস্কৃত উপায় ছিল না।</em>'
                    ) ?>
                </p>
                <p>
                    <?= $t(
                        'We built a bridge. A platform where households become heroes, where waste becomes worth, and where every kilogram recycled lights up a greener future for all of Bangladesh.',
                        'আমরা একটি সেতু তৈরি করেছি। একটি প্ল্যাটফর্ম যেখানে পরিবারগুলি হিরো হয়ে ওঠে, যেখানে বর্জ্য মূল্যবান হয়, এবং যেখানে প্রতিটি কেজি পুনর্ব্যবহার সমগ্র বাংলাদেশের জন্য একটি সবুজ ভবিষ্যত আলোকিত করে।'
                    ) ?>
                </p>
            </div>
        </div>

        <!-- PILLARS -->
        <div class="pillars">
            <div class="pillar-card">
                <div class="pillar-icon" style="background:#dcfce7; color:#166534;">&#127758;</div>
                <h4><?= $t('Our Mission', 'আমাদের লক্ষ্য') ?></h4>
                <p><?= $t(
                    'To make responsible waste disposal accessible, rewarding, and community-driven for every household in Bangladesh — starting from Dhaka and scaling to the nation.',
                    'প্রত্যেক বাংলাদেশী পরিবারের জন্য দায়িত্বশীল বর্জ্য নিষ্কাশনকে সহজলভ্য, পুরস্কৃত এবং কমিউনিটি-চালিত করা — ঢাকা থেকে শুরু করে সারা দেশে সম্প্রসারণ।'
                ) ?></p>
            </div>
            <div class="pillar-card">
                <div class="pillar-icon" style="background:#dbeafe; color:#2563eb;">&#128301;</div>
                <h4><?= $t('Our Vision', 'আমাদের দৃষ্টিভঙ্গি') ?></h4>
                <p><?= $t(
                    'A Bangladesh where circular economy principles are woven into daily life — where every citizen is empowered as an environmental steward, and recycling is as natural as breathing.',
                    'একটি বাংলাদেশ যেখানে সার্কুলার ইকোনমি নীতিগুলি দৈনন্দিন জীবনে বোনা — যেখানে প্রতিটি নাগরিক পরিবেশের রক্ষক হিসাবে ক্ষমতায়িত এবং পুনর্ব্যবহার শ্বাস নেওয়ার মতোই স্বাভাবিক।'
                ) ?></p>
            </div>
            <div class="pillar-card">
                <div class="pillar-icon" style="background:#fef3c7; color:#d97706;">&#128161;</div>
                <h4><?= $t('Our Motivation', 'আমাদের প্রেরণা') ?></h4>
                <p><?= $t(
                    'We are students who refused to accept the status quo. Climate urgency, the SDG goals for 2030, and the raw potential of Bangladesh\'s people ignited us to build something that matters beyond the classroom.',
                    'আমরা এমন ছাত্র যারা স্থিতাবস্থা মেনে নিতে অস্বীকার করেছি। জলবায়ু জরুরিতা, ২০৩০ সালের এসডিজি লক্ষ্যমাত্রা এবং বাংলাদেশের জনগণের কাঁচা সম্ভাবনা আমাদের ক্লাসরুমের বাইরে কিছু তৈরি করতে প্রজ্বলিত করেছে।'
                ) ?></p>
            </div>
            <div class="pillar-card">
                <div class="pillar-icon" style="background:#ede9fe; color:#7c3aed;">&#129309;</div>
                <h4><?= $t('Our Promise', 'আমাদের অঙ্গীকার') ?></h4>
                <p><?= $t(
                    'We promise transparency, fairness, and impact. Every point you earn is real. Every pickup counts. Every eco-product in our shop was chosen because it represents what responsible commerce should look like.',
                    'আমরা স্বচ্ছতা, ন্যায্যতা এবং প্রভাবের প্রতিশ্রুতি দিই। আপনার অর্জিত প্রতিটি পয়েন্ট বাস্তব। প্রতিটি পিকআপ গণনা করে। আমাদের দোকানের প্রতিটি ইকো-পণ্য বেছে নেওয়া হয়েছে কারণ এটি দায়িত্বশীল বাণিজ্যের প্রতিনিধিত্ব করে।'
                ) ?></p>
            </div>
        </div>

        <!-- TEAM -->
        <div class="team-section">
            <div class="team-info">
                <div class="label"><?= $t('Built With ❤️ By', 'নির্মিত হয়েছে ❤️ দ্বারা') ?></div>
                <div class="name"><?= $t('Team GhostRiders', 'টিম ঘোস্টরাইডার্স') ?></div>
                <div class="uni"><?= $t('University of Liberal Arts Bangladesh', 'ইউনিভার্সিটি অফ লিবারেল আর্টস বাংলাদেশ') ?></div>
                <div class="event"><?= $t('THE INFINITY AI BUILDFEST 2026', 'দ্য ইনফিনিটি এআই বিল্ডফেস্ট ২০২৬') ?></div>
            </div>
            <div class="team-badges">
                <div class="team-badge">
                    <div class="icon" style="color:#16a34a;">&#9851;</div>
                    <div class="txt"><?= $t('Circular Economy', 'সার্কুলার ইকোনমি') ?></div>
                </div>
                <div class="team-badge">
                    <div class="icon" style="color:#2563eb;">&#129302;</div>
                    <div class="txt"><?= $t('AI-Powered', 'এআই-চালিত') ?></div>
                </div>
                <div class="team-badge">
                    <div class="icon" style="color:#d97706;">&#127942;</div>
                    <div class="txt"><?= $t('Reward-First', 'পুরস্কার-ভিত্তিক') ?></div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="cta-about">
            <h2><?= $t('Ready to make a difference?', 'পরিবর্তনের অংশ হতে প্রস্তুত?') ?></h2>
            <p><?= $t('Join thousands of Bangladeshis building a greener future, one pickup at a time.', 'হাজার হাজার বাংলাদেশীর সাথে যান একটি সবুজ ভবিষ্যত গড়তে, এক পিকআপ at a time.') ?></p>
            <a href="register.php"><?= $t('Start Recycling Free →', 'বিনামূল্যে শুরু করুন →') ?></a>
        </div>
        <?php endif; ?>

    </main>

</body>
</html>
