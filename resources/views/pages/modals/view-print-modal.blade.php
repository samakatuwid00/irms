<!-- Print Resource View Modal -->
<div id="viewPrintModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-300 bg-blue-50 sticky top-0 z-10">
            <h2 class="text-2xl font-bold text-gray-800">Print Resource Details</h2>
            <button type="button" onclick="closePrintModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-8">
            <!-- Image + Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-center justify-center">
                    <img id="printImage"
                         src=""
                         alt="Book Cover"
                         onerror="this.src='{{ asset('assets/images/default.jpg') }}'"
                         class="w-full h-72 object-cover rounded-lg shadow-md">
                </div>
                <div class="md:col-span-2 space-y-5">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Title</label>
                        <p id="printTitle" class="text-xl font-semibold text-gray-900 mt-1"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Author</label>
                            <p id="printAuthor" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Publisher</label>
                            <p id="printPublisher" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Type</label>
                            <p id="printType" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">ISBN</label>
                            <p id="printISBN" class="font-mono text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Copyright Year</label>
                            <p id="printCopyright" class="text-gray-900 mt-1"></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Pages</label>
                            <p id="printPages" class="text-gray-900 mt-1"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Assignment -->
            <div class="border-t border-gray-300 pt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Subject Assignment</h3>
                <div id="printSubjects" class="bg-gray-50 p-4 rounded-lg min-h-15">
                    <!-- Will be filled by JS -->
                </div>
            </div>

            <!-- Acquisition History -->
            <div class="border-t border-gray-300 pt-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-800">Acquisition History</h3>
                <div class="overflow-x-auto border border-gray-300 rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border-b border-gray-300 px-3 py-2 text-left">Source</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-left">Date Acquired</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-left">Cost</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-left">IAR No.</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-left">Remarks</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-center">Usable</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-center">PD</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-center">Damaged</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-center">Lost</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-center">Cond.</th>
                                <th class="border-b border-gray-300 px-3 py-2 text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="printAcquisitionBody" class="divide-y">
                            <!-- Will be filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quantity Summary -->
            <div class="bg-linear-to-br from-blue-50 to-indigo-50 rounded-lg p-6 border-t border-gray-300">
                <h4 class="font-semibold text-gray-700 mb-4 text-lg">Overall Quantity Summary</h4>
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 text-center">
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-green-600 block" id="printUsable">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Usable</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-yellow-600 block" id="printPD">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Partially Damaged</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-red-600 block" id="printDamaged">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Damaged</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-purple-600 block" id="printLost">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Lost</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm">
                        <strong class="text-2xl text-gray-800 block" id="printCondemnable">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block">Condemnable</span>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm md:col-span-1 border-2 border-blue-200">
                        <strong class="text-3xl text-blue-600 block" id="printTotal">0</strong>
                        <span class="text-sm font-bold text-blue-600 mt-1 block">Total</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="flex justify-end p-6 border-t border-gray-300 gap-3 bg-gray-50 sticky bottom-0">
            <button type="button"
                    onclick="closePrintModal()"
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
@vite('resources/js/view-print-modal.js')
