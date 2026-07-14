<!-- Print Resource View Modal -->
<div id="viewPrintModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[92vh] overflow-hidden flex flex-col dark:border dark:border-slate-700 dark:bg-slate-900">
        
        <!-- Header -->
        <div class="flex justify-between items-center p-5 md:p-6 border-b border-gray-200 bg-blue-50 sticky top-0 z-20 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Print Resource Details</h2>
            <button type="button" onclick="closePrintModal()" 
                    class="text-gray-500 hover:text-gray-700 transition-colors p-2 -mr-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-5 md:p-6 space-y-8">
            
            <!-- Image + Basic Info -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                <!-- Image -->
                <div class="lg:col-span-4 xl:col-span-3 flex justify-center lg:justify-start">
                    <div class="w-full max-w-[280px] lg:max-w-none">
                        <img id="printImage"
                             src=""
                             alt="Book Cover"
                             onerror="this.src='{{ asset('assets/images/default.jpg') }}'"
                             class="w-full h-auto max-h-[420px] object-contain rounded-xl border border-gray-200 shadow-md bg-gray-50 dark:border-slate-700 dark:bg-slate-950">
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="lg:col-span-8 xl:col-span-9 space-y-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500 block mb-1">Title</label>
                        <p id="printTitle" class="text-2xl font-semibold text-gray-900 leading-tight"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 text-sm">
                        <div>
                            <label class="text-gray-500">Author</label>
                            <p id="printAuthor" class="font-medium text-gray-900 mt-0.5"></p>
                        </div>
                        <div>
                            <label class="text-gray-500">Publisher</label>
                            <p id="printPublisher" class="font-medium text-gray-900 mt-0.5"></p>
                        </div>
                        <div>
                            <label class="text-gray-500">Type</label>
                            <p id="printType" class="font-medium text-gray-900 mt-0.5"></p>
                        </div>
                        <div>
                            <label class="text-gray-500">ISBN</label>
                            <p id="printISBN" class="font-mono text-gray-900 mt-0.5"></p>
                        </div>
                        <div>
                            <label class="text-gray-500">Copyright Year</label>
                            <p id="printCopyright" class="font-medium text-gray-900 mt-0.5"></p>
                        </div>
                        <div>
                            <label class="text-gray-500">Pages</label>
                            <p id="printPages" class="font-medium text-gray-900 mt-0.5"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Assignment -->
            <div class="border-t border-gray-200 pt-6 dark:border-slate-700">
                <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-slate-100">Subject Assignment</h3>
                <div id="printSubjects" class="bg-gray-50 p-5 rounded-xl min-h-[60px] text-gray-700 dark:bg-slate-800 dark:text-slate-200">
                    <!-- Filled by JS -->
                </div>
            </div>

            <!-- Acquisition History -->
            <div class="border-t border-gray-200 pt-6 dark:border-slate-700">
                <h3 class="text-lg font-semibold mb-3 text-gray-800 dark:text-slate-100">Acquisition History</h3>
                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm dark:border-slate-700">
                    <table class="w-full text-sm divide-y divide-gray-200 min-w-[900px]" id="printAcquisitionTable" data-user-level="{{ $level }}">
                        <thead class="bg-gray-100 sticky top-0 z-10">
                            <tr class="text-xs uppercase tracking-wider text-gray-600">
                                @if($level == 4)
                                    <th class="px-3 py-3 text-left font-medium whitespace-nowrap">Division</th>
                                @endif
                                <th class="px-3 py-3 text-left font-medium whitespace-nowrap">Station</th>
                                <th class="px-3 py-3 text-left font-medium whitespace-nowrap">Source</th>
                                <th class="px-3 py-3 text-left font-medium whitespace-nowrap">Date Acquired</th>
                                <th class="px-3 py-3 text-left font-medium whitespace-nowrap">Cost</th>
                                <th class="px-3 py-3 text-left font-medium whitespace-nowrap">IAR No.</th>
                                <th class="px-3 py-3 text-left font-medium whitespace-nowrap">Remarks</th>
                                <th class="px-2 py-3 text-center font-medium">Usable</th>
                                <th class="px-2 py-3 text-center font-medium">PD</th>
                                <th class="px-2 py-3 text-center font-medium">Damaged</th>
                                <th class="px-2 py-3 text-center font-medium">Lost</th>
                                <th class="px-2 py-3 text-center font-medium">Cond.</th>
                                <th class="px-2 py-3 text-center font-medium bg-blue-50">Total</th>
                            </tr>
                        </thead>
                        <tbody id="printAcquisitionBody" class="divide-y divide-gray-100 text-gray-700">
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quantity Summary -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100 dark:from-slate-800 dark:to-slate-900 dark:border-slate-700">
                <h4 class="font-semibold text-gray-700 mb-5 text-lg dark:text-slate-100">Overall Quantity Summary</h4>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-4 text-center">
                    <div class="bg-white rounded-xl py-3 shadow-sm dark:border dark:border-slate-700 dark:bg-slate-950">
                        <strong id="printUsable" class="text-3xl text-green-600 block">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block dark:text-slate-300">Usable</span>
                    </div>
                    <div class="bg-white rounded-xl py-3 shadow-sm dark:border dark:border-slate-700 dark:bg-slate-950">
                        <strong id="printPD" class="text-3xl text-yellow-600 block">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block dark:text-slate-300">Partially Damaged</span>
                    </div>
                    <div class="bg-white rounded-xl py-3 shadow-sm dark:border dark:border-slate-700 dark:bg-slate-950">
                        <strong id="printDamaged" class="text-3xl text-red-600 block">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block dark:text-slate-300">Damaged</span>
                    </div>
                    <div class="bg-white rounded-xl py-3 shadow-sm dark:border dark:border-slate-700 dark:bg-slate-950">
                        <strong id="printLost" class="text-3xl text-purple-600 block">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block dark:text-slate-300">Lost</span>
                    </div>
                    <div class="bg-white rounded-xl py-3 shadow-sm dark:border dark:border-slate-700 dark:bg-slate-950">
                        <strong id="printCondemnable" class="text-3xl text-gray-700 block dark:text-slate-100">0</strong>
                        <span class="text-xs text-gray-600 mt-1 block dark:text-slate-300">Condemnable</span>
                    </div>
                    <div class="bg-white rounded-xl py-3 shadow-sm border-2 border-blue-200 dark:border-blue-400/60 dark:bg-blue-950/40">
                        <strong id="printTotal" class="text-4xl text-blue-600 block">0</strong>
                        <span class="text-sm font-bold text-blue-600 mt-1 block">TOTAL</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-200 bg-gray-50 p-5 md:p-6 flex justify-end gap-3 sticky bottom-0 z-20 dark:border-slate-700 dark:bg-slate-800">
            @if(in_array($level, [1, 3]))
            <a id="printModalEditBtn"
               class="hidden items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium shadow-sm"
               hidden
               aria-hidden="true"
               tabindex="-1"
               style="display: none;">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.687a1.875 1.875 0 112.652 2.652L7.5 19.153 3 21l1.847-4.5L16.862 4.487z"/>
                </svg>
                Edit
            </a>
            @endif
            <button type="button" onclick="closePrintModal()"
                    class="px-6 py-3 border border-gray-400 rounded-xl hover:bg-gray-100 transition-colors font-medium">
                Close
            </button>
        </div>
    </div>
</div>

@vite('resources/js/view-print-modal.js')
