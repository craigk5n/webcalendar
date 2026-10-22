#!/usr/bin/env python3
#
# sql2md.py
#
# Description:
#   Create Markdown documentation from a MySQL SQL schema file.
#   Parses CREATE TABLE statements and their comments to produce
#   a WebCalendar-Database.md file.
#
# Usage:
#   python3 docs/sql2md.py < wizard/shared/tables-mysql.sql > docs/WebCalendar-Database.md
#
import sys
import re


def get_version():
    """Read the program version from includes/config.php."""
    try:
        with open('includes/config.php', 'r') as f:
            for line in f:
                m = re.search(r"PROGRAM_VERSION\s*=\s*'([^']*)'", line)
                if m:
                    return m.group(1)
    except FileNotFoundError:
        pass
    return 'Unknown'


def parse_comment_line(line):
    """Extract comment text from a line, stripping leading * characters."""
    text = line.strip()
    text = re.sub(r'^\*+\s?', '', text)
    return text


def parse_sql(input_lines):
    """Parse SQL input and return a dict of table_name -> table info."""
    tables = {}
    in_comment = False
    in_create_table = False
    descr = ''
    table_name = ''
    table_description = ''
    columns = []
    primary_keys = set()

    for line in input_lines:
        line = line.rstrip('\n').rstrip('\r')

        if in_create_table:
            # Continue a multi-line comment inside CREATE TABLE
            if in_comment:
                if '*/' in line:
                    descr += ' ' + parse_comment_line(
                        line[:line.index('*/')]
                    )
                    in_comment = False
                else:
                    descr += ' ' + parse_comment_line(line)
                continue

            # Check for comment-only line(s) inside CREATE TABLE
            # Single-line: /* ... */
            single_cmt = re.match(r'\s*/\*+(.*?)\*/', line)
            if single_cmt:
                descr += ' ' + single_cmt.group(1).strip()
                continue

            # Multi-line comment start: /* ...
            multi_cmt = re.match(r'\s*/\*+(.*)', line)
            if multi_cmt:
                descr = multi_cmt.group(1).strip()
                in_comment = True
                continue

            # INDEX line - skip
            if re.search(r'\bINDEX\b', line, re.IGNORECASE):
                descr = ''
                continue

            # PRIMARY KEY
            pk_match = re.search(
                r'PRIMARY\s+KEY\s*\(\s*(.*?)\s*\)', line, re.IGNORECASE
            )
            if pk_match:
                keys = pk_match.group(1).replace(' ', '').split(',')
                primary_keys.update(keys)
                descr = ''
                continue

            # End of CREATE TABLE
            if re.match(r'\s*\);', line):
                tables[table_name] = {
                    'description': table_description,
                    'columns': columns,
                    'primary_keys': primary_keys,
                }
                in_create_table = False
                descr = ''
                table_name = ''
                table_description = ''
                columns = []
                primary_keys = set()
                continue

            # Column definition
            col_match = re.match(r'\s*(\S+)\s+(\S+)', line)
            if col_match:
                col_name = col_match.group(1)
                col_type_raw = col_match.group(2).rstrip(',')

                # Extract type and size
                size_match = re.match(r'(\w+)\((\d+)\)', col_type_raw)
                if size_match:
                    col_type = size_match.group(1)
                    col_size = size_match.group(2)
                else:
                    col_type = col_type_raw
                    col_size = ''

                # Null
                if re.search(r'\bNOT\s+NULL\b', line, re.IGNORECASE):
                    col_null = 'N'
                else:
                    col_null = 'Y'

                # Default
                def_match = re.search(
                    r"\bDEFAULT\s+(\S+)", line, re.IGNORECASE
                )
                if def_match:
                    col_default = def_match.group(1).rstrip(',')
                else:
                    col_default = ''

                # Clean up description
                col_descr = descr.strip()
                col_descr = re.sub(r'\s+', ' ', col_descr)
                col_descr = convert_html_to_md_inline(col_descr)

                columns.append({
                    'name': col_name,
                    'type': col_type.upper(),
                    'size': col_size,
                    'null': col_null,
                    'default': col_default,
                    'description': col_descr,
                })
                descr = ''

        elif in_comment:
            if '*/' in line:
                descr += ' ' + parse_comment_line(
                    line[:line.index('*/')]
                )
                in_comment = False
            else:
                descr += ' ' + parse_comment_line(line)

        else:
            # Look for CREATE TABLE
            ct_match = re.search(
                r'CREATE\s+TABLE\s+(\S+)', line, re.IGNORECASE
            )
            if ct_match:
                in_create_table = True
                table_name = ct_match.group(1)
                table_description = descr.strip()
                table_description = re.sub(r'\s+', ' ', table_description)
                table_description = convert_html_to_md(table_description)
                descr = ''

            # Single-line comment outside CREATE TABLE
            elif re.search(r'/\*.*\*/', line):
                m = re.search(r'/\*+(.*?)\*/', line)
                if m:
                    descr = m.group(1).strip()

            # Multi-line comment start outside CREATE TABLE
            elif re.search(r'/\*', line):
                m = re.search(r'/\*+(.*)', line)
                if m:
                    descr = m.group(1).strip()
                    in_comment = True

    return tables


def convert_html_to_md(text):
    """Convert simple HTML markup in table/column descriptions to Markdown."""
    # Convert <a href="#table">table</a> to [table](#table)
    text = re.sub(
        r'<a\s+href="([^"]*)"[^>]*>(.*?)</a>',
        r'[\2](\1)',
        text
    )
    # Convert <ul>...</ul> list items
    text = re.sub(r'</?ul>', '', text)
    text = re.sub(r'</?ol>', '', text)
    text = re.sub(r'<li>(.*?)</li>', r'  - \1', text)
    # Strip remaining HTML tags
    text = re.sub(r'<[^>]+>', '', text)
    # Clean up whitespace
    text = re.sub(r'\s+', ' ', text).strip()
    return text


def convert_html_to_md_inline(text):
    """Convert HTML in column descriptions for table cells."""
    text = re.sub(
        r'<a\s+href="([^"]*)"[^>]*>(.*?)</a>',
        r'[\2](\1)',
        text
    )
    # Convert list items to comma-separated values for table cells
    items = re.findall(r'<li>(.*?)</li>', text)
    if items:
        text = re.sub(
            r'<ul>.*?</ul>', ', '.join(items), text, flags=re.DOTALL
        )
        text = re.sub(
            r'<ol>.*?</ol>', ', '.join(items), text, flags=re.DOTALL
        )
    text = re.sub(r'<[^>]+>', '', text)
    text = re.sub(r'\s+', ' ', text).strip()
    return text


def escape_md_table(text):
    """Escape pipe characters in text destined for a Markdown table cell."""
    return text.replace('|', '\\|')


def generate_markdown(tables, version):
    """Generate the full Markdown document."""
    lines = []

    # Header
    lines.append('# WebCalendar Database Documentation')
    lines.append('')
    lines.append(
        '**Home Page:** [https://k5n.us/webcalendar]'
        '(https://k5n.us/webcalendar)  '
    )
    lines.append(
        '**Author:** [Craig Knudsen](https://k5n.us)  '
    )
    lines.append(f'**Version:** {version}')
    lines.append('')
    lines.append(
        '> This file is generated from '
        '[tables-mysql.sql](../wizard/shared/tables-mysql.sql).  '
    )
    lines.append(
        '> Below are the definitions of all WebCalendar tables, along with '
        'some descriptions of'
    )
    lines.append(
        '> how each table is used. Column names shown in **bold** are '
        'primary keys for that table.'
    )
    lines.append('')
    lines.append(
        '> If you update the SQL for WebCalendar, use the '
        '[sql2md.py](sql2md.py) script to regenerate this file.'
    )
    lines.append('')

    # Table of contents
    sorted_names = sorted(tables.keys())
    lines.append('## List of Tables')
    lines.append('')
    for name in sorted_names:
        lines.append(f'- [{name}](#{name})')
    lines.append('')
    lines.append('---')
    lines.append('')

    # Table definitions
    for name in sorted_names:
        table = tables[name]
        lines.append(f'### {name}')

        if table['description']:
            lines.append(f"> {table['description']}")

        lines.append('')
        lines.append(
            '| Column Name | Type | Length | Null | Default | Description |'
        )
        lines.append(
            '|-------------|------|--------|------|---------|-------------|'
        )

        for col in table['columns']:
            col_name = col['name']
            if col_name in table['primary_keys']:
                col_name = f'**{col_name}**'
            col_type = escape_md_table(col['type'])
            col_size = col['size'] if col['size'] else ' '
            col_null = col['null']
            col_default = (
                escape_md_table(col['default']) if col['default'] else ' '
            )
            col_descr = escape_md_table(col['description'])
            if not col_descr:
                col_descr = ' '
            lines.append(
                f'| {col_name} | {col_type} | {col_size} | '
                f'{col_null} | {col_default} | {col_descr} |'
            )

        lines.append('')
        lines.append('')

    return '\n'.join(lines)


def main():
    input_lines = sys.stdin.readlines()
    tables = parse_sql(input_lines)
    version = get_version()
    output = generate_markdown(tables, version)
    sys.stdout.write(output)


if __name__ == '__main__':
    main()
