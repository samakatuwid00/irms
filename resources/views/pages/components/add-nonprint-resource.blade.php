<!-- Non-Print Resource Form -->
<form id="nonprint" class="resource-form space-y-8" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- =========================
        1ST GROUP
    ========================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">

        <!-- LEFT: IMAGE -->
        <div class="h-full">
            <div class="h-full flex flex-col items-center justify-between
                        border-2 border-dashed border-blue-500 rounded-lg
                        p-4 text-center">

                <img
                    id="imagePreview"
                    src="{{ asset('assets/images/default.jpg') }}"
                    alt="Image preview"
                    class="w-full flex-1 object-cover rounded mb-4"
                >

                <input
                    type="file"
                    name="image"
                    id="imageUpload"
                    class="hidden"
                    accept="image/*"
                >

                <label
                    for="imageUpload"
                    class="cursor-pointer px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                >
                    Choose Image
                </label>

                <p class="text-xs text-gray-500 mt-2">
                    JPG, PNG • Max 5MB
                </p>
            </div>
        </div>

        <!-- RIGHT: INPUTS -->
        <div class="md:col-span-2 space-y-6">

            <!-- Title / Type / Brand -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Title</label>
                    <input type="text" name="title" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Type</label>
                    <select name="type" class="w-full border rounded px-3 py-2" required>
                        <option value="" disabled selected>Select type</option>
                        <option>Equipment</option>
                        <option>Furniture</option>
                        <option>ICT Device</option>
                        <option>Software</option>
                        <option>Teaching Aid</option>
                        <option>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Brand</label>
                    <input type="text" name="brand" class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <!-- Code / Version / Model -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Code</label>
                    <input type="text" name="code" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Version</label>
                    <input type="text" name="version" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Model</label>
                    <input type="text" name="model" class="w-full border rounded px-3 py-2">
                </div>
            </div>

            <!-- URL / Size -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">URL <span class="text-xs text-gray-500">(if applicable)</span></label>
                    <input type="url" name="url" placeholder="https://example.com" class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Size <span class="text-xs text-gray-500">(e.g., dimensions or file size)</span></label>
                    <input type="text" name="size" placeholder="e.g., 50x30 cm or 2.4 GB" class="w-full border rounded px-3 py-2">
                </div>
            </div>

        </div>
    </div>

    {{-- =========================
        2ND GROUP
    ========================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="md:col-span-2 space-y-4">
            <!-- Tabs -->
            <div class="flex gap-6 border-b">
                <button type="button" class="tab-btn active pb-2 border-b-2 border-blue-600 text-blue-600" data-tab="primary">Primary</button>
                <button type="button" class="tab-btn pb-2 text-gray-600" data-tab="secondary">Secondary</button>
                <button type="button" class="tab-btn pb-2 text-gray-600" data-tab="sh">Senior High</button>
            </div>

            <!-- PRIMARY -->
            <div class="tab-content" id="primary">
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 text-left w-48">Level</th>
                            <th class="border text-center">K</th>
                            <th class="border text-center">1</th>
                            <th class="border text-center">2</th>
                            <th class="border text-center">3</th>
                            <th class="border text-center">4</th>
                            <th class="border text-center">5</th>
                            <th class="border text-center">6</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border px-3 py-2">Assigned to Level</td>
                            @for ($i = 0; $i <= 6; $i++)
                                <td class="border text-center">
                                    <input type="checkbox" name="levels[primary][{{ $i }}]">
                                </td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SECONDARY -->
            <div class="tab-content hidden" id="secondary">
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 text-left w-48">Level</th>
                            <th class="border text-center">7</th>
                            <th class="border text-center">8</th>
                            <th class="border text-center">9</th>
                            <th class="border text-center">10</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border px-3 py-2">Assigned to Level</td>
                            @for ($i = 7; $i <= 10; $i++)
                                <td class="border text-center">
                                    <input type="checkbox" name="levels[secondary][{{ $i }}]">
                                </td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- SENIOR HIGH -->
            <div class="tab-content hidden" id="sh">
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-3 py-2 text-left w-48">Level</th>
                            <th class="border text-center">11</th>
                            <th class="border text-center">12</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border px-3 py-2">Assigned to Level</td>
                            @for ($i = 11; $i <= 12; $i++)
                                <td class="border text-center">
                                    <input type="checkbox" name="levels[sh][{{ $i }}]">
                                </td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- =========================
        3RD GROUP (ACQUISITION & CONDITION)
    ========================== --}}
    <div class="bg-gray-50 border rounded-xl p-6 space-y-6">

        <h3 class="text-lg font-semibold text-gray-700">
            Acquisition & Condition Details
        </h3>

        <div class="flex justify-end">
            <button type="button"
                    id="addAcquisitionBtn"
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                ➕ Add Acquisition
            </button>
        </div>

        <!-- Remarks (per acquisition) -->
        <div>
            <label class="block text-sm font-medium mb-1">
                Remarks <span class="text-xs text-gray-500">(will be saved with each acquisition)</span>
            </label>
            <textarea name="remarks" rows="4" class="w-full border rounded px-3 py-2"
                      placeholder="Any notes, specifications, condition details, or special remarks for this batch..."></textarea>
        </div>

        <!-- TOP ROW -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Source</label>
                <select id="source" name="source" class="w-full border rounded px-3 py-2">
                    <option value="" selected disabled>Select source</option>
                    <option value="DepEd">DepEd</option>
                    <option value="Donation">Donation</option>
                    <option value="Purchased">Purchased</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Date Acquired</label>
                <input type="date" name="date_acquired" class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Cost</label>
                <input type="number" step="0.01" name="cost" class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">IAR / Property No.</label>
                <input type="text" name="iar" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <!-- CONDITION QUANTITY -->
        <div>
            <h4 class="text-sm font-semibold mb-3 text-gray-600">
                Condition & Quantity
            </h4>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div>
                    <label class="block text-xs mb-1">Working / Usable</label>
                    <input type="number" name="usable" value="0" min="0" class="qty w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs mb-1">Needs Repair</label>
                    <input type="number" name="needs_repair" value="0" min="0" class="qty w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs mb-1">Damaged</label>
                    <input type="number" name="damaged" value="0" min="0" class="qty w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs mb-1">Lost / Missing</label>
                    <input type="number" name="lost" value="0" min="0" class="qty w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label class="block text-xs mb-1">Condemnable</label>
                    <input type="number" name="condemnable" value="0" min="0" class="qty w-full border rounded px-3 py-2">
                </div>

                <div class="md:col-span-1 lg:col-span-1">
                    <label class="block text-xs mb-1">Total Quantity</label>
                    <input type="number" name="total_quantity" id="totalQuantity" readonly
                           class="w-full bg-gray-100 border rounded px-3 py-2 font-semibold">
                </div>
            </div>
        </div>
    </div>

    {{-- =========================
        ACQUISITION LIST - Shows Remarks
    ========================== --}}
    <div class="mt-6">
        <h3 class="text-lg font-semibold mb-3 text-gray-700">Acquisition List</h3>
        <div class="overflow-x-auto">
            <table class="w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border px-2 py-1">Source</th>
                        <th class="border px-2 py-1">Date</th>
                        <th class="border px-2 py-1">Cost</th>
                        <th class="border px-2 py-1">IAR / Prop No.</th>
                        <th class="border px-2 py-1">Remarks</th>
                        <th class="border px-2 py-1">Usable</th>
                        <th class="border px-2 py-1">Repair</th>
                        <th class="border px-2 py-1">Damaged</th>
                        <th class="border px-2 py-1">Lost</th>
                        <th class="border px-2 py-1">Cond.</th>
                        <th class="border px-2 py-1">Total</th>
                        <th class="border px-2 py-1">Actions</th>
                    </tr>
                </thead>
                <tbody id="acquisitionTableBody">
                    <tr><td colspan="12" class="text-center text-gray-400 py-3">No acquisitions added</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <input type="hidden" name="acquisitions" id="acquisitionsInput">

    <!-- SUBMIT -->
    <div class="flex justify-end mt-8">
        <button class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Save Non-Print Resource
        </button>
    </div>
</form>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('nonprint');

        // IMAGE PREVIEW
        const imageUpload = form.querySelector('#imageUpload');
        const imagePreview = form.querySelector('#imagePreview');

        imageUpload.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file.');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image size must be less than 5MB.');
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => imagePreview.src = e.target.result;
                reader.readAsDataURL(file);
            }
        });

        // TABS
        const tabs = form.querySelectorAll('.tab-btn');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => {
                    t.classList.remove('border-b-2', 'border-blue-600', 'text-blue-600');
                    t.classList.add('text-gray-600');
                });
                form.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                tab.classList.add('border-b-2', 'border-blue-600', 'text-blue-600');
                form.querySelector(`#${tab.dataset.tab}`).classList.remove('hidden');
            });
        });

        // QUANTITY TOTAL CALCULATION
        const qtyInputs = form.querySelectorAll('.qty');
        const totalField = form.querySelector('#totalQuantity');
        const calculateTotal = () => {
            let total = 0;
            qtyInputs.forEach(input => total += parseInt(input.value) || 0);
            totalField.value = total;
        };
        qtyInputs.forEach(input => input.addEventListener('input', calculateTotal));

        // ACQUISITIONS MANAGEMENT
        let acquisitions = [];
        let editIndex = null;

        const fields = {
            source: () => form.querySelector('[name="source"]').value,
            date_acquired: () => form.querySelector('[name="date_acquired"]').value,
            cost: () => form.querySelector('[name="cost"]').value,
            iar: () => form.querySelector('[name="iar"]').value,
            remarks: () => form.querySelector('[name="remarks"]').value.trim(),
            usable: () => form.querySelector('[name="usable"]').value,
            needs_repair: () => form.querySelector('[name="needs_repair"]').value,
            damaged: () => form.querySelector('[name="damaged"]').value,
            lost: () => form.querySelector('[name="lost"]').value,
            condemnable: () => form.querySelector('[name="condemnable"]').value,
            total_quantity: () => totalField.value,
        };

        const acquisitionTableBody = form.querySelector('#acquisitionTableBody');
        const acquisitionsInput = form.querySelector('#acquisitionsInput');

        const renderAcquisitions = () => {
            acquisitionTableBody.innerHTML = '';
            if (acquisitions.length === 0) {
                acquisitionTableBody.innerHTML = `<tr><td colspan="12" class="text-center text-gray-400 py-3">No acquisitions added</td></tr>`;
                return;
            }

            acquisitions.forEach((a, index) => {
                const shortRemark = a.remarks.length > 40 ? a.remarks.substring(0, 37) + '...' : (a.remarks || '-');
                acquisitionTableBody.innerHTML += `
                    <tr>
                        <td class="border px-2 py-1">${a.source}</td>
                        <td class="border px-2 py-1">${a.date_acquired}</td>
                        <td class="border px-2 py-1">${a.cost || ''}</td>
                        <td class="border px-2 py-1">${a.iar || ''}</td>
                        <td class="border px-2 py-1 text-xs">${shortRemark}</td>
                        <td class="border px-2 py-1">${a.usable}</td>
                        <td class="border px-2 py-1">${a.needs_repair || 0}</td>
                        <td class="border px-2 py-1">${a.damaged}</td>
                        <td class="border px-2 py-1">${a.lost}</td>
                        <td class="border px-2 py-1">${a.condemnable}</td>
                        <td class="border px-2 py-1 font-semibold">${a.total_quantity}</td>
                        <td class="border px-2 py-1 text-center">
                            <div class="flex justify-center gap-2">
                                <button type="button" onclick="editAcquisition(${index})" class="p-1 rounded hover:bg-blue-100 text-blue-600" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                                    </svg>
                                </button>
                                <button type="button" onclick="deleteAcquisition(${index})" class="p-1 rounded hover:bg-red-100 text-red-600" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862 a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6 M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>`;
            });
        };

        const resetAcquisitionForm = () => {
            form.querySelector('[name="source"]').value = '';
            form.querySelector('[name="date_acquired"]').value = '';
            form.querySelector('[name="cost"]').value = '';
            form.querySelector('[name="iar"]').value = '';
            form.querySelector('[name="remarks"]').value = '';
            form.querySelector('[name="usable"]').value = 0;
            form.querySelector('[name="needs_repair"]').value = 0;
            form.querySelector('[name="damaged"]').value = 0;
            form.querySelector('[name="lost"]').value = 0;
            form.querySelector('[name="condemnable"]').value = 0;
            totalField.value = 0;
        };

        form.querySelector('#addAcquisitionBtn').addEventListener('click', () => {
            const acquisition = {};
            for (const key in fields) acquisition[key] = fields[key]();

            if (!acquisition.source || !acquisition.date_acquired) {
                alert('Source and Date Acquired are required.');
                return;
            }
            if ((parseInt(acquisition.total_quantity) || 0) < 1) {
                alert('Total Quantity must be at least 1.');
                return;
            }

            if (editIndex !== null) {
                acquisitions[editIndex] = acquisition;
                editIndex = null;
            } else {
                acquisitions.push(acquisition);
            }

            renderAcquisitions();
            resetAcquisitionForm();
        });

        window.editAcquisition = (index) => {
            const a = acquisitions[index];
            editIndex = index;
            form.querySelector('[name="source"]').value = a.source;
            form.querySelector('[name="date_acquired"]').value = a.date_acquired;
            form.querySelector('[name="cost"]').value = a.cost || '';
            form.querySelector('[name="iar"]').value = a.iar || '';
            form.querySelector('[name="remarks"]').value = a.remarks || '';
            form.querySelector('[name="usable"]').value = a.usable;
            form.querySelector('[name="needs_repair"]').value = a.needs_repair || 0;
            form.querySelector('[name="damaged"]').value = a.damaged;
            form.querySelector('[name="lost"]').value = a.lost;
            form.querySelector('[name="condemnable"]').value = a.condemnable;
            calculateTotal();
            form.querySelector('[name="source"]').scrollIntoView({ behavior: 'smooth' });
        };

        window.deleteAcquisition = (index) => {
            if (confirm('Delete this acquisition?')) {
                acquisitions.splice(index, 1);
                renderAcquisitions();
            }
        };

        form.addEventListener('submit', () => {
            acquisitionsInput.value = JSON.stringify(acquisitions);
        });
    });
</script>
