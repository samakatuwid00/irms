document.addEventListener('DOMContentLoaded', () => {

    // ── Server data injected by Blade ──────────────────────────────────────
    const { divisions, districts, schools, usertypes, old: oldData } = window.__REGISTER__ ?? {};

    // ── Elements ───────────────────────────────────────────────────────────
    const form        = document.getElementById('registerForm');
    const submitBtn   = document.getElementById('submitBtn');
    const contactField = document.getElementById('contact_number');

    const authorityLevel  = document.getElementById('authority_level');
    const usertypeSelect  = document.querySelector('select[name="usertype"]');

    const regionWrapper   = document.getElementById('regionWrapper');
    const divisionWrapper = document.getElementById('divisionWrapper');
    const districtWrapper = document.getElementById('districtWrapper');
    const schoolWrapper   = document.getElementById('schoolWrapper');

    const regionSelect   = document.getElementById('region');
    const divisionSelect = document.getElementById('division');
    const districtSelect = document.getElementById('district');
    const schoolSelect   = document.getElementById('school');

    // ── Flash Messages ────────────────────────────────────────────────────

    window.closeFlash = function (id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.style.transition = 'all 0.4s ease';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-10px)';
        setTimeout(() => el.remove(), 400);
    };

    const successMsg = document.getElementById('successMessage');
    if (successMsg) setTimeout(() => closeFlash('successMessage'), 6000);

    // ── Password Toggle ───────────────────────────────────────────────────

    const togglePassword = (fieldId, toggleId) => {
        const field  = document.getElementById(fieldId);
        const toggle = document.getElementById(toggleId);
        if (!field || !toggle) return;

        toggle.addEventListener('click', () => {
            field.type = field.type === 'password' ? 'text' : 'password';
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });
    };

    togglePassword('password', 'passwordToggle');
    togglePassword('confirmPassword', 'confirmPasswordToggle');

    // ── Helpers ───────────────────────────────────────────────────────────

    const hideAllWrappers = () => {
        [regionWrapper, divisionWrapper, districtWrapper, schoolWrapper]
            .forEach(el => el?.classList.add('hidden'));
    };

    const populateSelect = (select, items, textKey, valueKey) => {
        if (!select) return;
        const label = select.name.charAt(0).toUpperCase() + select.name.slice(1);
        select.innerHTML = `<option selected disabled>Select ${label}</option>`;
        items.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item[valueKey];
            opt.textContent = item[textKey];
            select.appendChild(opt);
        });
    };

    const filterUserTypesByLevel = (level) => {
        const filtered = (usertypes ?? []).filter(u => u.level == level);
        populateSelect(usertypeSelect, filtered, 'type_name', 'id');
    };

    const applyAuthorityLevel = (level) => {
        hideAllWrappers();

        if (level === '4') regionWrapper?.classList.remove('hidden');
        if (level === '3') {
            regionWrapper?.classList.remove('hidden');
            divisionWrapper?.classList.remove('hidden');
        }
        if (level === '2') {
            regionWrapper?.classList.remove('hidden');
            divisionWrapper?.classList.remove('hidden');
            districtWrapper?.classList.remove('hidden');
        }
        if (level === '1') {
            regionWrapper?.classList.remove('hidden');
            divisionWrapper?.classList.remove('hidden');
            districtWrapper?.classList.remove('hidden');
            schoolWrapper?.classList.remove('hidden');
        }

        filterUserTypesByLevel(level);
    };

    // ── Contact Number Formatter ──────────────────────────────────────────

    const formatContactNumber = (value) => {
        value = value.replace(/\D/g, '').slice(0, 11);

        if (value.length > 6) return value.replace(/(\d{4})(\d{3})(\d{0,4})/, '$1-$2-$3');
        if (value.length > 3) return value.replace(/(\d{4})(\d{0,3})/, '$1-$2');
        return value;
    };

    contactField?.addEventListener('input', function () {
        this.value = formatContactNumber(this.value);
    });

    // ── Cascade Dropdowns ─────────────────────────────────────────────────

    authorityLevel?.addEventListener('change', function () {
        applyAuthorityLevel(this.value);
    });

    regionSelect?.addEventListener('change', () => {
        populateSelect(
            divisionSelect,
            (divisions ?? []).filter(d => d.region_id == regionSelect.value),
            'division_name', 'id'
        );
        districtSelect.innerHTML = '<option selected disabled>Select District</option>';
        schoolSelect.innerHTML   = '<option selected disabled>Select School</option>';
    });

    divisionSelect?.addEventListener('change', () => {
        populateSelect(
            districtSelect,
            (districts ?? []).filter(d => d.division_id == divisionSelect.value),
            'district_name', 'id'
        );
        schoolSelect.innerHTML = '<option selected disabled>Select School</option>';
    });

    districtSelect?.addEventListener('change', () => {
        populateSelect(
            schoolSelect,
            (schools ?? []).filter(s => s.district_id == districtSelect.value),
            'school_name', 'id'
        );
    });

    // ── Restore Old Input (after validation error) ────────────────────────

    if (oldData?.authority_level) {
        authorityLevel.value = oldData.authority_level;
        applyAuthorityLevel(oldData.authority_level);
    }

    if (oldData?.region) {
        regionSelect.value = oldData.region;
        populateSelect(
            divisionSelect,
            (divisions ?? []).filter(d => d.region_id == oldData.region),
            'division_name', 'id'
        );
    }

    if (oldData?.division) {
        divisionSelect.value = oldData.division;
        populateSelect(
            districtSelect,
            (districts ?? []).filter(d => d.division_id == oldData.division),
            'district_name', 'id'
        );
    }

    if (oldData?.district) {
        districtSelect.value = oldData.district;
        populateSelect(
            schoolSelect,
            (schools ?? []).filter(s => s.district_id == oldData.district),
            'school_name', 'id'
        );
    }

    if (oldData?.school)   schoolSelect.value   = oldData.school;
    if (oldData?.usertype) usertypeSelect.value  = oldData.usertype;

    if (contactField && oldData?.contact_number) {
        contactField.value = formatContactNumber(oldData.contact_number);
    }

    // ── Form Submit ───────────────────────────────────────────────────────

    form?.addEventListener('submit', function (e) {

        if (!document.querySelector('input[name="agree"]').checked) {
            e.preventDefault();
            alert('You must agree to the Privacy and Terms to register.');
            return;
        }

        if (!confirm('Are you sure you want to submit the registration?')) {
            e.preventDefault();
            return;
        }

        // Strip formatting before sending to server
        if (contactField) {
            contactField.value = contactField.value.replace(/\D/g, '');
        }

        submitBtn.disabled    = true;
        submitBtn.textContent = 'Submitting...';
    });

});
