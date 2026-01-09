<!-- Non-Print Resource View Modal -->
<div id="viewNonPrintModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full max-h-screen overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b bg-indigo-50">
            <h2 class="text-2xl font-bold text-gray-800">Non-Print Resource Details</h2>
            <button type="button" onclick="closeNonPrintModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-8">
            <!-- Image + Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <img id="nonprintImage" src="" alt="Resource Image" class="w-full h-72 object-cover rounded-lg shadow-md">
                </div>
                <div class="md:col-span-2 space-y-5">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Title</label>
                        <p id="nonprintTitle" class="text-xl font-semibold text-gray-900"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-500">Type</label><p id="nonprintType"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Brand</label><p id="nonprintBrand"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Model</label><p id="nonprintModel"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Code</label><p id="nonprintCode" class="font-mono"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Version</label><p id="nonprintVersion"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Year Acquired</label><p id="nonprintYear"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">URL</label><p><a id="nonprintURL" href="#" target="_blank" class="text-blue-600 underline"></a></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Size</label><p id="nonprintSize"></p></div>
                    </div>
                </div>
            </div>

            <!-- Subject Assignment (for Non-Print) -->
            <div>
                <h3 class="text-lg font-semibold mb-3">Subject Assignment</h3>
                <div id="nonprintSubjects" class="bg-gray-50 p-4 rounded-lg">
                    <!-- Will be filled by JS -->
                </div>
            </div>

            <!-- Acquisition History -->
            <div>
                <h3 class="text-lg font-semibold mb-3">Acquisition History</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-3 py-2">Source</th>
                                <th class="border px-3 py-2">Date Acquired</th>
                                <th class="border px-3 py-2">Cost</th>
                                <th class="border px-3 py-2">IAR / Prop No.</th>
                                <th class="border px-3 py-2">Remarks</th>
                                <th class="border px-3 py-2 text-center">Usable</th>
                                <th class="border px-3 py-2 text-center">Repair</th>
                                <th class="border px-3 py-2 text-center">Damaged</th>
                                <th class="border px-3 py-2 text-center">Lost</th>
                                <th class="border px-3 py-2 text-center">Cond.</th>
                                <th class="border px-3 py-2 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="nonprintAcquisitionBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Quantity Summary -->
            <div class="bg-gray-50 rounded-lg p-5">
                <h4 class="font-semibold text-gray-700 mb-4">Overall Quantity Summary</h4>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                    <div><strong class="text-green-600" id="nonprintUsable">0</strong><br><span class="text-xs text-gray-600">Working/Usable</span></div>
                    <div><strong class="text-orange-600" id="nonprintRepair">0</strong><br><span class="text-xs text-gray-600">Needs Repair</span></div>
                    <div><strong class="text-red-600" id="nonprintDamaged">0</strong><br><span class="text-xs text-gray-600">Damaged</span></div>
                    <div><strong class="text-purple-600" id="nonprintLost">0</strong><br><span class="text-xs text-gray-600">Lost</span></div>
                    <div><strong class="text-gray-800" id="nonprintCondemnable">0</strong><br><span class="text-xs text-gray-600">Condemnable</span></div>
                    <div class="md:col-span-1"><strong class="text-2xl text-indigo-600" id="nonprintTotal">0</strong><br><span class="text-sm font-bold">Total</span></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-6 border-t gap-3">
            <button type="button" onclick="closeNonPrintModal()" class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Close
            </button>
            <a href="#" id="nonprintEditLink" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Resource
            </a>
        </div>
    </div>
</div>

<script>
    function openNonPrintModal(resource) {
        document.getElementById('nonprintImage').src = resource.image || '{{ asset('assets/images/default.jpg') }}';
        document.getElementById('nonprintTitle').textContent = resource.title;
        document.getElementById('nonprintType').textContent = resource.type || '-';
        document.getElementById('nonprintBrand').textContent = resource.brand || '-';
        document.getElementById('nonprintModel').textContent = resource.model || '-';
        document.getElementById('nonprintCode').textContent = resource.code || '-';
        document.getElementById('nonprintVersion').textContent = resource.version || '-';
        document.getElementById('nonprintYear').textContent = resource.year_acquired || '-';
        document.getElementById('nonprintSize').textContent = resource.size || '-';

        const urlEl = document.getElementById('nonprintURL');
        if (resource.url) {
            urlEl.href = resource.url;
            urlEl.textContent = resource.url;
        } else {
            urlEl.href = '#';
            urlEl.textContent = 'N/A';
        }

        // Subject Assignment - same format as print
        const subjectsContainer = document.getElementById('nonprintSubjects');
        if (resource.subjects && resource.subjects.length > 0) {
            subjectsContainer.innerHTML = resource.subjects.map(item =>
                `<span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                    ${item.subject} - Grade ${item.grade}
                </span>`
            ).join('');
        } else {
            subjectsContainer.innerHTML = '<p class="text-gray-500">No subject assignment.</p>';
        }

        // Acquisitions (unchanged)
        const tbody = document.getElementById('nonprintAcquisitionBody');
        tbody.innerHTML = '';
        let totals = { usable: 0, repair: 0, damaged: 0, lost: 0, condemnable: 0 };

        if (resource.acquisitions && resource.acquisitions.length > 0) {
            resource.acquisitions.forEach(aq => {
                const row = `<tr>
                    <td class="border px-3 py-2">${aq.source || '-'}</td>
                    <td class="border px-3 py-2">${aq.date_acquired || '-'}</td>
                    <td class="border px-3 py-2">${aq.cost ? '₱' + aq.cost : '-'}</td>
                    <td class="border px-3 py-2">${aq.iar || '-'}</td>
                    <td class="border px-3 py-2 text-xs">${aq.remarks || '-'}</td>
                    <td class="border px-3 py-2 text-center">${aq.usable || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.needs_repair || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.damaged || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.lost || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.condemnable || 0}</td>
                    <td class="border px-3 py-2 text-center font-medium">${aq.total_quantity || 0}</td>
                </tr>`;
                tbody.innerHTML += row;

                totals.usable += parseInt(aq.usable || 0);
                totals.repair += parseInt(aq.needs_repair || 0);
                totals.damaged += parseInt(aq.damaged || 0);
                totals.lost += parseInt(aq.lost || 0);
                totals.condemnable += parseInt(aq.condemnable || 0);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-gray-500">No acquisition records.</td></tr>';
        }

        // Summary
        document.getElementById('nonprintUsable').textContent = totals.usable;
        document.getElementById('nonprintRepair').textContent = totals.repair;
        document.getElementById('nonprintDamaged').textContent = totals.damaged;
        document.getElementById('nonprintLost').textContent = totals.lost;
        document.getElementById('nonprintCondemnable').textContent = totals.condemnable;
        document.getElementById('nonprintTotal').textContent = totals.usable + totals.repair + totals.damaged + totals.lost + totals.condemnable;

        document.getElementById('nonprintEditLink').href = resource.edit_url || '#';

        document.getElementById('viewNonPrintModal').classList.remove('hidden');
    }

    function closeNonPrintModal() {
        document.getElementById('viewNonPrintModal').classList.add('hidden');
    }

    document.getElementById('viewNonPrintModal').addEventListener('click', e => {
        if (e.target === document.getElementById('viewNonPrintModal')) closeNonPrintModal();
    });
</script>
