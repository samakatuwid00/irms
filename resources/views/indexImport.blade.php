<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload CSV Files</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #eef2ff, #f8fafc);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            background: #ffffff;
            padding: 2rem;
            width: 100%;
            max-width: 600px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            text-align: center;
            color: #1f2937;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert ul {
            margin: 0.5rem 0 0 1.5rem;
            padding: 0;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }

        .file-upload-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        input[type="file"] {
            width: 100%;
            padding: 0.6rem;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        input[type="file"]:hover {
            border-color: #4f46e5;
        }

        .file-list {
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.5rem;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .file-item:last-child {
            margin-bottom: 0;
        }

        .file-name {
            color: #374151;
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-size {
            color: #6b7280;
            margin-left: 1rem;
            white-space: nowrap;
        }

        .files-count {
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #f3f4f6;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #4b5563;
        }

        .files-count strong {
            color: #4f46e5;
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: #4f46e5;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        button:hover:not(:disabled) {
            background: #4338ca;
        }

        button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .hint {
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #6b7280;
            text-align: center;
        }

        .loading {
            display: none;
            text-align: center;
            margin-top: 1rem;
            color: #4f46e5;
        }

        .loading.active {
            display: block;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
            display: none;
        }

        .progress-bar.active {
            display: block;
        }

        .progress-fill {
            height: 100%;
            background: #4f46e5;
            transition: width 0.3s ease;
            width: 0%;
        }
    </style>
</head>
<body>

    <div class="card">
        <h1>Upload Imported School Resource CSV Files</h1>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
                @if(session('details'))
                    <ul>
                        @foreach(session('details') as $detail)
                            <li>{{ $detail }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <strong>File Processing Errors:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('imported_schools.import') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf

            <label for="csv_files">Select CSV Files (Multiple)</label>
            <div class="file-upload-wrapper">
                <input
                    type="file"
                    name="csv_files[]"
                    id="csv_files"
                    accept=".csv"
                    multiple
                    required>
            </div>

            <div id="filesCount" class="files-count" style="display: none;">
                Selected <strong><span id="countNumber">0</span></strong> file(s)
            </div>

            <div id="fileList" class="file-list" style="display: none;"></div>

            <div class="progress-bar" id="progressBar">
                <div class="progress-fill" id="progressFill"></div>
            </div>

            <button type="submit" id="submitBtn">Upload & Import</button>

            <div class="loading" id="loading">
                <p>Processing files, please wait...</p>
            </div>
        </form>

        <div class="hint">
            <strong>Accepted format:</strong> .csv only<br>
            <strong>Maximum files:</strong> 100 per upload
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('csv_files');
        const fileList = document.getElementById('fileList');
        const filesCount = document.getElementById('filesCount');
        const countNumber = document.getElementById('countNumber');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const loading = document.getElementById('loading');
        const progressBar = document.getElementById('progressBar');
        const progressFill = document.getElementById('progressFill');

        fileInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);

            if (files.length === 0) {
                fileList.style.display = 'none';
                filesCount.style.display = 'none';
                return;
            }

            // Display file count
            countNumber.textContent = files.length;
            filesCount.style.display = 'block';

            // Display file list
            fileList.innerHTML = '';
            files.forEach(file => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';

                const fileName = document.createElement('span');
                fileName.className = 'file-name';
                fileName.textContent = file.name;

                const fileSize = document.createElement('span');
                fileSize.className = 'file-size';
                fileSize.textContent = formatFileSize(file.size);

                fileItem.appendChild(fileName);
                fileItem.appendChild(fileSize);
                fileList.appendChild(fileItem);
            });

            fileList.style.display = 'block';
        });

        uploadForm.addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            loading.classList.add('active');
            progressBar.classList.add('active');

            // Simulate progress (you can make this more accurate with actual upload progress)
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress >= 90) {
                    clearInterval(interval);
                }
                progressFill.style.width = progress + '%';
            }, 200);
        });

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    </script>

</body>
</html>
