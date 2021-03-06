#!/bin/sh

# This script packs the relevant files of the LinkTitles
# extension into two archive files that contain the current
# git tag as the version number.

tar cvzf release/LinkTitles-`git describe --tags --abbrev=0`.tar.gz gpl-*.txt NEWS LinkTitles.* --exclude '*~' --transform 's,^,LinkTitles/,'
