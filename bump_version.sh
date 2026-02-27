#!/bin/bash
#
# bump_version.sh - Update WebCalendar version number across all project files
#
# Usage:
#   ./bump_version.sh              # Auto-increment patch version (e.g. v1.9.13 -> v1.9.14)
#   ./bump_version.sh v1.9.14     # Set a specific version
#   ./bump_version.sh -p          # Print current version and exit
#
# Run this script before making a release. It updates the version number in:
#   - wizard/shared/default_config.php  (WEBCAL_PROGRAM_VERSION)
#   - wizard/shared/upgrade_matrix.php  (PROGRAM_VERSION)
#   - includes/config.php               (PROGRAM_VERSION and PROGRAM_DATE)
#   - UPGRADING.html                    (version table)
#   - composer.json                     (version field)
#   - .npmrc                            (init-version)
#   - wizard/shared/tables-*.sql        (WEBCAL_PROGRAM_VERSION INSERT)
#   - wizard/shared/tables-sqlite*.php  (WEBCAL_PROGRAM_VERSION INSERT)
#   - wizard/shared/upgrade-sql.php     (new version entry)
#   - wizard/index.php                  (PROGRAM_VERSION const)
#   - wizard/headless.php               (PROGRAM_VERSION const)
#   - wizard/wizard.js                  (programVersion fallback)
#   - wizard/WizardState.php            (programVersion fallback)

# Function to bump version number
bump_version() {
    local version="$1"
    local major minor patch
    IFS='.' read -ra ADDR <<< "${version#v}"
    major=${ADDR[0]}
    minor=${ADDR[1]}
    patch=${ADDR[2]}
    patch=$((patch + 1))
    echo "v$major.$minor.$patch"
}

# Function to update version in wizard/shared/default_config.php
update_default_config_version() {
    local new_version="$1"
    sed -i -E "s/('WEBCAL_PROGRAM_VERSION' => ')[^']*(')/\1$new_version\2/" wizard/shared/default_config.php
}

# Function to update version and date in includes/config.php
update_config_php() {
    local file_path="includes/config.php"
    local new_version="$1"
    local new_date=$(date +"%d %b %Y")

    # Update version
    sed -i "/^ *\$PROGRAM_VERSION\s*=\s*/s/'[^']*'/'$new_version'/" "$file_path"

    # Update date
    sed -i "/^ *\$PROGRAM_DATE =\s*/s/'[^']*'/'$new_date'/" "$file_path"

    echo "Updated $file_path to version $new_version and date $new_date"
}

# Function to update version in UPGRADING.html
update_upgrading_html() {
    local file_path="UPGRADING.html"
    local new_version="$1"
    local version_without_v="${new_version#v}" # removes 'v' prefix for versions like v1.9.1

    # Get the line number containing the version
    local line_num=$(grep -nE '<th>WebCalendar Version:</th>' "$file_path" | cut -d: -f1)
    # Add 1 to the line number to target the next line
    ((line_num++))

    # If we found the line, update the version on that line
    if [[ -n "$line_num" ]]; then
        sed -i "${line_num}s|<td>[^<]*</td>|<td>$version_without_v</td>|" "$file_path"
    fi

    echo "Updated $file_path to version $new_version"
}

# Function to update version in composer.json
update_composer_json() {
    local file_path="composer.json"
    local new_version="$1"
    local version_without_v="${new_version#v}" # removes 'v' prefix for versions like v1.9.1

    # Use jq to update the version key in the JSON file
    jq ".version = \"$version_without_v\"" "$file_path" > "$file_path.tmp" && mv "$file_path.tmp" "$file_path"

    echo "Updated $file_path to version $new_version"
}

# Function to update version in wizard/shared/upgrade_matrix.php
function update_upgrade_matrix() {
    local NEW_VERSION="$1"
    local file_path="wizard/shared/upgrade_matrix.php"

    # Using sed to replace the version
    sed -i "3s/\$PROGRAM_VERSION = '.*';/\$PROGRAM_VERSION = '$NEW_VERSION';/" "$file_path"
    echo "Updated $file_path to version $NEW_VERSION"
}

# Function to update version in .npmrc
update_npmrc_version() {
    local new_version="$1"
    sed -i -E "s/(init-version = )[^ ]+/\1$new_version/" .npmrc
}

# Function to update version in SQL and PHP schema files
update_sql_files() {
    local new_version="$1"
    # Update .sql files
    sed -i -E "s/('WEBCAL_PROGRAM_VERSION',\s*)'[^']*'/\1'$new_version'/g" wizard/shared/tables-*.sql
    # Update .php schema files (SQLite)
    sed -i -E "s/('WEBCAL_PROGRAM_VERSION',\s*)'[^']*'/\1'$new_version'/g" wizard/shared/tables-sqlite*.php
    echo "Updated SQL and PHP schema files to version $new_version"
}

# Function to update wizard/shared/upgrade-sql.php
update_upgrade_sql_file() {
    local new_version="$1"
    local file_path="wizard/shared/upgrade-sql.php"
    
    # Check if version already exists in the file
    if grep -q "'version' => '$new_version'" "$file_path"; then
        echo "Version $new_version already exists in $file_path"
        return
    fi

    # Add new version placeholder before the final ];
    sed -i "/^];/i \  [\n    'version' => '$new_version',\n    'default-sql' => ''\n  ]," "$file_path"
    echo "Added version $new_version placeholder to $file_path"
}

# Function to update wizard related files
update_wizard_files() {
    local new_version="$1"
    
    # Update wizard/index.php
    sed -i "s/const PROGRAM_VERSION = '.*';/const PROGRAM_VERSION = '$new_version';/" wizard/index.php
    
    # Update wizard/headless.php
    sed -i "s/const PROGRAM_VERSION = '.*';/const PROGRAM_VERSION = '$new_version';/" wizard/headless.php
    
    # Update wizard/wizard.js
    sed -i "s/this.programVersion = options.programVersion || '.*';/this.programVersion = options.programVersion || '$new_version';/" wizard/wizard.js

    # Update wizard/WizardState.php
    sed -i "s/\$this->programVersion = '.*'; \/\/ Fallback/\$this->programVersion = '$new_version'; \/\/ Fallback/" wizard/WizardState.php

    echo "Updated wizard files to version $new_version"
}

# Function to print current version
print_version() {
    local version
    version=$(grep 'WEBCAL_PROGRAM_VERSION' wizard/shared/default_config.php | sed -E "s/.*'WEBCAL_PROGRAM_VERSION' => '([^']*)'.*/\1/")
    echo "$version" | tr -d v
}

# Main logic
if [ "$1" == "-p" ]; then
    # If the -p argument is provided, just print the current version and exit
    print_version
    exit 0
elif [ "$#" -eq 0 ]; then
    # No arguments provided, bump the version
    current_version=$(grep 'WEBCAL_PROGRAM_VERSION' wizard/shared/default_config.php | sed -E "s/.*'WEBCAL_PROGRAM_VERSION' => '([^']*)'.*/\1/")
    new_version=$(bump_version "$current_version")
else
    # Argument provided, use it as the new version
    new_version="$1"
fi

update_default_config_version "$new_version"
update_config_php "$new_version"
update_upgrading_html "$new_version"
update_composer_json "$new_version"
update_upgrade_matrix "$new_version"
update_npmrc_version "$new_version"
update_sql_files "$new_version"
update_upgrade_sql_file "$new_version"
update_wizard_files "$new_version"

echo ""
echo "Files updated to version $new_version"
