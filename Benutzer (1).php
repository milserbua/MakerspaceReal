<?php
// ==== "Fake-Datenbank": Daten direkt im Code ====

// Klassen-Liste
$klassen = [
    1 => ['id' => 1, 'name' => '1A'],
    2 => ['id' => 2, 'name' => '1B'],
    3 => ['id' => 3, 'name' => '2A'],
    4 => ['id' => 4, 'name' => '2B'],
];

// Schüler-Liste (mehr Fake-Schüler)
$schueler = [
    1 => [
        'id' => 1,
        'vorname' => 'Max',
        'nachname' => 'Muster',
        'alter' => 14,
        'klassen_id' => 1,
        'rechte' => ['Admin', 'Darf Computerraum nutzen', 'Darf Noten bearbeiten']
    ],
    2 => [
        'id' => 2,
        'vorname' => 'Anna',
        'nachname' => 'Schmidt',
        'alter' => 13,
        'klassen_id' => 1,
        'rechte' => ['Nur Lesen']
    ],
    3 => [
        'id' => 3,
        'vorname' => 'Lukas',
        'nachname' => 'Maier',
        'alter' => 14,
        'klassen_id' => 1,
        'rechte' => ['Standard Benutzer', 'Darf Drucker nutzen']
    ],
    4 => [
        'id' => 4,
        'vorname' => 'Sophie',
        'nachname' => 'Huber',
        'alter' => 13,
        'klassen_id' => 2,
        'rechte' => ['Nur Lesen', 'Darf Computerraum nutzen']
    ],
    5 => [
        'id' => 5,
        'vorname' => 'Jonas',
        'nachname' => 'Berger',
        'alter' => 14,
        'klassen_id' => 2,
        'rechte' => ['Standard Benutzer']
    ],
    6 => [
        'id' => 6,
        'vorname' => 'Mia',
        'nachname' => 'Gruber',
        'alter' => 15,
        'klassen_id' => 2,
        'rechte' => ['Admin']
    ],
    7 => [
        'id' => 7,
        'vorname' => 'Tobias',
        'nachname' => 'Klein',
        'alter' => 15,
        'klassen_id' => 3,
        'rechte' => ['Standard Benutzer', 'Darf Dateien hochladen']
    ],
    8 => [
        'id' => 8,
        'vorname' => 'Lea',
        'nachname' => 'Brandner',
        'alter' => 16,
        'klassen_id' => 3,
        'rechte' => ['Nur Lesen']
    ],
    9 => [
        'id' => 9,
        'vorname' => 'David',
        'nachname' => 'Schwarz',
        'alter' => 16,
        'klassen_id' => 3,
        'rechte' => ['Standard Benutzer', 'Darf Computerraum nutzen']
    ],
    10 => [
        'id' => 10,
        'vorname' => 'Emily',
        'nachname' => 'Hofer',
        'alter' => 16,
        'klassen_id' => 4,
        'rechte' => ['Admin', 'Darf Noten bearbeiten']
    ],
    11 => [
        'id' => 11,
        'vorname' => 'Niklas',
        'nachname' => 'Wagner',
        'alter' => 17,
        'klassen_id' => 4,
        'rechte' => ['Standard Benutzer']
    ],
    12 => [
        'id' => 12,
        'vorname' => 'Laura',
        'nachname' => 'Fischer',
        'alter' => 17,
        'klassen_id' => 4,
        'rechte' => ['Nur Lesen', 'Darf Dateien hochladen']
    ],
];

// ---- Hilfsfunktionen ----

function getStudentsByClass(array $schueler, int $classId): array {
    $result = [];
    foreach ($schueler as $s) {
        if ($s['klassen_id'] === $classId) {
            $result[] = $s;
        }
    }
    usort($result, fn($a, $b) => strcmp($a['nachname'], $b['nachname']));
    return $result;
}

function getStudentById(array $schueler, int $studentId): ?array {
    return $schueler[$studentId] ?? null;
}

// ---- Welche Ansicht wird angezeigt? ----
$classId   = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung</title>

    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #e0e7ff, #fdf2ff);
            --card-bg: #ffffff;
            --accent: #4f46e5;
            --accent-soft: #eef2ff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --border-soft: #e5e7eb;
        }

        body {
            font-family: system-ui, sans-serif;
            background: var(--bg-gradient);
            margin: 0;
            padding: 30px;
            color: var(--text-main);
        }

        .page-wrapper { max-width: 1100px; margin: auto; }
        .container {
            background: var(--card-bg);
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,0.12);
            border: 1px solid rgba(148,163,184,0.2);
        }

        /* ⭐ HEADER MIT LOGO */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-logo {
            height: 90px;
            width: auto;
        }

        .title-group h1 {
            margin: 0;
            font-size: 26px;
        }

        .title-group p {
            margin: 3px 0 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .tag {
            background: var(--accent-soft);
            padding: 4px 10px;
            border-radius: 999px;
            color: var(--accent);
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tag-dot {
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
        }

        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .back-link {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 14px;
            color: var(--text-muted);
        }

        .card {
            background: #f9fafb;
            padding: 18px;
            border-radius: 14px;
            border: 1px solid var(--border-soft);
            margin-bottom: 18px;
        }

        .list {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-soft);
        }

        .list-header {
            display: grid;
            grid-template-columns: 1fr auto;
            padding: 10px 14px;
            background: #eef2ff;
            font-size: 13px;
            text-transform: uppercase;
        }

        .list-item {
            display: grid;
            grid-template-columns: 1fr auto;
            padding: 10px 14px;
            border-top: 1px solid var(--border-soft);
            transition: 0.15s;
        }

        .list-item:hover {
            background: #f1f4ff;
            transform: translateY(-1px);
        }

        .pill {
            background: #eef2ff;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            transition: 0.15s;
        }

        .list-item:hover .pill {
            background: #e0e7ff;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            background: #e4ecff;
            margin: 4px;
            display: inline-block;
            font-size: 12px;
        }

        .badge.admin { background: #fee2e2; color: #b91c1c; }
        .badge.readonly { background: #ecfeff; color: #155e75; }
        .badge.standard { background: #ecfdf3; color: #166534; }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
            gap: 12px;
        }

        .detail-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .detail-value {
            font-weight: 600;
        }

        .muted { color: var(--text-muted); }
    </style>
</head>
<body>

<div class="page-wrapper">
    <div class="container">

        <!-- ⭐ HEADER MIT LOGO -->
        <div class="header">
            <div class="brand">
                <img src="csm_Logo_HTL_Anichstrasse_20ff29b691.png" class="brand-logo" alt="HTL Anichstraße">
                <div class="title-group">
                    <h1>Benutzerverwaltung</h1>
                    <p>Klassen, Schüler und Rechte verwalten</p>
                </div>
            </div>
            <div class="tag">
                <span class="tag-dot"></span>
                Demo-Modus
            </div>
        </div>

        <?php if ($studentId): ?>
            <?php $s = getStudentById($schueler, $studentId); ?>

            <a class="back-link" href="benutzer.php?class_id=<?= $s['klassen_id'] ?>">← Zurück</a>

            <div class="card">
                <h2><?= $s['vorname'] . " " . $s['nachname'] ?></h2>
                <p class="muted">Details & Rechte</p>

                <div class="detail-grid">
                    <div>
                        <div class="detail-label">Vorname</div>
                        <div class="detail-value"><?= $s['vorname'] ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Nachname</div>
                        <div class="detail-value"><?= $s['nachname'] ?></div>
                    </div>
                    <div>
                        <div class="detail-label">Alter</div>
                        <div class="detail-value"><?= $s['alter'] ?> Jahre</div>
                    </div>
                    <div>
                        <div class="detail-label">Klasse</div>
                        <div class="detail-value"><?= $klassen[$s['klassen_id']]['name'] ?></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3>Rechte</h3>
                <?php foreach ($s['rechte'] as $r):
                    $class = '';
                    $lower = strtolower($r);
                    if (str_contains($lower, 'admin')) $class = 'admin';
                    elseif (str_contains($lower, 'lesen')) $class = 'readonly';
                    elseif (str_contains($lower, 'standard')) $class = 'standard';
                ?>
                    <span class="badge <?= $class ?>"><?= $r ?></span>
                <?php endforeach; ?>
            </div>

        <?php elseif ($classId): ?>

            <?php $klasse = $klassen[$classId]; ?>
            <?php $students = getStudentsByClass($schueler, $classId); ?>

            <a class="back-link" href="benutzer.php">← Zurück</a>

            <div class="card">
                <h2>Klasse <?= $klasse['name'] ?></h2>
                <p class="muted"><?= count($students) ?> Schüler</p>
            </div>

            <div class="list">
                <div class="list-header">
                    <div>Schüler</div><div>Info</div>
                </div>

                <?php foreach ($students as $s): ?>
                    <div class="list-item">
                        <div>
                            <a href="benutzer.php?student_id=<?= $s['id'] ?>">
                                <strong><?= $s['nachname'] ?>, <?= $s['vorname'] ?></strong>
                            </a>
                            <div class="muted"><?= $s['alter'] ?> Jahre</div>
                        </div>
                        <div><span class="pill">Details ansehen</span></div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>

            <div class="card">
                <h2>Klassenübersicht</h2>
                <p class="muted">Wähle eine Klasse aus.</p>
            </div>

            <div class="list">
                <div class="list-header">
                    <div>Klasse</div><div>Schüler</div>
                </div>

                <?php foreach ($klassen as $c): ?>
                    <?php $count = count(getStudentsByClass($schueler, $c['id'])); ?>
                    <div class="list-item">
                        <div>
                            <a href="benutzer.php?class_id=<?= $c['id'] ?>">
                                <strong>Klasse <?= $c['name'] ?></strong>
                            </a>
                            <div class="muted"><?= $count ?> Schüler</div>
                        </div>
                        <div><span class="pill">Öffnen</span></div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</div>

<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>
