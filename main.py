#!/usr/bin/env python3
"""
PDF Timetable Extractor
Reads a PDF file containing a timetable and extracts course and professor data.

Structure:
- Columns: Weeks
- Rows: Days of the week
- Time slots: Morning (8:30-12:15) and Afternoon (13:30-17:15)
- Cell format: Course name + Professor name
"""

import argparse
import json
import re
import sys
from datetime import datetime, timedelta
from math import e
from pathlib import Path
from typing import Dict, List, Optional

try:
    import pdfplumber
except ImportError:
    print("Error: pdfplumber is not installed. Install it with: pip install pdfplumber")
    sys.exit(1)

try:
    from icalendar import Calendar, Event
except ImportError:
    print("Warning: icalendar is not installed. ICS export will not be available.")
    print("Install it with: pip install icalendar")
    Calendar = None
    Event = None


class TimetableEntry:
    """Represents a single timetable entry"""

    def __init__(
        self, day: str, time_slot: str, week: str, course: str, professor: str
    ):
        self.day = day
        self.time_slot = time_slot
        self.week = week
        self.course = course
        self.professor = professor

    def to_dict(self) -> Dict[str, str]:
        return {
            "day": self.day,
            "time_slot": self.time_slot,
            "week": self.week,
            "course": self.course,
            "professor": self.professor,
        }

    def __repr__(self):
        return f"{self.day} {self.time_slot} (Week {self.week}): {self.course} - {self.professor}"


def parse_timetable(
    table: List[List[str | None]], time_slots: Optional[Dict[str, str]] = None
) -> List[TimetableEntry]:
    """
    Parse a timetable into structured entries.

    Args:
        table: Raw table data (list of lists)
        time_slots: List of time slot labels (default: ["Morning 8:30-12:15", "Afternoon 13:30-17:15"])

    Returns:
        List of TimetableEntry objects
    """
    if not table or len(table) < 2:
        return []

    if time_slots is None:
        time_slots = {
            "matin": "Morning (8:30-12:15)",
            "après-midi": "Afternoon (13:30-17:15)",
            "morning": "Morning (8:30-12:15)",
            "afternoon": "Afternoon (13:30-17:15)",
        }

    entries = []

    # Days of week to recognize
    days_of_week = [
        "Lundi",
        "Mardi",
        "Mercredi",
        "Jeudi",
        "Vendredi",
        "Samedi",
        "Dimanche",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
        "Sunday",
    ]

    # Parse the table structure:
    # Row 1: Day name with dates "Lundi 15/9 29/9 13/10..."
    # Row 2: Empty
    # Row 3: "matin" + course data in following rows
    # Row 4+: Course/professor info
    # Row N: "après-midi" + course data in following rows
    # Row N+1+: Course/professor info
    # Then next day starts

    current_day_name = None
    weeks = []
    row_idx = 0

    while row_idx < len(table):
        row = table[row_idx]

        if not row or len(row) == 0:
            row_idx += 1
            continue

        first_col = row[0].strip() if row[0] else ""

        # Check if this is a day row
        is_day_row = any(first_col.startswith(day) for day in days_of_week)

        if is_day_row:
            # Extract day name
            for day in days_of_week:
                if first_col.startswith(day):
                    current_day_name = day
                    break

            # Extract weeks from the dates in this row
            # Format: "Lundi 15/9 29/9 13/10 27/10..."
            # Split by spaces and extract dates
            parts = first_col.split()
            weeks = []
            for part in parts[1:]:  # Skip the day name
                if "/" in part:
                    weeks.append(part)

            row_idx += 1
            continue

        # Check if this is a time slot row (matin or après-midi)
        if first_col.lower() in ["matin", "après-midi", "morning", "afternoon"]:
            if current_day_name:
                time_slot_label = time_slots.get(first_col.lower(), first_col)

                # Collect all rows until we hit another time slot, day, or empty section
                course_rows = []

                # Include the previous row (might contain course names)
                if row_idx > 0:
                    prev_row = table[row_idx - 1]
                    prev_first = prev_row[0].strip() if prev_row and prev_row[0] else ""
                    # Only include if it's not a day name and not another time slot
                    if not any(
                        prev_first.startswith(day) for day in days_of_week
                    ) and prev_first.lower() not in [
                        "matin",
                        "après-midi",
                        "morning",
                        "afternoon",
                    ]:
                        course_rows.append(prev_row)

                temp_idx = row_idx

                # Collect this row and following rows that contain course/professor data
                while temp_idx < len(table):
                    temp_row = table[temp_idx]
                    temp_first = temp_row[0].strip() if temp_row and temp_row[0] else ""

                    # Stop if we hit another time slot or day
                    if temp_first.lower() in [
                        "matin",
                        "après-midi",
                        "morning",
                        "afternoon",
                    ]:
                        if temp_idx != row_idx:  # Don't stop on the current row
                            break
                    elif any(temp_first.startswith(day) for day in days_of_week):
                        break

                    course_rows.append(temp_row)
                    temp_idx += 1

                    # Stop after collecting a few rows (usually course + professor = 2-3 rows max, or +1 for exam)
                    if temp_idx - row_idx > 5:
                        break

                # Now parse the collected rows to extract course info for each week
                # Each column (after column 0) represents a week
                # We need to find non-empty columns
                if course_rows:
                    first_row = course_rows[0]

                    # Iterate through columns to find weeks with data
                    for col_idx in range(1, len(first_row)):
                        # Collect all non-empty cells in this column across all course_rows
                        column_data = []
                        for row_data in course_rows:
                            if col_idx < len(row_data) and row_data[col_idx]:
                                cell_text = row_data[col_idx].strip()
                                if cell_text:
                                    column_data.append(cell_text)

                        if column_data:
                            # Format can be:
                            # 2 items: course, professor
                            # 3 items: course, professor, Examen (or other note)
                            # Sometimes first item might be empty, so we need to handle that

                            # Filter out empty items first
                            non_empty = [item for item in column_data if item.strip()]
                            if len(non_empty) >= 2:
                                course = non_empty[0]

                                # Check if second line is uppercase (professor name)
                                # If not uppercase, it's a course continuation
                                if non_empty[1][0].isupper():
                                    professor = non_empty[1]
                                    start_idx = 2
                                else:
                                    # Second line is not uppercase, add it to course
                                    course = f"{course} {non_empty[1]}"
                                    professor = ""
                                    start_idx = 2

                                # Check remaining items for exam indicator or professor (3rd+ item)
                                if len(non_empty) > start_idx:
                                    for extra in non_empty[start_idx:]:
                                        extra_lower = extra.lower()
                                        if (
                                            "examen" in extra_lower
                                            or "exam" in extra_lower
                                        ):
                                            # Add exam indicator to course name
                                            course = f"{course} [EXAMEN]"
                                        # If we don't have a professor yet and this item is uppercase, it's the professor
                                        elif (
                                            extra[0].isupper()
                                            and "Droit du" not in extra
                                        ):
                                            professor = non_empty[start_idx]
                            elif len(non_empty) == 1:
                                # Only one item - could be course without professor, or a note
                                course = non_empty[0]
                                professor = ""
                            else:
                                course = ""
                                professor = ""

                            # Determine week - map column index to week
                            # Columns are: 0=day/time, 1=week1, 2=empty, 3=week2, 4=empty, ...
                            # So odd columns have data, even columns (except 0) are empty
                            week_idx = (col_idx - 1) // 2
                            week_name = (
                                weeks[week_idx]
                                if week_idx < len(weeks)
                                else f"Week {week_idx + 1}"
                            )

                            if course:
                                entry = TimetableEntry(
                                    day=current_day_name,
                                    time_slot=time_slot_label,
                                    week=week_name,
                                    course=course,
                                    professor=professor,
                                )
                                entries.append(entry)

                row_idx = temp_idx
            else:
                row_idx += 1
        else:
            row_idx += 1
    return entries


def extract_table_from_pdf(pdf_path: str, page_num: int = 0) -> List[List[str | None]]:
    """
    Extract table data from a PDF file.

    Args:
        pdf_path: Path to the PDF file
        page_num: Page number to extract (0-indexed)

    Returns:
        Raw table data
    """
    pdf_file = Path(pdf_path)

    if not pdf_file.exists():
        print(f"Error: File '{pdf_path}' not found.")
        sys.exit(1)

    with pdfplumber.open(pdf_file) as pdf:
        # print(f"PDF opened: {pdf_file.name}")
        # print(f"Total pages: {len(pdf.pages)}")

        if page_num >= len(pdf.pages):
            print(
                f"Error: Page {page_num} does not exist. PDF has {len(pdf.pages)} pages."
            )
            sys.exit(1)

        page = pdf.pages[page_num]
        # print(f"\n--- Processing Page {page_num + 1} ---")

        # Extract tables
        tables = page.extract_tables()

        if tables:
            # print(f"Found {len(tables)} table(s) on page {page_num + 1}")
            # Return the first table (or you can modify to handle multiple)
            return tables[0]
        else:
            print(f"No tables found on page {page_num + 1}")
            return []


def extract_table_with_coordinates(
    pdf_path: str,
    page_num: int = 0,
    x: Optional[float] = None,
    y: Optional[float] = None,
    width: Optional[float] = None,
    height: Optional[float] = None,
) -> List[List[str | None]]:
    """
    Extract table from specific coordinates in the PDF.

    Args:
        pdf_path: Path to the PDF file
        page_num: Page number (0-indexed)
        x: X coordinate of top-left corner
        y: Y coordinate of top-left corner
        width: Width of the region
        height: Height of the region

    Returns:
        Extracted table data
    """
    pdf_file = Path(pdf_path)

    with pdfplumber.open(pdf_file) as pdf:
        page = pdf.pages[page_num]

        # If coordinates provided, crop the page
        if all(coord is not None for coord in [x, y, width, height]):
            print(
                f"Extracting from region: x={x}, y={y}, width={width}, height={height}"
            )
            if (x is not None and y is not None) and (
                width is not None and height is not None
            ):
                bbox = (x, y, x + width, y + height)
                cropped_page = page.crop(bbox)
                table = cropped_page.extract_table()
            else:
                table = []
        else:
            table = page.extract_table()

        return table if table else []


def print_table(table: List[List[str | None]], max_rows: Optional[int] = None):
    """
    Pretty print a table to console.

    Args:
        table: List of lists representing table rows
        max_rows: Maximum number of rows to display (None for all)
    """
    if not table:
        print("Empty table")
        return

    # Determine column widths
    col_widths = []
    for col_idx in range(len(table[0])):
        max_width = 0
        for row in table:
            if col_idx < len(row) and row[col_idx]:
                cell_width = len(str(row[col_idx]))
                max_width = max(max_width, cell_width)
        col_widths.append(min(max_width, 40))  # Cap at 40 characters

    # Print rows
    rows_to_print = table[:max_rows] if max_rows else table
    for row_idx, row in enumerate(rows_to_print):
        row_str = " | ".join(
            str(cell)[: col_widths[i]].ljust(col_widths[i])
            if cell
            else " " * col_widths[i]
            for i, cell in enumerate(row)
        )
        print(row_str)

        # Print separator after header
        if row_idx == 0:
            print("-" * len(row_str))

    if max_rows and len(table) > max_rows:
        print(f"... ({len(table) - max_rows} more rows)")


def print_entries(entries: List[TimetableEntry]):
    """Print timetable entries in a readable format"""
    if not entries:
        print("No entries found")
        return

    print("\n=== TIMETABLE ENTRIES ===\n")

    # Group by day
    by_day = {}
    for entry in entries:
        if entry.day not in by_day:
            by_day[entry.day] = []
        by_day[entry.day].append(entry)

    for day, day_entries in by_day.items():
        print(f"\n{day}:")
        print("-" * 60)
        for entry in day_entries:
            prof_str = f" ({entry.professor})" if entry.professor else ""
            print(f"  [{entry.time_slot}] Week {entry.week}: {entry.course}{prof_str}")


def save_to_csv(entries: List[TimetableEntry], output_path: str):
    """Save timetable entries to CSV file"""
    import csv

    output_file = Path(output_path)

    with open(output_file, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(
            f, fieldnames=["day", "time_slot", "week", "course", "professor"]
        )
        writer.writeheader()
        for entry in entries:
            writer.writerow(entry.to_dict())

    # print(f"\nTimetable saved to CSV: {output_file}")


def save_to_json(entries: List[TimetableEntry], output_path: str):
    """Save timetable entries to JSON file"""
    output_file = Path(output_path)

    data = [entry.to_dict() for entry in entries]

    with open(output_file, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)

    # print(f"\nTimetable saved to JSON: {output_file}")


def parse_week_date(week_str: str, year: Optional[int] = None) -> Optional[datetime]:
    """
    Parse week date string like '15/9' or '29/9' to datetime.

    Args:
        week_str: Date string in format 'dd/m' or 'dd/mm'
        year: Year to use (defaults to current year or next year if date has passed)

    Returns:
        datetime object
    """
    if year is None:
        year = datetime.now().year

    # Parse the date string
    parts = week_str.strip().split("/")
    if len(parts) != 2:
        return None

    try:
        day = int(parts[0])
        month = int(parts[1])

        # Create datetime
        date = datetime(year, month, day)

        # If the date is in the past and we're looking at months 1-8,
        # it might be next year
        now = datetime.now()
        if date < now and month <= 8:
            date = datetime(year + 1, month, day)

        return date
    except (ValueError, IndexError):
        return None


def get_day_offset(day_name: str) -> int:
    """Get offset from Monday for a given day name"""
    days = {
        "lundi": 0,
        "monday": 0,
        "mardi": 1,
        "tuesday": 1,
        "mercredi": 2,
        "wednesday": 2,
        "jeudi": 3,
        "thursday": 3,
        "vendredi": 4,
        "friday": 4,
        "samedi": 5,
        "saturday": 5,
        "dimanche": 6,
        "sunday": 6,
    }
    return days.get(day_name.lower(), 0)


def save_to_ics(
    entries: List[TimetableEntry], output_path: str, year: Optional[int] = None
):
    """
    Save timetable entries to ICS (iCalendar) file.

    Args:
        entries: List of TimetableEntry objects
        output_path: Path to save ICS file
        year: Year for the timetable (defaults to 2025)
    """
    if Calendar is None or Event is None:
        print("Error: icalendar library not installed. Cannot create ICS file.")
        print("Install with: pip install icalendar")
        return

    if year is None:
        # Try to extract year from entries or use 2025
        year = 2025

    output_file = Path(output_path)

    # Create calendar
    cal = Calendar()
    cal.add("prodid", "-//Timetable Extractor//EN")
    cal.add("version", "2.0")
    cal.add("calscale", "GREGORIAN")
    cal.add("method", "PUBLISH")
    cal.add("x-wr-calname", "Course Timetable")
    cal.add("x-wr-timezone", "Europe/Paris")

    # Time slot mappings
    time_slots = {
        "morning (8:30-12:15)": ("08:30", "12:15"),
        "afternoon (13:30-17:15)": ("13:30", "17:15"),
    }

    for entry in entries:
        # Parse the week date
        event_date = parse_week_date(entry.week, year)
        if not event_date:
            continue

        # Adjust to correct day of week
        day_offset = get_day_offset(entry.day)
        # Get the Monday of that week
        days_since_monday = event_date.weekday()
        monday = event_date - timedelta(days=days_since_monday)
        # Add offset for target day
        target_date = monday + timedelta(days=day_offset)

        # Get time slot
        time_slot_key = entry.time_slot.lower()
        if time_slot_key not in time_slots:
            # Try to match partial
            for key in time_slots:
                if key.split("(")[0].strip() in time_slot_key:
                    time_slot_key = key
                    break

        start_time, end_time = time_slots.get(time_slot_key, ("08:30", "12:15"))

        # Create start and end datetime
        start_hour, start_min = map(int, start_time.split(":"))
        end_hour, end_min = map(int, end_time.split(":"))

        dtstart = datetime(
            target_date.year, target_date.month, target_date.day, start_hour, start_min
        )
        dtend = datetime(
            target_date.year, target_date.month, target_date.day, end_hour, end_min
        )

        # Create event
        event = Event()

        # Set summary (title)
        summary = entry.course
        if "[EXAMEN]" in entry.course:
            summary = f"🎓 EXAM: {entry.course.replace('[EXAMEN]', '').strip()}"

        event.add("summary", summary)
        event.add("dtstart", dtstart)
        event.add("dtend", dtend)

        # Add description
        description_parts = []
        if entry.professor:
            description_parts.append(f"teacher: {entry.professor}")
        description_parts.append(f"Time: {entry.time_slot}")
        description_parts.append(f"Week: {entry.week}")

        if "[EXAMEN]" in entry.course:
            description_parts.append("\n⚠️ EXAMINATION SESSION")

        event.add("description", "\n".join(description_parts))

        # Add location (can be customized)
        event.add("location", "Campus")

        # Add categories
        categories = [entry.course.split()[0]]  # First word as category
        if "[EXAMEN]" in entry.course:
            categories.append("EXAM")
        event.add("categories", categories)

        # Add to calendar
        cal.add_component(event)

    # Write to file
    with open(output_file, "wb") as f:
        f.write(cal.to_ical())

    # print(f"\nTimetable saved to ICS: {output_file}")
    # print(f"Created {len(entries)} calendar events")
    # print("Import this file into Google Calendar, Outlook, or Apple Calendar")


def main():
    parser = argparse.ArgumentParser(
        description="Extract timetable data from PDF files",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  # Extract timetable from PDF
  python main.py timetable.pdf

  # Extract from specific page
  python main.py timetable.pdf --page 0

  # Extract from specific coordinates
  python main.py timetable.pdf --page 0 --x 50 --y 100 --width 500 --height 600

  # Save to CSV
  python main.py timetable.pdf --output timetable.csv

  # Save to JSON
  python main.py timetable.pdf --output timetable.json

  # Show raw table without parsing
  python main.py timetable.pdf --raw
        """,
    )

    parser.add_argument("pdf_file", help="Path to the PDF file")
    parser.add_argument(
        "--page",
        type=int,
        default=0,
        help="Page number to extract (0-indexed, default: 0)",
    )
    parser.add_argument("--x", type=float, help="X coordinate of table region")
    parser.add_argument("--y", type=float, help="Y coordinate of table region")
    parser.add_argument("--width", type=float, help="Width of table region")
    parser.add_argument("--height", type=float, help="Height of table region")
    parser.add_argument("--output", "-o", help="Output file path (CSV, JSON, or ICS)")
    parser.add_argument(
        "--year",
        type=int,
        help="Year for the timetable (for ICS export, default: 2025)",
    )
    parser.add_argument(
        "--raw", action="store_true", help="Show raw table without parsing"
    )
    parser.add_argument(
        "--max-rows", type=int, help="Maximum rows to display in raw table view"
    )

    args = parser.parse_args()

    # Extract table
    if any([args.x, args.y, args.width, args.height]):
        if not all([args.x, args.y, args.width, args.height]):
            print(
                "Error: All coordinates (x, y, width, height) must be specified together"
            )
            sys.exit(1)

        table = extract_table_with_coordinates(
            args.pdf_file, args.page, args.x, args.y, args.width, args.height
        )
    else:
        table = extract_table_from_pdf(args.pdf_file, args.page)

    if not table:
        print("No table data extracted")
        sys.exit(1)

    # Show raw table if requested
    if args.raw:
        print("\n=== RAW TABLE ===\n")
        print_table(table, max_rows=args.max_rows)
        return

    # Parse timetable
    # print("\nParsing timetable...")
    entries = parse_timetable(table)

    # print(f"\nExtracted {len(entries)} timetable entries")

    # Display entries
    # print_entries(entries)

    # Determine output format
    if args.output:
        output_path = Path(args.output)
        if output_path.suffix.lower() == ".json":
            save_to_json(entries, args.output)
        elif output_path.suffix.lower() in [".ics", ".ical"]:
            save_to_ics(entries, args.output, year=args.year)
        else:
            save_to_csv(entries, args.output)
        return

    # Default: Generate JSON output for Laravel integration
    # Write to a temporary file to avoid stdout contamination from library warnings
    events = []
    year = args.year if args.year else 2025

    # Time slot mappings
    time_slots = {
        "morning (8:30-12:15)": ("08:30", "12:15"),
        "afternoon (13:30-17:15)": ("13:30", "17:15"),
    }

    for entry in entries:
        # Parse the week date
        event_date = parse_week_date(entry.week, year)
        if not event_date:
            continue

        # Adjust to correct day of week
        day_offset = get_day_offset(entry.day)
        # Get the Monday of that week
        days_since_monday = event_date.weekday()
        monday = event_date - timedelta(days=days_since_monday)
        # Add offset for target day
        target_date = monday + timedelta(days=day_offset)

        # Get time slot
        time_slot_key = entry.time_slot.lower()
        if time_slot_key not in time_slots:
            # Try to match partial
            for key in time_slots:
                if key.split("(")[0].strip() in time_slot_key:
                    time_slot_key = key
                    break

        start_time, end_time = time_slots.get(time_slot_key, ("08:30", "12:15"))

        # Create start and end datetime
        start_hour, start_min = map(int, start_time.split(":"))
        end_hour, end_min = map(int, end_time.split(":"))

        dtstart = datetime(
            target_date.year, target_date.month, target_date.day, start_hour, start_min
        )
        dtend = datetime(
            target_date.year, target_date.month, target_date.day, end_hour, end_min
        )

        # Convert to format expected by Laravel
        events.append(
            {
                "title": entry.course,
                "teacher": entry.professor if entry.professor else None,
                "description": f"{entry.course} - {entry.professor}"
                if entry.professor
                else entry.course,
                "location": None,  # Not available in PDF
                "start_time": dtstart.isoformat(),
                "end_time": dtend.isoformat(),
                "type": "exam" if "[EXAMEN]" in entry.course else "course",
                "color": None,
            }
        )

        output_data = {
            "events": events,
            "summary": {
                "total": len(events),
                "courses": len([e for e in events if e["type"] == "course"]),
                "exams": len([e for e in events if e["type"] == "exam"]),
            },
        }

        # Write to temporary file instead of stdout to avoid library warnings
        import tempfile

        with tempfile.NamedTemporaryFile(
            mode="w", suffix=".json", delete=False, encoding="utf-8"
        ) as f:
            json.dump(output_data, f, ensure_ascii=False, indent=2)
            output_file = f.name

        # Print only the filename to stdout so Laravel can read it
        print(output_file)


if __name__ == "__main__":
    main()
