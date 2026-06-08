/* =============================================
   comptes.js — JavaScript de comptes.php
   ============================================= */

function ouvrirMdp(id, login) {
  document.getElementById('modal-id').value = id;
  document.getElementById('modal-login').textContent = login;
  document.getElementById('overlay').classList.add('active');
  setTimeout(() => document.getElementById('mdp-input').focus(), 100);
}

function fermerModal() {
  document.getElementById('overlay').classList.remove('active');
}

document.getElementById('overlay').addEventListener('click', e => {
  if (e.target === document.getElementById('overlay')) fermerModal();
});
