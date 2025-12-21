    <!-- Print Resource Form -->
    <form id="print" class="resource-form space-y-8" method="POST" enctype="multipart/form-data">
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

                <!-- Title / Author / Publisher -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <input type="text" name="title" class="w-full border rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Author</label>
                        <input type="text" name="author" class="w-full border rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Publisher</label>
                        <input type="text" name="publisher" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <!-- Two Columns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Type</label>
                            <select name="type" class="w-full border rounded px-3 py-2">
                                <option>Select type</option>
                                <option>Book</option>
                                <option>Journal</option>
                                <option>Magazine</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Volume</label>
                            <input name="volume" class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Edition</label>
                            <input name="edition" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm mb-1">Copyright</label>
                            <input name="copyright" type="number" class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">ISBN</label>
                            <input name="isbn" class="w-full border rounded px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm mb-1">Pages</label>
                            <input name="pages" type="number" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- =========================
            2ND GROUP (EDGE-TO-EDGE)
        ========================== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div class="md:col-span-2 space-y-4">
                <!-- Tabs -->
                <div class="flex gap-6 border-b">
                    <button type="button" class="tab-btn active" data-tab="primary">Primary</button>
                    <button type="button" class="tab-btn" data-tab="secondary">Secondary</button>
                    <button type="button" class="tab-btn" data-tab="sh">Senior High</button>
                </div>

                <!-- PRIMARY -->
                <div class="tab-content" id="primary">
                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left w-48">Subject</th>
                                <th class="border">K</th>
                                <th class="border">1</th>
                                <th class="border">2</th>
                                <th class="border">3</th>
                                <th class="border">4</th>
                                <th class="border">5</th>
                                <th class="border">6</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['English','Math','Science','Filipino'] as $subject)
                            <tr>
                                <td class="border px-3 py-2">{{ $subject }}</td>
                                @for ($i = 0; $i <= 6; $i++)
                                    <td class="border text-center">
                                        <input type="checkbox"
                                            name="primary[{{ $subject }}][{{ $i }}]">
                                    </td>
                                @endfor
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- SECONDARY -->
                <div class="tab-content hidden" id="secondary">
                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left w-48">Subject</th>
                                <th class="border">7</th>
                                <th class="border">8</th>
                                <th class="border">9</th>
                                <th class="border">10</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['English','Math','Science','Filipino'] as $subject)
                            <tr>
                                <td class="border px-3 py-2">{{ $subject }}</td>
                                @for ($i = 0; $i < 4; $i++)
                                    <td class="border text-center">
                                        <input type="checkbox"
                                            name="primary[{{ $subject }}][{{ $i }}]">
                                    </td>
                                @endfor
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- SH -->
                <div class="tab-content hidden" id="sh">
                    <table class="w-full border text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2 text-left w-48">Subject</th>
                                <th class="border">11</th>
                                <th class="border">12</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (['English','Math','Science','Filipino'] as $subject)
                            <tr>
                                <td class="border px-3 py-2">{{ $subject }}</td>
                                @for ($i = 0; $i < 2; $i++)
                                    <td class="border text-center">
                                        <input type="checkbox"
                                            name="primary[{{ $subject }}][{{ $i }}]">
                                    </td>
                                @endfor
                            </tr>
                            @endforeach
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
                    <button type="button" id="addAcquisitionBtn"
                            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
                        ➕ Add Acquisition
                    </button>
                </div>

                <!-- Remarks -->
                <div>
                    <label class="block text-sm font-medium mb-1">
                        Remarks <span class="text-xs text-gray-500">(will be saved with each acquisition)</span>
                    </label>
                    <textarea name="remarks" rows="3" class="w-full border rounded px-3 py-2"
                            placeholder="Any notes, condition details, or special remarks for this batch..."></textarea>
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
                        <label class="block text-sm font-medium mb-1">IAR No.</label>
                        <input type="text" name="iar" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <!-- CONDITION QUANTITY (unchanged) -->
                <div>
                    <h4 class="text-sm font-semibold mb-3 text-gray-600">Condition & Quantity</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        <div><label class="block text-xs mb-1">Usable</label><input type="number" name="usable" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Partially Damaged</label><input type="number" name="partially_damaged" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Damaged</label><input type="number" name="damaged" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Lost</label><input type="number" name="lost" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div><label class="block text-xs mb-1">Condemnable</label><input type="number" name="condemnable" value="0" min="0" class="qty w-full border rounded px-3 py-2"></div>
                        <div class="md:col-span-2">
                            <label class="block text-xs mb-1">Total Quantity</label>
                            <input type="number" name="total_quantity" id="totalQuantity" readonly class="w-full bg-gray-100 border rounded px-3 py-2 font-semibold">
                        </div>
                    </div>
                </div>
            </div>

            {{-- =========================
                ACQUISITION LIST
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
                                <th class="border px-2 py-1">IAR</th>
                                <th class="border px-2 py-1">Remarks</th>
                                <th class="border px-2 py-1">Usable</th>
                                <th class="border px-2 py-1">PD</th>
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
        <div class="flex justify-end">
            <button class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Save Print Resource
            </button>
        </div>
    </form>

    <!-- JavaScript for Image Preview -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('print');

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
                    reader.onload = (e) => {
                        imagePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // TABS
            const tabs = form.querySelectorAll('.tab-btn');
            const contents = form.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => {
                        t.classList.remove('border-blue-600', 'text-blue-600');
                        t.classList.add('text-gray-600');
                    });
                    contents.forEach(c => c.classList.add('hidden'));

                    tab.classList.add('border-blue-600', 'text-blue-600');
                    form.querySelector(`#${tab.dataset.tab}`).classList.remove('hidden');
                });
            });

            // QUANTITY TOTAL CALCULATION
            const qtyInputs = form.querySelectorAll('.qty');
            const totalField = form.querySelector('#totalQuantity');

            const calculateTotal = () => {
                let total = 0;
                qtyInputs.forEach(input => {
                    total += parseInt(input.value) || 0;
                });
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
            partially_damaged: () => form.querySelector('[name="partially_damaged"]').value,
            damaged: () => form.querySelector('[name="damaged"]').value,
            lost: () => form.querySelector('[name="lost"]').value,
            condemnable: () => form.querySelector('[name="condemnable"]').value,
            total_quantity: () => form.querySelector('#totalQuantity').value,
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
                const shortRemark = a.remarks.length > 40 ? a.remarks.substring(0, 37) + '...' : a.remarks || '-';
                acquisitionTableBody.innerHTML += `
                    <tr>
                        <td class="border px-2 py-1">${a.source}</td>
                        <td class="border px-2 py-1">${a.date_acquired}</td>
                        <td class="border px-2 py-1">${a.cost}</td>
                        <td class="border px-2 py-1">${a.iar}</td>
                        <td class="border px-2 py-1 text-xs">${shortRemark}</td>
                        <td class="border px-2 py-1">${a.usable}</td>
                        <td class="border px-2 py-1">${a.partially_damaged}</td>
                        <td class="border px-2 py-1">${a.damaged}</td>
                        <td class="border px-2 py-1">${a.lost}</td>
                        <td class="border px-2 py-1">${a.condemnable}</td>
                        <td class="border px-2 py-1 font-semibold">${a.total_quantity}</td>
                                                    <td class="border px-2 py-1 text-center">
                                <div class="flex justify-center gap-2">

                                    <!-- EDIT ICON -->
                                    <button type="button"
                                        onclick="editAcquisition(${index})"
                                        class="p-1 rounded hover:bg-blue-100 text-blue-600"
                                        title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153
                                                3 21l1.847-4.5L16.862 4.487z"/>
                                        </svg>
                                    </button>

                                    <!-- DELETE ICON -->
                                    <button type="button"
                                        onclick="deleteAcquisition(${index})"
                                        class="p-1 rounded hover:bg-red-100 text-red-600"
                                        title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862
                                                a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6
                                                M9 7h6m2 0H7m3-3h4a1 1 0 011 1v1H9V5a1 1 0 011-1z"/>
                                        </svg>
                                    </button>

                                </div>
                            </td>
                    </tr>`;
                });
            };


            const resetAcquisitionForm = () => {
                form.querySelector('[name="remarks"]').value = '';
                form.querySelector('[name="source"]').value = '';
                form.querySelector('[name="date_acquired"]').value = '';
                form.querySelector('[name="cost"]').value = '';
                form.querySelector('[name="iar"]').value = '';
                form.querySelector('[name="usable"]').value = 0;
                form.querySelector('[name="partially_damaged"]').value = 0;
                form.querySelector('[name="damaged"]').value = 0;
                form.querySelector('[name="lost"]').value = 0;
                form.querySelector('[name="condemnable"]').value = 0;
                form.querySelector('[name="total_quantity"]').value = 0;
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
                Object.keys(fields).forEach(key => {
                    form.querySelector(`[name="${key}"]`).value = a[key];
                });
                form.querySelector('[name="source"]').scrollIntoView({ behavior: 'smooth' });
            };

            window.deleteAcquisition = (index) => {
                if (!confirm('Delete this acquisition?')) return;
                acquisitions.splice(index, 1);
                renderAcquisitions();
            };

            // SUBMIT
            form.addEventListener('submit', () => {
                acquisitionsInput.value = JSON.stringify(acquisitions);
            });
        });
    </script>
