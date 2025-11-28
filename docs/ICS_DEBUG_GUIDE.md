# ICS Import Debug Guide

## Problem
Getting "Le fichier ne contient aucun événement" error when uploading a valid ICS file.

## Changes Made

### 1. Updated `parseIcsFile()` method
Changed from passing file path directly to reading file content first:

```php
// Before
$calendar->parse($filePath);

// After  
$fileContent = file_get_contents($filePath);
$calendar->parse($fileContent);
```

### 2. Added Debug Logging
Added logging to track:
- File content length
- First 500 characters of file
- Number of events found
- Number of events parsed successfully
- Any events that return null

### 3. Updated `validateIcsFile()` 
Same change - read content before parsing:
```php
$fileContent = file_get_contents($filePath);
$calendar->parse($fileContent);
```

## How to Debug

### Step 1: Check Laravel Logs
After uploading a file, check the logs:

```bash
tail -f storage/logs/laravel.log
```

Look for these log entries:
- `ICS File Content Length:` - Should show file size
- `ICS File First 500 chars:` - Should show "BEGIN:VCALENDAR"
- `Processing event #X` - Shows each event being processed
- `Total events found:` - How many VEVENT components found
- `Total events parsed successfully:` - How many passed validation

### Step 2: Check ICS File Format
Your ICS file should have this structure:

```ics
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:unique-id@domain.com
DTSTART:20251201T100000Z
DTEND:20251201T120000Z
SUMMARY:Event Title
END:VEVENT
END:VCALENDAR
```

**Required fields per event:**
- `SUMMARY` (event title) - Required
- `DTSTART` (start date/time) - Required
- Other fields are optional

### Step 3: Common ICS File Issues

#### Issue: Line Endings
ICS files should use `\r\n` (CRLF) line endings, not just `\n` (LF).

**Fix:**
```bash
# Convert line endings (Linux/Mac)
sed -i 's/$/\r/' yourfile.ics

# Or use dos2unix (if installed)
unix2dos yourfile.ics
```

#### Issue: Character Encoding
ICS files should be UTF-8 encoded.

**Check encoding:**
```bash
file -i yourfile.ics
```

**Convert to UTF-8:**
```bash
iconv -f ISO-8859-1 -t UTF-8 yourfile.ics > yourfile_utf8.ics
```

#### Issue: Folded Lines
ICS spec allows long lines to be folded with CRLF + space/tab.

Example:
```ics
SUMMARY:This is a very long title that gets folded across multiple
  lines using a space at the start of continuation lines
```

The library should handle this automatically.

## Test File

Create a minimal test file to verify the library works:

```ics
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Test//Test//EN
BEGIN:VEVENT
UID:test-001@example.com
DTSTART:20251201T100000Z
DTEND:20251201T120000Z
SUMMARY:Test Event
DESCRIPTION:This is a test event
LOCATION:Test Room
END:VEVENT
END:VCALENDAR
```

Save this as `test-minimal.ics` and try uploading it.

## Debugging the extractEventData Method

The `extractEventData()` method returns `null` if:
1. `$summary` (SUMMARY field) is empty or missing
2. `$dtstart` (DTSTART field) is empty or missing
3. An exception occurs during processing

### Check What's Being Extracted

Add temporary debug to `extractEventData()`:

```php
protected function extractEventData($vevent): ?array
{
    try {
        $summary = $vevent->getSummary();
        $dtstart = $vevent->getDtstart();
        
        \Log::info('Event Summary: '.($summary ?? 'NULL'));
        \Log::info('Event DTSTART: '.print_r($dtstart, true));
        
        if (!$summary || !$dtstart) {
            \Log::warning('Rejected: Missing summary or dtstart');
            return null;
        }
        
        // ... rest of method
    } catch (\Exception $e) {
        \Log::error('extractEventData exception: '.$e->getMessage());
        return null;
    }
}
```

## Kigkonsult/icalcreator Library

### Known Issues with the Library

1. **parse() method signature**
   - Can accept file path OR file content
   - Some versions work better with content

2. **getComponent() behavior**
   - Returns components in order
   - Returns `false` when no more components
   - Must call in a loop with `while`

3. **Date/Time Formats**
   - Returns dates in various formats (array, DateTime, string)
   - Our `parseDatetime()` method handles this

## Alternative: Try a Different Library

If the kigkonsult library continues to have issues, we can switch to `sabre/vobject`:

```bash
composer require sabre/vobject
```

Example usage:
```php
use Sabre\VObject;

$vcalendar = VObject\Reader::read($fileContent);
foreach ($vcalendar->VEVENT as $event) {
    $summary = (string) $event->SUMMARY;
    $dtstart = $event->DTSTART->getDateTime();
    // ...
}
```

## Next Steps

1. **Upload your ICS file** to the import page
2. **Check the logs** at `storage/logs/laravel.log`
3. **Share the log output** with these key lines:
   - File content length
   - First 500 characters
   - Total events found
   - Any error messages

4. **If logs show 0 events found**, the issue is with the library parsing
5. **If logs show events found but 0 parsed**, the issue is in `extractEventData()`

## Quick Test Command

You can test the parser directly in tinker:

```bash
php artisan tinker
```

```php
$service = new \App\Services\IcsImportService();
$result = $service->parseIcsFile('/path/to/your/file.ics');
dd($result);
```

This will show exactly what the service is returning.

## Status

✅ Added file content reading instead of path parsing
✅ Added comprehensive debug logging
✅ Updated both parse and validate methods
🔄 Waiting for log output to diagnose further

The logs will tell us exactly where the parsing is failing!

