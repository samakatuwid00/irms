
const modal          = document.getElementById('privacyModal');
const openModalBtn   = document.getElementById('openPrivacyModal');
const closeBtns      = document.querySelectorAll('#closePrivacyModal, #closePrivacyModalBottom');
const privacyTab     = document.getElementById('privacyTab');
const termsTab       = document.getElementById('termsTab');
const privacyContent = document.getElementById('privacyContent');
const termsContent   = document.getElementById('termsContent');

openModalBtn?.addEventListener('click', () => modal.classList.remove('hidden'));

closeBtns.forEach((btn) =>
  btn.addEventListener('click', () => modal.classList.add('hidden'))
);

modal.addEventListener('click', (e) => {
  if (e.target === modal) modal.classList.add('hidden');
});

function switchTab(activeTab) {
  const isPrivacy = activeTab === 'privacy';

  privacyTab.classList.toggle('text-custom-teal',   isPrivacy);
  privacyTab.classList.toggle('border-custom-teal', isPrivacy);
  privacyTab.classList.toggle('border-b-4',         isPrivacy);
  privacyTab.classList.toggle('text-gray-600',      !isPrivacy);

  termsTab.classList.toggle('text-custom-teal',     !isPrivacy);
  termsTab.classList.toggle('border-custom-teal',   !isPrivacy);
  termsTab.classList.toggle('border-b-4',           !isPrivacy);
  termsTab.classList.toggle('text-gray-600',        isPrivacy);

  privacyContent.classList.toggle('hidden', !isPrivacy);
  termsContent.classList.toggle('hidden',    isPrivacy);
}

privacyTab.addEventListener('click', () => switchTab('privacy'));
termsTab.addEventListener('click',   () => switchTab('terms'));