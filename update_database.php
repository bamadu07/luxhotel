<?php
// update_database.php
include 'config/database.php';

try {
    // Ajouter les colonnes manquantes
    $queries = [
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS prix_jour DECIMAL(10,2) DEFAULT 0",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS categorie VARCHAR(50) DEFAULT 'Standard'",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS description_courte VARCHAR(255)",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS superficie INT DEFAULT 25",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS equipements TEXT",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE",
        
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS type_reservation ENUM('nuit', 'jour') DEFAULT 'nuit'",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS heure_arrivee TIME NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS heure_depart TIME NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS message_special TEXT"
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
        echo "Query executed: $query<br>";
    }

    // Mettre à jour les données existantes
    $update_queries = [
        "UPDATE chambres SET prix_jour = prix_nuit * 0.6 WHERE prix_jour = 0",
        "UPDATE chambres SET categorie = 'Standard' WHERE categorie IS NULL",
        "UPDATE chambres SET description_courte = LEFT(description, 100) WHERE description_courte IS NULL",
        "UPDATE chambres SET superficie = 25 WHERE superficie = 0",
        "UPDATE chambres SET equipements = 'WiFi, TV écran plat, Climatisation, Mini-bar, Sèche-cheveux' WHERE equipements IS NULL",
        "UPDATE chambres SET featured = 1 WHERE id IN (1, 3, 4)"
    ];

    foreach ($update_queries as $query) {
        $pdo->exec($query);
        echo "Data updated: $query<br>";
    }

    echo "Database updated successfully!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
// update_database.php
include 'config/database.php';

try {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f8f9fa; border-radius: 10px;'>";
    echo "<h2 style='color: #2C5530; text-align: center; margin-bottom: 30px;'>Mise à jour de la Base de Données</h2>";
    
    // Ajouter les colonnes manquantes à la table chambres
    $queries = [
        // Table chambres
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS prix_jour DECIMAL(10,2) DEFAULT 0",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS categorie VARCHAR(50) DEFAULT 'Standard'",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS description_courte VARCHAR(255)",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS superficie INT DEFAULT 25",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS equipements TEXT",
        "ALTER TABLE chambres ADD COLUMN IF NOT EXISTS featured BOOLEAN DEFAULT FALSE",
        
        // Table reservations
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS type_reservation ENUM('nuit', 'jour') DEFAULT 'nuit'",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS heure_arrivee TIME NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS heure_depart TIME NULL",
        "ALTER TABLE reservations ADD COLUMN IF NOT EXISTS message_special TEXT",
        
        // Table administrateurs - NOUVELLES COLONNES
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS nom_complet VARCHAR(100) AFTER id",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS email VARCHAR(100) AFTER nom_complet",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'admin' AFTER password",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS actif BOOLEAN DEFAULT TRUE AFTER role",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS remember_token VARCHAR(100) AFTER actif",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS token_expiry DATETIME AFTER remember_token",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0 AFTER token_expiry",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS last_login DATETIME AFTER login_attempts",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS date_creation DATETIME AFTER last_login",
        "ALTER TABLE administrateurs ADD COLUMN IF NOT EXISTS date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER date_creation"
    ];

    foreach ($queries as $query) {
        try {
            $pdo->exec($query);
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #28a745;'>
                    ✅ Query executed: " . htmlspecialchars($query) . "
                  </div>";
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #dc3545;'>
                    ❌ Error executing: " . htmlspecialchars($query) . "<br>
                    Error: " . $e->getMessage() . "
                  </div>";
        }
    }

    // Mettre à jour les données existantes
    $update_queries = [
        // Mise à jour des chambres
        "UPDATE chambres SET prix_jour = prix_nuit * 0.6 WHERE prix_jour = 0 OR prix_jour IS NULL",
        "UPDATE chambres SET categorie = 'Standard' WHERE categorie IS NULL OR categorie = ''",
        "UPDATE chambres SET description_courte = LEFT(description, 100) WHERE description_courte IS NULL OR description_courte = ''",
        "UPDATE chambres SET superficie = 25 WHERE superficie = 0 OR superficie IS NULL",
        "UPDATE chambres SET equipements = 'WiFi, TV écran plat, Climatisation, Mini-bar, Sèche-cheveux' WHERE equipements IS NULL OR equipements = ''",
        "UPDATE chambres SET featured = 1 WHERE id IN (1, 3, 4)",
        
        // Mise à jour des administrateurs
        "UPDATE administrateurs SET nom_complet = username WHERE nom_complet IS NULL OR nom_complet = ''",
        "UPDATE administrateurs SET email = CONCAT(username, '@hotelprestige.com') WHERE email IS NULL OR email = ''",
        "UPDATE administrateurs SET actif = 1 WHERE actif IS NULL",
        "UPDATE administrateurs SET date_creation = NOW() WHERE date_creation IS NULL"
    ];

    foreach ($update_queries as $query) {
        try {
            $result = $pdo->exec($query);
            echo "<div style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #17a2b8;'>
                    🔄 Data updated: " . htmlspecialchars($query) . "<br>
                    <small>Rows affected: " . $result . "</small>
                  </div>";
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #dc3545;'>
                    ❌ Error updating: " . htmlspecialchars($query) . "<br>
                    Error: " . $e->getMessage() . "
                  </div>";
        }
    }

    // Créer les index pour améliorer les performances
    $index_queries = [
        "CREATE INDEX IF NOT EXISTS idx_admin_username ON administrateurs(username)",
        "CREATE INDEX IF NOT EXISTS idx_admin_email ON administrateurs(email)",
        "CREATE INDEX IF NOT EXISTS idx_admin_actif ON administrateurs(actif)",
        "CREATE INDEX IF NOT EXISTS idx_chambres_disponibilite ON chambres(disponibilite)",
        "CREATE INDEX IF NOT EXISTS idx_chambres_featured ON chambres(featured)",
        "CREATE INDEX IF NOT EXISTS idx_reservations_date_arrivee ON reservations(date_arrivee)",
        "CREATE INDEX IF NOT EXISTS idx_reservations_statut ON reservations(statut)"
    ];

    foreach ($index_queries as $query) {
        try {
            $pdo->exec($query);
            echo "<div style='background: #e2e3e5; color: #383d41; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #6c757d;'>
                    📊 Index created: " . htmlspecialchars($query) . "
                  </div>";
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; border-left: 4px solid #dc3545;'>
                    ❌ Error creating index: " . htmlspecialchars($query) . "<br>
                    Error: " . $e->getMessage() . "
                  </div>";
        }
    }

    // Vérifier la structure des tables
    echo "<div style='margin-top: 30px; padding: 20px; background: white; border-radius: 10px;'>";
    echo "<h3 style='color: #2C5530;'>Structure des Tables</h3>";
    
    $tables = ['administrateurs', 'chambres', 'reservations', 'contacts'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div style='margin: 15px 0;'>";
            echo "<h4 style='color: #495057; margin-bottom: 10px;'>Table: $table</h4>";
            echo "<table style='width: 100%; border-collapse: collapse; font-size: 14px;'>";
            echo "<thead><tr style='background: #2C5530; color: white;'>";
            echo "<th style='padding: 8px; border: 1px solid #ddd;'>Field</th>";
            echo "<th style='padding: 8px; border: 1px solid #ddd;'>Type</th>";
            echo "<th style='padding: 8px; border: 1px solid #ddd;'>Null</th>";
            echo "<th style='padding: 8px; border: 1px solid #ddd;'>Key</th>";
            echo "<th style='padding: 8px; border: 1px solid #ddd;'>Default</th>";
            echo "</tr></thead><tbody>";
            
            foreach ($columns as $column) {
                echo "<tr style='background: " . ($column['Null'] == 'YES' ? '#fff3cd' : '#ffffff') . "'>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'><strong>" . $column['Field'] . "</strong></td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $column['Type'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $column['Null'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . $column['Key'] . "</td>";
                echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ($column['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table>";
            echo "</div>";
        } catch (PDOException $e) {
            echo "<div style='color: #dc3545;'>Erreur lors de la lecture de la table $table: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "</div>";

    // Vérifier les données de test
    echo "<div style='margin-top: 30px; padding: 20px; background: white; border-radius: 10px;'>";
    echo "<h3 style='color: #2C5530;'>Données de Test</h3>";
    
    // Compter les enregistrements
    $count_queries = [
        'Administrateurs' => 'SELECT COUNT(*) as count FROM administrateurs',
        'Chambres' => 'SELECT COUNT(*) as count FROM chambres',
        'Réservations' => 'SELECT COUNT(*) as count FROM reservations',
        'Contacts' => 'SELECT COUNT(*) as count FROM contacts'
    ];
    
    foreach ($count_queries as $label => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p><strong>$label:</strong> " . $result['count'] . " enregistrement(s)</p>";
        } catch (PDOException $e) {
            echo "<p style='color: #dc3545;'>Erreur pour $label: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "</div>";

    echo "<div style='margin-top: 30px; padding: 20px; background: #d4edda; color: #155724; border-radius: 10px; text-align: center;'>";
    echo "<h3 style='color: #155724;'>✅ Mise à jour terminée avec succès !</h3>";
    echo "<p>La base de données a été mise à jour avec toutes les nouvelles fonctionnalités.</p>";
    echo "<p><a href='login.php' style='color: #155724; text-decoration: underline;'>Accéder à la connexion administrateur</a></p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px; text-align: center;'>";
    echo "<h3>❌ Erreur de connexion à la base de données</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>