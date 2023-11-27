#!/bin/bash

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

# Function to update version in install/default_config.php
update_default_config_version() {
    local new_version="$1"
    sed -i -E "s/('WEBCAL_PROGRAM_VERSION' => ')[^']*(')/\1$new_version\2/" install/default_config.php
}

update_sql_version() {
    local file_path="$1"
    local new_version="$2"
    
    # Get the last two lines of the file
    local last_two_lines=$(tail -n 2 "$file_path")
    
    # Check if the last two lines contain version strings.
    if [[ $(echo "$last_two_lines" | head -n 1) == *upgrade* && $(echo "$last_two_lines" | tail -n 1) == *upgrade* ]]; then
        # If both lines are versions, replace the version in the last line.
        sed -i "$ s/.*/\/\*upgrade_${new_version}\*\//g" "$file_path"
    else
        # If not, append a new line with the version.
        echo "/*upgrade_${new_version}*/" >> "$file_path"
    fi

    echo "Updated $file_path to version $new_version"
}



# SQL files to update
declare -a sql_files=(
    "install/sql/upgrade-db2.sql"
    "install/sql/upgrade-ibase.sql"
    "install/sql/upgrade-mssql.sql"
    "install/sql/upgrade-mysql.sql"
    "install/sql/upgrade-oracle.sql"
    "install/sql/upgrade-postgres.sql"
    "install/sql/upgrade.sql"
)

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

function update_upgrade_matrix() {
    local NEW_VERSION="$1"
    local file_path="install/sql/upgrade_matrix.php"

    # Using sed to replace the version
    sed -i "3s/\$PROGRAM_VERSION = '.*';/\$PROGRAM_VERSION = '$NEW_VERSION';/" "$file_path"
    echo "Updated $file_path to version $new_version"
}

update_npmrc_version() {
    local new_version="$1"
    sed -i -E "s/(init-version = )[^ ]+/\1$new_version/" .npmrc
}

# Function to print current version
print_version() {
    local version
    version=$(grep 'WEBCAL_PROGRAM_VERSION' install/default_config.php | sed -E "s/.*'WEBCAL_PROGRAM_VERSION' => '([^']*)'.*/\1/")
    echo "$version" | tr -d v
}

# Main logic
if [ "$1" == "-p" ]; then
    # If the -p argument is provided, just print the current version and exit
    print_version
    exit 0
elif [ "$#" -eq 0 ]; then
    # No arguments provided, bump the version
    current_version=$(grep 'WEBCAL_PROGRAM_VERSION' install/default_config.php | sed -E "s/.*'WEBCAL_PROGRAM_VERSION' => '([^']*)'.*/\1/")
    new_version=$(bump_version "$current_version")
else
    # Argument provided, use it as the new version
    new_version="$1"
fi

update_default_config_version "$new_version"

for file in "${sql_files[@]}"; do
    update_sql_version "$file" "$new_version"
    echo "Updated $file to version $new_version"
done

update_config_php "$new_version"
update_upgrading_html "$new_version"
update_composer_json "$new_version"
update_upgrade_matrix "$new_version"
update_npmrc_version "$new_version"

echo ""
echo "Files updated to version $new_version"

