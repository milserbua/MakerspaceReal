<?php
// hilfe.php
session_start();
// Check ob eingeloggt
if (!isset($_SESSION['userid'])) { header("Location: login.php"); exit; }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Hilfe & FAQ</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Standard CSS (Einheitlich mit Dashboard & Profil) */
        :root { 
            --primary-color: #4a90e2; 
            --primary-hover: #357abd;
            --sidebar-bg: #2c3e50; 
            --sidebar-hover: #34495e; 
            --bg-color: #f4f7f6; 
            --text-color: #333; 
            --text-muted: #7f8c8d; 
            --card-shadow: 0 4px 6px rgba(0,0,0,0.05); 
            --border-radius: 12px; 
        }

        body { 
            font-family: 'Roboto', sans-serif; margin: 0; background: var(--bg-color); color: var(--text-color); 
            height: 100vh; display: flex; overflow: hidden; 
        }

        /* --- SIDEBAR (Fixiertes Layout) --- */
        .sidebar {
            width: 260px; background-color: var(--sidebar-bg); color: #ecf0f1;
            display: flex; flex-direction: column; justify-content: space-between; /* WICHTIG */
            flex-shrink: 0; box-shadow: 4px 0 15px rgba(0,0,0,0.05); z-index: 10; height: 100vh;
        }

        /* Wrapper für oben (Logo + Navi) */
        .sidebar-content-top { display: flex; flex-direction: column; flex: 1; overflow-y: auto; }

        .sidebar-brand { 
            min-height: 70px; display: flex; align-items: center; padding: 0 25px; 
            font-size: 1.4rem; font-weight: 700; text-transform: uppercase; 
            border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; gap: 12px; 
        }
        .sidebar-brand i { color: var(--primary-color); }

        .sidebar-nav { padding: 20px 10px; }

        /* Wrapper für unten (Impressum) */
        .sidebar-bottom { 
            padding: 15px 10px; border-top: 1px solid rgba(255,255,255,0.05); background-color: rgba(0,0,0,0.1); 
        }

        .nav-link { 
            display: flex; align-items: center; padding: 12px 20px; color: #bdc3c7; 
            text-decoration: none; transition: all 0.3s ease; font-size: 0.95rem; 
            border-radius: 8px; margin-bottom: 5px; 
        }
        .nav-link:hover, .nav-link.active { 
            background-color: var(--sidebar-hover); color: #fff; transform: translateX(5px); 
        }
        .nav-link i { width: 35px; font-size: 1.1rem; transition: color 0.3s; }
        .nav-link:hover i, .nav-link.active i { color: var(--primary-color); }
        
        /* CONTENT */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        
        .top-navbar { 
            height: 70px; background: #fff; padding: 0 40px; 
            display: flex; justify-content: space-between; align-items: center; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.03); flex-shrink: 0; 
        }
        .page-title { color: var(--text-muted); font-weight: 500; }
        
        .user-info { display: flex; align-items: center; gap: 20px; }
        .btn-logout { 
            color: #e74c3c; text-decoration: none; border: 1px solid rgba(231,76,60,0.3); 
            padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; 
            transition: 0.2s; display: flex; gap: 8px; align-items: center; 
        }
        .btn-logout:hover { background: #e74c3c; color: white; }
        
        .container { max-width: 900px; margin: 0 auto; padding: 40px; width: 100%; box-sizing: border-box; flex: 1; }
        
        /* FAQ Styles */
        .card { background: #fff; border-radius: var(--border-radius); box-shadow: var(--card-shadow); padding: 30px; margin-bottom: 20px; }
        h3 { margin-top: 0; color: #2c3e50; }
        .faq-item { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .faq-item:last-child { border-bottom: none; }
        .question { font-weight: 700; color: #444; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .question i { color: var(--primary-color); }
        .answer { color: #666; line-height: 1.6; }

        .main-footer { 
            background: #fff; border-top: 1px solid #eee; padding: 20px; 
            text-align: center; color: #aaa; font-size: 0.8rem; margin-top: auto; 
        }
        .credits .name { font-weight: 500; color: #999; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <!-- OBERER BEREICH -->
        <div class="sidebar-content-top">
            <div class="sidebar-brand">
                <i class="fas fa-tools"></i> MAKERSPACE
            </div>
            
            <nav class="sidebar-nav">
                <!-- Link korrigiert auf dashboard.php -->
                <a href="dashboard_benutzer_view.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="geraete_uebersicht.php" class="nav-link">
                    <i class="fas fa-hammer"></i> Geräte
                </a>
                <a href="profil.php" class="nav-link">
                    <i class="fas fa-user-circle"></i> Mein Profil
                </a>
                <!-- Active Class nur hier -->
                <a href="hilfe.php" class="nav-link active">
                    <i class="fas fa-question-circle"></i> Hilfe
                </a>
            </nav>
        </div>

        <!-- UNTERER BEREICH (Impressum) -->
        <div class="sidebar-bottom">
            <a href="impressum.php" class="nav-link">
                <!-- Neues Icon: Waage (Recht) -->
                <i class="fas fa-scale-balanced"></i> Impressum
            </a>
        </div>
    </aside>

    <main class="main-content">
        <nav class="top-navbar">
            <div class="page-title">Mitgliederbereich &rsaquo; Hilfe</div>
            <div class="user-info">
                <span>Hallo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
            </div>
        </nav>

        <div class="container">
            <div class="card">
                <h3>Häufig gestellte Fragen (FAQ)</h3>
                
                <div class="faq-item">
                    <div class="question"><i class="fas fa-question-circle"></i> Wie bekomme ich eine Schulung?</div>
                    <div class="answer">Bitte wende dich an den zuständigen Raumbeauftragten oder Lehrer. Schulungen finden in der Regel wöchentlich statt. Sobald du eine Schulung absolviert hast, wird sie in deinem Profil freigeschaltet.</div>
                </div>

                <div class="faq-item">
                    <div class="question"><i class="fas fa-exclamation-triangle"></i> Eine Maschine ist defekt. Was tun?</div>
                    <div class="answer">Bitte melde den Defekt sofort einem Aufsichtsführenden oder nutze das Kontaktformular. Benutze die Maschine auf keinen Fall weiter!</div>
                </div>

                <div class="faq-item">
                    <div class="question"><i class="fas fa-clock"></i> Wann hat der Makerspace geöffnet?</div>
                    <div class="answer">Die Öffnungszeiten hängen am Eingang aus. In der Regel ist der Makerspace von Montag bis Samstag von 08:00 bis 21:00 Uhr geöffnet.</div>
                </div>
            </div>
        </div>

        <footer class="main-footer">
            &copy; 2025 Makerspace<br>
        </footer>
    </main>
</body>
</html>