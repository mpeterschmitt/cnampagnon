# ICS Import - JSON Serialization Debugging Guide

## Status
Added comprehensive logging to identify the exact JSON serialization issue.

## What Was Added

### 1. In `IcsImportService::extractEventData()`

Added logging to track:
- Data types of datetime values from ICS parser
- Values of datetime fields
- Carbon conversion results
- Final result array structure

```php
\Log::info('Event data types:', [
    'dtstart_type' => gettype($dtstart),
    'dtstart_value' => $dtstart,
    'dtend_type' => gettype($dtend),
    'dtend_value' => $dtend,
]);

\Log::info('Converted times:', [
    'startTime_type' => gettype($startTime),
    'startTime_class' => get_class($startTime),
    'endTime_type' => gettype($endTime),
    'endTime_class' => get_class($endTime),
]);

\Log::info('Result array:', ['result' => $result]);
```

### 2. In Livewire Component `handleFileUpload()`

Added JSON encoding test before Livewire assignment:

```php
// Test JSON encoding before assigning to Livewire properties
$jsonTest = json_encode($result['events']);
if ($jsonTest === false) {
    \Log::error('JSON encode failed: '.json_last_error_msg());
    throw new \Exception('Cannot JSON encode events: '.json_last_error_msg());
}

\Log::info('JSON encoding successful, length: '.strlen($jsonTest));
```

## How to Debug

### Step 1: Upload an ICS File
Go to `/admin/import-ics` and upload your ICS file.

### Step 2: Check the Logs
```bash
tail -f storage/logs/laravel.log
```

### Step 3: Look for These Key Entries

#### A. Event Data Types
```
[timestamp] local.INFO: Event data types: 
{
  "dtstart_type": "string",    // or "integer", "object", etc.
  "dtstart_value": "20251201T100000Z",
  "dtend_type": "string",
  "dtend_value": "20251201T120000Z"
}
```

**What this tells us:**
- How `johngrogg/ics-parser` returns datetime values
- If they're strings, timestamps, or objects

#### B. Converted Times
```
[timestamp] local.INFO: Converted times: 
{
  "startTime_type": "object",
  "startTime_class": "Carbon\\Carbon",
  "endTime_type": "object",
  "endTime_class": "Carbon\\Carbon"
}
```

**What this tells us:**
- If Carbon conversion is working
- If the objects are correct

#### C. Result Array
```
[timestamp] local.INFO: Result array: 
{
  "result": {
    "title": "Mathématiques - CM",
    "start_time": "2025-12-01T10:00:00+00:00",
    "end_time": "2025-12-01T12:00:00+00:00",
    ...
  }
}
```

**What this tells us:**
- If ISO8601 conversion is working
- If all values are strings (should be)

#### D. JSON Encoding Test
```
[timestamp] local.INFO: JSON encoding successful, length: 15234
```

**OR ERROR:**
```
[timestamp] local.ERROR: JSON encode failed: Malformed UTF-8 characters
```

**What this tells us:**
- If the array can be JSON encoded
- Specific error if it fails

## Possible Issues & Solutions

### Issue 1: Non-String Datetime Values

**Symptoms:**
```
"start_time": "2025-12-01T10:00:00+00:00"  // Good ✅
```

**Solution:** Already implemented with `->toIso8601String()`

### Issue 2: Invalid UTF-8 in Event Data

**Symptoms:**
```
JSON encode failed: Malformed UTF-8 characters
```

**Solution:** Add UTF-8 encoding to all string fields:

```php
return [
    'title' => mb_convert_encoding($parsedInfo['title'] ?? $title, 'UTF-8', 'UTF-8'),
    'description' => $description ? mb_convert_encoding($description, 'UTF-8', 'UTF-8') : null,
    'location' => $location ? mb_convert_encoding($location, 'UTF-8', 'UTF-8') : null,
    // ...
];
```

### Issue 3: Special Characters in String Values

**Symptoms:**
```
JSON encode failed: Type is not supported
```

**Solution:** Strip or escape special characters:

```php
'description' => $description ? preg_replace('/[\x00-\x1F\x7F]/u', '', $description) : null,
```

### Issue 4: Nested Objects Not Converted

**Symptoms:**
```
"room": { "someObject": ... }  // Object instead of string
```

**Solution:** Ensure all values are primitives (string, int, bool, null):

```php
'room' => is_string($location) ? $location : ($location ? (string)$location : null),
```

### Issue 5: Array Values Need Encoding

**Symptoms:**
```
"metadata": ["key" => "value"]  // Already JSON-safe ✅
```

**Solution:** Arrays are OK if they contain only primitives

## Testing Different Scenarios

### Scenario 1: Simple ASCII Event
```ics
SUMMARY:Math Class
LOCATION:Room 101
```
**Expected:** Should work perfectly ✅

### Scenario 2: Unicode Characters
```ics
SUMMARY:Mathématiques - École
LOCATION:Salle École
```
**Expected:** Should work with UTF-8 encoding ✅

### Scenario 3: Special Characters
```ics
SUMMARY:Project & Design
DESCRIPTION:Line 1\nLine 2\t\tTabbed
```
**Expected:** May need escaping

### Scenario 4: Long Content
```ics
DESCRIPTION:Lorem ipsum... (5000 characters)
```
**Expected:** Should work, just large JSON

## Next Steps Based on Logs

### If Logs Show:
1. **"JSON encoding successful"** → Problem is elsewhere (Livewire response handling)
2. **"JSON encode failed: Malformed UTF-8"** → Add UTF-8 conversion
3. **"JSON encode failed: Type is not supported"** → Some value is not a primitive
4. **No result array logged** → Exception in extractEventData (check stack trace)
5. **"Cannot JSON encode events"** → Check the specific error message

## Quick Fixes to Try

### Fix 1: Force UTF-8 Encoding
Add to `extractEventData()`:

```php
$result = [
    'title' => mb_convert_encoding($parsedInfo['title'] ?? $title, 'UTF-8', 'UTF-8'),
    'description' => $description ? mb_convert_encoding($description, 'UTF-8', 'UTF-8') : null,
    'location' => $location ? mb_convert_encoding($location, 'UTF-8', 'UTF-8') : null,
    'start_time' => $startTime->toIso8601String(),
    'end_time' => $endTime->toIso8601String(),
    // ... rest
];
```

### Fix 2: Strip Control Characters
Add to `extractEventData()`:

```php
$sanitize = fn($str) => $str ? preg_replace('/[\x00-\x1F\x7F]/u', '', $str) : null;

$result = [
    'title' => $sanitize($parsedInfo['title'] ?? $title),
    'description' => $sanitize($description),
    'location' => $sanitize($location),
    // ... rest
];
```

### Fix 3: Ensure All Values Are Primitives
Add type checking:

```php
$toPrimitive = function($value) {
    if (is_object($value) && method_exists($value, '__toString')) {
        return (string)$value;
    }
    if (is_scalar($value) || is_null($value)) {
        return $value;
    }
    return json_encode($value);
};

// Apply to all values
```

## Action Items

1. ✅ **Upload an ICS file** to trigger the logging
2. ✅ **Check the logs** at `storage/logs/laravel.log`
3. ✅ **Share the log output** - especially:
   - Event data types
   - Converted times
   - Result array
   - JSON encoding result/error
4. Based on logs, apply the appropriate fix from above

## Files Modified

- `/app/Services/IcsImportService.php` - Added detailed logging in extractEventData()
- `/resources/views/livewire/admin/import-ics.blade.php` - Added JSON encoding test

The logs will now show us **exactly** where the JSON serialization is failing!

