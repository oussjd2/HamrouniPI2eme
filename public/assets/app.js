'use strict';
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); });
});
document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', event => {
        let error = '';
        const value = name => form.elements.namedItem(name)?.value.trim() ?? '';
        const mode = form.dataset.validate;
        const required = {login:['email','mot_de_passe'],register:['nom','prenom','email','mot_de_passe'],utilisateurs:['nom','prenom','email'],categories:['nom'],equipements:['nom','categorie_id','tarif_journalier','quantite_totale','quantite_maintenance','quantite_endommagee','seuil_alerte'],location:['equipement_id','quantite','date_debut','date_fin'],retour:['retour_maintenance','retour_endommage']}[mode] ?? [];
        if (required.some(name => !value(name))) error = 'Veuillez compléter tous les champs obligatoires.';
        if (!error && value('email') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value('email'))) error = 'Vérifiez votre adresse e-mail.';
        if (!error && value('mot_de_passe') && mode !== 'login' && (new TextEncoder().encode(value('mot_de_passe')).length < 8 || new TextEncoder().encode(value('mot_de_passe')).length > 72)) error = 'Mot de passe : entre 8 et 72 caractères.';
        const ints = ['quantite','quantite_totale','quantite_maintenance','quantite_endommagee','seuil_alerte','retour_maintenance','retour_endommage'];
        if (!error && ints.some(name => form.elements.namedItem(name) && (!/^\d+$/.test(value(name)) || (name === 'quantite' && Number(value(name)) < 1)))) error = 'Vérifiez les quantités : des entiers positifs sont attendus.';
        if (!error && value('date_debut') && value('date_fin') && value('date_fin') < value('date_debut')) error = 'La date de fin doit suivre la date de début.';
        if (error) { event.preventDefault(); form.querySelector('.form-error').textContent = error; }
    });
});
const rental = document.querySelector('form[data-validate="location"]');
if (rental) {
    const updateEstimate = () => {
        const start = rental.elements.date_debut.value;
        const end = rental.elements.date_fin.value;
        const price = Number(rental.elements.equipement_id.selectedOptions[0]?.dataset.price ?? 0);
        const quantity = Number(rental.elements.quantite.value);
        const days = Math.round((Date.parse(end) - Date.parse(start)) / 86400000) + 1;
        const output = document.getElementById('rental-estimate');
        output.textContent = price > 0 && quantity > 0 && days > 0 ? `${(Math.round(price * 100) * quantity * days / 100).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ${document.documentElement.dataset.currency || 'TND'}` : '—';
    };
    rental.addEventListener('input', updateEstimate); rental.addEventListener('change', updateEstimate); updateEstimate();
}
const photoInput = document.getElementById('product-photo');
if (photoInput) {
    const form = photoInput.form;
    const preview = document.getElementById('photo-preview');
    const original = preview.getAttribute('src');
    const remove = form.elements.namedItem('remove_photo');
    photoInput.addEventListener('change', () => {
        const file = photoInput.files[0];
        const error = form.querySelector('.form-error');
        error.textContent = '';
        if (!file) { preview.src = remove?.checked ? 'assets/product-placeholder.svg' : original; return; }
        if (file.size > 2 * 1024 * 1024 || (file.type && !['image/jpeg', 'image/png', 'image/webp'].includes(file.type))) {
            error.textContent = 'Photo : choisissez un fichier JPG, PNG ou WebP de 2 Mo maximum.';
            photoInput.value = '';
            preview.src = remove?.checked ? 'assets/product-placeholder.svg' : original;
            return;
        }
        if (remove) remove.checked = false;
        const reader = new FileReader();
        reader.addEventListener('load', () => { preview.src = reader.result; });
        reader.readAsDataURL(file);
    });
    remove?.addEventListener('change', () => {
        if (remove.checked) { photoInput.value = ''; preview.src = 'assets/product-placeholder.svg'; }
        else preview.src = original;
    });
}
