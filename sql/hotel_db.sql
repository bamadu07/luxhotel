-- hotel_db.sql
CREATE DATABASE IF NOT EXISTS hotel_db;
USE hotel_db;

-- Table des chambres
CREATE TABLE chambres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_chambre VARCHAR(50) NOT NULL,
    description TEXT,
    prix_nuit DECIMAL(10,2) NOT NULL,
    capacite INT NOT NULL,
    disponibilite BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(255)
);

-- Table des réservations
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_client VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    date_arrivee DATE NOT NULL,
    date_depart DATE NOT NULL,
    type_chambre VARCHAR(50) NOT NULL,
    nombre_personnes INT NOT NULL,
    prix_total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'annulee') DEFAULT 'en_attente',
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des contacts
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table administrateur
CREATE TABLE administrateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Insertion de données d'exemple
INSERT INTO chambres (type_chambre, description, prix_nuit, capacite, image_url) VALUES
('Chambre Standard', 'Chambre confortable avec lit double, salle de bain privée et vue sur le jardin.', 120.00, 2, 'standard.jpg'),
('Chambre Deluxe', 'Chambre spacieuse avec lit king-size, balcon et salle de bain avec baignoire.', 180.00, 2, 'deluxe.jpg'),
('Suite Familiale', 'Suite spacieuse avec chambre séparée, idéale pour les familles.', 250.00, 4, 'familiale.jpg'),
('Suite Présidentielle', 'Suite de luxe avec salon séparé, jacuzzi et vue panoramique.', 450.00, 2, 'presidentielle.jpg');

-- Insertion d'un administrateur (mot de passe: admin123)
INSERT INTO administrateurs (username, password) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    sujet VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu TINYINT(1) DEFAULT 0
);
