<!-- Print Resource View Modal -->
<div id="viewPrintModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full max-h-screen overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b bg-blue-50">
            <h2 class="text-2xl font-bold text-gray-800">Print Resource Details</h2>
            <button type="button" onclick="closePrintModal()" class="text-gray-500 hover:text-gray-700">
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
                    <img id="printImage" src="" alt="Book Cover" class="w-full h-72 object-cover rounded-lg shadow-md">
                </div>
                <div class="md:col-span-2 space-y-5">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Title</label>
                        <p id="printTitle" class="text-xl font-semibold text-gray-900"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-sm font-medium text-gray-500">Author</label><p id="printAuthor"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Publisher</label><p id="printPublisher"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Type</label><p id="printType"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">ISBN</label><p id="printISBN" class="font-mono"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Copyright Year</label><p id="printCopyright"></p></div>
                        <div><label class="text-sm font-medium text-gray-500">Pages</label><p id="printPages"></p></div>
                    </div>
                </div>
            </div>

            <!-- Subject Assignment -->
            <div>
                <h3 class="text-lg font-semibold mb-3">Subject Assignment</h3>
                <div id="printSubjects" class="bg-gray-50 p-4 rounded-lg">
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
                                <th class="border px-3 py-2">IAR No.</th>
                                <th class="border px-3 py-2">Remarks</th>
                                <th class="border px-3 py-2 text-center">Usable</th>
                                <th class="border px-3 py-2 text-center">PD</th>
                                <th class="border px-3 py-2 text-center">Damaged</th>
                                <th class="border px-3 py-2 text-center">Lost</th>
                                <th class="border px-3 py-2 text-center">Cond.</th>
                                <th class="border px-3 py-2 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="printAcquisitionBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- Quantity Summary -->
            <div class="bg-gray-50 rounded-lg p-5">
                <h4 class="font-semibold text-gray-700 mb-4">Overall Quantity Summary</h4>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                    <div><strong class="text-green-600" id="printUsable">0</strong><br><span class="text-xs text-gray-600">Usable</span></div>
                    <div><strong class="text-yellow-600" id="printPD">0</strong><br><span class="text-xs text-gray-600">Partially Damaged</span></div>
                    <div><strong class="text-red-600" id="printDamaged">0</strong><br><span class="text-xs text-gray-600">Damaged</span></div>
                    <div><strong class="text-purple-600" id="printLost">0</strong><br><span class="text-xs text-gray-600">Lost</span></div>
                    <div><strong class="text-gray-800" id="printCondemnable">0</strong><br><span class="text-xs text-gray-600">Condemnable</span></div>
                    <div class="md:col-span-1"><strong class="text-2xl text-blue-600" id="printTotal">0</strong><br><span class="text-sm font-bold">Total</span></div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-6 border-t gap-3">
            <button type="button" onclick="closePrintModal()" class="px-5 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Close
            </button>
            <a href="#" id="printEditLink" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Edit Resource
            </a>
        </div>
    </div>
</div>

<script>
    function openPrintModal(resource) {
        document.getElementById('printImage').src = resource.image || '{{ asset('assets/images/default.jpg') }}';
        document.getElementById('printTitle').textContent = resource.title;
        document.getElementById('printAuthor').textContent = resource.author || '-';
        document.getElementById('printPublisher').textContent = resource.publisher || '-';
        document.getElementById('printType').textContent = resource.type || '-';
        document.getElementById('printISBN').textContent = resource.isbn || 'N/A';
        document.getElementById('printCopyright').textContent = resource.copyright || '-';
        document.getElementById('printPages').textContent = resource.pages || '-';

        // Subject Assignment - now expects array of { subject: "English", grade: "5" } or similar
        const subjectsContainer = document.getElementById('printSubjects');
        if (resource.subjects && resource.subjects.length > 0) {
            subjectsContainer.innerHTML = resource.subjects.map(item =>
                `<span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                    ${item.subject} - Grade ${item.grade}
                </span>`
            ).join('');
        } else {
            subjectsContainer.innerHTML = '<p class="text-gray-500">No subject assignment.</p>';
        }

        // Acquisitions (unchanged)
        const tbody = document.getElementById('printAcquisitionBody');
        tbody.innerHTML = '';
        let totals = { usable: 0, pd: 0, damaged: 0, lost: 0, condemnable: 0 };

        if (resource.acquisitions && resource.acquisitions.length > 0) {
            resource.acquisitions.forEach(aq => {
                const row = `<tr>
                    <td class="border px-3 py-2">${aq.source || '-'}</td>
                    <td class="border px-3 py-2">${aq.date_acquired || '-'}</td>
                    <td class="border px-3 py-2">${aq.cost ? '₱' + aq.cost : '-'}</td>
                    <td class="border px-3 py-2">${aq.iar || '-'}</td>
                    <td class="border px-3 py-2 text-xs">${aq.remarks || '-'}</td>
                    <td class="border px-3 py-2 text-center">${aq.usable || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.partially_damaged || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.damaged || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.lost || 0}</td>
                    <td class="border px-3 py-2 text-center">${aq.condemnable || 0}</td>
                    <td class="border px-3 py-2 text-center font-medium">${aq.total_quantity || 0}</td>
                </tr>`;
                tbody.innerHTML += row;

                totals.usable += parseInt(aq.usable || 0);
                totals.pd += parseInt(aq.partially_damaged || 0);
                totals.damaged += parseInt(aq.damaged || 0);
                totals.lost += parseInt(aq.lost || 0);
                totals.condemnable += parseInt(aq.condemnable || 0);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-gray-500">No acquisition records.</td></tr>';
        }

        // Summary
        document.getElementById('printUsable').textContent = totals.usable;
        document.getElementById('printPD').textContent = totals.pd;
        document.getElementById('printDamaged').textContent = totals.damaged;
        document.getElementById('printLost').textContent = totals.lost;
        document.getElementById('printCondemnable').textContent = totals.condemnable;
        document.getElementById('printTotal').textContent = totals.usable + totals.pd + totals.damaged + totals.lost + totals.condemnable;

        document.getElementById('printEditLink').href = resource.edit_url || '#';

        document.getElementById('viewPrintModal').classList.remove('hidden');
    }

    function closePrintModal() {
        document.getElementById('viewPrintModal').classList.add('hidden');
    }

    document.getElementById('viewPrintModal').addEventListener('click', e => {
        if (e.target === document.getElementById('viewPrintModal')) closePrintModal();
    });
</script>
