
document.addEventListener('DOMContentLoaded', () => {
    const sf6Input    = document.getElementById('sf6_file');
    const dropZone    = document.getElementById('dropZone');
    const fileName    = document.getElementById('fileName');
    const fileNameTxt = document.getElementById('fileNameText');
    const sf6Form     = document.getElementById('sf6Form');
    const previewBtn  = document.getElementById('previewBtn');

    ['dragenter', 'dragover'].forEach(evt =>
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        })
    );

    ['dragleave', 'drop'].forEach(evt =>
        dropZone.addEventListener(evt, e => {
            e.preventDefault();
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        })
    );

    dropZone.addEventListener('drop', e => {
        const files = e.dataTransfer.files;
        if (files.length) {
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            sf6Input.files = dt.files;
            showFileName(files[0].name);
        }
    });

    sf6Input.addEventListener('change', () => {
        if (sf6Input.files.length) showFileName(sf6Input.files[0].name);
    });

    function showFileName(name) {
        fileNameTxt.textContent = name;
        fileName.classList.remove('hidden');
    }

    sf6Form.addEventListener('submit', async e => {
        e.preventDefault();

        const formData = new FormData(sf6Form);

        previewBtn.disabled    = true;
        previewBtn.textContent = 'Reading file…';

        try {
            const res  = await fetch(sf6Form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();

            if (!json.success) {
                alert(json.message || 'Failed to read the file.');
                return;
            }

            renderPreview(json.data, json.sy_id, formData.get('sf6_file'));
        } catch (err) {
            alert('An error occurred while reading the file.');
            console.error(err);
        } finally {
            previewBtn.disabled    = false;
            previewBtn.textContent = 'Preview Extracted Data';
        }
    });

    // ── Render preview table ───────────────────────────────────
    const gradeLabels = {
        K:   'Kindergarten',
        g1:  'Grade 1',
        g2:  'Grade 2',
        g3:  'Grade 3',
        g4:  'Grade 4',
        g5:  'Grade 5',
        g6:  'Grade 6',
        g7:  'Grade 7',
        g8:  'Grade 8',
        g9:  'Grade 9',
        g10: 'Grade 10',
        g11: 'Grade 11',
        g12: 'Grade 12',
    };

    function renderPreview(data, syId, file) {
        const tbody = document.getElementById('previewTableBody');
        tbody.innerHTML = '';

        let totalMale = 0, totalFemale = 0, totalAll = 0;

        Object.entries(data).forEach(([grade, counts]) => {
            const { male, female, total } = counts;
            totalMale   += male;
            totalFemale += female;
            totalAll    += total;

            tbody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-2 font-medium text-gray-700">
                        ${gradeLabels[grade] ?? grade}
                    </td>
                    <td class="px-4 py-2 text-center text-blue-600 font-semibold">${male}</td>
                    <td class="px-4 py-2 text-center text-pink-600 font-semibold">${female}</td>
                    <td class="px-4 py-2 text-center text-gray-800 font-bold">${total}</td>
                </tr>
            `);
        });

        // Totals row
        tbody.insertAdjacentHTML('beforeend', `
            <tr class="bg-blue-50 border-t-2 border-blue-200">
                <td class="px-4 py-2 font-bold text-blue-800">TOTAL</td>
                <td class="px-4 py-2 text-center font-bold text-blue-700">${totalMale}</td>
                <td class="px-4 py-2 text-center font-bold text-blue-700">${totalFemale}</td>
                <td class="px-4 py-2 text-center font-bold text-blue-900">${totalAll}</td>
            </tr>
        `);

        // Attach file + sy_id to the confirm form
        document.getElementById('hiddenSyId').value = syId;
        const hiddenFile = document.getElementById('hiddenFile');
        const dt = new DataTransfer();
        dt.items.add(file);
        hiddenFile.files = dt.files;

        // Show preview section
        document.getElementById('previewSection').classList.remove('hidden');
        document.getElementById('previewSection').scrollIntoView({ behavior: 'smooth' });
    }

    // ── Reset ──────────────────────────────────────────────────
    window.resetForm = function () {
        sf6Form.reset();
        fileName.classList.add('hidden');
        document.getElementById('previewSection').classList.add('hidden');
        document.getElementById('previewTableBody').innerHTML = '';
    };
});
