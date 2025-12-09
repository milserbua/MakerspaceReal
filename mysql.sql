-- 1. Datenbank erstellen (und alte löschen, falls vorhanden)
DROP DATABASE IF EXISTS makerspace;
CREATE DATABASE makerspace DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE makerspace;

-- ==========================================
-- 2. Tabellen erstellen (Stammdaten zuerst)
-- ==========================================

-- Tabelle: Werkstattbenutzer (Die User)
CREATE TABLE Werkstattbenutzer (
    WerkBenutzerID INT AUTO_INCREMENT PRIMARY KEY,
    Vorname VARCHAR(50) NOT NULL,
    Nachname VARCHAR(50) NOT NULL,
    Username VARCHAR(50) NOT NULL UNIQUE, -- Für Login
    Passwort VARCHAR(255) NOT NULL,       -- Für Login (Hash)
    Rolle VARCHAR(20) DEFAULT 'Teilnehmer', -- Admin oder Teilnehmer
    ErstelltAm TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabelle: Werkstattbereich (Die Räume/Bereiche)
CREATE TABLE Werkstattbereich (
    WerkBereichID INT AUTO_INCREMENT PRIMARY KEY,
    Bezeichnung VARCHAR(100) NOT NULL, -- z.B. "Holzwerkstatt", "Metallbereich"
    Ort VARCHAR(100)                   -- Optional: Wo ist der Raum?
);

-- Tabelle: Maschinenschulungen (Die Schulungs-Typen)
CREATE TABLE Maschinenschulungen (
    MaschinenSchulungsID INT AUTO_INCREMENT PRIMARY KEY,
    Bezeichnung VARCHAR(100) NOT NULL,       -- z.B. "CNC-Grundkurs"
    MaschinenGruppe VARCHAR(100) NOT NULL,   -- z.B. "Fräsen"
    Beschreibung TEXT
);

-- ==========================================
-- 3. Tabellen mit Fremdschlüsseln (Verknüpfungen)
-- ==========================================

-- Tabelle: Authentifizierungen (Tokens, Karten der User)
CREATE TABLE Authentifizierungen (
    WerkBenutzerAuthID INT AUTO_INCREMENT PRIMARY KEY,
    WerkBenutzerID INT NOT NULL,
    TokenCode VARCHAR(255) NOT NULL, -- z.B. RFID-Kartennummer
    Typ VARCHAR(50),                 -- z.B. "RFID", "Keycard"
    FOREIGN KEY (WerkBenutzerID) REFERENCES Werkstattbenutzer(WerkBenutzerID) ON DELETE CASCADE
);

-- Tabelle: WerkstattsbereichAuthentifizierungen 
-- (Welche Auth-Arten gibt es für einen Bereich?)
CREATE TABLE WerkstattsbereichAuthentifizierungen (
    WerkBereichAuthID INT AUTO_INCREMENT PRIMARY KEY,
    WerkBereichID INT NOT NULL,
    AuthArt VARCHAR(100) NOT NULL, -- z.B. "Kartenleser Eingang West"
    FOREIGN KEY (WerkBereichID) REFERENCES Werkstattbereich(WerkBereichID) ON DELETE CASCADE
);

-- Tabelle: MaschinenImWerkstattbereich (Die physischen Maschinen)
CREATE TABLE MaschinenImWerkstattbereich (
    MaschineID INT AUTO_INCREMENT PRIMARY KEY,
    Bezeichnung VARCHAR(100) NOT NULL, -- z.B. "Bohrmaschine XY"
    WerkBereichID INT NOT NULL,        -- Wo steht sie?
    NotwendigeSchulungsID INT,         -- Welche Schulung braucht man? (nullable, falls keine nötig)
    FOREIGN KEY (WerkBereichID) REFERENCES Werkstattbereich(WerkBereichID) ON DELETE RESTRICT,
    FOREIGN KEY (NotwendigeSchulungsID) REFERENCES Maschinenschulungen(MaschinenSchulungsID) ON DELETE SET NULL
);

-- ==========================================
-- 4. n:m Verknüpfungstabellen (Relationen)
-- ==========================================

-- Tabelle: Werkstattbenutzerschulungen (Welcher User hat welche Schulung?)
CREATE TABLE Werkstattbenutzerschulungen (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    WerkBenutzerID INT NOT NULL,
    MaschinenSchulungsID INT NOT NULL,
    AbschlussDatum DATE NOT NULL,
    FOREIGN KEY (WerkBenutzerID) REFERENCES Werkstattbenutzer(WerkBenutzerID) ON DELETE CASCADE,
    FOREIGN KEY (MaschinenSchulungsID) REFERENCES Maschinenschulungen(MaschinenSchulungsID) ON DELETE CASCADE
);

-- Tabelle: Zutrittsberechtigungen (Wer darf in welchen Raum?)
CREATE TABLE Zutrittsberechtigungen (
    ZutrittID INT AUTO_INCREMENT PRIMARY KEY,
    WerkBenutzerID INT NOT NULL,
    WerkBereichID INT NOT NULL,
    StartDatum DATE NOT NULL,
    EndeDatum DATE NOT NULL,
    FOREIGN KEY (WerkBenutzerID) REFERENCES Werkstattbenutzer(WerkBenutzerID) ON DELETE CASCADE,
    FOREIGN KEY (WerkBereichID) REFERENCES Werkstattbereich(WerkBereichID) ON DELETE CASCADE
);

-- Tabelle: Benutzungserlaubnis (Wer darf welche konkrete Maschine nutzen?)
-- Oft logisch abgeleitet aus Schulung, aber im Diagramm als eigene Tabelle:
CREATE TABLE Benutzungserlaubnis (
    ErlaubnisID INT AUTO_INCREMENT PRIMARY KEY,
    WerkBenutzerID INT NOT NULL,
    MaschineID INT NOT NULL,
    ErteiltAm TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (WerkBenutzerID) REFERENCES Werkstattbenutzer(WerkBenutzerID) ON DELETE CASCADE,
    FOREIGN KEY (MaschineID) REFERENCES MaschinenImWerkstattbereich(MaschineID) ON DELETE CASCADE
);
