#!/bin/bash
# Build and push the docker image for PHP8
# If this is the "master" git branch, push tag and push 'latest' (upon user confirmation)
# Regardless of branch, also tag and push version (e.g "1.9.1").
# If not on "master", also tag and push the branch (e.g. "dev") (upon user confirmation)
# Should we build and push for different version of PHP or the base image?
# We could create an image for Ubuntu/Apache and another for Alpine/Nginx.
#
# NOTE: For now, I will use this script to manage building, tagging and pushing images
# to dockerhub.  But, I may want to use a Github action to do this for me.  Not sure
# if it will be this script or something else...

# prompt user for confirmation
function yes_or_no {
  while true; do
    read -p "$* [y/n]: " yn
    case $yn in
      [Yy]*) return 0  ;;  
      [Nn]*) return  1 ;;
    esac
  done
}

# Which git branch are we on?
branch=$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
echo "Git branch is '$branch'."

# If we are on master branch, do we want to include the "latest" tag?
doTagBranch=0
tagBranch=""
if [ "$branch" == "master" ]; then
  yes_or_no "Do you want to use the 'latest' tag?" && doTagBranch=1 && tagBranch="latest"
else
  yes_or_no "Do you want to use the '$branch' tag?" && doTagBranch=1 && tagBranch=$branch
fi

# Get version number from includes/config.php
# Should be in the format like "1.9.1"
version=`grep 'PROGRAM_VERSION = ' ../includes/config.php | awk '{ print $3 }' | tr -d "'v;"`
echo "Version: $version"
#version=1.9.1

cd ..
tagBranchParam=""
if [ "$doTagBranch" == "1" ]; then
  tagBranchParam="k5nus/webcalendar:$tagBranch"
  echo "Tagging and pushing '$tagBranch' tag"
fi
echo docker build $tagBranchParam -t k5nus/webcalendar:$version -f docker/Dockerfile-php8 .
docker build $tagBranchParam -t k5nus/webcalendar:$version -f docker/Dockerfile-php8 .
if [ "$doTagBranch" == "1" ]; then
  echo "Pushing $tagBranch"
  docker push k5nus/webcalendar:$tagBranch
fi
echo "Pushing $version"
docker push k5nus/webcalendar:$version

exit 0

