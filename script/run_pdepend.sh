#!/usr/bin/env bash

# Read file and directory paths into an array
IFS=$'\n' read -d '' -r -a files < php_paths

# Join array elements with a comma
file_list=$(
    IFS=,
    echo "${files[*]}"
)

# Run PDepend with the file list
vendor/bin/pdepend --quiet --summary-xml=var/cache/pdepend/summary.xml "${file_list}"
