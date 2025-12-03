<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Makerspace</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 1100px; margin: 0 auto; }
        
        /* Tabellen Styling */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        
        /* Status Farben */
        .role-admin { color: red; font-weight: bold; }
        .role-teilnehmer { color: green; }

        /* Layout Container */
        .container { 
            display: flex; 
            gap: 20px; 
            background-color: #f0f0f0; 
            padding: 20px;
            border-radius: 8px;
            /* Mindesthöhe definiert die Basisgröße. 
               Da 'align-items' standardmäßig 'stretch' ist, 
               werden beide Kinder gleich hoch. */
            min-height: 500px; 
        }

        /* Sidebar (Linkes Menü) */
        .sidebar {
            width: 250px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex-shrink: 0; 
            /* WICHTIG: 'height: fit-content' wurde entfernt, 
               damit sich die Box auf die volle Höhe zieht. */
        }

        .sidebar h3 {
            margin-top: 0;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 8px;
        }

        .sidebar a {
            display: block;
            padding: 12px;
            background-color: #e9ecef;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s, padding-left 0.2s;
        }

        .sidebar a:hover {
            background-color: #007bff; 
            color: white;
            padding-left: 15px; 
        }

        /* Hauptinhalt (Rechts) */
        .main-content {
            flex-grow: 1; 
            background: white;
            padding: 40px; 
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            
            /* ZENTRIERUNG */
            display: flex;
            flex-direction: column; 
            align-items: center;    
            justify-content: center; 
            text-align: center;     
        }
        
        .welcome-text h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #333;
        }

        .welcome-text p {
            font-size: 1.2rem;
            color: #666;
        }

        /* Top Bar angepasst */
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .top-bar .brand { font-size: 1.2em; font-weight: bold; color: #555; }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="brand">Makerspace Dashboard</div>
        <a href="logout.php" style="color:red; text-decoration: none; border: 1px solid red; padding: 5px 10px; border-radius: 4px;">Ausloggen</a>
    </div>

    <?php // include 'message.php'; ?>

    <div class="container">   
        
        <!-- LINKER BEREICH: Navigation -->
        <div class="sidebar">
            <h3>Verwaltung</h3>
            <ul>
                <li><a href="benutzer.php">👤 Benutzer</a></li>
                <li><a href="rollen.php">🎭 Rollen</a></li>
                <li><a href="berechtigungen.php">🔑 Berechtigungen</a></li>
                <li><a href="raeume.php">🏢 Räume</a></li>
                <li><a href="maschinen.php">⚙️ Maschinen</a></li>
            </ul>
        </div>

        <!-- RECHTER BEREICH: Inhalt -->
        <div class="main-content">
            <div class="welcome-text">
                <h1>Hallo, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>!</h1>
                <p>Willkommen im Admin-Dashboard.<br>Wähle links einen Menüpunkt aus, um zu starten.</p>
            </div>
        </div>
<h2>Raum hinzufügen</h2>
<form action="add_room.php" method="post">
    <input type="text" name="raumnummer" placeholder="Raumnummer" required>
    <button type="submit">Hinzufügen</button>
</form>

<h2>Raum löschen</h2>
<form action="delete_room.php" method="post">
    <input type="text" name="raumnummer" placeholder="Raumnummer" required>
    <button type="submit">Löschen</button>
</form>

    </div>

</body>
</html>