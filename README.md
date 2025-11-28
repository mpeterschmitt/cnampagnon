# EDT OCR - PDF Timetable Extractor

A Python tool to extract structured timetable data from PDF files, specifically designed for course schedules with weekly layouts.

## Features

- **Automatic table extraction** from PDF files
- **Intelligent parsing** of course and professor information
- **Structured output** organized by day, time slot, and week
- **Multiple export formats**: CSV, JSON, and ICS (iCalendar)
- **Flexible extraction**: automatic detection or manual coordinate specification
- **Calendar integration**: Export to ICS for Google Calendar, Outlook, Apple Calendar
- **Pretty console output** for quick review

## Timetable Format

This tool is designed for timetables with the following structure:

- **Columns**: Weeks

- **Rows**: Days of the week
- **Time slots**:
  - Morning: 8:30 - 12:15
  - Afternoon: 13:30 - 17:15
- **Cell content**: Course name + Professor name

## Installation

1. Ensure you have Python 3.13+ installed

2. Install dependencies:
```bash
pip install pdfplumber
```

Or using uv:
```bash
uv pip install pdfplumber icalendar
```

## Usage

### Basic Usage

Extract and parse timetable from PDF:
```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf
```

This will:
- Extract the table from the first page
- Parse course and professor information
- Display organized entries by day and time slot

### Specific Page

```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --page 0
```

### View Raw Table

To see the raw extracted table without parsing:
```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --raw
```

Limit displayed rows:
```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --raw --max-rows 20
```

### Extract from Specific Coordinates

If automatic detection doesn't work well, specify exact table position:
```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --page 0 --x 50 --y 100 --width 500 --height 600
```

Coordinates explained:
- `--x`: X coordinate of the top-left corner (in PDF points)
- `--y`: Y coordinate of the top-left corner (in PDF points)
- `--width`: Width of the table region
- `--height`: Height of the table region

### Export to CSV

```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --output timetable.csv
```

CSV format includes columns:
- `day`: Day of the week
- `time_slot`: Morning or Afternoon with time range
- `week`: Week identifier
- `course`: Course name
- `professor`: Professor name

### Export to JSON

```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --output timetable.json
```

JSON format provides structured data with the same fields as CSV.

### Export to ICS (iCalendar)

```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --output timetable.ics --year 2025
```

ICS format creates calendar events that can be imported into:
- Google Calendar
- Microsoft Outlook
- Apple Calendar
- Any calendar application supporting iCalendar format

Features:
- Events automatically scheduled on correct dates and times
- Exam sessions marked with 🎓 EXAM prefix
- Professor information included in event description
- Special "EXAM" category for filtering exam events

### Combined Options

```bash
python main.py FIP1A_EDT_2025_2026-v12112025.pdf --page 0 --x 50 --y 100 --width 500 --height 600 --output schedule.csv
```

## Output Example

### Console Output
```
=== TIMETABLE ENTRIES ===

Monday:
------------------------------------------------------------
  [Morning (8:30-12:15)] Week 1: Mathematics (Prof. Smith)
  [Afternoon (13:30-17:15)] Week 1: Physics (Prof. Johnson)

Tuesday:
------------------------------------------------------------
  [Morning (8:30-12:15)] Week 1: Chemistry (Prof. Williams)
  [Afternoon (13:30-17:15)] Week 2: Biology (Prof. Brown)
```

### CSV Output
```csv
day,time_slot,week,course,professor
Monday,Morning (8:30-12:15),Week 1,Mathematics,Prof. Smith
Monday,Afternoon (13:30-17:15),Week 1,Physics,Prof. Johnson
Tuesday,Morning (8:30-12:15),Week 1,Chemistry,Prof. Williams
```

## How the Parser Works

The script intelligently parses cell content to separate course names from professor names:

1. **Newline separation**: If cell contains multiple lines, first line is the course, remaining lines are the professor
2. **Pattern matching**: Detects professor names with titles (M., Mme, Dr., etc.) or in parentheses
3. **Fallback**: Uses heuristics based on capitalization and word count

## Finding Coordinates

If automatic table detection fails:

1. Run with `--raw` to see what's being extracted
2. Open the PDF in a viewer with coordinate display (Adobe Acrobat, PDF-XChange)
3. Note the bounding box of your table
4. Use those coordinates with `--x`, `--y`, `--width`, `--height`

Alternatively, use trial and error with the coordinate parameters until the table is properly extracted.

## Dependencies

- **Python 3.13+**
- **pdfplumber**: PDF processing and table extraction library
- **icalendar**: iCalendar file generation for calendar export (optional, only needed for ICS export)

## Project Structure

```
edt-ocr/
├── main.py                          # Main script with parsing logic
├── pyproject.toml                   # Project configuration and dependencies
├── README.md                        # This file
└── FIP1A_EDT_2025_2026-v12112025.pdf  # Example PDF (your timetable)
```

## Troubleshooting

### No tables detected
- Try different pages with `--page N`
- Use coordinate-based extraction with `--x`, `--y`, `--width`, `--height`
- Check if the PDF contains actual tables (not images of tables)
- Use `--raw` to see what's being extracted

### Course and professor not separated correctly
- The parser tries multiple patterns to split course/professor
- If it fails, the full cell content will be in the course field
- You can manually adjust the parsing logic in the `parse_cell_content()` function

### Incorrect table structure
- Use `--raw` to verify the table structure
- Adjust coordinates for more precise extraction
- Check if the PDF has the expected structure (weeks as columns, days as rows)

### Empty cells or missing data
- Some cells might be empty (no class scheduled)
- The parser skips empty cells automatically
- Merged cells in the PDF might cause parsing issues

## Advanced Usage

### Customizing Time Slots

If your timetable has different time slots, you can modify the `time_slots` parameter in the `parse_timetable()` function:

```python
time_slots = ["Morning (8:00-12:00)", "Afternoon (14:00-18:00)", "Evening (18:00-22:00)"]
```

### Customizing Day Names

The parser supports both English and French day names. To add more languages, edit the `days_of_week` list in `parse_timetable()`.

### Using ICS Files

After generating the ICS file:

1. **Google Calendar**: 
   - Go to Google Calendar
   - Click the "+" next to "Other calendars"
   - Select "Import"
   - Upload the .ics file

2. **Outlook**:
   - File → Open & Export → Import/Export
   - Select "Import an iCalendar (.ics) file"
   - Choose the file

3. **Apple Calendar**:
   - Double-click the .ics file
   - Or File → Import → select the file

4. **Mobile Devices**:
   - Email the .ics file to yourself
   - Open on mobile and import to your calendar app

## License

MIT

## Contributing

Feel free to open issues or submit pull requests for improvements!
