<!-- Print Resource View Modal -->
<div id="viewNonPrintModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b bg-blue-50 sticky top-0 z-10">
            <h2 class="text-2xl font-bold text-gray-800">Print Resource Details</h2>
            <button type="button" onclick="closeNonPrintModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
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
                    <img id="nonprintImage"
                         src=""
                         alt="Book Cover"
                         class="w-full h-72 object-cover rounded-lg shadow-md">
                </div>
                <div class="md:col-span-2 space-y-5">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Title</label>
                        <p id="nonprintTitle" class="text-xl font-semibold text-gray-900 mt-1"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Type</label>
                            <p id="nonprintType" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Brand</label>
                            <p id="brand" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Code</label>
                            <p id="code" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Version</label>
                            <p id="version" class="font-mono text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">URL</label>
                            <p id="url" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Size</label>
                            <p id="size" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Model</label>
                            <p id="model" class="text-gray-900 mt-1"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Assignment -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Subject Assignment</h3>
                <div id="nonprintSubjects" class="bg-gray-50 p-4 rounded-lg min-h-[60px]">
                    <!-- Will be filled by JS -->
                </div>
            </div>

            <!-- Acquisition History -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Acquisition History</h3>
                <div class="overflow-x-auto border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border-b px-3 py-2 text-left">Source</th>
                                <th class="border-b px-3 py-2 text-left">Date Acquired</th>
                                <th class="border-b px-3 py-2 text-left">Cost</th>
                                <th class="border-b px-3 py-2 text-left">IAR No.</th>
                                <th class="border-b px-3 py-2 text-left">Remarks</th>
                                <th class="border-b px-3 py-2 text-center">Usable</th>
                                <th class="border-b px-3 py-2 text-center">PD</th>
                                <th class="border-b px-3 py-2 text-center">Damaged</th>
                                <th class="border-b px-3 py-2 text-center">Lost</th>
                                <th class="border-b px-3 py-2 text-center">Cond.</th>
                                <th class="border-b px-3 py-2 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="nonprintAcquisitionBody" class="divide-y">
                            <!-- Will be filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quantity Summary -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 border-t">
                <h4 class="font-semibold text-gray-700 mb-4 text-lg">Overall Quantity Summary</h4>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-green-600 block" id="nonprintUsable">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Usable</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-yellow-600 block" id="nonprintPD">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Partially Damaged</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-red-600 block" id="nonprintDamaged">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Damaged</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-purple-600 block" id="nonprintLost">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Lost</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-gray-800 block" id="nonprintCondemnable">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Condemnable</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm md:col-span-1 border-2 border-blue-200">
                        <strong class="text-3xl text-blue-600 block" id="nonprintTotal">0</strong>
                        <span class="text-sm font-bold text-blue-600 mt-1 block">Total</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-6 border-t gap-3 bg-gray-50 sticky bottom-0">
            <button type="button"
                    onclick="closeNonPrintModal()"
                    class="px-5 py-2 border border-gray-600 rounded-lg hover:bg-gray-300 transition-colors">
                Close
            </button>
            {{-- <a href="#"
               id="printEditLink"
               class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                Edit Resource
            </a> --}}
        </div>
    </div>
</div>

<script>
    function openNonPrintModal(resource) {
        // Set image
        const imgElement = document.getElementById('nonprintImage');
        imgElement.src = resource.image || '/assets/images/default.jpg';
        imgElement.alt = resource.title || 'Book Cover';

        // Set basic info
        document.getElementById('nonprintTitle').textContent = resource.title || 'N/A';
        document.getElementById('nonprintType').textContent = resource.type || '-';
        document.getElementById('brand').textContent = resource.brand || '-';
        document.getElementById('code').textContent = resource.code || '-';
        document.getElementById('version').textContent = resource.version || 'N/A';
        document.getElementById('url').textContent = resource.url || '-';
        document.getElementById('size').textContent = resource.size || '-';
        document.getElementById('model').textContent = resource.model || '-';

        // Subject Assignment
        const subjectsContainer = document.getElementById('nonprintSubjects');
        if (resource.subjects && resource.subjects.length > 0) {
            subjectsContainer.innerHTML = resource.subjects.map(item =>
                `<span class="inline-block bg-blue-100 text-blue-800 text-xs font-medium px-3 py-1 rounded-full mr-2 mb-2">
                    ${item.subject} - ${item.grade}
                </span>`
            ).join('');
        } else {
            subjectsContainer.innerHTML = '<p class="text-gray-500">No subject assignment.</p>';
        }

        // Acquisition History
        const tbody = document.getElementById('nonprintAcquisitionBody');
        tbody.innerHTML = '';
        let totals = {
            usable: 0,
            pd: 0,
            damaged: 0,
            lost: 0,
            condemnable: 0
        };

        if (resource.acquisitions && resource.acquisitions.length > 0) {
            resource.acquisitions.forEach(aq => {
                const usable = parseInt(aq.usable || 0);
                const pd = parseInt(aq.partially_damaged || 0);
                const damaged = parseInt(aq.damaged || 0);
                const lost = parseInt(aq.lost || 0);
                const condemnable = parseInt(aq.condemnable || 0);
                const total = usable + pd + damaged + lost + condemnable;

                const row = `<tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">${aq.source || '-'}</td>
                    <td class="px-3 py-2">${aq.date_acquired || '-'}</td>
                    <td class="px-3 py-2">${aq.cost ? '₱' + parseFloat(aq.cost).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                    <td class="px-3 py-2">${aq.iar || '-'}</td>
                    <td class="px-3 py-2 text-xs">${aq.remarks || '-'}</td>
                    <td class="px-3 py-2 text-center text-green-600 font-medium">${usable}</td>
                    <td class="px-3 py-2 text-center text-yellow-600 font-medium">${pd}</td>
                    <td class="px-3 py-2 text-center text-red-600 font-medium">${damaged}</td>
                    <td class="px-3 py-2 text-center text-purple-600 font-medium">${lost}</td>
                    <td class="px-3 py-2 text-center text-gray-800 font-medium">${condemnable}</td>
                    <td class="px-3 py-2 text-center font-bold text-blue-600">${total}</td>
                </tr>`;
                tbody.innerHTML += row;

                // Add to totals
                totals.usable += usable;
                totals.pd += pd;
                totals.damaged += damaged;
                totals.lost += lost;
                totals.condemnable += condemnable;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-gray-500">No acquisition records.</td></tr>';
        }

        // Overall Quantity Summary
        const grandTotal = totals.usable + totals.pd + totals.damaged + totals.lost + totals.condemnable;

        document.getElementById('nonprintUsable').textContent = totals.usable;
        document.getElementById('nonprintPD').textContent = totals.pd;
        document.getElementById('nonprintDamaged').textContent = totals.damaged;
        document.getElementById('nonprintLost').textContent = totals.lost;
        document.getElementById('nonprintCondemnable').textContent = totals.condemnable;
        document.getElementById('nonprintTotal').textContent = grandTotal;

        // Set edit link
        // document.getElementById('printEditLink').href = resource.edit_url || '#';

        // Show modal
        document.getElementById('viewNonPrintModal').classList.remove('hidden');
    }

    function closeNonPrintModal() {
        document.getElementById('viewNonPrintModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('viewNonPrintModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeNonPrintModal();
                }
            });
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeNonPrintModal();
        }
    });
</script>
