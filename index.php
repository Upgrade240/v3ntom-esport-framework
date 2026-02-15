<?php
session_start();
require_once 'includes/Database.php';

$teamsByGame = [];
$games = ['CS2', 'LoL', 'RL'];

if (file_exists('config.php')) {
    $config = include 'config.php';
    $db = new Database($config);

    foreach ($games as $game) {
        $teamsByGame[$game] = $db->fetchAll($db->query(
        "SELECT t.id, t.name, t.description, t.game, t.color, u.username as leader_name
         FROM teams t
         LEFT JOIN users u ON u.id = t.leader_id
         WHERE t.game = ?
         ORDER BY t.created_at DESC",
        [$game]
    ));
    }
} else {
    foreach ($games as $game) {
        $teamsByGame[$game] = [];
    }
}

$loggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V3NTOM eSports</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #0f1013; color: #fff; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }
        .hero { text-align: center; padding: 3rem 0; }
        .btn { display: inline-block; padding: .8rem 1.2rem; border-radius: 6px; text-decoration: none; margin: .5rem; }
        .btn-primary { background: #5865f2; color: #fff; }
        .btn-secondary { border: 1px solid #5865f2; color: #fff; }
        .section-title { margin-top: 2rem; color: #8ea0ff; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 1rem; }
        .card { background: #1a1d24; border: 1px solid #272b33; border-radius: 8px; padding: 1rem; }
        .muted { color: #9da3b3; font-size: .95rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="hero">
            <?php if (!file_exists('config.php')): ?>
                <p class="muted">Hinweis: System ist noch nicht konfiguriert. Bitte erst die Installation abschließen.</p>
            <?php endif; ?>
            <h1>V3NTOM eSports Community</h1>
            <p class="muted">Community Management System mit Discord Login, Team-Verwaltung und Rollen-Sync.</p>
            <?php if ($loggedIn): ?>
                <a class="btn btn-primary" href="dashboard.php">Zum Dashboard</a>
            <?php else: ?>
                <a class="btn btn-primary" href="api/auth.php">Mit Discord anmelden</a>
            <?php endif; ?>
        </section>

        <section>
            <h2>Unsere Teams</h2>
            <?php foreach ($teamsByGame as $game => $teams): ?>
                <h3 class="section-title"><?php echo htmlspecialchars($game); ?></h3>
                <div class="grid">
                    <?php if (empty($teams)): ?>
                        <div class="card muted">Noch kein Team in dieser Kategorie.</div>
                    <?php else: ?>
                        <?php foreach ($teams as $team): ?>
                            <article class="card">
                                <h4><?php echo htmlspecialchars($team['name']); ?></h4>
                                <p class="muted"><?php echo htmlspecialchars($team['description'] ?: 'Keine Beschreibung'); ?></p>
                                <p class="muted">Leitung: <?php echo htmlspecialchars($team['leader_name'] ?: 'Offen'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
    </div>
</body>
</html>
