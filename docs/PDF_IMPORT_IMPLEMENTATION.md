# PDF Import Implementation

## Overview
The PDF import feature allows administrators to upload a PDF timetable and automatically extract course information using Python OCR processing.

## Architecture

### Flow
1. User uploads PDF file via Livewire component
2. File is temporarily stored in `storage/app/temp/`
3. Python script (`main.py`) processes the PDF
4. Python script writes JSON output to a temporary file
5. Laravel reads the JSON file and displays extracted events
6. User confirms import, events are saved to database
7. Temporary files are cleaned up

### Why File Output Instead of Stdout?
The Python library `pdfplumber` emits warnings to stderr/stdout that cannot be suppressed. To avoid these warnings interfering with JSON parsing, the Python script now:
- Writes JSON output to a temporary file using `tempfile.NamedTemporaryFile()`
- Prints only the file path to stdout
- Laravel reads the JSON from the file path

## Files Modified

### 1. `/app/Services/PdfImportService.php`
**Purpose**: Service class that handles PDF processing via Python script

**Key Methods**:
- `processFile(string $filePath): array` - Processes PDF and returns extracted events data

**Flow**:
```php
1. Validate Python script exists
2. Validate PDF file exists
3. Run Python script: `uv run main.py <pdf_path>`
4. Read output file path from stdout
5. Read JSON from the output file
6. Delete temporary output file
7. Parse JSON and return data
8. Clean up input PDF file
```

### 2. `/main.py`
**Purpose**: Python script for PDF text extraction and event parsing

**Changes**:
- Modified to write JSON output to temporary file instead of stdout
- Prints only the temporary file path to stdout
- Uses `tempfile.NamedTemporaryFile()` with `delete=False`

**Output Format**:
```json
{
  "events": [
    {
      "title": "Course Name",
      "teacher": "Professor Name",
      "description": "Course - Professor",
      "location": null,
      "start_time": "2025-01-15T08:30:00",
      "end_time": "2025-01-15T12:15:00",
      "type": "course",
      "color": null
    }
  ],
  "summary": {
    "total": 10,
    "courses": 9,
    "exams": 1
  }
}
```

### 3. `/resources/views/livewire/admin/import-pdf.blade.php`
**Purpose**: Livewire Volt component for PDF import UI

**Features**:
- File upload with drag & drop support
- Real-time file validation
- Processing status indicators
- Extracted data preview
- Import confirmation

**Key Actions**:
- `processPDF()` - Uploads and processes PDF file
- `confirmImport()` - Saves extracted events to database
- `resetForm()` - Clears form state

## Usage

### For Users
1. Navigate to `/admin/import-pdf`
2. Click "Choisir un fichier" or drag & drop a PDF
3. Click "Lancer l'extraction" to process
4. Review extracted events
5. Click "Importer les données" to save to database

### For Developers

**Testing PDF Processing**:
```bash
# Test Python script directly
uv run main.py path/to/timetable.pdf

# The script will output a temporary file path like:
# /tmp/tmpXXXXXX.json

# You can then read that file:
cat /tmp/tmpXXXXXX.json
```

**Debugging**:
- Check Laravel logs: `storage/logs/laravel.log`
- Check Python errors: stderr output in Laravel log
- File upload issues: Check `storage/app/temp/` directory exists and is writable

## Dependencies

### Python
- `pdfplumber` - PDF text extraction
- `icalendar` - ICS file generation (optional)

Install via:
```bash
uv sync
```

### PHP/Laravel
- Laravel 12
- Livewire 3 with Volt
- Laravel Process facade

## Error Handling

### Common Errors
1. **"Python script not found"** - `main.py` missing from project root
2. **"PDF file not found"** - File upload failed or wrong path
3. **"Python Error"** - Check Python dependencies installed
4. **"Failed to parse JSON"** - Python script output invalid JSON

### Logging
All operations are logged with context:
```php
Log::info('Starting PDF processing', ['file' => $filePath]);
Log::error('PDF processing failed', ['error' => $error]);
```

## Security Considerations

1. **File Validation**: Only PDF files up to 10MB accepted
2. **Temporary Files**: All temp files cleaned up after processing
3. **Admin Only**: Route protected by admin middleware
4. **Input Sanitization**: Events validated before database insertion

## Future Improvements

- [ ] Add drag & drop file upload
- [ ] Show PDF preview before processing
- [ ] Add progress bar for long processing times
- [ ] Allow editing extracted events before import
- [ ] Support batch PDF uploads
- [ ] Add OCR quality settings (currently placeholder)
- [ ] Detect and merge duplicate events
- [ ] Export extracted data to CSV/ICS

## Testing

```bash
# Run related tests
php artisan test --filter=Admin

# Test specific functionality
php artisan test tests/Feature/AdminPdfImportTest.php
```

## Troubleshooting

### File Upload Not Working
1. Check browser console for JavaScript errors
2. Verify Livewire is loaded: `window.Livewire` should exist
3. Check file input has correct `wire:model="file"`
4. Ensure `WithFileUploads` trait is used

### Python Processing Fails
1. Verify Python dependencies: `uv sync`
2. Test script manually: `uv run main.py test.pdf`
3. Check Python version: Requires Python 3.13+
4. Check error logs for detailed Python errors

### JSON Parsing Fails
1. Run Python script manually and check output
2. Verify temporary file is created and readable
3. Check file permissions on temp directory
4. Look for Python warnings in output (should now be avoided with file output)

