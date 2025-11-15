// assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Validation des formulaires Bootstrap
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Calcul du prix de réservation
    const typeChambreSelect = document.getElementById('type_chambre');
    const dateArriveeInput = document.getElementById('date_arrivee');
    const dateDepartInput = document.getElementById('date_depart');
    const resumeReservation = document.getElementById('resume-reservation');
    const detailsReservation = document.getElementById('details-reservation');
    const prixTotal = document.getElementById('prix-total');
    
    function calculerPrix() {
        if (typeChambreSelect.value && dateArriveeInput.value && dateDepartInput.value) {
            const dateArrivee = new Date(dateArriveeInput.value);
            const dateDepart = new Date(dateDepartInput.value);
            const differenceTemps = dateDepart.getTime() - dateArrivee.getTime();
            const nuits = Math.ceil(differenceTemps / (1000 * 3600 * 24));
            
            if (nuits > 0) {
                const prixNuit = parseFloat(typeChambreSelect.selectedOptions[0].dataset.prix);
                const total = nuits * prixNuit;
                
                detailsReservation.textContent = `${typeChambreSelect.value} pour ${nuits} nuit(s)`;
                prixTotal.textContent = `Total: ${total.toFixed(2)} €`;
                resumeReservation.style.display = 'block';
            } else {
                resumeReservation.style.display = 'none';
            }
        } else {
            resumeReservation.style.display = 'none';
        }
    }
    
    if (typeChambreSelect && dateArriveeInput && dateDepartInput) {
        typeChambreSelect.addEventListener('change', calculerPrix);
        dateArriveeInput.addEventListener('change', calculerPrix);
        dateDepartInput.addEventListener('change', calculerPrix);
    }
    
    // Définir la date minimale pour les champs de date
    const today = new Date().toISOString().split('T')[0];
    if (dateArriveeInput) {
        dateArriveeInput.min = today;
    }
    if (dateDepartInput) {
        dateDepartInput.min = today;
    }
    
    // Empêcher la sélection de dates de départ antérieures aux dates d'arrivée
    if (dateArriveeInput && dateDepartInput) {
        dateArriveeInput.addEventListener('change', function() {
            dateDepartInput.min = this.value;
            if (dateDepartInput.value && dateDepartInput.value < this.value) {
                dateDepartInput.value = '';
            }
            calculerPrix();
        });
    }
});

// assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    // Animation au défilement
    const fadeElements = document.querySelectorAll('.fade-in');
    
    const fadeInOnScroll = () => {
        fadeElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('visible');
            }
        });
    };
    
    window.addEventListener('scroll', fadeInOnScroll);
    fadeInOnScroll(); // Initial check
    
    // Navigation scroll effect
    const navbar = document.querySelector('.custom-navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Validation des formulaires Bootstrap
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Calcul du prix de réservation avec options jour/nuit
    const typeChambreSelect = document.getElementById('type_chambre');
    const typeReservationSelect = document.getElementById('type_reservation');
    const dateArriveeInput = document.getElementById('date_arrivee');
    const dateDepartInput = document.getElementById('date_depart');
    const heureArriveeInput = document.getElementById('heure_arrivee');
    const heureDepartInput = document.getElementById('heure_depart');
    const resumeReservation = document.getElementById('resume-reservation');
    const detailsReservation = document.getElementById('details-reservation');
    const prixTotal = document.getElementById('prix-total');
    
    function calculerPrix() {
        if (typeChambreSelect.value && typeReservationSelect.value) {
            let total = 0;
            let details = '';
            
            const prixNuit = parseFloat(typeChambreSelect.selectedOptions[0].dataset.prixNuit);
            const prixJour = parseFloat(typeChambreSelect.selectedOptions[0].dataset.prixJour);
            
            if (typeReservationSelect.value === 'nuit') {
                if (dateArriveeInput.value && dateDepartInput.value) {
                    const dateArrivee = new Date(dateArriveeInput.value);
                    const dateDepart = new Date(dateDepartInput.value);
                    const differenceTemps = dateDepart.getTime() - dateArrivee.getTime();
                    const nuits = Math.ceil(differenceTemps / (1000 * 3600 * 24));
                    
                    if (nuits > 0) {
                        total = nuits * prixNuit;
                        details = `${typeChambreSelect.value} pour ${nuits} nuit(s)`;
                    }
                }
            } else if (typeReservationSelect.value === 'jour') {
                if (dateArriveeInput.value && heureArriveeInput.value && heureDepartInput.value) {
                    total = prixJour;
                    details = `${typeChambreSelect.value} - Réservation journée`;
                }
            }
            
            if (total > 0) {
                detailsReservation.textContent = details;
                prixTotal.textContent = `Total: ${total.toLocaleString('fr-FR')} FCFA`;
                resumeReservation.style.display = 'block';
            } else {
                resumeReservation.style.display = 'none';
            }
        } else {
            resumeReservation.style.display = 'none';
        }
    }
    
    // Gestion de l'affichage des champs selon le type de réservation
    function toggleReservationFields() {
        const typeReservation = typeReservationSelect.value;
        const nuitFields = document.getElementById('nuit-fields');
        const jourFields = document.getElementById('jour-fields');
        
        if (typeReservation === 'nuit') {
            nuitFields.style.display = 'block';
            jourFields.style.display = 'none';
        } else if (typeReservation === 'jour') {
            nuitFields.style.display = 'none';
            jourFields.style.display = 'block';
        } else {
            nuitFields.style.display = 'none';
            jourFields.style.display = 'none';
        }
        calculerPrix();
    }
    
    if (typeReservationSelect) {
        typeReservationSelect.addEventListener('change', toggleReservationFields);
        toggleReservationFields(); // Initial call
    }
    
    if (typeChambreSelect) {
        typeChambreSelect.addEventListener('change', calculerPrix);
    }
    if (dateArriveeInput) {
        dateArriveeInput.addEventListener('change', calculerPrix);
    }
    if (dateDepartInput) {
        dateDepartInput.addEventListener('change', calculerPrix);
    }
    if (heureArriveeInput) {
        heureArriveeInput.addEventListener('change', calculerPrix);
    }
    if (heureDepartInput) {
        heureDepartInput.addEventListener('change', calculerPrix);
    }
    
    // Définir la date minimale pour les champs de date
    const today = new Date().toISOString().split('T')[0];
    if (dateArriveeInput) {
        dateArriveeInput.min = today;
    }
    if (dateDepartInput) {
        dateDepartInput.min = today;
    }
    
    // Empêcher la sélection de dates de départ antérieures aux dates d'arrivée
    if (dateArriveeInput && dateDepartInput) {
        dateArriveeInput.addEventListener('change', function() {
            dateDepartInput.min = this.value;
            if (dateDepartInput.value && dateDepartInput.value < this.value) {
                dateDepartInput.value = '';
            }
            calculerPrix();
        });
    }
});

// Filtrage des chambres
function filterRooms() {
    const typeFilter = document.getElementById('filterType').value;
    const capacityFilter = document.getElementById('filterCapacity').value;
    const priceFilter = document.getElementById('filterPrice').value;
    
    const rooms = document.querySelectorAll('.room-item');
    
    rooms.forEach(room => {
        const roomType = room.getAttribute('data-type');
        const roomCapacity = parseInt(room.getAttribute('data-capacity'));
        const roomPrice = parseInt(room.getAttribute('data-price'));
        
        let showRoom = true;
        
        // Filtre par type
        if (typeFilter !== 'all' && !roomType.includes(typeFilter)) {
            showRoom = false;
        }
        
        // Filtre par capacité
        if (capacityFilter !== 'all') {
            const capacity = parseInt(capacityFilter);
            if (capacity === 4) {
                if (roomCapacity < 4) showRoom = false;
            } else if (roomCapacity !== capacity) {
                showRoom = false;
            }
        }
        
        // Filtre par prix
        if (priceFilter !== 'all') {
            const maxPrice = parseInt(priceFilter);
            if (roomPrice > maxPrice) showRoom = false;
        }
        
        if (showRoom) {
            room.style.display = 'block';
            room.classList.add('fade-in');
        } else {
            room.style.display = 'none';
        }
    });
    
    // Réinitialiser les animations
    const fadeElements = document.querySelectorAll('.fade-in');
    fadeElements.forEach(el => {
        const elementTop = el.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < window.innerHeight - elementVisible) {
            el.classList.add('visible');
        }
    });
}